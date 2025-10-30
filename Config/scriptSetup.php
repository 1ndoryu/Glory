<?php

use Glory\Manager\AssetManager;
use Glory\Manager\OpcionManager;
use Glory\Integration\Compatibility;
use Glory\Core\GloryFeatures;
use Glory\Integration\IntegrationsManager;
use Glory\Services\QueryProfiler;

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

        // Configuración específica de Avada (agnóstico en el JS)
        'criticalScriptKeywords' => Compatibility::avadaActivo() ? [
            'formCreatorConfig',
            'fusion_form',
            'fusionAppConfig',
            'avadaVars',
            'avadaMenuVars'
        ] : [],

        // Sincronización agnóstica de SEO en navegación AJAX
        'syncHeadSeo' => true,
        'headSeoConfig' => [
            'canonicalSelector' => 'link[rel="canonical"]',
            'metaSelectors'     => ['meta[name="description"]'],
            // Reemplazar solo el JSON-LD generado por el tema (marcado con data-glory-seo)
            'jsonLdSelectors'   => ['script[type="application/ld+json"][data-glory-seo="1"]'],
        ],
    ];

    // Permite al tema modificar fácilmente esta configuración
    if (function_exists('apply_filters')) {
        $glory_nav_config = apply_filters('glory/nav_config', $glory_nav_config);
    }

    // Dependencias condicionales: fusionBuilderDetect solo si Avada está activo
    $glory_nav_deps = ['jquery'];
    if (Compatibility::avadaActivo()) {
        $glory_nav_deps[] = 'fusionBuilderDetect';
    }

    AssetManager::define(
        'script',
        'glory-gloryajaxnav',
        '/Glory/assets/js/genericAjax/gloryAjaxNav.js',
        [
            'deps'      => $glory_nav_deps,
            'in_footer' => true,
            'localize'  => [
                'nombreObjeto' => 'gloryNavConfig',
                'datos'        => $glory_nav_config,
            ]
        ]
    );

    // Hook agnóstico para Fusion Builder: configurar hooks después de localizar datos
    if (Compatibility::avadaActivo()) {
        add_action('wp_footer', function () {
?>
            <script>
                (function() {
                    // Extender configuración de Glory Nav con hooks específicos de Fusion Builder
                    if (window.gloryNavConfig) {
                        // Hook para abortar inicialización si Fusion Builder está activo
                        window.gloryNavConfig.shouldAbortInit = function() {
                            return window.isFusionBuilderActive && window.isFusionBuilderActive();
                        };

                        // Hook para saltar AJAX en enlaces de Fusion Builder
                        window.gloryNavConfig.shouldSkipAjax = function(url, linkElement) {
                            // Si estamos en modo Fusion Builder, saltar AJAX
                            if (window.isFusionBuilderActive && window.isFusionBuilderActive()) {
                                return true;
                            }
                            // Si el enlace apunta a Fusion Builder, saltar AJAX
                            try {
                                const testUrl = new URL(url, window.location.origin);
                                if (testUrl.searchParams.has('fb-edit')) {
                                    return true;
                                }
                            } catch (e) {
                                // ignore parsing errors
                            }
                            // Devolver undefined para continuar con lógica por defecto
                            return undefined;
                        };
                    }
                })();
            </script>
<?php
        }, 5); // Prioridad 5 para que se ejecute antes que el script principal
    }

    // Bridge para re-inicializar Avada Forms después de navegación AJAX
    if (Compatibility::avadaActivo()) {
        AssetManager::define(
            'script',
            'glory-avada-form-bridge',
            '/Glory/assets/js/avada-form-bridge.js',
            [
                'deps'      => ['jquery', 'glory-gloryajaxnav'],
                'in_footer' => true,
            ]
        );
    }
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
        // Mover assets de integración de Avada a carpeta específica y cargarlos solo cuando la integración esté activada
        'avada-form-bridge.js',
        'glory-carousel.js',
        'glory-horizontal-drag.js',
        'glory-toggle.js',
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
        'gloryDateRange.js',
        'gloryThemeToggle.js',
        'gloryContentActions.js',
        // Excluir componentes que deben respetar sus features
        'gloryCalendario.js',
        'masonryRowMajor.js',
        // Excluir el perfilador para definirlo de forma controlada abajo
        'query-profiler.js'
    ]
);

