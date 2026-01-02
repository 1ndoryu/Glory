<?php

namespace Glory\Plugins\AmazonProduct\Service;

/**
 * Servicio para generar el indice de busqueda optimizado.
 * 
 * Genera un indice JSON ligero con todos los productos para
 * habilitar busqueda instantanea del lado del cliente.
 * 
 * El indice incluye solo datos minimos:
 * - id: ID del producto
 * - t: Titulo normalizado (sin acentos)
 * - p: Precio
 * - i: URL de imagen (thumbnail)
 * - u: URL del producto
 * - o: Titulo original
 */
class SearchIndexService
{
    private const CACHE_KEY = 'amazon_search_index_v2';
    private const CACHE_DURATION = HOUR_IN_SECONDS;

    /**
     * Obtiene el indice de busqueda.
     * Devuelve desde cache o genera uno nuevo.
     */
    public function getIndex(): array
    {
        $cached = get_transient(self::CACHE_KEY);

        if ($cached !== false && isset($cached['products'])) {
            return $cached;
        }

        return $this->buildIndex();
    }

    /**
     * Construye el indice de busqueda con datos minimos.
     * Optimizado para transferencia: claves cortas, datos compactos.
     */
    private function buildIndex(): array
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
        $affiliateTag = get_option('amazon_affiliate_tag', '');

        foreach ($query->posts as $postId) {
            $title = get_the_title($postId);
            $normalizedTitle = $this->normalizeText($title);

            $imageUrl = '';
            if (has_post_thumbnail($postId)) {
                $imageUrl = get_the_post_thumbnail_url($postId, 'thumbnail');
            }
            if (empty($imageUrl)) {
                $imageUrl = get_post_meta($postId, 'image_url', true);
            }

            $productUrl = get_post_meta($postId, 'product_url', true);
            if (!empty($affiliateTag) && !empty($productUrl)) {
                $separator = (strpos($productUrl, '?') !== false) ? '&' : '?';
                $productUrl .= $separator . 'tag=' . esc_attr($affiliateTag);
            }

            $products[] = [
                'id' => $postId,
                't' => $normalizedTitle,
                'o' => $title,
                'p' => get_post_meta($postId, 'price', true),
                'i' => $imageUrl,
                'u' => $productUrl,
            ];
        }

        $index = [
            'products' => $products,
            'timestamp' => time(),
            'count' => count($products),
        ];

        set_transient(self::CACHE_KEY, $index, self::CACHE_DURATION);

        return $index;
    }

    /**
     * Obtiene solo el timestamp del indice actual.
     * Util para verificar si el cliente necesita actualizar.
     */
    public function getTimestamp(): int
    {
        $cached = get_transient(self::CACHE_KEY);

        if ($cached !== false && isset($cached['timestamp'])) {
            return $cached['timestamp'];
        }

        return 0;
    }

    /**
     * Invalida la cache del indice.
     * Llamar cuando se modifican productos.
     */
    public static function invalidateCache(): void
    {
        delete_transient(self::CACHE_KEY);

        /* Invalidar tambien la cache del FuzzySearchService */
        FuzzySearchService::invalidateCache();
    }

    /**
     * Normaliza texto removiendo acentos y convirtiendo a minusculas.
     */
    private function normalizeText(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');

        $replacements = [
            'á' => 'a',
            'à' => 'a',
            'ä' => 'a',
            'â' => 'a',
            'ã' => 'a',
            'é' => 'e',
            'è' => 'e',
            'ë' => 'e',
            'ê' => 'e',
            'í' => 'i',
            'ì' => 'i',
            'ï' => 'i',
            'î' => 'i',
            'ó' => 'o',
            'ò' => 'o',
            'ö' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ú' => 'u',
            'ù' => 'u',
            'ü' => 'u',
            'û' => 'u',
            'ñ' => 'n',
            'ç' => 'c',
        ];

        return strtr($text, $replacements);
    }
}
