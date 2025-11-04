<?php

// Este archivo está reservado para opciones que son intrínsecas al funcionamiento del framework Glory.
// Las opciones específicas del tema deben definirse en App/Config/opcionesTema.php.

use Glory\Manager\OpcionManager;

OpcionManager::register('glory_css_critico_activado', [
    'valorDefault'  => false,
    'tipo'          => 'checkbox',
    'etiqueta'      => 'Activar CSS Crítico',
    'descripcion'   => 'Genera y aplica automáticamente CSS crítico para mejorar los tiempos de carga. Esto puede tardar unos segundos en la primera visita a una página.',
    'seccion'       => 'performance',
    'etiquetaSeccion' => 'Rendimiento',
    'featureKey'    => 'cssCritico'
]);

OpcionManager::register('glory_css_critico_auto', [
    'valorDefault'  => false,
    'tipo'          => 'toggle',
    'etiqueta'      => 'Generación automática',
    'descripcion'   => 'Si está activo, se intentará generar CSS crítico automáticamente en la primera visita. Si está inactivo, solo se usará el CSS crítico cacheado y podrás generarlo manualmente desde el admin.',
    'seccion'       => 'performance',
    'etiquetaSeccion' => 'Rendimiento',
]);

OpcionManager::register('glory_critical_css_mode', [
    'valorDefault'  => 'local',
    'tipo'          => 'select',
    'etiqueta'      => 'Modo de CSS crítico',
    'descripcion'   => 'local (Penthouse en tu servidor) o remote (endpoint HTTP configurable).',
    'opciones'      => [
        'local'  => 'Local (Puppeteer/Penthouse)',
        'remote' => 'Remoto (HTTP)'
    ],
    'seccion'       => 'performance',
    'etiquetaSeccion' => 'Rendimiento',
]);

OpcionManager::register('glory_critical_css_node_path', [
    'valorDefault'  => 'node',
    'tipo'          => 'text',
    'etiqueta'      => 'Ruta binario Node (opcional)',
    'descripcion'   => 'Si el binario node no está en PATH, indica ruta completa. También puedes usar env GLORY_CRITICAL_CSS_NODE.',
    'seccion'       => 'performance',
    'etiquetaSeccion' => 'Rendimiento',
]);

// Endpoint configurable para la API de CSS crítico (y respaldo)
OpcionManager::register('glory_critical_css_api_url', [
    'valorDefault'  => 'https://critical-css-api.glorycat.workers.dev/',
    'tipo'          => 'text',
    'etiqueta'      => 'Endpoint API CSS crítico',
    'descripcion'   => 'URL del servicio que devuelve CSS crítico para una URL. Puedes sobreescribir por variable de entorno GLORY_CRITICAL_CSS_API.',
    'seccion'       => 'performance',
    'etiquetaSeccion' => 'Rendimiento',
]);

OpcionManager::register('glory_critical_css_api_backup_url', [
    'valorDefault'  => '',
    'tipo'          => 'text',
    'etiqueta'      => 'Endpoint de respaldo CSS crítico',
    'descripcion'   => 'URL alternativa si falla el endpoint principal.',
    'seccion'       => 'performance',
    'etiquetaSeccion' => 'Rendimiento',
]);

// Pestaña de estado de CSS crítico (render personalizado)
OpcionManager::register('glory_critical_css_status_box', [
    'valorDefault'  => '',
    'tipo'          => 'custom',
    'etiqueta'      => 'Estado de CSS crítico',
    'descripcion'   => 'Listado de páginas con CSS crítico generado.',
    'renderCallback'=> [\Glory\Services\GestorCssCritico::class, 'renderAdminStatus'],
    'seccion'       => 'performance',
    'etiquetaSeccion' => 'Rendimiento',
    'subSeccion'    => 'critical_status',
]);

