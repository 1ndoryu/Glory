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
     * 
     * Si hay un solo termino, usa la busqueda nativa de WordPress.
     * Si hay multiples terminos (con comas), NO los pasa a WP
     * para que se puedan filtrar en PHP con logica OR.
     */
    private function applySearchFilter(array $args, array $params): array
    {
        if (empty($params['search'])) {
            return $args;
        }

        $search = $params['search'];

        /* 
         * Si hay comas, NO pasar a WordPress.
         * Los terminos se filtraran en PHP despues con filterBySearchTerms.
         */
        if (strpos($search, ',') !== false) {
            return $args;
        }

        $args['s'] = $search;
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
     * Obtiene los terminos de busqueda de los parametros.
     * Solo devuelve terminos si se uso busqueda multiple (con comas).
     */
    public function getSearchTerms(array $params): array
    {
        if (empty($params['search'])) {
            return [];
        }

        $search = $params['search'];

        if (strpos($search, ',') === false) {
            return [];
        }

        $terms = array_map('trim', explode(',', $search));
        return array_filter($terms);
    }

    /**
     * Filtra los resultados incluyendo solo posts que contengan
     * al menos uno de los terminos de busqueda en el titulo.
     * 
     * @param array $posts Array de posts
     * @param array $searchTerms Terminos de busqueda (OR)
     * @return array Posts que coinciden con al menos un termino
     */
    public static function filterBySearchTerms(array $posts, array $searchTerms): array
    {
        if (empty($searchTerms)) {
            return $posts;
        }

        return array_filter($posts, function ($post) use ($searchTerms) {
            $title = strtolower($post->post_title);
            foreach ($searchTerms as $term) {
                if (stripos($title, strtolower($term)) !== false) {
                    return true;
                }
            }
            return false;
        });
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
     * 
     * Para orden aleatorio, usa una semilla fija para mantener consistencia
     * entre peticiones de paginacion. La semilla se genera una vez por sesion
     * y se pasa como parametro 'random_seed'.
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
                /* 
                 * Orden aleatorio con semilla para consistencia en paginacion.
                 * 
                 * Problema: RAND() genera un nuevo orden en cada query, causando
                 * que productos se repitan entre paginas.
                 * 
                 * Solucion: Usar RAND(seed) con una semilla fija por sesion.
                 * La semilla viene del frontend (random_seed) o se genera aqui.
                 */
                $seed = $this->getRandomSeed($params);
                $args['orderby'] = 'rand';

                // Agregar filtro para inyectar la semilla en la query SQL
                // Se auto-remueve despues de ejecutarse para evitar afectar otras queries
                $filterCallback = function ($orderby_sql) use ($seed, &$filterCallback) {
                    // Auto-remover el filtro inmediatamente
                    remove_filter('posts_orderby', $filterCallback, 10);

                    if (strpos($orderby_sql, 'RAND()') !== false) {
                        return str_replace('RAND()', "RAND({$seed})", $orderby_sql);
                    }
                    return $orderby_sql;
                };
                add_filter('posts_orderby', $filterCallback, 10, 1);
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
     * Obtiene o genera una semilla para el orden aleatorio.
     * 
     * La semilla asegura que el orden aleatorio sea consistente entre
     * peticiones de paginacion dentro de la misma sesion.
     * 
     * @param array $params Parametros de la query
     * @return int Semilla numerica para RAND()
     */
    private function getRandomSeed(array $params): int
    {
        // Si viene una semilla del frontend, usarla
        if (!empty($params['random_seed'])) {
            return (int) $params['random_seed'];
        }

        // Semilla por defecto: basada en la fecha del dia
        // Esto hace que el orden cambie cada dia pero sea consistente durante el dia
        return (int) date('Ymd');
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
