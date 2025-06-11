<?php
/**
 * Configuración y registro de scripts y estilos para el tema/framework Glory.
 *
 * Este archivo utiliza ScriptManager y StyleManager para definir scripts individuales,
 * así como para procesar carpetas completas de assets (JS y CSS), aplicando configuraciones
 * globales o específicas para su carga en el frontend.
 *
 * @package Glory\Config
 */

use Glory\Core\GloryLogger;
use Glory\Core\ScriptManager;
use Glory\Core\StyleManager;
use Glory\Core\Setup;


ScriptManager::defineFolder(
    '/Glory/Assets/js', // Ruta de la carpeta de scripts
    ['jquery'],         // Dependencias por defecto para los scripts en la carpeta
    true,               // Cargar en footer por defecto para scripts en la carpeta
    null,               // Modo de desarrollo para la carpeta (afecta versión, ?bool). Null usará el general de ScriptManager.
    'glory-',           // Prefijo para los handles de los scripts en la carpeta (ej. 'glory-nombrearchivo')
    [                   // Lista de archivos JS específicos a procesar dentro de la carpeta (si está vacía, podría procesar todos)
        'adminPanel.js', // Ejemplo: se registraría como 'glory-adminPanel'
        'gloryLogs.js',  // Ejemplo: se registraría como 'glory-gloryLogs' (potencial colisión con el de arriba si el prefijo no ayuda)
                        // Es importante que ScriptManager maneje la no-duplicación de handles o que esta configuración sea coherente.
    ]
);

StyleManager::defineFolder(
    '/Glory/Assets/css', // Ruta de la carpeta de estilos
    [],                  // Dependencias por defecto para los estilos en la carpeta
    'all',               // Media por defecto (ej. 'all', 'screen')
    null,                // Modo de desarrollo para la carpeta (afecta versión, ?bool). Null usará el general de StyleManager.
    'glory-',            // Prefijo para los handles de los estilos en la carpeta (ej. 'glory-nombrearchivo')
    [                    // Archivos CSS a excluir de la carga global (si defineFolder carga todo por defecto menos exclusiones)
        // 'admin-specific.css', // Ejemplo de CSS específico del admin que no se carga globalmente
        // Añadir aquí otros CSS que no deban cargarse en todas partes si defineFolder tiene esa lógica.
    ]
);




