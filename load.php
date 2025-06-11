<?php
/**
 * Glory Framework - Punto de Entrada Principal.
 *
 * Este archivo es responsable de definir constantes básicas, cargar archivos de configuración
 * e inicializar el núcleo del framework Glory.
 *
 * @author @wandorius
 */
namespace Glory;

use Glory\Core\Setup;

// Asegura que las constantes necesarias del framework estén definidas.
if (!defined('GLORY_FRAMEWORK_PATH')) {
    // GLORY_FRAMEWORK_PATH: Ruta absoluta al directorio raíz del framework Glory.
    // __DIR__ apunta al directorio actual del archivo (que se espera sea el directorio 'Glory').
    define('GLORY_FRAMEWORK_PATH', __DIR__);
}
if (!defined('GLORY_CONFIG_PATH')) {
    // GLORY_CONFIG_PATH: Ruta absoluta al directorio de configuración del framework.
    define('GLORY_CONFIG_PATH', GLORY_FRAMEWORK_PATH . '/Config');
}

// --- Carga Automática de Archivos de Configuración ---
// Encuentra todos los archivos .php en el directorio de configuración.
$configFiles = glob(GLORY_CONFIG_PATH . '/*.php');

if ($configFiles) {
    foreach ($configFiles as $configFile) {
        if (is_readable($configFile)) {
            // Carga cada archivo de configuración encontrado.
            require_once $configFile;
        } else {
            // Si un archivo de configuración no es legible, se registra un error.
            // Se utiliza error_log directamente porque GloryLogger podría no estar inicializado aún.
            error_log("Glory Framework: No se pudo leer el archivo de configuración: {$configFile}");
        }
    }
}
// Limpia las variables del bucle para evitar contaminación del ámbito global.
unset($configFiles, $configFile);

// Inicializa el núcleo del framework.
new Setup();