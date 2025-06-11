<?php
namespace Glory\Services;

use WP_Query;
use WP_User_Query;
use Glory\Core\GloryLogger;

/**
 * Gestiona la lógica de negocio para las operaciones de búsqueda en todo el sitio.
 *
 * Se encarga de ejecutar consultas para diferentes tipos de contenido (posts, usuarios, o tipos personalizados mediante manejadores)
 * y devolver los datos estructurados, listos para ser renderizados.
 *
 * @author @wandorius
 * // @tarea Jules: Considerar la implementación de paginación para los resultados de búsqueda si el volumen de datos esperado es alto.
 * // @tarea Jules: Evaluar separar la responsabilidad de consulta de la responsabilidad de formateo de resultados en BusquedaService para un mejor SRP. (Implementado parcialmente mediante métodos _formatearResultadoPost y _formatearResultadoUsuario)
 * @tarea Jules: Refactorizado para separar consulta y formateo de resultados. Método buscarUsuarios renombrado a _buscarUsuariosPredeterminado.
 */
class BusquedaService
{
    /** @var string El término de búsqueda sanitizado. */
    private string $terminoBusqueda;
    /** @var array Configuración de los tipos de entidades a buscar y sus argumentos. */
    private array $configuracionBusqueda = [];
    /** @var array Almacena los resultados de la búsqueda. */
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
     * @param string $tipoEntidad Identificador único para el tipo de entidad (ej. 'post', 'usuario', 'productos_especiales').
     *                            Esta clave se usará para almacenar los resultados.
     * @param array $argumentos Argumentos específicos para la búsqueda de esta entidad.
     *                          Para tipos predeterminados como 'post', puede incluir 'post_type', 'limite'.
     *                          Para manejadores personalizados, la estructura de estos argumentos dependerá del manejador.
     * @param callable|null $manejadorBusqueda Un callable que ejecutará la lógica de búsqueda para este tipo.
     *                                         Debe tener la firma: `function(string $terminoBusqueda, array $argumentos): array`.
     *                                         Si es null, se intentará usar un método de búsqueda predeterminado basado en `$tipoEntidad`.
     * @return self Permite encadenar métodos para una configuración fluida.
     */
    public function agregarTipoBusqueda(string $tipoEntidad, array $argumentos = [], ?callable $manejadorBusqueda = null): self
    {
        $this->configuracionBusqueda[$tipoEntidad] = [
            'argumentos' => $argumentos,
            'manejadorBusqueda' => $manejadorBusqueda
        ];
        return $this;
    }

    /**
     * Ejecuta todas las búsquedas configuradas.
     * Itera sobre los tipos de entidad configurados. Si se ha definido un manejador de búsqueda personalizado (`callable`)
     * para un tipo, se invoca dicho manejador. De lo contrario, se intenta utilizar un método de búsqueda
     * predeterminado interno (ej., para 'post', 'usuario'). Los resultados de cada tipo de entidad
     * se almacenan en el array `$this->resultados` utilizando `$tipoEntidad` como clave.
     *
     * @return self Permite encadenar métodos.
     */
    public function ejecutar(): self
    {
        GloryLogger::info('Iniciando ejecución de BusquedaService.', [
            'configuracion' => array_map(function($config) {
                // No se puede serializar un Closure, así que lo representamos como string.
                if (is_callable($config['manejadorBusqueda'])) {
                    $config['manejadorBusqueda'] = 'callable';
                }
                return $config;
            }, $this->configuracionBusqueda),
            'termino' => $this->terminoBusqueda
        ]);

        $this->resultados = []; // Limpiar resultados previos

        foreach ($this->configuracionBusqueda as $tipoEntidad => $config) {
            $argumentos = $config['argumentos'] ?? [];
            $manejador = $config['manejadorBusqueda'] ?? null;
            $resultadosEntidad = [];

            GloryLogger::info("Procesando tipo de entidad para búsqueda: {$tipoEntidad}", ['argumentos' => $argumentos, 'tiene_manejador' => !is_null($manejador)]);

            if (is_callable($manejador)) {
                $resultadosEntidad = call_user_func($manejador, $this->terminoBusqueda, $argumentos);
            } else {
                // Fallback a la lógica predeterminada si no hay manejador
                // o si el manejador no es callable (aunque la firma del método ya lo sugiere).
                switch ($tipoEntidad) {
                    case 'post': // Podría ser un tipo genérico 'post' o un CPT específico.
                        $tipoPostEspecifico = $argumentos['post_type'] ?? 'post';
                        $resultadosEntidad = $this->_buscarPostsPredeterminado(
                            $tipoPostEspecifico,
                            $argumentos['limite'] ?? 3
                        );
                        // La clave en $this->resultados será $tipoEntidad (ej. 'post', o si se registró un CPT como 'evento', será 'evento').
                        break;
                    case 'usuario':
                        $resultadosEntidad = $this->_buscarUsuariosPredeterminado(
                            $argumentos['limite'] ?? 3
                        );
                        break;
                    // Aquí se podrían añadir más casos predeterminados si es necesario.
                    default:
                        GloryLogger::warning("BusquedaService: No hay un manejador de búsqueda predeterminado ni uno personalizado válido para el tipo de entidad '{$tipoEntidad}'.");
                        break;
                }
            }

            $this->resultados[$tipoEntidad] = $resultadosEntidad;
            GloryLogger::info("Búsqueda para '{$tipoEntidad}' completada.", ['cantidad' => count($resultadosEntidad)]);
        }
        GloryLogger::info('Ejecución de BusquedaService finalizada.', ['tipos_procesados' => array_keys($this->resultados)]);
        return $this;
    }

