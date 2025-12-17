<?php

/**
 * Glory React - Configuración de Scripts y Assets
 *
 * Versión simplificada para proyectos React-first.
 * La mayoría de scripts JS nativos han sido reemplazados por React.
 *
 * @package Glory\Config
 */

use Glory\Manager\AssetManager;
use Glory\Core\GloryFeatures;
use Glory\Services\QueryProfiler;

/* 
 * Carga de estilos CSS de la carpeta /assets/css/
 * Solo estilos base que no son componentes específicos
 */

AssetManager::defineFolder(
    'style',
    '/Glory/assets/css/',
    ['deps' => [], 'media' => 'all'],
    'glory-',
    [
        /* Excluir estilos de componentes eliminados */
        'alert.css',
        'admin-panel.css',
        'admin-elementor.css',
        'dateRange.css',
        'query-profiler.css',
        'settings-panel.css',
    ]
);

/* 
 * Query Profiler (solo desarrollo)
 * Útil para debugging incluso con React
 */
add_action('after_setup_theme', [QueryProfiler::class, 'init'], 100);

AssetManager::define(
    'style',
    'glory-query-profiler',
    '/Glory/assets/css/query-profiler.css',
    [
        'media'    => 'all',
        'area'     => 'both',
        'dev_mode' => true,
        'feature'  => 'queryProfiler',
    ]
);

AssetManager::define(
    'script',
    'glory-query-profiler',
    '/Glory/assets/js/query-profiler.js',
    [
        'deps'      => ['jquery'],
        'in_footer' => true,
        'area'      => 'both',
        'dev_mode'  => true,
        'feature'   => 'queryProfiler',
    ]
);
