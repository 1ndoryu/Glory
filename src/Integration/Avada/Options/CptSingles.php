<?php

namespace Glory\Integration\Avada\Options;

use Glory\Integration\Avada\AvadaOptionsBridge;

final class CptSingles
{
    /**
     * Inyecta una sección raíz por cada CPT público (excluyendo 'post' y built-in) con un sub-tab de opciones Single.
     * IDs de opciones: glory_{post_type}_single_*
     *
     * @param array $sections
     * @return array
     */
    public static function injectCptSections(array $sections): array
    {
        $postTypes = self::getPublicCustomPostTypes();
        if (empty($postTypes)) {
            return $sections;
        }

        foreach ($postTypes as $pt => $obj) {
            $sectionId = 'glory_cpt_' . $pt;
            $singleSubId = $pt . '_single_options';

            $sections[$sectionId] = [
                'label'    => isset($obj->labels->name) && $obj->labels->name ? (string) $obj->labels->name : ucfirst($pt),
                'id'       => $sectionId,
                'priority' => 28,
                'icon'     => 'fusiona-file-edit',
                'fields'   => [
                    $singleSubId => [
                        'label'       => esc_html__('Single Post', 'glory-ab'),
                        'description' => '',
                        'id'          => $singleSubId,
                        'type'        => 'sub-section',
                        'icon'        => true,
                        'fields'      => self::buildSingleFields($pt),
                    ],
                ],
            ];
        }

        return $sections;
    }

    /**
     * Registra filtros pre_option_* para que get_option('glory_{pt}_single_*') lea desde Avada (fusion_options).
     */
    public static function registerFilters(): void
    {
        $postTypes = self::getPublicCustomPostTypes();
        foreach (array_keys($postTypes) as $pt) {
            foreach (self::getOptionKeysForPostType($pt) as $key) {
                add_filter('pre_option_' . $key, [AvadaOptionsBridge::class, 'filterPreOptionFromAvada'], 10, 3);
            }
        }
    }

    private static function getPublicCustomPostTypes(): array
    {
        // Preferir los definidos en el PostTypeManager (para que exista aun en backend sin query de objetos)
        if (class_exists('Glory\\Manager\\PostTypeManager') && method_exists('Glory\\Manager\\PostTypeManager', 'getDefined')) {
            $defs = \Glory\Manager\PostTypeManager::getDefined();
            if (is_array($defs) && !empty($defs)) {
                $result = [];
                foreach ($defs as $name => $def) {
                    if ('post' === $name) { continue; }
                    $obj = (object) [
                        'name'   => $name,
                        'labels' => (object) [ 'name' => $def['plural'] ?? $name, 'singular_name' => $def['singular'] ?? ucfirst($name) ],
                        '_builtin' => false,
                    ];
                    $result[$name] = $obj;
                }
                return $result;
            }
        }
        $pts = function_exists('get_post_types') ? get_post_types([ 'public' => true ], 'objects') : [];
        if (!is_array($pts)) {
            return [];
        }
        $result = [];
        foreach ($pts as $name => $obj) {
            if ('post' === $name || (isset($obj->_builtin) && $obj->_builtin)) {
                continue;
            }
            $result[$name] = $obj;
        }
        return $result;
    }

    /**
     * Construye los campos para el sub-tab de Single del CPT dado.
     * Los valores por defecto copian los de Avada para Blog Single Post.
     */
    private static function buildSingleFields(string $postType): array
    {
        $p = sanitize_key($postType);
        $id = function(string $suffix) use ($p): string { return 'glory_' . $p . '_single_' . $suffix; };

        return [
            [
                'label'           => esc_html__('Portfolio Gallery', 'glory-ab'),
                'description'     => esc_html__('Turn on to display the default gallery at the end of the single post.', 'glory-ab'),
                'id'              => $id('gallery'),
                'default'         => '1',
                'type'            => 'switch',
            ],
            [
                'label'           => esc_html__('100% Width Page', 'glory-ab'),
                'description'     => esc_html__('Turn on to display single posts at full browser width.', 'glory-ab'),
                'id'              => $id('width_100'),
                'default'         => 0,
                'type'            => 'switch',
            ],
            [
                'label'           => esc_html__('Featured Image / Video', 'glory-ab'),
                'description'     => esc_html__('Turn on to display featured images/videos on single posts.', 'glory-ab'),
                'id'              => $id('featured_images'),
                'default'         => '1',
                'type'            => 'switch',
            ],
            [
                'label'           => esc_html__('Previous/Next Pagination', 'glory-ab'),
                'description'     => esc_html__('Turn on to display previous/next navigation on single posts.', 'glory-ab'),
                'id'              => $id('pn_nav'),
                'default'         => '1',
                'type'            => 'switch',
            ],
            [
                'label'           => esc_html__('Post Title', 'glory-ab'),
                'description'     => esc_html__('Controls if the post title displays above/below the featured image or is disabled.', 'glory-ab'),
                'id'              => $id('post_title'),
                'default'         => 'below',
                'type'            => 'radio-buttonset',
                'choices'         => [
                    'below'    => esc_html__('Below', 'glory-ab'),
                    'above'    => esc_html__('Above', 'glory-ab'),
                    'disabled' => esc_html__('Disabled', 'glory-ab'),
                ],
            ],
            [
                'label'           => esc_html__('Meta Data Position', 'glory-ab'),
                'description'     => esc_html__('Choose where the meta data is positioned.', 'glory-ab'),
                'id'              => $id('post_meta_position'),
                'default'         => 'below_article',
                'type'            => 'radio-buttonset',
                'choices'         => [
                    'below_article' => esc_html__('Below Article', 'glory-ab'),
                    'below_title'   => esc_html__('Below Title', 'glory-ab'),
                ],
                'required'        => [
                    [ 'setting' => $id('post_title'), 'operator' => '!=', 'value' => 'disabled' ],
                ],
            ],
            [
                'label'           => esc_html__('Social Sharing Box', 'glory-ab'),
                'description'     => esc_html__('Turn on to display the social sharing box.', 'glory-ab'),
                'id'              => $id('social_sharing_box'),
                'default'         => '1',
                'type'            => 'switch',
            ],
            [
                'label'           => esc_html__('Author Info Box', 'glory-ab'),
                'description'     => esc_html__('Turn on to display the author info box below posts.', 'glory-ab'),
                'id'              => $id('author_info'),
                'default'         => '1',
                'type'            => 'switch',
            ],
            [
                'label'           => esc_html__('Related Posts', 'glory-ab'),
                'description'     => esc_html__('Turn on to display related posts.', 'glory-ab'),
                'id'              => $id('related_posts'),
                'default'         => '1',
                'type'            => 'switch',
            ],
            [
                'label'           => esc_html__('Comments', 'glory-ab'),
                'description'     => esc_html__('Turn on to display comments.', 'glory-ab'),
                'id'              => $id('comments'),
                'default'         => '1',
                'type'            => 'switch',
            ],
        ];
    }

    private static function getOptionKeysForPostType(string $postType): array
    {
        $p = sanitize_key($postType);
        $base = 'glory_' . $p . '_single_';
        return [
            $base . 'gallery',
            $base . 'width_100',
            $base . 'featured_images',
            $base . 'pn_nav',
            $base . 'post_title',
            $base . 'post_meta_position',
            $base . 'social_sharing_box',
            $base . 'author_info',
            $base . 'related_posts',
            $base . 'comments',
        ];
    }
}


