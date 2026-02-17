<?php

namespace Glory\Manager;

use Glory\Core\GloryLogger;

/*
 * Responsabilidad: CRUD y reconciliación de páginas gestionadas en WordPress.
 * Crea, actualiza y elimina páginas en la BD, sincroniza contenido de handlers,
 * y gestiona la página frontal (front page).
 * Extraído de PageManager para cumplir SRP (max 300 líneas).
 */
class PageProcessor
{
    private const CLAVE_META_GESTION = '_page_manager_managed';
    private const CLAVE_META_HASH = '_glory_content_hash';
    private const CLAVE_MODO_CONTENIDO = '_glory_content_mode';

    /* ── CRUD: Procesamiento de páginas definidas ── */

    public static function procesarPaginasDefinidas(): void
    {
        $paginasDefinidas = PageDefinition::getPaginasDefinidas();
        $idPaginaInicioProcesada = null;
        $idsPaginasProcesadas = [];

        if (empty($paginasDefinidas)) {
            GloryLogger::info('PageManager: No hay páginas definidas para procesar.');
            return;
        }

        foreach ($paginasDefinidas as $slug => $defPagina) {
            $pathBusqueda = $defPagina['slug'];
            if (!empty($defPagina['parentSlug'])) {
                $pathBusqueda = $defPagina['parentSlug'] . '/' . $defPagina['slug'];
            }
            $paginaExistente = get_page_by_path($pathBusqueda, \OBJECT, 'page');
            $idPaginaActual = null;

            if (!$paginaExistente) {
                $idPaginaActual = self::crearPaginaDefinida($defPagina);
            } else {
                $idPaginaActual = self::actualizarPaginaExistente($paginaExistente, $defPagina);
            }

            if ($idPaginaActual) {
                $idsPaginasProcesadas[] = $idPaginaActual;
                if ($defPagina['slug'] === 'home') {
                    $idPaginaInicioProcesada = $idPaginaActual;
                }
                self::sincronizarEditorSiNoEditado($idPaginaActual, $defPagina);
            }
        }

        PageReconciler::actualizarOpcionesPaginaFrontal($idPaginaInicioProcesada);
        set_transient('pagemanager_ids_procesados', $idsPaginasProcesadas, 15 * \MINUTE_IN_SECONDS);
    }

    public static function crearPaginaDefinida(array $defPagina): ?int
    {
        $modoPorDefecto = PageDefinition::getDefaultContentMode();
        $contenidoInicial = '';
        $hashContenido = '';
        if (!empty($defPagina['funcion']) && $modoPorDefecto === 'editor') {
            $contenidoInicial = self::renderHandlerParaCopiar($defPagina['funcion']);
            $hashContenido = $contenidoInicial !== '' ? self::hashContenido($contenidoInicial) : '';
        }

        $parentId = 0;
        if (!empty($defPagina['parentSlug'])) {
            $paginaPadre = get_page_by_path($defPagina['parentSlug'], \OBJECT, 'page');
            if ($paginaPadre) {
                $parentId = $paginaPadre->ID;
            } else {
                /* Safety net: auto-crear pagina padre stub si no existe en WP */
                $parentId = self::asegurarPaginaPadre($defPagina['parentSlug']);
                if (!$parentId) {
                    GloryLogger::warning("PageManager: Padre '{$defPagina['parentSlug']}' no encontrado ni creado para '{$defPagina['slug']}'.");
                }
            }
        }

        $datosPagina = [
            'post_title'    => $defPagina['titulo'],
            'post_content'  => $contenidoInicial,
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_name'     => $defPagina['slug'],
            'post_parent'   => $parentId,
            'page_template' => $defPagina['plantilla'] ?: '',
        ];
        $idInsertado = wp_insert_post($datosPagina, true);

        if (!is_wp_error($idInsertado) && $idInsertado > 0) {
            update_post_meta($idInsertado, self::CLAVE_META_GESTION, true);
            if (!metadata_exists('post', $idInsertado, self::CLAVE_MODO_CONTENIDO)) {
                update_post_meta($idInsertado, self::CLAVE_MODO_CONTENIDO, 'code');
            }
            if ($modoPorDefecto === 'editor') {
                update_post_meta($idInsertado, self::CLAVE_MODO_CONTENIDO, 'editor');
                if ($hashContenido !== '') {
                    update_post_meta($idInsertado, self::CLAVE_META_HASH, $hashContenido);
                }
            }
            PageSeoDefaults::aplicarSeoPorDefecto($idInsertado, $defPagina['slug'] ?? '');
            return $idInsertado;
        }

        $mensajeError = is_wp_error($idInsertado) ? $idInsertado->get_error_message() : 'Error desconocido (ID 0)';
        GloryLogger::error("PageManager: FALLÓ al crear '{$defPagina['slug']}': " . $mensajeError);
        return null;
    }

