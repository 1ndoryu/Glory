<?php
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
 *
 * *Comentario por Jules:* El servicio de búsqueda es bastante completo. Considerar la paginación
 * para los resultados de búsqueda si se esperan muchos ítems.
 */
class BusquedaService
{
    private string $terminoBusqueda;
    private array $configuracionBusqueda = [];
    private array $resultados = [];

    /**
     * Constructor de la clase.
     *
     * @param string $terminoBusqueda El término de búsqueda introducido por el usuario.
     */
    public function __construct(string $terminoBusqueda)
    {
        $this->terminoBusqueda = sanitize_text_field($terminoBusqueda);
    }

    /**
     * Agrega un tipo de contenido para incluir en la búsqueda.
     *
     * @param string $tipoEntidad El identificador del tipo de entidad (ej. 'post', 'usuario').
     * @param array $argumentos Argumentos específicos para este tipo de entidad de búsqueda.
     * @return self Permite encadenar métodos.
     */
    public function agregarTipoBusqueda(string $tipoEntidad, array $argumentos = []): self
    {
        $this->configuracionBusqueda[$tipoEntidad] = $argumentos;
        return $this;
    }

    /**
     * Ejecuta todas las búsquedas configuradas.
     *
     * @return self Permite encadenar métodos.
     */
    public function ejecutar(): self
    {
        GloryLogger::info('Iniciando ejecución de BusquedaService.', ['configuracion' => $this->configuracionBusqueda, 'termino' => $this->terminoBusqueda]);
        foreach ($this->configuracionBusqueda as $tipoEntidad => $argumentos) {
            GloryLogger::info("Procesando tipo de entidad para búsqueda: {$tipoEntidad}", ['argumentos' => $argumentos]);
            switch ($tipoEntidad) {
                case 'post':
                    $tipoPost = $argumentos['post_type'] ?? 'post'; // Clave 'post_type' es de WP_Query, no traducir.
                    $resultadosPosts = $this->buscarPosts(
                        $tipoPost,
                        $argumentos['limite'] ?? 3
                    );
                    $this->resultados[$tipoPost] = $resultadosPosts; // Usa $tipoPost como clave para consistencia si es CPT.
                    GloryLogger::info("Búsqueda de posts '{$tipoPost}' completada.", ['cantidad' => count($resultadosPosts)]);
                    break;

                case 'usuario':
                    $resultadosUsuarios = $this->buscarUsuarios(
                        $argumentos['limite'] ?? 3
                    );
                    $this->resultados['perfiles'] = $resultadosUsuarios; // Clave 'perfiles' para diferenciar de tipos de post.
                    GloryLogger::info('Búsqueda de usuarios completada.', ['cantidad' => count($resultadosUsuarios)]);
                    break;
            }
        }
        GloryLogger::info('Ejecución de BusquedaService finalizada.', ['resultados_totales' => $this->resultados]);
        return $this;
    }

    /**
     * Balancea los resultados para limitar el número de ítems por tipo.
     *
     * @param int $maximoPorTipo El número máximo de ítems por cada tipo de resultado.
     * @return self Permite encadenar métodos.
     */
    public function balancear(int $maximoPorTipo = 2): self
    {
        foreach ($this->resultados as $tipo => &$items) { // $tipo aquí es la clave del array $this->resultados (ej. 'post', 'page', 'perfiles')
            $items = array_slice($items, 0, $maximoPorTipo);
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
     * Realiza una búsqueda de posts basada en un tipo de post específico.
     *
     * @param string $tipoPost Slug del tipo de post (ej. 'post', 'page', 'producto').
     * @param int $limite El número máximo de resultados a devolver.
     * @return array Lista de resultados para este tipo de post.
     */
    private function buscarPosts(string $tipoPost, int $limite): array
    {
        $resultadosFormateados = [];
        $queryArgs = [
            'post_type'   => $tipoPost,
            'post_status'  => 'publish',
            's'       => $this->terminoBusqueda, // Usa el término de búsqueda de la instancia.
            'posts_per_page' => $limite,
        ];
        $query = new WP_Query($queryArgs);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $resultadosFormateados[] = [
                    'titulo' => get_the_title(),
                    'url'  => get_permalink(),
                    'tipo' => ucfirst(str_replace(['_', '-'], ' ', $tipoPost)), // Muestra un nombre legible del tipo de post.
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
    private function buscarUsuarios(int $limite): array
    {
        $resultadosFormateados = [];
        $queryArgs = [
            'search'    => '*' . esc_attr($this->terminoBusqueda) . '*', // Usa el término de búsqueda de la instancia.
            'search_columns' => ['user_login', 'display_name', 'user_email'], // Columnas de WP_User_Query, no traducir.
            'number'    => $limite, // 'number' es de WP_User_Query, no traducir.
        ];
        $query = new WP_User_Query($queryArgs);

        $usuarios = $query->get_results();
        if (!empty($usuarios)) {
            foreach ($usuarios as $usuario) {
                $resultadosFormateados[] = [
                    'titulo' => $usuario->display_name,
                    'url'  => get_author_posts_url($usuario->ID),
                    'tipo' => 'Perfil', // Tipo de resultado específico para usuarios.
                    'imagen' => get_avatar_url($usuario->ID),
                ];
            }
        }
        return $resultadosFormateados;
    }

    /**
     * Obtiene la URL de la imagen destacada de un post o de un campo personalizado.
     * *Comentario por Jules:* Esta función actualmente prioriza la imagen destacada y luego un metadato específico ('imagenDestacada').
     * Para mayor flexibilidad, podría extenderse para buscar en una lista configurable de metadatos
     * o incluso permitir un filtro para que otros módulos modifiquen la lógica de obtención de imagen.
     *
     * @param int $idPost El ID del post.
     * @return string|false La URL de la imagen en tamaño 'thumbnail' o false si no se encuentra.
     */
    private function obtenerImagenPost(int $idPost)
    {
        if (has_post_thumbnail($idPost)) {
            return get_the_post_thumbnail_url($idPost, 'thumbnail');
        }

        // Se busca en un metadato con formato camelCase.
        // El nombre 'imagenDestacada' es un ejemplo, podría ser cualquier clave de metadato.
        $imagenId = get_post_meta($idPost, 'imagenDestacada', true);
        if ($imagenId) {
            // Asegura que $imagenId sea numérico (ID de adjunto) antes de pasarlo a wp_get_attachment_image_url.
            if (is_numeric($imagenId)) {
                return wp_get_attachment_image_url((int) $imagenId, 'thumbnail');
            }
        }

        return false; // Retorna false si no se encuentra ninguna imagen.
    }
}
