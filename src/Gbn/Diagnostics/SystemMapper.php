<?php

namespace Glory\Gbn\Diagnostics;

use Glory\Gbn\Config\ContainerRegistry;

class SystemMapper
{
    /**
     * Devuelve un mapa de los Traits utilizados por cada componente registrado.
     * 
     * @return array<string, string[]|array>
     */
    public static function getTraitMap(): array
    {
        $traits = [];
        foreach (ContainerRegistry::all() as $role => $data) {
            // Intentamos inferir la clase del componente.
            // La convención actual es Glory\Gbn\Components\{Role}\{Role}Component
            // Capitalizamos la primera letra del rol para coincidir con la carpeta/clase.
            $classNamePart = ucfirst($role);
            $className = "Glory\\Gbn\\Components\\{$classNamePart}\\{$classNamePart}Component";

            if (class_exists($className)) {
                // class_uses devuelve los traits usados por la clase
                $usedTraits = class_uses($className);
                $traits[$role] = $usedTraits ? array_keys($usedTraits) : [];
            } else {
                // Si no encontramos la clase, devolvemos null o un indicador
                $traits[$role] = null;
            }
        }
        return $traits;
    }

    /**
     * Genera un snapshot completo del estado del sistema.
     * 
     * @return array
     */
    public static function dump(): array
    {
        // Obtener configuración global del tema
        $themeSettings = get_option('gbn_theme_settings', []);

        // Obtener payload de roles (schemas)
        $roleSchemas = ContainerRegistry::rolePayload();

        return [
            'components' => ContainerRegistry::all(),
            'themeSettings' => $themeSettings,
            'traits' => self::getTraitMap(),
            'payload' => [
                'size' => strlen(json_encode($roleSchemas)),
                'schemas' => $roleSchemas
            ],
            'timestamp' => current_time('mysql'),
            'version' => wp_get_theme()->get('Version')
        ];
    }
}
