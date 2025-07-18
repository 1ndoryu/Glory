<?php

namespace Glory\Services;

use Glory\Core\DefaultContentRegistry;
use Glory\Repository\DefaultContentRepository;
use Glory\Core\GloryLogger;
use Glory\Utility\AssetsUtility;
use WP_Post;
use WP_Error;

/**
 * Orquesta la sincronización del contenido por defecto.
 *
 * Contiene la lógica de negocio principal para asegurar que el contenido definido en
 * el código se refleje en la base de datos, usando las clases Registry y Repository.
 */
class DefaultContentSynchronizer
{
    private const META_CLAVE_SLUG_DEFAULT = '_glory_default_content_slug';
    private const META_CLAVE_EDITADO_MANUALMENTE = '_glory_default_content_edited';
    private const META_CLAVE_GALERIA_IDS = '_glory_default_galeria_ids';

    private DefaultContentRepository $repositorio;
    private bool $procesando = false;

    public function __construct()
    {
        $this->repositorio = new DefaultContentRepository();
    }

    /**
     * Procesa todas las definiciones de contenido y las sincroniza con la BD.
     * Previene ejecuciones anidadas o recurrentes.
     */
    public function sincronizar(): void
    {
        if ($this->procesando) return;
        $this->procesando = true;

        $tInicioGlobal = microtime(true);

        $definicionesPorTipo = DefaultContentRegistry::getDefiniciones();

        if (empty($definicionesPorTipo)) {
            $this->procesando = false;
            return;
        }

        foreach ($definicionesPorTipo as $tipoPost => $config) {
            if (!post_type_exists($tipoPost)) {
                continue;
            }

            if (!$this->validarDefinicionesParaTipo($tipoPost, $config['definicionesPost'])) {
                GloryLogger::warning("DCS: Definiciones inválidas para '{$tipoPost}', se omite.");
                continue;
            }

            $this->sincronizarPostsParaTipo($tipoPost, $config);
            $this->eliminarPostsObsoletosParaTipo($tipoPost, $config);
        }

        $this->procesando = false;
    }

    /**
     * Detecta si un post gestionado es editado manualmente en el admin.
     * Se engancha a 'save_post_{$tipoPost}'.
     */
    public function detectarEdicionManual(int $idPost, WP_Post $objetoPost, bool $esActualizacion): void
    {
        if ($this->procesando || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($idPost) || !$esActualizacion) return;
        if (!is_admin() || !current_user_can('edit_post', $idPost)) return;

        $slugDefault = get_post_meta($idPost, self::META_CLAVE_SLUG_DEFAULT, true);
        if (!empty($slugDefault) && !$this->repositorio->haSidoEditadoManualmente($idPost)) {
            update_post_meta($idPost, self::META_CLAVE_EDITADO_MANUALMENTE, '1');
        }
    }

    private function sincronizarPostsParaTipo(string $tipoPost, array $config): void
    {
        $modoActualizacion = $config['modoActualizacion'];

        foreach ($config['definicionesPost'] as $datosPostDefinicion) {
            $slugDefault = trim($datosPostDefinicion['slugDefault']);
            $postExistente = $this->repositorio->findPorSlug($tipoPost, $slugDefault);

            if ($postExistente) {
                $this->gestionarPostExistente($postExistente, $datosPostDefinicion, $modoActualizacion);
            } else {
                $this->crearNuevoPost($tipoPost, $datosPostDefinicion);
            }
        }
    }

    private function gestionarPostExistente(WP_Post $postExistente, array $definicion, string $modoActualizacion): void
    {
        if ($modoActualizacion === 'none') return;

        $editadoManualmente = $this->repositorio->haSidoEditadoManualmente($postExistente->ID);

        if ($modoActualizacion === 'force') {
            $this->actualizarPost($postExistente->ID, $definicion, true);
        } elseif ($modoActualizacion === 'smart' && !$editadoManualmente) {
            if ($this->definicionDifiereDePostDb($postExistente, $definicion)) {
                $this->actualizarPost($postExistente->ID, $definicion, false);
            }
        }
    }