// Estos scripts pertenecen a la integración con Avada y se registran solo si la integración está activa.
if (GloryFeatures::isActive('avadaIntegration') !== false) {
    AssetManager::define(
        'script',
        'glory-glory-toggle',
        '/Glory/assets/js/integration/Avada/glory-toggle.js',
        [
            'deps'      => [],
            'in_footer' => true,
            'area'      => 'frontend',
            'feature'   => 'avadaIntegration'
        ]
    );

    AssetManager::define(
        'script',
        'glory-horizontal-drag',
        '/Glory/assets/js/integration/Avada/glory-horizontal-drag.js',
        [
            'deps'      => [],
            'in_footer' => true,
            'area'      => 'frontend',
            'feature'   => 'avadaIntegration'
        ]
    );
}

// Registrar Highlight.js (CDN) y un controlador para cambio de tema y highlight
AssetManager::define(
    'script',
    'highlightjs-cdn',
    'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/highlight.min.js',
    [
        'deps'      => [],
        'in_footer' => true,
        'area'      => 'frontend',
        'feature'   => 'highlight',
    ]
);

// Controlador local que aplica theme switching y llama a hljs.highlightAll()
AssetManager::define(
    'script',
    'glory-highlight-controller',
    '/Glory/assets/js/utils/highlightThemeController.js',
    [
        'deps'      => ['highlightjs-cdn'],
        'in_footer' => true,
        'area'      => 'frontend',
        'feature'   => 'highlight'
    ]
);

// Assets específicos de Avada: registramos el bridge y el carousel solo si la integración está activa
if (GloryFeatures::isActive('avadaIntegration') !== false) {
    AssetManager::define(
        'script',
        'glory-avada-form-bridge',
        '/Glory/assets/js/integration/Avada/avada-form-bridge.js',
        [
            'deps'      => ['jquery'],
            'in_footer' => true,
            'area'      => 'frontend',
            'feature'   => 'avadaIntegration'
        ]
    );

    AssetManager::define(
        'script',
        'glory-glory-carousel',
        '/Glory/assets/js/integration/Avada/glory-carousel.js',
        [
            'deps'      => [],
            'in_footer' => true,
            'area'      => 'frontend',
            'feature'   => 'avadaIntegration'
        ]
    );
}

