# Delete Attached Media on Post Deletion

When a post is permanently deleted (from the Trash), this plugin also deletes any media attached to that post—provided those files are not used anywhere else on the site.

## Features

- Deletes attached media when a post is permanently deleted
- Skips media still used elsewhere (featured images, content references, postmeta, site icon, custom logo)
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
  - Appears in other `postmeta` values (including serialized/JSON best-effort)
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

## Notes

- This plugin does not add or store any options/data.
- It intentionally performs conservative checks to avoid accidental data loss. If a heuristic suggests the file may be in use, it will not delete it.

## Compatibility

- Requires WordPress 5.6+ and PHP 7.4+.

## Changelog

- 1.0.0 — Initial release.