    private function eliminarPostsObsoletosParaTipo(string $tipoPost, array $config): void
    {
        if (!$config['permitirEliminacion']) return;

        $slugsDefinidos = array_column($config['definicionesPost'], 'slugDefault');
        $idsObsoletos = $this->repositorio->findObsoletos($tipoPost, $slugsDefinidos);

        foreach ($idsObsoletos as $idPost) {
            if (!$this->repositorio->haSidoEditadoManualmente($idPost)) {
                $resultado = wp_delete_post($idPost, true); // Forzar borrado permanente
                if (!$resultado) {
                    GloryLogger::error("DefaultContentSynchronizer: FALLÓ al eliminar post obsoleto ID {$idPost}.");
                }
            }
        }
    }

    private function validarDefinicionesParaTipo(string $tipoPost, array $postsDefault): bool
    {
        $slugsProcesados = [];
        foreach ($postsDefault as $indice => $datosPost) {
            if (!is_array($datosPost) || empty($datosPost['slugDefault']) || !is_string($datosPost['slugDefault']) || empty($datosPost['titulo']) || !is_string($datosPost['titulo'])) {
                GloryLogger::error("DefaultContentManager: Definición de post inválida en el índice {$indice} para '{$tipoPost}'. Se requieren 'slugDefault' y 'titulo' como strings no vacíos.");
                return false;
            }
            $slugActual = trim($datosPost['slugDefault']);
            if (isset($slugsProcesados[$slugActual])) {
                GloryLogger::error("DefaultContentManager: 'slugDefault' ('{$slugActual}') duplicado para '{$tipoPost}'.");
                return false;
            }
            $slugsProcesados[$slugActual] = true;
        }
        return true;
    }

    private function definicionDifiereDePostDb(WP_Post $postDb, array $definicionCodigo): bool
    {
        if ($postDb->post_title !== ($definicionCodigo['titulo'] ?? '')) return true;
        if ($postDb->post_content !== ($definicionCodigo['contenido'] ?? '')) return true;
        if ($postDb->post_status !== ($definicionCodigo['estado'] ?? 'publish')) return true;
        if ($postDb->post_excerpt !== ($definicionCodigo['extracto'] ?? '')) return true;
        if (isset($definicionCodigo['fecha']) && $postDb->post_date !== $definicionCodigo['fecha']) return true;
        if (isset($definicionCodigo['fechaGmt']) && $postDb->post_date_gmt !== $definicionCodigo['fechaGmt']) return true;

        $metaDefinida = $definicionCodigo['metaEntrada'] ?? [];
        foreach ($metaDefinida as $clave => $valor) {
            if (get_post_meta($postDb->ID, $clave, true) != $valor) return true;
        }

        if (isset($definicionCodigo['imagenDestacadaAsset'])) {
            $idAdjuntoDefinicion = AssetsUtility::get_attachment_id_from_asset($definicionCodigo['imagenDestacadaAsset']);
            $idAdjuntoActual = get_post_thumbnail_id($postDb->ID);
            if ($idAdjuntoDefinicion != $idAdjuntoActual) {
                return true;
            }
        }

        $idsGaleriaDefinicion = $this->resolverIdsGaleria($definicionCodigo);
        if (!empty($idsGaleriaDefinicion)) {
            $idsGaleriaActual = get_post_meta($postDb->ID, self::META_CLAVE_GALERIA_IDS, true);
            $idsGaleriaActual = is_array($idsGaleriaActual) ? array_map('intval', $idsGaleriaActual) : [];
            if ($idsGaleriaActual !== $idsGaleriaDefinicion) {
                return true;
            }
        }

        $categoriasRaw = $definicionCodigo['metaEntrada']['categoria'] ?? null;
        if (!empty($categoriasRaw) && is_string($categoriasRaw)) {
            $categoriasEsperadas = array_filter(array_map('trim', explode(',', $categoriasRaw)));
            sort($categoriasEsperadas);

            $terminosActuales = wp_get_post_terms($postDb->ID, 'category', ['fields' => 'names']);
            if (!is_wp_error($terminosActuales)) {
                sort($terminosActuales);

                if ($categoriasEsperadas !== $terminosActuales) {
                    return true;
                }
            }

            // Comprobaciones adicionales para propiedades de las categorías
            $mapeoImagenes = $GLOBALS['glory_imagen_por_categoria'] ?? [];
            $mapeoDescripciones = $GLOBALS['glory_descripcion_por_categoria'] ?? [];

            foreach ($categoriasEsperadas as $nombreCategoria) {
                $term = get_term_by('name', $nombreCategoria, 'category');
                if ($term instanceof \WP_Term) {
                    // Verificar si a la categoría le falta la imagen
                    if (isset($mapeoImagenes[$nombreCategoria])) {
                        $idImagenActual = get_term_meta($term->term_id, 'glory_category_image_id', true);
                        if (empty($idImagenActual)) {
                            return true; // Forzar actualización si falta la imagen
                        }
                    }

                    // <<< INICIO DE LA MODIFICACIÓN >>>
                    // Verificar si a la categoría le falta la descripción
                    if (isset($mapeoDescripciones[$nombreCategoria])) {
                        $descripcionEsperada = trim($mapeoDescripciones[$nombreCategoria]);
                        if (empty($term->description) || trim($term->description) !== $descripcionEsperada) {
                            return true; // Forzar actualización si la descripción es incorrecta
                        }
                    }
                    // <<< FIN DE LA MODIFICACIÓN >>>
                }
            }
        }

        return false;
    }

