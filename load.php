<?php
// Glory/load.php
namespace Glory;
use Glory\Class\PageManager;
use Glory\Class\ScriptManager;

// Asegúrate de que las constantes estén definidas (si las usas aquí)
if (!defined('GLORY_FRAMEWORK_PATH')) {
    define('GLORY_FRAMEWORK_PATH', __DIR__); // __DIR__ apunta al directorio actual (Glory)
}
if (!defined('GLORY_CONFIG_PATH')) {
    define('GLORY_CONFIG_PATH', GLORY_FRAMEWORK_PATH . '/Config');
}

// --- Carga Automática de Archivos de Configuración ---

$config_files = glob(GLORY_CONFIG_PATH . '/*.php'); // Encuentra todos los archivos .php en Config/

if ($config_files) {
    foreach ($config_files as $config_file) {
        if (is_readable($config_file)) {
            require_once $config_file; // Carga cada archivo de configuración
        } else {
            // Opcional: Registrar un error si un archivo no es legible
            error_log("Glory Framework: No se pudo leer el archivo de configuración {$config_file}");
        }
    }
}
unset($config_files, $config_file); // Limpia variables


ScriptManager::setGlobalDevMode(true);  
ScriptManager::setThemeVersion('0.1.2'); 

ScriptManager::defineFolder('/js');
PageManager::define('home');


ScriptManager::register();
PageManager::register();