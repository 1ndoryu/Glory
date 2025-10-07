<?php

namespace Glory\Utility;

use Glory\Core\GloryLogger;
use Glory\Manager\AssetManager;


class AssetsUtility
{

    private static array $assetPaths = [];

    private static bool $isInitialized = false;


    public static function init(): void
    {
        if (self::$isInitialized) {
            return;
        }
        self::registerAssetPath('glory', 'Glory/assets/images');
        self::registerAssetPath('elements', 'Glory/assets/images/elements');
        self::registerAssetPath('tema', 'App/assets/images');
        // Alias dedicado a la carpeta de colores solicitada para portafolio
        self::registerAssetPath('colors', 'Glory/assets/images/colors');
        // Alias para logos de marcas
        self::registerAssetPath('logos', 'Glory/assets/images/logos');
        self::$isInitialized = true;
        add_action('admin_init', [AssetsUtility::class, 'importTemaAssets']);
    }


    public static function registerAssetPath(string $alias, string $path): void
    {
        self::$assetPaths[sanitize_key($alias)] = trim($path, '/\\');
    }


    public static function parseAssetReference(string $reference): array
    {
        if (strpos($reference, '::') !== false) {
            return explode('::', $reference, 2);
        }
        return ['glory', $reference];
    }


    private static function resolveAssetPath(string $alias, string $nombreArchivo): ?string
    {
        if (!isset(self::$assetPaths[$alias])) {
            GloryLogger::warning("AssetsUtility: El alias de ruta '{$alias}' no está registrado.");
            return null;
        }
        return self::$assetPaths[$alias] . '/' . ltrim($nombreArchivo, '/\\');
    }


    /**
     * Verifica si un asset referido existe físicamente en el tema.
     */
    public static function assetExists(string $assetReference): bool
    {
        if (!self::$isInitialized) self::init();
        list($alias, $nombreArchivo) = self::parseAssetReference($assetReference);
        $rutaRelativa = self::resolveAssetPath($alias, $nombreArchivo);
        if (!$rutaRelativa) {
            return false;
        }
        $rutaLocal = get_template_directory() . '/' . $rutaRelativa;
        return file_exists($rutaLocal);
    }


    public static function getRandomDefaultImageName(string $alias = 'glory'): ?string
    {
        if (!self::$isInitialized) {
            self::init();
        }

        if (!isset(self::$assetPaths[$alias])) {
            GloryLogger::error("AssetsUtility: La ruta con alias '{$alias}' para imágenes aleatorias no está registrada.");
            return null;
        }

        $directorioImagenes = get_template_directory() . '/' . self::$assetPaths[$alias] . '/';
        $patronBusqueda = $directorioImagenes . 'default*.{jpg,jpeg,png,gif,webp}';
        $archivos = glob($patronBusqueda, GLOB_BRACE);

        if (empty($archivos)) {
            GloryLogger::warning("AssetsUtility: No se encontraron imágenes por defecto con el patrón '{$patronBusqueda}'.");
            return null;
        }

        return basename($archivos[array_rand($archivos)]);
    }


    /**
     * Retorna un arreglo de nombres de archivo (basename) de imágenes aleatorias y únicas
     * dentro del alias de assets indicado. Útil para sembrar contenido sin repetición.
     *
     * @param string $alias Alias registrado del directorio de assets (por ejemplo, 'colors').
     * @param int $cantidad Número de imágenes únicas a seleccionar.
     * @param array $extensiones Extensiones permitidas.
     * @return array<string> Lista de nombres de archivo (sin ruta) seleccionados aleatoriamente.
     */
    public static function getRandomUniqueImagesFromAlias(
        string $alias,
        int $cantidad,
        array $extensiones = ['jpg','jpeg','png','gif','webp']
    ): array {
        if (!self::$isInitialized) self::init();

        if (!isset(self::$assetPaths[$alias])) {
            GloryLogger::error("AssetsUtility: Alias '{$alias}' no registrado para selección aleatoria.");
            return [];
        }

        $directorioImagenes = trailingslashit(get_template_directory() . '/' . self::$assetPaths[$alias]);

        $archivos = [];
        foreach ($extensiones as $ext) {
            $glob = glob($directorioImagenes . '*.' . $ext, GLOB_NOSORT);
            if (is_array($glob)) {
                $archivos = array_merge($archivos, $glob);
            }
        }

        if (empty($archivos)) {
            GloryLogger::warning("AssetsUtility: No se encontraron imágenes en '{$directorioImagenes}'.");
            return [];
        }

        shuffle($archivos);
        $seleccionados = array_slice($archivos, 0, max(0, $cantidad));
        return array_values(array_map('basename', $seleccionados));
    }


