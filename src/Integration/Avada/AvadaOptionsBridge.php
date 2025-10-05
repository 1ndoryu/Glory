<?php

namespace Glory\Integration\Avada;

use Glory\Core\OpcionRepository;
use Glory\Manager\OpcionManager;
use Glory\Manager\AssetManager;
use Glory\Integration\Avada\Options\Discovery;
use Glory\Integration\Avada\Options\Normalizer;
use Glory\Integration\Avada\Options\FieldsBuilder;
use Glory\Integration\Avada\Options\Sync;
use Glory\Integration\Avada\Options\Logger;
use Glory\Integration\Avada\Options\Registrar;
use Glory\Integration\Avada\Options\CptSingles;

/**
 * Puente para mostrar opciones de Glory (OpcionManager) dentro del panel de Avada
 * y servir los valores desde el storage de Avada cuando Avada está activo.
 *
 * Enfoque:
 * - Inyectar sección "Glory" en Avada Global Options via filtro avada_options_sections.
 * - Exponer filtros pre_option_* para que las lecturas de OpcionManager (get_option)
 *   se resuelvan desde las opciones globales de Avada si hay valor guardado.
 */
class AvadaOptionsBridge
{
    /**
     * IDs de opciones de Glory que mostraremos en Avada y serviremos desde Avada.
     * Mantener sincronizados con los registros de OpcionManager en App/Config/opcionesTema.php
     */
    private const FALLBACK_OPTION_IDS = [
        'glory_logo_mode',
        'glory_logo_text',
        'glory_logo_image',
        'glory_gsc_verification_code',
        'glory_ga4_measurement_id',
        'glory_custom_header_scripts',
    ];

    public static function register(): void
    {
        Registrar::register();
        // Forzar 100% width en singles de CPTs propios cuando su opción esté activa
        add_filter('fusion_is_hundred_percent_template', [self::class, 'forceHundredPercentForCpt'], 99, 2);
    }

    /**
     * Devuelve el valor de una opción desde Avada si existe, si no, deja seguir el flujo normal.
     *
     * @param mixed  $pre
     * @param string $option
     * @param mixed  $default
     * @return mixed
     */
    public static function filterPreOptionFromAvada($pre, $option, $default)
    {
        return Sync::filterPreOptionFromAvada($pre, $option, $default);
    }

    /**
     * Espeja una actualización de opción de Glory hacia las Global Options de Avada.
     *
     * @param mixed  $value     Nuevo valor
     * @param mixed  $old_value Valor anterior
     * @param string $option    Nombre de la opción (glory_*)
     * @return mixed Devuelve $value para no interrumpir el flujo normal de update_option()
     */
    public static function mirrorUpdateToAvada($value, $old_value, $option)
    {
        return Sync::mirrorUpdateToAvada($value, $old_value, $option);
    }

    /**
     * Espeja el borrado de una opción Glory desde el storage original hacia Avada.
     *
     * @param string $option Nombre de la opción (solo informativo)
     * @return void
     */
    public static function mirrorDeleteFromAvada($option): void
    {
        Sync::mirrorDeleteFromAvada($option);
    }

    /**
     * Inyecta la sección Glory con campos mapeados.
     *
     * @param array $sections
     * @return array
     */
    public static function injectGlorySection(array $sections): array
    {
        self::log('injectGlorySection:start');
        $fields = FieldsBuilder::buildFields();
        if (empty($fields)) {
            self::log('injectGlorySection:no_fields');
            // Aunque no haya campos Glory, aún podemos inyectar secciones por CPT.
            // Continuamos sin retornar.
        }

        // Sección raíz Glory con subsecciones
        $sections['glory'] = [
            'label'    => esc_html__('Glory', 'glory-ab'),
            'id'       => 'glory',
            'priority' => 28,
            'icon'     => 'fusiona-code',
            'fields'   => is_array($fields) ? $fields : [],
        ];

        // Inyectar secciones por CPT al mismo nivel que las secciones nativas de Avada
        $sections = CptSingles::injectCptSections($sections);

        self::log('injectGlorySection:done', ['fields_count' => is_array($fields) ? count($fields) : 0]);
        return $sections;
    }

