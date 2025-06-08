<?php

use Glory\Class\ScriptManager;

ScriptManager::define(
    'gloryAjaxNav',
    '/Glory/assets/js/gloryAjaxNav.js',
    [],
    null,
    true,
    [
        'nombreObjeto' => 'gloryAjaxNavConfig',
        'datos' => [
            'enabled' => true,
            'contentSelector' => '#contentAjax',
            'mainScrollSelector' => '#contentAjax',
            'loadingBarSelector' => '#loadingBar',
            'cacheEnabled' => true,
            'ignoreUrlPatterns' => [
                '/wp-admin',
                '/wp-login\.php',
                '\.(pdf|zip|rar|jpg|jpeg|png|gif|webp|mp3|mp4|xml|txt|docx|xlsx)$',
            ],
            'ignoreUrlParams' => ['s', 'nocache', 'preview'],
            'noAjaxClass' => 'no-ajax',
            'idUsuario' => get_current_user_id(),
        ],
    ],
    null,
);