    /**
     * Lista todas las imágenes disponibles para un alias dado, retornando solo los nombres de archivo (basename).
     * Ordena alfabéticamente para obtener una selección determinística.
     *
     * @param string $alias
     * @param array $extensiones
     * @return array<string>
     */
    public static function listImagesForAlias(
        string $alias,
        array $extensiones = ['jpg','jpeg','png','gif','webp','svg']
    ): array {
        if (!self::$isInitialized) self::init();

        if (!isset(self::$assetPaths[$alias])) {
            GloryLogger::error("AssetsUtility: Alias '{$alias}' no registrado para listado de imágenes.");
            return [];
        }

        $directorioImagenes = trailingslashit(get_template_directory() . '/' . self::$assetPaths[$alias]);
        $archivos = [];
        foreach ($extensiones as $ext) {
            $glob = glob($directorioImagenes . '*.' . $ext, GLOB_NOSORT);
            if (is_array($glob)) {
                foreach ($glob as $ruta) {
                    if (is_file($ruta)) {
                        $archivos[] = basename($ruta);
                    }
                }
            }
        }

        if (empty($archivos)) {
            return [];
        }

        sort($archivos, SORT_NATURAL | SORT_FLAG_CASE);
        return array_values($archivos);
    }


    public static function imagen(string $assetReference, array $atributos = []): void
    {
        if (!self::$isInitialized) self::init();

        list($alias, $nombreArchivo) = self::parseAssetReference($assetReference);
        $rutaRelativa = self::resolveAssetPath($alias, $nombreArchivo);

        if (!$rutaRelativa) return;

        $rutaLocal = get_template_directory() . '/' . $rutaRelativa;
        $urlBase = get_template_directory_uri() . '/' . $rutaRelativa;

        $ancho = $alto = null;
        if (file_exists($rutaLocal)) {
            $dimensiones = @getimagesize($rutaLocal);
            if ($dimensiones !== false) {
                [$ancho, $alto] = $dimensiones;
            }
        }

        $urlFinal = function_exists('jetpack_photon_url') ? jetpack_photon_url($urlBase) : $urlBase;

        if (!isset($atributos['alt'])) {
            $atributos['alt'] = ucwords(str_replace(['-', '_'], ' ', pathinfo($nombreArchivo, PATHINFO_FILENAME)));
        }

        if ($ancho && $alto) {
            if (!isset($atributos['width'])) $atributos['width'] = $ancho;
            if (!isset($atributos['height'])) $atributos['height'] = $alto;
        }

        $atributosString = '';
        foreach ($atributos as $clave => $valor) {
            $atributosString .= sprintf(' %s="%s"', esc_attr($clave), esc_attr($valor));
        }

        printf('<img src="%s"%s>', esc_url($urlFinal), $atributosString);
    }

    public static function imagenUrl(string $assetReference): ?string
    {
        if (!self::$isInitialized) self::init();

        list($alias, $nombreArchivo) = self::parseAssetReference($assetReference);
        $rutaRelativa = self::resolveAssetPath($alias, $nombreArchivo);

        if (!$rutaRelativa) {
            return null;
        }
        
        $rutaLocal = get_template_directory() . '/' . $rutaRelativa;
        if (!file_exists($rutaLocal)) {
            GloryLogger::warning("AssetsUtility: El archivo de asset '{$rutaRelativa}' no fue encontrado.");
            return null;
        }

        $urlBase = get_template_directory_uri() . '/' . $rutaRelativa;
        $urlFinal = function_exists('jetpack_photon_url') ? jetpack_photon_url($urlBase) : $urlBase;

        return esc_url($urlFinal);
    }


