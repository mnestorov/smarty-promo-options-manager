<?php
/**
 * Plugin Name:             SM - Promo Options Manager for WooCommerce
 * Plugin URI:              https://github.com/mnestorov/smarty-promo-options-manager
 * Description:             Manage promotional options for WooCommerce products with customizable labels and styles.
 * Version:                 1.0.2
 * Author:                  Martin Nestorov
 * Author URI:              https://github.com/mnestorov
 * Text Domain:             smarty-promo-options-manager
 * Domain Path:             /languages/
 * WC requires at least:    3.5.0
 * WC tested up to:         9.6.0
 * Requires Plugins:        woocommerce
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

/**
 * HPOS Compatibility Declaration.
 *
 * This ensures that the plugin explicitly declares compatibility with 
 * WooCommerce's High-Performance Order Storage (HPOS).
 * 
 * HPOS replaces the traditional `wp_posts` and `wp_postmeta` storage system 
 * for orders with a dedicated database table structure, improving scalability 
 * and performance.
 * 
 * More details:
 * - WooCommerce HPOS Documentation: 
 *   https://developer.woocommerce.com/2022/09/12/high-performance-order-storage-in-woocommerce/
 * - Declaring Plugin Compatibility: 
 *   https://github.com/woocommerce/woocommerce/wiki/High-Performance-Order-Storage-Upgrade-Recipe-Book#how-to-declare-compatibility
 */
add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

if (!function_exists('smarty_po_enqueue_admin_scripts')) {
    /**
     * Enqueues admin scripts and styles for the settings page.
     *
     * @since 1.0.0
     * @param string $hook_suffix The current admin page hook suffix.
     */
    function smarty_po_enqueue_admin_scripts($hook_suffix) {
        // Only add to the admin page of the plugin
        if ('woocommerce_page_smarty-po-settings' !== $hook_suffix) {
            return;
        }

        wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js', array('jquery'), '4.0.13', true);
        wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css', array(), '4.0.13');
        wp_enqueue_script('smarty-po-admin-js', plugin_dir_url(__FILE__) . 'js/smarty-po-admin.js', array('jquery', 'select2'), '1.0.0', true);
        wp_enqueue_style('smarty-po-admin-css', plugin_dir_url(__FILE__) . 'css/smarty-po-admin.css', array(), '1.0.0');

        // Enqueue style and script for using the WordPress color picker.
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');

        wp_localize_script(
            'smarty-po-admin-js',
            'smartyPromoOptionsManager',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'siteUrl' => site_url(),
                'nonce'   => wp_create_nonce('smarty_promo_options_nonce'),
            )
        );
    }
    add_action('admin_enqueue_scripts', 'smarty_po_enqueue_admin_scripts');
}

