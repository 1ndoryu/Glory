<?php

OpcionManager::register('glory_ejemplos_activo', [
    'valorDefault' => false,
    'tipo'         => 'checkbox',
    'etiqueta'     => 'Activar Opciones de Ejemplo',
    'descripcion'  => 'Marca esta casilla para mostrar una pestaña con ejemplos de todos los tipos de campos disponibles en el panel.',
    'seccion'      => 'general',
    'subSeccion'   => 'configuracion_avanzada',
]);


if (OpcionManager::get('glory_ejemplos_activo')) {

    $seccionEjemplos = 'ejemplos';
    $etiquetaSeccion = 'Ejemplos de Campos';

    OpcionManager::register('ejemplo_texto', [
        'valorDefault' => 'Este es un texto simple.',
        'tipo'         => 'text',
        'etiqueta'     => 'Campo de Texto',
        'descripcion'  => 'Para textos cortos como títulos o nombres.',
        'seccion'      => $seccionEjemplos,
        'etiquetaSeccion' => $etiquetaSeccion,
        'subSeccion'   => 'campos_de_texto',
    ]);

    OpcionManager::register('ejemplo_textarea', [
        'valorDefault' => 'Este es un área de texto para párrafos más largos.',
        'tipo'         => 'textarea',
        'etiqueta'     => 'Área de Texto',
        'descripcion'  => 'Ideal para descripciones o bloques de texto sin formato.',
        'seccion'      => $seccionEjemplos,
        'etiquetaSeccion' => $etiquetaSeccion,
        'subSeccion'   => 'campos_de_texto',
    ]);

    OpcionManager::register('ejemplo_rich_text', [
        'valorDefault' => '<p>Este es <strong>texto enriquecido</strong> usando el editor de WordPress.</p>',
        'tipo'         => 'richText',
        'etiqueta'     => 'Editor de Texto Enriquecido',
        'descripcion'  => 'Permite formato como negritas, itálicas y listas.',
        'seccion'      => $seccionEjemplos,
        'etiquetaSeccion' => $etiquetaSeccion,
        'subSeccion'   => 'campos_de_texto',
    ]);

    OpcionManager::register('ejemplo_checkbox', [
        'valorDefault' => true,
        'tipo'         => 'checkbox',
        'etiqueta'     => 'Activar Característica Ejemplo',
        'descripcion'  => 'Una simple casilla para activar o desactivar algo.',
        'seccion'      => $seccionEjemplos,
        'etiquetaSeccion' => $etiquetaSeccion,
        'subSeccion'   => 'campos_de_seleccion',
    ]);

    OpcionManager::register('ejemplo_select', [
        'valorDefault' => 'opcion2',
        'tipo'         => 'select',
        'etiqueta'     => 'Selector Desplegable',
        'descripcion'  => 'Para elegir una opción de una lista predefinida.',
        'opciones'     => [
            'opcion1' => 'Primera Opción',
            'opcion2' => 'Segunda Opción',
            'opcion3' => 'Tercera Opción',
        ],
        'seccion'      => $seccionEjemplos,
        'etiquetaSeccion' => $etiquetaSeccion,
        'subSeccion'   => 'campos_de_seleccion',
    ]);

    OpcionManager::register('ejemplo_radio', [
        'valorDefault' => 'radio_b',
        'tipo'         => 'radio',
        'etiqueta'     => 'Botones de Radio',
        'descripcion'  => 'Para elegir una única opción de un grupo visible.',
        'opciones'     => [
            'radio_a' => 'Alternativa A',
            'radio_b' => 'Alternativa B',
        ],
        'seccion'      => $seccionEjemplos,
        'etiquetaSeccion' => $etiquetaSeccion,
        'subSeccion'   => 'campos_de_seleccion',
    ]);

    OpcionManager::register('ejemplo_imagen', [
        'valorDefault' => '',
        'tipo'         => 'imagen',
        'etiqueta'     => 'Selector de Imagen',
        'descripcion'  => 'Abre la biblioteca de medios de WordPress para seleccionar una imagen.',
        'seccion'      => $seccionEjemplos,
        'etiquetaSeccion' => $etiquetaSeccion,
        'subSeccion'   => 'campos_avanzados',
    ]);

    OpcionManager::register('ejemplo_color', [
        'valorDefault' => '#21759b',
        'tipo'         => 'color',
        'etiqueta'     => 'Selector de Color',
        'descripcion'  => 'Un selector de color interactivo.',
        'seccion'      => $seccionEjemplos,
        'etiquetaSeccion' => $etiquetaSeccion,
        'subSeccion'   => 'campos_avanzados',
    ]);

    OpcionManager::register('ejemplo_numero', [
        'valorDefault' => 10,
        'tipo'         => 'numero',
        'etiqueta'     => 'Campo Numérico',
        'descripcion'  => 'Para ingresar valores numéricos.',
        'seccion'      => $seccionEjemplos,
        'etiquetaSeccion' => $etiquetaSeccion,
        'subSeccion'   => 'campos_avanzados',
    ]);
}