<?php

/**
 * Configuración y registro de scripts y estilos para el tema/framework Glory.
 *
 * Este archivo utiliza el AssetManager unificado para definir y registrar
 * todos los assets (JS y CSS) de forma centralizada.
 *
 * @package Glory\Config
 */

use Glory\Core\AssetManager;

// --- Scripts y Estilos Generales ---
// Define las carpetas de assets que se cargarán automáticamente.
// AssetManager procesará todos los archivos .js y .css en estas carpetas,
// a menos que se especifiquen exclusiones.

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

AssetManager::define(
    'script',
    'gloryPagination',
    '/Glory/assets/js/UI/gloryPagination.js',
    [
        'deps'      => [],
        'in_footer' => true
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


// --- Configuración Específica para el Script de Navegación AJAX ---
// Se fusiona el contenido de ajaxPageSetup.php aquí para centralizar la configuración.