if (!function_exists('smarty_po_register_settings')) {
    /**
     * Registers settings, sections, and fields for the plugin.
     * 
     * @since 1.0.0
     */
    function smarty_po_register_settings() {
        // Register settings
        register_setting('smarty_po_settings_group', 'smarty_po_enable_labels', [
            'type'              => 'string',
            'sanitize_callback' => function($value) {
                return $value === '1' ? '1' : '0';
            },
            'default' => '0',
        ]);

        $color_fields = ['smarty_po_border_color', 'smarty_po_bg_color', 'smarty_po_text_color'];
        foreach ($color_fields as $field) {
            register_setting('smarty_po_settings_group', $field, [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_hex_color',
            ]);
        }

        // Font sizes: integer between 10–30
        $font_size_callback = function ($value) {
            $int = intval($value);
            return ($int < 10) ? 10 : (($int > 30) ? 30 : $int);
        };

        // Text field for promo message
        register_setting('smarty_po_settings_group', 'smarty_po_text', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ]);

        // Float promo percent (cast to float and limit to 0–100)
        register_setting('smarty_po_settings_group', 'smarty_po_number', [
            'type' => 'number',
            'sanitize_callback' => function ($value) {
                $float = floatval($value);
                return ($float < 0) ? 0 : (($float > 100) ? 100 : $float);
            },
        ]);

        register_setting('smarty_po_settings_group', 'smarty_po_d_font_size', [
            'type' => 'integer',
            'sanitize_callback' => $font_size_callback,
        ]);

        register_setting('smarty_po_settings_group', 'smarty_po_m_font_size', [
            'type' => 'integer',
            'sanitize_callback' => $font_size_callback,
        ]);
        
        // Add settings sections
        add_settings_section('smarty_po_general_section', 'General', 'smarty_po_general_section_cb', 'smarty_po_settings_page');
        add_settings_section('smarty_po_colors_section', 'Colors', 'smarty_po_colors_section_cb', 'smarty_po_settings_page');
        add_settings_section('smarty_po_font_sizes_section', 'Font Sizes', 'smarty_po_font_sizes_section_cb', 'smarty_po_settings_page');
        add_settings_section('smarty_po_text_section', 'Custom Text', 'smarty_po_text_section_cb', 'smarty_po_settings_page');

        // Add settings field for disable/enable labels
        add_settings_field('smarty_po_enable_labels', 'Disable/Enable', 'smarty_po_checkbox_field_cb', 'smarty_po_settings_page', 'smarty_po_general_section', ['id' => 'smarty_po_enable_labels']);

        // Add settings fields for colors
        add_settings_field('smarty_po_border_color', 'Border', 'smarty_po_color_field_cb', 'smarty_po_settings_page', 'smarty_po_colors_section', ['id' => 'smarty_po_border_color']);
        add_settings_field('smarty_po_bg_color', 'Background', 'smarty_po_color_field_cb', 'smarty_po_settings_page', 'smarty_po_colors_section', ['id' => 'smarty_po_bg_color']);
        add_settings_field('smarty_po_text_color', 'Text', 'smarty_po_color_field_cb', 'smarty_po_settings_page', 'smarty_po_colors_section', ['id' => 'smarty_po_text_color']);
        
        // Add settings fields for font sizes
        add_settings_field('smarty_po_d_font_size', 'Promo Text (Desktop)', 'smarty_po_d_font_size_field_cb', 'smarty_po_settings_page', 'smarty_po_font_sizes_section', ['id' => 'smarty_po_d_font_size']);
        add_settings_field('smarty_po_m_font_size', 'Promo Text (Mobile)', 'smarty_po_m_font_size_field_cb', 'smarty_po_settings_page', 'smarty_po_font_sizes_section', ['id' => 'smarty_po_m_font_size']);

        // Add settings field for promo options text and number
        add_settings_field('smarty_po_text', 'Promo Label', 'smarty_po_text_field_cb', 'smarty_po_settings_page', 'smarty_po_text_section');
        add_settings_field('smarty_po_number', 'Promo Percent', 'smarty_po_number_field_cb', 'smarty_po_settings_page', 'smarty_po_text_section');
    }
    add_action('admin_init', 'smarty_po_register_settings');
}

if (!function_exists('smarty_po_register_settings_page')) {
    /**
     * Registers the plugin settings page in the WooCommerce admin menu.
     *
     * Adds a submenu item under the "WooCommerce" menu titled "Promo Options Manager".
     * The submenu loads the settings page content using the callback function.
     *
     * @since 1.0.0
     * @return void
     */
    function smarty_po_register_settings_page() {
        add_submenu_page(
            'woocommerce',
            __('Promo Options Manager | Settings', 'smarty-promo-options-manager'),
            __('Promo Options Manager', 'smarty-promo-options-manager'),
            'manage_options',
            'smarty-po-settings',
            'smarty_po_settings_page_content',
        );
    }
    add_action('admin_menu', 'smarty_po_register_settings_page');
}

