<?php

namespace Glory;

use Glory\Core\Setup;

if (!defined('GLORY_FRAMEWORK_PATH')) {
    define('GLORY_FRAMEWORK_PATH', __DIR__);
}
if (!defined('GLORY_CONFIG_PATH')) {
    define('GLORY_CONFIG_PATH', GLORY_FRAMEWORK_PATH . '/Config');
}

// Carga de definición de opciones del tema (primero, para que scriptSetup pueda consultarlas)
$optionsFile = GLORY_CONFIG_PATH . '/options.php';
if (is_readable($optionsFile)) {
    require_once $optionsFile;
}

// Carga de configuración de scripts (después de registrar opciones)
$scriptSetupFile = GLORY_CONFIG_PATH . '/scriptSetup.php';
if (is_readable($scriptSetupFile)) {
    require_once $scriptSetupFile;
} else {
    error_log("Glory Framework: No se pudo leer el archivo de configuración: {$scriptSetupFile}");
}

// Carga de funciones globales
$functionsFile = GLORY_FRAMEWORK_PATH . '/functions.php';
if (is_readable($functionsFile)) {
    require_once $functionsFile;
} else {
    error_log("Glory Framework: No se pudo leer el archivo de funciones: {$functionsFile}");
}

// Cargar configuración de control del tema ANTES de instanciar Setup para que los flags apliquen temprano
$themeControlFile = get_template_directory() . '/App/Config/control.php';
if (is_readable($themeControlFile)) {
    require_once $themeControlFile;
}

// Intentamos cargar las definiciones de opciones del tema (si existen) antes de instanciar Setup
$themeOptionsFile = get_template_directory() . '/App/Config/opcionesTema.php';
if (is_readable($themeOptionsFile)) {
    require_once $themeOptionsFile;
}

// Evitar doble bootstrap del framework
if (!defined('GLORY_BOOTED')) {
    define('GLORY_BOOTED', true);
    new Setup();
}