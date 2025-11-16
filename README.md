# Delete Attached Media on Post Deletion

When a post is permanently deleted (either by clicking "Delete Permanently" or emptying the Trash), this plugin also deletes any media attached to that post—provided those files are not used anywhere else on the site.

## Features

- Deletes attached media when a post is permanently deleted
- Skips media still used elsewhere (featured images, content references, postmeta IDs and URL strings, site icon, custom logo)
- Optional deep scans for URLs in term meta, options, and comments
- Includes the post's featured image even if it has no parent
- Runs only on permanent deletion (not when moving to trash)
- Accessible admin settings page under **Settings → Delete Post Media**
- Performance tuning: enable/disable individual scans for bulk operations
- Safe-by-default heuristics with extensible filters and actions
- Light UI feedback: progress overlay during deletion and a post-action summary notice
- Background processing: optional queued cleanup to prevent timeouts on bulk deletes

## ⚠️ Important Warning

**This plugin permanently deletes media files and this action cannot be undone.** While the plugin performs conservative checks to avoid deleting files that are still in use, you should always:

- **Backup your site** before using this plugin, especially before bulk deletions
- Test the plugin on a staging environment first
- Review the settings to ensure they match your needs

Once media files are deleted by this plugin, they cannot be recovered unless you have a backup.

## Installation

1. Copy the folder `wp-delete-post-images` into `wp-content/plugins/`.
2. Ensure the main file is at `wp-content/plugins/wp-delete-post-images/wp-delete-post-images.php`.
3. Activate the plugin in WP Admin → Plugins.
4. (Optional) Configure settings at **Settings → Delete Post Media**.

## How It Works

- Hooks into `before_delete_post` (fires only on permanent deletion, including emptying trash)
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

- **Process In Background**: Run media cleanup via a background queue to avoid timeouts during bulk deletions. Recommended.
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

### User Feedback

- A minimal progress overlay appears when you trigger delete actions (e.g., Delete Permanently, Empty Trash, bulk delete) so users see that cleanup is running.
- After the redirect, a small admin notice summarizes results (e.g., how many attachments were deleted or kept because they were still in use).

### Background Cleanup

- When enabled, the plugin queues attachments for cleanup and processes them in small batches via WP-Cron, reducing the chance of HTTP timeouts when deleting many posts at once (including Empty Trash).
- The first page load after enqueuing shows a notice indicating how many items were queued. As batches complete, items are deleted in the background.

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

## Translation

The plugin is fully translatable and includes:

- Spanish (es_ES) translation included
- POT file for creating additional translations at `languages/wp-delete-post-images.pot`

To contribute translations, copy the `.pot` file to a new `.po` file with your locale (e.g., `wp-delete-post-images-fr_FR.po`), translate the strings, and compile to `.mo` using a tool like Poedit or `msgfmt`.

## Changelog

### 1.0.0

— Initial release.
— Detect attachment URL strings in postmeta by default; optional URL scans for term meta, options, and comments via filters; added before/after deletion actions.
— Added accessible admin settings page under Settings → Delete Post Media; all scans and post types now configurable via UI; improved performance with memoization.
— Uses `before_delete_post` hook to properly handle both direct permanent deletion and emptying trash.