    private function crearNuevoPost(string $tipoPost, array $datosPost): ?int
    {
        $slugDefault = trim($datosPost['slugDefault']);
        $datosInsercion = [
            'post_type'  => $tipoPost,
            'post_title' => $datosPost['titulo'],
            'post_content' => $datosPost['contenido'] ?? '',
            'post_status' => $datosPost['estado'] ?? 'publish',
            'post_excerpt' => $datosPost['extracto'] ?? '',
            'meta_input' => $datosPost['metaEntrada'] ?? [],
        ];
        if (isset($datosPost['fecha'])) $datosInsercion['post_date'] = $datosPost['fecha'];
        if (isset($datosPost['fechaGmt'])) $datosInsercion['post_date_gmt'] = $datosPost['fechaGmt'];

        $datosInsercion['meta_input'][self::META_CLAVE_SLUG_DEFAULT] = $slugDefault;

        $idPost = wp_insert_post($datosInsercion, true);

        if (is_wp_error($idPost)) {
            GloryLogger::error("DefaultContentSynchronizer: FALLÓ al insertar post para '{$tipoPost}' (slug: {$slugDefault}).", ['error' => $idPost->get_error_message()]);
            return null;
        }

        if (isset($datosPost['imagenDestacadaAsset']) && !empty($datosPost['imagenDestacadaAsset'])) {
            $idAdjunto = AssetsUtility::get_attachment_id_from_asset($datosPost['imagenDestacadaAsset']);
            if ($idAdjunto) {
                set_post_thumbnail($idPost, $idAdjunto);
            }
        }

        $idsGaleria = $this->resolverIdsGaleria($datosPost);
        if (!empty($idsGaleria)) {
            update_post_meta($idPost, self::META_CLAVE_GALERIA_IDS, $idsGaleria);
            foreach ($idsGaleria as $idAdjuntoGaleria) {
                wp_update_post([
                    'ID' => $idAdjuntoGaleria,
                    'post_parent' => $idPost,
                ]);
            }
        }

        $this->asignarCategoriasWp($idPost, $datosPost);

        return $idPost;
    }

