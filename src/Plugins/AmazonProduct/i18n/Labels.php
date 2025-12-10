<?php

namespace Glory\Plugins\AmazonProduct\i18n;

/**
 * Centralized labels and translations for Amazon Product Plugin.
 * Follows Single Responsibility Principle - only handles translations.
 */
class Labels
{
    private static ?string $currentLang = null;

    private const TRANSLATIONS = [
        'en' => [
            'search_placeholder' => 'Search products...',
            'filters' => 'Filters',
            'min_price' => 'Min Price',
            'max_price' => 'Max Price',
            'prime_only' => 'Prime Only',
            'deals_only' => 'Deals Only',
            'newest' => 'Newest First',
            'best_discount' => 'Best Discount',
            'price_low' => 'Price: Low to High',
            'price_high' => 'Price: High to Low',
            'top_rated' => 'Top Rated',
            'no_results' => 'No products found.',
            'view_amazon' => 'View on Amazon',
            'categories' => 'Categories',
            'options' => 'Options',
            'products' => 'Products',
            'results' => 'results',
            'reset_filters' => 'Reset all filters',
            'clear_search' => 'Clear search',
            'and_more' => '& more',
            'no_categories' => 'No categories available.',
            'no_deals' => 'No deals available at this time.',
            'daily_deals' => 'Daily Deals',
            'flash_deal' => 'Flash Deal',
            'manual_import_tab' => 'Manual Import',
            'manual_import_title' => 'Amazon HTML Importer',
            'manual_import_desc' => 'Paste the source code (HTML) of an Amazon product page to automatically extract its data.',
            'paste_html_label' => 'HTML Code',
            'paste_html_help' => 'Go to the product on Amazon, press Ctrl+U (View Source), copy everything and paste it here.',
            'process_html_btn' => 'Process HTML',
            'verify_data_title' => 'Verify and Edit Extracted Data',
            'import_product_btn' => 'Save Product',
            'error_parsing' => 'No product data found. Make sure to copy the full HTML of an individual product page.',
            'success_parsing' => 'Data extracted successfully. Check the information below.',
            'product_imported_success' => 'Product imported successfully to the database.',
            'product_updated_success' => 'Existing product updated successfully.',
            'title_label' => 'Title',
            'price_label' => 'Price',
            'cancel' => 'Cancel',
        ],
        'es' => [
            'search_placeholder' => 'Buscar productos...',
            'filters' => 'Filtros',
            'min_price' => 'Precio Min',
            'max_price' => 'Precio Max',
            'prime_only' => 'Solo Prime',
            'deals_only' => 'Solo Ofertas',
            'newest' => 'Mas Recientes',
            'best_discount' => 'Mayor Descuento',
            'price_low' => 'Precio: Bajo a Alto',
            'price_high' => 'Precio: Alto a Bajo',
            'top_rated' => 'Mejor Valorados',
            'no_results' => 'No se encontraron productos.',
            'view_amazon' => 'Ver en Amazon',
            'categories' => 'Categorias',
            'options' => 'Opciones',
            'products' => 'Productos',
            'results' => 'resultados',
            'reset_filters' => 'Restablecer todos los filtros',
            'clear_search' => 'Limpiar busqueda',
            'and_more' => 'y mas',
            'no_categories' => 'No hay categorias.',
            'no_deals' => 'No hay ofertas disponibles en este momento.',
            'daily_deals' => 'Ofertas del Dia',
            'flash_deal' => 'Oferta Flash',
            'manual_import_tab' => 'Importación Manual',
            'manual_import_title' => 'Importador de HTML de Amazon',
            'manual_import_desc' => 'Pega el código fuente (HTML) de una página de producto de Amazon para extraer sus datos automáticamente.',
            'paste_html_label' => 'Código HTML',
            'paste_html_help' => 'Ve al producto en Amazon, presiona Ctrl+U (Ver código fuente), copia todo y pégalo aquí.',
            'process_html_btn' => 'Procesar HTML',
            'verify_data_title' => 'Verificar y Editar Datos Extraídos',
            'import_product_btn' => 'Guardar Producto',
            'error_parsing' => 'No se encontraron datos del producto. Asegúrate de copiar el HTML completo de una página de producto individual.',
            'success_parsing' => 'Datos extraídos con éxito. Revisa la información abajo.',
            'product_imported_success' => 'Producto importado correctamente a la base de datos.',
            'product_updated_success' => 'Producto existente actualizado correctamente.',
            'title_label' => 'Título',
            'price_label' => 'Precio',
            'cancel' => 'Cancelar',
        ]
    ];

    /**
     * Get the current language based on plugin settings or site locale.
     */
    public static function getLanguage(): string
    {
        if (self::$currentLang === null) {
            $lang = get_option('amazon_plugin_lang', 'default');
            if ($lang === 'default') {
                $lang = substr(get_locale(), 0, 2);
            }
            self::$currentLang = $lang;
        }
        return self::$currentLang;
    }

    /**
     * Get a translated label by key.
     * Falls back to English if translation not found.
     */
    public static function get(string $key): string
    {
        $lang = self::getLanguage();
        $dict = self::TRANSLATIONS[$lang] ?? self::TRANSLATIONS['en'];
        return $dict[$key] ?? $key;
    }

    /**
     * Reset cached language (useful for testing or after settings change).
     */
    public static function resetCache(): void
    {
        self::$currentLang = null;
    }
}
