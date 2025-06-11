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
 * @see BusquedaRenderer Componente encargado de renderizar los resultados de esta búsqueda.
 *
 * Historial de Refactorización (Jules):
 * - Se ha continuado la separación de responsabilidades entre la consulta de datos y el formateo de resultados
 *   (ej. métodos `_formatearResultadoPost`, `_formatearResultadoUsuario`).
 * - El método que originalmente se llamaba `buscarUsuarios` fue renombrado a `_buscarUsuariosPredeterminado`
 *   para reflejar su naturaleza como una implementación de fallback.
 * - Se evaluó la paginación, pero se decidió mantenerla fuera del alcance de este servicio por ahora,
 *   dejando esa responsabilidad al consumidor del servicio o a una capa de presentación.
 */
class BusquedaService
{
    /** @var string El término de búsqueda sanitizado y almacenado. */
    private string $terminoBusqueda;
    /** @var array Configuración de los diversos tipos de entidades a buscar y sus respectivos argumentos y manejadores. */
    private array $configuracionBusqueda = [];
    /** @var array Almacena los resultados de la búsqueda, agrupados por tipo de entidad. */
    private array $resultados = [];

    /**
     * Constructor del servicio de búsqueda.
     *
     * @param string $terminoBusqueda El término de búsqueda original introducido por el usuario.
     *                                Será sanitizado automáticamente.
     */
    public function __construct(string $terminoBusqueda)
    {
        $this->terminoBusqueda = sanitize_text_field($terminoBusqueda);
    }

    /**
     * Agrega un tipo de entidad (ej. 'post', 'usuario') para incluir en la búsqueda global.
     *
     * Permite definir argumentos específicos para la consulta de esta entidad y, opcionalmente,
     * un manejador personalizado (`callable`) que se encargará de la lógica de búsqueda y formateo.
     * Si no se provee un manejador, el servicio intentará utilizar una implementación predeterminada
     * basada en el `$tipoEntidad` (ej. `_buscarPostsPredeterminado` para 'post').
     *
     * @param string $tipoEntidad Identificador único para el tipo de entidad (ej. 'post', 'usuario', 'productos').
     *                            Esta clave se usará para agrupar los resultados.
     * @param array $argumentos Un array asociativo con argumentos específicos para la búsqueda de esta entidad.
     *                          Para 'post', puede incluir 'post_type', 'limite'.
     *                          Para 'usuario', puede incluir 'limite', 'roles'.
     *                          La estructura dependerá del manejador (predeterminado o personalizado).
     * @param callable|null $manejadorBusqueda Un callable opcional que ejecuta la lógica de búsqueda para este tipo.
     *                                         Firma esperada: `function(string $terminoBusqueda, array $argumentos): array`.
     *                                         Debe devolver un array de resultados ya formateados.
     * @return self Retorna la propia instancia para permitir el encadenamiento de llamadas (`fluent interface`).
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
     * Ejecuta todas las búsquedas configuradas según los tipos de entidad y manejadores agregados.
     *
     * Itera sobre cada `$tipoEntidad` configurado:
     * 1. Si se proporcionó un `$manejadorBusqueda` personalizado (`callable`), se invoca.
     *    Este manejador es responsable tanto de la consulta como del formateo de los resultados.
     * 2. Si no hay manejador personalizado, se intenta utilizar un método de búsqueda y formateo
     *    predeterminado interno (ej., `_buscarPostsPredeterminado` para 'post',
     *    `_buscarUsuariosPredeterminado` para 'usuario').
     *
     * Los resultados de cada tipo de entidad se almacenan internamente.
     * Se registran logs informativos durante el proceso.
     *
     * @return self Retorna la propia instancia para encadenamiento.
     */
    public function ejecutar(): self
    {
        $configLog = array_map(function($config) {
            // No se puede serializar un Closure directamente en el log, así que se representa como string.
            if (is_callable($config['manejadorBusqueda'])) {
                $config['manejadorBusqueda'] = 'callable_personalizado';
            } else {
                $config['manejadorBusqueda'] = 'predeterminado_interno';
            }
            return $config;
        }, $this->configuracionBusqueda);

        GloryLogger::info('Iniciando ejecución de BusquedaService.', [
            'termino_busqueda' => $this->terminoBusqueda,
            'configuracion_busqueda' => $configLog
        ]);

        $this->resultados = []; // Limpiar resultados de ejecuciones previas.

        foreach ($this->configuracionBusqueda as $tipoEntidad => $config) {
            $argumentos = $config['argumentos'] ?? [];
            $manejadorPersonalizado = $config['manejadorBusqueda'] ?? null;
            $resultadosEntidad = [];

            GloryLogger::info("Procesando tipo de entidad para búsqueda: '{$tipoEntidad}'.", [
                'argumentos' => $argumentos,
                'usando_manejador_personalizado' => is_callable($manejadorPersonalizado)
            ]);

            if (is_callable($manejadorPersonalizado)) {
                $resultadosEntidad = call_user_func($manejadorPersonalizado, $this->terminoBusqueda, $argumentos);
            } else {
                // Intentar usar un manejador predeterminado basado en el tipo de entidad.
                switch ($tipoEntidad) {
                    case 'post': // Puede ser 'post' genérico o un CPT específico si se pasa en $argumentos.
                        $tipoPostEspecifico = $argumentos['post_type'] ?? 'post'; // Clave 'post_type' es estándar de WP_Query.
                        $limitePosts = $argumentos['limite'] ?? 3;
                        $resultadosEntidad = $this->_buscarPostsPredeterminado($tipoPostEspecifico, $limitePosts);
                        break;
                    case 'usuario':
                        $limiteUsuarios = $argumentos['limite'] ?? 3;
                        $resultadosEntidad = $this->_buscarUsuariosPredeterminado($limiteUsuarios);
                        break;
                    default:
                        GloryLogger::warning(
                            "BusquedaService: No se encontró manejador personalizado ni predeterminado para el tipo de entidad '{$tipoEntidad}'.",
                            ['termino_busqueda' => $this->terminoBusqueda]
                        );
                        break;
                }
            }

            $this->resultados[$tipoEntidad] = $resultadosEntidad;
            GloryLogger::info("Búsqueda para '{$tipoEntidad}' completada.", ['cantidad_resultados' => count($resultadosEntidad)]);
        }

        GloryLogger::info('Ejecución de BusquedaService finalizada.', ['tipos_procesados' => array_keys($this->resultados)]);
        return $this;
    }

