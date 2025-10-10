<?php

namespace Glory\Integration\Avada\Elements\GlorySplitContent;

class GlorySplitContentTemplate
{
    /**
     * Item de lista solo con título, clickable.
     *
     * @param \WP_Post $post
     * @param string $itemClass
     */
    public static function titleItem(\WP_Post $post, string $itemClass): void
    {
        $id = (int) $post->ID;
        ?>
        <div id="post-<?php echo $id; ?>" class="<?php echo esc_attr(trim($itemClass . ' glory-split__item')); ?>" data-post-id="<?php echo esc_attr((string) $id); ?>">
            <button type="button" class="glory-split__title" aria-controls="glory-split-content-<?php echo $id; ?>">
                <span class="glory-split__title-text"><?php echo esc_html(get_the_title($post)); ?></span>
            </button>
        </div>
        <?php
    }
}


