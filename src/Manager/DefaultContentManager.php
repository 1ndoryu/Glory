<?php

namespace Glory\Manager;

use Glory\Core\GloryLogger;

use WP_Query;
use WP_Post;
use WP_Error;

class DefaultContentManager {
    private static array $definiciones = [];

    private const metaClaveSlugDefault = '_glory_default_content_slug';
    private const metaClaveEditadoManualmente = '_glory_default_content_edited';

    public static function define(string $tipoPost, array $postsDefault, string $modoActualizacion = 'smart', bool $permitirEliminacion = false): void {
        if (empty($tipoPost)) {
            GloryLogger::error("DefaultContentManager: El slug del tipo de post no puede estar vacío. Definición omitida.");
            return;
        }
        if (!in_array($modoActualizacion, ['none', 'force', 'smart'], true)) {
            $modoActualizacion = 'smart';
        }

        $slugsProcesados = [];
        foreach ($postsDefault as $indice => $datosPost) {
            if (!is_array($datosPost)) {
                GloryLogger::error("DefaultContentManager: La definición del post en el índice {$indice} para el tipo de post '{$tipoPost}' no es un array. Omitiendo definición.");
                return;
            }
            if (!isset($datosPost['slugDefault']) || !is_string($datosPost['slugDefault']) || empty(trim($datosPost['slugDefault']))) {
                GloryLogger::error("DefaultContentManager: 'slugDefault' inválido o ausente (cadena no vacía) en el índice {$indice} para el tipo de post '{$tipoPost}'. Omitiendo definición.");
                return;
            }
            $slugActual = trim($datosPost['slugDefault']);
            if (isset($slugsProcesados[$slugActual])) {
                GloryLogger::error("DefaultContentManager: 'slugDefault' ('{$slugActual}') duplicado encontrado en la definición para el tipo de post '{$tipoPost}'. Omitiendo definición.");
                return;
            }
            $slugsProcesados[$slugActual] = true;

            if (!isset($datosPost['titulo']) || !is_string($datosPost['titulo']) || empty(trim($datosPost['titulo']))) {
                GloryLogger::error("DefaultContentManager: 'titulo' inválido o ausente (cadena no vacía) para slugDefault '{$slugActual}', tipo de post '{$tipoPost}'. Omitiendo definición.");
                return;
            }
            
            if (isset($datosPost['fecha'])) {
                if (!is_string($datosPost['fecha']) || !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $datosPost['fecha'])) {
                    GloryLogger::error("DefaultContentManager: Formato de 'fecha' inválido (debe ser YYYY-MM-DD HH:MM:SS) para slugDefault '{$slugActual}', tipo de post '{$tipoPost}'. Omitiendo definición.");
                    return;
                }
            }
            if (isset($datosPost['fechaGmt'])) {
                if (!is_string($datosPost['fechaGmt']) || ($datosPost['fechaGmt'] !== '0000-00-00 00:00:00' && !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $datosPost['fechaGmt']))) {
                     GloryLogger::error("DefaultContentManager: Formato de 'fechaGmt' inválido (debe ser YYYY-MM-DD HH:MM:SS o 0000-00-00 00:00:00) para slugDefault '{$slugActual}', tipo de post '{$tipoPost}'. Omitiendo definición.");
                    return;
                }
                if (!isset($datosPost['fecha']) && $datosPost['fechaGmt'] !== '0000-00-00 00:00:00') {
                    GloryLogger::error("DefaultContentManager: 'fechaGmt' está definida para '{$slugActual}' ('{$tipoPost}') pero 'fecha' no. 'fecha' es requerida si 'fechaGmt' está definida (a menos que GMT sea '0000-00-00 00:00:00'). Omitiendo definición.");
                    return;
                }
            }

