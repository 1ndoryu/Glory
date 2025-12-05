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
use Glory\Gbn\Traits\HasSpacing;
use Glory\Gbn\Traits\HasBackground;
use Glory\Gbn\Traits\HasBorder;
use Glory\Gbn\Services\PostRenderService;

class PostRenderComponent extends AbstractComponent
{
    use HasSpacing, HasBackground, HasBorder;

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
            'displayMode' => 'grid',
            'gridColumns' => 3,
            'gap' => '20px',
            'flexDirection' => 'row',
            'flexWrap' => 'wrap',
            'alignItems' => 'stretch',
            'justifyContent' => 'flex-start',
            
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

        $schema->addOption(
            Option::iconGroup('displayMode', 'Modo de Visualización')
                ->options([
                    [
                        'valor' => 'grid', 
                        'etiqueta' => 'Grid', 
                        'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18"/><path d="M15 3v18"/><path d="M3 9h18"/><path d="M3 15h18"/></svg>'
                    ],
                    [
                        'valor' => 'flex', 
                        'etiqueta' => 'Flex', 
                        'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><rect x="3" y="4" width="6" height="16" rx="1"/><rect x="11" y="4" width="4" height="16" rx="1"/><rect x="17" y="4" width="4" height="16" rx="1"/></svg>'
                    ],
                    [
                        'valor' => 'block', 
                        'etiqueta' => 'Bloque', 
                        'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>'
                    ],
                ])
                ->default('grid')
                ->tab('Layout')
        );

        $schema->addOption(
            Option::slider('gridColumns', 'Columnas')
                ->min(1)
                ->max(6)
                ->step(1)
                ->default(3)
                ->tab('Layout')
                ->condition(['displayMode', '==', 'grid'])
        );

        $schema->addOption(
            Option::text('gap', 'Espaciado (Gap)')
                ->default('20px')
                ->tab('Layout')
                ->condition(['displayMode', 'in', ['grid', 'flex']])
        );

        // Flex Options
        $schema->addOption(
            Option::iconGroup('flexDirection', 'Dirección')
                ->options([
                    [
                        'valor' => 'row', 
                        'etiqueta' => 'Horizontal', 
                        'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg>'
                    ],
                    [
                        'valor' => 'column', 
                        'etiqueta' => 'Vertical', 
                        'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M12 5v14"/><path d="M5 12l7 7 7-7"/></svg>'
                    ],
                ])
                ->default('row')
                ->tab('Layout')
                ->condition(['displayMode', '==', 'flex'])
        );

        $schema->addOption(
            Option::iconGroup('flexWrap', 'Envolver')
                ->options([
                    [
                        'valor' => 'nowrap', 
                        'etiqueta' => 'No Envolver', 
                        'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M4 12h16"/></svg>'
                    ],
                    [
                        'valor' => 'wrap', 
                        'etiqueta' => 'Envolver', 
                        'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M4 8h16"/><path d="M4 16h16"/></svg>'
                    ],
                ])
                ->default('wrap')
                ->tab('Layout')
                ->condition(['displayMode', '==', 'flex'])
        );

        $schema->addOption(
            Option::iconGroup('alignItems', 'Alinear Items')
                ->options([
                    ['valor' => 'flex-start', 'etiqueta' => 'Inicio', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M4 4h16"/><rect x="8" y="8" width="8" height="8" rx="1"/></svg>'],
                    ['valor' => 'center', 'etiqueta' => 'Centro', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M4 12h16"/><rect x="8" y="8" width="8" height="8" rx="1"/></svg>'],
                    ['valor' => 'flex-end', 'etiqueta' => 'Fin', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M4 20h16"/><rect x="8" y="8" width="8" height="8" rx="1"/></svg>'],
                    ['valor' => 'stretch', 'etiqueta' => 'Estirar', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M4 4h16"/><path d="M4 20h16"/><rect x="8" y="6" width="8" height="12" rx="1"/></svg>'],
                ])
                ->default('stretch')
                ->tab('Layout')
                ->condition(['displayMode', '==', 'flex'])
        );

        $schema->addOption(
            Option::iconGroup('justifyContent', 'Justificar')
                ->options([
                    ['valor' => 'flex-start', 'etiqueta' => 'Inicio', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><rect x="4" y="6" width="6" height="12" rx="1"/><rect x="12" y="6" width="4" height="12" rx="1"/></svg>'],
                    ['valor' => 'center', 'etiqueta' => 'Centro', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><rect x="5" y="6" width="5" height="12" rx="1"/><rect x="12" y="6" width="5" height="12" rx="1"/></svg>'],
                    ['valor' => 'flex-end', 'etiqueta' => 'Fin', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><rect x="8" y="6" width="4" height="12" rx="1"/><rect x="14" y="6" width="6" height="12" rx="1"/></svg>'],
                    ['valor' => 'space-between', 'etiqueta' => 'Espacio Entre', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><rect x="4" y="6" width="5" height="12" rx="1"/><rect x="15" y="6" width="5" height="12" rx="1"/></svg>'],
                ])
                ->default('flex-start')
                ->tab('Layout')
                ->condition(['displayMode', '==', 'flex'])
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
<div gloryPostRender opciones="postType: 'post', postsPerPage: 6, displayMode: 'grid', gridColumns: 3, gap: '20px'" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
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
}