// Perfilador de Consultas (UI). Por defecto activo en DEV, inactivo en PROD.
OpcionManager::register('glory_query_profiler_activado', [
    'valorDefault'  => false,
    'tipo'          => 'toggle',
    'etiqueta'      => 'Activar Query Profiler (UI)',
    'descripcion'   => 'Muestra un widget minimalista con las consultas SQL y sus tiempos (front y admin).',
    'seccion'       => 'debug',
    'etiquetaSeccion' => 'Depuración',
    'featureKey'    => 'queryProfiler',
    'hideInProd'    => true,
]);

// Perfilador de Consultas (Logs). Por defecto inactivo. Si se activa, escribe Top 10 más lentas por petición.
OpcionManager::register('glory_query_profiler_logs_activado', [
    'valorDefault'  => false,
    'tipo'          => 'toggle',
    'etiqueta'      => 'Activar Query Profiler Logs',
    'descripcion'   => 'Escribe en el log el Top 10 de consultas más lentas por petición.',
    'seccion'       => 'debug',
    'etiquetaSeccion' => 'Depuración',
    'featureKey'    => 'queryProfilerLogs',
    'hideInProd'    => true,
]);

// Perfilador de Rendimiento (Performance Profiler). Por defecto inactivo. Mide tiempos de carga de componentes Glory.
OpcionManager::register('glory_performance_profiler_activado', [
    'valorDefault'  => false,
    'tipo'          => 'toggle',
    'etiqueta'      => 'Activar Performance Profiler',
    'descripcion'   => 'Mide y registra los tiempos de carga de cada componente de Glory para identificar cuellos de botella.',
    'seccion'       => 'debug',
    'etiquetaSeccion' => 'Depuración',
    'featureKey'    => 'performanceProfiler',
    'hideInProd'    => true,
]);

/*
 * jules refactor: proxima tarea, crear un sistema de registro de funcionalidades centralizado.
 *
 * Estas opciones controlan la carga de los componentes de JavaScript del framework.
 * Por defecto, todos los componentes están activados.
 *
 * Para desactivar un componente desde el código de tu tema (ej. functions.php),
 * puedes usar la clase `GloryFeatures`. Esta anulará la configuración del panel de opciones.
 *
 * Ejemplo de uso en tu tema:
 *
 * use Glory\Core\GloryFeatures;
 *
 * // Desactiva los modales y la navegación AJAX
 * GloryFeatures::disable('modales');
 * GloryFeatures::disable('navegacionAjax');
 *
 */
OpcionManager::register('glory_componente_navegacion_ajax_activado', [
    'valorDefault'  => true,
    'tipo'          => 'toggle',
    'etiqueta'      => 'Activar Navegación AJAX',
    'descripcion'   => 'Activa la navegación tipo SPA (Single Page Application) que carga el contenido sin recargar la página.',
    'seccion'       => 'componentes',
    'etiquetaSeccion' => 'Componentes',
    'featureKey'    => 'navegacionAjax' // Clave para el control por código con GloryFeatures
]);

OpcionManager::register('glory_componente_modales_activado', [
    'valorDefault'  => true,
    'tipo'          => 'toggle',
    'etiqueta'      => 'Activar Modales',
    'descripcion'   => 'Activa el sistema de ventanas modales (`gloryModal.js`).',
    'seccion'       => 'componentes',
    'etiquetaSeccion' => 'Componentes',
    'featureKey'    => 'modales'
]);

OpcionManager::register('glory_componente_submenus_activado', [
    'valorDefault'  => true,
    'tipo'          => 'toggle',
    'etiqueta'      => 'Activar Submenús',
    'descripcion'   => 'Activa la funcionalidad para menús desplegables (`submenus.js`).',
    'seccion'       => 'componentes',
    'etiquetaSeccion' => 'Componentes',
    'featureKey'    => 'submenus'
]);

OpcionManager::register('glory_componente_pestanas_activado', [
    'valorDefault'  => true,
    'tipo'          => 'toggle',
    'etiqueta'      => 'Activar Pestañas',
    'descripcion'   => 'Activa la funcionalidad para sistemas de pestañas (`pestanas.js`).',
    'seccion'       => 'componentes',
    'etiquetaSeccion' => 'Componentes',
    'featureKey'    => 'pestanas'
]);

