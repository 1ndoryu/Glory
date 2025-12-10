<?php

namespace Glory\Plugins\AmazonProduct\Service;

/**
 * Servicio para descargar imagenes externas y guardarlas en la biblioteca de medios de WordPress.
 */
class ImageDownloaderService
{
    /**
     * Descarga una imagen desde una URL y la guarda en la biblioteca de medios.
     * 
     * @param string $imageUrl URL de la imagen a descargar
     * @param int $postId ID del post al que asociar la imagen (opcional)
     * @param string $title Titulo/nombre para la imagen
     * @return int|false Attachment ID on success, false on failure
     */
    public function downloadAndSave(string $imageUrl, int $postId = 0, string $title = ''): int|false
    {
        if (empty($imageUrl)) {
            return false;
        }

        // Requerir funciones de WordPress para manejo de medios
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // Descargar imagen a archivo temporal
        $tempFile = download_url($imageUrl, 30);

        if (is_wp_error($tempFile)) {
            error_log('ImageDownloader: Error descargando imagen - ' . $tempFile->get_error_message());
            return false;
        }

        // Obtener extension del archivo
        $extension = $this->getImageExtension($imageUrl, $tempFile);

        // Generar nombre de archivo limpio
        $filename = $this->generateFilename($title, $extension);

        // Preparar array de archivo para wp_handle_sideload
        $fileArray = [
            'name' => $filename,
            'tmp_name' => $tempFile
        ];

        // Subir a la biblioteca de medios
        $attachmentId = media_handle_sideload($fileArray, $postId, $title);

        // Limpiar archivo temporal si aun existe
        if (file_exists($tempFile)) {
            @unlink($tempFile);
        }

        if (is_wp_error($attachmentId)) {
            error_log('ImageDownloader: Error subiendo imagen - ' . $attachmentId->get_error_message());
            return false;
        }

        return $attachmentId;
    }

    /**
     * Descarga imagen y la asigna como Featured Image (thumbnail) del post.
     * 
     * @param string $imageUrl URL de la imagen
     * @param int $postId ID del post
     * @param string $title Titulo para la imagen
     * @return int|false Attachment ID on success, false on failure
     */
    public function downloadAndSetAsThumbnail(string $imageUrl, int $postId, string $title = ''): int|false
    {
        $attachmentId = $this->downloadAndSave($imageUrl, $postId, $title);

        if ($attachmentId) {
            set_post_thumbnail($postId, $attachmentId);

            // Tambien guardar la URL original como referencia
            update_post_meta($postId, '_original_image_url', $imageUrl);
        }

        return $attachmentId;
    }

    /**
     * Verifica si ya existe una imagen descargada para este post.
     * 
     * @param int $postId
     * @return bool
     */
    public function hasLocalImage(int $postId): bool
    {
        return has_post_thumbnail($postId);
    }

    /**
     * Obtiene la extension correcta de la imagen.
     */
    private function getImageExtension(string $url, string $tempFile): string
    {
        // Intento 1: Obtener del content-type del archivo descargado
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $tempFile);
        finfo_close($finfo);

        $mimeToExt = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp'
        ];

        if (isset($mimeToExt[$mimeType])) {
            return $mimeToExt[$mimeType];
        }

        // Intento 2: Extraer de la URL
        if (preg_match('/\.(jpe?g|png|gif|webp)/i', $url, $matches)) {
            return strtolower($matches[1]);
        }

        // Default
        return 'jpg';
    }

    /**
     * Genera un nombre de archivo limpio basado en el titulo.
     */
    private function generateFilename(string $title, string $extension): string
    {
        if (empty($title)) {
            $title = 'amazon-product';
        }

        // Limpiar titulo para usar como nombre de archivo
        $filename = sanitize_file_name($title);
        $filename = preg_replace('/[^a-zA-Z0-9-_]/', '-', $filename);
        $filename = preg_replace('/-+/', '-', $filename);
        $filename = trim($filename, '-');
        $filename = substr($filename, 0, 50); // Limitar longitud

        // Agregar timestamp para evitar colisiones
        $filename .= '-' . time();

        return $filename . '.' . $extension;
    }
}
