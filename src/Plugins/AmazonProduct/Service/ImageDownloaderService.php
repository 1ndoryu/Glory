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

        // Descargar imagen a archivo temporal usando proxy si esta configurado
        $tempFile = $this->downloadWithProxy($imageUrl);

        if (is_wp_error($tempFile) || $tempFile === false) {
            $errorMsg = is_wp_error($tempFile) ? $tempFile->get_error_message() : 'Error desconocido';
            error_log('ImageDownloader: Error descargando imagen - ' . $errorMsg);
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

    /**
     * Descarga una imagen usando el proxy configurado.
     * 
     * @param string $url URL de la imagen
     * @return string|false Ruta al archivo temporal o false si falla
     */
    private function downloadWithProxy(string $url): string|false
    {
        // Crear archivo temporal
        $tempFile = wp_tempnam($url);
        if (!$tempFile) {
            return false;
        }

        $ch = curl_init();

        // Configuracion del proxy (igual que WebScraperProvider)
        $proxy = defined('GLORY_PROXY_HOST')
            ? GLORY_PROXY_HOST
            : get_option('amazon_scraper_proxy', '');

        $proxyAuth = defined('GLORY_PROXY_AUTH')
            ? GLORY_PROXY_AUTH
            : get_option('amazon_scraper_proxy_auth', '');

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            CURLOPT_HTTPHEADER => [
                'Accept: image/webp,image/apng,image/*,*/*;q=0.8',
                'Accept-Language: es-ES,es;q=0.9,en;q=0.8',
                'Referer: https://www.amazon.es/',
            ],
        ];

        // Configurar proxy si esta disponible
        if (!empty($proxy)) {
            $options[CURLOPT_PROXY] = $proxy;
            $options[CURLOPT_FRESH_CONNECT] = true;
            $options[CURLOPT_FORBID_REUSE] = true;

            if (!empty($proxyAuth)) {
                // Generar sessid unico para rotacion de IP
                $sessionId = bin2hex(random_bytes(8));
                [$proxyUser, $proxyPass] = explode(':', $proxyAuth, 2);
                $proxyAuth = "{$proxyUser};sessid.{$sessionId}:{$proxyPass}";
                $options[CURLOPT_PROXYUSERPWD] = $proxyAuth;
            }
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if (!empty($curlError) || $httpCode !== 200 || empty($response)) {
            @unlink($tempFile);
            error_log("ImageDownloader: cURL error (HTTP {$httpCode}): {$curlError}");
            return false;
        }

        // Guardar contenido en archivo temporal
        $written = file_put_contents($tempFile, $response);
        if ($written === false) {
            @unlink($tempFile);
            return false;
        }

        return $tempFile;
    }
}
