<?
/**
 * Configuración y registro de scripts y estilos para el tema/framework Glory.
 *
 * Este archivo utiliza el AssetManager unificado para definir y registrar
 * todos los assets (JS y CSS) de forma centralizada.
 *
 * @package Glory\Config
 */

use Glory\Core\AssetManager;

// Define las carpetas de assets que se cargarán automáticamente.
// AssetManager procesará todos los archivos .js y .css en estas carpetas,
// a menos que se especifiquen exclusiones.

// Definición de todos los scripts en la carpeta de JavaScript.
AssetManager::defineFolder(
    'script',                                  // Tipo de asset
    '/Glory/assets/js/',                             // Ruta de la carpeta relativa al tema
    [                                          // Configuración por defecto para estos scripts
        'deps'      => ['jquery'],
        'in_footer' => true,
    ],
    'glory-',                                  // Prefijo para los handles
    [                                          
        'adminPanel.js',
        'gloryLogs.js',
    ]
);

// Definición de todos los estilos en la carpeta de CSS.
AssetManager::defineFolder(
    'style',                                   // Tipo de asset
    '/Glory/assets/css/',                            // Ruta de la carpeta relativa al tema
    [                                          // Configuración por defecto para estos estilos
        'deps'  => [],
        'media' => 'all',
    ],
    'glory-',                                  // Prefijo para los handles
    []                                         // Archivos a excluir
);