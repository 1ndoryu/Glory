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
]);