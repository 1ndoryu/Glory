<?php

namespace Glory\Support\WP;

class PostsDedup
{
    private static $activeCount = 0;

    public static function enable(): void
    {
        if ( function_exists('add_filter') ) {
            if ( 0 === self::$activeCount ) {
                add_filter('posts_distinct', [self::class, 'filterPostsDistinct'], 10, 2);
                add_filter('the_posts', [self::class, 'filterThePostsDedup'], 10, 2);
            }
            self::$activeCount++;
        }
    }

    public static function disable(): void
    {
        if ( self::$activeCount <= 0 ) {
            return;
        }
        self::$activeCount--;
        if ( 0 === self::$activeCount && function_exists('remove_filter') ) {
            remove_filter('posts_distinct', [self::class, 'filterPostsDistinct'], 10);
            remove_filter('the_posts', [self::class, 'filterThePostsDedup'], 10);
        }
    }

    public static function filterPostsDistinct($distinct, $query)
    {
        if ( self::$activeCount > 0 ) {
            return 'DISTINCT';
        }
        return $distinct;
    }

    public static function filterThePostsDedup($posts, $query)
    {
        if ( self::$activeCount <= 0 || ! is_array($posts) ) {
            return $posts;
        }
        $seen = [];
        $deduped = [];
        foreach ( $posts as $post ) {
            $id = is_object($post) && isset($post->ID) ? (int) $post->ID : (int) $post;
            if ( $id && ! isset($seen[$id]) ) {
                $seen[$id] = true;
                $deduped[] = $post;
            }
        }
        return $deduped;
    }
}