if (!function_exists('smarty_po_general_section_cb')) {
    /**
     * Outputs the description for the general settings section.
     *
     * Displays a short paragraph explaining the purpose of the section.
     *
     * @since 1.0.0
     * @return void
     */
    function smarty_po_general_section_cb() {
        echo '<p>Enable or disable promo labels for products.</p>';
    }
}

if (!function_exists('smarty_po_checkbox_field_cb')) {
    /**
     * Renders a custom checkbox toggle switch for a settings field.
     *
     * Uses a custom-styled toggle switch with the "smarty-toggle-switch" class.
     * Retrieves the current option value to determine whether the checkbox is checked.
     *
     * @since 1.0.0
     * @param array $args {
     *     Array of arguments passed to the callback.
     *
     *     @type string $id The ID of the settings field.
     * }
     * @return void
     */
    function smarty_po_checkbox_field_cb($args) {
        $option = get_option($args['id'], '0'); // Default is '0'
        $checked = checked('1', $option, false);
        echo "<label class='smarty-toggle-switch'>";
        echo "<input type='checkbox' id='{$args['id']}' name='{$args['id']}' value='1' {$checked} />";
        echo "<span class='smarty-slider round'></span>";
        echo "</label>";
    }
}

if (!function_exists('smarty_po_colors_section_cb')) {
     /**
     * Callback for the colors section description.
     *
     * Outputs an explanatory message for the color customization section
     * in the plugin settings.
     *
     * @since 1.0.0
     * @return void
     */
    function smarty_po_colors_section_cb() {
        echo '<p>Customize the colors for promo elements in your WooCommerce shop and single product pages.</p>';
    }
}

if (!function_exists('smarty_po_color_field_cb')) {
    /**
     * Callback for rendering a color input field.
     *
     * Outputs a WordPress color picker input tied to the given option.
     *
     * @since 1.0.0
     * @param array $args {
     *     Arguments for the color field.
     *
     *     @type string $id The ID (and option name) of the field.
     * }
     * @return void
     */
    function smarty_po_color_field_cb($args) {
        $option = get_option($args['id'], '');
        echo '<input type="text" name="' . $args['id'] . '" value="' . esc_attr($option) . '" class="smarty-po-color-field" data-default-color="' . esc_attr($option) . '" />';
    }
}

if (!function_exists('smarty_po_font_sizes_section_cb')) {
    /**
     * Callback for the font sizes section description.
     *
     * Outputs a short note describing the purpose of the font size controls.
     *
     * @since 1.0.0
     * @return void
     */
    function smarty_po_font_sizes_section_cb() {
        echo '<p>Customize the font sizes for promo elements in your WooCommerce shop and single product pages.</p>';
    }
}

if (!function_exists('smarty_po_d_font_size_field_cb')) {
    /**
     * Callback for rendering the desktop font size input slider.
     *
     * Outputs a range slider for selecting the font size (in pixels) for desktop views.
     *
     * @since 1.0.0
     * @param array $args {
     *     Arguments for the slider field.
     *
     *     @type string $id The ID (and option name) of the field.
     * }
     * @return void
     */
    function smarty_po_d_font_size_field_cb($args) {
        $option = get_option($args['id'], '14');
        echo '<input type="range" name="' . $args['id'] . '" min="10" max="30" value="' . esc_attr($option) . '" class="smarty-po-font-size-slider" />';
        echo '<span id="' . $args['id'] . '-value">' . esc_attr($option) . 'px</span>';
    }
}

if (!function_exists('smarty_po_m_font_size_field_cb')) {
    /**
     * Callback for rendering the mobile font size input slider.
     *
     * Outputs a range slider for selecting the font size (in pixels) for mobile views.
     *
     * @since 1.0.0
     * @param array $args {
     *     Arguments for the slider field.
     *
     *     @type string $id The ID (and option name) of the field.
     * }
     * @return void
     */
    function smarty_po_m_font_size_field_cb($args) {
        $option = get_option($args['id'], '14');
        echo '<input type="range" name="' . $args['id'] . '" min="10" max="30" value="' . esc_attr($option) . '" class="smarty-po-font-size-slider" />';
        echo '<span id="' . $args['id'] . '-value">' . esc_attr($option) . 'px</span>';
    }
}