    public static function get_attachment_id_from_asset(string $assetReference, bool $allowAliasFallback = true): ?int
    {
        if (!self::$isInitialized) self::init();

        $cacheKey = 'glory_asset_id_' . md5($assetReference);
        $cachedId = get_transient($cacheKey);

        if ($cachedId !== false) {
            return $cachedId === 'null' ? null : (int)$cachedId;
        }

        list($alias, $nombreArchivo) = self::parseAssetReference($assetReference);
        $rutaAssetRelativaSolicitada = self::resolveAssetPath($alias, $nombreArchivo);
        $rutaAssetRelativa = $rutaAssetRelativaSolicitada;

        if (!$rutaAssetRelativa) {
            set_transient($cacheKey, 'null', HOUR_IN_SECONDS);
            return null;
        }

        $rutaAssetCompleta = get_template_directory() . '/' . $rutaAssetRelativa;

        if (!file_exists($rutaAssetCompleta)) {
            if ($allowAliasFallback && isset(self::$assetPaths[$alias])) {
                $dirAlias = trailingslashit(get_template_directory() . '/' . self::$assetPaths[$alias]);
                $alt = glob($dirAlias . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE) ?: [];
                if (!empty($alt)) {
                    $idx = abs(crc32($nombreArchivo)) % count($alt);
                    $fallbackFile = $alt[$idx];
                    $nombreArchivo = basename($fallbackFile);
                    $rutaAssetRelativa = self::resolveAssetPath($alias, $nombreArchivo) ?: $rutaAssetRelativaSolicitada;
                    $rutaAssetCompleta = get_template_directory() . '/' . $rutaAssetRelativa;
                }
            }
            if (!file_exists($rutaAssetCompleta)) {
                set_transient($cacheKey, 'null', HOUR_IN_SECONDS);
                return null;
            }
        }

        // Búsqueda ampliada: primero por nuestro meta, y como respaldo por nombre de archivo en _wp_attached_file
        $nombreArchivoBase = basename($rutaAssetRelativa);

        $args = [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'meta_query'     => [
                'relation' => 'OR',
                [
                    'key'     => '_glory_asset_source',
                    'value'   => $rutaAssetRelativa,
                    'compare' => '='
                ],
                [
                    'key'     => '_wp_attached_file',
                    'value'   => $nombreArchivoBase,
                    'compare' => 'LIKE'
                ],
                [
                    'key'     => '_glory_asset_requested',
                    'value'   => $rutaAssetRelativaSolicitada,
                    'compare' => '='
                ],
            ],
        ];
        $query = new \WP_Query($args);

        if ($query->have_posts()) {
            $id = (int) $query->posts[0];

            // Verificar que el archivo físico exista; si no, intentar reparar manteniendo el mismo adjunto
            $rutaAdjunta = get_attached_file($id);
            if (empty($rutaAdjunta) || !file_exists($rutaAdjunta)) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');

                $archivoTemporal = wp_tempnam($nombreArchivo);
                @copy($rutaAssetCompleta, $archivoTemporal);

                $datosArchivo = [
                    'name'     => basename($nombreArchivo),
                    'tmp_name' => $archivoTemporal,
                    'error'    => 0,
                    'size'     => @filesize($rutaAssetCompleta) ?: 0,
                ];
                $subida = wp_handle_sideload($datosArchivo, ['test_form' => false]);

                if (!isset($subida['error']) && isset($subida['file'])) {
                    // Actualizar el archivo asociado al mismo adjunto
                    if (function_exists('update_attached_file')) {
                        update_attached_file($id, $subida['file']);
                    }
                    $meta = wp_generate_attachment_metadata($id, $subida['file']);
                    wp_update_attachment_metadata($id, $meta);
                    update_post_meta($id, '_glory_asset_source', $rutaAssetRelativa);
                    update_post_meta($id, '_glory_asset_requested', $rutaAssetRelativaSolicitada);
                    // MUY IMPORTANTE: actualizar GUID a la nueva URL física
                    if (isset($subida['url'])) {
                        wp_update_post([
                            'ID'   => $id,
                            'guid' => $subida['url'],
                        ]);
                    }
                } else {
                    // Si la reparación falla, importar como nuevo adjunto y devolver su ID
                    $nuevoId = null;
                    $datosArchivo = [
                        'name'     => basename($nombreArchivo),
                        'tmp_name' => $archivoTemporal,
                        'error'    => 0,
                        'size'     => @filesize($rutaAssetCompleta) ?: 0,
                    ];
                    $subida2 = wp_handle_sideload($datosArchivo, ['test_form' => false]);
                    if (!isset($subida2['error']) && isset($subida2['file'])) {
                        $nuevoId = wp_insert_attachment([
                            'guid'           => $subida2['url'],
                            'post_mime_type' => $subida2['type'],
                            'post_title'     => preg_replace('/\.[^.]+$/', '', basename($subida2['file'])),
                            'post_content'   => '',
                            'post_status'    => 'inherit'
                        ], $subida2['file']);
                        if (!is_wp_error($nuevoId)) {
                            $meta2 = wp_generate_attachment_metadata($nuevoId, $subida2['file']);
                            wp_update_attachment_metadata($nuevoId, $meta2);
                            update_post_meta($nuevoId, '_glory_asset_source', $rutaAssetRelativa);
                            update_post_meta($nuevoId, '_glory_asset_requested', $rutaAssetRelativaSolicitada);
                            set_transient($cacheKey, (int) $nuevoId, HOUR_IN_SECONDS);
                            return (int) $nuevoId;
                        }
                    }
                }
            }

            // Asegurar metas de trazabilidad
            if (!metadata_exists('post', $id, '_glory_asset_source')) {
                update_post_meta($id, '_glory_asset_source', $rutaAssetRelativa);
            }
            if (!metadata_exists('post', $id, '_glory_asset_requested')) {
                update_post_meta($id, '_glory_asset_requested', $rutaAssetRelativaSolicitada);
            }

            set_transient($cacheKey, $id, HOUR_IN_SECONDS);
            return $id;
        }

