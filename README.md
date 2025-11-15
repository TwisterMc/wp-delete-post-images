# Delete Attached Media on Post Deletion

When a post is permanently deleted (from the Trash), this plugin also deletes any media attached to that post—provided those files are not used anywhere else on the site.

## Features

- Deletes attached media when a post is permanently deleted
- Skips media still used elsewhere (featured images, content references, postmeta IDs and URL strings, site icon, custom logo)
- Optional deep scans for URLs in term meta, options, and comments
- Includes the post's featured image even if it has no parent
- Runs only on permanent deletion (not when moving to trash)
- Accessible admin settings page under **Settings → Delete Post Media**
- Performance tuning: enable/disable individual scans for bulk operations
- Safe-by-default heuristics with extensible filters and actions

## Installation

1. Copy the folder `wp-delete-post-images` into `wp-content/plugins/`.
2. Ensure the main file is at `wp-content/plugins/wp-delete-post-images/wp-delete-post-images.php`.
3. Activate the plugin in WP Admin → Plugins.
4. (Optional) Configure settings at **Settings → Delete Post Media**.

## How It Works

- Hooks into `deleted_post` (fires only on permanent deletion)
- Collects attachments where `post_parent` is the deleted post, plus that post's `featured image`
- For each attachment, checks if it's used elsewhere:
  - Featured image for another post
  - Referenced in other posts' `post_content`/`post_excerpt` (Gutenberg JSON id, classic classes, gallery shortcode ids, or filename)
  - Appears in other `postmeta` values (including serialized/JSON best-effort) and URL strings (full URL and path-only)
  - Used as `site icon` or `custom logo`
- Deletes only attachments that are not used elsewhere

## Settings

Go to **Settings → Delete Post Media** in your WordPress admin to configure:

### Performance Settings

- **Scan Post Content (REGEXP)**: Search for attachment IDs in post content/excerpt using pattern matching.
- **Scan Post Content (Filename)**: Search for filename matches in post content/excerpt.
- **Scan Postmeta (ID)**: Search for numeric attachment IDs in postmeta values.
- **Scan Postmeta (URL)**: Search for attachment URLs in postmeta values.

Disable heavy scans to improve performance during bulk post deletions.

### Deep Scans (Optional)

- **Scan Term Meta for URLs**: Search for attachment URLs in term metadata.
- **Scan Options for URLs**: Search for attachment URLs in site options.
- **Scan Comments for URLs**: Search for attachment URLs in comment content.

Only enable these if you store attachment URLs in these locations. May impact performance on large sites.

### Supported Post Types

By default, all post types (except revisions, nav menu items, and attachments) trigger cleanup. Select specific post types to limit which posts are processed.

### Protected Attachments

Enter attachment IDs (comma-separated) that should never be deleted, regardless of usage detection. Useful for critical images like logos or headers.

## Filters

All settings can also be controlled programmatically via filters:

- `wpdpi_supported_post_types`: Limit which post types trigger cleanup.

  ```php
  add_filter('wpdpi_supported_post_types', function () {
      return ['post', 'page', 'my_cpt'];
  });
  ```

- `wpdpi_skip_delete`: Decide per-attachment whether to skip deletion.

  ```php
  add_filter('wpdpi_skip_delete', function ($skip, $attachment_id, $original_post_id) {
      // Example: keep specific IDs (can also use settings UI)
      $protected = [123, 456];
      return $skip || in_array($attachment_id, $protected, true);
  }, 10, 3);
  ```

  **Note**: Protected attachment IDs can now be configured via the settings page.

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

- Settings are stored in the `wpdpi_options` option. All filters remain available for programmatic control.
- The plugin intentionally performs conservative checks to avoid accidental data loss. If a heuristic suggests the file may be in use, it will not delete it.
- For bulk operations (deleting many posts at once), consider disabling heavy scans temporarily via the settings page.

## Compatibility

- Requires WordPress 5.6+ and PHP 7.4+.

## Changelog

### 1.0.0

— Initial release.
— Detect attachment URL strings in postmeta by default; optional URL scans for term meta, options, and comments via filters; added before/after deletion actions.
— Added accessible admin settings page under Settings → Delete Post Media; all scans and post types now configurable via UI; improved performance with memoization.
