<?php

/**
 * PostItemComponent - Template hijo para cada item en PostRender
 * 
 * Define la estructura visual del template que se replica por cada post.
 * Solo puede existir como hijo directo de PostRenderComponent.
 * 
 * @package Glory\Gbn\Components\PostRender
 */

namespace Glory\Gbn\Components\PostRender;

use Glory\Gbn\Components\AbstractComponent;
use Glory\Gbn\Schema\SchemaBuilder;
use Glory\Gbn\Schema\Option;
use Glory\Gbn\Traits\HasSpacing;
use Glory\Gbn\Traits\HasBorder;

class PostItemComponent extends AbstractComponent
{
    use HasSpacing, HasBorder;

    protected string $id = 'postItem';
    protected string $label = 'Post Item';

    public function getSelector(): array
    {
        return [
            'attribute' => 'gloryPostItem',
            'dataAttribute' => 'data-gbn-post-item',
        ];
    }

    public function getDefaults(): array
    {
        return [
            // Layout interno
            'display' => 'flex',
            'flexDirection' => 'column',
            'alignItems' => 'stretch',
            'justifyContent' => 'flex-start',
            'gap' => '12px',
            
            // Spacing
            'padding' => '16px',
            'margin' => '',
            
            // Background
            'backgroundColor' => '',
            
            // Border
            'hasBorder' => true,
            'borderWidth' => '1px',
            'borderStyle' => 'solid',
            'borderColor' => '#e5e5e5',
            'borderRadius' => '8px',
            
            // Interacción
            'hoverEffect' => 'none',
            'cursor' => 'pointer',
            'linkBehavior' => 'card', // 'card' = toda la card es link, 'button' = solo el link interno
        ];
    }

    public function getSchema(): array
    {
        $schema = SchemaBuilder::create();

        // ═══════════════════════════════════════════════
        // Tab: LAYOUT
        // ═══════════════════════════════════════════════

        $schema->addOption(
            Option::iconGroup('display', 'Display')
                ->options([
                    ['valor' => 'block', 'etiqueta' => 'Block', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><rect x="4" y="4" width="16" height="16" rx="2"/></svg>'],
                    ['valor' => 'flex', 'etiqueta' => 'Flex', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M12 4v16"/></svg>'],
                ])
                ->default('flex')
                ->tab('Layout')
        );

        $schema->addOption(
            Option::iconGroup('flexDirection', 'Dirección')
                ->options([
                    ['valor' => 'column', 'etiqueta' => 'Columna', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M12 5v14"/><path d="M5 12l7 7 7-7"/></svg>'],
                    ['valor' => 'row', 'etiqueta' => 'Fila', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg>'],
                ])
                ->default('column')
                ->tab('Layout')
                ->condition(['display', '==', 'flex'])
        );

        $schema->addOption(
            Option::iconGroup('alignItems', 'Alinear Items')
                ->options([
                    ['valor' => 'flex-start', 'etiqueta' => 'Inicio', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M4 4h16"/><rect x="8" y="8" width="8" height="6" rx="1"/></svg>'],
                    ['valor' => 'center', 'etiqueta' => 'Centro', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M4 12h16"/><rect x="8" y="9" width="8" height="6" rx="1"/></svg>'],
                    ['valor' => 'flex-end', 'etiqueta' => 'Fin', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M4 20h16"/><rect x="8" y="10" width="8" height="6" rx="1"/></svg>'],
                    ['valor' => 'stretch', 'etiqueta' => 'Estirar', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M4 4h16"/><path d="M4 20h16"/><rect x="8" y="6" width="8" height="12" rx="1"/></svg>'],
                ])
                ->default('stretch')
                ->tab('Layout')
                ->condition(['display', '==', 'flex'])
        );

        $schema->addOption(
            Option::text('gap', 'Gap Interno')
                ->default('12px')
                ->tab('Layout')
                ->description('Espacio entre elementos internos')
        );

        // ═══════════════════════════════════════════════
        // Tab: ESTILO
        // ═══════════════════════════════════════════════

        // Spacing
        foreach ($this->getSpacingOptions() as $option) {
            $option->tab('Estilo');
            $schema->addOption($option);
        }

        // Background
        $schema->addOption(
            Option::color('backgroundColor', 'Color de Fondo')
                ->tab('Estilo')
                ->allowTransparency(true)
        );

        // Border
        $this->addBorderOptions($schema, 'Estilo');

        // ═══════════════════════════════════════════════
        // Tab: INTERACCIÓN
        // ═══════════════════════════════════════════════

        $schema->addOption(
            Option::select('hoverEffect', 'Efecto Hover')
                ->options([
                    ['valor' => 'none', 'etiqueta' => 'Ninguno'],
                    ['valor' => 'lift', 'etiqueta' => 'Elevar (Shadow)'],
                    ['valor' => 'scale', 'etiqueta' => 'Escalar'],
                    ['valor' => 'glow', 'etiqueta' => 'Brillo'],
                ])
                ->default('none')
                ->tab('Interacción')
        );

        $schema->addOption(
            Option::select('linkBehavior', 'Comportamiento Link')
                ->options([
                    ['valor' => 'card', 'etiqueta' => 'Toda la Card'],
                    ['valor' => 'button', 'etiqueta' => 'Solo Botón/Link Interno'],
                    ['valor' => 'none', 'etiqueta' => 'Sin Link'],
                ])
                ->default('card')
                ->tab('Interacción')
                ->description('Cómo se comporta la navegación al hacer clic')
        );

        $schema->addOption(
            Option::select('cursor', 'Cursor')
                ->options([
                    ['valor' => 'pointer', 'etiqueta' => 'Puntero'],
                    ['valor' => 'default', 'etiqueta' => 'Default'],
                ])
                ->default('pointer')
                ->tab('Interacción')
                ->condition(['linkBehavior', '!=', 'none'])
        );

        return $schema->toArray();
    }

    public function getIcon(): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="5" width="14" height="14" rx="2"/><path d="M9 9h6v4H9z"/><path d="M9 17h6"/></svg>';
    }

    public function getTemplate(): string
    {
        return <<<HTML
<article gloryPostItem class="post-item" style="display: flex; flex-direction: column; gap: 12px; padding: 16px; border: 1px solid #e5e5e5; border-radius: 8px;">
    <div gloryPostField="featuredImage" style="width: 100%; aspect-ratio: 16/9; background: #f0f0f0; border-radius: 4px; overflow: hidden;">
        <img src="" alt="" style="width: 100%; height: 100%; object-fit: cover;">
    </div>
    <h3 gloryPostField="title" style="margin: 0; font-size: 18px; font-weight: 600;">Título del Post</h3>
    <p gloryPostField="excerpt" style="margin: 0; color: #666; font-size: 14px; line-height: 1.5;">Extracto del contenido aparecerá aquí...</p>
</article>
HTML;
    }

    /**
     * PostItem acepta PostField (campos semánticos) y elementos de diseño.
     * Permite construir templates flexibles para cada post.
     * 
     * @return array<string>
     */
    public function getAllowedChildren(): array
    {
        return ['postField', 'text', 'image', 'secundario', 'button'];
    }
}
