<?php

namespace Glory\Plugins\AmazonProduct\Admin\Tabs;

use Glory\Plugins\AmazonProduct\Service\SectionManager;

/**
 * Sections Tab - Gestion de secciones dinamicas de productos.
 * 
 * Permite a los clientes:
 * - Ver todas las secciones registradas
 * - Modificar configuracion (search, exclude, order, etc)
 * - Excluir productos individuales
 * - Restaurar a valores default
 */
class SectionsTab implements TabInterface
{
    private SectionManager $manager;

    public function __construct()
    {
        $this->manager = new SectionManager();
    }

    public function getSlug(): string
    {
        return 'sections';
    }

    public function getLabel(): string
    {
        return 'Secciones';
    }

    public function render(): void
    {
        $this->enqueueAssets();

        /* 
         * Escanear archivos automaticamente al abrir la tab.
         * Detecta shortcodes con section="xxx" en /App/Templates/
         */
        $this->manager->syncSectionsFromFiles();

        $sections = $this->manager->getAll();
        $stats = $this->manager->getStats();

        $this->renderHeader($stats);
        $this->renderSectionsList($sections);
    }

    private function enqueueAssets(): void
    {
        $baseUrl = get_template_directory_uri() . '/Glory/src/Plugins/AmazonProduct/assets';

        wp_enqueue_style(
            'glory-sections-tab',
            $baseUrl . '/css/sections-tab.css',
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'glory-sections-tab',
            $baseUrl . '/js/sections-tab.js',
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script('glory-sections-tab', 'glorySections', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('glory_sections_nonce'),
            'strings' => [
                'confirmRestore' => '¿Restaurar esta seccion a sus valores por defecto?',
                'saving' => 'Guardando...',
                'saved' => 'Guardado',
                'error' => 'Error al guardar',
                'loading' => 'Cargando...',
            ],
        ]);
    }

    private function renderHeader(array $stats): void
    {
?>
        <div class="seccionesEncabezado">
            <div class="seccionesEncabezadoInfo">
                <h3>Gestion de Secciones</h3>
                <p class="seccionesDescripcion">
                    Las secciones se detectan automaticamente desde los shortcodes que usan
                    <code>section="nombre"</code>. Aqui puedes modificar la configuracion
                    sin editar el HTML.
                </p>
            </div>
            <div class="seccionesEstadisticas">
                <span class="seccionesContador">
                    <?php echo esc_html($stats['total']); ?> secciones
                </span>
                <?php if ($stats['modified'] > 0): ?>
                    <span class="seccionesModificadas">
                        <?php echo esc_html($stats['modified']); ?> modificadas
                    </span>
                <?php endif; ?>
            </div>
        </div>
    <?php
    }

    private function renderSectionsList(array $sections): void
    {
        if (empty($sections)) {
            $this->renderEmptyState();
            return;
        }
    ?>
        <div class="seccionesLista" id="seccionesLista">
            <?php foreach ($sections as $section): ?>
                <?php $this->renderSectionCard($section); ?>
            <?php endforeach; ?>
        </div>
    <?php
    }

    private function renderEmptyState(): void
    {
    ?>
        <div class="seccionesVacio">
            <div class="seccionesVacioIcono">📦</div>
            <h4>No hay secciones registradas</h4>
            <p>
                Las secciones se crean automaticamente cuando usas un shortcode con el
                atributo <code>section</code>:
            </p>
            <pre>[amazon_products section="palas" search="pala" orderby="random"]</pre>
            <p>
                Una vez que el shortcode se renderice en el frontend, la seccion
                aparecera aqui para que puedas configurarla.
            </p>
        </div>
    <?php
    }

