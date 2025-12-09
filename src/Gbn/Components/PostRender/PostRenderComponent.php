<?php

/**
 * PostRenderComponent - Componente contenedor para renderizar listados de posts
 * 
 * Este componente permite consultar posts/CPTs y mostrarlos con un template
 * visual diseñado en el editor GBN.
 * 
 * @package Glory\Gbn\Components\PostRender
 */

namespace Glory\Gbn\Components\PostRender;

use Glory\Gbn\Components\AbstractComponent;
use Glory\Gbn\Schema\SchemaBuilder;
use Glory\Gbn\Schema\Option;
use Glory\Gbn\Schema\SchemaConstants;
use Glory\Gbn\Traits\HasSpacing;
use Glory\Gbn\Traits\HasBackground;
use Glory\Gbn\Traits\HasBorder;
use Glory\Gbn\Traits\HasLayoutOptions;
use Glory\Gbn\Services\PostRenderService;

class PostRenderComponent extends AbstractComponent
{
    use HasSpacing, HasBackground, HasBorder, HasLayoutOptions;

    protected string $id = 'postRender';
    protected string $label = 'Post Render';

    public function getSelector(): array
    {
        return [
            'attribute' => 'gloryPostRender',
            'dataAttribute' => 'data-gbn-post-render',
        ];
    }

    public function getDefaults(): array
    {
        return [
            // Query
            'postType' => 'post',
            'postsPerPage' => 6,
            'orderBy' => 'date',
            'order' => 'DESC',
            'status' => 'publish',
            'offset' => 0,
            'postIn' => '',
            'postNotIn' => '',
            
            // Layout
            SchemaConstants::FIELD_LAYOUT => 'grid',
            SchemaConstants::FIELD_GRID_COLUMNS => 3,
            SchemaConstants::FIELD_GAP => 20,
            SchemaConstants::FIELD_FLEX_DIRECTION => 'row',
            SchemaConstants::FIELD_FLEX_WRAP => 'wrap',
            SchemaConstants::FIELD_ALIGN => 'stretch',
            SchemaConstants::FIELD_JUSTIFY => 'flex-start',
            'layoutPattern' => 'none',
            'hoverEffect' => 'none',
            
            // Interacción
            'categoryFilter' => false,
            'pagination' => false,
            'loadMore' => false,
            
            // Spacing (del trait)
            'padding' => '',
            'margin' => '',
            
            // Border (del trait)
            'hasBorder' => false,
            'borderWidth' => '',
            'borderStyle' => 'solid',
            'borderColor' => '',
            'borderRadius' => '',
        ];
    }

