<?

namespace Glory\Services;

use WP_Query;
use WP_User_Query;

/**
 * Gestiona las operaciones de búsqueda en todo el sitio.
 *
 * Permite realizar búsquedas flexibles de diferentes tipos de contenido (posts, usuarios, etc.),
 * balancear los resultados y generar el HTML correspondiente.
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
     * @return array Los resultados crudos de la búsqueda.
     */
    public function ejecutar(): array
    {
        foreach ($this->configuracionBusqueda as $tipo => $args) {
            switch ($tipo) {
                case 'post':
                    $this->resultados[$args['post_type']] = $this->buscarPosts(
                        $args['post_type'],
                        $args['limite'] ?? 3
                    );
                    break;

                case 'usuario':
                    $this->resultados['perfiles'] = $this->buscarUsuarios(
                        $args['limite'] ?? 3
                    );
                    break;
            }
        }
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
            'post_type'      => $postType,
            'post_status'    => 'publish',
            's'              => $this->texto,
            'posts_per_page' => $limite,
        ]);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $resultadosFormateados[] = [
                    'titulo' => get_the_title(),
                    'url'    => get_permalink(),
                    'tipo'   => ucfirst(str_replace(['_', '-'], ' ', $postType)),
                    'imagen' => $this->obtenerImagenPost(get_the_ID()),
                ];
            }
        }
        wp_reset_postdata();
        return $resultadosFormateados;
    }

    /**
     * Devuelve los resultados de la búsqueda procesados.
     *
     * @return array Los resultados de la búsqueda.
     */
    public function obtenerResultados(): array
    {
        return $this->resultados;
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
            'search'         => '*' . esc_attr($this->texto) . '*',
            'search_columns' => ['user_login', 'display_name', 'user_email'],
            'number'         => $limite,
        ]);

        $usuarios = $query->get_results();
        if (!empty($usuarios)) {
            foreach ($usuarios as $usuario) {
                $resultadosFormateados[] = [
                    'titulo' => $usuario->display_name,
                    'url'    => get_author_posts_url($usuario->ID),
                    'tipo'   => 'Perfil',
                    // TODO: Implementar la obtención de la imagen de perfil.
                    'imagen' => null, // $this->obtenerImagenPerfil($usuario->ID),
                ];
            }
        }
        return $resultadosFormateados;
    }

    /**
     * Obtiene la URL de la imagen destacada o de un campo personalizado.
     *
     * @param int $postId El ID del post.
     * @return string|false La URL de la imagen o false si no se encuentra.
     */
    private function obtenerImagenPost($postId)
    {
        if (has_post_thumbnail($postId)) {
            return get_the_post_thumbnail_url($postId, 'thumbnail');
        }

        // TODO: Adaptar el nombre del metadato si es necesario.
        $imagenId = get_post_meta($postId, 'imagenTemporal', true);
        if ($imagenId) {
            return wp_get_attachment_image_url($imagenId, 'thumbnail');
        }

        return false;
    }

    /**
     * Balancea los resultados para que no haya un exceso de un solo tipo.
     *
     * @param int $maxPorTipo El número máximo de items por cada tipo de resultado.
     * @return self Permite encadenar métodos.
     */
    public function balancear($maxPorTipo = 2): self
    {
        $resultadosBalanceados = [];
        foreach ($this->resultados as $tipo => $items) {
            $resultadosBalanceados[$tipo] = array_slice($items, 0, $maxPorTipo);
        }
        $this->resultados = $resultadosBalanceados;
        return $this;
    }

    /**
     * Genera el HTML para mostrar los resultados de la búsqueda.
     *
     * @return string El bloque de HTML con los resultados.
     */
    public function generarHtml(): string
    {
        ob_start();
        $totalResultados = array_reduce($this->resultados, function ($carry, $grupo) {
            return $carry + count($grupo);
        }, 0);

        if ($totalResultados === 0) {
            echo '<div class="resultado-item">No se encontraron resultados.</div>';
        } else {
            foreach ($this->resultados as $grupo) {
                foreach ($grupo as $item) {
                    $this->renderizarItemHtml($item);
                }
            }
        }

        return ob_get_clean();
    }

    /**
     * Renderiza el HTML de un único item de resultado.
     *
     * @param array $item Los datos del item a renderizar.
     */
    private function renderizarItemHtml($item)
    {
        $url = esc_url($item['url']);
        $titulo = esc_html($item['titulo']);
        $tipo = esc_html($item['tipo']);
        $imagenHtml = '';

        if (!empty($item['imagen'])) {
            $imagenUrl = esc_url($item['imagen']);
            $imagenHtml = "<img class=\"resultado-imagen\" src=\"{$imagenUrl}\" alt=\"{$titulo}\">";
        }
?>
        <a href="<?= $url ?>" class="resultado-enlace">
            <div class="resultado-item">
                <?= $imagenHtml ?>
                <div class="resultado-info">
                    <h3><?= $titulo ?></h3>
                    <p><?= $tipo ?></p>
                </div>
            </div>
        </a>
<?
    }

    /**
     * Método estático para simplificar la ejecución de una búsqueda completa.
     *
     * @param string $texto El término de búsqueda.
     * @return string HTML con los resultados.
     */
    public static function buscar($texto): string
    {
        // TODO: Implementar un sistema de caché.
        // $cacheKey = 'busqueda_' . md5($texto);
        // $cachedHtml = wp_cache_get($cacheKey, 'glory');
        // if (false !== $cachedHtml) {
        //     return $cachedHtml;
        // }

        $manager = new self($texto);

        // Configurar los tipos de contenido a buscar
        $manager->agregarTipoBusqueda('post', ['post_type' => 'misiones', 'limite' => 3])
            ->agregarTipoBusqueda('usuario', ['limite' => 3]);

        $manager->ejecutar();
        $manager->balancear(2);

        $html = $manager->generarHtml();

        // wp_cache_set($cacheKey, $html, 'glory', HOUR_IN_SECONDS);

        return $html;
    }
}


use Glory\Components\BusquedaComponent;

function busquedaAjax()
{
    // Validar que los parámetros necesarios, incluido el renderer, están presentes.
    if (empty($_POST['texto']) || empty($_POST['tipos']) || empty($_POST['renderer'])) {
        wp_send_json_error(['message' => 'Parámetros insuficientes para la búsqueda.']);
        return;
    }

    $textoBusqueda = sanitize_text_field($_POST['texto']);
    $tipos = sanitize_text_field($_POST['tipos']);
    $renderer = sanitize_text_field($_POST['renderer']); // Captura el nombre del renderer
    $cantidad = !empty($_POST['cantidad']) ? absint($_POST['cantidad']) : 2;

    $manager = new BusquedaComponent($textoBusqueda);
    $tiposArray = explode(',', $tipos);

    foreach ($tiposArray as $tipo) {
        $tipo = trim($tipo);
        if ($tipo === 'perfiles') {
            $manager->agregarTipoBusqueda('usuario', ['limite' => $cantidad + 1]);
        } else {
            $manager->agregarTipoBusqueda('post', ['post_type' => $tipo, 'limite' => $cantidad + 1]);
        }
    }

    $manager->ejecutar();
    $manager->balancear($cantidad);
    
    $resultados = $manager->obtenerResultados();

    $html = call_user_func($renderer, $resultados);

    // Envía el HTML renderizado al frontend.
    wp_send_json_success(['html' => $html]);
}

add_action('wp_ajax_busquedaAjax', 'busquedaAjax');
