<?php
/**
 * Plugin Name: Delete Attached Media on Post Deletion
 * Description: When a post is permanently deleted, also deletes its attached media if they are not used anywhere else on the site.
 * Version: 1.0.0
 * Author: Thomas McMahon
 * Text Domain: wp-delete-post-images
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 7.4
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Load plugin textdomain for translations.
 */
add_action( 'plugins_loaded', 'wpdpi_load_textdomain' );

/**
 * Load the plugin textdomain.
 *
 * @return void
 */
function wpdpi_load_textdomain(): void {
    load_plugin_textdomain(
        'wp-delete-post-images',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages'
    );
}

/**
 * Main hook: fire when a post is permanently deleted (not just trashed).
 *
 * Notes:
 * - We listen on 'deleted_post', which only fires on permanent deletion.
 * - We intentionally skip attachments, revisions, and menu items.
 *
 * @see https://developer.wordpress.org/reference/hooks/deleted_post/
 */
add_action( 'deleted_post', 'wpdpi_on_deleted_post', 10, 1 );

/**
 * Register admin settings page.
 */
add_action( 'admin_menu', 'wpdpi_register_settings_page' );
add_action( 'admin_init', 'wpdpi_register_settings' );

/**
 * Wire up saved settings to filters.
 */
add_filter( 'wpdpi_supported_post_types', 'wpdpi_apply_supported_post_types_setting' );
add_filter( 'wpdpi_enable_content_regex', 'wpdpi_apply_enable_content_regex_setting', 10, 3 );
add_filter( 'wpdpi_enable_filename_like', 'wpdpi_apply_enable_filename_like_setting', 10, 3 );
add_filter( 'wpdpi_enable_postmeta_id_scan', 'wpdpi_apply_enable_postmeta_id_scan_setting', 10, 3 );
add_filter( 'wpdpi_enable_postmeta_url_scan', 'wpdpi_apply_enable_postmeta_url_scan_setting', 10, 3 );
add_filter( 'wpdpi_scan_termmeta_for_urls', 'wpdpi_apply_scan_termmeta_for_urls_setting', 10, 3 );
add_filter( 'wpdpi_scan_options_for_urls', 'wpdpi_apply_scan_options_for_urls_setting', 10, 3 );
add_filter( 'wpdpi_scan_comments_for_urls', 'wpdpi_apply_scan_comments_for_urls_setting', 10, 3 );

/**
 * Apply supported post types setting.
 *
 * @param array<string> $types Current post types.
 * @return array<string>
 */
function wpdpi_apply_supported_post_types_setting( array $types ): array {
    $options = get_option( 'wpdpi_options', wpdpi_get_default_settings() );
    return ! empty( $options['supported_post_types'] ) ? $options['supported_post_types'] : $types;
}

/**
 * Apply enable_content_regex setting.
 *
 * @param bool $enabled Current value.
 * @param int  $attachment_id Attachment ID.
 * @param int  $original_post_id Original post ID.
 * @return bool
 */
function wpdpi_apply_enable_content_regex_setting( bool $enabled, int $attachment_id, int $original_post_id ): bool {
    $options = get_option( 'wpdpi_options', wpdpi_get_default_settings() );
    return ! empty( $options['enable_content_regex'] );
}

/**
 * Apply enable_filename_like setting.
 *
 * @param bool $enabled Current value.
 * @param int  $attachment_id Attachment ID.
 * @param int  $original_post_id Original post ID.
 * @return bool
 */
function wpdpi_apply_enable_filename_like_setting( bool $enabled, int $attachment_id, int $original_post_id ): bool {
    $options = get_option( 'wpdpi_options', wpdpi_get_default_settings() );
    return ! empty( $options['enable_filename_like'] );
}

/**
 * Apply enable_postmeta_id_scan setting.
 *
 * @param bool $enabled Current value.
 * @param int  $attachment_id Attachment ID.
 * @param int  $original_post_id Original post ID.
 * @return bool
 */
function wpdpi_apply_enable_postmeta_id_scan_setting( bool $enabled, int $attachment_id, int $original_post_id ): bool {
    $options = get_option( 'wpdpi_options', wpdpi_get_default_settings() );
    return ! empty( $options['enable_postmeta_id_scan'] );
}

