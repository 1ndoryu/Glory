<?php
/**
 * Archivo de carga principal del framework Glory.
 *
 * Este archivo establece las constantes necesarias, carga configuraciones,
 * funciones globales y arranca el núcleo del framework.
 *
 * @package Glory
 */

namespace Glory;

use Glory\Core\Setup;

// Definición de constantes de ruta
if (!defined('GLORY_FRAMEWORK_PATH')) {
    define('GLORY_FRAMEWORK_PATH', __DIR__);
}
if (!defined('GLORY_CONFIG_PATH')) {
    define('GLORY_CONFIG_PATH', GLORY_FRAMEWORK_PATH . '/Config');
}

// Carga de definición de opciones del tema (primero, para que scriptSetup pueda consultarlas)
$archivoOpciones = GLORY_CONFIG_PATH . '/options.php';
if (is_readable($archivoOpciones)) {
    require_once $archivoOpciones;
}

// Carga de configuración de scripts (después de registrar opciones)
$archivoConfiguracionScripts = GLORY_CONFIG_PATH . '/scriptSetup.php';
if (is_readable($archivoConfiguracionScripts)) {
    require_once $archivoConfiguracionScripts;
} else {
    error_log("Glory Framework: No se pudo leer el archivo de configuración: {$archivoConfiguracionScripts}");
}

// Carga de funciones globales
$archivoFunciones = GLORY_FRAMEWORK_PATH . '/functions.php';
if (is_readable($archivoFunciones)) {
    require_once $archivoFunciones;
} else {
    error_log("Glory Framework: No se pudo leer el archivo de funciones: {$archivoFunciones}");
}

// Cargar configuración de control del tema ANTES de instanciar Setup para que los flags apliquen temprano
$archivoControlTema = get_template_directory() . '/App/Config/control.php';
if (is_readable($archivoControlTema)) {
    require_once $archivoControlTema;
}

// Intentamos cargar las definiciones de opciones del tema (si existen) antes de instanciar Setup
$archivoOpcionesTema = get_template_directory() . '/App/Config/opcionesTema.php';
if (is_readable($archivoOpcionesTema)) {
    require_once $archivoOpcionesTema;
}

// Evitar doble arranque del framework
if (!defined('GLORY_BOOTED')) {
    define('GLORY_BOOTED', true);
    new Setup();
}
