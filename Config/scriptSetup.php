<?php

use Glory\Manager\AssetManager;
use Glory\Manager\OpcionManager;
use Glory\Integration\Compatibility;

/*
 * jules refactor: proxima tarea, considerar obtener todos los valores de las opciones de componentes
 * en un solo array al principio de este archivo para evitar llamadas repetidas a OpcionManager::get().
 * Ejemplo: $componentes = ['modales' => OpcionManager::get(...), 'submenus' => OpcionManager::get(...)];
 * y luego usar if ($componentes['modales']).
 */

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

// El script de Navegación AJAX siempre se registra para poder localizar sus datos.
// Su activación se controla mediante la variable 'enabled' que se pasa a JavaScript.
AssetManager::define(
    'script',
    'glory-gloryajaxnav', // Se ha cambiado el handle para seguir el prefijo 'glory-'
    '/Glory/assets/js/genericAjax/gloryAjaxNav.js',
    [
        'deps'      => ['jquery'],
        'in_footer' => true,
        'localize'  => [
            'nombreObjeto' => 'dataGlobal',
            'datos'        => [
                'enabled'            => (bool) OpcionManager::get('glory_componente_navegacion_ajax_activado', true),
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

// Carga de la mayoría de scripts de la carpeta /assets/js/
// Se excluyen los scripts que ahora son opcionales para definirlos condicionalmente más abajo.
AssetManager::defineFolder(
    'script',
    '/Glory/assets/js/',
    [
        'deps'      => ['jquery'],
        'in_footer' => true,
    ],
    'glory-',
    [
        // Exclusiones de utilidad
        'adminPanel.js',
        'gloryLogs.js',
        'options-panel.js',
        'disableMenuClicksInFusionBuilder.js',
        'fusionBuilderDetect.js',
        'gloryAjaxNav.js', // Se define manualmente arriba
        // Exclusiones de componentes opcionales
        'adaptiveHeader.js',
        'alertas.js',
        'crearfondo.js',
        'formModal.js',
        'gloryModal.js',
        'pestanas.js',
        'submenus.js',
        'gestionarPreviews.js',
        'gloryPagination.js',
        'gloryScheduler.js',
        'menu.js',
    ]
);

// --- Carga condicional de Componentes UI ---

// Componente: Modales
if (OpcionManager::get('glory_componente_modales_activado', true)) {
    AssetManager::define(
        'script',
        'glory-crearfondo',
        '/Glory/assets/js/UI/crearfondo.js',
        ['deps' => ['jquery'], 'in_footer' => true, 'area' => 'both']
    );
    AssetManager::define(
        'script',
        'glory-modal',
        '/Glory/assets/js/UI/gloryModal.js',
        ['deps' => ['jquery', 'glory-crearfondo'], 'in_footer' => true, 'area' => 'both']
    );
    AssetManager::define(
        'script',
        'glory-formmodal',
        '/Glory/assets/js/UI/formModal.js',
        ['deps' => ['jquery', 'glory-modal', 'glory-gloryform', 'glory-ajax'], 'in_footer' => true, 'area' => 'both']
    );
    AssetManager::define(
        'style',
        'glory-modal-css',
        '/assets/css/reservas-admin.css',
        ['media' => 'all', 'area' => 'both']
    );
}

// Componente: Submenús
if (OpcionManager::get('glory_componente_submenus_activado', true)) {
    AssetManager::define(
        'script',
        'glory-submenus',
        '/Glory/assets/js/UI/submenus.js',
        ['deps' => ['jquery'], 'in_footer' => true]
    );
}

// Componente: Pestañas
if (OpcionManager::get('glory_componente_pestanas_activado', true)) {
    AssetManager::define(
        'script',
        'glory-pestanas',
        '/Glory/assets/js/UI/pestanas.js',
        ['deps' => ['jquery'], 'in_footer' => true]
    );
}

// Componente: Header Adaptativo
if (OpcionManager::get('glory_componente_header_adaptativo_activado', true)) {
    AssetManager::define(
        'script',
        'glory-adaptiveheader',
        '/Glory/assets/js/UI/adaptiveHeader.js',
        ['deps' => [], 'in_footer' => true]
    );
}

// Componente: Alertas
if (OpcionManager::get('glory_componente_alertas_activado', true)) {
    AssetManager::define(
        'script',
        'glory-alertas',
        '/Glory/assets/js/UI/alertas.js',
        ['deps' => [], 'in_footer' => true, 'area' => 'both']
    );
}

// Componente: Previews
if (OpcionManager::get('glory_componente_previews_activado', true)) {
    AssetManager::define(
        'script',
        'glory-gestionarpreviews',
        '/Glory/assets/js/UI/gestionarPreviews.js',
        ['deps' => ['jquery'], 'in_footer' => true]
    );
}

// Componente: Paginación
if (OpcionManager::get('glory_componente_paginacion_activado', true)) {
    AssetManager::define(
        'script',
        'glory-glorypagination',
        '/Glory/assets/js/UI/gloryPagination.js',
        ['deps' => ['jquery'], 'in_footer' => true]
    );
}

// Componente: Scheduler
if (OpcionManager::get('glory_componente_scheduler_activado', true)) {
    AssetManager::define(
        'script',
        'glory-gloryscheduler',
        '/Glory/assets/js/UI/gloryScheduler.js',
        ['deps' => ['jquery'], 'in_footer' => true]
    );
}

// Componente: Menu
if (OpcionManager::get('glory_componente_menu_activado', true)) {
    AssetManager::define(
        'script',
        'glory-menu',
        '/Glory/assets/js/UI/menu.js',
        ['deps' => ['jquery'], 'in_footer' => true]
    );
}


// --- Scripts de Servicios (siempre necesarios) ---

// Manejador de formularios
AssetManager::define(
    'script',
    'glory-gloryform',
    '/Glory/assets/js/Services/gloryForm.js',
    ['deps' => ['jquery'], 'in_footer' => true, 'area' => 'both']
);

// Función AJAX genérica
AssetManager::define(
    'script',
    'glory-ajax',
    '/Glory/assets/js/genericAjax/gloryAjax.js',
    ['deps' => ['jquery'], 'in_footer' => true, 'area' => 'both']
);

// Carga de todos los estilos CSS de la carpeta /assets/css/
AssetManager::defineFolder(
    'style',
    '/Glory/assets/css/',
    ['deps' => [], 'media' => 'all'],
    'glory-'
);