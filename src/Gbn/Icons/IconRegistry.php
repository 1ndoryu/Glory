<?php
namespace Glory\Gbn\Icons;

/**
 * Registro centralizado de iconos SVG para componentes GBN.
 * 
 * Principio: Single Source of Truth para todos los iconos.
 * Cualquier componente que necesite un icono debe obtenerlo de aquí.
 */
class IconRegistry
{
    private static array $icons = [];
    private static bool $initialized = false;

    /**
     * Inicializa el registro cargando todos los iconos.
     */
    public static function init(): void
    {
        if (self::$initialized) return;
        
        // Cargar iconos de diferentes categorías
        if (class_exists(LayoutIcons::class)) self::$icons = array_merge(self::$icons, LayoutIcons::all());
        if (class_exists(BackgroundIcons::class)) self::$icons = array_merge(self::$icons, BackgroundIcons::all());
        if (class_exists(PositioningIcons::class)) self::$icons = array_merge(self::$icons, PositioningIcons::all());
        if (class_exists(BorderIcons::class)) self::$icons = array_merge(self::$icons, BorderIcons::all());
        if (class_exists(FormatIcons::class)) self::$icons = array_merge(self::$icons, FormatIcons::all());
        
        if (class_exists(ActionIcons::class)) self::$icons = array_merge(self::$icons, ActionIcons::all());
        
        self::$initialized = true;
    }

    /**
     * Obtiene un icono por su clave.
     * 
     * @param string $key Clave única del icono (ej: 'layout.grid')
     * @param array $attrs Atributos opcionales a sobrescribir
     * @return string SVG del icono
     */
    public static function get(string $key, array $attrs = []): string
    {
        self::init();
        
        if (!isset(self::$icons[$key])) {
            error_log("IconRegistry: Icono no encontrado: {$key}");
            return self::getFallback();
        }
        
        $icon = self::$icons[$key];
        
        // Permitir sobrescribir width/height y otros atributos
        if (!empty($attrs)) {
            foreach ($attrs as $attr => $value) {
                // Escapar valor para seguridad básica
                $value = htmlspecialchars($value, ENT_QUOTES);
                
                // Si el atributo ya existe, reemplazarlo
                if (preg_match("/{$attr}=\"[^\"]*\"/", $icon)) {
                    $icon = preg_replace(
                        "/{$attr}=\"[^\"]*\"/",
                        "{$attr}=\"{$value}\"",
                        $icon
                    );
                } else {
                    // Si no existe, agregarlo al tag svg
                    // Asumimos que el string empieza con <svg
                    $icon = preg_replace('/<svg/', "<svg {$attr}=\"{$value}\"", $icon, 1);
                }
            }
        }
        
        return $icon;
    }

    /**
     * Obtiene múltiples iconos para iconGroup.
     * 
     * @param array $keys Array de claves ['layout.grid', 'layout.flex', ...] o config
     * @return array Opciones formateadas para iconGroup
     */
    public static function getGroup(array $keys): array
    {
        $options = [];
        foreach ($keys as $key => $config) {
            // Manejar tanto array asociativo como lista simple
            $isSimpleString = is_string($config);
            $iconKey = $isSimpleString ? $config : ($config['icon'] ?? $key);
            
            $option = [
                'valor' => $isSimpleString ? $config : ($config['valor'] ?? $key),
                'etiqueta' => $isSimpleString ? ucfirst($config) : ($config['etiqueta'] ?? ucfirst($key)),
                'icon' => self::get($iconKey)
            ];
            
            // Mantener otros atributos si se pasaron
            if (is_array($config)) {
                $option = array_merge($config, $option);
            }
            
            $options[] = $option;
        }
        return $options;
    }

    private static function getFallback(): string
    {
        return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>';
    }
}
