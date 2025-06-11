<?php 

function metaPost(string $metaKey, int $postId = 0)
{
    if (!$postId) {
        $postId = get_the_ID();
    }

    if (!$postId) {
        return;
    }

    return get_post_meta($postId, $metaKey, true);

}