    /**
     * Balancea los resultados para limitar el número de ítems mostrados por cada tipo de entidad.
     *
     * Este método es útil para asegurar que ningún tipo de resultado domine la visualización final
     * si, por ejemplo, se encuentran muchos posts pero pocos usuarios.
     *
     * @param int $maximoPorTipo El número máximo de ítems a conservar por cada tipo de resultado.
     * @return self Retorna la propia instancia para encadenamiento.
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
     * Devuelve los resultados finales de la búsqueda, agrupados por tipo de entidad.
     *
     * @return array Los resultados de la búsqueda, estructurados y listos para ser consumidos o renderizados.
     *               Ejemplo: `['post' => [...], 'usuario' => [...]]`.
     */
    public function obtenerResultados(): array
    {
        return $this->resultados;
    }

    /**
     * Realiza una búsqueda de posts utilizando `WP_Query` como implementación predeterminada.
     *
     * Este método se invoca si se agrega el tipo de entidad 'post' (o un CPT) sin un manejador personalizado.
     * Los resultados son formateados por `_formatearResultadoPost`.
     * Las claves de los argumentos de WP_Query (`post_type`, `post_status`, `posts_per_page`)
     * son estándar de WordPress y se mantienen en `snake_case`.
     *
     * @param string $tipoPostSlug Slug del tipo de post a buscar (ej. 'post', 'page', 'evento_cpt').
     * @param int    $limite       El número máximo de resultados a devolver para este tipo.
     * @return array Lista de posts formateados. Vacía si no hay resultados.
     */
    private function _buscarPostsPredeterminado(string $tipoPostSlug, int $limite): array
    {
        $resultadosFormateados = [];
        $queryArgs = [
            'post_type'      => $tipoPostSlug, // Clave estándar de WP_Query
            'post_status'    => 'publish',    // Clave estándar de WP_Query
            's'              => $this->terminoBusqueda,
            'posts_per_page' => $limite,       // Clave estándar de WP_Query
        ];
        $query = new WP_Query($queryArgs);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post(); // Configura el post global de WordPress
                // get_post() obtiene el post actual del bucle de WordPress
                $resultadosFormateados[] = $this->_formatearResultadoPost(get_post(), $tipoPostSlug);
            }
        }
        wp_reset_postdata(); // Restaura el post global original
        return $resultadosFormateados;
    }

    /**
     * Formatea un objeto `WP_Post` en un array estructurado para los resultados de búsqueda.
     *
     * Las claves del array resultante (`titulo`, `url`, `tipo`, `imagen`) están en `camelCase` o minúsculas.
     *
     * @param \WP_Post $post         El objeto del post a formatear.
     * @param string   $tipoPostSlug Slug del tipo de post (ej. 'post', 'evento_cpt'). Se usa para generar
     *                               un nombre legible para el campo 'tipo' y para buscar metadatos de imagen.
     * @return array Datos del post formateados.
     */
    private function _formatearResultadoPost(\WP_Post $post, string $tipoPostSlug): array
    {
        // Funciones como get_the_title() y get_permalink() operan sobre el post global
        // configurado por $query->the_post() en el método llamador.
        // Usar $post->ID asegura que se refiera al post correcto del bucle.
        $metaClavesParaImagen = ['imagenDestacada', $tipoPostSlug . '_imagen_destacada']; // 'imagenDestacada' es camelCase, el segundo es dinámico.
        return [
            'titulo' => get_the_title($post->ID),
            'url'    => get_permalink($post->ID),
            'tipo'   => ucfirst(str_replace(['_', '-'], ' ', $tipoPostSlug)), // ej. 'Mi Cpt' desde 'mi_cpt'
            'imagen' => $this->obtenerImagenPost($post->ID, $metaClavesParaImagen),
            // 'id' => $post->ID, // Opcional: incluir el ID si es útil para el frontend.
        ];
    }

    /**
     * Realiza una búsqueda de usuarios utilizando `WP_User_Query` como implementación predeterminada.
     *
     * Este método se invoca si se agrega el tipo de entidad 'usuario' sin un manejador personalizado.
     * Los resultados son formateados por `_formatearResultadoUsuario`.
     * Las claves de los argumentos de WP_User_Query (`search_columns`, `number`)
     * son estándar de WordPress y se mantienen en `snake_case`.
     *
     * @param int $limite El número máximo de resultados a devolver.
     * @return array Lista de usuarios formateados. Vacía si no hay resultados.
     */
    private function _buscarUsuariosPredeterminado(int $limite): array
    {
        $resultadosFormateados = [];
        $queryArgs = [
            'search'         => '*' . esc_attr($this->terminoBusqueda) . '*', // Búsqueda wildcard
            'search_columns' => ['user_login', 'display_name', 'user_email'], // Claves estándar de WP_User_Query
            'number'         => $limite, // Clave estándar de WP_User_Query
            // 'role'        => 'Subscriber', // Ejemplo: filtrar por rol si se pasara en $argumentos
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
     * Formatea un objeto `WP_User` en un array estructurado para los resultados de búsqueda.
     *
     * Las claves del array resultante (`titulo`, `url`, `tipo`, `imagen`) están en `camelCase` o minúsculas.
     *
     * @param \WP_User $usuario El objeto del usuario a formatear.
     * @return array Datos del usuario formateados.
     */
    private function _formatearResultadoUsuario(\WP_User $usuario): array
    {
        return [
            'titulo' => $usuario->display_name,
            'url'    => get_author_posts_url($usuario->ID),
            'tipo'   => 'Perfil', // Tipo de resultado específico para la entidad 'usuario'.
            'imagen' => get_avatar_url($usuario->ID),
            // 'id' => $usuario->ID, // Opcional: incluir el ID si es útil.
        ];
    }

    /**
     * Obtiene la URL de la imagen asociada a un post, priorizando la imagen destacada.
     *
     * Primero intenta obtener la imagen destacada estándar de WordPress (post thumbnail).
     * Si no existe, itera sobre un array de claves de metadatos (`$metaClavesImagen`)
     * para buscar un ID de adjunto de imagen en los campos personalizados del post.
     * La URL de la imagen se obtiene en tamaño 'thumbnail'.
     *
     * Un filtro `glory_busqueda_imagen_post` permite modificar externamente la URL de la imagen
     * o la lógica de obtención.
     *
     * @param int   $idPost           El ID del post.
     * @param array $metaClavesImagen Array de claves de metadatos donde buscar el ID de la imagen si no hay thumbnail.
     *                                Ejemplo: `['imagenDestacada', 'custom_post_type_featured_image']`.
     *                                Por defecto, solo busca 'imagenDestacada'.
     * @return string|false La URL de la imagen en tamaño 'thumbnail', o `false` si no se encuentra ninguna imagen.
     */
    private function obtenerImagenPost(int $idPost, array $metaClavesImagen = ['imagenDestacada']): string|false
    {
        $imagenUrl = false;

        if (has_post_thumbnail($idPost)) {
            $imagenUrl = get_the_post_thumbnail_url($idPost, 'thumbnail');
        } else {
            foreach ($metaClavesImagen as $metaClave) {
                $imagenId = get_post_meta($idPost, $metaClave, true);
                if ($imagenId && is_numeric($imagenId)) { // Asegura que sea un ID numérico de adjunto.
                    $urlObtenida = wp_get_attachment_image_url((int) $imagenId, 'thumbnail');
                    if ($urlObtenida) {
                        $imagenUrl = $urlObtenida;
                        break; // Imagen encontrada a través de metadatos, salir del bucle.
                    }
                }
            }
        }

        // Filtro para permitir la modificación externa de la URL de la imagen o la lógica de obtención.
        // El autor original del filtro es @wandorius.
        $imagenUrlFiltrada = apply_filters('glory_busqueda_imagen_post', $imagenUrl, $idPost, $metaClavesImagen);

        return $imagenUrlFiltrada;
    }
}