    public function getSchema(): array
    {
        $schema = SchemaBuilder::create();

        // ═══════════════════════════════════════════════
        // Tab: QUERY
        // ═══════════════════════════════════════════════
        
        $schema->addOption(
            Option::select('postType', 'Tipo de Contenido')
                ->options($this->getDynamicPostTypes())
                ->tab('Query')
                ->description('Selecciona el tipo de contenido a mostrar')
        );

        $schema->addOption(
            Option::slider('postsPerPage', 'Posts por Página')
                ->min(1)
                ->max(50)
                ->step(1)
                ->default(6)
                ->tab('Query')
        );

        $schema->addOption(
            Option::select('orderBy', 'Ordenar Por')
                ->options([
                    ['valor' => 'date', 'etiqueta' => 'Fecha'],
                    ['valor' => 'title', 'etiqueta' => 'Título'],
                    ['valor' => 'rand', 'etiqueta' => 'Aleatorio'],
                    ['valor' => 'menu_order', 'etiqueta' => 'Orden de Menú'],
                    ['valor' => 'modified', 'etiqueta' => 'Última Modificación'],
                    ['valor' => 'comment_count', 'etiqueta' => 'Comentarios'],
                ])
                ->default('date')
                ->tab('Query')
        );

        $schema->addOption(
            Option::iconGroup('order', 'Orden')
                ->options([
                    [
                        'valor' => 'DESC', 
                        'etiqueta' => 'Descendente', 
                        'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M12 5v14"/><path d="M19 12l-7 7-7-7"/></svg>'
                    ],
                    [
                        'valor' => 'ASC', 
                        'etiqueta' => 'Ascendente', 
                        'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M12 19V5"/><path d="M5 12l7-7 7 7"/></svg>'
                    ],
                ])
                ->default('DESC')
                ->tab('Query')
        );

        $schema->addOption(
            Option::select('status', 'Estado')
                ->options([
                    ['valor' => 'publish', 'etiqueta' => 'Publicado'],
                    ['valor' => 'draft', 'etiqueta' => 'Borrador'],
                    ['valor' => 'any', 'etiqueta' => 'Cualquiera'],
                ])
                ->default('publish')
                ->tab('Query')
        );

        $schema->addOption(
            Option::slider('offset', 'Saltar Posts')
                ->min(0)
                ->max(20)
                ->step(1)
                ->default(0)
                ->tab('Query')
                ->description('Número de posts a omitir')
        );

        $schema->addOption(
            Option::text('postIn', 'IDs Específicos')
                ->tab('Query')
                ->description('IDs separados por coma para incluir')
        );

        $schema->addOption(
            Option::text('postNotIn', 'Excluir IDs')
                ->tab('Query')
                ->description('IDs separados por coma para excluir')
        );

        // ═══════════════════════════════════════════════
        // Tab: LAYOUT
        // ═══════════════════════════════════════════════

        // Usar el trait HasLayoutOptions para estandarizar
        $layoutOptions = $this->getLayoutOptions('full', SchemaConstants::FIELD_LAYOUT);
        
        foreach ($layoutOptions as $option) {
            $option->tab('Layout');
            
            // PostRender add-ons: agregar condiciones extra si es necesario
            // Por ahora el trait maneja las condiciones base (displayMode == flex/grid)
            
            $schema->addOption($option);
        }

        $schema->addOption(
            Option::select('layoutPattern', 'Patrón de Layout')
                ->options([
                    ['valor' => 'none', 'etiqueta' => 'Ninguno (Normal)'],
                    ['valor' => 'alternado_lr', 'etiqueta' => 'Alternado Izq/Der'],
                    ['valor' => 'masonry', 'etiqueta' => 'Masonry (próximamente)'],
                ])
                ->default('none')
                ->tab('Layout')
                ->description('Patrón visual de disposición de items')
        );

        $schema->addOption(
            Option::select('hoverEffect', 'Efecto Hover')
                ->options([
                    ['valor' => 'none', 'etiqueta' => 'Ninguno'],
                    ['valor' => 'lift', 'etiqueta' => 'Elevación'],
                    ['valor' => 'scale', 'etiqueta' => 'Escala'],
                    ['valor' => 'glow', 'etiqueta' => 'Resplandor'],
                ])
                ->default('none')
                ->tab('Layout')
                ->description('Efecto visual al pasar el mouse')
        );

        // ═══════════════════════════════════════════════
        // Tab: ESTILO
        // ═══════════════════════════════════════════════

        // Spacing (del trait)
        foreach ($this->getSpacingOptions() as $option) {
            $option->tab('Estilo');
            $schema->addOption($option);
        }

        // Border (del trait)
        $this->addBorderOptions($schema, 'Estilo');

        // ═══════════════════════════════════════════════
        // Tab: INTERACCIÓN
        // ═══════════════════════════════════════════════

        $schema->addOption(
            Option::toggle('categoryFilter', 'Filtro por Categoría')
                ->default(false)
                ->tab('Interacción')
                ->description('Mostrar pestañas de filtrado')
        );

        $schema->addOption(
            Option::toggle('pagination', 'Paginación')
                ->default(false)
                ->tab('Interacción')
                ->description('Habilitar paginación AJAX')
        );

        $schema->addOption(
            Option::toggle('loadMore', 'Botón Cargar Más')
                ->default(false)
                ->tab('Interacción')
                ->condition(['pagination', '==', false])
        );

        return $schema->toArray();
    }

    /**
     * Obtiene los post types dinámicamente para el selector.
     * 
     * @return array Opciones formateadas
     */
    private function getDynamicPostTypes(): array
    {
        // Fallback estático si el service no está disponible
        $defaults = [
            ['valor' => 'post', 'etiqueta' => 'Entradas'],
            ['valor' => 'page', 'etiqueta' => 'Páginas'],
        ];

        if (class_exists(PostRenderService::class)) {
            try {
                return PostRenderService::getPostTypeOptions();
            } catch (\Exception $e) {
                return $defaults;
            }
        }

        return $defaults;
    }

    public function getIcon(): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M7 7h3v3H7z"/><path d="M14 7h3v3h-3z"/><path d="M7 14h3v3H7z"/><path d="M14 14h3v3h-3z"/></svg>';
    }

    public function getTemplate(): string
    {
        // Template mínimo: contenedor + un PostItem de ejemplo
        return <<<HTML
<div gloryPostRender opciones="postType: 'post', postsPerPage: 6, layout: 'grid', gridColumns: 3, gap: '20px'" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
    <article gloryPostItem class="post-item" style="padding: 16px; border: 1px solid #e5e5e5; border-radius: 8px;">
        <div gloryPostField="featuredImage" style="width: 100%; aspect-ratio: 16/9; background: #f0f0f0; margin-bottom: 12px;">
            <img src="" alt="" style="width: 100%; height: 100%; object-fit: cover;">
        </div>
        <h3 gloryPostField="title" style="margin: 0 0 8px; font-size: 18px;">Título del Post</h3>
        <p gloryPostField="excerpt" style="margin: 0; color: #666; font-size: 14px;">Extracto del contenido aparecerá aquí...</p>
    </article>
</div>
HTML;
    }

    /**
     * PostRender solo acepta PostItem como hijo directo.
     * Los PostItem son los templates que se replican por cada post.
     * 
     * @return array<string>
     */
    public function getAllowedChildren(): array
    {
        return ['postItem'];
    }
}
