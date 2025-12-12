<?php

namespace Glory\Core;

use Glory\Manager\OpcionManager;
use Glory\Manager\AssetManager;
use Glory\Core\OpcionRegistry;

/**
 * Clase para controlar mediante programación la activación o desactivación de funcionalidades del tema.
 * Permite a los desarrolladores anular las opciones de la base de datos directamente desde el código.
 */
class GloryFeatures
{
    /** @var array Almacena el estado de las features sobreescritas en tiempo de ejecución. */
    private static array $features = [];

    /**
     * Mapeo de alias para mantener compatibilidad entre nombres de features.
     * Las claves se comparan en minúsculas sin guiones/underscores.
     * @var array
     */
    private static array $aliasMap = [
        'schedulemanager' => 'scheduler',
        'schedule_manager' => 'scheduler',
        'schedule-manager' => 'scheduler',
        // Compatibilidad entre nombres históricos
        'gestionarpreviews' => 'previews',
    ];

    /**
     * Desactiva una funcionalidad específica.
     *
     * @param string $feature El nombre clave de la funcionalidad (ej. 'modales', 'navegacionAjax').
     */
    public static function disable(string $feature): void
    {
        self::$features[self::normalizeKey($feature)] = false;
    }

    /**
     * Activa una funcionalidad específica.
     *
     * @param string $feature El nombre clave de la funcionalidad.
     */
    public static function enable(string $feature): void
    {
        self::$features[self::normalizeKey($feature)] = true;
    }

    /**
     * Comprueba si una funcionalidad ha sido configurada explícitamente (activada o desactivada).
     *
     * @param string $feature El nombre clave de la funcionalidad.
     * @return bool True si la funcionalidad ha sido configurada, false en caso contrario.
     */
    public static function isSet(string $feature): bool
    {
        return isset(self::$features[self::normalizeKey($feature)]);
    }

    /**
     * Comprueba si una funcionalidad está activada.
     * Devuelve el estado configurado (true/false) solo si ha sido establecido.
     *
     * @param string $feature El nombre clave de la funcionalidad.
     * @return bool|null El estado de la funcionalidad si está configurado, de lo contrario null.
     */
    public static function isEnabled(string $feature): ?bool
    {
        $featureKey = self::normalizeKey($feature);
        if (self::isSet($featureKey)) {
            return self::$features[$featureKey];
        }
        return null;
    }

    /**
     * Normaliza la clave para asegurar consistencia (camelCase).
     *
     * @param string $key La clave a normalizar.
     * @return string La clave en formato camelCase.
     */
    private static function normalizeKey(string $key): string
    {
        // Aplicar alias si corresponde (comparación en minúsculas sin separadores)
        $aliasKey = strtolower(str_replace(['-', '_'], '', $key));
        if (isset(self::$aliasMap[$aliasKey])) {
            $key = self::$aliasMap[$aliasKey];
        }

        $key = str_replace(['-', '_'], ' ', $key);
        $key = ucwords($key);
        $key = str_replace(' ', '', $key);
        return lcfirst($key);
    }

