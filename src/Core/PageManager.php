<?php

namespace Glory\Core;

use Glory\Core\GloryLogger;

/**
 * Gestiona la creación y reconciliación de páginas personalizadas en WordPress.
 * Permite definir páginas a través de código y asegura que existan en la base de datos,
 * actualizando sus plantillas y opcionalmente configurándolas como página de inicio.
 *
 * Nota: La gestión de páginas mediante código es una funcionalidad central.
 * Se podría considerar a futuro un mecanismo para permitir que plugins o temas
 * extiendan o modifiquen las páginas definidas por el framework de forma segura.
 * @author @wandorius
 * @tarea Jules: Revisión general de código y comentarios.
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
                $paginaExistente = get_page_by_path($defPagina['slug'], OBJECT, 'page');
                $idPaginaActual = null;

                if (!$paginaExistente) {
                    $idPaginaActual = self::_crearPaginaDefinida($defPagina);
                } else {
                    $idPaginaActual = self::_actualizarPaginaExistente($paginaExistente, $defPagina);
                }

                if ($idPaginaActual) {
                    $idsPaginasProcesadas[] = $idPaginaActual;
                    if ($defPagina['slug'] === 'home') {
                        $idPaginaInicioProcesada = $idPaginaActual;
                    }
                }
            }
        } else {
            // No hay páginas definidas en el array self::$paginasDefinidas.
            GloryLogger::warning("PageManager procesarPaginasDefinidas: No hay páginas definidas en self::\$paginasDefinidas.");
            $idPaginaFrontalActual = (int) get_option('page_on_front');
            if ($idPaginaFrontalActual > 0 && get_option('show_on_front') === 'page' && get_post_meta($idPaginaFrontalActual, self::CLAVE_META_GESTION, true)) {
                GloryLogger::error("La página frontal actual (ID: {$idPaginaFrontalActual}) es gestionada pero no hay páginas definidas en el código. Se anulará la página de inicio.");
                $idPaginaInicioProcesada = null;
            }
        }

        self::actualizarOpcionesPaginaFrontal($idPaginaInicioProcesada);
        set_transient('pagemanager_ids_procesados', $idsPaginasProcesadas, 15 * MINUTE_IN_SECONDS);
    }

    /**
     * Crea una nueva página en la base de datos según la definición proporcionada.
     *
     * @param array $defPagina Array con la definición de la página (slug, titulo, plantilla).
     * @return int|null El ID de la página creada, o null si falla la creación.
     */
    private static function _crearPaginaDefinida(array $defPagina): ?int
    {
        $datosPagina = [
            'post_title' => $defPagina['titulo'],
            'post_content' => "<!-- Contenido autogenerado por Glory PageManager. Slug de referencia: {$defPagina['slug']}. Modificar directamente desde el editor de WordPress. -->",
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_name' => $defPagina['slug'],
            'page_template' => $defPagina['plantilla'] ?: '',
        ];
        $idInsertado = wp_insert_post($datosPagina, true);

        if (!is_wp_error($idInsertado) && $idInsertado > 0) {
            update_post_meta($idInsertado, self::CLAVE_META_GESTION, true);
            return $idInsertado;
        } else {
            $mensajeError = is_wp_error($idInsertado) ? $idInsertado->get_error_message() : 'Error desconocido (ID 0)';
            GloryLogger::error("PageManager: FALLÓ al crear página '{$defPagina['slug']}': " . $mensajeError);
            return null;
        }
    }

    /**
     * Actualiza una página existente, asegurando que esté marcada como gestionada
     * y que su plantilla coincida con la definición.
     *
     * @param \WP_Post $paginaExistente Objeto de la página existente.
     * @param array $defPagina Array con la definición de la página.
     * @return int El ID de la página actualizada.
     */
    private static function _actualizarPaginaExistente(\WP_Post $paginaExistente, array $defPagina): int
    {
        $idPaginaActual = $paginaExistente->ID;
        update_post_meta($idPaginaActual, self::CLAVE_META_GESTION, true);

        $plantillaActual = get_post_meta($idPaginaActual, '_wp_page_template', true);
        $nuevaValorPlantilla = $defPagina['plantilla'] ?: '';

        if ($plantillaActual !== $nuevaValorPlantilla) {
            update_post_meta($idPaginaActual, '_wp_page_template', $nuevaValorPlantilla);
        }
        return $idPaginaActual;
    }

    /**
     * Reconcilia las páginas gestionadas en la base de datos con las definiciones actuales.
     * Elimina páginas que fueron gestionadas previamente pero ya no están definidas en el código.
     * Utiliza un transitorio para optimizar la obtención de IDs de páginas procesadas recientemente.
     */
    public static function reconciliarPaginasGestionadas(): void {
        $idsDefinidosActuales = self::_obtenerIdsDefinidosActualesDelTransitorioOComputar();

        $argsTodasGestionadas = [
            'post_type'      => 'page',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'meta_key'       => self::CLAVE_META_GESTION,
            'meta_value'     => true,
            'fields'         => 'ids',
        ];
        $idsPaginasEnBdGestionadas = get_posts($argsTodasGestionadas);

        if (empty($idsPaginasEnBdGestionadas)) {
            return;
        }

        $idsPaginasParaEliminar = array_diff($idsPaginasEnBdGestionadas, $idsDefinidosActuales);

        if (!empty($idsPaginasParaEliminar)) {
            self::_eliminarPaginasObsoletas($idsPaginasParaEliminar);
        }
    }

    /**
     * Obtiene los IDs de las páginas actualmente definidas, intentando primero desde un transitorio
     * y, si no está disponible, computándolos desde las definiciones en self::$paginasDefinidas.
     *
     * @return array Lista de IDs de páginas que están actualmente definidas y gestionadas.
     */
    private static function _obtenerIdsDefinidosActualesDelTransitorioOComputar(): array
    {
        $idsDefinidos = get_transient('pagemanager_ids_procesados');

        if ($idsDefinidos === false) {
            $idsDefinidos = [];
            if (!empty(self::$paginasDefinidas)) {
                $slugsDefinidos = array_keys(self::$paginasDefinidas);
                $args = [
                    'post_type'      => 'page',
                    'post_status'    => 'publish', // Solo nos interesan las publicadas para este cómputo
                    'posts_per_page' => -1,
                    'meta_key'       => self::CLAVE_META_GESTION,
                    'meta_value'     => true,
                    'fields'         => 'ids',
                    'post_name__in'  => $slugsDefinidos, // Optimización: solo buscar páginas cuyos slugs están definidos
                ];
                $idsReconstruidos = get_posts($args);
                if (!empty($idsReconstruidos)) {
                    // No es necesario filtrar por slug aquí porque post_name__in ya lo hizo.
                    $idsDefinidos = $idsReconstruidos;
                }
            } else {
                GloryLogger::info("PageManager: No hay páginas definidas en el código para la reconstrucción de IDs definidos.");
            }
        } else {
            delete_transient('pagemanager_ids_procesados'); // Limpiar transitorio después de usarlo.
            if (!is_array($idsDefinidos)) { // Asegurar que el transitorio era un array.
                $idsDefinidos = [];
            }
        }
        return $idsDefinidos;
    }

    /**
     * Elimina las páginas gestionadas que ya no están definidas en el código.
     *
     * @param array $idsPaginasParaEliminar Array de IDs de páginas a eliminar.
     */
    private static function _eliminarPaginasObsoletas(array $idsPaginasParaEliminar): void
    {
        $forzarEliminacionDirecta = true; // true para enviar a la papelera, false para eliminar permanentemente.
        $idPaginaFrontalActual = (int) get_option('page_on_front');
        $idPaginaEntradasActual = (int) get_option('page_for_posts');

        foreach ($idsPaginasParaEliminar as $idPagina) {
            if ($idPagina === $idPaginaFrontalActual && $idPaginaFrontalActual > 0) {
                GloryLogger::warning("PageManager: OMITIENDO eliminación de página ID {$idPagina} porque es la página frontal actual.");
                continue;
            }
            if ($idPagina === $idPaginaEntradasActual && $idPaginaEntradasActual > 0) {
                GloryLogger::warning("PageManager: OMITIENDO eliminación de página ID {$idPagina} porque es la página de entradas actual.");
                continue;
            }

            $paginaEliminada = wp_delete_post($idPagina, $forzarEliminacionDirecta);
            if (!$paginaEliminada) {
                GloryLogger::error("PageManager: FALLÓ al eliminar página gestionada obsoleta con ID: {$idPagina}.");
            } else {
                GloryLogger::info("PageManager: Página gestionada obsoleta con ID: {$idPagina} eliminada (o enviada a la papelera).");
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