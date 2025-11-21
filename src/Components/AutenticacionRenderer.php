<?php
declare(strict_types=1);

/**
 * Renderizador de Autenticación
 *
 * Proporciona formularios preconstruidos para inicio de sesión y enlaces de registro,
 * manteniendo la lógica de presentación separada de las plantillas.
 *
 * @package Glory\Components
 */

namespace Glory\Components;

use function esc_attr;
use function esc_html;
use function esc_url;
use function home_url;
use function is_user_logged_in;
use function wp_login_url;
use function wp_registration_url;

/**
 * Clase AutenticacionRenderer.
 *
 * Componentes UI para login y registro.
 */
class AutenticacionRenderer
{
    /**
     * Renderiza un formulario de inicio de sesión nativo de WordPress.
     *
     * Opciones soportadas:
     * - 'redirectTo' (string): URL a la que redirigir tras login. Por defecto, home.
     * - 'mostrarRecordarme' (bool): Mostrar checkbox "Recuérdame". Por defecto true.
     * - 'claseWrapper' (string): clases extra para el contenedor.
     *
     * @param array $opciones Configuración del formulario.
     * @return string HTML del formulario.
     */
    public static function renderLogin(array $opciones = []): string
    {
        if (is_user_logged_in()) {
            return '';
        }

        $redirectTo = isset($opciones['redirectTo']) && is_string($opciones['redirectTo'])
            ? $opciones['redirectTo']
            : home_url('/');

        $mostrarRecordarme = array_key_exists('mostrarRecordarme', $opciones)
            ? (bool) $opciones['mostrarRecordarme']
            : true;

        $claseWrapper = isset($opciones['claseWrapper']) && is_string($opciones['claseWrapper'])
            ? $opciones['claseWrapper']
            : '';

        $action = wp_login_url($redirectTo);

        $html  = '<div class="gloryAuthForm ' . esc_attr($claseWrapper) . '">';
        $html .= '  <form method="post" action="' . esc_url($action) . '" class="gloryForm formularioLogin">';
        $html .= '      <div class="formCampo">';
        $html .= '          <label for="user_login" class="etiquetaCampo">' . esc_html__('Usuario o email', 'glory') . '</label>';
        $html .= '          <input type="text" name="log" id="user_login" required autocomplete="username" />';
        $html .= '      </div>';
        $html .= '      <div class="formCampo">';
        $html .= '          <label for="user_pass" class="etiquetaCampo">' . esc_html__('Contraseña', 'glory') . '</label>';
        $html .= '          <input type="password" name="pwd" id="user_pass" required autocomplete="current-password" />';
        $html .= '      </div>';
        if ($mostrarRecordarme) {
            $html .= '  <div class="formCampo">';
            $html .= '      <label for="rememberme" class="etiquetaCheckbox" style="font-weight:400; display:flex; gap:10px; align-items:center;">';
            $html .= '          <input type="checkbox" name="rememberme" id="rememberme" value="forever" />';
            $html .= '          ' . esc_html__('Recuérdame', 'glory');
            $html .= '      </label>';
            $html .= '  </div>';
        }
        $html .= '      <input type="hidden" name="redirect_to" value="' . esc_attr($redirectTo) . '" />';
        $html .= '      <button type="submit" class="button button-primary" style="margin-top:10px;">' . esc_html__('Iniciar sesión', 'glory') . '</button>';
        $html .= '  </form>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza acceso al registro de WordPress.
     *
     * Devuelve un enlace a la pantalla de registro (si el sitio lo permite)
     * para mantener el componente agnóstico.
     *
     * @param string $texto Texto del enlace.
     * @return string HTML del enlace de registro.
     */
    public static function renderRegistroEnlace(string $texto = 'Crear cuenta'): string
    {
        $url = wp_registration_url();
        if (!$url) {
            return '';
        }
        return '<div class="gloryAuthRegistro"><a class="button" href="' . esc_url($url) . '">' . esc_html($texto) . '</a></div>';
    }
}