    /**
     * Comprueba si una feature está activa.
     *
     * Combina la configuración por código (Runtime Override) y la opción
     * almacenada en la base de datos (OpcionManager).
     *
     * - Si el override por código es false => inactivo.
     * - Si el override por código es true => activo.
     * - Si no hay override explícito, se consulta la opción en BD.
     * - Si no hay override ni opción, se devuelve el valor por defecto.
     *
     * @param string      $feature       Nombre de la funcionalidad.
     * @param string|null $optionKey     Clave específica de opción en BD (opcional).
     * @param bool        $defaultOption Valor por defecto si no se encuentra configuración.
     * @return bool True si la feature está activa.
     */
    public static function isActive(string $feature, ?string $optionKey = null, bool $defaultOption = true): bool
    {
        $normalized = self::normalizeKey($feature);
        $overridden = self::isEnabled($normalized);

        // Verificar modo desarrollo
        $isDevMode = (method_exists(AssetManager::class, 'isGlobalDevMode') && AssetManager::isGlobalDevMode()) || (defined('WP_DEBUG') && WP_DEBUG);

        // En modo desarrollo, el override por código tiene máxima prioridad
        if ($isDevMode) {
            if ($overridden === false) return false;
            if ($overridden === true) return true;
        } else {
            // En producción, el panel tiene prioridad; el valor del código actúa como default
            if ($overridden !== null) {
                $defaultOption = (bool) $overridden;
            }
        }

        // Resolver clave(s) de opción posibles evitando advertencias por opciones no definidas.
        $snake = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $normalized));

        $candidateKeys = [];
        if ($optionKey !== null) {
            $candidateKeys[] = $optionKey;
        } else {
            // Convención principal
            $candidateKeys[] = 'glory_componente_' . $snake . '_activado';
            // Convención core previa
            $candidateKeys[] = 'glory_' . $snake . '_activado';
            // Variante "service" para claves históricas (ajax/form)
            if (str_starts_with($snake, 'glory_')) {
                $sinPrefijo = substr($snake, strlen('glory_'));
                $candidateKeys[] = 'glory_' . $sinPrefijo . '_service_activado';
            }
        }

        foreach ($candidateKeys as $key) {
            if (OpcionRegistry::getDefinicion($key) !== null) {
                return (bool) OpcionManager::get($key, $defaultOption);
            }
        }

        // Si ninguna clave está definida, usar el valor por defecto.
        return (bool) $defaultOption;
    }

    /**
     * Features que se desactivan cuando React esta activo.
     * 
     * IMPORTANTE: En Modo React, Glory NO carga NINGUN script nativo.
     * React maneja TODO de forma independiente:
     * - UI (modales, tabs, navegacion, etc.)
     * - Servicios (AJAX via fetch, formularios via React)
     * - Renderers (todo es componente React)
     * 
     * Solo se mantienen activos algunos managers de backend necesarios para
     * que WordPress funcione correctamente (pageManager, postTypeManager, etc.)
     * 
     * El panel glory-opciones NO se usa con React. Las opciones de React
     * se configuran via ReactContentProvider y un panel dedicado.
     * 
     * @var array
     */
    private static array $reactExcludedFeatures = [
        // =====================================================================
        // UI COMPONENTS - React tiene sus propios componentes
        // =====================================================================
        'modales',
        'submenus',
        'pestanas',
        'scheduler',
        'headerAdaptativo',
        'themeToggle',
        'alertas',
        'gestionarPreviews',
        'paginacion',
        'gloryFilters',
        'calendario',
        'badgeList',
        'highlight',
        'gsap',
        'menu',
        'contentActions',

        // =====================================================================
        // SERVICES - React usa fetch/axios y sus propios servicios
        // =====================================================================
        'navegacionAjax',
        'gloryAjax',
        'gloryForm',
        'gloryBusqueda',
        'gloryRealtime',
        'cssCritico',

        // =====================================================================
        // RENDERERS - Todo es componente React
        // =====================================================================
        'logoRenderer',
        'contentRender',
        'termRender',

        // =====================================================================
        // PLUGINS/FEATURES ESPECIFICOS - No aplican a este proyecto React
        // =====================================================================
        'task',
        'amazonProduct',
        'gbn',
        'gbnSplitContent',
        'gloryLinkCpt',

        // =====================================================================
        // INTEGRACIONES - No se usan con React
        // =====================================================================
        'avadaIntegration',
    ];

    /**
     * Verifica si el Modo React esta activo.
     * 
     * Se controla exclusivamente via control.php con:
     * GloryFeatures::enable('reactMode') o GloryFeatures::disable('reactMode')
     * 
     * El panel glory-opciones NO se usa con React.
     * 
     * @return bool True si reactMode esta habilitado.
     */
    public static function isReactMode(): bool
    {
        return self::isActive('reactMode', null, false);
    }

    /**
     * Aplica el Modo React desactivando todas las features que React reemplaza.
     * 
     * Este metodo debe llamarse DESPUES de definir reactMode en control.php
     * y ANTES de que se carguen los assets (idealmente al final de control.php).
     * 
     * Nota: Solo afecta al frontend. Las features de admin no se tocan.
     */
    public static function applyReactMode(): void
    {
        if (!self::isReactMode()) {
            return;
        }

        foreach (self::$reactExcludedFeatures as $feature) {
            self::disable($feature);
        }
    }

    /**
     * Obtiene la lista de features excluidas por React Mode.
     * Util para el panel de opciones (ocultar secciones innecesarias).
     * 
     * @return array Lista de nombres de features.
     */
    public static function getReactExcludedFeatures(): array
    {
        return self::$reactExcludedFeatures;
    }
}
