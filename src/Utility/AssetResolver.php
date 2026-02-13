<?php

namespace Glory\Utility;

/**
 * Resolución de rutas y búsqueda de assets del tema.
 * Gestiona alias de directorios, resolución flexible de nombres de archivo
 * (case-insensitive, sin extensión) y búsqueda de adjuntos existentes.
 */
class AssetResolver
{
    private static array $assetPaths = [];

    private static bool $isInitialized = false;


    public static function init(): void
    {
        if (self::$isInitialized) {
            return;
        }
        /* Paths propios del framework Glory */
        self::registerAssetPath('glory', 'Glory/assets/images');
        self::registerAssetPath('elements', 'Glory/assets/images/elements');
        self::registerAssetPath('colors', 'Glory/assets/images/colors');
        self::registerAssetPath('logos', 'Glory/assets/images/logos');

        /* Hook para que el proyecto registre sus propios paths sin hardcodear en Glory */
        do_action('glory/register_asset_paths');

        self::$isInitialized = true;
    }


    public static function registerAssetPath(string $alias, string $path): void
    {
        self::$assetPaths[sanitize_key($alias)] = trim($path, '/\\');
    }


    /**
     * Getter para que AssetImporter y AssetLister puedan consultar rutas registradas.
     */
    public static function getAssetPaths(): array
    {
        return self::$assetPaths;
    }


    /**
     * Comprueba si un alias de ruta está registrado.
     */
    public static function hasAlias(string $alias): bool
    {
        return isset(self::$assetPaths[$alias]);
    }


    /**
     * Separa la referencia "alias::archivo" en sus dos componentes.
     * Si no tiene "::", asume alias "glory".
     */
    public static function parseAssetReference(string $reference): array
    {
        if (strpos($reference, '::') !== false) {
            return explode('::', $reference, 2);
        }
        return ['glory', $reference];
    }


    /**
     * Construye la ruta relativa simple alias + nombre de archivo.
     */
    public static function resolveAssetPath(string $alias, string $nombreArchivo): ?string
    {
        if (!isset(self::$assetPaths[$alias])) {
            return null;
        }
        return self::$assetPaths[$alias] . '/' . ltrim($nombreArchivo, '/\\');
    }


    /**
     * Intenta resolver la ruta relativa REAL del asset dentro del alias, aceptando:
     * - Referencias sin extensión (probará varias extensiones comunes)
     * - Diferencias de mayúsculas/minúsculas en el nombre del archivo
     * - Archivos dentro de subdirectorios (ej: 'libros/mi-libro.png')
     * Retorna la ruta relativa con el nombre de archivo real si existe; de lo contrario null.
     */
    public static function resolveActualRelativeAssetPath(string $alias, string $nombreArchivo): ?string
    {
        if (!isset(self::$assetPaths[$alias])) {
            return null;
        }

        $nombreArchivo = wp_normalize_path($nombreArchivo);
        $subDir = dirname($nombreArchivo);
        $basenameSolicitado = basename($nombreArchivo);

        $dirRel = self::$assetPaths[$alias];

        if ($subDir !== '.' && $subDir !== '') {
            $dirRel .= '/' . $subDir;
        }

        $baseDir = trailingslashit(get_template_directory() . '/' . $dirRel);

        if (!is_dir($baseDir)) {
            return null;
        }

        $extensiones = ['svg', 'png', 'jpg', 'jpeg', 'webp', 'gif'];

        /* 1) Coincidencia exacta con el filesystem */
        $directCandidate = $baseDir . $basenameSolicitado;
        if (is_file($directCandidate)) {
            return $dirRel . '/' . $basenameSolicitado;
        }

        $basenameLower = strtolower($basenameSolicitado);
        $poseeExtension = (strpos($basenameSolicitado, '.') !== false);

        /* 2) Búsqueda insensible a mayúsculas/minúsculas */
        if ($poseeExtension) {
            foreach ($extensiones as $ext) {
                $glob = glob($baseDir . '*.' . $ext, GLOB_NOSORT) ?: [];
                foreach ($glob as $ruta) {
                    if (strtolower(basename($ruta)) === $basenameLower) {
                        return $dirRel . '/' . basename($ruta);
                    }
                }
            }
            return null;
        }

        /* 3) Sin extensión: buscar por nombre (filename) y preferir orden de extensiones */
        $needle = strtolower(pathinfo($basenameSolicitado, PATHINFO_FILENAME));
        foreach ($extensiones as $ext) {
            $glob = glob($baseDir . '*.' . $ext, GLOB_NOSORT) ?: [];
            foreach ($glob as $ruta) {
                if (strtolower(pathinfo($ruta, PATHINFO_FILENAME)) === $needle) {
                    return $dirRel . '/' . basename($ruta);
                }
            }
        }

        return null;
    }


    /**
     * Verifica si un asset referido existe físicamente en el tema.
     */
    public static function assetExists(string $assetReference): bool
    {
        self::init();
        list($alias, $nombreArchivo) = self::parseAssetReference($assetReference);
        $rutaRelativa = self::resolveActualRelativeAssetPath($alias, $nombreArchivo);
        if (!$rutaRelativa) {
            return false;
        }
        $rutaLocal = get_template_directory() . '/' . $rutaRelativa;
        return is_file($rutaLocal);
    }


    /**
     * Busca un adjunto existente que corresponda a un asset dado, sin importar/crear nada.
     * Retorna null si no hay un adjunto válido o si el archivo físico del adjunto no existe.
     * Búsqueda ESTRICTA solo por metas de Glory, no usa LIKE.
     */
    public static function findExistingAttachmentIdForAsset(string $assetReference): ?int
    {
        self::init();
        list($alias, $nombreArchivo) = self::parseAssetReference($assetReference);
        $rutaRelativaSolicitada = self::resolveAssetPath($alias, $nombreArchivo);
        if (!$rutaRelativaSolicitada) {
            return null;
        }

        $args = [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'meta_query'     => [
                'relation' => 'OR',
                ['key' => '_glory_asset_source',    'value' => $rutaRelativaSolicitada, 'compare' => '='],
                ['key' => '_glory_asset_requested', 'value' => $rutaRelativaSolicitada, 'compare' => '='],
            ],
        ];
        $q = new \WP_Query($args);
        if ($q->have_posts()) {
            $id = (int) $q->posts[0];
            $file = get_attached_file($id);
            if ($file && file_exists($file)) {
                return $id;
            }
        }
        return null;
    }
}