/**
 * Apply enable_postmeta_url_scan setting.
 *
 * @param bool $enabled Current value.
 * @param int  $attachment_id Attachment ID.
 * @param int  $original_post_id Original post ID.
 * @return bool
 */
function wpdpi_apply_enable_postmeta_url_scan_setting( bool $enabled, int $attachment_id, int $original_post_id ): bool {
    $options = get_option( 'wpdpi_options', wpdpi_get_default_settings() );
    return ! empty( $options['enable_postmeta_url_scan'] );
}

/**
 * Apply scan_termmeta_for_urls setting.
 *
 * @param bool $enabled Current value.
 * @param int  $attachment_id Attachment ID.
 * @param int  $original_post_id Original post ID.
 * @return bool
 */
function wpdpi_apply_scan_termmeta_for_urls_setting( bool $enabled, int $attachment_id, int $original_post_id ): bool {
    $options = get_option( 'wpdpi_options', wpdpi_get_default_settings() );
    return ! empty( $options['scan_termmeta_for_urls'] );
}

/**
 * Apply scan_options_for_urls setting.
 *
 * @param bool $enabled Current value.
 * @param int  $attachment_id Attachment ID.
 * @param int  $original_post_id Original post ID.
 * @return bool
 */
function wpdpi_apply_scan_options_for_urls_setting( bool $enabled, int $attachment_id, int $original_post_id ): bool {
    $options = get_option( 'wpdpi_options', wpdpi_get_default_settings() );
    return ! empty( $options['scan_options_for_urls'] );
}

/**
 * Apply scan_comments_for_urls setting.
 *
 * @param bool $enabled Current value.
 * @param int  $attachment_id Attachment ID.
 * @param int  $original_post_id Original post ID.
 * @return bool
 */
function wpdpi_apply_scan_comments_for_urls_setting( bool $enabled, int $attachment_id, int $original_post_id ): bool {
    $options = get_option( 'wpdpi_options', wpdpi_get_default_settings() );
    return ! empty( $options['scan_comments_for_urls'] );
}

/**
 * Apply protected attachment IDs check to skip_delete filter.
 *
 * @param bool $skip Current skip value.
 * @param int  $attachment_id Attachment ID.
 * @param int  $original_post_id Original post ID.
 * @return bool
 */
function wpdpi_apply_protected_attachment_ids( bool $skip, int $attachment_id, int $original_post_id ): bool {
    if ( $skip ) {
        return true;
    }

    $options   = get_option( 'wpdpi_options', wpdpi_get_default_settings() );
    $protected = $options['protected_attachment_ids'] ?? [];

    return in_array( $attachment_id, $protected, true );
}

add_filter( 'wpdpi_skip_delete', 'wpdpi_apply_protected_attachment_ids', 10, 3 );

/**
 * Register the settings page under Settings menu.
 *
 * @return void
 */
function wpdpi_register_settings_page(): void {
    add_options_page(
        __( 'Delete Post Media Settings', 'wp-delete-post-images' ),
        __( 'Delete Post Media', 'wp-delete-post-images' ),
        'manage_options',
        'wpdpi-settings',
        'wpdpi_render_settings_page'
    );
}

/**
 * Register all settings fields and sections.
 *
 * @return void
 */
