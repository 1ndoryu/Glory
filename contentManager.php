<?php
# App/Glory/ContentManager.php

namespace App\Glory;

class ContentManager
{
    /**
     * Prefijo usado para guardar las opciones en la base de datos.
     * Ayuda a evitar colisiones de nombres.
     */
    private const OPTION_PREFIX = 'glory_content_';

    /**
     * Obtiene el valor de un contenido editable.
     *
     * Busca una opción en la base de datos con el prefijo y la clave dada.
     * Si no se encuentra o está vacío, devuelve el valor por defecto.
     *
     * @param string $key      Identificador único para este contenido (e.g., 'tituloHome', 'descripcionFooter').
     * @param string $default  Valor a devolver si el contenido no ha sido definido en las opciones.
     * @param bool   $escape   (Opcional) Si es true, escapa el valor para salida segura en HTML. Default: true.
     *
     * @return string El valor del contenido (escapado por defecto) o el valor por defecto.
     */
    public static function get(string $key, string $default = '', bool $escape = true): string
    {
        $option_name = self::OPTION_PREFIX . $key;
        $value = get_option($option_name);

        // Si no hay valor o está vacío, usamos el default.
        // `get_option` devuelve `false` si la opción no existe.
        if ($value === false || $value === '') {
            $value = $default;
        }

        // Escapar para seguridad por defecto al imprimir en HTML
        if ($escape) {
            // Puedes elegir la función de escape más adecuada.
            // `esc_html` para texto dentro de etiquetas HTML.
            // `esc_attr` para atributos HTML.
            // `esc_textarea` para textareas.
            // `wp_kses_post` si necesitas permitir algo de HTML seguro.
            // Usaremos esc_html como un buen punto de partida general.
            return esc_html($value);
        } else {
            // Devolver el valor crudo si no se requiere escapar.
            return $value;
        }
    }

    /**
     * Método de ayuda específico para obtener texto simple (siempre escapado).
     * Es un alias de get() con $escape = true.
     *
     * @param string $key      Identificador único.
     * @param string $default  Valor por defecto.
     * @return string Texto escapado.
     */
    public static function text(string $key, string $default = ''): string
    {
        return self::get($key, $default, true);
    }

    /**
     * Método de ayuda específico para obtener texto que podría contener HTML simple y seguro.
     * Usa wp_kses_post para limpiar.
     *
     * @param string $key      Identificador único.
     * @param string $default  Valor por defecto.
     * @return string Texto sanitizado con HTML permitido por wp_kses_post.
     */
    public static function richText(string $key, string $default = ''): string
    {
        $value = self::get($key, $default, false); // Obtenemos valor crudo
        return wp_kses_post($value); // Limpiamos permitiendo HTML seguro
    }

    // --- Futuros métodos podrían ir aquí ---
    // public static function image(...) {}
    // public static function url(...) {}
}
