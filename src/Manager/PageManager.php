<?php

namespace Glory\Manager;

use Glory\Core\GloryLogger;
use Glory\Utility\UserUtility;

class PageManager
{
    private const CLAVE_META_GESTION = '_page_manager_managed';
    private static array $paginasDefinidas = [];
    private static ?string $funcionParaRenderizar = null;

    /**
     * Define una página gestionada.
     *
     * @param string $slug El slug de la página.
     * @param string|null $handler El título, nombre de la función de renderizado, o nombre del archivo de plantilla.
     * @param string|null $plantilla Opcional. El nombre del archivo de plantilla si se provee un título en $handler.
     */
    public static function define(string $slug, ?string $handler = null, ?string $plantilla = null, array $roles = []): void
    {
        if (empty($slug) || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            GloryLogger::error("PageManager: Slug inválido '{$slug}'.");
            return;
        }

        $titulo = ucwords(str_replace(['-', '_'], ' ', $slug));
        $nombreFuncion = null;
        $nombrePlantilla = "Template" . str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $slug))) . ".php";

        if ($handler) {
            if (str_ends_with($handler, '.php')) {
                $nombrePlantilla = $handler;
            } elseif ($plantilla) {
                $titulo = $handler;
                $nombrePlantilla = $plantilla;
            } else {
                $titulo = ucwords(str_replace(['-', '_'], ' ', $slug));
                $nombrePlantilla = 'TemplateGlory.php';
                $nombreFuncion = $handler;
            }
        }

        self::$paginasDefinidas[$slug] = [
            'titulo'    => $titulo,
            'plantilla' => $nombrePlantilla,
            'funcion'   => $nombreFuncion,
            'slug'      => $slug,
            'roles'     => $roles,
        ];
    }

    public static function register(): void
    {
        add_filter('template_include', [self::class, 'interceptarPlantilla'], 99);
    }

    public static function interceptarPlantilla(string $plantilla): string
    {
        if (!is_page() || is_admin()) {
            return $plantilla;
        }

        $slug = get_post_field('post_name', get_queried_object_id());

        if (isset(self::$paginasDefinidas[$slug])) {
            $defPagina = self::$paginasDefinidas[$slug];
            $rolesRequeridos = $defPagina['roles'] ?? [];

            if (!empty($rolesRequeridos)) {
                if (!is_user_logged_in()) {
                    $login_url = wp_login_url(get_permalink());
                    wp_redirect($login_url);
                    exit;
                } elseif (!UserUtility::tieneRoles($rolesRequeridos)) {
                    wp_die('No tienes permiso para ver esta página.', 'Acceso Denegado', ['response' => 403]);
                }
            }

            if (!empty($defPagina['funcion'])) {
                self::$funcionParaRenderizar = $defPagina['funcion'];
                $plantillaCentral = get_template_directory() . '/TemplateGlory.php';
                if (file_exists($plantillaCentral)) {
                    return $plantillaCentral;
                }
                GloryLogger::error("PageManager: No se encontró la plantilla central 'TemplateGlory.php'.");
            }
        }
        return $plantilla;
    }

    public static function getFuncionParaRenderizar(): ?string
    {
        return self::$funcionParaRenderizar;
    }

    public static function procesarPaginasDefinidas(): void
    {
        $idPaginaInicioProcesada = null;
        $idsPaginasProcesadas = [];

        if (empty(self::$paginasDefinidas)) {
            GloryLogger::info('PageManager: No hay páginas definidas para procesar.');
            return;
        }

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

        self::actualizarOpcionesPaginaFrontal($idPaginaInicioProcesada);
        set_transient('pagemanager_ids_procesados', $idsPaginasProcesadas, 15 * MINUTE_IN_SECONDS);
    }

    private static function _crearPaginaDefinida(array $defPagina): ?int
    {
        $datosPagina = [
            'post_title'   => $defPagina['titulo'],
            'post_content' => "",
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_name'    => $defPagina['slug'],
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

    public static function reconciliarPaginasGestionadas(): void
    {
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

    private static function _obtenerIdsDefinidosActualesDelTransitorioOComputar(): array
    {
        $idsDefinidos = get_transient('pagemanager_ids_procesados');

        if ($idsDefinidos === false) {
            $idsDefinidos = [];
            if (!empty(self::$paginasDefinidas)) {
                $slugsDefinidos = array_keys(self::$paginasDefinidas);
                $args = [
                    'post_type'      => 'page',
                    'post_status'    => 'publish',
                    'posts_per_page' => -1,
                    'meta_key'       => self::CLAVE_META_GESTION,
                    'meta_value'     => true,
                    'fields'         => 'ids',
                    'post_name__in'  => $slugsDefinidos,
                ];
                $idsReconstruidos = get_posts($args);
                if (!empty($idsReconstruidos)) {
                    $idsDefinidos = $idsReconstruidos;
                }
            } else {
                GloryLogger::info("PageManager: No hay páginas definidas en el código para la reconstrucción de IDs definidos.");
            }
        } else {
            delete_transient('pagemanager_ids_procesados');
            if (!is_array($idsDefinidos)) {
                $idsDefinidos = [];
            }
        }
        return $idsDefinidos;
    }

    private static function _eliminarPaginasObsoletas(array $idsPaginasParaEliminar): void
    {
        $forzarEliminacionDirecta = true;
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

    private static function actualizarOpcionesPaginaFrontal(?int $idPaginaInicio): void
    {
        $opcionMostrarEnFrontActual = get_option('show_on_front');
        $opcionPaginaEnFrontActual = (int) get_option('page_on_front');
        $opcionPaginaParaEntradasActual = (int) get_option('page_for_posts');

        if ($idPaginaInicio && $idPaginaInicio > 0) {
            $objetoPaginaInicio = get_post($idPaginaInicio);
            if (!$objetoPaginaInicio || $objetoPaginaInicio->post_type !== 'page' || $objetoPaginaInicio->post_status !== 'publish') {
                GloryLogger::error("PageManager actualizarOpcionesPaginaFrontal: ID de página de inicio {$idPaginaInicio} es inválido, no es una página o no está publicada. No se puede establecer como página frontal.");
                if ($opcionMostrarEnFrontActual === 'page' && $opcionPaginaEnFrontActual === $idPaginaInicio) {
                    GloryLogger::warning("PageManager actualizarOpcionesPaginaFrontal: Revirtiendo a 'entradas' porque el ID de la página frontal actual {$idPaginaInicio} es inválido.");
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
