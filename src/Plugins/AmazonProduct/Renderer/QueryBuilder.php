<?php

namespace Glory\Plugins\AmazonProduct\Renderer;

/**
 * Query Builder - Construye WP_Query para productos Amazon.
 * 
 * Responsabilidad unica: Construccion de queries con todos los filtros soportados.
 * Soporta: ids, search, category, price, rating, prime, deals, ordenamiento.
 */
class QueryBuilder
{
    /**
     * Construye la query con los parametros proporcionados.
     * 
     * @param array $params Parametros de filtrado
     * @return \WP_Query Query configurada
     */
    public function build(array $params): \WP_Query
    {
        $args = $this->getBaseArgs($params);
        $args = $this->applyIdFilter($args, $params);
        $args = $this->applyExcludedIdsFilter($args, $params);
        $args = $this->applySearchFilter($args, $params);
        $args = $this->applyExcludeFilter($args, $params);
        $args = $this->applyCategoryFilter($args, $params);
        $args = $this->applyPriceFilters($args, $params);
        $args = $this->applyRatingFilter($args, $params);
        $args = $this->applyPrimeFilter($args, $params);
        $args = $this->applyDealsFilter($args, $params);
        $args = $this->applySorting($args, $params);

        return new \WP_Query($args);
    }

    /**
     * Filtro por IDs excluidos (de secciones dinamicas).
     * Excluye productos que han sido manualmente quitados de una seccion.
     */
    private function applyExcludedIdsFilter(array $args, array $params): array
    {
        if (empty($params['_excluded_ids'])) {
            return $args;
        }

        $excludedIds = array_map('intval', (array) $params['_excluded_ids']);
        $excludedIds = array_filter($excludedIds);

        if (!empty($excludedIds)) {
            $existing = $args['post__not_in'] ?? [];
            $args['post__not_in'] = array_unique(array_merge($existing, $excludedIds));
        }

        return $args;
    }

    /**
     * Argumentos base de la query.
     */
    private function getBaseArgs(array $params): array
    {
        return [
            'post_type' => 'amazon_product',
            'posts_per_page' => $params['limit'] ?? 12,
            'paged' => $params['paged'] ?? 1,
            'meta_query' => [],
        ];
    }

    /**
     * Filtro por IDs especificos de WordPress.
     * Tiene prioridad sobre otros filtros de busqueda.
     */
    private function applyIdFilter(array $args, array $params): array
    {
        if (empty($params['ids'])) {
            return $args;
        }

        $ids = array_map('intval', explode(',', $params['ids']));
        $ids = array_filter($ids);

        if (!empty($ids)) {
            $args['post__in'] = $ids;
            $args['orderby'] = 'post__in'; // Mantener orden de IDs proporcionados
        }

        return $args;
    }

    /**
     * Filtro de busqueda por texto en titulo.
     */
    private function applySearchFilter(array $args, array $params): array
    {
        if (!empty($params['search'])) {
            $args['s'] = $params['search'];
        }

        return $args;
    }

    /**
     * Filtro de exclusion por palabras en el titulo.
     * Excluye productos cuyo titulo contenga cualquiera de las palabras especificadas.
     * 
     * Uso: exclude="paletero,bolsa,funda" excluira productos que contengan esas palabras.
     */
    private function applyExcludeFilter(array $args, array $params): array
    {
        if (empty($params['exclude'])) {
            return $args;
        }

        // Separar palabras a excluir
        $excludeWords = array_map('trim', explode(',', $params['exclude']));
        $excludeWords = array_filter($excludeWords);

        if (empty($excludeWords)) {
            return $args;
        }

        // Guardar palabras de exclusión para filtrar después de la query
        // WordPress no tiene forma nativa de excluir por palabras en título
        $args['exclude_words'] = $excludeWords;

        return $args;
    }

    /**
     * Filtra los resultados de una query excluyendo posts por palabras en título.
     * Se debe llamar después de obtener los resultados de WP_Query.
     * 
     * @param array $posts Array de posts
     * @param array $excludeWords Palabras a excluir
     * @return array Posts filtrados
     */
    public static function filterExcludedPosts(array $posts, array $excludeWords): array
    {
        if (empty($excludeWords)) {
            return $posts;
        }

        return array_filter($posts, function ($post) use ($excludeWords) {
            $title = strtolower($post->post_title);
            foreach ($excludeWords as $word) {
                if (stripos($title, strtolower($word)) !== false) {
                    return false; // Excluir este post
                }
            }
            return true; // Mantener este post
        });
    }

