<?php

/**
 * PostRenderProcessor - Orquestador de renderizado para PostRender
 * 
 * Este archivo es el punto de entrada del sistema de renderizado de PostRender.
 * Orquesta los módulos especializados y expone la API pública.
 * 
 * REFACTOR-003 Completado (Diciembre 2025):
 * - Antes: 768 líneas en un solo archivo
 * - Después: ~280 líneas + 4 módulos especializados
 * 
 * Módulos del sistema:
 * - PostFieldProcessor.php   → Procesamiento de campos semánticos [gloryPostField]
 * - PostItemRenderer.php     → Renderizado de items individuales
 * - PostRenderStyles.php     → Generación de CSS scoped y estilos
 * - PostRenderUI.php         → Componentes UI (filtros, paginación, mensajes)
 * 
 * @package Glory\Gbn\Components\PostRender
 */

namespace Glory\Gbn\Components\PostRender;

use Glory\Gbn\Services\PostRenderService;
use WP_Post;
use DOMDocument;
use DOMXPath;

class PostRenderProcessor
{
    /**
     * Configuración actual del procesador.
     */
    private array $config;

    /**
     * Clase CSS única para esta instancia (scoped CSS).
     */
    private string $instanceClass;

    /**
     * Template HTML del PostItem (se clona por cada post).
     */
    private string $template;

    /**
     * Atributos del editor GBN a preservar en el contenedor.
     */
    private array $editorAttrs;

    /**
     * Instancia del generador de estilos.
     */
    private PostRenderStyles $styles;

    /**
     * Instancia del generador de UI.
     */
    private PostRenderUI $ui;

    /**
     * Instancia del renderizador de items.
     */
    private PostItemRenderer $itemRenderer;

    /**
     * Constructor.
     * 
     * @param array $config Configuración del PostRender
     * @param string $template HTML del PostItem template
     * @param array $editorAttrs Atributos del editor GBN a preservar
     */
    public function __construct(array $config, string $template, array $editorAttrs = [])
    {
        $this->config = $config;
        $this->template = $template;
        $this->editorAttrs = $editorAttrs;
        $this->instanceClass = 'gbn-pr-' . substr(md5(uniqid('', true)), 0, 8);
        
        // Inicializar módulos auxiliares
        $this->styles = new PostRenderStyles($config, $this->instanceClass);
        $this->ui = new PostRenderUI($config, $this->instanceClass);
        $this->itemRenderer = new PostItemRenderer($template, $config);
    }

    /**
     * Detecta si estamos en modo editor GBN.
     * 
     * En modo editor, NO procesamos el PostRender para que el usuario pueda
     * editar el template original. El JS (post-render.js) manejará el preview.
     * 
     * Condiciones para modo editor:
     * - Usuario puede editar posts (current_user_can('edit_posts'))
     * - NO es una petición AJAX (las peticiones AJAX de preview son manejadas por el handler)
     * - NO es una petición REST API
     * 
     * @return bool True si estamos en modo editor
     */
    private static function isEditorMode(): bool
    {
        // Si no puede editar, no está en modo editor
        if (!current_user_can('edit_posts')) {
            return false;
        }
        
        // Las peticiones AJAX de preview deben procesarse normalmente
        if (wp_doing_ajax()) {
            return false;
        }
        
        // Las peticiones REST API no son modo editor
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return false;
        }
        
