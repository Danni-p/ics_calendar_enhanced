<?php
/**
 * Settings Page Class
 *
 * @package ICSEnhanced
 */

declare(strict_types=1);

namespace ICSEnhanced\Admin;

use ICSEnhanced\Includes\Helpers;

/**
 * Settings_Page Class - Admin settings page for category image mappings.
 */
final class Settings_Page {

    /**
     * Page slug.
     */
    public const PAGE_SLUG = 'ics-calendar-enhanced';

    /**
     * Nonce action.
     */
    private const NONCE_ACTION = 'ics_enhanced_save_settings';

    /**
     * Nonce field name.
     */
    private const NONCE_FIELD = 'ics_enhanced_nonce';

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
        add_action( 'admin_init', [ $this, 'handle_form_submission' ] );
    }

    /**
     * Add the settings page to the admin menu.
     */
    public function add_menu_page(): void {
        add_options_page(
            __( 'ICS Calendar Enhanced', 'ics-calendar-enhanced' ),
            __( 'ICS Calendar Enhanced', 'ics-calendar-enhanced' ),
            'manage_options',
            self::PAGE_SLUG,
            [ $this, 'render_settings_page' ]
        );
    }

    /**
     * Handle form submission.
     */
    public function handle_form_submission(): void {
        // Check if form was submitted
        if ( ! isset( $_POST[ self::NONCE_FIELD ] ) ) {
            return;
        }

        // Verify nonce
        if ( ! wp_verify_nonce( $_POST[ self::NONCE_FIELD ], self::NONCE_ACTION ) ) {
            add_settings_error(
                'ics_enhanced_settings',
                'invalid_nonce',
                __( 'Security check failed. Please try again.', 'ics-calendar-enhanced' ),
                'error'
            );
            return;
        }

        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            add_settings_error(
                'ics_enhanced_settings',
                'insufficient_permissions',
                __( 'You do not have permission to change these settings.', 'ics-calendar-enhanced' ),
                'error'
            );
            return;
        }

        // Process category mappings with images and colors
        $mappings = [];
        if ( isset( $_POST['ics_enhanced_categories'] ) ) {
            $categories = array_map( 'sanitize_text_field', (array) $_POST['ics_enhanced_categories'] );
            $images = isset( $_POST['ics_enhanced_images'] ) 
                ? array_map( 'absint', (array) $_POST['ics_enhanced_images'] ) 
                : [];
            $colors = isset( $_POST['ics_enhanced_colors'] ) 
                ? array_map( 'sanitize_text_field', (array) $_POST['ics_enhanced_colors'] ) 
                : [];

            foreach ( $categories as $index => $category ) {
                $category = trim( $category );
                $image_id = $images[ $index ] ?? 0;
                $color = Category_Mapper::sanitize_color( $colors[ $index ] ?? '' );

                // Only save if category is not empty and has at least image or color
                if ( ! empty( $category ) && ( $image_id > 0 || ! empty( $color ) ) ) {
                    $mappings[ $category ] = [
                        'image_id' => $image_id,
                        'color'    => $color,
                    ];
                }
            }
        }

        // Save mappings
        Category_Mapper::save_mappings( $mappings );

        // Save fallback
        $general_fallback = isset( $_POST['ics_enhanced_general_fallback'] ) 
            ? absint( $_POST['ics_enhanced_general_fallback'] ) 
            : 0;
        Category_Mapper::save_general_fallback( $general_fallback );

        // Add success message
        add_settings_error(
            'ics_enhanced_settings',
            'settings_saved',
            __( 'Settings saved successfully.', 'ics-calendar-enhanced' ),
            'success'
        );
    }

    /**
     * Render the settings page.
     */
    public function render_settings_page(): void {
        $mappings = Category_Mapper::get_mappings();
        $general_fallback = Category_Mapper::get_general_fallback();
        ?>
        <div class="wrap ics-enhanced-settings">
            <h1><?php esc_html_e( 'ICS Calendar Enhanced Settings', 'ics-calendar-enhanced' ); ?></h1>
            
            <?php settings_errors( 'ics_enhanced_settings' ); ?>

            <form method="post" action="">
                <?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD ); ?>

                <!-- Category Mappings Section -->
                <div class="ics-enhanced-section">
                    <h2><?php esc_html_e( 'Category Image Mappings', 'ics-calendar-enhanced' ); ?></h2>
                    <p class="description">
                        <?php esc_html_e( 'Map category strings from your ICS calendar feeds to images and colors. The category string should match exactly what appears in your calendar events.', 'ics-calendar-enhanced' ); ?>
                    </p>

                    <table class="wp-list-table widefat fixed striped" id="ics-enhanced-mappings-table">
                        <thead>
                            <tr>
                                <th class="column-category"><?php esc_html_e( 'Category String', 'ics-calendar-enhanced' ); ?></th>
                                <th class="column-image"><?php esc_html_e( 'Image', 'ics-calendar-enhanced' ); ?></th>
                                <th class="column-color"><?php esc_html_e( 'Color', 'ics-calendar-enhanced' ); ?></th>
                                <th class="column-actions"><?php esc_html_e( 'Actions', 'ics-calendar-enhanced' ); ?></th>
                            </tr>
                        </thead>
                        <tbody id="ics-enhanced-mappings-body">
                            <?php if ( ! empty( $mappings ) ) : ?>
                                <?php foreach ( $mappings as $category => $data ) : ?>
                                    <?php 
                                    $image_id = $data['image_id'] ?? 0;
                                    $color = $data['color'] ?? '';
                                    $this->render_mapping_row( $category, $image_id, $color ); 
                                    ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <p>
                        <button type="button" class="button button-secondary" id="ics-enhanced-add-mapping">
                            <?php esc_html_e( 'Add New Mapping', 'ics-calendar-enhanced' ); ?>
                        </button>
                    </p>
                </div>

                <!-- Fallback Section -->
                <div class="ics-enhanced-section">
                    <h2><?php esc_html_e( 'Fallback Image', 'ics-calendar-enhanced' ); ?></h2>
                    <p class="description">
                        <?php esc_html_e( 'This image will be shown for any category that is not explicitly mapped above. If not set, a bundled default image will be used.', 'ics-calendar-enhanced' ); ?>
                    </p>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="ics-enhanced-general-fallback">
                                    <?php esc_html_e( 'Fallback', 'ics-calendar-enhanced' ); ?>
                                </label>
                            </th>
                            <td>
                                <div class="ics-enhanced-image-field">
                                    <input type="hidden" 
                                           name="ics_enhanced_general_fallback" 
                                           id="ics-enhanced-general-fallback"
                                           value="<?php echo esc_attr( $general_fallback ); ?>" />
                                    
                                    <div class="ics-enhanced-image-preview" id="ics-enhanced-general-fallback-preview">
                                        <?php if ( $general_fallback > 0 ) : ?>
                                            <?php echo wp_get_attachment_image( $general_fallback, 'thumbnail' ); ?>
                                        <?php endif; ?>
                                    </div>

                                    <button type="button" class="button ics-enhanced-select-image" data-target="ics-enhanced-general-fallback">
                                        <?php esc_html_e( 'Select Image', 'ics-calendar-enhanced' ); ?>
                                    </button>
                                    <button type="button" class="button ics-enhanced-remove-image" data-target="ics-enhanced-general-fallback" <?php echo $general_fallback <= 0 ? 'style="display:none;"' : ''; ?>>
                                        <?php esc_html_e( 'Remove', 'ics-calendar-enhanced' ); ?>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>

                <?php submit_button( __( 'Save Settings', 'ics-calendar-enhanced' ) ); ?>
            </form>
        </div>

        <!-- Row Template for JavaScript -->
        <script type="text/template" id="ics-enhanced-row-template">
            <?php $this->render_mapping_row( '', 0, '', true ); ?>
        </script>
        <?php
    }

    /**
     * Render a single mapping row.
     *
     * @param string $category    Category string.
     * @param int    $image_id    Image attachment ID.
     * @param string $color       Hex color code.
     * @param bool   $is_template Whether this is a template row.
     */
    private function render_mapping_row( string $category, int $image_id, string $color = '', bool $is_template = false ): void {
        $row_id = $is_template ? '{{INDEX}}' : uniqid( 'row_' );
        ?>
        <tr class="ics-enhanced-mapping-row" data-row-id="<?php echo esc_attr( $row_id ); ?>">
            <td class="column-category">
                <input type="text" 
                       name="ics_enhanced_categories[]" 
                       value="<?php echo esc_attr( $category ); ?>" 
                       placeholder="<?php esc_attr_e( 'Enter category name...', 'ics-calendar-enhanced' ); ?>"
                       class="regular-text ics-enhanced-category-input" />
            </td>
            <td class="column-image">
                <div class="ics-enhanced-image-field">
                    <input type="hidden" 
                           name="ics_enhanced_images[]" 
                           class="ics-enhanced-image-id"
                           value="<?php echo esc_attr( $image_id ); ?>" />
                    
                    <div class="ics-enhanced-image-preview ics-enhanced-row-preview">
                        <?php if ( $image_id > 0 ) : ?>
                            <?php echo wp_get_attachment_image( $image_id, 'thumbnail' ); ?>
                        <?php endif; ?>
                    </div>

                    <button type="button" class="button button-small ics-enhanced-select-row-image">
                        <?php esc_html_e( 'Select', 'ics-calendar-enhanced' ); ?>
                    </button>
                </div>
            </td>
            <td class="column-color">
                <input type="text" 
                       name="ics_enhanced_colors[]" 
                       value="<?php echo esc_attr( $color ); ?>" 
                       class="ics-enhanced-color-picker"
                       data-default-color="" />
            </td>
            <td class="column-actions">
                <button type="button" class="button button-small button-link-delete ics-enhanced-remove-row">
                    <?php esc_html_e( 'Remove', 'ics-calendar-enhanced' ); ?>
                </button>
            </td>
        </tr>
        <?php
    }
}
