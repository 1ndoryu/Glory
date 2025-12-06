<?php

namespace Glory\Gbn\Services;

/**
 * TemplateService - Gestión de templates de Header y Footer para GBN.
 * 
 * Maneja la persistencia de templates en wp_options y su renderizado
 * condicional cuando GBN está activo.
 * 
 * @package Glory\Gbn\Services
 */
class TemplateService
{
    /** @var string Clave de opción para el template del header */
    public const HEADER_OPTION_KEY = 'gbn_header_template';

    /** @var string Clave de opción para el template del footer */
    public const FOOTER_OPTION_KEY = 'gbn_footer_template';

    /** @var string Clave de opción para la configuración del header */
    public const HEADER_CONFIG_KEY = 'gbn_header_config';

    /** @var string Clave de opción para la configuración del footer */
    public const FOOTER_CONFIG_KEY = 'gbn_footer_config';

    /**
     * Guarda el template del header en wp_options.
     * 
     * @param string $html HTML del template
     * @param array $config Configuración del componente
     * @return bool True si se guardó correctamente
     */
    public static function saveHeader(string $html, array $config = []): bool
    {
        $savedHtml = update_option(self::HEADER_OPTION_KEY, $html, false);
        $savedConfig = update_option(self::HEADER_CONFIG_KEY, $config, false);
        
        // Limpiar cache si existe
        if (function_exists('wp_cache_delete')) {
            wp_cache_delete(self::HEADER_OPTION_KEY, 'options');
            wp_cache_delete(self::HEADER_CONFIG_KEY, 'options');
        }
        
        return $savedHtml || $savedConfig;
    }

    /**
     * Guarda el template del footer en wp_options.
     * 
     * @param string $html HTML del template
     * @param array $config Configuración del componente
     * @return bool True si se guardó correctamente
     */
    public static function saveFooter(string $html, array $config = []): bool
    {
        $savedHtml = update_option(self::FOOTER_OPTION_KEY, $html, false);
        $savedConfig = update_option(self::FOOTER_CONFIG_KEY, $config, false);
        
        if (function_exists('wp_cache_delete')) {
            wp_cache_delete(self::FOOTER_OPTION_KEY, 'options');
            wp_cache_delete(self::FOOTER_CONFIG_KEY, 'options');
        }
        
        return $savedHtml || $savedConfig;
    }

    /**
     * Obtiene el template del header desde wp_options.
     * 
     * @return array ['html' => string, 'config' => array]
     */
    public static function getHeader(): array
    {
        return [
            'html' => get_option(self::HEADER_OPTION_KEY, ''),
            'config' => get_option(self::HEADER_CONFIG_KEY, [])
        ];
    }

    /**
     * Obtiene el template del footer desde wp_options.
     * 
     * @return array ['html' => string, 'config' => array]
     */
    public static function getFooter(): array
    {
        return [
            'html' => get_option(self::FOOTER_OPTION_KEY, ''),
            'config' => get_option(self::FOOTER_CONFIG_KEY, [])
        ];
    }

    /**
     * Verifica si existe un template de header guardado.
     * 
     * @return bool
     */
    public static function hasHeader(): bool
    {
        $header = self::getHeader();
        return !empty($header['html']);
    }

    /**
     * Verifica si existe un template de footer guardado.
     * 
     * @return bool
     */
    public static function hasFooter(): bool
    {
        $footer = self::getFooter();
        return !empty($footer['html']);
    }

    /**
     * Renderiza el header GBN si existe, o devuelve false.
     * 
     * @return string|false HTML del header o false si no existe
     */
    public static function renderHeader()
    {
        if (!self::hasHeader()) {
            return false;
        }

        $header = self::getHeader();
        $html = $header['html'];

        // Procesar shortcodes en el template
        $html = do_shortcode($html);

        // Aplicar filtro para extensibilidad
        $html = apply_filters('gbn_header_output', $html, $header['config']);

        return $html;
    }

    /**
     * Renderiza el footer GBN si existe, o devuelve false.
     * 
     * @return string|false HTML del footer o false si no existe
     */
    public static function renderFooter()
    {
        if (!self::hasFooter()) {
            return false;
        }

        $footer = self::getFooter();
        $html = $footer['html'];

        // Reemplazar placeholders dinámicos
        $html = self::processFooterPlaceholders($html);

        // Procesar shortcodes
        $html = do_shortcode($html);

        // Aplicar filtro para extensibilidad
        $html = apply_filters('gbn_footer_output', $html, $footer['config']);

        return $html;
    }

