<?php

use Glory\Manager\AssetManager;
use Glory\Manager\OpcionManager;
use Glory\Integration\Compatibility;
use Glory\Core\GloryFeatures;

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

if (GloryFeatures::isActive('navegacionAjax', 'glory_componente_navegacion_ajax_activado')) {
    // Config base para la navegación AJAX, filtrable desde el tema
    $glory_nav_config = [
        'enabled'            => true,
        'contentSelector'    => '#main',
        'mainScrollSelector' => '#main',
        'loadingBarSelector' => '#loadingBar',
        'cacheEnabled'       => true,
        'ignoreUrlPatterns'  => [
            '/wp-admin',
            '/wp-login\.php',
            '\\.(pdf|zip|rar|jpg|jpeg|png|gif|webp|mp3|mp4|xml|txt|docx|xlsx)$',
        ],
        'ignoreUrlParams'    => ['s', 'nocache', 'preview'],
        'noAjaxClass'        => 'noAjax',
        'idUsuario'          => get_current_user_id(),
        'nonce'              => wp_create_nonce('globalNonce'),
        'nombreUsuario'      => is_user_logged_in() ? wp_get_current_user()->display_name : '',
        'username'           => is_user_logged_in() ? wp_get_current_user()->user_login : '',
    ];

    // Permite al tema modificar fácilmente esta configuración
    if (function_exists('apply_filters')) {
        $glory_nav_config = apply_filters('glory/nav_config', $glory_nav_config);
    }

    AssetManager::define(
        'script',
        'glory-gloryajaxnav',
        '/Glory/assets/js/genericAjax/gloryAjaxNav.js',
        [
            'deps'      => ['jquery'],
            'in_footer' => true,
            'localize'  => [
                'nombreObjeto' => 'gloryNavConfig',
                'datos'        => $glory_nav_config,
            ]
        ]
    );
}

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
        'gloryAjaxNav.js',
        'gloryForm.js',
        'gloryBusqueda.js',
        'gloryAjax.js',
        'adaptiveHeader.js',
        'alertas.js',
        'crearfondo.js',
        'formModal.js',
        'gloryModal.js',
        'pestanas.js',
        'submenus.js',
        'gestionarPreviews.js',
        'gloryPagination.js',
        'gloryFilters.js',
        'gloryScheduler.js',
        'menu.js',
        'gloryDateRange.js'
    ]
);

// --- Carga condicional de Componentes UI ---

// Componente: Modales
AssetManager::define(
    'script',
    'glory-crearfondo',
    '/Glory/assets/js/UI/crearfondo.js',
    ['deps' => ['jquery'], 'in_footer' => true, 'area' => 'both', 'feature' => 'modales']
);
AssetManager::define(
    'script',
    'glory-modal',
    '/Glory/assets/js/UI/gloryModal.js',
    ['deps' => ['jquery', 'glory-crearfondo'], 'in_footer' => true, 'area' => 'both', 'feature' => 'modales']
);
AssetManager::define(
    'script',
    'glory-formmodal',
    '/Glory/assets/js/UI/formModal.js',
    ['deps' => ['jquery', 'glory-modal', 'glory-gloryform', 'glory-ajax'], 'in_footer' => true, 'area' => 'both', 'feature' => 'modales']
);

// Componente: Submenús
AssetManager::define(
    'script',
    'glory-submenus',
    '/Glory/assets/js/UI/submenus.js',
    ['deps' => ['jquery'], 'in_footer' => true, 'feature' => 'submenus']
);

// Componente: Pestañas
AssetManager::define(
    'script',
    'glory-pestanas',
    '/Glory/assets/js/UI/pestanas.js',
    ['deps' => ['jquery'], 'in_footer' => true, 'feature' => 'pestanas']
);

// Componente: Header Adaptativo
AssetManager::define(
    'script',
    'glory-adaptiveheader',
    '/Glory/assets/js/UI/adaptiveHeader.js',
    ['deps' => [], 'in_footer' => true, 'feature' => 'headerAdaptativo']
);

// Componente: Alertas
AssetManager::define(
    'script',
    'glory-alertas',
    '/Glory/assets/js/UI/alertas.js',
    ['deps' => [], 'in_footer' => true, 'area' => 'both', 'feature' => 'alertas']
);
// Registrar también el CSS de alertas solo si la feature está activada
AssetManager::define(
    'style',
    'glory-alerts',
    '/Glory/assets/css/alert.css',
    ['media' => 'all', 'area' => 'frontend', 'feature' => 'alertas']
);

// Componente: Previews
AssetManager::define(
    'script',
    'glory-gestionarpreviews',
    '/Glory/assets/js/UI/gestionarPreviews.js',
    ['deps' => ['jquery'], 'in_footer' => true, 'feature' => 'gestionarPreviews']
);

// Componente: Paginación
AssetManager::define(
    'script',
    'glory-glorypagination',
    '/Glory/assets/js/UI/gloryPagination.js',
    ['deps' => ['jquery'], 'in_footer' => true, 'feature' => 'paginacion']
);

// Componente: Filtros (actualización en tiempo real)
AssetManager::define(
    'script',
    'glory-gloryfilters',
    '/Glory/assets/js/UI/gloryFilters.js',
    ['deps' => ['jquery', 'glory-ajax'], 'in_footer' => true, 'feature' => 'gloryFilters']
);

// Componente: DateRange (usa el mismo feature que gloryFilters)
AssetManager::define(
    'script',
    'glory-glorydaterange',
    '/Glory/assets/js/UI/gloryDateRange.js',
    ['deps' => ['jquery'], 'in_footer' => true, 'area' => 'both', 'feature' => 'gloryFilters']
);

AssetManager::define(
    'style',
    'glory-daterange',
    '/Glory/assets/css/dateRange.css',
    ['media' => 'all', 'area' => 'both', 'feature' => 'gloryFilters']
);

// Componente: Scheduler
AssetManager::define(
    'script',
    'glory-gloryscheduler',
    '/Glory/assets/js/UI/gloryScheduler.js',
    ['deps' => ['jquery'], 'in_footer' => true, 'feature' => 'scheduler']
);

// Componente: Menu
AssetManager::define(
    'script',
    'glory-menu',
    '/Glory/assets/js/UI/menu.js',
    ['deps' => ['jquery'], 'in_footer' => true, 'feature' => 'menu']
);


// --- Scripts de Servicios (controlables por feature) ---

// Manejador de formularios
AssetManager::define(
    'script',
    'glory-gloryform',
    '/Glory/assets/js/Services/gloryForm.js',
    ['deps' => ['jquery'], 'in_footer' => true, 'area' => 'both', 'feature' => 'gloryForm']
);

// Función AJAX genérica
AssetManager::define(
    'script',
    'glory-ajax',
    '/Glory/assets/js/genericAjax/gloryAjax.js',
    ['deps' => ['jquery'], 'in_footer' => true, 'area' => 'both', 'feature' => 'gloryAjax']
);

// Servicio: Búsqueda
AssetManager::define(
    'script',
    'glory-glorybusqueda',
    '/Glory/assets/js/Services/gloryBusqueda.js',
    ['deps' => ['jquery', 'glory-ajax'], 'in_footer' => true, 'area' => 'frontend', 'feature' => 'gloryBusqueda']
);

// Carga de todos los estilos CSS de la carpeta /assets/css/
AssetManager::defineFolder(
    'style',
    '/Glory/assets/css/',
    ['deps' => [], 'media' => 'all'],
    'glory-',
    [
        'alert.css'
    ]
);
