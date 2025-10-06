<?php

namespace Glory\Services;

use Glory\Core\GloryLogger;

/**
 * Clase para realizar reemplazo inteligente de URLs en la base de datos.
 * Maneja todos los casos especiales de WordPress:
 * - Datos serializados de PHP
 * - JSON
 * - URLs con y sin protocolo
 * - URLs con y sin trailing slash
 */
class DatabaseUrlReplacer
{
    private string $oldUrl;
    private string $newUrl;
    private array $oldUrlVariations = [];
    private array $newUrlVariations = [];
    
    /**
     * Constructor
     * 
     * @param string $oldUrl URL antigua
     * @param string $newUrl URL nueva
     */
    public function __construct(string $oldUrl, string $newUrl)
    {
        $this->oldUrl = $this->normalizeUrl($oldUrl);
        $this->newUrl = $this->normalizeUrl($newUrl);
        $this->generateUrlVariations();
    }

    /**
     * Normaliza una URL eliminando espacios y slashes finales innecesarios
     * 
     * @param string $url
     * @return string
     */
    private function normalizeUrl(string $url): string
    {
        $url = trim($url);
        // Eliminar trailing slash solo si no es la raíz del dominio
        if (preg_match('#^https?://[^/]+/$#', $url)) {
            return $url; // Es solo el dominio, mantener el slash
        }
        return rtrim($url, '/');
    }

    /**
     * Genera todas las variaciones posibles de las URLs
     * para asegurar reemplazo completo
     */
    private function generateUrlVariations(): void
    {
        // Parsear URLs
        $oldParsed = parse_url($this->oldUrl);
        $newParsed = parse_url($this->newUrl);

        // Variaciones de la URL antigua
        $this->oldUrlVariations = [
            'full' => $this->oldUrl,
            'full_slash' => $this->oldUrl . '/',
            'no_protocol' => str_replace(['https://', 'http://'], '', $this->oldUrl),
            'no_protocol_slash' => str_replace(['https://', 'http://'], '', $this->oldUrl) . '/',
            'double_slash' => '//' . str_replace(['https://', 'http://'], '', $this->oldUrl),
            'double_slash_slash' => '//' . str_replace(['https://', 'http://'], '', $this->oldUrl) . '/',
        ];

        // Si la URL antigua tiene HTTP, agregar variación HTTPS
        if (isset($oldParsed['scheme']) && $oldParsed['scheme'] === 'http') {
            $this->oldUrlVariations['https'] = str_replace('http://', 'https://', $this->oldUrl);
            $this->oldUrlVariations['https_slash'] = str_replace('http://', 'https://', $this->oldUrl) . '/';
        }

        // Variaciones de la URL nueva
        $this->newUrlVariations = [
            'full' => $this->newUrl,
            'full_slash' => $this->newUrl . '/',
            'no_protocol' => str_replace(['https://', 'http://'], '', $this->newUrl),
            'no_protocol_slash' => str_replace(['https://', 'http://'], '', $this->newUrl) . '/',
            'double_slash' => '//' . str_replace(['https://', 'http://'], '', $this->newUrl),
            'double_slash_slash' => '//' . str_replace(['https://', 'http://'], '', $this->newUrl) . '/',
        ];

        if (isset($newParsed['scheme']) && $newParsed['scheme'] === 'https') {
            $this->newUrlVariations['https'] = $this->newUrl;
            $this->newUrlVariations['https_slash'] = $this->newUrl . '/';
        }
    }

    /**
     * Reemplaza URLs en un valor, manejando datos serializados y JSON
     * 
     * @param mixed $data
     * @return mixed
     */
    public function replace($data)
    {
        // Si es null o vacío, retornar tal cual
        if (is_null($data) || $data === '') {
            return $data;
        }

        // Si es un array u objeto, procesar recursivamente
        if (is_array($data) || is_object($data)) {
            return $this->replaceRecursive($data);
        }

        // Si es string, verificar si es serializado o JSON
        if (is_string($data)) {
            // Intentar detectar y procesar datos serializados
            if ($this->isSerialized($data)) {
                return $this->replaceInSerialized($data);
            }

            // Intentar detectar y procesar JSON
            if ($this->isJson($data)) {
                return $this->replaceInJson($data);
            }

            // Si es string simple, reemplazar directamente
            return $this->replaceInString($data);
        }

        // Para otros tipos (int, float, bool), retornar tal cual
        return $data;
    }