function wpdpi_register_settings(): void {
    $option_group = 'wpdpi_settings';
    $option_name  = 'wpdpi_options';

    register_setting(
        $option_group,
        $option_name,
        [
            'type'              => 'array',
            'sanitize_callback' => 'wpdpi_sanitize_settings',
            'default'           => wpdpi_get_default_settings(),
        ]
    );

    // Performance Scans Section.
    add_settings_section(
        'wpdpi_performance_section',
        __( 'Performance Settings', 'wp-delete-post-images' ),
        'wpdpi_render_performance_section_description',
        'wpdpi-settings'
    );

    add_settings_field(
        'enable_content_regex',
        __( 'Scan Post Content (REGEXP)', 'wp-delete-post-images' ),
        'wpdpi_render_checkbox_field',
        'wpdpi-settings',
        'wpdpi_performance_section',
        [
            'label_for'   => 'wpdpi_enable_content_regex',
            'option_name' => $option_name,
            'field_key'   => 'enable_content_regex',
            'description' => __( 'Search for attachment IDs in post content/excerpt using REGEXP (wp-image-, attachment_, Gutenberg JSON, etc.).', 'wp-delete-post-images' ),
        ]
    );

    add_settings_field(
        'enable_filename_like',
        __( 'Scan Post Content (Filename)', 'wp-delete-post-images' ),
        'wpdpi_render_checkbox_field',
        'wpdpi-settings',
        'wpdpi_performance_section',
        [
            'label_for'   => 'wpdpi_enable_filename_like',
            'option_name' => $option_name,
            'field_key'   => 'enable_filename_like',
            'description' => __( 'Search for filename matches in post content/excerpt (covers direct links and sized variants).', 'wp-delete-post-images' ),
        ]
    );

    add_settings_field(
        'enable_postmeta_id_scan',
        __( 'Scan Postmeta (ID)', 'wp-delete-post-images' ),
        'wpdpi_render_checkbox_field',
        'wpdpi-settings',
        'wpdpi_performance_section',
        [
            'label_for'   => 'wpdpi_enable_postmeta_id_scan',
            'option_name' => $option_name,
            'field_key'   => 'enable_postmeta_id_scan',
            'description' => __( 'Search for numeric attachment IDs in postmeta values (including serialized data).', 'wp-delete-post-images' ),
        ]
    );

    add_settings_field(
        'enable_postmeta_url_scan',
        __( 'Scan Postmeta (URL)', 'wp-delete-post-images' ),
        'wpdpi_render_checkbox_field',
        'wpdpi-settings',
        'wpdpi_performance_section',
        [
            'label_for'   => 'wpdpi_enable_postmeta_url_scan',
            'option_name' => $option_name,
            'field_key'   => 'enable_postmeta_url_scan',
            'description' => __( 'Search for attachment URLs in postmeta values (e.g., custom fields, page builders).', 'wp-delete-post-images' ),
        ]
    );

    // Deep Scans Section.
    add_settings_section(
        'wpdpi_deep_scans_section',
        __( 'Deep Scans (Optional)', 'wp-delete-post-images' ),
        'wpdpi_render_deep_scans_section_description',
        'wpdpi-settings'
    );

    add_settings_field(
        'scan_termmeta_for_urls',
        __( 'Scan Term Meta for URLs', 'wp-delete-post-images' ),
        'wpdpi_render_checkbox_field',
        'wpdpi-settings',
        'wpdpi_deep_scans_section',
        [
            'label_for'   => 'wpdpi_scan_termmeta_for_urls',
            'option_name' => $option_name,
            'field_key'   => 'scan_termmeta_for_urls',
            'description' => __( 'Search for attachment URLs in term metadata. May impact performance on large sites.', 'wp-delete-post-images' ),
        ]
    );

    add_settings_field(
        'scan_options_for_urls',
        __( 'Scan Options for URLs', 'wp-delete-post-images' ),
        'wpdpi_render_checkbox_field',
        'wpdpi-settings',
        'wpdpi_deep_scans_section',
        [
            'label_for'   => 'wpdpi_scan_options_for_urls',
            'option_name' => $option_name,
            'field_key'   => 'scan_options_for_urls',
            'description' => __( 'Search for attachment URLs in site options. May impact performance.', 'wp-delete-post-images' ),
        ]
    );

    add_settings_field(
        'scan_comments_for_urls',
        __( 'Scan Comments for URLs', 'wp-delete-post-images' ),
        'wpdpi_render_checkbox_field',
        'wpdpi-settings',
        'wpdpi_deep_scans_section',
        [
            'label_for'   => 'wpdpi_scan_comments_for_urls',
            'option_name' => $option_name,
            'field_key'   => 'scan_comments_for_urls',
            'description' => __( 'Search for attachment URLs in comment content. May impact performance.', 'wp-delete-post-images' ),
        ]
    );

    // Post Types Section.
    add_settings_section(
        'wpdpi_post_types_section',
        __( 'Supported Post Types', 'wp-delete-post-images' ),
        'wpdpi_render_post_types_section_description',
        'wpdpi-settings'
    );

    add_settings_field(
        'supported_post_types',
        __( 'Limit to Post Types', 'wp-delete-post-images' ),
        'wpdpi_render_post_types_field',
        'wpdpi-settings',
        'wpdpi_post_types_section',
        [
            'label_for'   => 'wpdpi_supported_post_types',
            'option_name' => $option_name,
            'field_key'   => 'supported_post_types',
        ]
    );

    // Protection Section.
    add_settings_section(
        'wpdpi_protection_section',
        __( 'Protected Attachments', 'wp-delete-post-images' ),
        'wpdpi_render_protection_section_description',
        'wpdpi-settings'
    );

    add_settings_field(
        'protected_attachment_ids',
        __( 'Protected Attachment IDs', 'wp-delete-post-images' ),
        'wpdpi_render_protected_ids_field',
        'wpdpi-settings',
        'wpdpi_protection_section',
        [
            'label_for'   => 'wpdpi_protected_attachment_ids',
            'option_name' => $option_name,
            'field_key'   => 'protected_attachment_ids',
        ]
    );
}