    /**
     * Registra los filtros de lectura/escritura/borrado para todas las opciones descubiertas.
     * Usa fallback si no se puede descubrir dinámicamente.
     */
    public static function bootstrapOptionFilters(): void
    {
        $ids = Discovery::discoverOptionIds();
        if (empty($ids)) {
            $ids = Discovery::getFallbackOptionIds();
            self::log('bootstrapOptionFilters:fallback_ids', ['count' => count($ids)]);
        } else {
            self::log('bootstrapOptionFilters:discovered_ids', ['count' => count($ids)]);
        }
        foreach ($ids as $optionId) {
            if (Discovery::shouldExcludeById($optionId)) {
                self::log('bootstrapOptionFilters:exclude', ['id' => $optionId]);
                continue;
            }
            // Solo lectura previa de glory_* desde Avada para no tocar fusion_options al guardar.
            add_filter('pre_option_' . $optionId, [self::class, 'filterPreOptionFromAvada'], 10, 3);
            // No interceptamos pre_update/delete para evitar escribir en fusion_options y no afectar opciones nativas.
            // Interceptar lectura de Avada para que use el valor real del repositorio de Glory.
            add_filter('avada_setting_get_' . $optionId, function($current) use ($optionId) {
                try {
                    $repoVal = OpcionRepository::get($optionId);
                    if ($repoVal !== OpcionRepository::getCentinela()) {
                        return self::normalizeOptionValueForWrite($optionId, $repoVal);
                    }
                } catch (\Throwable $t) {
                    // noop
                }
                return $current;
            }, 10, 1);
            self::log('bootstrapOptionFilters:registered', ['id' => $optionId]);
        }

        // Registrar filtros para opciones dinámicas de CPT (glory_{pt}_single_*)
        CptSingles::registerFilters();
    }

    /**
     * Permite registrar filtros para una opción individual cuando OpcionManager lo anuncie.
     * @param string $id
     * @param array  $def
     */
    public static function registerFiltersForOption($id, $def = []): void
    {
        $optionId = (string) $id;
        if ($optionId === '' || Discovery::shouldExcludeById($optionId)) {
            return;
        }
        add_filter('pre_option_' . $optionId, [self::class, 'filterPreOptionFromAvada'], 10, 3);
        // No interceptamos pre_update/delete para evitar escribir en fusion_options.
        self::log('registerFiltersForOption:hooked', ['id' => $optionId]);
    }

    /**
     * Handler cuando Avada guarda opciones globales. Sincroniza solo claves glory_* al repositorio.
     * @param array $data          Todas las opciones guardadas
     * @param array $changed_values Valores cambiados reportados por Avada
     */
    public static function onFusionOptionsSaved($data, $changed_values): void
    {
        try {
            Sync::handleFusionOptionsSaved(is_array($data) ? $data : [], is_array($changed_values) ? $changed_values : []);
        } catch (\Throwable $t) {
            // noop
        }
    }

    /**
     * Descubre IDs de opciones consultando el registro de OpcionManager.
     * @return array
     */
    private static function discoverOptionIds(): array
    {
        return Discovery::discoverOptionIds();
    }

    private static function getFallbackOptionIds(): array
    {
        return Discovery::getFallbackOptionIds();
    }

    private static function shouldExcludeById(string $id): bool
    {
        return Discovery::shouldExcludeById($id);
    }

    /**
     * Normaliza el valor leído desde Avada para el consumo de OpcionManager/get_option.
     * Convierte strings '0'/'1' en booleanos para opciones booleanas.
     * @param string $id
     * @param mixed  $value
     * @return mixed
     */
    private static function normalizeOptionValueForRead(string $id, $value)
    {
        return Normalizer::normalizeOptionValueForRead($id, $value);
    }

    /**
     * Normaliza el valor que vamos a escribir en Avada para que sea consistente (1/0 para booleanas).
     * @param string $id
     * @param mixed  $value
     * @return mixed
     */
    private static function normalizeOptionValueForWrite(string $id, $value)
    {
        return Normalizer::normalizeOptionValueForWrite($id, $value);
    }

    /**
     * Determina si una opción es booleana por convención de nombre o por su tipo registrado.
     * @param string $id
     */
    private static function isBooleanOption(string $id): bool
    {
        return Normalizer::isBooleanOption($id);
    }

    /**
     * Obtiene el tipo de una opción desde el registro descubierto, cacheado en memoria.
     * @param string $id
     * @return string|null
     */
    private static function getOptionType(string $id): ?string
    {
        return Discovery::getOptionType($id);
    }