    public static function actualizarPaginaExistente(\WP_Post $paginaExistente, array $defPagina): int
    {
        $modoPorDefecto = PageDefinition::getDefaultContentMode();
        $idPaginaActual = $paginaExistente->ID;
        update_post_meta($idPaginaActual, self::CLAVE_META_GESTION, true);

        $plantillaActual = get_post_meta($idPaginaActual, '_wp_page_template', true);
        $nuevaValorPlantilla = $defPagina['plantilla'] ?: '';
        if ($plantillaActual !== $nuevaValorPlantilla) {
            update_post_meta($idPaginaActual, '_wp_page_template', $nuevaValorPlantilla);
        }

        $parentIdEsperado = 0;
        if (!empty($defPagina['parentSlug'])) {
            $paginaPadre = get_page_by_path($defPagina['parentSlug'], \OBJECT, 'page');
            if ($paginaPadre) {
                $parentIdEsperado = $paginaPadre->ID;
            } else {
                /* Safety net: auto-crear padre si fue eliminado manualmente */
                $parentIdEsperado = self::asegurarPaginaPadre($defPagina['parentSlug']);
            }
        }
        if ((int) $paginaExistente->post_parent !== $parentIdEsperado) {
            wp_update_post(['ID' => $idPaginaActual, 'post_parent' => $parentIdEsperado]);
        }

        if (!metadata_exists('post', $idPaginaActual, self::CLAVE_MODO_CONTENIDO)) {
            $modo = $modoPorDefecto;
            update_post_meta($idPaginaActual, self::CLAVE_MODO_CONTENIDO, $modo);
            if ($modo === 'editor' && empty($paginaExistente->post_content) && !empty($defPagina['funcion'])) {
                $contenido = self::renderHandlerParaCopiar($defPagina['funcion']);
                if ($contenido !== '') {
                    wp_update_post(['ID' => $idPaginaActual, 'post_content' => $contenido]);
                    update_post_meta($idPaginaActual, self::CLAVE_META_HASH, self::hashContenido($contenido));
                }
            }
        } else {
            $modoActual = get_post_meta($idPaginaActual, self::CLAVE_MODO_CONTENIDO, true);
            if ($modoPorDefecto === 'editor' && $modoActual !== 'editor' && !empty($defPagina['funcion'])) {
                $gbnConfig = get_post_meta($idPaginaActual, 'gbn_config', true);
                $gbnStyles = get_post_meta($idPaginaActual, 'gbn_styles', true);
                $hasGbnData = (is_string($gbnConfig) && $gbnConfig !== '')
                    || (is_string($gbnStyles) && $gbnStyles !== '');

                if (!$hasGbnData) {
                    $contenidoActual = (string) get_post_field('post_content', $idPaginaActual);
                    $hashGuardado = (string) get_post_meta($idPaginaActual, self::CLAVE_META_HASH, true);
                    $hashActual = $contenidoActual !== '' ? self::hashContenido($contenidoActual) : '';
                    $noEditado = ($hashGuardado !== '' && $hashGuardado === $hashActual) || ($contenidoActual === '');
                    if ($noEditado) {
                        update_post_meta($idPaginaActual, self::CLAVE_MODO_CONTENIDO, 'editor');
                        if ($contenidoActual === '') {
                            $contenido = self::renderHandlerParaCopiar($defPagina['funcion']);
                            if ($contenido !== '') {
                                wp_update_post(['ID' => $idPaginaActual, 'post_content' => $contenido]);
                                update_post_meta($idPaginaActual, self::CLAVE_META_HASH, self::hashContenido($contenido));
                            }
                        } else {
                            update_post_meta($idPaginaActual, self::CLAVE_META_HASH, $hashActual);
                        }
                    }
                }
            }
        }

        PageSeoDefaults::aplicarSeoPorDefecto($idPaginaActual, $defPagina['slug'] ?? '');
        return $idPaginaActual;
    }

