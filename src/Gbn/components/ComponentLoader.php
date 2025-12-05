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
            
            // Escanear TODOS los archivos *Component.php en este directorio
            // Esto permite tener múltiples componentes por directorio (ej: PostRender/PostItemComponent.php)
            $componentFiles = glob($dir . '/*Component.php');
            
            if ($componentFiles === false) {
                continue;
            }

            foreach ($componentFiles as $filename) {
                require_once $filename;

                // Extraer nombre del archivo sin extensión
                $componentName = basename($filename, '.php');

                // Construir nombre de clase completamente calificado
                // Convención Namespace: Glory\Gbn\Components\{Dirname}\{ComponentName}
                $className = "Glory\\Gbn\\Components\\{$dirname}\\{$componentName}";

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
