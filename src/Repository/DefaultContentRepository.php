<?
namespace Glory\Repository;

use WP_Query;
use WP_Post;

/**
 * Repositorio para el contenido por defecto.
 *
 * Abstrae todas las interacciones con la base de datos (WP_Query, get_post_meta)
 * relacionadas con los posts gestionados por el sistema de contenido por defecto.
 * Su única responsabilidad es consultar y devolver datos.
 */
class DefaultContentRepository
{
    private const META_CLAVE_SLUG_DEFAULT = '_glory_default_content_slug';
    private const META_CLAVE_EDITADO_MANUALMENTE = '_glory_default_content_edited';

    /**
     * Busca un post gestionado existente por su tipo y slug de definición.
     *
     * @param string $tipoPost El tipo de post a buscar.
     * @param string $slugDefault El slug único definido en el código.
     * @return WP_Post|null El objeto WP_Post si se encuentra, de lo contrario null.
     */
    public function findPorSlug(string $tipoPost, string $slugDefault): ?WP_Post
    {
        $queryArgs = [
            'post_type' => $tipoPost,
            'post_status' => 'any',
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => self::META_CLAVE_SLUG_DEFAULT,
                    'value' => $slugDefault,
                    'compare' => '=',
                ],
            ],
            'fields' => 'ids',
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ];
        
        $consulta = new WP_Query($queryArgs);

        if (!empty($consulta->posts)) {
            $postEncontrado = get_post($consulta->posts[0]);
            return ($postEncontrado instanceof WP_Post) ? $postEncontrado : null;
        }

        return null;
    }

    /**
     * Encuentra todos los posts gestionados de un tipo que ya no están en la lista de slugs definidos.
     *
     * @param string $tipoPost El tipo de post.
     * @param array $slugsDefinidos Array de slugs que SÍ están definidos actualmente en el código.
     * @return int[] Un array de IDs de posts gestionados que son obsoletos.
     */
    public function findObsoletos(string $tipoPost, array $slugsDefinidos): array
    {
        $queryArgs = [
            'post_type' => $tipoPost,
            'post_status' => 'any',
            'posts_per_page' => -1,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => self::META_CLAVE_SLUG_DEFAULT,
                    'compare' => 'EXISTS',
                ],
                [
                    'key' => self::META_CLAVE_SLUG_DEFAULT,
                    'value' => $slugsDefinidos,
                    'compare' => 'NOT IN',
                ],
            ],
            'fields' => 'ids',
            'no_found_rows' => true,
            'update_post_term_cache' => false,
        ];
        
        $consulta = new WP_Query($queryArgs);

        return !empty($consulta->posts) ? $consulta->posts : [];
    }
    
    /**
     * Verifica si un post ha sido marcado como editado manualmente.
     *
     * @param int $idPost El ID del post.
     * @return bool
     */
    public function haSidoEditadoManualmente(int $idPost): bool
    {
        return get_post_meta($idPost, self::META_CLAVE_EDITADO_MANUALMENTE, true) === '1';
    }
}