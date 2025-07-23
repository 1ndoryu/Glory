<?php
// Glory/src/Services/DefaultContentSynchronizer.php

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
    private const META_CLAVE_CATEGORIA_GESTIONADA = '_glory_managed_category';


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

        GloryLogger::info('DCS: Iniciando sincronización de contenido por defecto.');

        $definicionesPorTipo = DefaultContentRegistry::getDefiniciones();

        $this->sincronizarCategoriasDirectamente();

        if (empty($definicionesPorTipo)) {
            GloryLogger::info('DCS: No hay definiciones de contenido (posts) para sincronizar.');
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
     * Restablece todo el contenido gestionado a su estado por defecto.
     * Itera sobre todas las definiciones, encuentra los posts correspondientes
     * y los actualiza forzadamente a la versión del código.
     */
    public function restablecer(): void
    {
        if ($this->procesando) return;
        $this->procesando = true;

        $definicionesPorTipo = DefaultContentRegistry::getDefiniciones();

        if (empty($definicionesPorTipo)) {
            $this->procesando = false;
            return;
        }

        foreach ($definicionesPorTipo as $tipoPost => $config) {
            if (!post_type_exists($tipoPost)) {
                continue;
            }

            foreach ($config['definicionesPost'] as $datosPostDefinicion) {
                $slugDefault = trim($datosPostDefinicion['slugDefault']);
                $postExistente = $this->repositorio->findPorSlug($tipoPost, $slugDefault);

                if ($postExistente) {
                    // Forzar la actualización independientemente de si fue editado manualmente.
                    $this->actualizarPost($postExistente->ID, $datosPostDefinicion, true);
                    GloryLogger::info("DCS: Post '{$slugDefault}' (ID: {$postExistente->ID}) restablecido a default.");
                }
            }
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
                    // Verificar si la imagen de la categoría ha cambiado
                    if (isset($mapeoImagenes[$nombreCategoria])) {
                        $nombreArchivoDefinido = $mapeoImagenes[$nombreCategoria];
                        $idImagenEsperado = AssetsUtility::get_attachment_id_from_asset($nombreArchivoDefinido);
                        $idImagenActual = get_term_meta($term->term_id, 'glory_category_image_id', true);

                        if ((int) $idImagenActual != (int) $idImagenEsperado) {
                            return true;
                        }
                    }

                    // Verificar si a la categoría le falta la descripción
                    if (isset($mapeoDescripciones[$nombreCategoria])) {
                        $descripcionEsperada = trim($mapeoDescripciones[$nombreCategoria]);
                        if (empty($term->description) || trim($term->description) !== $descripcionEsperada) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    private function crearNuevoPost(string $tipoPost, array $datosPost): ?int
    {
        $slugDefault = trim($datosPost['slugDefault']);
        $datosInsercion = [
            'post_type' => $tipoPost,
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
        // --- INICIO DE LA SOLUCIÓN ---
        // Se consolida la actualización de post y meta en una sola llamada.
        $datosActualizacion = [
            'ID'  => $idPost,
            'post_title' => $datosPost['titulo'],
            'post_content' => $datosPost['contenido'] ?? '',
            'post_status' => $datosPost['estado'] ?? 'publish',
            'post_excerpt' => $datosPost['extracto'] ?? '',
            'meta_input' => $datosPost['metaEntrada'] ?? [], // Se añade meta_input
        ];
        if (isset($datosPost['fecha'])) $datosActualizacion['post_date'] = $datosPost['fecha'];
        if (isset($datosPost['fechaGmt'])) $datosActualizacion['post_date_gmt'] = $datosPost['fechaGmt'];

        // Asegurarse de que el slug de control no se borre.
        $datosActualizacion['meta_input'][self::META_CLAVE_SLUG_DEFAULT] = $datosPost['slugDefault'];

        $resultado = wp_update_post($datosActualizacion, true);
        // --- FIN DE LA SOLUCIÓN ---

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

        if ($esForzado) {
            // Limpiar metadatos antiguos si es una actualización forzada
            $metaDefinida = $datosPost['metaEntrada'] ?? [];
            $metaExistente = get_post_meta($idPost);
            foreach (array_keys($metaExistente) as $claveExistente) {
                if (str_starts_with($claveExistente, '_')) continue;
                if (!array_key_exists($claveExistente, $metaDefinida)) {
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
                $idAdjunto = AssetsUtility::get_attachment_id_from_asset($nombreArchivoImagen);
                if ($idAdjunto) {
                    update_term_meta($term->term_id, 'glory_category_image_id', $idAdjunto);
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

    private function sincronizarCategoriasDirectamente(): void
    {
        GloryLogger::info('DCS: Iniciando sincronización directa de categorías.');

        $categoriasDefinicion = $GLOBALS['glory_categorias_definidas'] ?? [];
        if (empty($categoriasDefinicion)) {
            GloryLogger::info('DCS: No hay definiciones de categorías para sincronizar.');
            return;
        }

        // --- INICIO DE LA SOLUCIÓN ---

        // 1. Obtener todos los nombres de las categorías definidas en el código.
        $nombresDefinidos = array_column($categoriasDefinicion, 'nombre');
        if (empty($nombresDefinidos)) {
            GloryLogger::warning('DCS: La definición de categorías existe pero no contiene nombres.');
            return;
        }

        // 2. Obtener todas las categorías de la base de datos que están marcadas como gestionadas.
        $terminosGestionadosEnBd = get_terms([
            'taxonomy' => 'category',
            'hide_empty' => false,
            'meta_key' => self::META_CLAVE_CATEGORIA_GESTIONADA,
            'meta_value' => '1',
        ]);

        // 3. Identificar y eliminar las categorías obsoletas (gestionadas pero ya no definidas).
        if (!is_wp_error($terminosGestionadosEnBd)) {
            foreach ($terminosGestionadosEnBd as $term) {
                if (!in_array($term->name, $nombresDefinidos, true)) {
                    GloryLogger::info("DCS: Eliminando categoría obsoleta '{$term->name}' (ID: {$term->term_id}).");
                    wp_delete_term($term->term_id, 'category');
                }
            }
        }

        // 4. Iterar sobre las definiciones para crear o actualizar las categorías.
        foreach ($categoriasDefinicion as $def) {
            $nombreCategoria = $def['nombre'] ?? null;
            if (!$nombreCategoria) continue;

            $descripcionDefinida = $def['descripcion'] ?? '';
            $imagenAssetDefinida = $def['imagenAsset'] ?? null;
            $term = get_term_by('name', $nombreCategoria, 'category');

            if (!$term) {
                // La categoría no existe, la creamos.
                $term_result = wp_insert_term($nombreCategoria, 'category', ['description' => $descripcionDefinida]);
                if (is_wp_error($term_result)) {
                    GloryLogger::error("DCS: Error al crear la categoría '{$nombreCategoria}'.", ['error' => $term_result->get_error_message()]);
                    continue;
                }
                $term_id = $term_result['term_id'];
                $term = get_term($term_id);
                GloryLogger::info("DCS: Categoría '{$nombreCategoria}' creada.");
            }

            // Asegurarnos de que la categoría esté marcada como gestionada.
            if ($term) {
                update_term_meta($term->term_id, self::META_CLAVE_CATEGORIA_GESTIONADA, '1');
            }

            // Sincronizar descripción si es diferente.
            if ($term && $term->description !== $descripcionDefinida) {
                wp_update_term($term->term_id, 'category', ['description' => $descripcionDefinida]);
                GloryLogger::info("DCS: Descripción actualizada para la categoría '{$nombreCategoria}'.");
            }

            // Sincronizar imagen si es diferente.
            if ($term && $imagenAssetDefinida) {
                $idImagenEsperado = AssetsUtility::get_attachment_id_from_asset($imagenAssetDefinida);
                $idImagenActual = get_term_meta($term->term_id, 'glory_category_image_id', true);

                if ($idImagenEsperado && (int)$idImagenActual != (int)$idImagenEsperado) {
                    update_term_meta($term->term_id, 'glory_category_image_id', $idImagenEsperado);
                    GloryLogger::info("DCS: Imagen actualizada para la categoría '{$nombreCategoria}'.");
                }
            }
        }
        // --- FIN DE LA SOLUCIÓN ---
    }
}
