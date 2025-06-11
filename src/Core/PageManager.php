<?php

namespace Glory\Core;

use Glory\Core\GloryLogger;

/**
 * Gestiona la creación y reconciliación de páginas personalizadas en WordPress.
 * Permite definir páginas a través de código y asegura que existan en la base de datos,
 * actualizando sus plantillas y opcionalmente configurándolas como página de inicio.
 *
 * *Comentario por Jules:* La gestión de páginas por código es útil. Considerar si se necesita un mecanismo
 * para que los plugins/temas extiendan o modifiquen páginas definidas por el framework de forma segura.
 */
class PageManager {
    private const CLAVE_META_GESTION = '_page_manager_managed'; // Clave para marcar páginas gestionadas.
    private static array $paginasDefinidas = []; // Almacena las definiciones de las páginas.

    /**
     * Define una página que será gestionada por el PageManager.
     *
     * @param string $slug El slug de la página (ej. 'contacto', 'sobre-nosotros'). Debe ser único y en minúsculas con guiones.
     * @param string|null $titulo El título de la página. Si es null, se generará a partir del slug.
     * @param string|null $plantilla La plantilla de página a asignar (ej. 'template-contacto.php'). Si es null, se intentará generar un nombre.
     */
    public static function define(string $slug, ?string $titulo = null, ?string $plantilla = null): void {
        if (empty($slug) || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            GloryLogger::error("PageManager: Slug inválido '{$slug}'. Los slugs deben ser alfanuméricos en minúsculas con guiones.");
            return;
        }

        if (is_null($titulo)) {
            $titulo = ucwords(str_replace(['-', '_'], ' ', $slug)); // Genera un título legible desde el slug.
        }
        // Si no se provee una plantilla, se genera un nombre basado en el slug.
        // Ejemplo: slug 'mi-pagina' -> plantilla 'TemplateMiPagina.php'
        if (is_null($plantilla)) {
            $nombrePlantilla = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $slug)));
            $plantilla = "Template{$nombrePlantilla}.php";
        }
        self::$paginasDefinidas[$slug] = [
            'titulo' => $titulo,
            'plantilla' => $plantilla,
            'slug' => $slug
        ];
    }

    /**
     * Registra los hooks de WordPress necesarios para procesar y reconciliar las páginas.
     * Se engancha a 'init' para ambas acciones, con diferentes prioridades.
     */
    public static function register(): void {
        add_action('init', [self::class, 'procesarPaginasDefinidas'], 10);
        add_action('init', [self::class, 'reconciliarPaginasGestionadas'], 100);
    }

    /**
     * Crea o actualiza las páginas definidas en la base de datos.
     * Este método se ejecuta en el hook 'init'.
     * También actualiza la página de inicio si se define una página con slug 'home'.
     */
    public static function procesarPaginasDefinidas(): void {
        $idPaginaInicioProcesada = null;
        $idsPaginasProcesadas = [];

        if (!empty(self::$paginasDefinidas)) {
            foreach (self::$paginasDefinidas as $slug => $defPagina) {
                $slugPagina = $defPagina['slug'];
                $tituloPagina = $defPagina['titulo'];
                $plantillaPagina = $defPagina['plantilla'];
                $idPaginaActual = null;

                $paginaExistente = get_page_by_path($slugPagina, OBJECT, 'page');

                if (!$paginaExistente) {
                    $datosPagina = [
                        'post_title' => $tituloPagina,
                        'post_content' => "<!-- Página gestionada por Glory PageManager. Slug: {$slugPagina} -->",
                        'post_status' => 'publish',
                        'post_type' => 'page',
                        'post_name' => $slugPagina,
                        'page_template' => $plantillaPagina ?: '', // Si no hay plantilla, se usa string vacío.
                    ];
                    $idInsertado = wp_insert_post($datosPagina, true); // El segundo parámetro true devuelve WP_Error en caso de fallo.

                    if (!is_wp_error($idInsertado) && $idInsertado > 0) {
                        $idPaginaActual = $idInsertado;
                        update_post_meta($idPaginaActual, self::CLAVE_META_GESTION, true); // Marca la página como gestionada.
                    } else {
                        $mensajeError = is_wp_error($idInsertado) ? $idInsertado->get_error_message() : 'Error desconocido (ID 0)';
                        GloryLogger::error("PageManager: FALLÓ al crear página '{$slugPagina}': " . $mensajeError);
                        continue; // Continúa con la siguiente página definida.
                    }
                } else {
                    // La página ya existe, se asegura que esté marcada como gestionada y actualiza la plantilla si es necesario.
                    $idPaginaActual = $paginaExistente->ID;
                    update_post_meta($idPaginaActual, self::CLAVE_META_GESTION, true);
                    $plantillaActual = get_post_meta($idPaginaActual, '_wp_page_template', true);
                    $nuevoValorPlantilla = $plantillaPagina ?: '';

                    if ($plantillaActual !== $nuevoValorPlantilla) {
                        update_post_meta($idPaginaActual, '_wp_page_template', $nuevoValorPlantilla);
                    }
                }

                if ($idPaginaActual) {
                    $idsPaginasProcesadas[] = $idPaginaActual;
                    // Si la página tiene slug 'home', se considera para ser la página de inicio.
                    if ($slugPagina === 'home') {
                        $idPaginaInicioProcesada = $idPaginaActual;
                    }
                }
            }
        } else {
            // No hay páginas definidas en el array self::$paginasDefinidas.
            GloryLogger::warning("PageManager procesarPaginasDefinidas: No hay páginas definidas en self::\$paginasDefinidas.");
            $idPaginaFrontalActual = (int) get_option('page_on_front');
            // Si la página frontal actual está marcada como gestionada pero no hay definiciones,
            // se considera un estado inconsistente y se podría desconfigurar.
            if ($idPaginaFrontalActual > 0 && get_option('show_on_front') === 'page') {
                if (get_post_meta($idPaginaFrontalActual, self::CLAVE_META_GESTION, true)) {
                    GloryLogger::error("La página frontal actual (ID: {$idPaginaFrontalActual}) es gestionada pero no hay páginas definidas en el código. Se anulará la página de inicio.");
                    $idPaginaInicioProcesada = null; // Para que actualizarOpcionesPaginaFrontal la desconfigure.
                }
            }
        }

        self::actualizarOpcionesPaginaFrontal($idPaginaInicioProcesada);
        // Guarda los IDs de las páginas procesadas en un transitorio para usarlos en la reconciliación.
        // Esto evita recalcularlos si la reconciliación se ejecuta en la misma petición. - Jules
        set_transient('pagemanager_ids_procesados', $idsPaginasProcesadas, 15 * MINUTE_IN_SECONDS);
    }

    /**
     * Reconcilia las páginas gestionadas en la base de datos con las definiciones actuales.
     * Elimina páginas que fueron gestionadas previamente pero ya no están definidas en el código.
     * Utiliza un transitorio para optimizar la obtención de IDs de páginas procesadas recientemente.
     */
    public static function reconciliarPaginasGestionadas(): void {
        // Intenta obtener los IDs de las páginas que se acaban de procesar/definir desde un transitorio.
        // Esto es una optimización para evitar consultas a la BD si procesarPaginasDefinidas() ya se ejecutó. - Jules
        $idsDefinidosActuales = get_transient('pagemanager_ids_procesados');

        if ($idsDefinidosActuales === false) {
            // El transitorio no existe o ha expirado, se deben reconstruir los IDs de las páginas definidas actualmente. - Jules
            $idsDefinidosActuales = [];
            if (!empty(self::$paginasDefinidas)) {
                $slugsDefinidos = array_keys(self::$paginasDefinidas);
                // Obtener todas las páginas marcadas como gestionadas que coinciden con los slugs definidos.
                $args = [
                    'post_type' => 'page',
                    'post_status' => 'any', // Considerar todos los estados
                    'posts_per_page' => -1, // Sin límite
                    'meta_key' => self::CLAVE_META_GESTION,
                    'meta_value' => true,
                    'fields' => 'ids', // Solo necesitamos los IDs
                    // 'post_name__in' => $slugsDefinidos, // Comentado como en el original, para obtener todas las gestionadas y luego filtrar.
                ];
                $idsReconstruidos = get_posts($args);

                if (!empty($idsReconstruidos)) {
                    foreach ($idsReconstruidos as $idPagina) {
                        $slugPagina = get_post_field('post_name', $idPagina);
                        // Solo se consideran válidas si el slug de la página en BD está en las definiciones actuales.
                        if (in_array($slugPagina, $slugsDefinidos, true)) {
                            $idsDefinidosActuales[] = $idPagina;
                        }
                    }
                }
            } else {
                // No hay páginas definidas, por lo que no hay IDs esperados.
                GloryLogger::warning("PageManager reconciliarPaginasGestionadas: No hay páginas definidas, la reconciliación basada en definiciones no produce IDs esperados.");
            }
            // No es necesario borrar el transitorio aquí si no se encontró, ya que get_transient devuelve false.
            // Si se quisiera asegurar que no se use un valor antiguo en una ejecución posterior, se podría borrar.
        } else {
            // El transitorio existía, se borra para asegurar que en la próxima ejecución se recalcule si es necesario.
            delete_transient('pagemanager_ids_procesados');
            if (!is_array($idsDefinidosActuales)) {
                // Si el transitorio contenía algo inesperado, se inicializa como array vacío.
                $idsDefinidosActuales = [];
            }
        }

        // Obtener todas las páginas marcadas como gestionadas en la base de datos.
        $argsTodasGestionadas = [
            'post_type' => 'page',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'meta_key' => self::CLAVE_META_GESTION,
            'meta_value' => true,
            'fields' => 'ids',
        ];
        $idsPaginasPotencialmenteGestionadas = get_posts($argsTodasGestionadas);

        if (empty($idsPaginasPotencialmenteGestionadas)) {
            return; // No hay páginas gestionadas en la BD, nada que reconciliar.
        }

        // Compara todas las páginas gestionadas en la BD con las que están actualmente definidas (o reconstruidas).
        // Las que están en la BD pero no definidas actualmente, son candidatas a eliminación. - Jules
        $idsPaginasParaEliminar = array_diff($idsPaginasPotencialmenteGestionadas, $idsDefinidosActuales);

        if (empty($idsPaginasParaEliminar)) {
            return; // No hay páginas para eliminar.
        }

        $forzarEliminacionDirecta = true; // true para enviar a la papelera, false para eliminar permanentemente.
        $idPaginaFrontalActual = (int) get_option('page_on_front');
        $idPaginaEntradasActual = (int) get_option('page_for_posts');

        foreach ($idsPaginasParaEliminar as $idPagina) {
            // Evitar eliminar la página configurada como página de inicio o página de entradas.
            if ($idPagina === $idPaginaFrontalActual && $idPaginaFrontalActual > 0) {
                GloryLogger::warning("PageManager reconciliarPaginasGestionadas: OMITIENDO eliminación de página ID {$idPagina} porque es la página frontal actual.");
                continue;
            }
            if ($idPagina === $idPaginaEntradasActual && $idPaginaEntradasActual > 0) {
                GloryLogger::warning("PageManager reconciliarPaginasGestionadas: OMITIENDO eliminación de página ID {$idPagina} porque es la página de entradas actual.");
                continue;
            }

            $paginaEliminada = wp_delete_post($idPagina, $forzarEliminacionDirecta);

            if (!$paginaEliminada) {
                GloryLogger::error("PageManager reconciliarPaginasGestionadas: FALLÓ al eliminar página gestionada con ID: {$idPagina}. Podría haber sido eliminada ya o ocurrió otro problema.");
            }
        }
    }

    /**
     * Configura la página de inicio de WordPress si se ha definido una página con slug 'home'.
     * También maneja la desconfiguración si la página de inicio ya no es válida o no está definida.
     *
     * @param int|null $idPaginaInicio El ID de la página que se usará como página de inicio. Null para desconfigurar.
     */
    private static function actualizarOpcionesPaginaFrontal(?int $idPaginaInicio): void {
        $opcionMostrarEnFrontActual = get_option('show_on_front');
        $opcionPaginaEnFrontActual = (int) get_option('page_on_front');
        $opcionPaginaParaEntradasActual = (int) get_option('page_for_posts');

        if ($idPaginaInicio && $idPaginaInicio > 0) {
            $objetoPaginaInicio = get_post($idPaginaInicio);
            // Verifica que el ID proporcionado sea una página válida y publicada.
            if (!$objetoPaginaInicio || $objetoPaginaInicio->post_type !== 'page' || $objetoPaginaInicio->post_status !== 'publish') {
                GloryLogger::error("PageManager actualizarOpcionesPaginaFrontal: ID de página de inicio {$idPaginaInicio} es inválido, no es una página o no está publicada. No se puede establecer como página frontal.");
                // Si la página frontal actual es la que ahora es inválida, revierte a mostrar 'posts'.
                if ($opcionMostrarEnFrontActual === 'page' && $opcionPaginaEnFrontActual === $idPaginaInicio) {
                    GloryLogger::warning("PageManager actualizarOpcionesPaginaFrontal: Revirtiendo a 'entradas' porque el ID de la página frontal actual {$idPaginaInicio} es inválido.");
                    update_option('show_on_front', 'posts');
                    update_option('page_on_front', 0);
                }
                return;
            }

            // Configura WordPress para mostrar una página estática en el frontal.
            if ($opcionMostrarEnFrontActual !== 'page') {
                update_option('show_on_front', 'page');
            }
            // Establece la página de inicio.
            if ($opcionPaginaEnFrontActual !== $idPaginaInicio) {
                update_option('page_on_front', $idPaginaInicio);
                // Si la página de inicio era también la página de entradas, desasigna la página de entradas.
                if ($opcionPaginaParaEntradasActual === $idPaginaInicio) {
                    update_option('page_for_posts', 0);
                }
            }
        } else {
            // Si no se proporciona un ID de página de inicio (o es 0/null),
            // y WordPress está configurado para mostrar una página estática, revierte a mostrar 'posts'.
            if ($opcionMostrarEnFrontActual === 'page') {
                update_option('show_on_front', 'posts');
                update_option('page_on_front', 0);
            }
        }
    }
}