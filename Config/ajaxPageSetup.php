<?php

use Glory\Core\ScriptManager;

/**
 * Configuración para el script de navegación AJAX (gloryAjaxNav).
 *
 * Este script maneja la carga de contenido de forma dinámica para mejorar la experiencia de usuario.
 * Los datos localizados configuran su comportamiento, como selectores, URLs a ignorar, etc.
 */
ScriptManager::define(
    'gloryAjaxNav',                                     // Handle único para el script.
    '/Glory/assets/js/genericAjax/gloryAjaxNav.js',     // Ruta al archivo JS.
    ['jquery'],                                         // Dependencias (jQuery es común para AJAX).
    null,                                               // Versión (null para cálculo automático).
    true,                                               // Cargar en el footer.
    [                                                   // Datos para wp_localize_script.
        'nombreObjeto' => 'dataGlobal',                  // Nombre del objeto JS global (window.dataGlobal).
        'datos'        => [                              // Datos específicos para el script:
            'enabled'            => true,                 // Habilitar/deshabilitar la navegación AJAX.
            'contentSelector'    => '#contentAjax',       // Selector del contenedor principal del contenido a reemplazar.
            'mainScrollSelector' => '#contentAjax',       // Selector del elemento cuya barra de scroll se gestiona.
            'loadingBarSelector' => '#loadingBar',        // Selector de la barra de carga.
            'cacheEnabled'       => true,                 // Habilitar/deshabilitar caché de páginas.
            'ignoreUrlPatterns'  => [                    // Patrones de URL a ignorar (expresiones regulares).
                '/wp-admin',
                '/wp-login\.php',
                '\.(pdf|zip|rar|jpg|jpeg|png|gif|webp|mp3|mp4|xml|txt|docx|xlsx)$', // Extensiones de archivo.
            ],
            'ignoreUrlParams'    => ['s', 'nocache', 'preview'], // Parámetros de URL que desactivan AJAX para esa petición.
            'noAjaxClass'        => 'noAjax',             // Clase para enlaces que no deben usar AJAX.
            'idUsuario'          => get_current_user_id(),  // ID del usuario actual (0 si no está logueado).
            'nonce'              => wp_create_nonce('globalNonce'), // Nonce para validaciones (ej. si se hacen peticiones AJAX al backend).
            'nombreUsuario'      => is_user_logged_in() ? wp_get_current_user()->display_name : '', // Nombre público del usuario.
            'username'           => is_user_logged_in() ? wp_get_current_user()->user_login : '', // Nombre de usuario (login).
        ],
    ],
    null, // El último parámetro de `define` en scriptSetup.php era un array de datos, aquí es null.
          // Asumo que la estructura de `define` es (handle, src, deps, ver, in_footer, localize_data)
          // y que el `null` final es correcto si no hay más argumentos o si es opcional.
          // Si `define` tuviera otro significado para el 7º argumento, esto necesitaría revisión.
);

