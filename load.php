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
use Glory\Core\GloryConfig;

// Definición de constantes de ruta
if (!defined('GLORY_FRAMEWORK_PATH')) {
    define('GLORY_FRAMEWORK_PATH', __DIR__);
}
if (!defined('GLORY_CONFIG_PATH')) {
    define('GLORY_CONFIG_PATH', GLORY_FRAMEWORK_PATH . '/Config');
}

// Carga de definición de opciones del tema (primero, para que el proyecto pueda consultarlas)
$archivoOpciones = GLORY_CONFIG_PATH . '/options.php';
if (is_readable($archivoOpciones)) {
    require_once $archivoOpciones;
}

/* Inicializar GloryConfig — resuelve rutas del proyecto sin hardcodear App/ */
GloryConfig::load();

/*
 * Control del tema: se carga antes del boot para que
 * las features esten definidas cuando se registren los assets.
 */
$archivoControlTema = GloryConfig::path('config_dir') . '/control.php';
if (is_readable($archivoControlTema)) {
    require_once $archivoControlTema;
}

// Cargar opciones del tema
$archivoOpcionesTema = GloryConfig::path('config_dir') . '/opcionesTema.php';
if (is_readable($archivoOpcionesTema)) {
    require_once $archivoOpcionesTema;
}

// Carga de funciones globales
$archivoFunciones = GLORY_FRAMEWORK_PATH . '/functions.php';
if (is_readable($archivoFunciones)) {
    require_once $archivoFunciones;
} else {
    error_log("Glory Framework: No se pudo leer el archivo de funciones: {$archivoFunciones}");
}

// Evitar doble arranque del framework
if (!defined('GLORY_BOOTED')) {
    define('GLORY_BOOTED', true);
    new Setup();
}
