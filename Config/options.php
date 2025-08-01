<?php

use Glory\Manager\OpcionManager;
use Glory\Core\Compatibility;

if (Compatibility::is_avada_active()) {
    // Opciones cuando Avada está activo
    OpcionManager::register('glory_logo_mode', [
        'valorDefault'    => 'default',
        'tipo'            => 'select',
        'etiqueta'        => 'Header Logo Mode',
        'descripcion'     => 'Select how the site logo should be displayed. In "Default" mode, the logo is managed via <strong>Avada > Options > Logo</strong>.',
        'opciones'        => [
            'default' => 'Default (Managed by Avada)',
            'text'    => 'Custom Text',
            'none'    => 'No Logo',
        ],
        'seccion'         => 'header',
        'etiquetaSeccion' => 'Header Settings',
        'subSeccion'      => 'logo_configuration',
    ]);
} else {
    // Opciones cuando Avada NO está activo
    OpcionManager::register('glory_logo_mode', [
        'valorDefault'    => 'image',
        'tipo'            => 'select',
        'etiqueta'        => 'Header Logo Mode',
        'descripcion'     => 'Select how the site logo should be displayed.',
        'opciones'        => [
            'image' => 'Image Logo',
            'text'  => 'Custom Text',
            'none'  => 'No Logo',
        ],
        'seccion'         => 'header',
        'etiquetaSeccion' => 'Header Settings',
        'subSeccion'      => 'logo_configuration',
    ]);

    OpcionManager::register('glory_logo_image', [
        'valorDefault' => '',
        'tipo'         => 'imagen',
        'etiqueta'     => 'Logo Image',
        'descripcion'  => 'Upload or select the logo image to be used when "Image Logo" mode is selected.',
        'seccion'      => 'header',
        'subSeccion'   => 'logo_configuration',
    ]);
}

// Opción común para el logo de texto, se mostrará condicionalmente
OpcionManager::register('glory_logo_text', [
    'valorDefault' => get_bloginfo('name', 'display'),
    'tipo'         => 'text',
    'etiqueta'     => 'Logo Text',
    'descripcion'  => 'This text will be used as the logo when "Custom Text" mode is selected.',
    'seccion'      => 'header',
    'subSeccion'   => 'logo_configuration',
]);