if (!function_exists('smarty_po_text_section_cb')) {
    /**
     * Callback for the text section description.
     *
     * Provides instructions or context about setting custom promo text.
     *
     * @since 1.0.0
     * @return void
     */
    function smarty_po_text_section_cb() {
        echo '<p>Use custom text for promo label.</p>';
    }
}

if (!function_exists('smarty_po_text_field_cb')) {
    /**
     * Callback for rendering the promo label text input.
     *
     * Renders a standard input field for users to define promo label text.
     *
     * @since 1.0.0
     * @param array $args Optional. Arguments for the field. Not currently used.
     * @return void
     */
    function smarty_po_text_field_cb($args) {
        $option = get_option('smarty_po_text', 'Example text'); // Default is empty
        echo '<input type="text" name="smarty_po_text" value="' . esc_attr($option) . '" />';
        echo '<p class="description">Set the text for promo label.</p>';
    }
}

if (!function_exists('smarty_po_number_field_cb')) {
    /**
     * Callback for rendering the numeric input for discount threshold.
     *
     * Outputs a number input field used to determine the minimum discount value for the promo label.
     *
     * @since 1.0.0
     * @return void
     */
    function smarty_po_number_field_cb() {
        $option = get_option('smarty_po_number');
        echo '<input type="number" step="0.01" name="smarty_po_number" value="' . esc_attr($option) . '" />';
        echo '<p class="description">Set the amount required for promo label percent.</p>';
    }
}

if (!function_exists('smarty_po_settings_page_content')) {
    /**
     * Renders the plugin settings page content in the WordPress admin.
     *
     * Outputs the full HTML and JavaScript required to:
     * - Display plugin settings fields and sections
     * - Provide a tabbed UI for "Documentation" and "Changelog"
     * - Initialize the WordPress color picker for color fields
     * - Dynamically update displayed values for sliders (e.g., font size)
     *
     * This page is registered via `add_submenu_page()` under the WooCommerce admin menu.
     *
     * @since 1.0.0
     * @return void
     */
    function smarty_po_settings_page_content() {
        ?>
       <div class="wrap">
            <h1><?php _e('Promo Options Manager | Settings', 'smarty-promo-options-manager'); ?></h1>
            <div id="smarty-po-settings-container">
                <div>
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('smarty_po_settings_group');
                        do_settings_sections('smarty_po_settings_page');
                        ?>
                        <?php submit_button(); ?>
                    </form>
                </div>
                <div id="smarty-po-tabs-container">
                    <div>
                        <h2 class="smarty-po-nav-tab-wrapper">
                            <a href="#smarty-po-documentation" class="smarty-po-nav-tab smarty-po-nav-tab-active"><?php esc_html_e('Documentation', 'smarty-promo-options-manager'); ?></a>
                            <a href="#smarty-po-changelog" class="smarty-po-nav-tab"><?php esc_html_e('Changelog', 'smarty-promo-options-manager'); ?></a>
                        </h2>
                        <div id="smarty-po-documentation" class="smarty-po-tab-content active">
                            <div class="smarty-po-view-more-container">
                                <p><?php esc_html_e('Click "View More" to load the plugin documentation.', 'smarty-promo-options-manager'); ?></p>
                                <button id="smarty-po-load-readme-btn" class="button button-primary">
                                    <?php esc_html_e('View More', 'smarty-promo-options-manager'); ?>
                                </button>
                            </div>
                            <div id="smarty-po-readme-content" style="margin-top: 20px;"></div>
                        </div>
                        <div id="smarty-po-changelog" class="smarty-po-tab-content">
                            <div class="smarty-po-view-more-container">
                                <p><?php esc_html_e('Click "View More" to load the plugin changelog.', 'smarty-promo-options-manager'); ?></p>
                                <button id="smarty-po-load-changelog-btn" class="button button-primary">
                                    <?php esc_html_e('View More', 'smarty-promo-options-manager'); ?>
                                </button>
                            </div>
                            <div id="smarty-po-changelog-content" style="margin-top: 20px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <style>
            .wp-color-result { vertical-align: middle; }
        </style>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('.smarty-po-color-field').wpColorPicker();

                // Update the font size and image width value display
                $('.smarty-po-font-size-slider').on('input', function() {
                    var sliderId = $(this).attr('name');
                    var unit;

                    // Determine the unit based on the class
                    if ($(this).hasClass('smarty-po-font-size-slider')) {
                        unit = 'px';
                    } else {
                        unit = ''; // Default to no unit if not identified
                    }

                    // Update the value display with the correct unit
                    $('#' + sliderId + '-value').text($(this).val() + unit);
                });
            });
        </script>
        <?php
    }
}

