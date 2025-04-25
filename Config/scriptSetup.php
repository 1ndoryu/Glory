<?php

use Glory\ScriptManager; 

ScriptManager::define(
    'glory-email-signup', 
    '/Glory/Assets/js/GloryEmailSignup.js',
    [],
    null, 
    true,
    [
        'object_name' => 'gloryGlobalData', // JS object name
        'data' => [
            'ajax_url' => admin_url('admin-ajax.php'),
        ]
    ]
);
