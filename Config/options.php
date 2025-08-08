<?php

// Este archivo está reservado para opciones que son intrínsecas al funcionamiento del framework Glory.
// Las opciones específicas del tema deben definirse en App/Config/opcionesTema.php.

use Glory\Manager\OpcionManager;

OpcionManager::register('glory_css_critico_activado', [
    'valorDefault'  => false,
    'tipo'          => 'toggle',
    'etiqueta'      => 'Activar CSS Crítico',
    'descripcion'   => 'Genera y aplica automáticamente CSS crítico para mejorar los tiempos de carga. Esto puede tardar unos segundos en la primera visita a una página.',
    'seccion'       => 'performance',
    'etiquetaSeccion' => 'Rendimiento',
    'featureKey'    => 'cssCritico'
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
    'featureKey'      => 'assetManager'
]);

OpcionManager::register('glory_sync_manager_activado', [
    'valorDefault'    => true,
    'tipo'            => 'toggle',
    'etiqueta'        => 'Activar Sync Manager',
    'descripcion'     => 'Controla la inicialización del SyncManager del framework.',
    'seccion'         => 'core',
    'etiquetaSeccion' => 'Core',
    'featureKey'      => 'syncManager'
]);

OpcionManager::register('glory_logger_activado', [
    'valorDefault'    => true,
    'tipo'            => 'toggle',
    'etiqueta'        => 'Activar Logger',
    'descripcion'     => 'Activa el sistema de logging interno (GloryLogger).',
    'seccion'         => 'core',
    'etiquetaSeccion' => 'Core',
    'featureKey'      => 'gloryLogger'
]);

OpcionManager::register('glory_default_content_manager_activado', [
    'valorDefault'    => true,
    'tipo'            => 'toggle',
    'etiqueta'        => 'Activar Default Content Manager',
    'descripcion'     => 'Controla la inicialización del DefaultContentManager.',
    'seccion'         => 'core',
    'etiquetaSeccion' => 'Core',
    'featureKey'      => 'defaultContentManager'
]);

OpcionManager::register('glory_page_manager_activado', [
    'valorDefault'    => true,
    'tipo'            => 'toggle',
    'etiqueta'        => 'Activar Page Manager',
    'descripcion'     => 'Controla la inicialización del PageManager.',
    'seccion'         => 'core',
    'etiquetaSeccion' => 'Core',
    'featureKey'      => 'pageManager'
]);

OpcionManager::register('glory_post_type_manager_activado', [
    'valorDefault'    => true,
    'tipo'            => 'toggle',
    'etiqueta'        => 'Activar Post Type Manager',
    'descripcion'     => 'Controla la inicialización del PostTypeManager.',
    'seccion'         => 'core',
    'etiquetaSeccion' => 'Core',
    'featureKey'      => 'postTypeManager'
]);

OpcionManager::register('glory_taxonomy_meta_manager_activado', [
    'valorDefault'    => true,
    'tipo'            => 'toggle',
    'etiqueta'        => 'Activar Taxonomy Meta Manager',
    'descripcion'     => 'Controla la inicialización del TaxonomyMetaManager.',
    'seccion'         => 'core',
    'etiquetaSeccion' => 'Core',
    'featureKey'      => 'taxonomyMetaManager'
]);

OpcionManager::register('glory_schedule_manager_activado', [
    'valorDefault'    => true,
    'tipo'            => 'toggle',
    'etiqueta'        => 'Activar Schedule Manager',
    'descripcion'     => 'Controla la inicialización del ScheduleManager.',
    'seccion'         => 'core',
    'etiquetaSeccion' => 'Core',
    'featureKey'      => 'scheduleManager'
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