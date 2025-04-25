<?php
// En functions.php o App/Config/scripts.php
use Glory\ScriptManager;

// Definir el script de navegación AJAX
ScriptManager::define(
    'glory-ajax-nav',                                   
    '/Glory/Assets/js/ajax-page.js',                        
    [],                                                 
    null,                                               
    true,                                               
    [                                                   
        'object_name' => 'gloryAjaxNavConfig',          
        'data' => [                                     
            'enabled'            => true,
            'contentSelector'    => '#content',
            'mainScrollSelector' => '#main',
            'loadingBarSelector' => '#loadingBar',
            'cacheEnabled'       => true,
            'ignoreUrlPatterns'  => [
                '/wp-admin',
                '/wp-login\\.php',
                '\\.(pdf|zip|rar|jpg|jpeg|png|gif|webp|mp3|mp4|xml|txt|docx|xlsx)$'
            ],
            'ignoreUrlParams'    => ['s', 'nocache', 'preview'],
            'noAjaxClass'        => 'no-ajax',
        ]
    ],
    null 
);

ScriptManager::register();