if (!function_exists('smarty_po_load_readme')) {
    /**
     * AJAX handler to load and parse the README.md content.
     * 
     * @since 1.0.0
     */
    function smarty_po_load_readme() {
        check_ajax_referer('smarty_promo_options_nonce', 'nonce');
    
        if (!current_user_can('manage_options')) {
            wp_send_json_error('You do not have sufficient permissions.');
        }
    
        $readme_path = plugin_dir_path(__FILE__) . 'README.md';
        if (file_exists($readme_path)) {
            // Include Parsedown library
            if (!class_exists('Parsedown')) {
                require_once plugin_dir_path(__FILE__) . 'libs/Parsedown.php';
            }
    
            $parsedown = new Parsedown();
            $markdown_content = file_get_contents($readme_path);
            $html_content = $parsedown->text($markdown_content);
    
            // Remove <img> tags from the content
            $html_content = preg_replace('/<img[^>]*>/', '', $html_content);
    
            wp_send_json_success($html_content);
        } else {
            wp_send_json_error('README.md file not found.');
        }
    }    
    add_action('wp_ajax_smarty_po_load_readme', 'smarty_po_load_readme');
}

if (!function_exists('smarty_po_load_changelog')) {
    /**
     * AJAX handler to load and parse the CHANGELOG.md content.
     * 
     * @since 1.0.0
     */
    function smarty_po_load_changelog() {
        check_ajax_referer('smarty_promo_options_nonce', 'nonce');
    
        if (!current_user_can('manage_options')) {
            wp_send_json_error('You do not have sufficient permissions.');
        }
    
        $changelog_path = plugin_dir_path(__FILE__) . 'CHANGELOG.md';
        if (file_exists($changelog_path)) {
            if (!class_exists('Parsedown')) {
                require_once plugin_dir_path(__FILE__) . 'libs/Parsedown.php';
            }
    
            $parsedown = new Parsedown();
            $markdown_content = file_get_contents($changelog_path);
            $html_content = $parsedown->text($markdown_content);
    
            wp_send_json_success($html_content);
        } else {
            wp_send_json_error('CHANGELOG.md file not found.');
        }
    }
    add_action('wp_ajax_smarty_po_load_changelog', 'smarty_po_load_changelog');
}

