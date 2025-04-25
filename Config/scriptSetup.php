<?php

use Glory\ScriptManager; 

ScriptManager::define(
    'EmailFormBuilder', 
    '/Glory/Assets/js/EmailFormBuilder.js',
    [],
    null, 
    true,
    [
        'object_name' => 'gloryGlobalData', 
        'data' => [
            'ajax_url' => admin_url('admin-ajax.php'),
        ]
    ]
);
