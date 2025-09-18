<?php

namespace Glory\Integration\Avada;

use Glory\Core\OpcionRepository;
use Glory\Manager\OpcionManager;
use Glory\Manager\AssetManager;

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
        // Inyectar sección y campos en Avada Options.
        add_filter('avada_options_sections', [self::class, 'injectGlorySection']);

        // Registrar filtros dinámicamente a partir del registro de OpcionManager cuando WP esté listo.
        add_action('init', [self::class, 'bootstrapOptionFilters'], 20);
        add_action('after_setup_theme', [self::class, 'bootstrapOptionFilters'], 20);
        add_action('wp_loaded', [self::class, 'bootstrapOptionFilters'], 20);
        // Si OpcionManager emite un hook por cada registro, engancharlo para cubrir opciones nuevas al vuelo.
        add_action('glory_opcion_registered', [self::class, 'registerFiltersForOption'], 10, 2);

        // Hook de depuración: observar cambios en fusion_options (registro de Avada) para claves glory_*.
        add_action('init', function() {
            if (class_exists('Fusion_Settings')) {
                try {
                    $optionName = \Fusion_Settings::get_option_name();
                    add_filter('pre_update_option_' . $optionName, [self::class, 'debugLogFusionOptionsUpdate'], 10, 3);
                    // Al leer las opciones globales de Avada, inyectar/mergear los valores de Glory desde su repositorio
                    // para que el panel muestre el estado real guardado en PROD.
                    add_filter('option_' . $optionName, [self::class, 'filterMergeGloryValuesIntoAvadaOptions'], 10, 1);
                } catch (\Throwable $t) {
                    // noop
                }
            }
        }, 5);
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
        // Asegurar dependencias de Avada.
        if (!class_exists('Fusion_Settings')) {
            return $pre;
        }
        try {
            // Leer del array global de opciones de Avada.
            $optionName = \Fusion_Settings::get_option_name();
            $globals    = get_option($optionName, []);
            if (is_array($globals) && array_key_exists($option, $globals)) {
                $value = self::normalizeOptionValueForRead((string) $option, $globals[$option]);
                if (self::isBooleanOption((string) $option)) {
                    // En pre_option debemos devolver 1/0 para no caer en defaults por un false booleano.
                    $ret = $value ? 1 : 0;
                    self::log('filterPreOptionFromAvada:boolean', ['id' => $option, 'stored' => $globals[$option], 'normalized' => $ret]);
                    return $ret;
                }
                self::log('filterPreOptionFromAvada:value', ['id' => $option, 'stored' => $globals[$option], 'normalized' => $value]);
                return $value;
            }
            self::log('filterPreOptionFromAvada:miss', ['id' => $option]);
        } catch (\Throwable $t) {
            // Silencioso: si algo falla, no bloquear lecturas normales.
            self::log('filterPreOptionFromAvada:error', ['id' => (string) $option, 'err' => $t->getMessage()]);
        }
        return $pre;
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
        if (!class_exists('Fusion_Settings')) {
            return $value;
        }
        try {
            $optionName = \Fusion_Settings::get_option_name();
            $globals    = get_option($optionName, []);
            if (!is_array($globals)) {
                $globals = [];
            }
            // Si Avada ya tiene un valor para esta clave, no lo sobreescribimos desde Glory.
            if (is_array($globals) && array_key_exists($option, $globals)) {
                self::log('mirrorUpdateToAvada:skip_existing', ['id' => $option, 'existing' => $globals[$option], 'incoming' => $value]);
                return $value;
            }
            $globals[$option] = self::normalizeOptionValueForWrite((string) $option, $value);
            self::log('mirrorUpdateToAvada:write', ['id' => $option, 'write' => $globals[$option]]);
            update_option($optionName, $globals);
        } catch (\Throwable $t) {
            // Silencioso: no romper la escritura original.
            self::log('mirrorUpdateToAvada:error', ['id' => (string) $option, 'err' => $t->getMessage()]);
        }
        return $value;
    }

    /**
     * Espeja el borrado de una opción Glory desde el storage original hacia Avada.
     *
     * @param string $option Nombre de la opción (solo informativo)
     * @return void
     */
    public static function mirrorDeleteFromAvada($option): void
    {
        if (!class_exists('Fusion_Settings')) {
            return;
        }
        try {
            $optionName = \Fusion_Settings::get_option_name();
            $globals    = get_option($optionName, []);
            if (is_array($globals) && array_key_exists($option, $globals)) {
                unset($globals[$option]);
                update_option($optionName, $globals);
                self::log('mirrorDeleteFromAvada:deleted', ['id' => $option]);
            }
        } catch (\Throwable $t) {
            // Silencioso.
            self::log('mirrorDeleteFromAvada:error', ['id' => (string) $option, 'err' => $t->getMessage()]);
        }
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
        $fields = self::buildFields();
        if (empty($fields)) {
            self::log('injectGlorySection:no_fields');
            return $sections;
        }

        $sections['glory'] = [
            'label'    => esc_html__('Glory', 'glory-ab'),
            'id'       => 'glory',
            'priority' => 28,
            'icon'     => 'fusiona-code',
            // Avada espera arrays indexados para sub-secciones y campos.
            'fields'   => is_array($fields) ? array_values($fields) : $fields,
        ];

        self::log('injectGlorySection:done', ['fields_count' => is_array($fields) ? count($fields) : 0]);
        return $sections;
    }

    /**
     * Registra los filtros de lectura/escritura/borrado para todas las opciones descubiertas.
     * Usa fallback si no se puede descubrir dinámicamente.
     */
    public static function bootstrapOptionFilters(): void
    {
        $ids = self::discoverOptionIds();
        if (empty($ids)) {
            $ids = self::getFallbackOptionIds();
            self::log('bootstrapOptionFilters:fallback_ids', ['count' => count($ids)]);
        } else {
            self::log('bootstrapOptionFilters:discovered_ids', ['count' => count($ids)]);
        }
        foreach ($ids as $optionId) {
            if (self::shouldExcludeById($optionId)) {
                self::log('bootstrapOptionFilters:exclude', ['id' => $optionId]);
                continue;
            }
            add_filter('pre_option_' . $optionId, [self::class, 'filterPreOptionFromAvada'], 10, 3);
            add_filter('pre_update_option_' . $optionId, [self::class, 'mirrorUpdateToAvada'], 10, 3);
            add_action('delete_option_' . $optionId, [self::class, 'mirrorDeleteFromAvada'], 10, 1);
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
    }

    /**
     * Permite registrar filtros para una opción individual cuando OpcionManager lo anuncie.
     * @param string $id
     * @param array  $def
     */
    public static function registerFiltersForOption($id, $def = []): void
    {
        $optionId = (string) $id;
        if ($optionId === '' || self::shouldExcludeById($optionId)) {
            return;
        }
        add_filter('pre_option_' . $optionId, [self::class, 'filterPreOptionFromAvada'], 10, 3);
        add_filter('pre_update_option_' . $optionId, [self::class, 'mirrorUpdateToAvada'], 10, 3);
        add_action('delete_option_' . $optionId, [self::class, 'mirrorDeleteFromAvada'], 10, 1);
        self::log('registerFiltersForOption:hooked', ['id' => $optionId]);
    }

    /**
     * Descubre IDs de opciones consultando el registro de OpcionManager.
     * @return array
     */
    private static function discoverOptionIds(): array
    {
        $options = self::gatherOptionsFromOpcionManager();
        if (empty($options)) {
            return [];
        }
        $ids = [];
        foreach ($options as $opt) {
            if (isset($opt['id']) && is_string($opt['id']) && $opt['id'] !== '') {
                $ids[] = $opt['id'];
            }
        }
        return array_values(array_unique($ids));
    }

    private static function getFallbackOptionIds(): array
    {
        return self::FALLBACK_OPTION_IDS;
    }

    private static function shouldExcludeById(string $id): bool
    {
        return in_array($id, ['glory_logo_mode','glory_logo_text','glory_logo_image'], true);
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
        if (self::isBooleanOption($id)) {
            if (is_bool($value)) {
                return $value;
            }
            if (is_int($value)) {
                return $value === 1;
            }
            if (is_string($value)) {
                $v = strtolower(trim($value));
                if ($v === '1' || $v === 'true' || $v === 'on' || $v === 'yes') { return true; }
                if ($v === '0' || $v === 'false' || $v === 'off' || $v === 'no' || $v === '') { return false; }
            }
            if (is_null($value)) { return false; }
            if (is_array($value)) { return !empty($value); }
        }
        return $value;
    }

    /**
     * Normaliza el valor que vamos a escribir en Avada para que sea consistente (1/0 para booleanas).
     * @param string $id
     * @param mixed  $value
     * @return mixed
     */
    private static function normalizeOptionValueForWrite(string $id, $value)
    {
        if (self::isBooleanOption($id)) {
            $read = self::normalizeOptionValueForRead($id, $value);
            return $read ? 1 : 0;
        }
        return $value;
    }

    /**
     * Determina si una opción es booleana por convención de nombre o por su tipo registrado.
     * @param string $id
     */
    private static function isBooleanOption(string $id): bool
    {
        // Convención por sufijo.
        if (substr($id, -9) === '_activado') { return true; }
        // Consultar tipo desde el registro si está disponible.
        $tipo = self::getOptionType($id);
        if ($tipo && in_array(strtolower($tipo), ['toggle','checkbox','switch'], true)) {
            return true;
        }
        return false;
    }

    /**
     * Obtiene el tipo de una opción desde el registro descubierto, cacheado en memoria.
     * @param string $id
     * @return string|null
     */
    private static function getOptionType(string $id): ?string
    {
        static $cache = null;
        if ($cache === null) {
            $cache = [];
            $opts = self::gatherOptionsFromOpcionManager();
            foreach ($opts as $opt) {
                if (!empty($opt['id']) && !empty($opt['tipo'])) {
                    $cache[(string) $opt['id']] = (string) $opt['tipo'];
                }
            }
        }
        return $cache[$id] ?? null;
    }

    private static function getGloryOptionDefinition(string $id): ?array
    {
        static $cache = null;
        if ($cache === null) {
            $cache = [];
            $opts = self::gatherOptionsFromOpcionManager();
            foreach ($opts as $opt) {
                if (!empty($opt['id'])) {
                    $cache[(string) $opt['id']] = $opt;
                }
            }
        }
        return $cache[$id] ?? null;
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
        // Intentar discovery dinámico desde OpcionManager.
        $dynamic = self::gatherOptionsFromOpcionManager();

        // Si hay opciones descubiertas, construir sub-secciones por seccion/subSeccion.
        if (!empty($dynamic)) {
            self::log('buildFields:dynamic_found', ['count' => count($dynamic)]);
            $grouped = [];
            foreach ($dynamic as $opt) {
                // Excluir opciones de header de Glory cuando Avada está activo.
                if (self::shouldExcludeOption($opt)) {
                    self::log('buildFields:excluded', ['id' => $opt['id'] ?? '']);
                    continue;
                }
                $sectionId   = isset($opt['seccion']) && $opt['seccion'] ? (string) $opt['seccion'] : 'glory_general';
                $sectionLabel= isset($opt['etiquetaSeccion']) && $opt['etiquetaSeccion'] ? (string) $opt['etiquetaSeccion'] : esc_html__('Glory Options', 'glory-ab');
                if (!isset($grouped[$sectionId])) {
                    $grouped[$sectionId] = [
                        'label'  => $sectionLabel,
                        'id'     => $sectionId,
                        'type'   => 'sub-section',
                        'icon'   => true,
                        'fields' => [],
                    ];
                }
                $field = self::mapGloryOptionToAvadaField($opt);
                if ($field) {
                    // Usar array indexado para los fields.
                    $grouped[$sectionId]['fields'][] = $field;
                } else {
                    self::log('buildFields:map_skipped', ['id' => $opt['id'] ?? '']);
                }
            }
            // Devolver array indexado de sub-secciones.
            $result = array_values($grouped);
            self::log('buildFields:dynamic_result', ['subsections' => count($result)]);
            return $result;
        }

        // Fallback: defaults razonables alineados con opcionesTema.php, excluyendo imagen si Avada activo.
        $fields = [];

        // Sub-sección Integrations & Tracking.
        $fields['glory_integrations'] = [
            'label' => esc_html__('Integrations & Tracking', 'glory-ab'),
            'id'    => 'glory_integrations',
            'type'  => 'sub-section',
            'icon'  => true,
            'fields' => [
                [
                    'label'       => esc_html__('Google Search Console Verification Code', 'glory-ab'),
                    'description' => esc_html__('Paste the content of the GSC verification meta tag.', 'glory-ab'),
                    'id'          => 'glory_gsc_verification_code',
                    'type'        => 'text',
                    'default'     => '',
                ],
                [
                    'label'       => esc_html__('Google Analytics 4 Measurement ID', 'glory-ab'),
                    'description' => esc_html__('Enter your GA4 Measurement ID (e.g., G-XXXXXXXXXX).', 'glory-ab'),
                    'id'          => 'glory_ga4_measurement_id',
                    'type'        => 'text',
                    'default'     => '',
                ],
                [
                    'label'       => esc_html__('Custom Header Scripts', 'glory-ab'),
                    'description' => esc_html__('Scripts or meta tags to include in <head>.', 'glory-ab'),
                    'id'          => 'glory_custom_header_scripts',
                    'type'        => 'code',
                    'choices'     => [
                        'language' => 'html',
                        'height'   => 180,
                        'theme'    => 'chrome',
                        'minLines' => 5,
                        'maxLines' => 16,
                    ],
                    'default'     => '',
                ],
            ],
        ];

        // Devolver array indexado de sub-secciones.
        $result = array_values($fields);
        self::log('buildFields:fallback_result', ['subsections' => count($result)]);
        return $result;
    }

    /**
     * Intenta descubrir dinámicamente las opciones registradas en OpcionManager.
     * Devuelve un array de opciones con llaves: id,tipo,etiqueta,descripcion,valorDefault,opciones,seccion,subSeccion,etiquetaSeccion
     *
     * @return array
     */
    private static function gatherOptionsFromOpcionManager(): array
    {
        $options = [];
        try {
            // 1) Vía filtro público si OpcionManager lo expone.
            $filtered = apply_filters('glory_opcion_manager_registry', null);
            if (is_array($filtered)) {
                $options = self::normalizeRegistry($filtered);
                if (!empty($options)) {
                    return $options;
                }
            }

            // 2) Llamadas a métodos públicos comunes.
            if (class_exists('Glory\\Manager\\OpcionManager')) {
                $class = 'Glory\\Manager\\OpcionManager';
                // Intentos de método público común: options(), all(), registry(), getAll().
                foreach (['options','all','registry','getAll'] as $method) {
                    if (is_callable([$class, $method])) {
                        $result = call_user_func([$class, $method]);
                        if (is_array($result)) {
                            $options = self::normalizeRegistry($result);
                            break;
                        }
                    }
                }
                if (empty($options)) {
                    // Intentar acceder por Reflection a propiedades estáticas comunes.
                    $ref = new \ReflectionClass($class);
                    foreach (['registry','options','_options','store'] as $propName) {
                        if ($ref->hasProperty($propName)) {
                            $prop = $ref->getProperty($propName);
                            if ($prop->isStatic()) {
                                $prop->setAccessible(true);
                                $raw = $prop->getValue();
                                if (is_array($raw)) {
                                    $options = self::normalizeRegistry($raw);
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            // 3) Fallback: escanear archivos de configuración conocidos y extraer registros.
            if (empty($options)) {
                $fromFiles = self::gatherOptionsFromFiles();
                if (!empty($fromFiles)) {
                    $options = $fromFiles;
                }
            }
        } catch (\Throwable $t) {
            // Silencioso.
        }
        return $options;
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
        $files = [];
        try {
            $base = trailingslashit(get_template_directory());
            $files[] = $base . 'Glory/Config/options.php';
            $files[] = $base . 'App/Config/opcionesTema.php';
        } catch (\Throwable $t) {
            return [];
        }

        $found = [];
        foreach ($files as $file) {
            if (!is_readable($file)) {
                continue;
            }
            $code = @file_get_contents($file);
            if (!is_string($code) || '' === $code) {
                continue;
            }
            // Quitar comentarios para simplificar parseo.
            $code = preg_replace('#/\*.*?\*/#s', '', $code);
            $lines = preg_split('/\r\n|\r|\n/', $code);
            $buffer = '';
            foreach ($lines as $line) {
                $buffer .= $line . "\n";
            }
            // Regex para capturar OpcionManager::register('id', [ ... ]);
            if (preg_match_all("#OpcionManager::register\(\s*([\\'\"])\s*([^'\"]+)\s*\\1\s*,\s*(\[.*?\])\s*\)\s*;#s", $buffer, $m, PREG_SET_ORDER)) {
                foreach ($m as $match) {
                    $id = trim($match[2]);
                    $arrLiteral = trim($match[3]);
                    $def = self::parseArrayLiteral($arrLiteral, $id);
                    if (!empty($def)) {
                        $found[] = $def;
                    }
                }
            }
        }
        return $found;
    }

    /**
     * Parser simple de arrays literales para claves comunes.
     * No ejecuta código ni evalúa variables.
     */
    private static function parseArrayLiteral(string $literal, string $id): array
    {
        $def = [ 'id' => $id ];

        // Helpers de extracción con delimitador ~ para simplificar escapes.
        $getString = function(string $key) use ($literal): ?string {
            $pattern = "~'" . preg_quote($key, "~") . "'\\s*=>\\s*'([^']*)'~";
            if (preg_match($pattern, $literal, $m)) { return $m[1]; }
            return null;
        };
        $getBool = function(string $key) use ($literal): ?bool {
            $pattern = "~'" . preg_quote($key, "~") . "'\\s*=>\\s*(true|false)~i";
            if (preg_match($pattern, $literal, $m)) { return strtolower($m[1]) === 'true'; }
            return null;
        };
        $getNum = function(string $key) use ($literal): ?int {
            $pattern = "~'" . preg_quote($key, "~") . "'\\s*=>\\s*(-?[0-9]+)~";
            if (preg_match($pattern, $literal, $m)) { return (int) $m[1]; }
            return null;
        };

        foreach (['tipo','etiqueta','descripcion','seccion','etiquetaSeccion','subSeccion'] as $k) {
            $v = $getString($k);
            if ($v !== null) { $def[$k] = $v; }
        }
        $vb = $getBool('valorDefault');
        if ($vb !== null) { $def['valorDefault'] = $vb; }
        else {
            $vn = $getNum('valorDefault');
            if ($vn !== null) { $def['valorDefault'] = $vn; }
            else {
                $vs = $getString('valorDefault');
                if ($vs !== null) { $def['valorDefault'] = $vs; }
            }
        }
        // Normalizar tipos comunes.
        if (isset($def['tipo']) && $def['tipo'] === 'toggle') {
            $def['tipo'] = 'checkbox';
        }
        return $def;
    }

    /**
     * Normaliza el registro de opciones a un formato estándar.
     *
     * @param array $raw
     * @return array
     */
    private static function normalizeRegistry(array $raw): array
    {
        $normalized = [];
        foreach ($raw as $key => $def) {
            if (is_string($key) && is_array($def)) {
                $def['id'] = $key;
                $normalized[] = $def;
                continue;
            }
            if (is_array($def) && isset($def['id'])) {
                $normalized[] = $def;
            }
        }
        return $normalized;
    }

    /**
     * Determina si debemos excluir una opción del panel (p.ej., opciones de header cuando Avada está activo).
     *
     * @param array $opt
     * @return bool
     */
    private static function shouldExcludeOption(array $opt): bool
    {
        $id = isset($opt['id']) ? (string) $opt['id'] : '';
        // Excluir opciones de logo de Glory en Avada.
        if (in_array($id, ['glory_logo_mode','glory_logo_text','glory_logo_image'], true)) {
            return true;
        }
        return false;
    }

    /**
     * Mapea una definición de OpcionManager a un campo de Avada.
     *
     * @param array $opt
     * @return array|null
     */
    private static function mapGloryOptionToAvadaField(array $opt): ?array
    {
        if (empty($opt['id']) || empty($opt['tipo'])) {
            return null;
        }
        $typeMap = [
            'text'      => 'text',
            'textarea'  => 'textarea',
            'checkbox'  => 'switch',
            'toggle'    => 'switch',
            'radio'     => 'radio-buttonset',
            'select'    => 'select',
            'color'     => 'color-alpha',
            'numero'    => 'slider',
            'imagen'    => 'upload',
            'richText'  => 'code',
        ];
        $type = $typeMap[$opt['tipo']] ?? 'text';
        $field = [
            'label'       => isset($opt['etiqueta']) ? (string) $opt['etiqueta'] : (string) $opt['id'],
            'description' => isset($opt['descripcion']) ? (string) $opt['descripcion'] : '',
            'id'          => (string) $opt['id'],
            'type'        => $type,
            'default'     => $opt['valorDefault'] ?? '',
        ];
        // Normalizar defaults para switches.
        if ('switch' === $type) {
            $def = $opt['valorDefault'] ?? false;
            if (is_string($def)) {
                $v = strtolower(trim($def));
                $def = in_array($v, ['1','true','on','yes'], true);
            }
            $field['default'] = $def ? '1' : '0';
            // Si existe un valor en el repositorio propio de Glory, usarlo como default
            // para que el panel muestre el estado real aunque Avada no lo tenga guardado aún.
            try {
                $repoVal = OpcionRepository::get($field['id']);
                if ($repoVal !== OpcionRepository::getCentinela()) {
                    $normRepo = self::normalizeOptionValueForWrite($field['id'], $repoVal);
                    $field['default'] = (string) ( (int) $normRepo );
                }
            } catch (\Throwable $t) {
                // noop
            }
        }
        if ('select' === $type || 'radio-buttonset' === $type) {
            if (!empty($opt['opciones']) && is_array($opt['opciones'])) {
                $choices = [];
                foreach ($opt['opciones'] as $k => $v) {
                    $choices[(string) $k] = (string) $v;
                }
                $field['choices'] = $choices;
            }
        }
        if ('code' === $type) {
            $field['choices'] = [
                'language' => 'html',
                'height'   => 180,
                'theme'    => 'chrome',
                'minLines' => 5,
                'maxLines' => 16,
            ];
        }
        if ('slider' === $type) {
            // Intentar inferir rangos si existen.
            if (isset($opt['min']) || isset($opt['max']) || isset($opt['step'])) {
                $field['choices'] = [
                    'min'  => isset($opt['min']) ? (int) $opt['min'] : 0,
                    'max'  => isset($opt['max']) ? (int) $opt['max'] : 100,
                    'step' => isset($opt['step']) ? (int) $opt['step'] : 1,
                ];
            }
        }
        return $field;
    }

    /**
     * Log de depuración de cambios en fusion_options (solo claves glory_*).
     */
    public static function debugLogFusionOptionsUpdate($value, $old_value, $option)
    {
        try {
            $is_user_save = false;
            if (isset($_POST['data']) && is_string($_POST['data'])) {
                $is_user_save = true;
            }
            if (defined('DOING_AJAX') && DOING_AJAX) {
                $is_user_save = true;
            }
            if (isset($_POST['action'])) {
                $action = (string) $_POST['action'];
                if (stripos($action, 'fusion') !== false || stripos($action, 'fusionredux') !== false) {
                    $is_user_save = true;
                }
            }
            $reqUri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
            if (stripos($reqUri, 'admin-ajax.php') !== false) {
                $is_user_save = true;
            }
            if ((isset($_REQUEST['avada_update']) && (string) $_REQUEST['avada_update'] === '1')) {
                $is_user_save = true;
            }
            self::log('fusion_options:pre_update_hook_start', ['user_save' => $is_user_save ? 1 : 0]);

            // Leer el valor actual directamente desde la BBDD para evitar inconsistencias con $old_value.
            $existing = get_option($option, []);
            if (!is_array($existing)) {
                $existing = [];
            }

            $glory_existing = [];
            foreach ($existing as $k => $v) {
                if (strpos((string) $k, 'glory_') === 0) {
                    $glory_existing[$k] = $v;
                }
            }
            self::log('fusion_options:existing_glory_db_values', $glory_existing);


            // Evitar que Avada resetee los valores de Glory a sus defaults en cargas de página.
            if (is_array($value)) {
                $ids = self::discoverOptionIds();
                $preserved = [];

                foreach ($ids as $id) {
                    if ($id === 'glory_componente_navegacion_ajax_activado') {
                        $opt_def_debug = self::getGloryOptionDefinition($id);
                        $default_value_debug = $opt_def_debug['valorDefault'] ?? 'not_found';
                        $normalized_default_debug = self::normalizeOptionValueForWrite($id, $default_value_debug);
                        $incoming_value_debug = $value[$id] ?? 'null';
                        $existing_value_debug = $existing[$id] ?? 'null';
                        $norm_incoming_debug = self::normalizeOptionValueForWrite($id, $value[$id] ?? null);
                        $norm_existing_debug = self::normalizeOptionValueForWrite($id, $existing[$id] ?? null);

                        self::log('fusion_options:DEBUG_CHECK', [
                            'id' => $id,
                            'incoming' => $incoming_value_debug,
                            'existing' => $existing_value_debug,
                            'default' => $default_value_debug,
                            'norm_incoming' => $norm_incoming_debug,
                            'norm_existing' => $norm_existing_debug,
                            'norm_default' => $normalized_default_debug,
                        ]);
                    }

                    $opt_def = self::getGloryOptionDefinition($id);
                    if (!$opt_def) {
                        continue;
                    }

                    $default_value = $opt_def['valorDefault'] ?? null;
                    if ($default_value === null) {
                        continue;
                    }

                    $normalized_default = self::normalizeOptionValueForWrite($id, $default_value);

                    $incoming_value = $value[$id] ?? null;
                    $existing_value = $existing[$id] ?? null;

                    // Valor existente en el repositorio propio de Glory (glory_opcion_*)
                    $repo_raw = null;
                    $repo_exists = false;
                    try {
                        $repo_tmp = OpcionRepository::get($id);
                        if ($repo_tmp !== OpcionRepository::getCentinela()) {
                            $repo_raw = $repo_tmp;
                            $repo_exists = true;
                        }
                    } catch (\Throwable $t) {
                        // noop
                    }

                    if ($incoming_value !== null && $existing_value !== null) {
                        // Normalizar para comparación.
                        $norm_incoming = self::normalizeOptionValueForWrite($id, $incoming_value);
                        $norm_existing = self::normalizeOptionValueForWrite($id, $existing_value);

                        // Sólo aplicar heurística anti-reseteo cuando NO es un guardado del usuario.
                        if (!$is_user_save) {
                            // Si el valor entrante es el default, pero el existente es diferente,
                            // asumimos que es un reseteo no deseado y preservamos el valor de la BBDD.
                            if ($norm_incoming == $normalized_default && $norm_existing != $normalized_default) {
                                $value[$id] = $existing_value;
                                $preserved[$id] = ['kept' => $existing_value, 'incoming' => $incoming_value];
                            }
                        }
                    } elseif ($incoming_value === null && $existing_value !== null) {
                        // Si la clave no viene en el guardado, pero existía, la preservamos.
                        $value[$id] = $existing_value;
                        $preserved[$id] = ['kept' => $existing_value, 'incoming' => 'null (missing)'];
                    }

                    // Hidratación desde el repositorio propio cuando Avada intenta aplicar defaults.
                    // No intervenir en guardados explícitos del usuario (permitir ON/OFF reales).
                    if (!$is_user_save && $repo_exists) {
                        $norm_default = $normalized_default;
                        $norm_repo = self::normalizeOptionValueForWrite($id, $repo_raw);
                        $norm_incoming2 = ($incoming_value === null) ? null : self::normalizeOptionValueForWrite($id, $incoming_value);

                        // Si Avada intenta escribir default o no trae valor, pero el repo tiene uno distinto, usar el del repo
                        if ($incoming_value === null || ($norm_incoming2 === $norm_default && $norm_repo !== $norm_default)) {
                            $value[$id] = $repo_raw;
                            $preserved[$id] = ['kept' => $repo_raw, 'reason' => 'repo_hydrate'];
                        }
                    }
                }

                if (!empty($preserved)) {
                    self::log('fusion_options:preserved_existing_values', ['keys' => $preserved]);
                }

                // Sincronizar valores finales hacia el storage propio (glory_opcion_*)
                // Solo escribir si no existe o si cambió respecto al repositorio.
                $synced = [];
                foreach ($ids as $id) {
                    if (!array_key_exists($id, $value)) {
                        continue;
                    }
                    try {
                        $finalVal = $value[$id];
                        $writeVal = self::normalizeOptionValueForWrite($id, $finalVal);
                        $currentRepo = null;
                        $hasCurrent = false;
                        try {
                            $tmp = OpcionRepository::get($id);
                            if ($tmp !== OpcionRepository::getCentinela()) {
                                $currentRepo = self::normalizeOptionValueForWrite($id, $tmp);
                                $hasCurrent = true;
                            }
                        } catch (\Throwable $t) {}

                        if (!$hasCurrent || $currentRepo !== $writeVal) {
                            OpcionRepository::save($id, $writeVal);
                            $synced[$id] = $writeVal;
                        }
                    } catch (\Throwable $t) {
                        self::log('fusion_options:sync_error', ['id' => (string) $id, 'err' => $t->getMessage()]);
                    }
                }
                if (!empty($synced)) {
                    // Limpiar cache de lecturas de opciones para reflejar el nuevo estado inmediatamente.
                    if (class_exists(OpcionManager::class) && method_exists(OpcionManager::class, 'clearCache')) {
                        OpcionManager::clearCache();
                    }
                    self::log('fusion_options:synced_to_glory_repo', ['keys' => $synced]);
                }
            }

            $changed = [];
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    if (strpos((string) $k, 'glory_') === 0) {
                        $old = is_array($old_value) && array_key_exists($k, $old_value) ? $old_value[$k] : null;
                        if ($old !== $v) {
                            $changed[$k] = ['old' => $old, 'new' => $v];
                        }
                    }
                }
            }
            if (!empty($changed)) {
                self::log('fusion_options:update', ['changed' => $changed]);
            } else {
                self::log('fusion_options:update_no_changes_glory');
            }
        } catch (\Throwable $t) {
            self::log('fusion_options:update_error', ['err' => $t->getMessage()]);
        }
        return $value;
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
        try {
            if (!is_array($value)) {
                $value = is_array($value) ? $value : [];
            }

            $ids = self::discoverOptionIds();
            if (empty($ids)) {
                $ids = self::getFallbackOptionIds();
            }

            $merged = [];
            foreach ($ids as $id) {
                if (self::shouldExcludeById($id)) {
                    continue;
                }
                try {
                    $repoVal = OpcionRepository::get($id);
                    if ($repoVal !== OpcionRepository::getCentinela()) {
                        $value[$id] = self::normalizeOptionValueForWrite($id, $repoVal);
                        $merged[$id] = $value[$id];
                    }
                } catch (\Throwable $t) {
                    // noop
                }
            }
            if (!empty($merged)) {
                self::log('option_merge:merged_repo_values', ['keys' => $merged]);
            }
        } catch (\Throwable $t) {
            self::log('option_merge:error', ['err' => $t->getMessage()]);
        }
        return $value;
    }

    private static function log(string $message, array $context = []): void
    {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        $prefix = '[Glory-AvadaBridge] ';
        $line = $prefix . $message;
        if (!empty($context)) {
            $json = json_encode($context);
            if ($json !== false) {
                $line .= ' ' . $json;
            }
        }
        error_log($line);
    }
}


