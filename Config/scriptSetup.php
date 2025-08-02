<?php

use Glory\Manager\AssetManager;
use Glory\Integration\Compatibility;

// Carga condicional de scripts de Avada/Fusion Builder
if (Compatibility::avadaActivo()) {
    AssetManager::define(
        'script',
        'fusionBuilderDetect',
        '/Glory/assets/js/utils/fusionBuilderDetect.js',
        ['deps' => [], 'in_footer' => false]
    );
    AssetManager::define(
        'script',
        'disableMenuClicksInFusionBuilder',
        '/Glory/assets/js/utils/disableMenuClicksInFusionBuilder.js',
        ['deps' => ['fusionBuilderDetect'], 'in_footer' => true]
    );
}

AssetManager::define(
    'script',
    'gloryAjaxNav',
    '/Glory/assets/js/genericAjax/gloryAjaxNav.js',
    [
        'deps'      => ['jquery'],
        'in_footer' => true,
        'localize'  => [
            'nombreObjeto' => 'dataGlobal',
            'datos'        => [
                'enabled'            => true,
                'contentSelector'    => '#main',
                'mainScrollSelector' => '#main',
                'loadingBarSelector' => '#loadingBar',
                'cacheEnabled'       => true,
                'ignoreUrlPatterns'  => [
                    '/wp-admin',
                    '/wp-login\.php',
                    '\.(pdf|zip|rar|jpg|jpeg|png|gif|webp|mp3|mp4|xml|txt|docx|xlsx)$',
                ],
                'ignoreUrlParams'    => ['s', 'nocache', 'preview'],
                'noAjaxClass'        => 'noAjax',
                'idUsuario'          => get_current_user_id(),
                'nonce'              => wp_create_nonce('globalNonce'),
                'nombreUsuario'      => is_user_logged_in() ? wp_get_current_user()->display_name : '',
                'username'           => is_user_logged_in() ? wp_get_current_user()->user_login : '',
            ],
        ]
    ]
);

AssetManager::defineFolder(
    'script',
    '/Glory/assets/js/',
    [
        'deps'      => ['jquery'],
        'in_footer' => true,
    ],
    'glory-',
    [
        'adminPanel.js',
        'gloryLogs.js',
        'options-panel.js',
        'disableMenuClicksInFusionBuilder.js',
        'fusionBuilderDetect.js'
    ]
);

AssetManager::defineFolder(
    'style',
    '/Glory/assets/css/',
    [
        'deps'  => [],
        'media' => 'all',
    ],
    'glory-',
    []
);