        // Si llegamos aquí, es un usuario editor viendo la página normalmente
        return true;
    }

    /**
     * Renderiza el componente PostRender completo.
     * 
     * @return string HTML renderizado
     */
    public function render(): string
    {
        // 1. Ejecutar query
        $query = PostRenderService::query($this->config);
        
        if (!$query->have_posts()) {
            return $this->ui->renderEmpty();
        }

        // 2. Generar CSS scoped
        $css = $this->styles->generateScopedCss();

        // 3. Renderizar items
        $items = '';
        while ($query->have_posts()) {
            $query->the_post();
            $items .= $this->itemRenderer->render(get_post());
        }
        wp_reset_postdata();

        // 4. Generar filtro de categorías si está habilitado
        $categoryFilter = $this->ui->isCategoryFilterEnabled()
            ? $this->ui->renderCategoryFilter($query->posts) 
            : '';

        // 5. Construir contenedor
        $containerStyles = $this->styles->getContainerStyles();
        $containerAttrs = $this->styles->getContainerAttributes();
        
        // Agregar atributos del editor si existen
        $editorAttrsStr = '';
        foreach ($this->editorAttrs as $attr => $value) {
            $editorAttrsStr .= sprintf(' %s="%s"', esc_attr($attr), esc_attr($value));
        }

        $html = '';
        
        // CSS inline
        if (!empty($css)) {
            $html .= '<style>' . $css . '</style>';
        }

        // Filtro de categorías
        $html .= $categoryFilter;

        // Contenedor con atributos del editor preservados
        $html .= sprintf(
            '<div class="%s" %s%s style="%s">%s</div>',
            esc_attr($this->instanceClass . ' gbn-post-render'),
            $containerAttrs,
            $editorAttrsStr,
            esc_attr($containerStyles),
            $items
        );

        // Paginación
        if ($this->ui->isPaginationEnabled()) {
            $html .= $this->ui->renderPagination($query);
        }

        return $html;
    }

    /**
     * Obtiene la clase de instancia generada.
     * 
     * @return string Clase CSS única
     */
    public function getInstanceClass(): string
    {
        return $this->instanceClass;
    }

    /**
     * Procesa el contenido del frontend buscando componentes PostRender.
     * Usa DOMDocument para manejar HTML anidado correctamente.
     * 
     * IMPORTANTE: En modo editor, NO procesamos el contenido para que el
     * usuario pueda editar el template original. El procesamiento dinámico
     * solo ocurre en el frontend para usuarios que no están editando.
     * 
     * @param string $content Contenido de la página/post
     * @return string Contenido procesado
     */
    public static function processContent(string $content): string
    {
        // ========================================================================
        // MODO EDITOR: No procesar para que el usuario edite el template original
        // El editor GBN necesita ver el template (PostItem) sin los posts clonados.
        // post-render.js manejará el preview en el editor.
        // ========================================================================
        if (self::isEditorMode()) {
            return $content;
        }
        
        // Detección case-insensitive porque DOMDocument convierte a minúsculas
        if (stripos($content, 'gloryPostRender') === false) {
            return $content;
        }

        // Usar DOMDocument para parsing robusto de HTML anidado
        $doc = new DOMDocument();
        $doc->encoding = 'UTF-8';
        libxml_use_internal_errors(true);
        
        // Preservar encoding UTF-8 usando meta charset
        $htmlWithMeta = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body><div id="gbn-root-wrapper">' . $content . '</div></body></html>';
        $doc->loadHTML($htmlWithMeta, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new DOMXPath($doc);
        
        // Buscar elementos con atributo glorypostrender (minúsculas - DOMDocument convierte)
        $postRenderNodes = $xpath->query('//*[@glorypostrender or @gloryPostRender]');
        
        if ($postRenderNodes->length === 0) {
            return $content;
        }
        
        foreach ($postRenderNodes as $node) {
            self::processPostRenderNode($doc, $node);
        }
        
        // Extraer el contenido procesado del wrapper
        $wrapper = $doc->getElementById('gbn-root-wrapper');
        if ($wrapper) {
            $result = '';
            foreach ($wrapper->childNodes as $child) {
                $result .= $doc->saveHTML($child);
            }
            return $result;
        }
        
        return $content;
    }

    /**
     * Procesa un nodo PostRender individual.
     * 
     * @param DOMDocument $doc Documento DOM
     * @param \DOMElement $node Nodo PostRender a procesar
     */
    private static function processPostRenderNode(DOMDocument $doc, \DOMElement $node): void
    {
        // Parsear configuración desde el atributo opciones
        $config = self::parseNodeConfig($node);
        
        // Capturar atributos del editor GBN para preservarlos
        $editorAttrs = self::extractEditorAttributes($node);

        // Extraer template (manejar contenido ya procesado)
        $innerHtml = self::extractTemplate($doc, $node);
        
        // Crear el procesador y obtener el HTML renderizado
        $processor = new self($config, $innerHtml, $editorAttrs);
        $renderedHtml = $processor->render();
        
        // Reemplazar el nodo con el contenido renderizado
        self::replaceNodeWithHtml($doc, $node, $renderedHtml);
    }

    /**
     * Parsea la configuración desde los atributos del nodo.
     * 
     * @param \DOMElement $node Nodo a parsear
     * @return array Configuración parseada
     */
    private static function parseNodeConfig(\DOMElement $node): array
    {
        $config = [];
        
        if (!$node->hasAttribute('opciones')) {
            return $config;
        }
        
        $opcionesStr = $node->getAttribute('opciones');
        // Parsear formato key: 'value', key: value
        preg_match_all("/(\w+):\s*'([^']*)'|(\w+):\s*([^,\s]+)/", $opcionesStr, $opts);
        
        foreach ($opts[0] as $i => $match) {
            $key = !empty($opts[1][$i]) ? $opts[1][$i] : $opts[3][$i];
            $value = !empty($opts[2][$i]) ? $opts[2][$i] : $opts[4][$i];
            
            // Convertir booleanos y números
            if ($value === 'true') $value = true;
            if ($value === 'false') $value = false;
            if (is_numeric($value)) $value = $value + 0;
            
            $config[$key] = $value;
        }
        
        // Migrar configuración a nombres canónicos
        if (class_exists(\Glory\Gbn\Schema\FieldAliasMapper::class)) {
            $config = \Glory\Gbn\Schema\FieldAliasMapper::migrateConfig($config);
        }
        
        return $config;
    }

    /**
     * Extrae los atributos del editor GBN del nodo.
     * 
     * @param \DOMElement $node Nodo a procesar
     * @return array Atributos del editor
     */
    private static function extractEditorAttributes(\DOMElement $node): array
    {
        $editorAttrs = [];
        $attrsToPreserve = [
            'class', 'data-gbn-id', 'data-gbn-role', 'data-gbn-post-render', 
            'data-gbn-ready', 'data-gbn-schema', 'draggable', 'glorypostrender'
        ];
        
        foreach ($attrsToPreserve as $attrName) {
            if ($node->hasAttribute($attrName)) {
                $value = $node->getAttribute($attrName);
                // Para class, agregar gbn-node si no existe
                if ($attrName === 'class' && strpos($value, 'gbn-node') === false) {
                    $value = 'gbn-node gbn-block ' . $value;
                }
                $editorAttrs[$attrName] = $value;
            }
        }
        
        return $editorAttrs;
    }

    /**
     * Extrae el template HTML del nodo, manejando contenido ya procesado.
     * 
     * @param DOMDocument $doc Documento DOM
     * @param \DOMElement $node Nodo PostRender
     * @return string HTML del template
     */
    private static function extractTemplate(DOMDocument $doc, \DOMElement $node): string
    {
        $innerHtml = '';
        $postItemCount = 0;
        $firstPostItem = null;
        
        foreach ($node->childNodes as $child) {
            // Contar PostItems con data-post-id (indicador de contenido ya procesado)
            if ($child instanceof \DOMElement) {
                if ($child->hasAttribute('glorypostitem') || $child->hasAttribute('gloryPostItem')) {
                    $postItemCount++;
                    if ($postItemCount === 1) {
                        $firstPostItem = $doc->saveHTML($child);
                    }
                    // Si encontramos más de 1 PostItem con data-post-id, usar solo el primero
                    if ($postItemCount > 1 && $child->hasAttribute('data-post-id')) {
                        continue;
                    }
                }
            }
            $innerHtml .= $doc->saveHTML($child);
        }
        
        // Si detectamos múltiples PostItems procesados, usar solo el template (primer item)
        if ($postItemCount > 1 && $firstPostItem) {
            // Limpiar atributos de datos del post anterior del template
            $innerHtml = preg_replace('/\s*data-post-id="[^"]*"/', '', $firstPostItem);
            $innerHtml = preg_replace('/\s*data-categories="[^"]*"/', '', $innerHtml);
        }
        
        return $innerHtml;
    }

    /**
     * Reemplaza un nodo con el HTML renderizado.
     * 
     * @param DOMDocument $doc Documento DOM principal
     * @param \DOMElement $node Nodo a reemplazar
     * @param string $renderedHtml HTML renderizado
     */
    private static function replaceNodeWithHtml(DOMDocument $doc, \DOMElement $node, string $renderedHtml): void
    {
        $tempDoc = new DOMDocument();
        $tempDoc->encoding = 'UTF-8';
        libxml_use_internal_errors(true);
        $tempDoc->loadHTML('<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body><div id="temp-wrap">' . $renderedHtml . '</div></body></html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        
        $tempWrapper = $tempDoc->getElementById('temp-wrap');
        if (!$tempWrapper) {
            return;
        }
        
        // Crear un fragmento para insertar múltiples nodos
        $fragment = $doc->createDocumentFragment();
        
        foreach ($tempWrapper->childNodes as $child) {
            $importedNode = $doc->importNode($child, true);
            $fragment->appendChild($importedNode);
        }
        
        // Reemplazar el nodo original con el fragmento
        $node->parentNode->replaceChild($fragment, $node);
    }
}