    /**
     * Balancea los resultados para limitar el número de ítems por tipo.
     * Asegura que ningún tipo de resultado domine la visualización final.
     *
     * @param int $maximoPorTipo El número máximo de ítems por cada tipo de resultado.
     * @return self Permite encadenar métodos.
     */
    public function balancear(int $maximoPorTipo = 2): self
    {
        // $tipo es la clave del array $this->resultados (ej. 'post', 'page', 'perfiles').
        foreach ($this->resultados as $tipo => &$items) {
            $items = array_slice($items, 0, $maximoPorTipo);
        }
        return $this;
    }

    /**
     * Devuelve los resultados finales de la búsqueda.
     *
     * @return array Los resultados de la búsqueda, estructurados y listos para ser renderizados.
     */
    public function obtenerResultados(): array
    {
        return $this->resultados;
    }

    /**
     * Realiza una búsqueda de posts predeterminada utilizando WP_Query.
     * Este método se usa como fallback si no se proporciona un manejador específico para un tipo 'post'
     * o un tipo de contenido personalizado (CPT). Delega el formateo de cada post
     * al método `_formatearResultadoPost`.
     *
     * @param string $tipoPostSlug Slug del tipo de post a buscar (ej. 'post', 'page', 'mi_cpt').
     * @param int $limite El número máximo de resultados a devolver.
     * @return array Lista de resultados formateados para este tipo de post.
     */
    private function _buscarPostsPredeterminado(string $tipoPostSlug, int $limite): array
    {
        $resultadosFormateados = [];
        $queryArgs = [
            'post_type'      => $tipoPostSlug,
            'post_status'    => 'publish',
            's'              => $this->terminoBusqueda,
            'posts_per_page' => $limite,
        ];
        $query = new WP_Query($queryArgs);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $resultadosFormateados[] = $this->_formatearResultadoPost(get_post(), $tipoPostSlug);
            }
        }
        wp_reset_postdata();
        return $resultadosFormateados;
    }

    /**
     * Formatea un objeto WP_Post para los resultados de búsqueda.
     *
     * @param \WP_Post $post Objeto del post a formatear.
     * @param string $tipoPostSlug Slug del tipo de post (usado para generar el tipo legible y meta keys de imagen).
     * @return array Datos del post formateados para la búsqueda.
     * @author @wandorius (Refactorizado por Jules)
     */
    private function _formatearResultadoPost(\WP_Post $post, string $tipoPostSlug): array
    {
        // Nota: get_the_title(), get_permalink(), get_the_ID() funcionan con el post global.
        // Es importante que the_post() haya sido llamado antes si se usan estas funciones directamente.
        // En este caso, _buscarPostsPredeterminado hace the_post().
        $metaClavesParaImagen = ['imagenDestacada', $tipoPostSlug . '_imagen_destacada'];
        return [
            'titulo' => get_the_title($post->ID),
            'url'    => get_permalink($post->ID),
            'tipo'   => ucfirst(str_replace(['_', '-'], ' ', $tipoPostSlug)),
            'imagen' => $this->obtenerImagenPost($post->ID, $metaClavesParaImagen),
        ];
    }

    /**
     * Realiza una búsqueda de usuarios predeterminada utilizando WP_User_Query.
     * Este método se usa como fallback si no se proporciona un manejador específico para el tipo 'usuario'.
     * Delega el formateo de cada usuario al método `_formatearResultadoUsuario`.
     *
     * @param int $limite El número máximo de resultados a devolver.
     * @return array Lista de resultados formateados para usuarios.
     */
    private function _buscarUsuariosPredeterminado(int $limite): array
    {
        $resultadosFormateados = [];
        $queryArgs = [
            'search'    => '*' . esc_attr($this->terminoBusqueda) . '*', // Usa el término de búsqueda de la instancia.
            // Las claves 'search_columns' son específicas de WP_User_Query y no deben traducirse.
            'search_columns' => ['user_login', 'display_name', 'user_email'],
            // La clave 'number' es específica de WP_User_Query y no debe traducirse.
            'number'    => $limite,
        ];
        $query = new WP_User_Query($queryArgs);

        $usuarios = $query->get_results();
        if (!empty($usuarios)) {
            foreach ($usuarios as $usuario) {
                $resultadosFormateados[] = $this->_formatearResultadoUsuario($usuario);
            }
        }
        return $resultadosFormateados;
    }

    /**
     * Formatea un objeto WP_User para los resultados de búsqueda.
     *
     * @param \WP_User $usuario Objeto del usuario a formatear.
     * @return array Datos del usuario formateados para la búsqueda.
     * @author @wandorius (Refactorizado por Jules)
     */
    private function _formatearResultadoUsuario(\WP_User $usuario): array
    {
        return [
            'titulo' => $usuario->display_name,
            'url'    => get_author_posts_url($usuario->ID),
            'tipo'   => 'Perfil', // Tipo de resultado específico para usuarios.
            'imagen' => get_avatar_url($usuario->ID),
        ];
    }

    /**
     * Obtiene la URL de la imagen destacada de un post o de un campo personalizado.
     *
     * Esta función prioriza la imagen destacada estándar de WordPress. Si no existe,
     * itera sobre un array de claves de metadatos proporcionadas para encontrar un ID de adjunto de imagen.
     * Finalmente, permite modificar la URL de la imagen o la lógica de obtención mediante un filtro.
     *
     * @param int $idPost El ID del post.
     * @param array $metaClavesImagen Array de claves de metadatos donde buscar el ID de la imagen. Por defecto ['imagenDestacada'].
     * @return string|false La URL de la imagen en tamaño 'thumbnail' o false si no se encuentra.
     */
    private function obtenerImagenPost(int $idPost, array $metaClavesImagen = ['imagenDestacada']): string|false
    {
        $imagenUrl = false;

        if (has_post_thumbnail($idPost)) {
            $imagenUrl = get_the_post_thumbnail_url($idPost, 'thumbnail');
        } else {
            foreach ($metaClavesImagen as $metaClave) {
                $imagenId = get_post_meta($idPost, $metaClave, true);
                if ($imagenId && is_numeric($imagenId)) { // Asegura que sea un ID numérico.
                    $urlObtenida = wp_get_attachment_image_url((int) $imagenId, 'thumbnail');
                    if ($urlObtenida) {
                        $imagenUrl = $urlObtenida;
                        break; // Imagen encontrada, salir del bucle.
                    }
                }
            }
        }

        // Permitir modificación externa de la URL de la imagen o lógica de obtención.
        // @author @wandorius
        $imagenUrlFiltrada = apply_filters('glory_busqueda_imagen_post', $imagenUrl, $idPost, $metaClavesImagen);

        return $imagenUrlFiltrada;
    }
}