        // Permitir importación en frontend si estamos en modo desarrollo
        if (!is_admin() && !AssetManager::isGlobalDevMode()) {
            return null;
        }

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $archivoTemporal = wp_tempnam($nombreArchivo);
        copy($rutaAssetCompleta, $archivoTemporal);

        $datosArchivo = [
            'name' => basename($nombreArchivo),
            'tmp_name' => $archivoTemporal,
            'error' => 0,
            'size' => filesize($rutaAssetCompleta)
        ];

        $subida = wp_handle_sideload($datosArchivo, ['test_form' => false]);

        if (isset($subida['error'])) {
            GloryLogger::error("AssetsUtility: Error al subir el asset '{$nombreArchivo}'.", ['error' => $subida['error']]);
            if (file_exists($archivoTemporal)) @unlink($archivoTemporal);
            set_transient($cacheKey, 'null', HOUR_IN_SECONDS);
            return null;
        }

        $idAdjunto = wp_insert_attachment([
            'guid' => $subida['url'],
            'post_mime_type' => $subida['type'],
            'post_title' => preg_replace('/\.[^.]+$/', '', basename($subida['file'])),
            'post_content' => '',
            'post_status' => 'inherit'
        ], $subida['file']);

        if (is_wp_error($idAdjunto)) {
            GloryLogger::error("AssetsUtility: Error al insertar el adjunto '{$nombreArchivo}'.", ['error' => $idAdjunto->get_error_message()]);
            set_transient($cacheKey, 'null', HOUR_IN_SECONDS);
            return null;
        }

        $metadatosAdjunto = wp_generate_attachment_metadata($idAdjunto, $subida['file']);
        wp_update_attachment_metadata($idAdjunto, $metadatosAdjunto);
        update_post_meta($idAdjunto, '_glory_asset_source', $rutaAssetRelativa);
        update_post_meta($idAdjunto, '_glory_asset_requested', $rutaAssetRelativaSolicitada);

        GloryLogger::info("AssetsUtility: El asset '{$nombreArchivo}' ha sido importado a la Biblioteca de Medios.", ['attachment_id' => $idAdjunto]);
        set_transient($cacheKey, $idAdjunto, HOUR_IN_SECONDS);

        return $idAdjunto;
    }

    public static function importTemaAssets(): void
    {
        self::importAllFromAlias('tema');
    }

    private static function importAllFromAlias(string $alias): void
    {
        if (!isset(self::$assetPaths[$alias])) {
            return;
        }
        $dir = get_template_directory() . '/' . self::$assetPaths[$alias] . '/';
        if (!is_dir($dir)) {
            return;
        }
        $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        foreach ($extensions as $ext) {
            $files = glob($dir . '*.' . $ext);
            if (!is_array($files)) {
                continue;
            }
            foreach ($files as $file) {
                if (!is_file($file)) {
                    continue;
                }
                $basename = basename($file);
                $assetRef = $alias . '::' . $basename;
                self::get_attachment_id_from_asset($assetRef);
            }
        }
    }
}