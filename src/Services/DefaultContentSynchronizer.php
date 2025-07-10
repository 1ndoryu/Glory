<?
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

        $definicionesPorTipo = DefaultContentRegistry::getDefiniciones();
        if (empty($definicionesPorTipo)) {
            $this->procesando = false;
            return;
        }

        foreach ($definicionesPorTipo as $tipoPost => $config) {
            if (!post_type_exists($tipoPost)) {
                GloryLogger::error("DefaultContentSynchronizer: No se puede procesar la definición porque el tipo de post '{$tipoPost}' no existe.");
                continue;
            }

            if (!$this->validarDefinicionesParaTipo($tipoPost, $config['definicionesPost'])) {
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

        // --- INICIO NUEVA FUNCIONALIDAD: Comprobar imagen destacada ---
        if (isset($definicionCodigo['imagenDestacadaAsset'])) {
            $idAdjuntoDefinicion = AssetsUtility::get_attachment_id_from_asset($definicionCodigo['imagenDestacadaAsset']);
            $idAdjuntoActual = get_post_thumbnail_id($postDb->ID);
            if ($idAdjuntoDefinicion != $idAdjuntoActual) {
                return true;
            }
        }
        // --- FIN NUEVA FUNCIONALIDAD ---

        return false;
    }

    private function crearNuevoPost(string $tipoPost, array $datosPost): ?int
    {
        $slugDefault = trim($datosPost['slugDefault']);
        $datosInsercion = [
            'post_type'    => $tipoPost,
            'post_title'   => $datosPost['titulo'],
            'post_content' => $datosPost['contenido'] ?? '',
            'post_status'  => $datosPost['estado'] ?? 'publish',
            'post_excerpt' => $datosPost['extracto'] ?? '',
            'meta_input'   => $datosPost['metaEntrada'] ?? [],
        ];
        if (isset($datosPost['fecha'])) $datosInsercion['post_date'] = $datosPost['fecha'];
        if (isset($datosPost['fechaGmt'])) $datosInsercion['post_date_gmt'] = $datosPost['fechaGmt'];

        $datosInsercion['meta_input'][self::META_CLAVE_SLUG_DEFAULT] = $slugDefault;

        $idPost = wp_insert_post($datosInsercion, true);

        if (is_wp_error($idPost)) {
            GloryLogger::error("DefaultContentSynchronizer: FALLÓ al insertar post para '{$tipoPost}' (slug: {$slugDefault}).", ['error' => $idPost->get_error_message()]);
            return null;
        }

        // --- INICIO NUEVA FUNCIONALIDAD: Asignar imagen destacada ---
        if (isset($datosPost['imagenDestacadaAsset']) && !empty($datosPost['imagenDestacadaAsset'])) {
            $idAdjunto = AssetsUtility::get_attachment_id_from_asset($datosPost['imagenDestacadaAsset']);
            if ($idAdjunto) {
                set_post_thumbnail($idPost, $idAdjunto);
            }
        }
        // --- FIN NUEVA FUNCIONALIDAD ---

        return $idPost;
    }

    private function actualizarPost(int $idPost, array $datosPost, bool $esForzado): void
    {
        $datosActualizacion = [
            'ID'           => $idPost,
            'post_title'   => $datosPost['titulo'],
            'post_content' => $datosPost['contenido'] ?? '',
            'post_status'  => $datosPost['estado'] ?? 'publish',
            'post_excerpt' => $datosPost['extracto'] ?? '',
        ];
        if (isset($datosPost['fecha'])) $datosActualizacion['post_date'] = $datosPost['fecha'];
        if (isset($datosPost['fechaGmt'])) $datosActualizacion['post_date_gmt'] = $datosPost['fechaGmt'];

        $resultado = wp_update_post($datosActualizacion, true);

        if (is_wp_error($resultado)) {
            GloryLogger::error("DefaultContentSynchronizer: FALLÓ al actualizar post ID {$idPost}.", ['error' => $resultado->get_error_message()]);
            return;
        }
        
        // --- INICIO NUEVA FUNCIONALIDAD: Asignar imagen destacada ---
        if (isset($datosPost['imagenDestacadaAsset']) && !empty($datosPost['imagenDestacadaAsset'])) {
            $idAdjunto = AssetsUtility::get_attachment_id_from_asset($datosPost['imagenDestacadaAsset']);
            if ($idAdjunto) {
                set_post_thumbnail($idPost, $idAdjunto);
            }
        }
        // --- FIN NUEVA FUNCIONALIDAD ---

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
    }
}