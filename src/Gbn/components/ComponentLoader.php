<?php

namespace Glory\Gbn\Components;

use Glory\Gbn\Config\ContainerRegistry;

class ComponentLoader
{
    /**
     * Escanea y carga componentes desde el directorio Components.
     */
    public static function load(): void
    {
        $baseDir = __DIR__;
        // Escanear subdirectorios
        $dirs = glob($baseDir . '/*', GLOB_ONLYDIR);

        if ($dirs === false) {
            return;
        }

        foreach ($dirs as $dir) {
            $dirname = basename($dir);
            // Convención: Nombre de clase coincide con nombre de directorio + 'Component'
            // Ej: Principal/PrincipalComponent.php
            $filename = $dir . '/' . $dirname . 'Component.php';

            if (file_exists($filename)) {
                require_once $filename;

                // Construir nombre de clase completamente calificado
                // Convención Namespace: Glory\Gbn\Components\{Dirname}\{Dirname}Component
                $className = "Glory\\Gbn\\Components\\{$dirname}\\{$dirname}Component";

                if (class_exists($className)) {
                    $instance = new $className();
                    if ($instance instanceof ComponentInterface) {
                        ContainerRegistry::register($instance->getId(), [
                            'label' => $instance->getLabel(),
                            'icon' => $instance->getIcon(),
                            'template' => $instance->getTemplate(),
                            'selector' => $instance->getSelector(),
                            'defaults' => [
                                'config' => $instance->getDefaults(),
                                'schema' => $instance->getSchema(),
                            ]
                        ]);
                    }
                }
            }
        }
    }
}
