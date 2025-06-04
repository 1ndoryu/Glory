<?php

namespace Glory\Class;

use Glory\Class\GloryLogger;

class PageManager {
    private const claveMetaGestion = '_page_manager_managed';
    private static $paginas = [];

    public static function define(string $slug, ?string $titulo = null, ?string $plantilla = null) {
        if (empty($slug) || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            GloryLogger::error("PageManager: Invalid slug '{$slug}'. Slugs must be lowercase alphanumeric with hyphens.");
            return;
        }

        if (is_null($titulo)) {
            $titulo = ucwords(str_replace(['-', '_'], ' ', $slug));
        }
        if (is_null($plantilla)) {
            $nombrePlantilla = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $slug)));
            $plantilla = "Template{$nombrePlantilla}.php";
        }
        self::$paginas[$slug] = [
            'titulo' => $titulo,
            'plantilla' => $plantilla,
            'slug' => $slug
        ];
    }

    public static function register() {
        add_action('init', [self::class, 'procesarPaginas'], 10);
        add_action('init', [self::class, 'reconciliarPaginasGestionadas'], 100);
    }

    // ANTERIOR: processPages
    public static function procesarPaginas() {
        $idPaginaInicioProcesada = null;
        $idsPaginasProcesadas = [];

        if (!empty(self::$paginas)) {
            foreach (self::$paginas as $slug => $defPagina) {
                $slugPagina = $defPagina['slug'];
                $tituloPagina = $defPagina['titulo'];
                $plantillaPagina = $defPagina['plantilla'];
                $idPaginaActual = null;

                $paginaExistente = get_page_by_path($slugPagina, OBJECT, 'page');

                if (!$paginaExistente) {
                    $datosPagina = [
                        'post_title' => $tituloPagina,
                        'post_content' => "<!-- Page managed by Glory PageManager. Slug: {$slugPagina} -->",
                        'post_status' => 'publish',
                        'post_type' => 'page',
                        'post_name' => $slugPagina,
                        'page_template' => $plantillaPagina ?: '',
                    ];
                    $idInsertado = wp_insert_post($datosPagina, true);

                    if (!is_wp_error($idInsertado) && $idInsertado > 0) {
                        $idPaginaActual = $idInsertado;
                        update_post_meta($idPaginaActual, self::claveMetaGestion, true);
                    } else {
                        $mensajeError = is_wp_error($idInsertado) ? $idInsertado->get_error_message() : 'Unknown error (ID 0)';
                        GloryLogger::error("PageManager: FAILED to create page '{$slugPagina}': " . $mensajeError);
                        continue;
                    }
                } else {
                    $idPaginaActual = $paginaExistente->ID;
                    update_post_meta($idPaginaActual, self::claveMetaGestion, true);
                    $plantillaActual = get_post_meta($idPaginaActual, '_wp_page_template', true);
                    $nuevoValorPlantilla = $plantillaPagina ?: '';

                    if ($plantillaActual !== $nuevoValorPlantilla) {
                        update_post_meta($idPaginaActual, '_wp_page_template', $nuevoValorPlantilla);
                    }
                }

                if ($idPaginaActual) {
                    $idsPaginasProcesadas[] = $idPaginaActual;
                    if ($slugPagina === 'home') {
                        $idPaginaInicioProcesada = $idPaginaActual;
                    }
                }
            }
        } else {
            GloryLogger::error("PageManager procesarPaginas: No pages defined in self::\$paginas.");
            $idPaginaFrontalActual = (int) get_option('page_on_front');
            if ($idPaginaFrontalActual > 0 && get_option('show_on_front') === 'page') {
                if (get_post_meta($idPaginaFrontalActual, self::claveMetaGestion, true)) {
                    GloryLogger::error("Current front page (ID: {$idPaginaFrontalActual}) is managed but no pages defined. Setting front page to null.");
                    $idPaginaInicioProcesada = null;
                }
            }
        }

        self::actualizarOpcionesPaginaFrontal($idPaginaInicioProcesada);
        set_transient('pagemanager_processed_ids', $idsPaginasProcesadas, 15 * MINUTE_IN_SECONDS);
    }

    // ANTERIOR: reconcileManagedPages
    public static function reconciliarPaginasGestionadas() {
        $idsDefinidosActuales = get_transient('pagemanager_processed_ids');

        if ($idsDefinidosActuales === false) {
            $idsDefinidosActuales = [];
            if (!empty(self::$paginas)) {
                $slugsDefinidos = array_keys(self::$paginas);
                $args = [
                    'post_type' => 'page',
                    'post_status' => 'any',
                    'posts_per_page' => -1,
                    'meta_key' => self::claveMetaGestion,
                    'meta_value' => true,
                    'fields' => 'ids',
                    // 'post_name__in' => $slugsDefinidos, // Kept commented as original, WP specific key
                ];
                $idsReconstruidos = get_posts($args);

                if (!empty($idsReconstruidos)) {
                    foreach ($idsReconstruidos as $idPagina) {
                        $slugPagina = get_post_field('post_name', $idPagina);
                        if (in_array($slugPagina, $slugsDefinidos, true)) {
                            $idsDefinidosActuales[] = $idPagina;
                        }
                    }
                }
            } else {
                GloryLogger::error("PageManager reconcileManagedPages: No pages defined, reconciliation based on definitions yields no expected IDs.");
            }
            delete_transient('pagemanager_processed_ids');
        } else {
            delete_transient('pagemanager_processed_ids');
            if (!is_array($idsDefinidosActuales)) {
                $idsDefinidosActuales = [];
            }
        }

        $argsTodasGestionadas = [
            'post_type' => 'page',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'meta_key' => self::claveMetaGestion,
            'meta_value' => true,
            'fields' => 'ids',
        ];
        $idsPagPotenGestionadas = get_posts($argsTodasGestionadas);

        if (empty($idsPagPotenGestionadas)) {
            return;
        }

        $idsPaginasParaEliminar = array_diff($idsPagPotenGestionadas, $idsDefinidosActuales);

        if (empty($idsPaginasParaEliminar)) {
            return;
        }

        $forzarEliminacionDirecta = true;
        $idPaginaFrontalActual = (int) get_option('page_on_front');
        $idPaginaEntradasActual = (int) get_option('page_for_posts');

        foreach ($idsPaginasParaEliminar as $idPagina) {
            if ($idPagina === $idPaginaFrontalActual && $idPaginaFrontalActual > 0) {
                continue;
            }
            if ($idPagina === $idPaginaEntradasActual && $idPaginaEntradasActual > 0) {
                error_log("PageManager reconcileManagedPages: SKIPPING deletion of page ID {$idPagina} because it is currently set as the posts page.");
                continue;
            }

            $paginaEliminada = wp_delete_post($idPagina, $forzarEliminacionDirecta);

            if (!$paginaEliminada) {
                GloryLogger::error("PageManager reconcileManagedPages: FAILED to delete managed page with ID: {$idPagina}. It might already be deleted or another issue occurred.");
            }
        }
    }

    // ANTERIOR: updateFrontPageOptions
    private static function actualizarOpcionesPaginaFrontal(?int $idPaginaInicio): void {
        $opcionMostrarEnFrontActual = get_option('show_on_front');
        $opcionPaginaEnFrontActual = (int) get_option('page_on_front');
        $opcionPaginaParaEntradasActual = (int) get_option('page_for_posts');

        if ($idPaginaInicio && $idPaginaInicio > 0) {
            $objetoPaginaInicio = get_post($idPaginaInicio);
            if (!$objetoPaginaInicio || $objetoPaginaInicio->post_type !== 'page' || $objetoPaginaInicio->post_status !== 'publish') {
                GloryLogger::error("PageManager updateFrontPageOptions: Provided home page ID {$idPaginaInicio} is invalid, not a page, or not published. Cannot set as front page.");
                if ($opcionMostrarEnFrontActual === 'page' && $opcionPaginaEnFrontActual === $idPaginaInicio) {
                    error_log("PageManager updateFrontPageOptions: Reverting to 'posts' because current front page ID {$idPaginaInicio} is invalid.");
                    update_option('show_on_front', 'posts');
                    update_option('page_on_front', 0);
                }
                return;
            }

            if ($opcionMostrarEnFrontActual !== 'page') {
                update_option('show_on_front', 'page');
            }
            if ($opcionPaginaEnFrontActual !== $idPaginaInicio) {
                update_option('page_on_front', $idPaginaInicio);
                if ($opcionPaginaParaEntradasActual === $idPaginaInicio) {
                    update_option('page_for_posts', 0);
                }
            }
        } else {
            if ($opcionMostrarEnFrontActual === 'page') {
                update_option('show_on_front', 'posts');
                update_option('page_on_front', 0);
            }
        }
    }
}