# Delete Attached Media on Post Deletion

When a post is permanently deleted (from the Trash), this plugin also deletes any media attached to that post—provided those files are not used anywhere else on the site.

## Features

- Deletes attached media when a post is permanently deleted
- Skips media still used elsewhere (featured images, content references, postmeta, site icon, custom logo)
- Skips media still used elsewhere (featured images, content references, postmeta IDs and URL strings, site icon, custom logo)
- Optional deep scans for URLs in term meta, options, and comments (behind filters)
- Includes the post's featured image even if it has no parent
- Runs only on permanent deletion (not when moving to trash)
- Lightweight, no UI, safe-by-default heuristics
- Filters to customize behavior

## Installation

1. Copy the folder `wp-delete-post-images` into `wp-content/plugins/`.
2. Ensure the main file is at `wp-content/plugins/wp-delete-post-images/wp-delete-post-images.php`.
3. Activate the plugin in WP Admin → Plugins.

## How It Works

- Hooks into `deleted_post` (fires only on permanent deletion)
- Collects attachments where `post_parent` is the deleted post, plus that post's `featured image`
- For each attachment, checks if it's used elsewhere:
  - Featured image for another post
  - Referenced in other posts' `post_content`/`post_excerpt` (Gutenberg JSON id, classic classes, gallery shortcode ids, or filename)
  - Appears in other `postmeta` values (including serialized/JSON best-effort) and URL strings (full URL and path-only)
  - Used as `site icon` or `custom logo`
- Deletes only attachments that are not used elsewhere

## Filters

- `wpdpi_supported_post_types`: Limit which post types trigger cleanup.

  ```php
  add_filter('wpdpi_supported_post_types', function () {
      return ['post', 'page', 'my_cpt'];
  });
  ```

- `wpdpi_skip_delete`: Decide per-attachment whether to skip deletion.

  ```php
  add_filter('wpdpi_skip_delete', function ($skip, $attachment_id, $original_post_id) {
      // Example: keep specific IDs
      $protected = [123, 456];
      return $skip || in_array($attachment_id, $protected, true);
  }, 10, 3);
  ```

- `wpdpi_attachment_used_elsewhere`: Extend usage detection logic.

  ```php
  add_filter('wpdpi_attachment_used_elsewhere', function ($used, $attachment_id, $original_post_id) {
      // Add org-specific checks here
      return $used;
  }, 10, 3);
  ```

- `wpdpi_enable_content_regex` (default: true): Toggle REGEXP scanning of post content/excerpt for attachment IDs.

  ```php
  add_filter('wpdpi_enable_content_regex', '__return_false');
  ```

- `wpdpi_enable_filename_like` (default: true): Toggle LIKE scanning of post content/excerpt for filename matches.

  ```php
  add_filter('wpdpi_enable_filename_like', '__return_false');
  ```

- `wpdpi_enable_postmeta_id_scan` (default: true): Toggle numeric ID scans in postmeta values.

  ```php
  add_filter('wpdpi_enable_postmeta_id_scan', '__return_false');
  ```

- `wpdpi_enable_postmeta_url_scan` (default: true): Toggle URL/path scans in postmeta values.

  ```php
  add_filter('wpdpi_enable_postmeta_url_scan', '__return_false');
  ```

- `wpdpi_scan_termmeta_for_urls` (default: false): When true, also scan `termmeta.meta_value` for URL strings.

  ```php
  add_filter('wpdpi_scan_termmeta_for_urls', '__return_true');
  ```

- `wpdpi_scan_options_for_urls` (default: false): When true, scan `options.option_value` for URL strings.

  ```php
  add_filter('wpdpi_scan_options_for_urls', '__return_true');
  ```

- `wpdpi_scan_comments_for_urls` (default: false): When true, scan `comments.comment_content` for URL strings.

  ```php
  add_filter('wpdpi_scan_comments_for_urls', '__return_true');
  ```

## Actions

- `wpdpi_before_delete_attachment( int $attachment_id, int $original_post_id )`: Fires right before an attachment is force-deleted by the plugin.
- `wpdpi_after_delete_attachment( int $attachment_id, int $original_post_id )`: Fires immediately after an attachment is deleted.

## Notes

- This plugin does not add or store any options/data.
- It intentionally performs conservative checks to avoid accidental data loss. If a heuristic suggests the file may be in use, it will not delete it.

## Compatibility

- Requires WordPress 5.6+ and PHP 7.4+.

## Changelog

- 1.1.0 — Detect attachment URL strings in postmeta by default; optional URL scans for term meta, options, and comments via filters; added before/after deletion actions.
- 1.0.0 — Initial release.