if (!function_exists('smarty_po_label_shortcode')) {
    /**
     * Shortcode to display a promotional label for WooCommerce products.
     *
     * @since 1.0.0
     * @return string The generated HTML for the promotional label or an empty string if not on a product page.
     */
    function smarty_po_label_shortcode() {
        global $product;

        if (!$product instanceof WC_Product) {
            return ''; // Ensure it's only used in a product loop
        }

        // Generate a unique cache key
        $product_id = $product->get_id();
        $cache_key  = 'smarty_po_label_' . $product_id;
        $cached     = wp_cache_get($cache_key, 'smarty_po');

        if ($cached !== false) {
            return $cached;
        }

        // Get plugin settings
        $po_border_color = get_option('smarty_po_border_color', '#222222');
        $po_bg_color = get_option('smarty_po_bg_color', '#222222');
        $po_text_color = get_option('smarty_po_text_color', '#ffffff');
        $po_text = get_option('smarty_po_text', 'Use promo code TEST123');
        $po_number = (int)get_option('smarty_po_number', 15);

        // Initialize prices
        $regular_price = 0;
        $sale_price = 0;

        // Check if it's a variable product
        if ($product->is_type('variable')) {
            $variations = $product->get_available_variations();

            if (!empty($variations)) {
                // Get the first variation
                $first_variation = reset($variations);
                $regular_price = (float)$first_variation['display_regular_price'];
                $sale_price = (float)$first_variation['display_price'];
            }
        } else {
            // For simple products, use regular and sale price directly
            $regular_price = (float)$product->get_regular_price();
            $sale_price = (float)$product->get_sale_price();
        }

        // Calculate the discount percentage
        $discount_percentage = 0;
        if ($regular_price > 0 && $sale_price > 0 && $sale_price < $regular_price) {
            $discount_percentage = round((($regular_price - $sale_price) / $regular_price) * 100);
        }

        // If the first variation has no discount, use only the additional discount percentage
        $label_discount = $discount_percentage > 0 ? $discount_percentage + $po_number : $po_number;

        // Generate the label content
        $label_text = "<span class='number' style='background-color:{$po_text_color};color:{$po_bg_color};'>-{$label_discount}%</span>";
        $label_text .= " <span class='text' style='background-color:{$po_bg_color};color:{$po_text_color};'>{$po_text}</span>";

        // Cache the output
        wp_cache_set($cache_key, $output, 'smarty_po', HOUR_IN_SECONDS);

        // Return the label HTML
        return '<div class="po-text">' . $label_text . '</div>';
    }
    add_shortcode('smarty_po_label', 'smarty_po_label_shortcode');
}

if (!function_exists('smarty_po_get_variation_label')) {
    /**
     * Generates a promotional label for a specific WooCommerce product variation.
     *
     * This function calculates the total discount percentage for a variation, including 
     * an additional discount, and returns the styled promotional label HTML.
     *
     * @since 1.0.0
     * 
     * @param int $variation_id The ID of the WooCommerce product variation.
     * @param int $additional_discount The additional discount percentage to apply (default: 15).
     *
     * @return string The generated promotional label HTML, or an empty string if the variation does not exist.
     *
     * Example Output:
     * <div class="po-text">
     *     <span class="number" style="background-color:#ffffff;color:#222222;">-30%</span>
     *     <span class="text" style="background-color:#222222;color:#ffffff;">Use promo code BLACK15</span>
     * </div>
     */
    function smarty_po_get_variation_label($variation_id) {
        // Fetch variation product object
        $single_variation = new WC_Product_Variation($variation_id);

        // Check if variation exists
        if (!$single_variation->exists()) {
            return '';
        }

        // Get prices
        $regular_price = (float) $single_variation->get_regular_price();
        $sale_price = (float) $single_variation->get_price();

        // Get plugin settings
        $po_border_color = get_option('smarty_po_border_color', '#222222');
        $po_bg_color = get_option('smarty_po_bg_color', '#222222');
        $po_text_color = get_option('smarty_po_text_color', '#ffffff');
        $po_text = get_option('smarty_po_text', 'Use promo code BLACK15');
		$po_number = (int) get_option('smarty_po_number', 10);

        // Calculate base discount percentage
        $discount_percentage = 0;
        if ($regular_price > 0 && $sale_price < $regular_price) {
            $discount_percentage = round((($regular_price - $sale_price) / $regular_price) * 100);
        }

        // Add additional discount
        $total_discount = $discount_percentage > 0 ? $discount_percentage + $po_number : $po_number;

        // Generate label content
        $label_text = "<span class='number' style='background-color:{$po_text_color};color:{$po_bg_color};'>-{$total_discount}%</span>";
        $label_text .= " <span class='text' style='background-color:{$po_bg_color};color:{$po_text_color};'>{$po_text}</span>";

        // Return the label HTML
        return '<div class="po-text">' . $label_text . '</div>';
    }
}