    /* ── Helpers de contenido ── */

    public static function sincronizarEditorSiNoEditado(int $postId, array $defPagina): void
    {
        if (empty($defPagina['funcion'])) {
            return;
        }
        $modo = get_post_meta($postId, self::CLAVE_MODO_CONTENIDO, true);
        if ($modo !== 'editor') {
            return;
        }

        $gbnConfig = get_post_meta($postId, 'gbn_config', true);
        $gbnStyles = get_post_meta($postId, 'gbn_styles', true);
        $hasGbnData = (is_string($gbnConfig) && $gbnConfig !== '')
            || (is_string($gbnStyles) && $gbnStyles !== '');
        if ($hasGbnData) {
            return;
        }

        $contenidoActual = get_post_field('post_content', $postId);
        $hashGuardado = (string) get_post_meta($postId, self::CLAVE_META_HASH, true);
        $hashActual = $contenidoActual !== '' ? self::hashContenido($contenidoActual) : '';

        if ($hashGuardado !== '' && $hashGuardado === $hashActual) {
            $contenidoNuevo = self::renderHandlerParaCopiar($defPagina['funcion']);
            if ($contenidoNuevo !== '' && $contenidoNuevo !== $contenidoActual) {
                wp_update_post(['ID' => $postId, 'post_content' => $contenidoNuevo]);
                update_post_meta($postId, self::CLAVE_META_HASH, self::hashContenido($contenidoNuevo));
            }
        }
    }

    public static function hashContenido(string $content): string
    {
        $normalized = preg_replace('/\s+/', ' ', trim($content));
        return hash('sha256', (string) $normalized);
    }

    public static function renderHandlerParaCopiar(string $handler): string
    {
        if (!is_callable($handler)) {
            return '';
        }
        $prev = $GLOBALS['gloryCopyContext'] ?? null;
        $GLOBALS['gloryCopyContext'] = true;
        ob_start();
        try {
            call_user_func($handler);
        } catch (\Throwable $e) {
            ob_end_clean();
            $GLOBALS['gloryCopyContext'] = $prev;
            return '';
        }
        $html = (string) ob_get_clean();
        $GLOBALS['gloryCopyContext'] = $prev;
        return self::limpiarHtmlCopiado($html);
    }

    private static function limpiarHtmlCopiado(string $html): string
    {
        $salida = trim($html);
        $patronFinalVacio = '/(?:<p[^>]*>\s*(?:<br\s*\/?>|&nbsp;|\s)*<\/p>\s*|<br\s*\/?>\s*)$/i';
        while (preg_match($patronFinalVacio, $salida) === 1) {
            $salida = (string) preg_replace($patronFinalVacio, '', $salida);
        }
        return $salida;
    }

    /**
     * Crea recursivamente paginas padre stub si no existen en WP.
     * Soporta multiples niveles: 'admin' o 'admin/sub'.
     * Retorna el ID de la pagina padre final, o 0 si falla.
     */
    private static function asegurarPaginaPadre(string $parentSlug): int
    {
        $paginaPadre = get_page_by_path($parentSlug, \OBJECT, 'page');
        if ($paginaPadre) {
            return $paginaPadre->ID;
        }

        $grandParentId = 0;
        $slugFinal = $parentSlug;

        if (str_contains($parentSlug, '/')) {
            $parts = explode('/', $parentSlug);
            $slugFinal = array_pop($parts);
            $grandParentSlug = implode('/', $parts);
            $grandParentId = self::asegurarPaginaPadre($grandParentSlug);
        }

        $idInsertado = wp_insert_post([
            'post_title'   => ucwords(str_replace(['-', '_'], ' ', $slugFinal)),
            'post_content' => '',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_name'    => $slugFinal,
            'post_parent'  => $grandParentId,
        ], true);

        if (!is_wp_error($idInsertado) && $idInsertado > 0) {
            update_post_meta($idInsertado, self::CLAVE_META_GESTION, true);
            GloryLogger::info("PageProcessor: Pagina padre stub '{$parentSlug}' auto-creada (ID: {$idInsertado}).");
            return $idInsertado;
        }

        GloryLogger::error("PageProcessor: Fallo al auto-crear pagina padre '{$parentSlug}'.");
        return 0;
    }
}
