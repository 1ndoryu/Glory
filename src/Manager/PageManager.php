<?php

namespace Glory\Manager;

use Glory\Core\GloryLogger;
use Glory\Utility\UserUtility;

class PageManager
{
    private const CLAVE_META_GESTION = '_page_manager_managed';
    private const CLAVE_META_HASH = '_glory_content_hash';
    private const CLAVE_MODO_CONTENIDO = '_glory_content_mode'; // 'code' | 'editor'
    private static array $paginasDefinidas = [];
    private static ?string $funcionParaRenderizar = null;
    private static string $modoPorDefecto = 'code'; // 'code' | 'editor'
    private static array $defaultSeoMap = [];

    // Paginas React Fullpage registradas dinamicamente desde App/Config/
    // Glory framework es agnostico - NO contiene slugs hardcodeados de proyectos
    private static array $paginasReactFullpage = [];

    /**
     * Registra slugs como paginas React Fullpage.
     * Las paginas React Fullpage renderizan su propio layout (sin header/footer de WP).
     * 
     * Uso desde App/Config/pages.php:
     *   PageManager::registerReactFullPages(['home', 'servicios', 'blog']);
     * 
     * @param array $slugs Array de slugs a registrar como React Fullpage
     */
    public static function registerReactFullPages(array $slugs): void
    {
        self::$paginasReactFullpage = array_unique(
            array_merge(self::$paginasReactFullpage, $slugs)
        );
    }