    private function renderSectionCard($section): void
    {
        $slug = $section->getSlug();
        $config = $section->getEffectiveConfig();
        $hasModifications = $section->hasModifications();
        $excludedCount = count($section->getExcludedIds());
        $productCount = $this->manager->getProductCount($slug);
    ?>
        <div class="seccionCard <?php echo $hasModifications ? 'seccionModificada' : ''; ?>"
            data-section="<?php echo esc_attr($slug); ?>">

            <div class="seccionCabecera" data-toggle-section="<?php echo esc_attr($slug); ?>">
                <span class="seccionToggle">▶</span>
                <h4 class="seccionNombre"><?php echo esc_html($slug); ?></h4>
                <span class="seccionConteoProductos">
                    <?php echo esc_html($productCount); ?> productos
                </span>
                <?php if ($hasModifications): ?>
                    <span class="seccionBadgeModificada">Modificada</span>
                <?php endif; ?>
                <?php if ($excludedCount > 0): ?>
                    <span class="seccionBadgeExcluidos">
                        <?php echo esc_html($excludedCount); ?> excluidos
                    </span>
                <?php endif; ?>
            </div>

            <div class="seccionContenido" id="seccion-<?php echo esc_attr($slug); ?>" style="display: none;">
                <?php $this->renderSectionForm($section, $config); ?>
            </div>
        </div>
    <?php
    }

    private function renderSectionForm($section, array $config): void
    {
        $slug = $section->getSlug();
        $defaults = $section->getDefaults();
    ?>
        <form class="seccionFormulario" data-section="<?php echo esc_attr($slug); ?>">
            <input type="hidden" name="section_slug" value="<?php echo esc_attr($slug); ?>">

            <div class="seccionCampos">
                <?php $this->renderField('search', 'Buscar', $config, $defaults, 'text', 'Palabras clave para buscar productos'); ?>
                <?php $this->renderField('exclude', 'Excluir palabras', $config, $defaults, 'text', 'Palabras a excluir separadas por coma'); ?>

                <div class="seccionCamposGrupo">
                    <?php $this->renderSelectField('orderby', 'Ordenar por', $config, $defaults, [
                        'date' => 'Fecha',
                        'random' => 'Aleatorio',
                        'price' => 'Precio',
                        'rating' => 'Rating',
                        'discount' => 'Descuento',
                    ]); ?>

                    <?php $this->renderSelectField('order', 'Direccion', $config, $defaults, [
                        'DESC' => 'Descendente',
                        'ASC' => 'Ascendente',
                    ]); ?>

                    <?php $this->renderField('limit', 'Limite', $config, $defaults, 'number', 'Cantidad maxima de productos'); ?>
                </div>

                <div class="seccionCamposGrupo">
                    <?php $this->renderField('min_price', 'Precio min', $config, $defaults, 'number', ''); ?>
                    <?php $this->renderField('max_price', 'Precio max', $config, $defaults, 'number', ''); ?>
                    <?php $this->renderField('min_rating', 'Rating min', $config, $defaults, 'number', '1-5'); ?>
                </div>

                <div class="seccionCamposGrupo">
                    <?php $this->renderCheckboxField('only_prime', 'Solo Prime', $config, $defaults); ?>
                    <?php $this->renderCheckboxField('only_deals', 'Solo Ofertas', $config, $defaults); ?>
                </div>
            </div>

            <?php $this->renderExcludedProducts($section); ?>

            <div class="seccionAcciones">
                <button type="button" class="button seccionBotonPreview" data-section="<?php echo esc_attr($slug); ?>">
                    Previsualizar
                </button>
                <button type="submit" class="button button-primary seccionBotonGuardar">
                    Guardar cambios
                </button>
                <?php if ($section->hasModifications()): ?>
                    <button type="button" class="button seccionBotonRestaurar" data-section="<?php echo esc_attr($slug); ?>">
                        Restaurar defaults
                    </button>
                <?php endif; ?>
            </div>

            <div class="seccionMensaje" style="display: none;"></div>
        </form>
    <?php
    }

