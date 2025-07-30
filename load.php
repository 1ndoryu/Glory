<?php

namespace Glory;

use Glory\Core\Setup;

if (!defined('GLORY_FRAMEWORK_PATH')) {
    define('GLORY_FRAMEWORK_PATH', __DIR__);
}
if (!defined('GLORY_CONFIG_PATH')) {
    define('GLORY_CONFIG_PATH', GLORY_FRAMEWORK_PATH . '/Config');
}

// Optimización: Reemplazado glob() por una inclusión directa.
$scriptSetupFile = GLORY_CONFIG_PATH . '/scriptSetup.php';
if (is_readable($scriptSetupFile)) {
    require_once $scriptSetupFile;
} else {
    error_log("Glory Framework: No se pudo leer el archivo de configuración: {$scriptSetupFile}");
}

// Cargar funciones comunes del framework
$functionsFile = GLORY_FRAMEWORK_PATH . '/functions.php';
if (is_readable($functionsFile)) {
    require_once $functionsFile;
} else {
    error_log("Glory Framework: No se pudo leer el archivo de funciones: {$functionsFile}");
}

new Setup();