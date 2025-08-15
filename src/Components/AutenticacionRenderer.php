<?php
declare(strict_types=1);

namespace Glory\Components;

use function esc_attr;
use function esc_html;
use function esc_url;
use function home_url;
use function is_user_logged_in;
use function wp_login_url;
use function wp_registration_url;

class AutenticacionRenderer
{
    /**
     * Renderiza un formulario de inicio de sesión nativo de WordPress.
     *
     * Opciones soportadas:
     * - 'redirectTo' (string): URL a la que redirigir tras login. Por defecto, home.
     * - 'mostrarRecordarme' (bool): Mostrar checkbox "Recuérdame". Por defecto true.
     * - 'claseWrapper' (string): clases extra para el contenedor.
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
        $html .= '          <label for="user_login" class="etiquetaCampo">Usuario o email</label>';
        $html .= '          <input type="text" name="log" id="user_login" required autocomplete="username" />';
        $html .= '      </div>';
        $html .= '      <div class="formCampo">';
        $html .= '          <label for="user_pass" class="etiquetaCampo">Contraseña</label>';
        $html .= '          <input type="password" name="pwd" id="user_pass" required autocomplete="current-password" />';
        $html .= '      </div>';
        if ($mostrarRecordarme) {
            $html .= '  <div class="formCampo">';
            $html .= '      <label for="rememberme" class="etiquetaCheckbox" style="font-weight:400; display:flex; gap:10px; align-items:center;">';
            $html .= '          <input type="checkbox" name="rememberme" id="rememberme" value="forever" />';
            $html .= '          Recuérdame';
            $html .= '      </label>';
            $html .= '  </div>';
        }
        $html .= '      <input type="hidden" name="redirect_to" value="' . esc_attr($redirectTo) . '" />';
        $html .= '      <button type="submit" class="button button-primary" style="margin-top:10px;">Iniciar sesión</button>';
        $html .= '  </form>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza acceso al registro de WordPress. Devuelve un enlace a la pantalla de registro
     * (si el sitio lo permite) para mantener el componente agnóstico.
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



