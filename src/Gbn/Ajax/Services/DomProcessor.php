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
        // Find all elements that might be blocks.
        // Note: loadHTML lowercases attributes, so we must query for lowercase versions.
        $query = "//*[@glorydiv] | //*[@glorydivsecundario] | //*[@glorycontentrender] | //*[@glorytermrender] | //*[@gloryimage] | //*[@data-gbnprincipal] | //*[@data-gbnsecundario] | //*[@data-gbncontent] | //*[@data-gbn-term-list] | //*[@data-gbn-image]";

        $nodes = $xpath->query($query);
        $toRemove = [];
        $nodesByParent = [];
        
        Logger::log('Nodes found: ' . $nodes->length);

        foreach ($nodes as $node) {
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
                // Store for reordering
                $parentId = spl_object_hash($node->parentNode);
                if (!isset($nodesByParent[$parentId])) {
                    $nodesByParent[$parentId] = ['parent' => $node->parentNode, 'children' => []];
                }
                $nodesByParent[$parentId]['children'][] = [
                    'node' => $node,
                    'order' => isset($configById[$id]['order']) ? (int) $configById[$id]['order'] : 9999
                ];
            }
        }
        
        // Reorder nodes
        foreach ($nodesByParent as $parentId => $group) {
            $parent = $group['parent'];
            $children = $group['children'];
            
            // Log before sort
            $orders = array_map(function($c) { return $c['order']; }, $children);
            Logger::log("Parent $parentId - Orders before: " . implode(', ', $orders));

            // Sort by order
            usort($children, function($a, $b) {
                return $a['order'] - $b['order'];
            });
            
            // Log after sort
            $orders = array_map(function($c) { return $c['order']; }, $children);
            Logger::log("Parent $parentId - Orders after: " . implode(', ', $orders));

            // Re-append in correct order
            foreach ($children as $child) {
                $parent->appendChild($child['node']);
            }
        }
        
        Logger::log('Nodes to remove: ' . count($toRemove));

        foreach ($toRemove as $node) {
            $node->parentNode->removeChild($node);
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
        $curr = $node;
        while ($curr && $curr->nodeType === XML_ELEMENT_NODE && $curr->nodeName !== 'body') {
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
            $sibling = $curr;
            while ($sibling = $sibling->previousSibling) {
                if ($sibling->nodeType === XML_ELEMENT_NODE && $sibling->nodeName === $curr->nodeName) {
                    $index++;
                }
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
            $hash = -((~$hash & 0xFFFFFFFF) + 1);
        }
        
        return abs($hash);
    }

    public static function hashContenidoLocal(string $content): string
    {
        $normalized = preg_replace('/\s+/', ' ', trim($content));
        return hash('sha256', (string) $normalized);
    }
}