    /**
     * Reemplaza URLs en estructuras recursivas (arrays y objetos)
     * 
     * @param mixed $data
     * @return mixed
     */
    private function replaceRecursive($data)
    {
        if (is_array($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                // También reemplazar en las claves si son strings
                $newKey = is_string($key) ? $this->replaceInString($key) : $key;
                $result[$newKey] = $this->replace($value);
            }
            return $result;
        }

        if (is_object($data)) {
            $object = clone $data;
            foreach ($object as $key => $value) {
                $newKey = is_string($key) ? $this->replaceInString($key) : $key;
                if ($newKey !== $key) {
                    unset($object->$key);
                }
                $object->$newKey = $this->replace($value);
            }
            return $object;
        }

        return $data;
    }

    /**
     * Reemplaza URLs en string serializado de PHP
     * 
     * @param string $data
     * @return string
     */
    private function replaceInSerialized(string $data): string
    {
        try {
            $unserialized = @unserialize($data);
            
            if ($unserialized === false && $data !== 'b:0;') {
                // No se pudo deserializar, intentar reemplazo directo
                return $this->replaceInString($data);
            }

            // Reemplazar recursivamente en los datos deserializados
            $replaced = $this->replace($unserialized);

            // Serializar de nuevo
            $serialized = serialize($replaced);

            return $serialized;
        } catch (\Exception $e) {
            GloryLogger::error("Error al procesar datos serializados: " . $e->getMessage());
            // Fallback a reemplazo directo
            return $this->replaceInString($data);
        }
    }

    /**
     * Reemplaza URLs en string JSON
     * 
     * @param string $data
     * @return string
     */
    private function replaceInJson(string $data): string
    {
        try {
            $decoded = json_decode($data, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                // No es JSON válido, intentar reemplazo directo
                return $this->replaceInString($data);
            }

            // Reemplazar recursivamente
            $replaced = $this->replace($decoded);

            // Codificar de nuevo a JSON
            $encoded = json_encode($replaced, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            return $encoded !== false ? $encoded : $data;
        } catch (\Exception $e) {
            GloryLogger::error("Error al procesar JSON: " . $e->getMessage());
            return $this->replaceInString($data);
        }
    }

    /**
     * Reemplaza todas las variaciones de URL en un string simple
     * 
     * @param string $data
     * @return string
     */
    private function replaceInString(string $data): string
    {
        $result = $data;

        // Reemplazar cada variación de la URL antigua con su correspondiente nueva
        foreach ($this->oldUrlVariations as $type => $oldVariation) {
            if (isset($this->newUrlVariations[$type])) {
                $result = str_replace($oldVariation, $this->newUrlVariations[$type], $result);
            }
        }

        return $result;
    }

    /**
     * Verifica si un string es un dato serializado de PHP
     * 
     * @param string $data
     * @return bool
     */
    private function isSerialized(string $data): bool
    {
        // Verificar si empieza con patrones típicos de serialización
        if (!preg_match('/^([adObis]):/', $data)) {
            return false;
        }

        // Intentar deserializar
        $unserialized = @unserialize($data);
        return $unserialized !== false || $data === 'b:0;';
    }

    /**
     * Verifica si un string es JSON válido
     * 
     * @param string $data
     * @return bool
     */
    private function isJson(string $data): bool
    {
        // JSON debe empezar con { o [
        if (!preg_match('/^[\[{]/', trim($data))) {
            return false;
        }

        json_decode($data);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Obtiene las variaciones de URL para logging o debug
     * 
     * @return array
     */
    public function getUrlVariations(): array
    {
        return [
            'old' => $this->oldUrlVariations,
            'new' => $this->newUrlVariations,
        ];
    }
}