    private function renderField(string $name, string $label, array $config, array $defaults, string $type, string $placeholder): void
    {
        $value = $config[$name] ?? '';
        $defaultValue = $defaults[$name] ?? '';
        $isOverridden = isset($config[$name]) && $config[$name] !== $defaultValue;
    ?>
        <div class="seccionCampo <?php echo $isOverridden ? 'seccionCampoModificado' : ''; ?>">
            <label for="field-<?php echo esc_attr($name); ?>">
                <?php echo esc_html($label); ?>
                <?php if ($isOverridden): ?>
                    <span class="seccionCampoDefault" title="Default: <?php echo esc_attr($defaultValue); ?>">
                        (modificado)
                    </span>
                <?php endif; ?>
            </label>
            <input type="<?php echo esc_attr($type); ?>"
                id="field-<?php echo esc_attr($name); ?>"
                name="<?php echo esc_attr($name); ?>"
                value="<?php echo esc_attr($value); ?>"
                placeholder="<?php echo esc_attr($placeholder ?: $defaultValue); ?>"
                class="regular-text">
        </div>
    <?php
    }

    private function renderSelectField(string $name, string $label, array $config, array $defaults, array $options): void
    {
        $value = $config[$name] ?? '';
        $defaultValue = $defaults[$name] ?? '';
        $isOverridden = isset($config[$name]) && $config[$name] !== $defaultValue;
    ?>
        <div class="seccionCampo <?php echo $isOverridden ? 'seccionCampoModificado' : ''; ?>">
            <label for="field-<?php echo esc_attr($name); ?>">
                <?php echo esc_html($label); ?>
                <?php if ($isOverridden): ?>
                    <span class="seccionCampoDefault">(modificado)</span>
                <?php endif; ?>
            </label>
            <select id="field-<?php echo esc_attr($name); ?>"
                name="<?php echo esc_attr($name); ?>">
                <?php foreach ($options as $optValue => $optLabel): ?>
                    <option value="<?php echo esc_attr($optValue); ?>"
                        <?php selected($value, $optValue); ?>>
                        <?php echo esc_html($optLabel); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php
    }

    private function renderCheckboxField(string $name, string $label, array $config, array $defaults): void
    {
        $value = $config[$name] ?? '';
        $checked = ($value === '1' || $value === 1 || $value === true);
    ?>
        <div class="seccionCampo seccionCampoCheckbox">
            <label>
                <input type="checkbox"
                    name="<?php echo esc_attr($name); ?>"
                    value="1"
                    <?php checked($checked); ?>>
                <?php echo esc_html($label); ?>
            </label>
        </div>
    <?php
    }

    private function renderExcludedProducts($section): void
    {
        $excludedIds = $section->getExcludedIds();

        if (empty($excludedIds)) {
            return;
        }
    ?>
        <div class="seccionExcluidos">
            <h5>Productos excluidos manualmente (<?php echo count($excludedIds); ?>)</h5>
            <div class="seccionExcluidosLista">
                <?php foreach ($excludedIds as $productId): ?>
                    <?php
                    $product = get_post($productId);
                    if (!$product) continue;
                    ?>
                    <div class="seccionExcluidoItem" data-product-id="<?php echo esc_attr($productId); ?>">
                        <span class="seccionExcluidoTitulo">
                            <?php echo esc_html(wp_trim_words($product->post_title, 8)); ?>
                        </span>
                        <button type="button" class="button-link seccionBotonIncluir"
                            data-section="<?php echo esc_attr($section->getSlug()); ?>"
                            data-product-id="<?php echo esc_attr($productId); ?>">
                            Incluir
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
<?php
    }

    /* 
     * AJAX Handlers 
     */

    public static function registerAjaxHandlers(): void
    {
        add_action('wp_ajax_glory_save_section', [self::class, 'ajaxSaveSection']);
        add_action('wp_ajax_glory_restore_section', [self::class, 'ajaxRestoreSection']);
        add_action('wp_ajax_glory_include_product', [self::class, 'ajaxIncludeProduct']);
        add_action('wp_ajax_glory_preview_section', [self::class, 'ajaxPreviewSection']);
        add_action('wp_ajax_glory_scan_sections', [self::class, 'ajaxScanSections']);
    }

