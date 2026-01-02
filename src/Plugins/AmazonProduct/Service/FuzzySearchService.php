<?php

namespace Glory\Plugins\AmazonProduct\Service;

/**
 * Servicio de busqueda fuzzy para productos Amazon.
 * 
 * Implementa busqueda tolerante a errores tipograficos usando:
 * - Distancia de Levenshtein para similitud de palabras
 * - Busqueda por prefijo para autocompletado
 * - Normalizacion de texto (acentos, mayusculas)
 */
class FuzzySearchService
{
    private const MAX_DISTANCE = 2;
    private const MIN_WORD_LENGTH = 3;

    /**
     * Busca productos por termino con tolerancia a errores.
     * 
     * @param string $searchTerm Termino de busqueda
     * @param int $limit Limite de resultados
     * @return array Productos encontrados con score de relevancia
     */
    public function search(string $searchTerm, int $limit = 5): array
    {
        $searchTerm = $this->normalizeText($searchTerm);
        
        if (strlen($searchTerm) < 2) {
            return ['products' => [], 'count' => 0];
        }

        $allProducts = $this->getAllProductsFromCache();
        $matches = $this->findMatches($allProducts, $searchTerm);
        
        usort($matches, fn($a, $b) => $b['score'] - $a['score']);
        
        $topMatches = array_slice($matches, 0, $limit);
        
        return [
            'products' => array_map(fn($m) => $m['product'], $topMatches),
            'count' => count($matches)
        ];
    }

    /**
     * Obtiene todos los productos desde cache o base de datos.
     * Usa transient para mejorar rendimiento.
     */
    private function getAllProductsFromCache(): array
    {
        $cacheKey = 'amazon_products_search_index';
        $cached = get_transient($cacheKey);

        if ($cached !== false) {
            return $cached;
        }

        $products = $this->buildSearchIndex();
        
        set_transient($cacheKey, $products, 5 * MINUTE_IN_SECONDS);

        return $products;
    }

    /**
     * Construye el indice de busqueda con datos minimos.
     */
    private function buildSearchIndex(): array
    {
        $query = new \WP_Query([
            'post_type' => 'amazon_product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'no_found_rows' => true,
            'update_post_term_cache' => false,
            'fields' => 'ids',
        ]);

        $products = [];

        foreach ($query->posts as $postId) {
            $title = get_the_title($postId);
            $normalizedTitle = $this->normalizeText($title);
            $words = $this->extractWords($normalizedTitle);

            $imageUrl = '';
            if (has_post_thumbnail($postId)) {
                $imageUrl = get_the_post_thumbnail_url($postId, 'thumbnail');
            }
            if (empty($imageUrl)) {
                $imageUrl = get_post_meta($postId, 'image_url', true);
            }

            $productUrl = get_post_meta($postId, 'product_url', true);
            $affiliateTag = get_option('amazon_affiliate_tag', '');
            if (!empty($affiliateTag) && !empty($productUrl)) {
                $separator = (strpos($productUrl, '?') !== false) ? '&' : '?';
                $productUrl .= $separator . 'tag=' . esc_attr($affiliateTag);
            }

            $products[] = [
                'id' => $postId,
                'title' => $title,
                'normalizedTitle' => $normalizedTitle,
                'words' => $words,
                'price' => get_post_meta($postId, 'price', true),
                'image' => $imageUrl,
                'url' => $productUrl,
            ];
        }

        return $products;
    }

    /**
     * Busca coincidencias fuzzy en la lista de productos.
     */
    private function findMatches(array $products, string $searchTerm): array
    {
        $matches = [];
        $searchWords = $this->extractWords($searchTerm);

        foreach ($products as $product) {
            $score = $this->calculateMatchScore($product, $searchTerm, $searchWords);

            if ($score > 0) {
                $matches[] = [
                    'product' => [
                        'title' => $product['title'],
                        'price' => $product['price'],
                        'image' => $product['image'],
                        'url' => $product['url'],
                    ],
                    'score' => $score
                ];
            }
        }

        return $matches;
    }

    /**
     * Calcula el score de coincidencia para un producto.
     */
    private function calculateMatchScore(array $product, string $searchTerm, array $searchWords): int
    {
        $score = 0;
        $normalizedTitle = $product['normalizedTitle'];
        $productWords = $product['words'];

        /* 
         * Coincidencia exacta del termino completo (mayor peso) 
         */
        if (strpos($normalizedTitle, $searchTerm) !== false) {
            $score += 100;
        }

        /* 
         * Coincidencia por prefijo (para autocompletado) 
         */
        if (strpos($normalizedTitle, $searchTerm) === 0) {
            $score += 50;
        }

        /* 
         * Busqueda fuzzy palabra por palabra 
         */
        foreach ($searchWords as $searchWord) {
            if (strlen($searchWord) < self::MIN_WORD_LENGTH) {
                /* Para palabras cortas, solo coincidencia exacta o prefijo */
                foreach ($productWords as $productWord) {
                    if ($productWord === $searchWord) {
                        $score += 30;
                    } elseif (strpos($productWord, $searchWord) === 0) {
                        $score += 20;
                    }
                }
                continue;
            }

            foreach ($productWords as $productWord) {
                /* Coincidencia exacta de palabra */
                if ($productWord === $searchWord) {
                    $score += 30;
                    continue;
                }

                /* Coincidencia por prefijo */
                if (strpos($productWord, $searchWord) === 0) {
                    $score += 25;
                    continue;
                }

                /* Busqueda fuzzy con Levenshtein */
                if (strlen($productWord) >= self::MIN_WORD_LENGTH) {
                    $distance = levenshtein($searchWord, $productWord);
                    $maxLen = max(strlen($searchWord), strlen($productWord));
                    
                    /* Ajustar distancia permitida segun longitud */
                    $allowedDistance = min(self::MAX_DISTANCE, floor($maxLen / 3));
                    
                    if ($distance <= $allowedDistance) {
                        /* Score inversamente proporcional a la distancia */
                        $score += (self::MAX_DISTANCE - $distance + 1) * 10;
                    }
                }
            }
        }

        return $score;
    }

    /**
     * Normaliza texto removiendo acentos y convirtiendo a minusculas.
     */
    private function normalizeText(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        
        $replacements = [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'ã' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n', 'ç' => 'c',
        ];

        return strtr($text, $replacements);
    }

    /**
     * Extrae palabras de un texto normalizado.
     */
    private function extractWords(string $text): array
    {
        $words = preg_split('/[\s\-_.,;:!?()\/\[\]]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        return array_filter($words, fn($w) => strlen($w) >= 2);
    }

    /**
     * Invalida la cache de busqueda.
     * Llamar cuando se agregan/modifican/eliminan productos.
     */
    public static function invalidateCache(): void
    {
        delete_transient('amazon_products_search_index');
    }
}