// Registrar GSAP desde CDN como asset opcional controlado por feature 'gsap'
AssetManager::define(
    'script',
    'gsap-cdn',
    'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js',
    [
        'deps'      => [],
        'in_footer' => true,
        'area'      => 'frontend',
        'feature'   => 'gsap'
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

// Componente: Content Actions (agnóstico)
AssetManager::define(
    'script',
    'glory-content-actions',
    '/Glory/assets/js/UI/gloryContentActions.js',
    ['deps' => ['jquery', 'glory-ajax'], 'in_footer' => true, 'area' => 'both', 'feature' => 'contentActions']
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
    ['deps' => ['gsap-cdn'], 'in_footer' => true, 'feature' => 'headerAdaptativo']
);

// Componente: Theme Toggle (core)
AssetManager::define(
    'script',
    'gloryThemeToggle',
    '/Glory/assets/js/UI/gloryThemeToggle.js',
    // Desactivar 'defer' explícitamente para asegurar inicialización consistente
    ['deps' => [], 'in_footer' => true, 'defer' => false, 'feature' => 'themeToggle']
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

// Componente: Calendario (gloryCalendario)
AssetManager::define(
    'script',
    'glory-calendario',
    '/Glory/assets/js/UI/gloryCalendario.js',
    ['deps' => ['jquery'], 'in_footer' => true, 'feature' => 'calendario']
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

// Componente: BadgeList (lista agnóstica de badges que persiste estado y controla visibilidad)
AssetManager::define(
    'script',
    'glory-badgelist',
    '/Glory/assets/js/UI/badgeList.js',
    ['deps' => ['jquery'], 'in_footer' => true, 'feature' => 'badgeList']
);

// Masonry row-major agnóstico para páginas de ejemplos (se activa cuando existe el contenedor)
AssetManager::define(
    'script',
    'glory-masonry-row-major',
    '/Glory/assets/js/UI/masonryRowMajor.js',
    ['deps' => ['jquery'], 'in_footer' => true, 'feature' => 'badgeList']
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
    [
        'deps'      => ['jquery'],
        'in_footer' => false,      // cargar en <head>
        'defer'     => false,      // ejecutar inmediatamente
        'area'      => 'both',
        'feature'   => 'gloryAjax'
    ]
);

// Servicio: Búsqueda
AssetManager::define(
    'script',
    'glory-glorybusqueda',
    '/Glory/assets/js/Services/gloryBusqueda.js',
    ['deps' => ['jquery', 'glory-ajax'], 'in_footer' => true, 'area' => 'frontend', 'feature' => 'gloryBusqueda']
);

// Servicio: Realtime (polling por AJAX)
AssetManager::define(
    'script',
    'glory-gloryrealtime',
    '/Glory/assets/js/Services/gloryRealtime.js',
    ['deps' => ['jquery', 'glory-ajax'], 'in_footer' => true, 'area' => 'both', 'feature' => 'gloryRealtime']
);

// Carga de todos los estilos CSS de la carpeta /assets/css/
// Excluir archivos de administración para que no se encolen en el front
AssetManager::defineFolder(
    'style',
    '/Glory/assets/css/',
    ['deps' => [], 'media' => 'all'],
    'glory-',
    [
        'alert.css',
        'admin-panel.css',
        'admin-elementor.css',
        'dateRange.css',
        // Excluir el perfilador para definirlo de forma controlada abajo
        'query-profiler.css',
    ]
);

// CSS específico para admin cuando Elementor está activo: ocultar el banner "Get Pro"
if (class_exists('Elementor\\Plugin')) {
    AssetManager::define(
        'style',
        'glory-admin-elementor-tweaks',
        '/Glory/assets/css/admin-elementor.css',
        [
            'deps'  => [],
            'media' => 'all',
            'area'  => 'admin',
            'ver'   => filemtime(get_template_directory() . '/Glory/assets/css/admin-elementor.css'),
        ]
    );
}

AssetManager::define(
    'script',
    'glory-scheduler-admin',
    '/Glory/assets/js/UI/gloryScheduler.js',
    [
        'deps'      => [],
        'in_footer' => true,
        'area'      => 'admin',
        'feature'   => 'scheduler',
    ]
);

AssetManager::define(
    'script',
    'glory-realtime-core',
    '/Glory/assets/js/Core/gloryRealtime.js',
    [
        'deps'      => [],
        'in_footer' => true,
        'area'      => 'both',
        'feature'   => 'gloryRealtime',
    ]
);

// --- Query Profiler (UI + Datos) ---
// Siempre definimos assets; inicialización diferida para respetar overrides en App/Config/control.php
add_action('after_setup_theme', [QueryProfiler::class, 'init'], 100);

AssetManager::define(
    'style',
    'glory-query-profiler',
    '/Glory/assets/css/query-profiler.css',
    [
        'media'   => 'all',
        'area'    => 'both',
        'dev_mode' => true,
        'feature' => 'queryProfiler',
    ]
);

$query_profiler_deps = ['jquery'];
if (Compatibility::avadaActivo()) {
    $query_profiler_deps[] = 'fusionBuilderDetect';
}

AssetManager::define(
    'script',
    'glory-query-profiler',
    '/Glory/assets/js/query-profiler.js',
    [
        'deps'      => $query_profiler_deps,
        'in_footer' => true,
        'area'      => 'both',
        'dev_mode'  => true,
        'feature'   => 'queryProfiler',
    ]
);