    /**
     * Verifica si un slug corresponde a una página React Fullpage.
     */
    public static function isReactFullPage(string $slug): bool
    {
        return in_array($slug, self::$paginasReactFullpage, true);
    }

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
        add_filter('the_content', [self::class, 'disableAutoPForManagedPages'], 1);
    }

    public static function disableAutoPForManagedPages($content)
    {
        global $post;
        if ($post && get_post_meta($post->ID, self::CLAVE_META_GESTION, true)) {
            remove_filter('the_content', 'wpautop');
            remove_filter('the_content', 'wptexturize');
        }
        return $content;
    }

    /**
     * Configura el modo de contenido por defecto para páginas gestionadas.
     */
    public static function setDefaultContentMode(string $mode): void
    {
        if (in_array($mode, ['code', 'editor'], true)) {
            self::$modoPorDefecto = $mode;
        }
    }

    public static function getDefaultContentMode(): string
    {
        return self::$modoPorDefecto;
    }

    /**
     * Define valores SEO por defecto por slug: ['title' => '', 'desc' => '', 'canonical' => '']
     */
    public static function setDefaultSeoMap(array $map): void
    {
        self::$defaultSeoMap = $map;
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
                // Sincronización inteligente: si está en modo editor pero no ha sido editado manualmente
                self::_sincronizarEditorSiNoEditado($idPaginaActual, $defPagina);
            }
        }

        self::actualizarOpcionesPaginaFrontal($idPaginaInicioProcesada);
        set_transient('pagemanager_ids_procesados', $idsPaginasProcesadas, 15 * MINUTE_IN_SECONDS);
    }

    private static function _crearPaginaDefinida(array $defPagina): ?int
    {
        $contenidoInicial = '';
        $hashContenido = '';
        if (!empty($defPagina['funcion']) && self::$modoPorDefecto === 'editor') {
            $contenidoInicial = self::renderHandlerParaCopiar($defPagina['funcion']);
            $hashContenido = $contenidoInicial !== '' ? self::hashContenido($contenidoInicial) : '';
        }

        $datosPagina = [
            'post_title'   => $defPagina['titulo'],
            'post_content' => $contenidoInicial,
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_name'    => $defPagina['slug'],
            'page_template' => $defPagina['plantilla'] ?: '',
        ];
        $idInsertado = wp_insert_post($datosPagina, true);

        if (!is_wp_error($idInsertado) && $idInsertado > 0) {
            update_post_meta($idInsertado, self::CLAVE_META_GESTION, true);
            // Solo establecer modo por defecto si no existe aún
            if (!metadata_exists('post', $idInsertado, self::CLAVE_MODO_CONTENIDO)) {
                update_post_meta($idInsertado, self::CLAVE_MODO_CONTENIDO, 'code');
            }
            if (self::$modoPorDefecto === 'editor') {
                update_post_meta($idInsertado, self::CLAVE_MODO_CONTENIDO, 'editor');
                if ($hashContenido !== '') {
                    update_post_meta($idInsertado, self::CLAVE_META_HASH, $hashContenido);
                }
            }
            // Aplicar SEO por defecto si existe
            self::aplicarSeoPorDefecto($idInsertado, $defPagina['slug'] ?? '');
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
        // Asegurar que exista el modo por defecto sin sobrescribir elecciones del usuario
        if (!metadata_exists('post', $idPaginaActual, self::CLAVE_MODO_CONTENIDO)) {
            $modo = self::$modoPorDefecto;
            update_post_meta($idPaginaActual, self::CLAVE_MODO_CONTENIDO, $modo);
            if ($modo === 'editor' && empty($paginaExistente->post_content) && !empty($defPagina['funcion'])) {
                $contenido = self::renderHandlerParaCopiar($defPagina['funcion']);
                if ($contenido !== '') {
                    wp_update_post([
                        'ID' => $idPaginaActual,
                        'post_content' => $contenido,
                    ]);
                    update_post_meta($idPaginaActual, self::CLAVE_META_HASH, self::hashContenido($contenido));
                }
            }
        } else {
            // Si el modo ya existe, permitir migrar automáticamente a 'editor' cuando es el default
            // sin romper ediciones manuales: sólo si el contenido está vacío o no ha sido editado (hash coincide)
            $modoActual = get_post_meta($idPaginaActual, self::CLAVE_MODO_CONTENIDO, true);
            if (self::$modoPorDefecto === 'editor' && $modoActual !== 'editor' && !empty($defPagina['funcion'])) {
                $contenidoActual = (string) get_post_field('post_content', $idPaginaActual);
                $hashGuardado = (string) get_post_meta($idPaginaActual, self::CLAVE_META_HASH, true);
                $hashActual = $contenidoActual !== '' ? self::hashContenido($contenidoActual) : '';
                $noEditado = ($hashGuardado !== '' && $hashGuardado === $hashActual) || ($contenidoActual === '');
                if ($noEditado) {
                    update_post_meta($idPaginaActual, self::CLAVE_MODO_CONTENIDO, 'editor');
                    if ($contenidoActual === '') {
                        $contenido = self::renderHandlerParaCopiar($defPagina['funcion']);
                        if ($contenido !== '') {
                            wp_update_post([
                                'ID' => $idPaginaActual,
                                'post_content' => $contenido,
                            ]);
                            update_post_meta($idPaginaActual, self::CLAVE_META_HASH, self::hashContenido($contenido));
                        }
                    } else {
                        // Mantener contenido actual y establecer hash para futuras sincronizaciones inteligentes
                        update_post_meta($idPaginaActual, self::CLAVE_META_HASH, $hashActual);
                    }
                }
            }
        }
        // Aplicar SEO por defecto si existe (sin sobreescribir manual)
        self::aplicarSeoPorDefecto($idPaginaActual, $defPagina['slug'] ?? '');
        return $idPaginaActual;
    }

    /**
     * Devuelve el modo de contenido para una página.
     */
    public static function getModoContenidoParaPagina(int $postId): string
    {
        $modo = get_post_meta($postId, self::CLAVE_MODO_CONTENIDO, true);
        return in_array($modo, ['code', 'editor'], true) ? $modo : 'code';
    }

    /**
     * Obtiene el handler de renderizado por slug, si existe.
     */
    public static function getHandlerPorSlug(string $slug): ?string
    {
        return isset(self::$paginasDefinidas[$slug]) ? (self::$paginasDefinidas[$slug]['funcion'] ?? null) : null;
    }

    /**
     * Obtiene la definición completa por slug, si existe.
     */
    public static function getDefinicionPorSlug(string $slug): ?array
    {
        return self::$paginasDefinidas[$slug] ?? null;
    }

    /**
     * Devuelve la configuración SEO por defecto para un slug si existe.
     */
    public static function getDefaultSeoForSlug(string $slug): array
    {
        return isset(self::$defaultSeoMap[$slug]) && is_array(self::$defaultSeoMap[$slug])
            ? self::$defaultSeoMap[$slug]
            : [];
    }

    private static function _sincronizarEditorSiNoEditado(int $postId, array $defPagina): void
    {
        if (empty($defPagina['funcion'])) {
            return;
        }
        $modo = get_post_meta($postId, self::CLAVE_MODO_CONTENIDO, true);
        if ($modo !== 'editor') {
            return;
        }
        $contenidoActual = get_post_field('post_content', $postId);
        $hashGuardado = (string) get_post_meta($postId, self::CLAVE_META_HASH, true);
        $hashActual = $contenidoActual !== '' ? self::hashContenido($contenidoActual) : '';

        if ($hashGuardado !== '' && $hashGuardado === $hashActual) {
            $contenidoNuevo = self::renderHandlerParaCopiar($defPagina['funcion']);
            if ($contenidoNuevo !== '' && $contenidoNuevo !== $contenidoActual) {
                wp_update_post([
                    'ID' => $postId,
                    'post_content' => $contenidoNuevo,
                ]);
                update_post_meta($postId, self::CLAVE_META_HASH, self::hashContenido($contenidoNuevo));
            }
        }
    }

    private static function hashContenido(string $content): string
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
        // Quitar espacios en blanco al inicio/fin, incluidas líneas en blanco finales
        $salida = trim($html);

        // Eliminar párrafos o saltos de línea vacíos al final: <p><br></p>, <p>&nbsp;</p> o <br>
        $patronFinalVacio = '/(?:<p[^>]*>\s*(?:<br\s*\/?>|&nbsp;|\s)*<\/p>\s*|<br\s*\/?>\s*)$/i';
        while (preg_match($patronFinalVacio, $salida) === 1) {
            $salida = (string) preg_replace($patronFinalVacio, '', $salida);
        }

        return $salida;
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

    private static function aplicarSeoPorDefecto(int $postId, string $slug): void
    {
        if ($slug === '' || empty(self::$defaultSeoMap[$slug]) || !is_array(self::$defaultSeoMap[$slug])) {
            return;
        }
        $def = self::$defaultSeoMap[$slug];
        $title = isset($def['title']) ? (string) $def['title'] : '';
        $desc = isset($def['desc']) ? (string) $def['desc'] : '';
        $canonical = isset($def['canonical']) ? (string) $def['canonical'] : '';
        $faq = isset($def['faq']) && is_array($def['faq']) ? $def['faq'] : [];
        $bc = isset($def['breadcrumb']) && is_array($def['breadcrumb']) ? $def['breadcrumb'] : [];
        if ($title !== '' && get_post_meta($postId, '_glory_seo_title', true) === '') {
            update_post_meta($postId, '_glory_seo_title', $title);
        }
        if ($desc !== '' && get_post_meta($postId, '_glory_seo_desc', true) === '') {
            update_post_meta($postId, '_glory_seo_desc', $desc);
        }
        if ($canonical !== '' && get_post_meta($postId, '_glory_seo_canonical', true) === '') {
            // Normalizar con barra final
            if (substr($canonical, -1) !== '/') {
                $canonical .= '/';
            }
            update_post_meta($postId, '_glory_seo_canonical', $canonical);
        }
        if (!empty($faq) && get_post_meta($postId, '_glory_seo_faq', true) === '') {
            update_post_meta($postId, '_glory_seo_faq', wp_json_encode($faq, JSON_UNESCAPED_UNICODE));
        }
        if (!empty($bc) && get_post_meta($postId, '_glory_seo_breadcrumb', true) === '') {
            update_post_meta($postId, '_glory_seo_breadcrumb', wp_json_encode($bc, JSON_UNESCAPED_UNICODE));
        }
    }
}
