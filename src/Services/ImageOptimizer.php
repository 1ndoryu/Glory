<?php

namespace Glory\Services;

/**
 * ImageOptimizer - Optimiza imagenes automaticamente al subirlas
 * 
 * Este servicio comprime las imagenes JPEG, PNG y WebP al momento
 * de subirlas a WordPress, reduciendo el tamano del archivo sin
 * perder calidad perceptible.
 * 
 * Uso:
 *   ImageOptimizer::register(); // En functions.php o un hook init
 * 
 * Configuracion via GloryFeatures:
 *   'imageOptimizer' => [
 *       'quality' => 82,           // Calidad JPEG/WebP (1-100)
 *       'maxWidth' => 2560,        // Ancho maximo en pixeles
 *       'maxHeight' => 2560,       // Alto maximo en pixeles  
 *       'convertToWebp' => false,  // Convertir a WebP (futuro)
 *   ]
 */
class ImageOptimizer
{
    private static bool $registered = false;

    /* Configuracion por defecto */
    private static array $defaultConfig = [
        'quality' => 82,
        'maxWidth' => 2560,
        'maxHeight' => 2560,
        'pngQuality' => 8, // 0-9, menor = mejor calidad
        'convertToWebp' => false,
    ];

    /**
     * Registra los hooks para optimizacion automatica
     */
    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        // Hook que se ejecuta despues de subir una imagen
        add_filter('wp_handle_upload', [self::class, 'optimizeOnUpload'], 10, 2);

        // Hook para optimizar los thumbnails generados
        add_filter('wp_generate_attachment_metadata', [self::class, 'optimizeThumbnails'], 10, 2);

        self::$registered = true;
    }

    /**
     * Optimiza la imagen original al subirla
     */
    public static function optimizeOnUpload(array $upload, string $context = 'upload'): array
    {
        if ($context !== 'upload' || empty($upload['file'])) {
            return $upload;
        }

        $filePath = $upload['file'];
        $mimeType = $upload['type'] ?? '';

        // Solo procesar imagenes
        if (!self::isOptimizableImage($mimeType)) {
            return $upload;
        }

        $config = self::getConfig();

        // Optimizar segun el tipo
        $optimized = match ($mimeType) {
            'image/jpeg' => self::optimizeJpeg($filePath, $config),
            'image/png' => self::optimizePng($filePath, $config),
            'image/webp' => self::optimizeWebp($filePath, $config),
            default => false
        };

        if ($optimized) {
            // Actualizar el tamano del archivo en el resultado
            $upload['size'] = filesize($filePath);
        }

        return $upload;
    }

    /**
     * Optimiza los thumbnails generados por WordPress
     */
    public static function optimizeThumbnails(array $metadata, int $attachmentId): array
    {
        if (empty($metadata['sizes'])) {
            return $metadata;
        }

        $uploadDir = wp_upload_dir();
        $basePath = trailingslashit($uploadDir['basedir']);

        // Si hay subdirectorio en el archivo original, usarlo
        $fileSubdir = '';
        if (!empty($metadata['file'])) {
            $fileSubdir = dirname($metadata['file']);
            if ($fileSubdir !== '.') {
                $basePath .= trailingslashit($fileSubdir);
            }
        }

        $mimeType = get_post_mime_type($attachmentId);
        $config = self::getConfig();

        foreach ($metadata['sizes'] as $sizeName => $sizeData) {
            if (empty($sizeData['file'])) {
                continue;
            }

            $thumbPath = $basePath . $sizeData['file'];

            if (!file_exists($thumbPath)) {
                continue;
            }

            match ($mimeType) {
                'image/jpeg' => self::optimizeJpeg($thumbPath, $config),
                'image/png' => self::optimizePng($thumbPath, $config),
                'image/webp' => self::optimizeWebp($thumbPath, $config),
                default => null
            };
        }

        return $metadata;
    }

    /**
     * Verifica si el tipo de imagen es optimizable
     */
    private static function isOptimizableImage(string $mimeType): bool
    {
        return in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true);
    }

    /**
     * Obtiene la configuracion del optimizador
     */
    private static function getConfig(): array
    {
        $config = self::$defaultConfig;

        // GloryFeatures solo maneja booleanos (activado/desactivado)
        // La configuracion especifica debe definirse aqui o via hooks en el futuro.
        if (class_exists('\Glory\Core\GloryFeatures') && !\Glory\Core\GloryFeatures::isEnabled('imageOptimizer')) {
            // Si la feature esta explicitamente desactivada, podriamos retornar config vacia o manejarlo antes.
            // Por ahora, asumimos que si se llama a esta clase, se quiere usar.
        }

        return $config;
    }

    /**
     * Optimiza imagen JPEG
     */
    private static function optimizeJpeg(string $filePath, array $config): bool
    {
        if (!function_exists('imagecreatefromjpeg')) {
            return false;
        }

        $image = @imagecreatefromjpeg($filePath);
        if (!$image) {
            return false;
        }

        // Redimensionar si excede el tamano maximo
        $image = self::resizeIfNeeded($image, $config['maxWidth'], $config['maxHeight']);

        // Guardar con la calidad especificada
        $result = imagejpeg($image, $filePath, $config['quality']);
        imagedestroy($image);

        return $result;
    }

    /**
     * Optimiza imagen PNG
     */
    private static function optimizePng(string $filePath, array $config): bool
    {
        if (!function_exists('imagecreatefrompng')) {
            return false;
        }

        $image = @imagecreatefrompng($filePath);
        if (!$image) {
            return false;
        }

        // Preservar transparencia
        imagesavealpha($image, true);
        imagealphablending($image, false);

        // Redimensionar si excede el tamano maximo
        $image = self::resizeIfNeeded($image, $config['maxWidth'], $config['maxHeight']);

        // Guardar con compresion
        $result = imagepng($image, $filePath, $config['pngQuality']);
        imagedestroy($image);

        return $result;
    }

    /**
     * Optimiza imagen WebP
     */
    private static function optimizeWebp(string $filePath, array $config): bool
    {
        if (!function_exists('imagecreatefromwebp')) {
            return false;
        }

        $image = @imagecreatefromwebp($filePath);
        if (!$image) {
            return false;
        }

        // Redimensionar si excede el tamano maximo
        $image = self::resizeIfNeeded($image, $config['maxWidth'], $config['maxHeight']);

        // Guardar con la calidad especificada
        $result = imagewebp($image, $filePath, $config['quality']);
        imagedestroy($image);

        return $result;
    }

    /**
     * Redimensiona la imagen si excede los limites
     */
    private static function resizeIfNeeded($image, int $maxWidth, int $maxHeight)
    {
        $width = imagesx($image);
        $height = imagesy($image);

        // Si no excede los limites, retornar sin cambios
        if ($width <= $maxWidth && $height <= $maxHeight) {
            return $image;
        }

        // Calcular nuevas dimensiones manteniendo proporcion
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = (int) round($width * $ratio);
        $newHeight = (int) round($height * $ratio);

        // Crear imagen redimensionada
        $resized = imagecreatetruecolor($newWidth, $newHeight);

        // Preservar transparencia para PNG/WebP
        imagealphablending($resized, false);
        imagesavealpha($resized, true);

        // Redimensionar con alta calidad
        imagecopyresampled(
            $resized,
            $image,
            0,
            0,
            0,
            0,
            $newWidth,
            $newHeight,
            $width,
            $height
        );

        imagedestroy($image);
        return $resized;
    }
}
