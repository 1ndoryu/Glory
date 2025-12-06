<?php

namespace Glory\Gbn\Components\Image;

use Glory\Gbn\Components\AbstractComponent;
use Glory\Gbn\Schema\SchemaBuilder;
use Glory\Gbn\Schema\Option;
use Glory\Gbn\Traits\HasBorder;
use Glory\Gbn\Traits\HasSpacing;
use Glory\Gbn\Traits\HasCustomCSS;

class ImageComponent extends AbstractComponent
{
    use HasBorder, HasSpacing, HasCustomCSS;

    protected string $id = 'image';
    protected string $label = 'Imagen';

    public function getSelector(): array
    {
        return [
            'attribute' => 'gloryImagen',
            'dataAttribute' => 'data-gbn-image',
        ];
    }

    public function getDefaults(): array
    {
        $placeholder = get_template_directory_uri() . '/Glory/src/Gbn/assets/js/image/landscape-placeholder.svg';
        return [
            'src' => $placeholder,
            'alt' => 'Imagen',
            'width' => '200px',
            'height' => '200px',
            'maxWidth' => '100%',
            'maxHeight' => '',
            'objectFit' => 'cover',
            'hasBorder' => false,
            'borderWidth' => '',
            'borderStyle' => 'solid',
            'borderColor' => '',
            'borderRadius' => ''
        ];
    }

    public function getSchema(): array
    {
        $schema = SchemaBuilder::create();
        $placeholder = get_template_directory_uri() . '/Glory/src/Gbn/assets/js/image/landscape-placeholder.svg';

        // 1. Source - Tab: Contenido
        $schema->addOption(
            Option::image('src', 'Fuente de Imagen')
                ->tab('Contenido')
                ->default($placeholder)
        );

        // 2. Alt Text - Tab: Contenido
        $schema->addOption(
            Option::text('alt', 'Texto Alternativo')
                ->tab('Contenido')
        );

        // 3. Dimensiones - Tab: Estilo
        // Usamos el nuevo campo visual 'dimensions' que agrupa ancho/alto
        $schema->addOption(
            Option::dimensions('dimensions', 'Dimensiones')
                ->tab('Estilo')
                // Definimos explícitamente los sub-campos que manejará el JS dimension.js
                ->options([
                    ['id' => 'width', 'label' => 'Ancho', 'icon' => '↔'],
                    ['id' => 'maxWidth', 'label' => 'Max', 'icon' => '⇥'],
                    ['id' => 'height', 'label' => 'Alto', 'icon' => '↕'],
                    ['id' => 'maxHeight', 'label' => 'Max', 'icon' => 'Bottom'] // Icono improvisado, JS maneja el render
                ])
                ->description('Control de tamaño (Width/Height)')
        );

        // 4. Object Fit - Tab: Estilo
        // Usamos iconGroup en lugar de select simple
        $schema->addOption(
            Option::iconGroup('objectFit', 'Ajuste de Imagen (Object-Fit)')
                ->options([
                    [
                        'valor' => 'cover', 
                        'etiqueta' => 'Cubrir (Cover)', 
                        'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><rect x="2" y="2" width="20" height="20" rx="2"/><line x1="2" y1="2" x2="22" y2="22"/><line x1="22" y1="2" x2="2" y2="22"/></svg>' // Simula llenar todo
                    ],
                    [
                        'valor' => 'contain', 
                        'etiqueta' => 'Contener (Contain)', 
                        'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><rect x="6" y="6" width="12" height="12" rx="2"/><rect x="2" y="2" width="20" height="20" rx="2" stroke-dasharray="4 4" opacity="0.5"/></svg>' // Simula caja dentro de caja
                    ],
                    [
                        'valor' => 'fill', 
                        'etiqueta' => 'Llenar (Fill)', 
                        'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><rect x="2" y="2" width="20" height="20"/><line x1="12" y1="2" x2="12" y2="22"/><line x1="2" y1="12" x2="22" y2="12"/></svg>' // Estirado
                    ],
                    [
                        'valor' => 'none', 
                        'etiqueta' => 'Ninguno', 
                        'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>'
                    ],
                    [
                        'valor' => 'scale-down', 
                        'etiqueta' => 'Reducir', 
                        'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><rect x="8" y="8" width="8" height="8" rx="1"/><path d="M4 4l4 4"/><path d="M20 20l-4-4"/><path d="M20 4l-4 4"/><path d="M4 20l4-4"/></svg>'
                    ]
                ])
                ->default('cover')
                ->tab('Estilo')
        );
        
        // 5. Border (Completo) - Tab: Estilo
        $this->addBorderOptions($schema, 'Estilo');

        // 6. Spacing
        foreach ($this->getSpacingOptions() as $option) {
            $option->tab('Estilo');
            $schema->addOption($option);
        }

        // 7. Custom CSS
        $schema->addOption($this->getCustomCSSOption());

        return $schema->toArray();
    }

    public function getIcon(): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>';
    }

    public function getTemplate(): string
    {
        $placeholder = get_template_directory_uri() . '/Glory/src/Gbn/assets/js/image/landscape-placeholder.svg';
        
        // IMPORTANTE:
        // 1. img style="width: 100%; height: 100%;" para llenar el contenedor y responder a object-fit.
        // 2. object-fit: var(...) para que el estilo funcione via CSS variable.
        // 3. display: block para evitar espacio fantasma inferior.
        
        return '<div gloryImagen opciones="src: \'' . $placeholder . '\', alt: \'Imagen\', width: \'200px\', height: \'200px\', maxWidth: \'100%\'" style="position: relative; display: inline-block; width: 200px; height: 200px; max-width: 100%; overflow: hidden;"><img src="' . $placeholder . '" alt="Imagen" style="width: 100%; height: 100%; object-fit: var(--gbn-img-object-fit, cover); display: block;"></div>';
    }
}
