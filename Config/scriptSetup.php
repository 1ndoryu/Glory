<?php

use Glory\Class\GloryLogger;
use Glory\Class\ScriptManager;
use Glory\Class\StyleManager;

// Esto no esta preparado para ejecutarse en el wp-admin, aún no se como hacer que scriptmanager funcione en wp-admin
ScriptManager::define(
    'gloryLogs',                           // Handle único para tu script
    '/Glory/Assets/js/GloryLogs.js',       // Ruta relativa al archivo JS desde la raíz del tema
    [],                                    // Dependencias (si las tuviera, e.g., ['another-script-handle'])
    null,                                  // Versión (null para que ScriptManager la calcule)
    true,                                  // Cargar en el footer
    [                                      // Datos para localizar (wp_localize_script)
        'object_name' => 'gloryLogsData',  // Nombre del objeto JS global (window.gloryLogsData)
        'data'        => [                 // Datos que se pasarán al objeto
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('glory_logs_nonce'),
        ],
    ],
);

ScriptManager::defineFolder('/Glory/Assets/js');
StyleManager::defineFolder('/Glory/assets/css');
// Glory\Assets\css\adminPanel.css

GloryLogger::init();