OpcionManager::register('glory_componente_header_adaptativo_activado', [
    'valorDefault'  => true,
    'tipo'          => 'toggle',
    'etiqueta'      => 'Activar Cabecera Adaptativa',
    'descripcion'   => 'Activa el cambio de color automático del texto del header (`adaptiveHeader.js`).',
    'seccion'       => 'componentes',
    'etiquetaSeccion' => 'Componentes',
    'featureKey'    => 'headerAdaptativo'
]);

OpcionManager::register('glory_componente_alertas_activado', [
    'valorDefault'  => true,
    'tipo'          => 'toggle',
    'etiqueta'      => 'Activar Alertas Personalizadas',
    'descripcion'   => 'Activa el sistema de alertas y notificaciones no bloqueantes (`alertas.js`).',
    'seccion'       => 'componentes',
    'etiquetaSeccion' => 'Componentes',
    'featureKey'    => 'alertas'
]);

OpcionManager::register('glory_componente_previews_activado', [
    'valorDefault'  => true,
    'tipo'          => 'toggle',
    'etiqueta'      => 'Activar Previews',
    'descripcion'   => 'Activa la funcionalidad para gestionar previews (`gestionarPreviews.js`).',
    'seccion'       => 'componentes',
    'etiquetaSeccion' => 'Componentes',
    'featureKey'    => 'gestionarPreviews'
]);

OpcionManager::register('glory_componente_paginacion_activado', [
    'valorDefault'  => true,
    'tipo'          => 'toggle',
    'etiqueta'      => 'Activar Paginación',
    'descripcion'   => 'Activa la paginación dinámica (`gloryPagination.js`).',
    'seccion'       => 'componentes',
    'etiquetaSeccion' => 'Componentes',
    'featureKey'    => 'paginacion'
]);

OpcionManager::register('glory_componente_scheduler_activado', [
    'valorDefault'  => true,
    'tipo'          => 'toggle',
    'etiqueta'      => 'Activar Scheduler',
    'descripcion'   => 'Activa el scheduler de tareas (`gloryScheduler.js`).',
    'seccion'       => 'componentes',
    'etiquetaSeccion' => 'Componentes',
    'featureKey'    => 'scheduler'
]);

OpcionManager::register('glory_componente_menu_activado', [
    'valorDefault'  => true,
    'tipo'          => 'toggle',
    'etiqueta'      => 'Activar Menu',
    'descripcion'   => 'Activa las mejoras de menú (`menu.js`).',
    'seccion'       => 'componentes',
    'etiquetaSeccion' => 'Componentes',
    'featureKey'    => 'menu'
]);

// --- Opciones para managers y servicios no expuestos todavía ---
OpcionManager::register('glory_asset_manager_activado', [
    'valorDefault'    => true,
    'tipo'            => 'toggle',
    'etiqueta'        => 'Activar Asset Manager',
    'descripcion'     => 'Controla la inicialización del AssetManager del framework.',
    'seccion'         => 'core',
    'etiquetaSeccion' => 'Core',
    'featureKey'      => 'assetManager',
    'hideInProd'      => true,
    'lockInProd'      => true
]);

OpcionManager::register('glory_sync_manager_activado', [
    'valorDefault'    => true,
    'tipo'            => 'toggle',
    'etiqueta'        => 'Activar Sync Manager',
    'descripcion'     => 'Controla la inicialización del SyncManager del framework.',
    'seccion'         => 'core',
    'etiquetaSeccion' => 'Core',
    'featureKey'      => 'syncManager',
    'hideInProd'      => true,
    'lockInProd'      => true
]);

OpcionManager::register('glory_logger_activado', [
    'valorDefault'    => true,
    'tipo'            => 'toggle',
    'etiqueta'        => 'Activar Logger',
    'descripcion'     => 'Activa el sistema de logging interno (GloryLogger).',
    'seccion'         => 'core',
    'etiquetaSeccion' => 'Core',
    'featureKey'      => 'gloryLogger',
    'hideInProd'      => true
]);

