<?php

namespace Glory\Plugins\AmazonProduct\Admin\Metabox;

use Glory\Plugins\AmazonProduct\Service\SectionManager;

/**
 * Metabox de Secciones en Productos.
 * 
 * Muestra en cada producto:
 * - Las secciones donde aparece el producto
 * - Las secciones donde esta excluido
 * - Permite cambiar la visibilidad por seccion
 */
class ProductSectionsMetabox
{
    private SectionManager $manager;

    public function __construct()
    {
        $this->manager = new SectionManager();
    }

    public function init(): void
    {
        add_action('add_meta_boxes', [$this, 'registerMetabox']);
        add_action('save_post_amazon_product', [$this, 'saveMetabox'], 10, 2);
    }

    public function registerMetabox(): void
    {
        add_meta_box(
            'glory_product_sections',
            'Secciones',
            [$this, 'renderMetabox'],
            'amazon_product',
            'side',
            'default'
        );
    }

    public function renderMetabox(\WP_Post $post): void
    {
        $sections = $this->manager->getAll();

        if (empty($sections)) {
            $this->renderEmptyState();
            return;
        }

        wp_nonce_field('glory_product_sections', 'glory_sections_nonce');

        $productId = $post->ID;
        $productTitle = strtolower($post->post_title);
?>
        <div class="productoSeccionesMetabox">
            <p class="descripcion">
                Este producto aparece en las siguientes secciones:
            </p>

            <div class="productoSeccionesLista">
                <?php foreach ($sections as $section): ?>
                    <?php $this->renderSectionRow($section, $productId, $productTitle); ?>
                <?php endforeach; ?>
            </div>
        </div>

        <style>
            .productoSeccionesMetabox .descripcion {
                color: #666;
                font-size: 12px;
                margin: 0 0 12px 0;
            }

            .productoSeccionesLista {
                display: flex;
                flex-direction: column;
                gap: 8px;
            }

            .productoSeccionItem {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 8px;
                background: #f9f9f9;
                border-radius: 4px;
            }

            .productoSeccionItem.excluido {
                background: #fff3e0;
            }

            .productoSeccionItem.noAplica {
                background: #f5f5f5;
                opacity: 0.6;
            }

            .productoSeccionNombre {
                flex: 1;
                font-size: 13px;
            }

            .productoSeccionEstado {
                font-size: 11px;
                padding: 2px 6px;
                border-radius: 10px;
            }

            .productoSeccionEstado.visible {
                background: #e8f5e9;
                color: #2e7d32;
            }

            .productoSeccionEstado.excluido {
                background: #ffebee;
                color: #c62828;
            }

            .productoSeccionEstado.noAplica {
                background: #eeeeee;
                color: #666;
            }
        </style>
    <?php
    }

    private function renderSectionRow($section, int $productId, string $productTitle): void
    {
        $slug = $section->getSlug();
        $config = $section->getEffectiveConfig();
        $isExcluded = $section->isProductExcluded($productId);

        $matchesSearch = $this->productMatchesSearch($productTitle, $config);
        $matchesExcludeWords = $this->productMatchesExcludeWords($productTitle, $config);

        if ($matchesExcludeWords) {
            $status = 'noAplica';
            $statusLabel = 'Excluido por palabras';
        } elseif ($isExcluded) {
            $status = 'excluido';
            $statusLabel = 'Excluido manual';
        } elseif ($matchesSearch) {
            $status = 'visible';
            $statusLabel = 'Visible';
        } else {
            $status = 'noAplica';
            $statusLabel = 'No coincide';
        }
    ?>
        <div class="productoSeccionItem <?php echo esc_attr($status); ?>">
            <input type="checkbox"
                name="glory_sections[<?php echo esc_attr($slug); ?>]"
                value="1"
                <?php checked($status === 'visible'); ?>
                <?php disabled($status === 'noAplica'); ?>>
            <span class="productoSeccionNombre"><?php echo esc_html($slug); ?></span>
            <span class="productoSeccionEstado <?php echo esc_attr($status); ?>">
                <?php echo esc_html($statusLabel); ?>
            </span>
        </div>
    <?php
    }

    private function productMatchesSearch(string $productTitle, array $config): bool
    {
        if (empty($config['search'])) {
            return true;
        }

        $searchTerms = array_map('trim', explode(' ', strtolower($config['search'])));
        foreach ($searchTerms as $term) {
            if (stripos($productTitle, $term) !== false) {
                return true;
            }
        }

        return false;
    }

    private function productMatchesExcludeWords(string $productTitle, array $config): bool
    {
        if (empty($config['exclude'])) {
            return false;
        }

        $excludeWords = array_map('trim', explode(',', strtolower($config['exclude'])));
        foreach ($excludeWords as $word) {
            if (stripos($productTitle, $word) !== false) {
                return true;
            }
        }

        return false;
    }

    private function renderEmptyState(): void
    {
    ?>
        <p style="color: #666; font-size: 12px;">
            No hay secciones configuradas.
            Las secciones se crean cuando usas shortcodes con
            <code>section="nombre"</code>.
        </p>
<?php
    }

    public function saveMetabox(int $postId, \WP_Post $post): void
    {
        if (!isset($_POST['glory_sections_nonce'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['glory_sections_nonce'], 'glory_product_sections')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $postId)) {
            return;
        }

        $sections = $this->manager->getAll();
        $submittedSections = $_POST['glory_sections'] ?? [];

        foreach ($sections as $section) {
            $slug = $section->getSlug();
            $isChecked = isset($submittedSections[$slug]);
            $wasExcluded = $section->isProductExcluded($postId);

            if ($isChecked && $wasExcluded) {
                $this->manager->includeProduct($slug, $postId);
            } elseif (!$isChecked && !$wasExcluded) {
                $productTitle = strtolower($post->post_title);
                $config = $section->getEffectiveConfig();

                if (
                    $this->productMatchesSearch($productTitle, $config) &&
                    !$this->productMatchesExcludeWords($productTitle, $config)
                ) {
                    $this->manager->excludeProduct($slug, $postId);
                }
            }
        }
    }
}
