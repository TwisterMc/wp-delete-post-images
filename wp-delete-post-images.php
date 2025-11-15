<?php
/**
 * Plugin Name: Delete Attached Media on Post Deletion
 * Description: When a post is permanently deleted, also deletes its attached media if they are not used anywhere else on the site.
 * Version: 1.0.0
 * Author: GitHub Copilot
 * Text Domain: wp-delete-post-images
 * Requires at least: 5.6
 * Requires PHP: 7.4
 */

namespace WPDPI; // WP Delete Post Images

// Exit if accessed directly.
if ( ! \defined( 'ABSPATH' ) ) {
    exit;
}

use WP_Post;

/**
 * Main hook: fire when a post is permanently deleted (not just trashed).
 *
 * Notes:
 * - We listen on 'deleted_post', which only fires on permanent deletion.
 * - We intentionally skip attachments, revisions, and menu items.
 *
 * @see https://developer.wordpress.org/reference/hooks/deleted_post/
 */
\add_action( 'deleted_post', __NAMESPACE__ . '\\on_deleted_post', 10, 1 );

/**
 * Handle post permanent deletion by removing attached media that are not used elsewhere.
 *
 * @param int $post_id The deleted post ID.
 * @return void
 */
function on_deleted_post( int $post_id ): void {
    $post = \get_post( $post_id );

    if ( ! $post instanceof WP_Post ) {
        return;
    }

    // Ignore unsupported post types.
    $unsupported_types = [ 'revision', 'nav_menu_item', 'attachment' ];
    $supported_types   = (array) \apply_filters( 'wpdpi_supported_post_types', [] );

    if ( ! empty( $supported_types ) ) {
        if ( ! in_array( $post->post_type, $supported_types, true ) ) {
            return;
        }
    } else {
        if ( in_array( $post->post_type, $unsupported_types, true ) ) {
            return;
        }
    }

    $attachment_ids = [];

    // 1) All attachments with this post as parent.
    $children = \get_children(
        [
            'post_parent'    => $post_id,
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'numberposts'    => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'update_post_term_cache' => false,
            'update_post_meta_cache' => false,
        ]
    );

    if ( is_array( $children ) && ! empty( $children ) ) {
        $attachment_ids = array_map( 'intval', $children );
    }

    // 2) Featured image for this post (may have no parent set).
    $thumb_id = \get_post_thumbnail_id( $post_id );
    if ( $thumb_id ) {
        $attachment_ids[] = (int) $thumb_id;
    }

    $attachment_ids = array_values( array_unique( array_filter( $attachment_ids ) ) );

    if ( empty( $attachment_ids ) ) {
        return;
    }

    foreach ( $attachment_ids as $attachment_id ) {
        // Skip if attachment no longer exists or already deleted.
        $attachment = \get_post( $attachment_id );
        if ( ! $attachment instanceof WP_Post || 'attachment' !== $attachment->post_type ) {
            continue;
        }

        $used_elsewhere = attachment_is_used_elsewhere( $attachment_id, $post_id );

        /**
         * Filter whether to skip deleting a specific attachment.
         *
         * @param bool $skip             True to skip deleting this attachment.
         * @param int  $attachment_id    The attachment ID being considered.
         * @param int  $original_post_id The deleted post ID.
         */
        $skip = (bool) \apply_filters( 'wpdpi_skip_delete', $used_elsewhere, $attachment_id, $post_id );

        if ( $skip ) {
            continue;
        }

        // Force delete the attachment and its files.
        \wp_delete_attachment( $attachment_id, true );
    }
}

/**
 * Heuristically determine if an attachment is used anywhere else on the site.
 *
 * We conservatively check several common usages:
 * - Featured image of another post
 * - Referenced in other post content/excerpt (image block JSON id, classes, shortcodes, or by filename)
 * - Present in other postmeta values (IDs in integers or serialized structures)
 * - Used as site icon or custom logo
 *
 * If any check indicates usage, we return true.
 *
 * @param int $attachment_id The attachment ID.
 * @param int $original_post_id The deleted post ID that owned this media.
 * @return bool True if the attachment appears to be used elsewhere, false otherwise.
 */