    private function actualizarPost(int $idPost, array $datosPost, bool $esForzado): void
    {
        $datosActualizacion = [
            'ID'     => $idPost,
            'post_title' => $datosPost['titulo'],
            'post_content' => $datosPost['contenido'] ?? '',
            'post_status' => $datosPost['estado'] ?? 'publish',
            'post_excerpt' => $datosPost['extracto'] ?? '',
        ];
        if (isset($datosPost['fecha'])) $datosActualizacion['post_date'] = $datosPost['fecha'];
        if (isset($datosPost['fechaGmt'])) $datosActualizacion['post_date_gmt'] = $datosPost['fechaGmt'];

        $resultado = wp_update_post($datosActualizacion, true);

        if (is_wp_error($resultado)) {
            GloryLogger::error("DefaultContentSynchronizer: FALLÓ al actualizar post ID {$idPost}.", ['error' => $resultado->get_error_message()]);
            return;
        }

        if (isset($datosPost['imagenDestacadaAsset']) && !empty($datosPost['imagenDestacadaAsset'])) {
            $idAdjunto = AssetsUtility::get_attachment_id_from_asset($datosPost['imagenDestacadaAsset']);
            if ($idAdjunto) {
                set_post_thumbnail($idPost, $idAdjunto);
            }
        }

        $nuevaMeta = $datosPost['metaEntrada'] ?? [];
        foreach ($nuevaMeta as $claveMeta => $valorMeta) {
            update_post_meta($idPost, $claveMeta, $valorMeta);
        }

        if ($esForzado) {
            $metaExistente = get_post_meta($idPost);
            foreach (array_keys($metaExistente) as $claveExistente) {
                if (str_starts_with($claveExistente, '_')) continue;
                if (!array_key_exists($claveExistente, $nuevaMeta)) {
                    delete_post_meta($idPost, $claveExistente);
                }
            }
            delete_post_meta($idPost, self::META_CLAVE_EDITADO_MANUALMENTE);
        }

        $idsGaleria = $this->resolverIdsGaleria($datosPost);
        update_post_meta($idPost, self::META_CLAVE_GALERIA_IDS, $idsGaleria);

        foreach ($idsGaleria as $idAdjuntoGaleria) {
            wp_update_post([
                'ID' => $idAdjuntoGaleria,
                'post_parent' => $idPost,
            ]);
        }

        $this->asignarCategoriasWp($idPost, $datosPost);
    }

    private function resolverIdsGaleria(array $definicion): array
    {
        $archivosGaleria = [];

        if (!empty($definicion['galeriaAssets']) && is_array($definicion['galeriaAssets'])) {
            $archivosGaleria = $definicion['galeriaAssets'];
        } elseif (!empty($definicion['imagenDestacadaAsset'])) {
            $nombreBase = preg_replace('/\.[^.]+$/', '', $definicion['imagenDestacadaAsset']);
            for ($i = 1; $i <= 3; $i++) {
                $archivosGaleria[] = $nombreBase . $i . '.jpg';
                $archivosGaleria[] = strtolower($nombreBase) . $i . '.jpg';
            }
        }

        $ids = [];
        foreach ($archivosGaleria as $archivo) {
            $idAdjunto = \Glory\Utility\AssetsUtility::get_attachment_id_from_asset($archivo);
            if ($idAdjunto) {
                $ids[] = (int) $idAdjunto;
            }
        }

        return array_unique($ids);
    }

    private function asignarCategoriasWp(int $idPost, array $datosPost): void
    {
        $categoriasRaw = $datosPost['metaEntrada']['categoria'] ?? null;
        if (empty($categoriasRaw) || !is_string($categoriasRaw)) {
            wp_set_object_terms($idPost, null, 'category');
            return;
        }

        $nombresCategorias = array_filter(array_map('trim', explode(',', $categoriasRaw)));
        if (empty($nombresCategorias)) {
            wp_set_object_terms($idPost, null, 'category');
            return;
        }

        wp_set_object_terms($idPost, $nombresCategorias, 'category', false);

        $mapeoImagenes = $GLOBALS['glory_imagen_por_categoria'] ?? [];
        $mapeoDescripciones = $GLOBALS['glory_descripcion_por_categoria'] ?? [];

        foreach ($nombresCategorias as $nombreCategoria) {
            $term = get_term_by('name', $nombreCategoria, 'category');

            if ($term instanceof \WP_Term && isset($mapeoImagenes[$nombreCategoria])) {
                $nombreArchivoImagen = $mapeoImagenes[$nombreCategoria];
                $idImagenActual = get_term_meta($term->term_id, 'glory_category_image_id', true);

                if (!$idImagenActual) {
                    $idAdjunto = AssetsUtility::get_attachment_id_from_asset($nombreArchivoImagen);
                    if ($idAdjunto) {
                        update_term_meta($term->term_id, 'glory_category_image_id', $idAdjunto);
                    }
                }
            }

            if ($term instanceof \WP_Term && isset($mapeoDescripciones[$nombreCategoria])) {
                $descripcionEsperada = trim($mapeoDescripciones[$nombreCategoria]);

                if (empty($term->description) || trim($term->description) !== $descripcionEsperada) {
                    wp_update_term($term->term_id, 'category', [
                        'description' => $descripcionEsperada,
                    ]);
                }
            }
        }
    }
}