/**
 * Get default settings.
 *
 * @return array<string,mixed>
 */
function wpdpi_get_default_settings(): array {
    return [
        'enable_content_regex'     => true,
        'enable_filename_like'     => true,
        'enable_postmeta_id_scan'  => true,
        'enable_postmeta_url_scan' => true,
        'scan_termmeta_for_urls'   => false,
        'scan_options_for_urls'    => false,
        'scan_comments_for_urls'   => false,
        'supported_post_types'     => [],
        'protected_attachment_ids' => [],
    ];
}

/**
 * Sanitize settings before saving.
 *
 * @param mixed $input Raw input from form.
 * @return array<string,mixed>
 */
function wpdpi_sanitize_settings( $input ): array {
    $defaults   = wpdpi_get_default_settings();
    $sanitized  = [];

    // Sanitize boolean fields.
    $bool_fields = [
        'enable_content_regex',
        'enable_filename_like',
        'enable_postmeta_id_scan',
        'enable_postmeta_url_scan',
        'scan_termmeta_for_urls',
        'scan_options_for_urls',
        'scan_comments_for_urls',
    ];

    foreach ( $bool_fields as $field ) {
        $sanitized[ $field ] = ! empty( $input[ $field ] );
    }

    // Sanitize post types array.
    $sanitized['supported_post_types'] = [];
    if ( ! empty( $input['supported_post_types'] ) && is_array( $input['supported_post_types'] ) ) {
        $sanitized['supported_post_types'] = array_map( 'sanitize_key', $input['supported_post_types'] );
    }

    // Sanitize protected attachment IDs.
    $sanitized['protected_attachment_ids'] = [];
    if ( ! empty( $input['protected_attachment_ids'] ) ) {
        if ( is_string( $input['protected_attachment_ids'] ) ) {
            // Parse comma-separated list.
            $ids = array_map( 'trim', explode( ',', $input['protected_attachment_ids'] ) );
            $ids = array_filter( $ids, 'is_numeric' );
            $sanitized['protected_attachment_ids'] = array_map( 'intval', $ids );
        } elseif ( is_array( $input['protected_attachment_ids'] ) ) {
            $sanitized['protected_attachment_ids'] = array_map( 'intval', $input['protected_attachment_ids'] );
        }
    }

    return $sanitized;
}

/**
 * Render performance section description.
 *
 * @return void
 */
function wpdpi_render_performance_section_description(): void {
    echo '<p>' . esc_html__( 'Control which scans run when checking if an attachment is used elsewhere. Disable heavy scans to improve performance during bulk deletions.', 'wp-delete-post-images' ) . '</p>';
}

/**
 * Render deep scans section description.
 *
 * @return void
 */
function wpdpi_render_deep_scans_section_description(): void {
    echo '<p>' . esc_html__( 'These scans search additional database tables. Only enable if you store attachment URLs in these locations. May impact performance on large sites.', 'wp-delete-post-images' ) . '</p>';
}

/**
 * Render post types section description.
 *
 * @return void
 */
function wpdpi_render_post_types_section_description(): void {
    echo '<p>' . esc_html__( 'By default, the plugin processes all post types except revisions, nav menu items, and attachments. Select specific post types to limit which posts trigger media cleanup.', 'wp-delete-post-images' ) . '</p>';
}

/**
 * Render protection section description.
 *
 * @return void
 */
