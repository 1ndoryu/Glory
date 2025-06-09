<?
// Glory/src/Services/BusquedaService.php

namespace Glory\Services;

use WP_Query;
use WP_User_Query;
use Glory\Core\GloryLogger;

/**
 * Gestiona la lógica de negocio para las operaciones de búsqueda en todo el sitio.
 *
 * Se encarga de ejecutar consultas para diferentes tipos de contenido (posts, usuarios)
 * y devolver los datos estructurados, listos para ser renderizados.
 */
class BusquedaService
{
    private $texto;
    private $configuracionBusqueda = [];
    private $resultados = [];

    /**
     * Constructor de la clase.
     *
     * @param string $texto El término de búsqueda introducido por el usuario.
     */
    public function __construct($texto)
    {
        $this->texto = sanitize_text_field($texto);
    }

    /**
     * Agrega un tipo de contenido para incluir en la búsqueda.
     *
     * @param string $tipo El identificador del tipo de búsqueda (ej. 'post', 'usuario').
     * @param array $args Argumentos específicos para este tipo de búsqueda.
     * @return self Permite encadenar métodos.
     */
    public function agregarTipoBusqueda($tipo, $args = []): self
    {
        $this->configuracionBusqueda[$tipo] = $args;
        return $this;
    }

    /**
     * Ejecuta todas las búsquedas configuradas.
     *
     * @return self
     */
    public function ejecutar(): self
    {
        GloryLogger::info('Iniciando ejecución de BusquedaService.', ['configuracion' => $this->configuracionBusqueda]);
        foreach ($this->configuracionBusqueda as $tipo => $args) {
            GloryLogger::info("Procesando tipo de búsqueda: {$tipo}", ['argumentos' => $args]);
            switch ($tipo) {
                case 'post':
                    $postType = $args['post_type'] ?? 'post';
                    $resultadosPost = $this->buscarPosts(
                        $postType,
                        $args['limite'] ?? 3
                    );
                    $this->resultados[$postType] = $resultadosPost;
                    GloryLogger::info("Búsqueda de posts '{$postType}' completada.", ['cantidad' => count($resultadosPost)]);
                    break;

                case 'usuario':
                    $resultadosUsuarios = $this->buscarUsuarios(
                        $args['limite'] ?? 3
                    );
                    $this->resultados['perfiles'] = $resultadosUsuarios;
                    GloryLogger::info('Búsqueda de usuarios completada.', ['cantidad' => count($resultadosUsuarios)]);
                    break;
            }
        }
        GloryLogger::info('Ejecución de BusquedaService finalizada.', ['resultados_totales' => $this->resultados]);
        return $this;
    }

    /**
     * Balancea los resultados para limitar el número de items por tipo.
     *
     * @param int $maxPorTipo El número máximo de items por cada tipo de resultado.
     * @return self Permite encadenar métodos.
     */
    public function balancear($maxPorTipo = 2): self
    {
        foreach ($this->resultados as $tipo => &$items) {
            $items = array_slice($items, 0, $maxPorTipo);
        }
        return $this;
    }

    /**
     * Devuelve los resultados finales de la búsqueda.
     *
     * @return array Los resultados de la búsqueda, listos para ser renderizados.
     */
    public function obtenerResultados(): array
    {
        return $this->resultados;
    }

    /**
     * Realiza una búsqueda de posts basada en un CPT.
     *
     * @param string $postType El slug del Custom Post Type.
     * @param int $limite El número máximo de resultados.
     * @return array Lista de resultados para este post type.
     */
    private function buscarPosts($postType, $limite): array
    {
        $resultadosFormateados = [];
        $query = new WP_Query([
            'post_type'   => $postType,
            'post_status'  => 'publish',
            's'       => $this->texto,
            'posts_per_page' => $limite,
        ]);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $resultadosFormateados[] = [
                    'titulo' => get_the_title(),
                    'url'  => get_permalink(),
                    'tipo' => ucfirst(str_replace(['_', '-'], ' ', $postType)),
                    'imagen' => $this->obtenerImagenPost(get_the_ID()),
                ];
            }
        }
        wp_reset_postdata();
        return $resultadosFormateados;
    }

    /**
     * Realiza una búsqueda de usuarios.
     *
     * @param int $limite El número máximo de resultados.
     * @return array Lista de perfiles de usuario encontrados.
     */
    private function buscarUsuarios($limite): array
    {
        $resultadosFormateados = [];
        $query = new WP_User_Query([
            'search'    => '*' . esc_attr($this->texto) . '*',
            'search_columns' => ['user_login', 'display_name', 'user_email'],
            'number'    => $limite,
        ]);

        $usuarios = $query->get_results();
        if (!empty($usuarios)) {
            foreach ($usuarios as $usuario) {
                $resultadosFormateados[] = [
                    'titulo' => $usuario->display_name,
                    'url'  => get_author_posts_url($usuario->ID),
                    'tipo' => 'Perfil',
                    'imagen' => get_avatar_url($usuario->ID),
                ];
            }
        }
        return $resultadosFormateados;
    }

    /**
     * Obtiene la URL de la imagen destacada de un post o de un campo personalizado.
     *
     * @param int $postId El ID del post.
     * @return string|false La URL de la imagen o false si no se encuentra.
     */
    private function obtenerImagenPost($postId)
    {
        if (has_post_thumbnail($postId)) {
            return get_the_post_thumbnail_url($postId, 'thumbnail');
        }

        // Se busca en un metadato con formato camelCase.
        $imagenId = get_post_meta($postId, 'imagenDestacada', true);
        if ($imagenId) {
            return wp_get_attachment_image_url($imagenId, 'thumbnail');
        }

        return false;
    }
}