function attachment_is_used_elsewhere( int $attachment_id, int $original_post_id ): bool {
    global $wpdb;

    // Sanity: if the file does not exist anymore, treat as unused.
    $file_path = \get_attached_file( $attachment_id );
    $file_base = $file_path ? wp_basename( $file_path ) : '';

    // 0) Site-wide special uses: site icon and custom logo.
    $site_icon_id = (int) \get_option( 'site_icon' );
    if ( $site_icon_id && $site_icon_id === $attachment_id ) {
        return true;
    }

    $custom_logo_id = (int) \get_theme_mod( 'custom_logo' );
    if ( $custom_logo_id && $custom_logo_id === $attachment_id ) {
        return true;
    }

    // 1) Featured image of another post.
    $thumbnail_in_use = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT 1 FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON pm.post_id = p.ID\n             WHERE pm.meta_key = '_thumbnail_id' AND pm.meta_value = %d AND p.ID <> %d AND p.post_status <> 'trash' LIMIT 1",
            $attachment_id,
            $original_post_id
        )
    );
    if ( $thumbnail_in_use ) {
        return true;
    }

    // 2) Referenced in other posts' content or excerpt.
    $id             = (int) $attachment_id;
    $id_regex_parts = [];
    $id_regex_parts[] = 'wp-image-' . $id;          // Classic/editor class.
    $id_regex_parts[] = 'attachment_' . $id;         // Attachment id CSS class.
    $id_regex_parts[] = '"id":' . $id;             // Gutenberg block JSON.
    $id_regex_parts[] = 'data-id=\"' . $id . '\"'; // Data attribute.
    // Gallery shortcode ids list (rough heuristic).
    $id_regex_parts[] = 'ids=\"[^\"]*\\b' . $id . '\\b';

    $content_in_use = 0;
    if ( ! empty( $id_regex_parts ) ) {
        $regex = '(' . implode( '|', array_map( 'preg_quote_for_mysql_regex', $id_regex_parts ) ) . ')';
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $regex is safely built using preg_quote_for_mysql_regex.
        $content_in_use = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT 1 FROM {$wpdb->posts} p\n                 WHERE p.ID <> %d AND p.post_status <> 'trash'\n                   AND p.post_type NOT IN ('revision','nav_menu_item','attachment')\n                   AND (p.post_content REGEXP %s OR p.post_excerpt REGEXP %s)\n                 LIMIT 1",
                $original_post_id,
                $regex,
                $regex
            )
        );
        if ( $content_in_use ) {
            return true;
        }
    }

    // 2b) Referenced by filename (covers direct links and sized variants). Conservative and may false-positive on same-name files.
    if ( $file_base ) {
        $like = '%' . $wpdb->esc_like( $file_base ) . '%';
        $filename_in_use = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT 1 FROM {$wpdb->posts} p\n                 WHERE p.ID <> %d AND p.post_status <> 'trash'\n                   AND p.post_type NOT IN ('revision','nav_menu_item','attachment')\n                   AND (p.post_content LIKE %s OR p.post_excerpt LIKE %s)\n                 LIMIT 1",
                $original_post_id,
                $like,
                $like
            )
        );
        if ( $filename_in_use ) {
            return true;
        }
    }

    // 3) Present in other postmeta values (as integer or inside serialized/JSON). Heuristic numeric boundary matching.
    $boundary_regex = '(^|[^0-9])' . (int) $attachment_id . '([^0-9]|$)';
    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- boundary regex composed from integer with delimiters.
    $meta_in_use = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT 1 FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON pm.post_id = p.ID\n             WHERE p.ID <> %d AND p.post_status <> 'trash'\n               AND (pm.meta_value = %s OR pm.meta_value REGEXP %s)\n             LIMIT 1",
            $original_post_id,
            (string) $attachment_id,
            $boundary_regex
        )
    );
    if ( $meta_in_use ) {
        return true;
    }

    // 4) Optional: term meta scan for the ID (best-effort, may not exist on very old installs).
    if ( ! empty( $wpdb->termmeta ) ) {
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- boundary regex composed from integer with delimiters.
        $termmeta_in_use = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT 1 FROM {$wpdb->termmeta} tm\n                 WHERE (tm.meta_value = %s OR tm.meta_value REGEXP %s)\n                 LIMIT 1",
                (string) $attachment_id,
                $boundary_regex
            )
        );
        if ( $termmeta_in_use ) {
            return true;
        }
    }

    /**
     * Filter to short-circuit or extend usage detection logic.
     *
     * Return true if the attachment should be treated as used elsewhere.
     *
     * @param bool $used_elsewhere Current determination.
     * @param int  $attachment_id  The attachment ID.
     * @param int  $original_post_id The deleted post ID.
     */
    return (bool) \apply_filters( 'wpdpi_attachment_used_elsewhere', false, $attachment_id, $original_post_id );
}

/**
 * Utility: escape a string to be safely embedded inside a MySQL REGEXP literal.
 * Similar aim as preg_quote, but tailored for MySQL's REGEXP syntax.
 *
 * @param string $raw Raw pattern text.
 * @return string
 */
function preg_quote_for_mysql_regex( string $raw ): string { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
    // Escape regex special characters. MySQL REGEXP uses POSIX ERE; this over-escapes for safety.
    $special = [ '\\', '.', '+', '*', '?', '[', '^', ']', '$', '(', ')', '{', '}', '=', '!', '<', '>', '|', ':', '-' ];
    $escaped = str_replace( $special, array_map( static function ( $c ) { return '\\' . $c; }, $special ), $raw );
    return $escaped;
}