    /**
     * Procesa placeholders dinámicos en el footer.
     * 
     * @param string $html
     * @return string
     */
    private static function processFooterPlaceholders(string $html): string
    {
        $replacements = [
            '{year}' => date('Y'),
            '{siteName}' => get_bloginfo('name'),
            '{siteUrl}' => home_url('/'),
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $html
        );
    }

    /**
     * Elimina el template del header.
     * 
     * @return bool
     */
    public static function deleteHeader(): bool
    {
        $deleted1 = delete_option(self::HEADER_OPTION_KEY);
        $deleted2 = delete_option(self::HEADER_CONFIG_KEY);
        return $deleted1 || $deleted2;
    }

    /**
     * Elimina el template del footer.
     * 
     * @return bool
     */
    public static function deleteFooter(): bool
    {
        $deleted1 = delete_option(self::FOOTER_OPTION_KEY);
        $deleted2 = delete_option(self::FOOTER_CONFIG_KEY);
        return $deleted1 || $deleted2;
    }

    /**
     * Obtiene el template por defecto para el header.
     * Carga desde gbn-header-template.php en la raíz del tema.
     * 
     * @return string
     */
    public static function getDefaultHeaderTemplate(): string
    {
        $templatePath = get_template_directory() . '/gbn-header-template.php';
        
        if (file_exists($templatePath)) {
            ob_start();
            include $templatePath;
            return ob_get_clean();
        }
        
        // Fallback si el archivo no existe
        $siteTitle = get_bloginfo('name');
        $homeUrl = esc_url(home_url('/'));

        return '<header gloryHeader class="gbn-header siteMenuW" role="banner">
    <div class="siteMenuContainer">
        <div gloryLogo class="siteMenuLogo">
            <a href="' . $homeUrl . '" rel="home" class="gbn-logo-link">
                <span class="gbn-logo-text">' . esc_html($siteTitle) . '</span>
            </a>
        </div>
        <nav gloryMenu class="siteMenuNav" role="navigation">
            <ul class="menu menu-level-1 gbn-menu-list">
                <li class="gbn-menu-item"><a href="' . $homeUrl . '">Inicio</a></li>
                <li class="gbn-menu-item"><a href="#">Servicios</a></li>
                <li class="gbn-menu-item"><a href="#">Nosotros</a></li>
                <li class="gbn-menu-item"><a href="#">Contacto</a></li>
            </ul>
        </nav>
        <button aria-label="Toggle menu" class="burger" type="button">
            <span></span>
            <span></span>
        </button>
    </div>
</header>';
    }

    /**
     * Obtiene el template por defecto para el footer.
     * Carga desde gbn-footer-template.php en la raíz del tema.
     * 
     * @return string
     */
    public static function getDefaultFooterTemplate(): string
    {
        $templatePath = get_template_directory() . '/gbn-footer-template.php';
        
        if (file_exists($templatePath)) {
            ob_start();
            include $templatePath;
            return ob_get_clean();
        }
        
        // Fallback si el archivo no existe
        $siteTitle = get_bloginfo('name');
        $year = date('Y');

        return '<footer gloryFooter class="gbn-footer" role="contentinfo">
    <div class="gbn-footer-container">
        <div class="gbn-footer-content">
            <div class="gbn-footer-section">
                <h4>Acerca de</h4>
                <p>Descripción breve del sitio o empresa.</p>
            </div>
            <div class="gbn-footer-section">
                <h4>Enlaces</h4>
                <ul>
                    <li><a href="#">Inicio</a></li>
                    <li><a href="#">Servicios</a></li>
                    <li><a href="#">Contacto</a></li>
                </ul>
            </div>
            <div class="gbn-footer-section">
                <h4>Contacto</h4>
                <p>info@ejemplo.com</p>
            </div>
        </div>
        <div class="gbn-footer-bottom">
            <p>&copy; ' . $year . ' ' . esc_html($siteTitle) . '. Todos los derechos reservados.</p>
        </div>
    </div>
</footer>';
    }
}
