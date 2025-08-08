<?php

namespace Glory\Core;

use Glory\Manager\OpcionManager;

/**
 * Clase para controlar mediante programación la activación o desactivación de funcionalidades del tema.
 * Permite a los desarrolladores anular las opciones de la base de datos directamente desde el código.
 * jules refactor: proxima tarea, considerar unificar el nombre de las keys, por ejemplo, usar camelCase en todos lados o snake_case
 */
class GloryFeatures
{
    private static array $features = [];

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
     * @param string $key
     * @return string
     */
    private static function normalizeKey(string $key): string
    {
        $key = str_replace(['-', '_'], ' ', $key);
        $key = ucwords($key);
        $key = str_replace(' ', '', $key);
        return lcfirst($key);
    }

    /**
     * Comprueba si una feature está activa combinando el override por código (GloryFeatures)
     * y la opción almacenada en la base de datos (OpcionManager).
     *
     * - Si el override por código es false => inactivo.
     * - Si el override por código es true => activo.
     * - Si no hay override explícito, se consulta la opción en BD si se suministra la key.
     * - Si no hay override ni opción, se devuelve el valor por defecto proporcionado.
     *
     * @param string $feature
     * @param string|null $optionKey
     * @param bool $defaultOption
     * @return bool
     */
    public static function isActive(string $feature, ?string $optionKey = null, bool $defaultOption = true): bool
    {
        $normalized = self::normalizeKey($feature);
        $overridden = self::isEnabled($normalized);

        if ($overridden === false) return false;
        if ($overridden === true) return true;

        // Si no se proporcionó una key de opción, inferir la clave por convención:
        // glory_componente_{feature_snake}_activado
        if ($optionKey === null) {
            $snake = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $normalized));
            $optionKey = 'glory_componente_' . $snake . '_activado';
        }

        return (bool) OpcionManager::get($optionKey, $defaultOption);
    }
}