    private static function getGloryOptionDefinition(string $id): ?array
    {
        return Discovery::getGloryOptionDefinition($id);
    }

    /**
     * Construye los campos del panel de Avada a partir de opciones de Glory conocidas.
     * Idealmente esto debería descubrir dinámicamente el registro de OpcionManager,
     * pero en ausencia de esa API, mapeamos claves usadas actualmente.
     *
     * @return array
     */
    private static function buildFields(): array
    {
        return FieldsBuilder::buildFields();
    }

    /**
     * Intenta descubrir dinámicamente las opciones registradas en OpcionManager.
     * Devuelve un array de opciones con llaves: id,tipo,etiqueta,descripcion,valorDefault,opciones,seccion,subSeccion,etiquetaSeccion
     *
     * @return array
     */
    private static function gatherOptionsFromOpcionManager(): array
    {
        return Discovery::gatherOptionsFromOpcionManager();
    }

    /**
     * Fallback para extraer opciones desde archivos de configuración del tema.
     * Busca llamadas a OpcionManager::register('id', [ ... ]);
     * e intenta parsear el array literal a un array PHP seguro.
     *
     * @return array
     */
    private static function gatherOptionsFromFiles(): array
    {
        return Discovery::gatherOptionsFromFiles();
    }

    /**
     * Parser simple de arrays literales para claves comunes.
     * No ejecuta código ni evalúa variables.
     */
    private static function parseArrayLiteral(string $literal, string $id): array
    {
        return Discovery::parseArrayLiteral($literal, $id);
    }

    /**
     * Normaliza el registro de opciones a un formato estándar.
     *
     * @param array $raw
     * @return array
     */
    private static function normalizeRegistry(array $raw): array
    {
        return Discovery::normalizeRegistry($raw);
    }

    /**
     * Determina si debemos excluir una opción del panel (p.ej., opciones de header cuando Avada está activo).
     *
     * @param array $opt
     * @return bool
     */
    private static function shouldExcludeOption(array $opt): bool
    {
        return FieldsBuilder::shouldExcludeOption($opt);
    }

    /**
     * Mapea una definición de OpcionManager a un campo de Avada.
     *
     * @param array $opt
     * @return array|null
     */
    private static function mapGloryOptionToAvadaField(array $opt): ?array
    {
        return FieldsBuilder::mapGloryOptionToAvadaField($opt);
    }

    /**
     * Log de depuración de cambios en fusion_options (solo claves glory_*).
     */
    public static function debugLogFusionOptionsUpdate($value, $old_value, $option)
    {
        return Sync::debugLogFusionOptionsUpdate($value, $old_value, $option);
    }

    /**
     * Al leer el array de opciones globales de Avada, mergeamos los valores de Glory desde su repositorio
     * (glory_opcion_*) para que el panel muestre el estado real persistido cuando Avada no haya guardado las claves.
     *
     * @param mixed $value Valor que Avada está a punto de retornar (array de opciones globales)
     * @return mixed
     */
    public static function filterMergeGloryValuesIntoAvadaOptions($value)
    {
        return Sync::filterMergeGloryValuesIntoAvadaOptions($value);
    }

    /**
     * Si el CPT actual tiene activa la opción global "100% Width Page", forzar el layout a 100%.
     *
     * @param bool        $value   Valor actual del filtro.
     * @param int|false   $page_id ID de página si Avada lo pasó.
     */
    public static function forceHundredPercentForCpt($value, $page_id = false)
    {
        try {
            if ($value) {
                return $value;
            }
            $pid = is_numeric($page_id) ? (int) $page_id : 0;
            if ($pid <= 0 && function_exists('fusion_library')) {
                $pid = (int) fusion_library()->get_page_id();
            }
            if ($pid <= 0) {
                return $value;
            }
            $pt = get_post_type($pid);
            if (!is_string($pt) || $pt === '' || in_array($pt, ['post','product','avada_portfolio'], true)) {
                return $value;
            }
            $optId = 'glory_' . sanitize_key($pt) . '_single_width_100';
            $on = get_option($optId, 0);
            if ((string) $on === '1' || (int) $on === 1) {
                return true;
            }
        } catch (\Throwable $t) {
            // noop
        }
        return $value;
    }

    private static function log(string $message, array $context = []): void
    {
        Logger::log($message, $context);
    }
}


