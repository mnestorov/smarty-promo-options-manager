<?php
/**
 * Plugin Name:             SM - Promo Options Manager for WooCommerce
 * Plugin URI:              https://github.com/mnestorov/smarty-promo-options-manager
 * Description:             Manage promotional options for WooCommerce products with customizable labels and styles.
 * Version:                 1.0.0
 * Author:                  Smarty Studio | Martin Nestorov
 * Author URI:              https://github.com/mnestorov
 * Text Domain:             smarty-promo-options-manager
 * Domain Path:             /languages/
 * WC requires at least:    3.5.0
 * WC tested up to:         9.4.1
 * Requires Plugins:        woocommerce
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

if (!function_exists('smarty_po_enqueue_scripts')) {
    function smarty_po_enqueue_scripts($hook_suffix) {
        // Only add to the admin page of the plugin
        if ('woocommerce_page_smarty-po-settings' !== $hook_suffix) {
            return;
        }

        wp_enqueue_style('select2-css', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css');
        wp_enqueue_script('select2-js', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'), '4.0.13', true);

        // Enqueue style and script for using the WordPress color picker.
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
    }
    add_action('admin_enqueue_scripts', 'smarty_po_enqueue_scripts');
}

if (!function_exists('smarty_po_register_settings')) {
    function smarty_po_register_settings() {
        // Register settings
        register_setting('smarty_po_settings_group', 'smarty_po_active_border_color');
        register_setting('smarty_po_settings_group', 'smarty_po_bg_color');
        register_setting('smarty_po_settings_group', 'smarty_po_text_color');
        register_setting('smarty_po_settings_group', 'smarty_po_text');
        register_setting('smarty_po_settings_group', 'smarty_po_number');
        register_setting('smarty_po_settings_group', 'smarty_po_font_size');
        
        // Add settings sections
        add_settings_section('smarty_po_colors_section', 'Colors', 'smarty_po_colors_section_cb', 'smarty_po_settings_page');
        add_settings_section('smarty_po_font_sizes_section', 'Font Sizes', 'smarty_po_font_sizes_section_cb', 'smarty_po_settings_page');
        add_settings_section('smarty_po_text_section', 'Promo Text', 'smarty_po_text_section_cb', 'smarty_po_settings_page');

        // Add settings fields for colors
        add_settings_field('smarty_po_active_border_color', 'Border', 'smarty_po_color_field_cb', 'smarty_po_settings_page', 'smarty_po_colors_section', ['id' => 'smarty_po_active_border_color']);
        add_settings_field('smarty_po_bg_color', 'Background', 'smarty_po_color_field_cb', 'smarty_po_settings_page', 'smarty_po_colors_section', ['id' => 'smarty_po_bg_color']);
        add_settings_field('smarty_po_text_color', 'Text', 'smarty_po_color_field_cb', 'smarty_po_settings_page', 'smarty_po_colors_section', ['id' => 'smarty_po__text_color']);
        
        // Add settings fields for font sizes
        add_settings_field('smarty_po_font_size', 'Additional Label', 'smarty_font_size_field_cb', 'smarty_po_settings_page', 'smarty_font_sizes_section', ['id' => 'smarty_po_font_size']);
        
        // Add settings field for promo options text and number
        add_settings_field('smarty_po_text', 'Text', 'smarty_po_text_field_cb', 'smarty_po_settings_page', 'smarty_po_text_section');
        add_settings_field('smarty_po_number', 'Number', 'smarty_po_number_field_cb', 'smarty_po_settings_page', 'smarty_po_text_section');
    }
    add_action('admin_init', 'smarty_po_register_settings');
}

if (!function_exists('smarty_po_register_settings_page')) {
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

if (!function_exists('smarty_po_colors_section_cb')) {
    function smarty_po_colors_section_cb() {
        echo '<p>Customize the colors for promo elements in your WooCommerce shop and single product pages.</p>';
    }
}

if (!function_exists('smarty_po_color_field_cb')) {
    function smarty_po_color_field_cb($args) {
        $option = get_option($args['id'], '');
        echo '<input type="text" name="' . $args['id'] . '" value="' . esc_attr($option) . '" class="smarty-po-color-field" data-default-color="' . esc_attr($option) . '" />';
    }
}

if (!function_exists('smarty_po_font_sizes_section_cb')) {
    function smarty_po_font_sizes_section_cb() {
        echo '<p>Customize the font sizes for promo elements in your WooCommerce shop and single product pages.</p>';
    }
}

if (!function_exists('smarty_po_font_size_field_cb')) {
    function smarty_po_font_size_field_cb($args) {
        $option = get_option($args['id'], '14');
        echo '<input type="range" name="' . $args['id'] . '" min="10" max="30" value="' . esc_attr($option) . '" class="smarty-po-font-size-slider" />';
        echo '<span id="' . $args['id'] . '-value">' . esc_attr($option) . 'px</span>';
    }
}

if (!function_exists('smarty_po_text_field_cb')) {
    function smarty_po_text_field_cb($args) {
        $option = get_option($args['id'], ''); // Default is empty
        echo '<input type="text" name="' . $args['id'] . '" value="' . esc_attr($option) . '" />';
        echo '<p class="description">Set the text for promo label.</p>';
    }
}

if (!function_exists('smarty_po_number_field_cb')) {
    function smarty_po_number_field_cb() {
        $option = get_option('smarty_po_number');
        echo '<input type="number" step="0.01" name="smarty_po_number" value="' . esc_attr($option) . '" />';
        echo '<p class="description">Set the amount required for label promo percent.</p>';
    }
}

if (!function_exists('smarty_po_settings_page_content')) {
    function smarty_po_settings_page_content() {
        ?>
       <div class="wrap">
            <h1><?php _e('Promo Options Manager | Settings', 'smarty-promo-options-manager'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('smarty_po_settings_group');
                do_settings_sections('smarty_po_settings_page');
                ?>
                <?php submit_button(); ?>
            </form>
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

if (!function_exists('smarty_po_label_shortcode')) {
    function smarty_po_label_shortcode() {
        global $product;

        if (!$product instanceof WC_Product) {
            return ''; // Ensure it's only used in a product loop
        }

        // Get plugin settings
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

        // Return the label HTML
        return '<div class="po-text">' . $label_text . '</div>';
    }
    add_shortcode('smarty_po_label', 'smarty_po_label_shortcode');
}

function smarty_po_public_css() {
    // Check if the current page is the WooCommerce shop page
    if (is_shop() || is_product()) {
        // Retrieve custom options for the promo styles
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
                border-top-left-radius: 5px;
                border-top-right-radius: 15px;
                border-bottom-left-radius: 15px;
                border-bottom-right-radius: 5px;
            }

            .po-text .text {
                background-color: <?php echo esc_attr($po_bg_color); ?>;
                color: <?php echo esc_attr($po_text_color); ?>;
                margin: 0 0 0 -20px;
                padding: 3px 10px;
                width: 100%;
                border-top-left-radius: 0;
                border-top-right-radius: 15px;
                border-bottom-left-radius: 15px;
                border-bottom-right-radius: 5px;
            }

            @media only screen and (max-width: 600px) {
                .po-text .number,
                .po-text .text {
                    font-size: <?php echo esc_attr($po_m_font_size); ?>px;
                }
            }
        </style>
    <?php } ?>

    <?php if (is_product()) { ?>
        <style>
            .po-text {
                padding: 10px 0;
            }
        </style>
    <?php } 
}
add_action('wp_head', 'smarty_po_public_css');