OpcionManager::register('glory_default_content_manager_activado', [
    'valorDefault'    => true,
    'tipo'            => 'toggle',
    'etiqueta'        => 'Activar Default Content Manager',
    'descripcion'     => 'Controla la inicialización del DefaultContentManager.',
    'seccion'         => 'core',
    'etiquetaSeccion' => 'Core',
    'featureKey'      => 'defaultContentManager',
    'hideInProd'      => true
]);

OpcionManager::register('glory_page_manager_activado', [
    'valorDefault'    => true,
    'tipo'            => 'toggle',
    'etiqueta'        => 'Activar Page Manager',
    'descripcion'     => 'Controla la inicialización del PageManager.',
    'seccion'         => 'core',
    'etiquetaSeccion' => 'Core',
    'featureKey'      => 'pageManager',
    'hideInProd'      => true
]);

OpcionManager::register('glory_post_type_manager_activado', [
    'valorDefault'    => true,
    'tipo'            => 'toggle',
    'etiqueta'        => 'Activar Post Type Manager',
    'descripcion'     => 'Controla la inicialización del PostTypeManager.',
    'seccion'         => 'core',
    'etiquetaSeccion' => 'Core',
    'featureKey'      => 'postTypeManager',
    'hideInProd'      => true
]);

OpcionManager::register('glory_taxonomy_meta_manager_activado', [
    'valorDefault'    => true,
    'tipo'            => 'toggle',
    'etiqueta'        => 'Activar Taxonomy Meta Manager',
    'descripcion'     => 'Controla la inicialización del TaxonomyMetaManager.',
    'seccion'         => 'core',
    'etiquetaSeccion' => 'Core',
    'featureKey'      => 'taxonomyMetaManager',
    'hideInProd'      => true
]);

OpcionManager::register('glory_schedule_manager_activado', [
    'valorDefault'    => true,
    'tipo'            => 'toggle',
    'etiqueta'        => 'Activar Schedule Manager',
    'descripcion'     => 'Controla la inicialización del ScheduleManager.',
    'seccion'         => 'core',
    'etiquetaSeccion' => 'Core',
    'featureKey'      => 'scheduleManager',
    'hideInProd'      => true
]);

// --- Servicios y utilidades adicionales ---
OpcionManager::register('glory_ajax_service_activado', [
    'valorDefault'    => true,
    'tipo'            => 'toggle',
    'etiqueta'        => 'Activar Glory Ajax',
    'descripcion'     => 'Habilita los endpoints y callbacks AJAX relacionados con Glory.',
    'seccion'         => 'componentes',
    'etiquetaSeccion' => 'Componentes',
    'featureKey'      => 'gloryAjax'
]);

OpcionManager::register('glory_form_service_activado', [
    'valorDefault'    => true,
    'tipo'            => 'toggle',
    'etiqueta'        => 'Activar Glory Form',
    'descripcion'     => 'Controla la inicialización y uso de FormHandler.',
    'seccion'         => 'componentes',
    'etiquetaSeccion' => 'Componentes',
    'featureKey'      => 'gloryForm'
]);

OpcionManager::register('glory_busqueda_activado', [
    'valorDefault'    => true,
    'tipo'            => 'toggle',
    'etiqueta'        => 'Activar Glory Busqueda',
    'descripcion'     => 'Habilita la búsqueda avanzada gestionada por el framework.',
    'seccion'         => 'componentes',
    'etiquetaSeccion' => 'Componentes',
    'featureKey'      => 'gloryBusqueda'
]);

// Realtime por AJAX (polling)
OpcionManager::register('glory_realtime_activado', [
    'valorDefault'    => true,
    'tipo'            => 'toggle',
    'etiqueta'        => 'Activar Glory Realtime',
    'descripcion'     => 'Habilita el servicio de polling por AJAX para actualizaciones en tiempo real.',
    'seccion'         => 'componentes',
    'etiquetaSeccion' => 'Componentes',
    'featureKey'      => 'gloryRealtime'
]);

