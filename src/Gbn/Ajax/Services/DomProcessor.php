<?php

namespace Glory\Gbn\Ajax\Services;

use Glory\Gbn\Logger;

class DomProcessor
{
    public static function processHtmlForPersistence(string $html, array $configById): string
    {
        if (trim($html) === '') {
            return '';
        }

        $dom = new \DOMDocument();
        // Suppress warnings for malformed HTML
        libxml_use_internal_errors(true);
        // Hack for UTF-8. Wrap in data-gbn-root to ensure consistent ID generation.
        $dom->loadHTML('<?xml encoding="utf-8" ?><body><div data-gbn-root>' . $html . '</div></body>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        // Logger::log('HTML Start: ' . substr($html, 0, 100));

        $xpath = new \DOMXPath($dom);
        // Find all elements that might be GBN blocks.
        // Note: loadHTML lowercases attributes, so we must query for lowercase versions.
        // Lista completa de componentes GBN (atributos glory* y data-gbn-*)
        $queryParts = [
            // Principal/Secundario
            '//*[@glorydiv]',
            '//*[@glorydivsecundario]',
            '//*[@data-gbnprincipal]',
            '//*[@data-gbnsecundario]',
            // Text
            '//*[@glorytexto]',
            '//*[@data-gbn-text]',
            // Button
            '//*[@glorybutton]',
            '//*[@data-gbn-button]',
            // Image (ambas variantes)
            '//*[@gloryimage]',
            '//*[@gloryimagen]',
            '//*[@data-gbn-image]',
            // PostRender/PostItem/PostField
            '//*[@glorypostrender]',
            '//*[@glorypostitem]',
            '//*[@glorypostfield]',
            '//*[@data-gbn-post-render]',
            '//*[@data-gbn-post-item]',
            '//*[@data-gbn-post-field]',
            // Tarjeta
            '//*[@glorytarjeta]',
            '//*[@data-gbn-tarjeta]',
            // Form components
            '//*[@gloryform]',
            '//*[@gloryinput]',
            '//*[@gloryselect]',
            '//*[@glorytextarea]',
            '//*[@glorysubmit]',
            '//*[@data-gbn-form]',
            '//*[@data-gbn-input]',
            '//*[@data-gbn-select]',
            '//*[@data-gbn-textarea]',
            '//*[@data-gbn-submit]',
            // Menu components
            '//*[@glorymenu]',
            '//*[@glorymenuitem]',
            '//*[@data-gbn-menu]',
            '//*[@data-gbn-menu-item]',
            // Header/Footer/Logo
            '//*[@gloryheader]',
            '//*[@gloryfooter]',
            '//*[@glorylogo]',
            '//*[@data-gbn-header]',
            '//*[@data-gbn-footer]',
            '//*[@data-gbn-logo]',
            // ContentRender/TermRender (legacy)
            '//*[@glorycontentrender]',
            '//*[@glorytermrender]',
            '//*[@data-gbncontent]',
            '//*[@data-gbn-term-list]',
            // Cualquier elemento con data-gbn-id (catch-all para bloques registrados)
            '//*[@data-gbn-id]'
        ];
        $query = implode(' | ', $queryParts);

        $nodes = $xpath->query($query);
        $toRemove = [];

        Logger::log('Nodes found: ' . $nodes->length);

        foreach ($nodes as $node) {
            // Verificar que es un DOMElement (para satisfacer Intelephense y type safety)
            if (!$node instanceof \DOMElement) {
                continue;
            }

            // Generate ID
            $id = $node->getAttribute('data-gbn-id');
            if (!$id) {
                $path = self::computeDomPath($node);
                $id = self::generateId($node);
                $node->setAttribute('data-gbn-id', $id);
                Logger::log("Generated ID: $id | Path: $path");
            }

            // Check if valid
            if (!isset($configById[$id])) {
                $toRemove[] = $node;
                Logger::log("Marked for removal: $id");
            } else {
                // Inject default classes for persistence
                // This ensures styles work even if GBN JS is disabled
                if ($node->hasAttribute('glorydiv') || $node->hasAttribute('data-gbnprincipal')) {
                    $classes = $node->getAttribute('class');
                    if (strpos($classes, 'primario') === false) {
                        $node->setAttribute('class', trim($classes . ' primario'));
                    }
                }
                if ($node->hasAttribute('glorydivsecundario') || $node->hasAttribute('data-gbnsecundario')) {
                    $classes = $node->getAttribute('class');
                    if (strpos($classes, 'secundario') === false) {
                        $node->setAttribute('class', trim($classes . ' secundario'));
                    }
                }
                if ($node->hasAttribute('gloryimage') || $node->hasAttribute('data-gbn-image')) {
                    $classes = $node->getAttribute('class');
                    if (strpos($classes, 'gbn-image') === false) {
                        $node->setAttribute('class', trim($classes . ' gbn-image'));
                    }
                }

                // Clean heavy internal attributes that shouldn't be in the DB
                // These are reconstructed from post_meta or defaults
                $node->removeAttribute('data-gbn-schema');
                $node->removeAttribute('data-gbn-config');

                // Cleanup editor-only attributes
                $node->removeAttribute('draggable');
                $node->removeAttribute('data-gbn-ready');

                // Cleanup editor-only classes (gbn-block, gbn-node, gbn-block-active)
                // Estas clases son inyectadas por el inspector y no deben persistirse
                $classes = $node->getAttribute('class');
                if ($classes !== '') {
                    $editorClasses = ['gbn-block', 'gbn-node', 'gbn-block-active', 'gbn-show-controls'];
                    $classArray = preg_split('/\s+/', $classes);
                    $cleanedClasses = array_filter($classArray, function ($c) use ($editorClasses) {
                        return !in_array($c, $editorClasses);
                    });
                    $newClasses = implode(' ', $cleanedClasses);
                    if ($newClasses !== $classes) {
                        if ($newClasses === '') {
                            $node->removeAttribute('class');
                        } else {
                            $node->setAttribute('class', $newClasses);
                        }
                    }
                }
            }
        }

        // BUG-018 FIX: NO reordenar nodos DOM aqui.
        // El HTML viene del cliente via innerHTML en el orden visual correcto (WYSIWYG).
        // El reordenamiento anterior usaba appendChild solo en nodos GBN, lo cual
        // los movia al final del padre y rompia la estructura con elementos no-GBN.
        // El orden se preserva naturalmente desde el HTML serializado del cliente.

        Logger::log('Nodes to remove: ' . count($toRemove));

        foreach ($toRemove as $node) {
            // Verificar que el nodo tiene padre antes de intentar removerlo
            if ($node->parentNode !== null) {
                $node->parentNode->removeChild($node);
            }
        }

        // Return HTML of body content
        $body = $dom->getElementsByTagName('body')->item(0);
        $output = '';
        foreach ($body->childNodes as $child) {
            $output .= $dom->saveHTML($child);
        }

        return $output;
    }

    public static function generateId(\DOMElement $node): string
    {
        $path = self::computeDomPath($node);
        $hash = self::hashString($path);
        return 'gbn-v3-' . base_convert((string)$hash, 10, 36);
    }

    private static function computeDomPath(\DOMElement $node): string
    {
        $segments = [];
        /** @var \DOMNode|null $curr */
        $curr = $node;
        while ($curr !== null && $curr->nodeType === XML_ELEMENT_NODE && $curr->nodeName !== 'body') {
            // Verificar que es DOMElement para acceder a hasAttribute
            if (!$curr instanceof \DOMElement) {
                break;
            }

            // Stop at data-gbn-root
            if ($curr->hasAttribute('data-gbn-root')) {
                break;
            }
            $tag = strtolower($curr->nodeName);

            // Ignore 'main' tag to fix inconsistency between client (with main) and server (without main)
            if ($tag === 'main') {
                $curr = $curr->parentNode;
                continue;
            }

            $index = 0;
            $sibling = $curr->previousSibling;
            while ($sibling !== null) {
                if ($sibling->nodeType === XML_ELEMENT_NODE && $sibling->nodeName === $curr->nodeName) {
                    $index++;
                }
                $sibling = $sibling->previousSibling;
            }
            array_unshift($segments, $tag . ':' . $index);
            $curr = $curr->parentNode;
        }
        return implode('>', $segments);
    }

    private static function hashString(string $str): int
    {
        $hash = 0;
        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $char = ord($str[$i]);
            $hash = (($hash << 5) - $hash) + $char;
            $hash = $hash & 0xFFFFFFFF; // Keep 32 bits (unsigned)
        }

        // Convert unsigned 32-bit to signed 32-bit to match JS bitwise operations
        if ($hash & 0x80000000) {
            $hash = - ((~$hash & 0xFFFFFFFF) + 1);
        }

        return abs($hash);
    }

    public static function hashContenidoLocal(string $content): string
    {
        $normalized = preg_replace('/\s+/', ' ', trim($content));
        return hash('sha256', (string) $normalized);
    }
}