    /**
     * Obtiene las palabras de exclusión de los argumentos de la query.
     */
    public function getExcludeWords(array $params): array
    {
        if (empty($params['exclude'])) {
            return [];
        }

        $excludeWords = array_map('trim', explode(',', $params['exclude']));
        return array_filter($excludeWords);
    }

    /**
     * Filtro por categoria (taxonomy).
     */
    private function applyCategoryFilter(array $args, array $params): array
    {
        if (empty($params['category'])) {
            return $args;
        }

        $args['tax_query'] = [
            [
                'taxonomy' => 'amazon_category',
                'field' => 'slug',
                'terms' => $params['category'],
            ]
        ];

        return $args;
    }

    /**
     * Filtros de precio minimo y maximo.
     */
    private function applyPriceFilters(array $args, array $params): array
    {
        // Precio minimo
        if (!empty($params['min_price'])) {
            $args['meta_query'][] = [
                'key' => 'price',
                'value' => (float) $params['min_price'],
                'compare' => '>=',
                'type' => 'NUMERIC'
            ];
        }

        // Precio maximo
        if (!empty($params['max_price'])) {
            $args['meta_query'][] = [
                'key' => 'price',
                'value' => (float) $params['max_price'],
                'compare' => '<=',
                'type' => 'NUMERIC'
            ];
        }

        return $args;
    }

    /**
     * Filtro por rating minimo.
     */
    private function applyRatingFilter(array $args, array $params): array
    {
        if (!empty($params['min_rating'])) {
            $args['meta_query'][] = [
                'key' => 'rating',
                'value' => (float) $params['min_rating'],
                'compare' => '>=',
                'type' => 'NUMERIC'
            ];
        }

        return $args;
    }

    /**
     * Filtro solo productos Prime.
     */
    private function applyPrimeFilter(array $args, array $params): array
    {
        if (!empty($params['only_prime'])) {
            $args['meta_query'][] = [
                'key' => 'prime',
                'value' => '1',
                'compare' => '='
            ];
        }

        return $args;
    }

    /**
     * Filtro solo productos con descuento (tienen original_price valido).
     */
    private function applyDealsFilter(array $args, array $params): array
    {
        if (empty($params['only_deals'])) {
            return $args;
        }

        $args['meta_query'][] = [
            'relation' => 'AND',
            [
                'key' => 'original_price',
                'compare' => 'EXISTS'
            ],
            [
                'key' => 'original_price',
                'value' => ['', '0', '0.00'],
                'compare' => 'NOT IN'
            ]
        ];

        return $args;
    }

    /**
     * Aplica ordenamiento a la query.
     * Solo aplica si no se especificaron IDs (los IDs mantienen su orden).
     */
    private function applySorting(array $args, array $params): array
    {
        // Si hay IDs especificos, mantener su orden
        if (!empty($params['ids'])) {
            return $args;
        }

        $orderby = $params['orderby'] ?? 'date';
        $order = $params['order'] ?? 'DESC';

        switch ($orderby) {
            case 'random':
                $args['orderby'] = 'rand';
                break;

            case 'price':
                $args['meta_key'] = 'price';
                $args['orderby'] = 'meta_value_num';
                break;

            case 'rating':
                $args['meta_key'] = 'rating';
                $args['orderby'] = 'meta_value_num';
                break;

            case 'discount':
                // Ordenamiento por descuento requiere traer todos para procesar en PHP
                $args['posts_per_page'] = -1;
                $args['orderby'] = 'date';
                break;

            default:
                $args['orderby'] = 'date';
                break;
        }

        $args['order'] = $order;

        return $args;
    }

    /**
     * Verifica si el ordenamiento es por descuento.
     * Este tipo de ordenamiento requiere procesamiento PHP adicional.
     */
    public function isDiscountSorting(array $params): bool
    {
        return ($params['orderby'] ?? 'date') === 'discount';
    }
}
