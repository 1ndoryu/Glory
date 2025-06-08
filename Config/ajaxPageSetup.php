<?php

use Glory\Core\ScriptManager;

ScriptManager::define(
    'gloryAjaxNav',
    '/Glory/assets/js/genericAjax/gloryAjaxNav.js',
    [],
    null,
    true,
    [
        'nombreObjeto' => 'dataGlobal',
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
            'noAjaxClass' => 'noAjax',
            'idUsuario' => get_current_user_id(),
            'nonce' => wp_create_nonce('globalNonce'),
            'nombreUsuario' => is_user_logged_in() ? wp_get_current_user()->display_name : '',
            'username' => is_user_logged_in() ? wp_get_current_user()->user_login : '',
        ],
    ],
    null,
);

