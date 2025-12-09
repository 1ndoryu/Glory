<?php

/**
 * PostFieldProcessor - Procesador de campos semánticos [gloryPostField]
 * 
 * Maneja la lógica de extracción y renderizado de campos de posts
 * (título, imagen destacada, extracto, etc.) desde elementos semánticos.
 * 
 * Parte del REFACTOR-003: División de PostRenderProcessor.php
 * 
 * @package Glory\Gbn\Components\PostRender
 */

namespace Glory\Gbn\Components\PostRender;

use WP_Post;
use DOMDocument;
use DOMXPath;

class PostFieldProcessor
{
    /**
     * Procesa todos los campos [gloryPostField] en el template.
     * Usa DOMDocument para manejar HTML anidado correctamente.
     * 
     * @param string $html HTML del template
     * @param WP_Post $post Post actual
     * @return string HTML con campos rellenados
     */
    public static function process(string $html, WP_Post $post): string
    {
        // Envolver en un contenedor para que DOMDocument no agregue html/body
        $wrappedHtml = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body><div id="gbn-temp-wrapper">' . $html . '</div></body></html>';

        $doc = new DOMDocument();
        $doc->encoding = 'UTF-8';
        // Suprimir warnings por HTML5 tags y preservar encoding
        libxml_use_internal_errors(true);
        $doc->loadHTML($wrappedHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new DOMXPath($doc);

        // Buscar todos los elementos con atributo gloryPostField o glorypostfield (DOMDocument convierte a minúsculas)
        $nodes = $xpath->query('//*[@glorypostfield or @gloryPostField]');

        foreach ($nodes as $node) {
            self::processFieldNode($doc, $node, $post);
        }

        // Extraer el HTML del wrapper
        $wrapper = $doc->getElementById('gbn-temp-wrapper');
        if ($wrapper) {
            $result = '';
            foreach ($wrapper->childNodes as $child) {
                $result .= $doc->saveHTML($child);
            }
            return $result;
        }

        return $html;
    }

    /**
     * Procesa un nodo individual de campo semántico.
     * 
     * @param DOMDocument $doc Documento DOM
     * @param \DOMElement $node Nodo a procesar
     * @param WP_Post $post Post actual
     */
    private static function processFieldNode(DOMDocument $doc, \DOMElement $node, WP_Post $post): void
    {
        // Obtener el tipo de campo (probar ambos casos)
        $fieldType = $node->hasAttribute('glorypostfield')
            ? $node->getAttribute('glorypostfield')
            : $node->getAttribute('gloryPostField');

        // Parsear opciones si existen
        $config = [];
        if ($node->hasAttribute('opciones')) {
            $config = self::parseFieldConfig('opciones="' . $node->getAttribute('opciones') . '"');
        }
        $config['fieldType'] = $fieldType;

        // Caso especial: imagen destacada
        if ($fieldType === 'featuredImage') {
            self::processFeaturedImage($doc, $node, $post, $config);
            return;
        }

        // Caso especial: título con enlace
        if ($fieldType === 'title') {
            self::processTitle($node, $post);
            return;
        }

        // Caso especial: enlace directo
        if ($fieldType === 'link') {
            $node->setAttribute('href', get_permalink($post));
            return;
        }

        // Renderizar otros campos
        $content = PostFieldComponent::renderField($post, $config);

        if (!empty($content)) {
            // Para campos con HTML (content, authorAvatar, etc), necesitamos insertar como nodos DOM
            // textContent escapa el HTML, así que usamos un enfoque diferente
            $htmlFields = ['content', 'authorAvatar', 'categories', 'tags', 'link'];

            if (in_array($fieldType, $htmlFields, true)) {
                // Limpiar contenido existente
                while ($node->firstChild) {
                    $node->removeChild($node->firstChild);
                }

                // Parsear el HTML del contenido y agregar como nodos
                $tempDoc = new DOMDocument();
                $tempDoc->encoding = 'UTF-8';
                libxml_use_internal_errors(true);
                $tempDoc->loadHTML('<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body><div id="gbn-content-wrap">' . $content . '</div></body></html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                libxml_clear_errors();

                $tempWrapper = $tempDoc->getElementById('gbn-content-wrap');
                if ($tempWrapper) {
                    foreach ($tempWrapper->childNodes as $child) {
                        $importedNode = $doc->importNode($child, true);
                        $node->appendChild($importedNode);
                    }
                }
            } else {
                // Para campos simples (excerpt, date, etc), usar textContent (escapa correctamente)
                $node->textContent = $content;
            }
        }
    }

    /**
     * Procesa el campo de imagen destacada.
     * 
     * @param DOMDocument $doc Documento DOM
     * @param \DOMElement $node Nodo del campo
     * @param WP_Post $post Post actual
     * @param array $config Configuración del campo
     */
    private static function processFeaturedImage(DOMDocument $doc, \DOMElement $node, WP_Post $post, array $config): void
    {
        if (!has_post_thumbnail($post)) {
            return;
        }

        $size = $config['imageSize'] ?? 'large';
        $imgUrl = get_the_post_thumbnail_url($post, $size);

        // Si asBackground es true, aplicar como background-image en lugar de crear img
        $asBackground = isset($config['asBackground']) && ($config['asBackground'] === 'true' || $config['asBackground'] === true);

        if ($asBackground) {
            // Agregar background-image al estilo existente
            $existingStyle = $node->hasAttribute('style') ? $node->getAttribute('style') : '';
            $bgStyle = "background-image: url('" . esc_url($imgUrl) . "'); background-size: cover; background-position: center;";
            $node->setAttribute('style', $existingStyle . ' ' . $bgStyle);
            return;
        }

        // Buscar la etiqueta img dentro y actualizar su src
        $imgNodes = $node->getElementsByTagName('img');
        if ($imgNodes->length > 0) {
            $imgNodes->item(0)->setAttribute('src', $imgUrl);
            $imgNodes->item(0)->setAttribute('alt', esc_attr(get_the_title($post)));
        } else {
            // Si no hay img, crear una
            $img = $doc->createElement('img');
            $img->setAttribute('src', $imgUrl);
            $img->setAttribute('alt', esc_attr(get_the_title($post)));
            $img->setAttribute('style', 'width: 100%; height: 100%; object-fit: cover;');
            $img->setAttribute('loading', 'lazy');
            // Limpiar contenido existente y agregar imagen
            while ($node->firstChild) {
                $node->removeChild($node->firstChild);
            }
            $node->appendChild($img);
        }
    }

    /**
     * Procesa el campo de título con soporte para enlaces.
     * 
     * @param \DOMElement $node Nodo del campo
     * @param WP_Post $post Post actual
     */
    private static function processTitle(\DOMElement $node, WP_Post $post): void
    {
        $title = get_the_title($post);
        $permalink = get_permalink($post);

        // Buscar si hay un <a> dentro
        $linkNodes = $node->getElementsByTagName('a');
        if ($linkNodes->length > 0) {
            $linkNodes->item(0)->setAttribute('href', $permalink);
            // Reemplazar el texto del enlace
            $linkNodes->item(0)->textContent = $title;
        } else {
            // Si no hay enlace, solo poner el título
            $node->textContent = $title;
        }
    }

    /**
     * Parsea la configuración del campo desde los atributos opciones="...".
     * 
     * @param string $attrs String de atributos HTML
     * @return array Configuración parseada
     */
    public static function parseFieldConfig(string $attrs): array
    {
        $config = [];

        // Buscar atributo opciones
        if (preg_match('/opciones="([^"]+)"/', $attrs, $matches)) {
            // Parsear formato key: value, key: value
            preg_match_all("/(\w+):\s*'([^']*)'|(\w+):\s*([^,\s]+)/", $matches[1], $opts);

            foreach ($opts[0] as $i => $match) {
                $key = !empty($opts[1][$i]) ? $opts[1][$i] : $opts[3][$i];
                $value = !empty($opts[2][$i]) ? $opts[2][$i] : $opts[4][$i];
                $config[$key] = $value;
            }
        }

        return $config;
    }

    /**
     * Renderiza el contenedor de imagen destacada (método legacy para compatibilidad).
     * 
     * @param WP_Post $post Post actual
     * @param string $attrsBefore Atributos antes de gloryPostField
     * @param string $attrsAfter Atributos después de gloryPostField
     * @param array $config Configuración del campo
     * @return string HTML del contenedor de imagen
     */
    public static function renderFeaturedImageContainer(WP_Post $post, string $attrsBefore, string $attrsAfter, array $config): string
    {
        $size = $config['imageSize'] ?? 'medium';
        $imageHtml = get_the_post_thumbnail($post, $size, [
            'loading' => 'lazy',
            'style' => 'width: 100%; height: 100%; object-fit: cover;',
        ]);

        return sprintf(
            '<div%s gloryPostField="featuredImage"%s>%s</div>',
            $attrsBefore,
            $attrsAfter,
            $imageHtml
        );
    }
}