function wpdpi_render_protection_section_description(): void {
    echo '<p>' . esc_html__( 'Specify attachment IDs that should never be deleted, regardless of usage detection.', 'wp-delete-post-images' ) . '</p>';
}

/**
 * Render a checkbox field.
 *
 * @param array<string,mixed> $args Field arguments.
 * @return void
 */
function wpdpi_render_checkbox_field( array $args ): void {
    $option_name = $args['option_name'];
    $field_key   = $args['field_key'];
    $options     = get_option( $option_name, wpdpi_get_default_settings() );
    $checked     = ! empty( $options[ $field_key ] );
    $id          = $args['label_for'];
    $description = $args['description'] ?? '';

    printf(
        '<label for="%s"><input type="checkbox" id="%s" name="%s[%s]" value="1" %s /> %s</label>',
        esc_attr( $id ),
        esc_attr( $id ),
        esc_attr( $option_name ),
        esc_attr( $field_key ),
        checked( $checked, true, false ),
        esc_html__( 'Enable', 'wp-delete-post-images' )
    );

    if ( $description ) {
        printf(
            '<p class="description">%s</p>',
            esc_html( $description )
        );
    }
}

/**
 * Render post types multiselect field.
 *
 * @param array<string,mixed> $args Field arguments.
 * @return void
 */
function wpdpi_render_post_types_field( array $args ): void {
    $option_name = $args['option_name'];
    $field_key   = $args['field_key'];
    $options     = get_option( $option_name, wpdpi_get_default_settings() );
    $selected    = $options[ $field_key ] ?? [];

    $post_types = get_post_types( [ 'public' => true ], 'objects' );

    echo '<fieldset>';
    echo '<legend class="screen-reader-text">' . esc_html__( 'Select post types to process', 'wp-delete-post-images' ) . '</legend>';

    if ( empty( $post_types ) ) {
        echo '<p>' . esc_html__( 'No public post types found.', 'wp-delete-post-images' ) . '</p>';
    } else {
        foreach ( $post_types as $post_type ) {
            $id      = 'wpdpi_post_type_' . $post_type->name;
            $checked = in_array( $post_type->name, $selected, true );

            printf(
                '<label for="%s" style="display: block; margin-bottom: 0.5em;"><input type="checkbox" id="%s" name="%s[%s][]" value="%s" %s /> %s</label>',
                esc_attr( $id ),
                esc_attr( $id ),
                esc_attr( $option_name ),
                esc_attr( $field_key ),
                esc_attr( $post_type->name ),
                checked( $checked, true, false ),
                esc_html( $post_type->label )
            );
        }
    }

    echo '<p class="description">' . esc_html__( 'Leave empty to process all post types (except revisions, nav menu items, and attachments).', 'wp-delete-post-images' ) . '</p>';
    echo '</fieldset>';
}

/**
 * Render protected attachment IDs field.
 *
 * @param array<string,mixed> $args Field arguments.
 * @return void
 */
function wpdpi_render_protected_ids_field( array $args ): void {
    $option_name = $args['option_name'];
    $field_key   = $args['field_key'];
    $options     = get_option( $option_name, wpdpi_get_default_settings() );
    $protected   = $options[ $field_key ] ?? [];
    $value       = ! empty( $protected ) ? implode( ', ', $protected ) : '';
    $id          = $args['label_for'];

    printf(
        '<label for="%s" class="screen-reader-text">%s</label>',
        esc_attr( $id ),
        esc_html__( 'Enter comma-separated attachment IDs', 'wp-delete-post-images' )
    );

    printf(
        '<input type="text" id="%s" name="%s[%s]" value="%s" class="regular-text" placeholder="%s" aria-describedby="%s_description" />',
        esc_attr( $id ),
        esc_attr( $option_name ),
        esc_attr( $field_key ),
        esc_attr( $value ),
        esc_attr__( 'e.g., 123, 456, 789', 'wp-delete-post-images' ),
        esc_attr( $id )
    );

    printf(
        '<p class="description" id="%s_description">%s</p>',
        esc_attr( $id ),
        esc_html__( 'Enter attachment IDs separated by commas. These attachments will never be deleted by this plugin.', 'wp-delete-post-images' )
    );
}

/**
 * Render the settings page.
 *
 * @return void
 */