if (!function_exists('smarty_po_public_css')) {
    /**
     * Outputs public CSS styles for the promo label on WooCommerce pages.
     * 
     * @since 1.0.0
     */
    function smarty_po_public_css() {
        // Check if the current page is the WooCommerce shop page
        if (is_shop() || is_product()) {
            // Retrieve custom options for the promo styles
            $po_border_color = get_option('smarty_po_border_color', '#222222');
            $po_bg_color = get_option('smarty_po_bg_color', '#222222');
            $po_text_color = get_option('smarty_po_text_color', '#ffffff');
            $po_d_font_size = get_option('smarty_po_d_font_size', '16');
            $po_m_font_size = get_option('smarty_po_m_font_size', '14'); ?>

            <style>
                .po-text {
                    position: relative;
                    top: 5px;
                    font-weight: bold;
                    display: flex;
                    z-index: 10;
                    border-radius: 10px;
                }

                .po-text .number {
                    background-color: <?php echo esc_attr($po_bg_color); ?>;
                    color: <?php echo esc_attr($po_text_color); ?>;
                    font-size: <?php echo esc_attr($po_d_font_size); ?>px;
                    align-content: center;
                    padding: 0 20px 0 10px;
                    margin-right: 5px;
                    border: 2px solid <?php echo esc_attr($po_border_color); ?>;
                }

                .po-text .text {
                    background-color: <?php echo esc_attr($po_bg_color); ?>;
                    color: <?php echo esc_attr($po_text_color); ?>;
                    margin: 0 0 0 -20px;
                    padding: 3px 10px;
                    width: 100%;
                    border-top-left-radius: 0;
                    border-top-right-radius: 25px;
                    border-bottom-left-radius: 20px;
                    border-bottom-right-radius: 0;
                }

                @media only screen and (max-width: 600px) {
                    .po-text .number,
                    .po-text .text {
                        font-size: <?php echo esc_attr($po_m_font_size); ?>px;
                    }
                }
            </style>
        <?php } ?>
		
		<?php if (is_shop()) { ?>
			<style>
				@media only screen and (max-width: 600px) {
					.po-text .text {
						line-height: 1;
					}
				}
            </style>
		<?php } ?>

        <?php if (is_product()) { ?>
            <style>
                .po-text {
                    top: 12px;
                    padding: 10px 0;
                    width: fit-content;
                }
            </style>
        <?php } 
    }
    add_action('wp_head', 'smarty_po_public_css');
}

if (!function_exists('smarty_po')) {
    /**
     * Checks if the Promo Options Manager plugin is active and promo labels are enabled.
     *
     * @since 1.0.0
     * @return bool True if the plugin is active and promo labels are enabled, false otherwise.
     */
    function smarty_po() {
        return function_exists('is_plugin_active') && 
               is_plugin_active('smarty-promo-options-manager/smarty-promo-options-manager.php') && 
               get_option('smarty_po_enable_labels', '1') === '1';
    }
}

// Add a links on the Plugins page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $links[] = '<a href="' . admin_url('admin.php?page=smarty-po-settings') . '">' . __('Settings', 'smarty-promo-options-manager') . '</a>';
    $links[] = '<a href="https://github.com/mnestorov/smarty-promo-options-manager" target="_blank">' . __('GitHub', 'smarty-promo-options-manager') . '</a>';
    return $links;
});