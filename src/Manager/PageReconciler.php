<?php

namespace Glory\Manager;

use Glory\Core\GloryLogger;

/*
 * Responsabilidad: reconciliar y limpiar páginas gestionadas obsoletas.
 * Elimina páginas de la BD que ya no están definidas en código,
 * y gestiona las opciones de página frontal (front page) de WordPress.
 * Extraído de PageProcessor para cumplir SRP (max 300 líneas).
 */
class PageReconciler
{
    private const CLAVE_META_GESTION = '_page_manager_managed';

    public static function reconciliarPaginasGestionadas(): void
    {
        $idsDefinidosActuales = self::obtenerIdsDefinidosActuales();

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
            self::eliminarPaginasObsoletas($idsPaginasParaEliminar);
        }
    }

    private static function obtenerIdsDefinidosActuales(): array
    {
        $idsDefinidos = get_transient('pagemanager_ids_procesados');

        if ($idsDefinidos === false) {
            $idsDefinidos = [];
            $paginasDefinidas = PageDefinition::getPaginasDefinidas();
            if (!empty($paginasDefinidas)) {
                $slugsDefinidos = array_keys($paginasDefinidas);
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
            }
        } else {
            delete_transient('pagemanager_ids_procesados');
            if (!is_array($idsDefinidos)) {
                $idsDefinidos = [];
            }
        }

        return $idsDefinidos;
    }

    private static function eliminarPaginasObsoletas(array $idsPaginasParaEliminar): void
    {
        $idPaginaFrontalActual = (int) get_option('page_on_front');
        $idPaginaEntradasActual = (int) get_option('page_for_posts');

        foreach ($idsPaginasParaEliminar as $idPagina) {
            if ($idPagina === $idPaginaFrontalActual && $idPaginaFrontalActual > 0) {
                GloryLogger::warning("PageProcessor: Omitiendo eliminación de página ID {$idPagina} (página frontal).");
                continue;
            }
            if ($idPagina === $idPaginaEntradasActual && $idPaginaEntradasActual > 0) {
                GloryLogger::warning("PageProcessor: Omitiendo eliminación de página ID {$idPagina} (página de entradas).");
                continue;
            }

            $paginaEliminada = wp_delete_post($idPagina, true);
            if (!$paginaEliminada) {
                GloryLogger::error("PageProcessor: Falló al eliminar página obsoleta ID: {$idPagina}.");
            } else {
                GloryLogger::info("PageProcessor: Página obsoleta ID: {$idPagina} eliminada.");
            }
        }
    }

    public static function actualizarOpcionesPaginaFrontal(?int $idPaginaInicio): void
    {
        $showOnFront = get_option('show_on_front');
        $pageOnFront = (int) get_option('page_on_front');
        $pageForPosts = (int) get_option('page_for_posts');

        if ($idPaginaInicio && $idPaginaInicio > 0) {
            $objetoPagina = get_post($idPaginaInicio);
            if (!$objetoPagina || $objetoPagina->post_type !== 'page' || $objetoPagina->post_status !== 'publish') {
                GloryLogger::error("PageProcessor: ID de página de inicio {$idPaginaInicio} es inválido.");
                if ($showOnFront === 'page' && $pageOnFront === $idPaginaInicio) {
                    update_option('show_on_front', 'posts');
                    update_option('page_on_front', 0);
                }
                return;
            }
            if ($showOnFront !== 'page') {
                update_option('show_on_front', 'page');
            }
            if ($pageOnFront !== $idPaginaInicio) {
                update_option('page_on_front', $idPaginaInicio);
                if ($pageForPosts === $idPaginaInicio) {
                    update_option('page_for_posts', 0);
                }
            }
        } else {
            if ($showOnFront === 'page') {
                update_option('show_on_front', 'posts');
                update_option('page_on_front', 0);
            }
        }
    }
}
