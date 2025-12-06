<?php

namespace Glory\Gbn\Services;

class TemplateService
{
    private const OPTION_HEADER = 'gbn_header_template';
    private const OPTION_FOOTER = 'gbn_footer_template';

    private const OPTION_HEADER_CONFIG = 'gbn_header_config';
    private const OPTION_HEADER_STYLES = 'gbn_header_styles';
    private const OPTION_FOOTER_CONFIG = 'gbn_footer_config';
    private const OPTION_FOOTER_STYLES = 'gbn_footer_styles';

    public static function getHeaderContent(): string
    {
        return get_option(self::OPTION_HEADER, self::getDefaultHeader());
    }

    public static function saveHeaderContent(string $content): void
    {
        update_option(self::OPTION_HEADER, $content);
    }

    public static function getHeaderConfig(): array
    {
        return get_option(self::OPTION_HEADER_CONFIG, []);
    }

    public static function saveHeaderConfig(array $config): void
    {
        update_option(self::OPTION_HEADER_CONFIG, $config);
    }

    public static function getHeaderStyles(): array
    {
        return get_option(self::OPTION_HEADER_STYLES, []);
    }

    public static function saveHeaderStyles(array $styles): void
    {
        update_option(self::OPTION_HEADER_STYLES, $styles);
    }

    public static function getFooterContent(): string
    {
        return get_option(self::OPTION_FOOTER, self::getDefaultFooter());
    }

    public static function saveFooterContent(string $content): void
    {
        update_option(self::OPTION_FOOTER, $content);
    }

    public static function getFooterConfig(): array
    {
        return get_option(self::OPTION_FOOTER_CONFIG, []);
    }

    public static function saveFooterConfig(array $config): void
    {
        update_option(self::OPTION_FOOTER_CONFIG, $config);
    }

    public static function getFooterStyles(): array
    {
        return get_option(self::OPTION_FOOTER_STYLES, []);
    }

    public static function saveFooterStyles(array $styles): void
    {
        update_option(self::OPTION_FOOTER_STYLES, $styles);
    }

    private static function getDefaultHeader(): string
    {
        // Basado en el header original de Glory/Components/HeaderRenderer.php
        return '<div gloryDiv class="siteMenuW" opciones="layout: { type: \'flex\', justify: \'center\', align: \'center\' }, padding: \'20px\', background: \'#ffffff\'">
            <div gloryDiv class="siteMenuContainer" opciones="layout: { type: \'flex\', justify: \'space-between\', align: \'center\' }, width: \'100%\', maxWidth: \'1200px\'">
                
                <div gloryDiv class="siteMenuLogo">
                   <a href="/" gloryButton opciones="variant: \'link\', styles: { textDecoration: \'none\', color: \'inherit\' }">
                        <h2 gloryText opciones="text: \'Glory Theme\'">Glory Theme</h2>
                   </a>
                </div>

                <nav gloryDiv class="siteMenuNav" opciones="layout: { type: \'flex\', gap: \'20px\' }">
                    <a href="/" gloryButton opciones="variant: \'link\'">Inicio</a>
                    <a href="/blog" gloryButton opciones="variant: \'link\'">Blog</a>
                    <a href="/contacto" gloryButton opciones="variant: \'link\'">Contacto</a>
                </nav>

                <div gloryDiv class="siteMenuActions">
                    <a href="#" gloryButton opciones="variant: \'primary\'">Call to Action</a>
                </div>

            </div>
        </div>';
    }

    private static function getDefaultFooter(): string
    {
        return '<div gloryDiv class="gbn-footer-default" opciones="layout: { type: \'flex\', justify: \'center\', align: \'center\' }, padding: \'40px\', background: \'#333\', color: \'#fff\'">
            <p gloryText opciones="text: \'© 2025 Glory Builder. Todos los derechos reservados.\'">© 2025 Glory Builder. Todos los derechos reservados.</p>
        </div>';
    }
}
