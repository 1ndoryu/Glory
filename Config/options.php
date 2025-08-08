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