// --- Renderers y control de funcionalidades del theme ---
OpcionManager::register('glory_logo_renderer_activado', [
    'valorDefault'    => true,
    'tipo'            => 'toggle',
    'etiqueta'        => 'Activar Logo Renderer',
    'descripcion'     => 'Controla la renderización del logo a través de `LogoRenderer`.',
    'seccion'         => 'header',
    'etiquetaSeccion' => 'Header Settings',
    'featureKey'      => 'logoRenderer'
]);

// Opción para activar/desactivar el Theme Toggle
OpcionManager::register('glory_componente_theme_toggle_activado', [
    'valorDefault' => true,
    'tipo'         => 'toggle',
    'etiqueta'     => 'Activar Theme Toggle',
    'descripcion'  => 'Muestra un botón para alternar entre modo claro y oscuro.',
    'seccion'      => 'componentes',
    'etiquetaSeccion' => 'Componentes',
    'featureKey'   => 'themeToggle'
]);

OpcionManager::register('glory_content_render_activado', [
    'valorDefault'    => true,
    'tipo'            => 'toggle',
    'etiqueta'        => 'Activar Content Render',
    'descripcion'     => 'Controla el componente `ContentRender`.',
    'seccion'         => 'componentes',
    'etiquetaSeccion' => 'Componentes',
    'featureKey'      => 'contentRender'
]);

OpcionManager::register('glory_term_render_activado', [
    'valorDefault'    => true,
    'tipo'            => 'toggle',
    'etiqueta'        => 'Activar Term Render',
    'descripcion'     => 'Controla la renderización de términos personalizados.',
    'seccion'         => 'componentes',
    'etiquetaSeccion' => 'Componentes',
    'featureKey'      => 'termRender'
]);

OpcionManager::register('glory_title_tag_activado', [
    'valorDefault'    => true,
    'tipo'            => 'toggle',
    'etiqueta'        => 'Activar Title Tag',
    'descripcion'     => 'Controla la gestión del title-tag por el theme.',
    'seccion'         => 'header',
    'etiquetaSeccion' => 'Header Settings',
    'featureKey'      => 'titleTag'
]);

OpcionManager::register('glory_post_thumbnails_activado', [
    'valorDefault'    => true,
    'tipo'            => 'toggle',
    'etiqueta'        => 'Activar Post Thumbnails',
    'descripcion'     => 'Controla la habilitación de thumbnails en posts.',
    'seccion'         => 'general',
    'etiquetaSeccion' => 'General',
    'featureKey'      => 'postThumbnails'
]);

// --- Glory Builder Native (GBN) y CPT glory_link ---
OpcionManager::register('glory_gbn_activado', [
    'valorDefault'    => true,
    'tipo'            => 'toggle',
    'etiqueta'        => 'Activar Glory Builder Native (GBN)',
    'descripcion'     => 'Activa la capa de edición nativa minimalista del framework.',
    'seccion'         => 'core',
    'etiquetaSeccion' => 'Core',
    'featureKey'      => 'gbn'
]);

OpcionManager::register('glory_gbn_split_content_activado', [
    'valorDefault'    => true,
    'tipo'            => 'toggle',
    'etiqueta'        => 'GBN para GlorySplitContent',
    'descripcion'     => 'Habilita controles GBN (edición/orden) sobre GlorySplitContent.',
    'seccion'         => 'componentes',
    'etiquetaSeccion' => 'Componentes',
    'featureKey'      => 'gbnSplitContent'
]);

OpcionManager::register('glory_glory_link_cpt_activado', [
    'valorDefault'    => true,
    'tipo'            => 'toggle',
    'etiqueta'        => 'Activar CPT glory_link',
    'descripcion'     => 'Registra el post type "glory_link" para enlaces externos usados por componentes.',
    'seccion'         => 'core',
    'etiquetaSeccion' => 'Core',
    'featureKey'      => 'gloryLinkCpt'
]);