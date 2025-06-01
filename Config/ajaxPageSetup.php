<?php

use Glory\Class\ScriptManager;

ScriptManager::define(
    'gloryAjaxNav',                                   
    '/Glory/Assets/js/gloryAjaxNav.js',                        
    [],                                                 
    null,                                               
    true,                                               
    [                                                   
        'object_name' => 'gloryAjaxNavConfig',          
        'data' => [                                     
            'enabled'            => false,
            'contentSelector'    => '#contentAjax',
            'mainScrollSelector' => '#contentAjax',
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