            if (isset($datosPost['metaEntrada']) && !is_array($datosPost['metaEntrada'])) {
                GloryLogger::error("DefaultContentManager: 'metaEntrada' inválida (debe ser un array si se proporciona) para slugDefault '{$slugActual}', tipo de post '{$tipoPost}'. Omitiendo definición.");
                return;
            }
            $camposEscalares = ['contenido', 'estado', 'extracto'];
            foreach ($camposEscalares as $campo) {
                if (isset($datosPost[$campo]) && !is_scalar($datosPost[$campo])) {
                    GloryLogger::error("DefaultContentManager: Tipo inválido para '{$campo}' (debe ser escalar) para slugDefault '{$slugActual}', tipo de post '{$tipoPost}'. Omitiendo definición.");
                    return;
                }
            }
        }

        self::$definiciones[$tipoPost] = [
            'definicionesPost'    => $postsDefault,
            'modoActualizacion'   => $modoActualizacion,
            'permitirEliminacion' => $permitirEliminacion,
        ];
    }

    public static function register(): void {
        add_action('init', [self::class, 'procesarDefiniciones'], 20);
        add_action('save_post', [self::class, 'detectarEdicionManual'], 99, 3);
    }

    public static function procesarDefiniciones(): void {
        if (empty(self::$definiciones)) return;
        static $procesando = false;
        if ($procesando) return;
        $procesando = true;

        $mapaSlugsDefinidos = [];
        foreach (self::$definiciones as $tp => $configDef) {
            $mapaSlugsDefinidos[$tp] = array_column($configDef['definicionesPost'], 'slugDefault');
        }

        foreach (self::$definiciones as $tipoPost => $configDefinicionActual) {
            $definicionesPost = $configDefinicionActual['definicionesPost'];
            $modoActualizacion = $configDefinicionActual['modoActualizacion'];
            $permitirEliminacion = $configDefinicionActual['permitirEliminacion'];
            $slugsDefinidosTpActual = $mapaSlugsDefinidos[$tipoPost] ?? [];

            if (!post_type_exists($tipoPost)) {
                GloryLogger::error("DefaultContentManager: No se puede procesar la definición porque el tipo de post '{$tipoPost}' no existe o aún no está registrado.");
                continue;
            }

            $idsPostsProcesadosDb = [];
            foreach ($definicionesPost as $datosPost) {
                $slugDefault = trim($datosPost['slugDefault']);
                $postExistente = self::buscarPostDefaultExistente($tipoPost, $slugDefault);

                if ($postExistente) {
                    $idPost = $postExistente->ID;
                    $idsPostsProcesadosDb[] = $idPost;
                    $editadoManualmente = get_post_meta($idPost, self::metaClaveEditadoManualmente, true) === '1';

                    if ($modoActualizacion === 'force') {
                        self::actualizarPost($idPost, $tipoPost, $datosPost, true);
                    } elseif ($modoActualizacion === 'smart') {
                        if (!$editadoManualmente) {
                            if (self::definicionDifiereDePostDb($postExistente, $datosPost)) {
                                self::actualizarPost($idPost, $tipoPost, $datosPost, false);
                            }
                        }
                    }
                } else {
                    $idNuevoPost = self::crearNuevoPost($tipoPost, $datosPost);
                    if ($idNuevoPost) {
                        $idsPostsProcesadosDb[] = $idNuevoPost;
                    }
                }
            }

            if ($permitirEliminacion) {
                $args = [
                    'post_type' => $tipoPost,
                    'post_status' => 'any',
                    'posts_per_page' => -1,
                    'meta_query' => [['key' => self::metaClaveSlugDefault, 'compare' => 'EXISTS']],
                    'fields' => 'ids',
                    'no_found_rows' => true,
                    'update_post_meta_cache' => true,
                    'update_post_term_cache' => false,
                ];
                $consultaPotencialesEliminar = new WP_Query($args);
                $idsPotencialesEliminar = $consultaPotencialesEliminar->posts;

                if (!empty($idsPotencialesEliminar)) {
                    foreach ($idsPotencialesEliminar as $idPostParaVerificar) {
                        if (in_array($idPostParaVerificar, $idsPostsProcesadosDb)) continue;
                        $slugDefaultPost = get_post_meta($idPostParaVerificar, self::metaClaveSlugDefault, true);
                        if (in_array($slugDefaultPost, $slugsDefinidosTpActual, true)) continue;

                        $editadoManualmente = get_post_meta($idPostParaVerificar, self::metaClaveEditadoManualmente, true) === '1';
                        if (!$editadoManualmente) {
                            $eliminado = wp_delete_post($idPostParaVerificar, true);
                            if (!$eliminado) {
                                GloryLogger::error("DefaultContentManager: FALLÓ al eliminar post ID {$idPostParaVerificar}.");
                            }
                        }
                    }
                }
            }
        }
        $procesando = false;
    }

    private static function buscarPostDefaultExistente(string $tipoPost, string $slugDefault): ?WP_Post {
        $queryArgs = [
            'post_type' => $tipoPost,
            'post_status' => 'any',
            'posts_per_page' => 1,
            'meta_query' => [['key' => self::metaClaveSlugDefault, 'value' => $slugDefault, 'compare' => '=']],
            'fields' => 'ids',
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ];
        $consulta = new WP_Query($queryArgs);
        if (!empty($consulta->posts)) {
            $postEncontrado = get_post($consulta->posts[0]);
            return ($postEncontrado instanceof WP_Post) ? $postEncontrado : null;
        }
        return null;
    }

    private static function definicionDifiereDePostDb(WP_Post $postDb, array $definicionCodigo): bool {
        if ($postDb->post_title !== ($definicionCodigo['titulo'] ?? '')) return true;
        if ($postDb->post_content !== ($definicionCodigo['contenido'] ?? '')) return true;
        if ($postDb->post_status !== ($definicionCodigo['estado'] ?? 'publish')) return true;
        if ($postDb->post_excerpt !== ($definicionCodigo['extracto'] ?? '')) return true;
        
        if (isset($definicionCodigo['fecha']) && $postDb->post_date !== $definicionCodigo['fecha']) return true;
        
        if (isset($definicionCodigo['fechaGmt']) && $definicionCodigo['fechaGmt'] !== '0000-00-00 00:00:00') {
            if ($postDb->post_date_gmt !== $definicionCodigo['fechaGmt']) return true;
        }

        $metaDefinida = $definicionCodigo['metaEntrada'] ?? [];
        foreach ($metaDefinida as $clave => $valor) {
            $valorDb = get_post_meta($postDb->ID, $clave, true);
            if ($valorDb != $valor) return true;
        }
        return false;
    }

    private static function crearNuevoPost(string $tipoPost, array $datosPost): int|false {
        $slugDefault = trim($datosPost['slugDefault']);
        $datosInsercion = [
            'post_type'    => $tipoPost,
            'post_title'   => $datosPost['titulo'],
            'post_content' => $datosPost['contenido'] ?? '',
            'post_status'  => $datosPost['estado'] ?? 'publish',
            'post_excerpt' => $datosPost['extracto'] ?? '',
        ];

        if (isset($datosPost['fecha'])) $datosInsercion['post_date'] = $datosPost['fecha'];
        if (isset($datosPost['fechaGmt'])) {
            $datosInsercion['post_date_gmt'] = $datosPost['fechaGmt'];
        } elseif (isset($datosPost['fecha'])) {
            $datosInsercion['post_date_gmt'] = '0000-00-00 00:00:00';
        }

        $datosInsercion['meta_input'] = $datosPost['metaEntrada'] ?? [];
        $datosInsercion['meta_input'][self::metaClaveSlugDefault] = $slugDefault;
        unset($datosInsercion['meta_input'][self::metaClaveEditadoManualmente]);

        remove_action('save_post', [self::class, 'detectarEdicionManual'], 99);
        $idPost = wp_insert_post($datosInsercion, true);
        add_action('save_post', [self::class, 'detectarEdicionManual'], 99, 3);

        if (is_wp_error($idPost)) {
            GloryLogger::error("DefaultContentManager: FALLÓ al insertar post default para '{$tipoPost}' (slug: {$slugDefault}).", [
                'errorCode'    => $idPost->get_error_code(),
                'errorMessage' => $idPost->get_error_message(),
                'data'         => $datosInsercion
            ]);
            return false;
        }
        return $idPost;
    }

    private static function actualizarPost(int $idPost, string $tipoPost, array $datosPost, bool $esActualizacionForzada): void {
        $slugDefault = trim($datosPost['slugDefault']);
        $etiquetaModo = $esActualizacionForzada ? 'force' : 'smart';
        $datosActualizacion = [
            'ID'           => $idPost,
            'post_title'   => $datosPost['titulo'],
            'post_content' => $datosPost['contenido'] ?? '',
            'post_status'  => $datosPost['estado'] ?? 'publish',
            'post_excerpt' => $datosPost['extracto'] ?? '',
        ];

        if (isset($datosPost['fecha'])) $datosActualizacion['post_date'] = $datosPost['fecha'];
        if (isset($datosPost['fechaGmt'])) {
            $datosActualizacion['post_date_gmt'] = $datosPost['fechaGmt'];
        } elseif (isset($datosPost['fecha'])) {
            $datosActualizacion['post_date_gmt'] = '0000-00-00 00:00:00';
        }

        remove_action('save_post', [self::class, 'detectarEdicionManual'], 99);
        $resultadoActualizacion = wp_update_post($datosActualizacion, true);
        add_action('save_post', [self::class, 'detectarEdicionManual'], 99, 3);

        if (is_wp_error($resultadoActualizacion)) {
            GloryLogger::error("DefaultContentManager: FALLÓ al actualizar datos principales para post ID {$idPost} ('{$tipoPost}', slug: {$slugDefault}). Modo: {$etiquetaModo}.", [
                'errorCode'    => $resultadoActualizacion->get_error_code(),
                'errorMessage' => $resultadoActualizacion->get_error_message(),
                'data'         => $datosActualizacion
            ]);
            return;
        }

        $nuevaMeta = $datosPost['metaEntrada'] ?? [];
        foreach ($nuevaMeta as $claveMeta => $valorMeta) {
            if ($claveMeta === self::metaClaveSlugDefault || $claveMeta === self::metaClaveEditadoManualmente) continue;
            update_post_meta($idPost, $claveMeta, $valorMeta);
        }

        if ($esActualizacionForzada) {
            $metaExistente = get_post_meta($idPost);
            $clavesMetaExistente = is_array($metaExistente) ? array_keys($metaExistente) : [];
            $clavesNuevaMetaDefinicion = array_keys($nuevaMeta);

            foreach ($clavesMetaExistente as $claveParaVerificar) {
                if ($claveParaVerificar === self::metaClaveSlugDefault || $claveParaVerificar === self::metaClaveEditadoManualmente || strpos($claveParaVerificar, '_') === 0) {
                    continue;
                }
                if (!in_array($claveParaVerificar, $clavesNuevaMetaDefinicion, true)) {
                    delete_post_meta($idPost, $claveParaVerificar);
                }
            }
            delete_post_meta($idPost, self::metaClaveEditadoManualmente);
        }
    }

    public static function detectarEdicionManual(int $idPost, WP_Post $objetoPost, bool $esActualizacion): void {
        if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($idPost) || isset($_REQUEST['bulk_edit'])) return;
        if (!is_admin() || !current_user_can('edit_post', $idPost)) return;

        $slugDefault = get_post_meta($idPost, self::metaClaveSlugDefault, true);
        if (!empty($slugDefault)) {
            $yaMarcado = get_post_meta($idPost, self::metaClaveEditadoManualmente, true) === '1';
            if (!$yaMarcado && has_action('save_post', [self::class, 'detectarEdicionManual'])) {
                update_post_meta($idPost, self::metaClaveEditadoManualmente, '1');
            }
        }
    }
}