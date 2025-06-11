<?
use Glory\Core\AssetManager;

/**
 * Configuración para el script de navegación AJAX (gloryAjaxNav).
 *
 * Este script maneja la carga de contenido de forma dinámica. Los datos
 * localizados configuran su comportamiento (selectores, URLs a ignorar, etc.).
 */
AssetManager::define(
    'script',                                           // Tipo de asset
    'gloryAjaxNav',                                     // Handle único
    '/Assets/js/genericAjax/gloryAjaxNav.js',           // Ruta al archivo JS
    [                                                   // Array de configuración
        'deps'      => ['jquery'],
        'in_footer' => true,
        'localize'  => [
            'nombreObjeto' => 'dataGlobal',
            'datos'        => [
                'enabled'            => true,
                'contentSelector'    => '#contentAjax',
                'mainScrollSelector' => '#contentAjax',
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