function wpdpi_render_settings_page(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Handle settings update message.
    if ( isset( $_GET['settings-updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        add_settings_error(
            'wpdpi_messages',
            'wpdpi_message',
            __( 'Settings saved.', 'wp-delete-post-images' ),
            'success'
        );
    }

    settings_errors( 'wpdpi_messages' );

    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

        <form method="post" action="options.php">
            <?php
            settings_fields( 'wpdpi_settings' );
            do_settings_sections( 'wpdpi-settings' );
            submit_button( __( 'Save Settings', 'wp-delete-post-images' ) );
            ?>
        </form>

        <hr />

        <h2><?php esc_html_e( 'How It Works', 'wp-delete-post-images' ); ?></h2>
        <p><?php esc_html_e( 'When a post is permanently deleted, this plugin automatically deletes attached media files—but only if they are not used anywhere else on your site.', 'wp-delete-post-images' ); ?></p>

        <h3><?php esc_html_e( 'Checks Performed', 'wp-delete-post-images' ); ?></h3>
        <ul style="list-style: disc; margin-left: 2em;">
            <li><?php esc_html_e( 'Featured image of another post', 'wp-delete-post-images' ); ?></li>
            <li><?php esc_html_e( 'Site icon or custom logo', 'wp-delete-post-images' ); ?></li>
            <li><?php esc_html_e( 'Referenced in post content/excerpt (IDs, classes, filenames)', 'wp-delete-post-images' ); ?></li>
            <li><?php esc_html_e( 'Stored in postmeta (IDs or URLs)', 'wp-delete-post-images' ); ?></li>
            <li><?php esc_html_e( 'Optionally: term meta, options, and comments (if enabled above)', 'wp-delete-post-images' ); ?></li>
        </ul>

        <p><?php esc_html_e( 'The plugin performs conservative checks to avoid accidental data loss. If any check suggests the file may be in use, it will not be deleted.', 'wp-delete-post-images' ); ?></p>
    </div>
    <?php
}

/**
 * Handle post permanent deletion by removing attached media that are not used elsewhere.
 *
 * @param int $post_id The deleted post ID.
 * @return void
 */
function wpdpi_on_deleted_post( int $post_id ): void {
    $post = get_post( $post_id );

    if ( ! $post instanceof \WP_Post ) {
        return;
    }

    // Ignore unsupported post types.
    $unsupported_types = [ 'revision', 'nav_menu_item', 'attachment' ];
    $supported_types   = (array) apply_filters( 'wpdpi_supported_post_types', [] );

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
    $children = get_children(
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
    $thumb_id = get_post_thumbnail_id( $post_id );
    if ( $thumb_id ) {
        $attachment_ids[] = (int) $thumb_id;
    }

    $attachment_ids = array_values( array_unique( array_filter( $attachment_ids ) ) );

    if ( empty( $attachment_ids ) ) {
        return;
    }

    foreach ( $attachment_ids as $attachment_id ) {
        // Skip if attachment no longer exists or already deleted.
        $attachment = get_post( $attachment_id );
        if ( ! $attachment instanceof \WP_Post || 'attachment' !== $attachment->post_type ) {
            continue;
        }

        $used_elsewhere = wpdpi_attachment_is_used_elsewhere( $attachment_id, $post_id );

        /**
         * Filter whether to skip deleting a specific attachment.
         *
         * @param bool $skip             True to skip deleting this attachment.
         * @param int  $attachment_id    The attachment ID being considered.
         * @param int  $original_post_id The deleted post ID.
         */
        $skip = (bool) apply_filters( 'wpdpi_skip_delete', $used_elsewhere, $attachment_id, $post_id );

        if ( $skip ) {
            continue;
        }

        // Allow integrations to react before deletion.
        do_action( 'wpdpi_before_delete_attachment', $attachment_id, $post_id );

        // Force delete the attachment and its files.
        wp_delete_attachment( $attachment_id, true );

        // Allow integrations to react after deletion.
        do_action( 'wpdpi_after_delete_attachment', $attachment_id, $post_id );
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
function wpdpi_attachment_is_used_elsewhere( int $attachment_id, int $original_post_id ): bool {
    global $wpdb;

    // Simple memoization across a single request to avoid duplicate scans.
    static $memo = [];
    $memo_key = $attachment_id . '|' . $original_post_id;
    if ( array_key_exists( $memo_key, $memo ) ) {
        return (bool) $memo[ $memo_key ];
    }

    // Sanity: if the file does not exist anymore, treat as unused.
    $file_path = get_attached_file( $attachment_id );
    $file_base = $file_path ? wp_basename( $file_path ) : '';
    $url       = (string) wp_get_attachment_url( $attachment_id );
    $url_path  = $url ? (string) wp_parse_url( $url, PHP_URL_PATH ) : '';

    // Allow sites to tune heavier scans for performance.
    $enable_content_regex     = (bool) apply_filters( 'wpdpi_enable_content_regex', true, $attachment_id, $original_post_id );
    $enable_filename_like     = (bool) apply_filters( 'wpdpi_enable_filename_like', true, $attachment_id, $original_post_id );
    $enable_postmeta_id_scan  = (bool) apply_filters( 'wpdpi_enable_postmeta_id_scan', true, $attachment_id, $original_post_id );
    $enable_postmeta_url_scan = (bool) apply_filters( 'wpdpi_enable_postmeta_url_scan', true, $attachment_id, $original_post_id );

    // 0) Site-wide special uses: site icon and custom logo.
    $site_icon_id = (int) get_option( 'site_icon' );
    if ( $site_icon_id && $site_icon_id === $attachment_id ) {
        $memo[ $memo_key ] = true;
        return true;
    }

    $custom_logo_id = (int) get_theme_mod( 'custom_logo' );
    if ( $custom_logo_id && $custom_logo_id === $attachment_id ) {
        $memo[ $memo_key ] = true;
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
        $memo[ $memo_key ] = true;
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
    if ( $enable_content_regex && ! empty( $id_regex_parts ) ) {
        $regex = '(' . implode( '|', array_map( 'wpdpi_preg_quote_for_mysql_regex', $id_regex_parts ) ) . ')';
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $regex is safely built using wpdpi_preg_quote_for_mysql_regex.
        $content_in_use = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT 1 FROM {$wpdb->posts} p\n                 WHERE p.ID <> %d AND p.post_status <> 'trash'\n                   AND p.post_type NOT IN ('revision','nav_menu_item','attachment')\n                   AND (p.post_content REGEXP %s OR p.post_excerpt REGEXP %s)\n                 LIMIT 1",
                $original_post_id,
                $regex,
                $regex
            )
        );
        if ( $content_in_use ) {
            $memo[ $memo_key ] = true;
            return true;
        }
    }

    // 2b) Referenced by filename (covers direct links and sized variants). Conservative and may false-positive on same-name files.
    if ( $file_base && $enable_filename_like ) {
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
            $memo[ $memo_key ] = true;
            return true;
        }
    }

    // 2c) URLs stored in postmeta (e.g., custom fields, builders) as full or path-only URLs.
    if ( $enable_postmeta_url_scan && $url ) {
        $like_url = '%' . $wpdb->esc_like( $url ) . '%';
        $meta_url_in_use = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT 1 FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON pm.post_id = p.ID\n                 WHERE p.ID <> %d AND p.post_status <> 'trash'\n                   AND pm.meta_value LIKE %s\n                 LIMIT 1",
                $original_post_id,
                $like_url
            )
        );
        if ( $meta_url_in_use ) {
            $memo[ $memo_key ] = true;
            return true;
        }
    }

    if ( $enable_postmeta_url_scan && $url_path ) {
        $like_path = '%' . $wpdb->esc_like( $url_path ) . '%';
        $meta_path_in_use = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT 1 FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON pm.post_id = p.ID\n                 WHERE p.ID <> %d AND p.post_status <> 'trash'\n                   AND pm.meta_value LIKE %s\n                 LIMIT 1",
                $original_post_id,
                $like_path
            )
        );
        if ( $meta_path_in_use ) {
            $memo[ $memo_key ] = true;
            return true;
        }
    }

    // 3) Present in other postmeta values (as integer or inside serialized/JSON). Heuristic numeric boundary matching.
    $boundary_regex = '(^|[^0-9])' . (int) $attachment_id . '([^0-9]|$)';
    
    if ( $enable_postmeta_id_scan ) {
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
            $memo[ $memo_key ] = true;
            return true;
        }
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
            $memo[ $memo_key ] = true;
            return true;
        }
    }

    // 4b) Optional: scan termmeta, options, and comments for URL strings (off by default for performance).
    $scan_termmeta_urls = (bool) apply_filters( 'wpdpi_scan_termmeta_for_urls', false, $attachment_id, $original_post_id );
    if ( $scan_termmeta_urls && ! empty( $wpdb->termmeta ) ) {
        if ( $url ) {
            $like_url = '%' . $wpdb->esc_like( $url ) . '%';
            $termmeta_url_in_use = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT 1 FROM {$wpdb->termmeta} tm\n                     WHERE tm.meta_value LIKE %s\n                     LIMIT 1",
                    $like_url
                )
            );
            if ( $termmeta_url_in_use ) {
                $memo[ $memo_key ] = true;
                return true;
            }
        }
        if ( $url_path ) {
            $like_path = '%' . $wpdb->esc_like( $url_path ) . '%';
            $termmeta_path_in_use = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT 1 FROM {$wpdb->termmeta} tm\n                     WHERE tm.meta_value LIKE %s\n                     LIMIT 1",
                    $like_path
                )
            );
            if ( $termmeta_path_in_use ) {
                $memo[ $memo_key ] = true;
                return true;
            }
        }
    }

    $scan_options = (bool) apply_filters( 'wpdpi_scan_options_for_urls', false, $attachment_id, $original_post_id );
    if ( $scan_options ) {
        if ( $url ) {
            $like_url = '%' . $wpdb->esc_like( $url ) . '%';
            $option_in_use = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT 1 FROM {$wpdb->options} o\n                     WHERE o.option_value LIKE %s\n                     LIMIT 1",
                    $like_url
                )
            );
            if ( $option_in_use ) {
                $memo[ $memo_key ] = true;
                return true;
            }
        }
        if ( $url_path ) {
            $like_path = '%' . $wpdb->esc_like( $url_path ) . '%';
            $option_path_in_use = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT 1 FROM {$wpdb->options} o\n                     WHERE o.option_value LIKE %s\n                     LIMIT 1",
                    $like_path
                )
            );
            if ( $option_path_in_use ) {
                $memo[ $memo_key ] = true;
                return true;
            }
        }
    }

    $scan_comments = (bool) apply_filters( 'wpdpi_scan_comments_for_urls', false, $attachment_id, $original_post_id );
    if ( $scan_comments ) {
        if ( $url ) {
            $like_url = '%' . $wpdb->esc_like( $url ) . '%';
            $comment_in_use = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT 1 FROM {$wpdb->comments} c\n                     WHERE c.comment_content LIKE %s\n                     LIMIT 1",
                    $like_url
                )
            );
            if ( $comment_in_use ) {
                $memo[ $memo_key ] = true;
                return true;
            }
        }
        if ( $url_path ) {
            $like_path = '%' . $wpdb->esc_like( $url_path ) . '%';
            $comment_path_in_use = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT 1 FROM {$wpdb->comments} c\n                     WHERE c.comment_content LIKE %s\n                     LIMIT 1",
                    $like_path
                )
            );
            if ( $comment_path_in_use ) {
                $memo[ $memo_key ] = true;
                return true;
            }
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
    $used = (bool) apply_filters( 'wpdpi_attachment_used_elsewhere', false, $attachment_id, $original_post_id );
    $memo[ $memo_key ] = $used;
    return $used;
}

/**
 * Utility: escape a string to be safely embedded inside a MySQL REGEXP literal.
 * Similar aim as preg_quote, but tailored for MySQL's REGEXP syntax.
 *
 * @param string $raw Raw pattern text.
 * @return string
 */
function wpdpi_preg_quote_for_mysql_regex( string $raw ): string {
    // Escape regex special characters. MySQL REGEXP uses POSIX ERE; this over-escapes for safety.
    $special = [ '\\', '.', '+', '*', '?', '[', '^', ']', '$', '(', ')', '{', '}', '=', '!', '<', '>', '|', ':', '-' ];
    $escaped = str_replace( $special, array_map( static function ( $c ) { return '\\' . $c; }, $special ), $raw );
    return $escaped;
}