    public static function ajaxSaveSection(): void
    {
        check_ajax_referer('glory_sections_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        $slug = sanitize_key($_POST['section_slug'] ?? '');
        if (empty($slug)) {
            wp_send_json_error(['message' => 'Seccion no especificada']);
        }

        $manager = new SectionManager();
        $section = $manager->get($slug);

        if (!$section) {
            wp_send_json_error(['message' => 'Seccion no encontrada']);
        }

        $overrides = [];
        $fields = ['search', 'exclude', 'orderby', 'order', 'limit', 'min_price', 'max_price', 'min_rating', 'only_prime', 'only_deals'];
        $defaults = $section->getDefaults();

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $value = sanitize_text_field($_POST[$field]);
                $defaultValue = $defaults[$field] ?? '';

                if ($value !== '' && $value !== $defaultValue) {
                    $overrides[$field] = $value;
                }
            }
        }

        $manager->updateOverrides($slug, $overrides);
        $productCount = $manager->getProductCount($slug);

        wp_send_json_success([
            'message' => 'Seccion guardada',
            'productCount' => $productCount,
            'hasModifications' => !empty($overrides),
        ]);
    }

    public static function ajaxRestoreSection(): void
    {
        check_ajax_referer('glory_sections_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        $slug = sanitize_key($_POST['section_slug'] ?? '');
        if (empty($slug)) {
            wp_send_json_error(['message' => 'Seccion no especificada']);
        }

        $manager = new SectionManager();
        $manager->reset($slug);

        wp_send_json_success(['message' => 'Seccion restaurada a defaults']);
    }

    public static function ajaxIncludeProduct(): void
    {
        check_ajax_referer('glory_sections_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        $slug = sanitize_key($_POST['section_slug'] ?? '');
        $productId = intval($_POST['product_id'] ?? 0);

        if (empty($slug) || $productId <= 0) {
            wp_send_json_error(['message' => 'Datos invalidos']);
        }

        $manager = new SectionManager();
        $manager->includeProduct($slug, $productId);

        wp_send_json_success(['message' => 'Producto incluido']);
    }

    public static function ajaxPreviewSection(): void
    {
        check_ajax_referer('glory_sections_nonce', 'nonce');

        $slug = sanitize_key($_POST['section_slug'] ?? '');
        if (empty($slug)) {
            wp_send_json_error(['message' => 'Seccion no especificada']);
        }

        $manager = new SectionManager();
        $section = $manager->get($slug);

        if (!$section) {
            wp_send_json_error(['message' => 'Seccion no encontrada']);
        }

        $config = $section->getEffectiveConfig();
        $excludedIds = $section->getExcludedIds();

        $params = array_merge($config, [
            'limit' => intval($config['limit'] ?? 12),
            'paged' => 1,
            '_excluded_ids' => $excludedIds,
        ]);

        $queryBuilder = new \Glory\Plugins\AmazonProduct\Renderer\QueryBuilder();
        $query = $queryBuilder->build($params);

        $posts = [];
        while ($query->have_posts()) {
            $query->the_post();
            $posts[] = get_post();
        }
        wp_reset_postdata();

        $excludeWords = $queryBuilder->getExcludeWords($params);
        if (!empty($excludeWords)) {
            $posts = \Glory\Plugins\AmazonProduct\Renderer\QueryBuilder::filterExcludedPosts($posts, $excludeWords);
        }

        $products = [];
        foreach ($posts as $post) {
            $postId = $post->ID;
            $products[] = [
                'id' => $postId,
                'title' => $post->post_title,
                'image' => get_the_post_thumbnail_url($postId, 'thumbnail') ?: get_post_meta($postId, 'image_url', true),
                'price' => get_post_meta($postId, 'price', true),
            ];
        }

        $totalCount = $manager->getProductCount($slug);

        wp_send_json_success([
            'products' => $products,
            'total' => $totalCount,
            'showing' => count($products),
        ]);
    }

    public static function ajaxScanSections(): void
    {
        check_ajax_referer('glory_sections_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        $manager = new SectionManager();
        $registered = $manager->syncSectionsFromFiles();

        wp_send_json_success([
            'message' => 'Escaneadas ' . count($registered) . ' secciones',
            'sections' => $registered,
        ]);
    }
}
