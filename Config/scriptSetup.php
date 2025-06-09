<?php

use Glory\Core\GloryLogger;
use Glory\Core\ScriptManager;
use Glory\Core\StyleManager;
use Glory\Core\Setup;

// Esto no esta preparado para ejecutarse en el wp-admin, aún no se como hacer que scriptmanager funcione en wp-admin
ScriptManager::define(
    'gloryLogs',                           // Handle único para tu script
    '/Glory/assets/js/GloryLogs.js',       // Ruta relativa al archivo JS desde la raíz del tema
    [],                                    // Dependencias (si las tuviera, e.g., ['another-script-handle'])
    null,                                  // Versión (null para que ScriptManager la calcule)
    true,                                  // Cargar en el footer
    [                                      // Datos para localizar (wp_localize_script)
        'nombreObjeto' => 'gloryLogsData',  // Nombre del objeto JS global (window.gloryLogsData)
        'datos'        => [                 // Datos que se pasarán al objeto
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('glory_logs_nonce'),
        ],
    ],
);

ScriptManager::defineFolder(
    '/Glory/Assets/js',
    [],    // dependencias por defecto
    true,  // defaultInFooter (booleano)
    null,  // folderDevMode (?bool)
    '',    // prefijo de handle por defecto (string)
    [     
        'adminPanel.js',
        'gloryLogs.js',
    ]
);

StyleManager::defineFolder(
    '/Glory/Assets/css',
    [], // dependencias por defecto
    'all', // media por defecto
    null, // devMode por defecto
    '', // prefijo de handle por defecto
    [ // Archivos CSS a excluir de la carga global (CSS de admin)
        //'content-admin-panel.css',
        // Añade aquí otros CSS específicos del admin si es necesario
    ]
);




