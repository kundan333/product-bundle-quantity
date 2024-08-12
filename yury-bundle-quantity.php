<?php
/*
 * Plugin Name: WooCommerce Bundle Quantity Plugin
 * Description: Adds bundle quantity options with discounts to WooCommerce products.
 * Version: 1.0
 * Author: Kundan Bora
 * Plugin URI: kundankb.com
 * Author URI: kundankb.com
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class Yury_Bundle_Quantity {
    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }

    public function init() {
        if (!class_exists('WooCommerce')) return;

        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // Enqueue frontend scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));

        // Add product data tab
        add_filter('woocommerce_product_data_tabs', array($this, 'add_product_data_tab'));
        add_action('woocommerce_product_data_panels', array($this, 'add_product_data_fields'));

        // Save custom fields
        add_action('woocommerce_process_product_meta', array($this, 'save_bundle_quantity_fields'));

        // Display bundle options on product page
        add_action('woocommerce_before_add_to_cart_button', array($this, 'display_bundle_options'));

        // Adjust cart item price
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_cart_item_data'), 10, 3);
        add_filter('woocommerce_get_cart_item_from_session', array($this, 'get_cart_item_from_session'), 10, 2);
        add_filter('woocommerce_cart_item_price', array($this, 'update_cart_item_price'), 10, 3);


        add_action('wp_ajax_add_bundle_to_cart', array($this, 'add_bundle_to_cart'));
        add_action('wp_ajax_nopriv_add_bundle_to_cart', array($this, 'add_bundle_to_cart'));

        add_action('admin_footer', array($this, 'add_bundle_option_template'));

    }

    public function enqueue_admin_assets($hook) {
        if ('post.php' != $hook || get_post_type() != 'product') return;
        wp_enqueue_style('yury-bundle-quantity-admin', plugins_url('assets/js/admin.css', __FILE__));
        wp_enqueue_script('yury-bundle-quantity-admin', plugins_url('assets/js/admin.js', __FILE__), array('jquery'), '1.0', true);
    }

    public function enqueue_frontend_assets() {
        if (!is_product()) return;
        wp_enqueue_style('yury-bundle-quantity-frontend', plugins_url('assets/css/frontend.css', __FILE__));
        wp_enqueue_script('yury-bundle-quantity-frontend', plugins_url('assets/js/frontend.js', __FILE__), array('jquery'), '1.0', true);
    }

    public function add_product_data_tab($tabs) {
        $tabs['bundle_quantity'] = array(
            'label' => __('Bundle Quantities', 'yury-bundle-quantity'),
            'target' => 'bundle_quantity_options',
            'class' => array('show_if_simple', 'show_if_variable'),
        );
        return $tabs;
    }



    public function add_product_data_fields() {
        global $post;
        ?>
        <div id='bundle_quantity_options' class='panel woocommerce_options_panel'>
            <div class='options_group'>
                <?php
                woocommerce_wp_checkbox(array(
                    'id' => '_enable_bundle_quantity',
                    'label' => __('Enable Bundle Quantity', 'yury-bundle-quantity'),
                    'description' => __('Enable bundle quantity options for this product', 'yury-bundle-quantity')
                ));
                ?>
                <div id="bundle_quantity_options_container">
                    <?php
                    $bundle_options = get_post_meta($post->ID, '_bundle_options', true);
                    if ($bundle_options) {
                        foreach ($bundle_options as $index => $option) {
                            ?>
                            <div class="bundle-option">
                                <p class="form-field">
                                    <label><?php _e('Quantity', 'yury-bundle-quantity'); ?></label>
                                    <input type="number" name="bundle_options[<?php echo $index; ?>][quantity]" value="<?php echo esc_attr($option['quantity']); ?>" min="1" step="1" />
                                </p>
                                <p class="form-field">
                                    <label><?php _e('Discount (%)', 'yury-bundle-quantity'); ?></label>
                                    <input type="number" name="bundle_options[<?php echo $index; ?>][discount]" value="<?php echo esc_attr($option['discount']); ?>" min="0" max="100" step="0.01" />
                                </p>
                                <button type="button" class="button remove-bundle-option"><?php _e('Remove', 'yury-bundle-quantity'); ?></button>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </div>
                <button type="button" id="add_bundle_option" class="button"><?php _e('Add Bundle Option', 'yury-bundle-quantity'); ?></button>
            </div>
        </div>
        <?php
    }

    public function save_bundle_quantity_fields($post_id) {
        $enable_bundle_quantity = isset($_POST['_enable_bundle_quantity']) ? 'yes' : 'no';
        update_post_meta($post_id, '_enable_bundle_quantity', $enable_bundle_quantity);

        // Save bundle options
        $bundle_options = isset($_POST['bundle_options']) ? $_POST['bundle_options'] : array();
        update_post_meta($post_id, '_bundle_options', $bundle_options);
    }

    public function display_bundle_options() {
        global $product;
        $enable_bundle_quantity = get_post_meta($product->get_id(), '_enable_bundle_quantity', true);
        if ($enable_bundle_quantity !== 'yes') return;

        $bundle_options = get_post_meta($product->get_id(), '_bundle_options', true);
        if (!$bundle_options) return;

        // Remove default add to cart button and quantity input
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);

        // Display bundle options
        ?>
        <div class="choose-your-package">
            <h2><?php _e('Choose Your Package', 'yury-bundle-quantity'); ?></h2>
            <div class="high-demand">
                <span class="flame-icon">🔥</span>
                <?php _e('High Demand: 47 people are currently looking at this offer!', 'yury-bundle-quantity'); ?>
            </div>
            <div class="bundle-options">
                <?php foreach ($bundle_options as $index => $option) :
                    $discounted_price = $product->get_price() * (1 - $option['discount'] / 100);
                    ?>
                    <div class="bundle-option" data-quantity="<?php echo esc_attr($option['quantity']); ?>" data-discount="<?php echo esc_attr($option['discount']); ?>">
                        <span class="quantity"><?php echo esc_html($option['quantity']); ?></span>
                        <span class="price"><?php echo wc_price($discounted_price); ?></span>
                        <span class="per-unit"><?php _e('Per Unit', 'yury-bundle-quantity'); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="total-price">
                <span class="original-price"><?php echo wc_price($product->get_price() * $bundle_options[0]['quantity']); ?></span>
                <span class="discounted-price"><?php echo wc_price($product->get_price() * $bundle_options[0]['quantity'] * (1 - $bundle_options[0]['discount'] / 100)); ?></span>
                <span class="saving">Saving <?php echo esc_html($bundle_options[0]['discount']); ?>%</span>
            </div>
            <div class="additional-info">
                <span class="warranty">1-Year Extended Warranty</span>
                <span class="free-shipping">Free Shipping Over $100</span>
            </div>
            <button class="add-to-cart-bundle"><?php _e('ADD TO CART', 'yury-bundle-quantity'); ?></button>
            <div class="stock-info">
                <?php if ($product->is_in_stock()) : ?>
                    <span class="in-stock">✓ In Stock: Ships by Aug 10, 2024</span>
                <?php endif; ?>
            </div>
            <div class="payment-methods">
                <img src="<?php echo plugins_url('images/payment-methods.png', __FILE__); ?>" alt="Payment Methods">
            </div>
            <div class="secure-transaction">
                <span>🔒 All transactions secured and encrypted</span>
            </div>
        </div>
        <?php
    }

    public function add_cart_item_data($cart_item_data, $product_id, $variation_id) {
        if (isset($_POST['bundle_option'])) {
            $bundle_options = get_post_meta($product_id, '_bundle_options', true);
            $selected_option = $bundle_options[$_POST['bundle_option']];
            $cart_item_data['bundle_option'] = array(
                'quantity' => $selected_option['quantity'],
                'discount' => $selected_option['discount']
            );
        }
        return $cart_item_data;
    }

    public function get_cart_item_from_session($cart_item, $values) {
        if (isset($values['bundle_option'])) {
            $cart_item['bundle_option'] = $values['bundle_option'];
        }
        return $cart_item;
    }

    public function update_cart_item_price($price, $cart_item, $cart_item_key) {
        if (isset($cart_item['bundle_option'])) {
            $product = $cart_item['data'];
            $base_price = $product->get_price();
            $quantity = $cart_item['bundle_option']['quantity'];
            $discount = $cart_item['bundle_option']['discount'];
            $discounted_price = $base_price * $quantity * (1 - $discount / 100);
            return wc_price($discounted_price);
        }
        return $price;
    }


    public function add_bundle_option_template() {
        ?>
        <script type="text/template" id="tmpl-bundle-option-template">
            <div class="bundle-option">
                <p class="form-field">
                    <label><?php _e('Quantity', 'yury-bundle-quantity'); ?></label>
                    <input type="number" name="bundle_options[{{data.index}}][quantity]" value="" min="1" step="1" />
                </p>
                <p class="form-field">
                    <label><?php _e('Discount (%)', 'yury-bundle-quantity'); ?></label>
                    <input type="number" name="bundle_options[{{data.index}}][discount]" value="" min="0" max="100" step="0.01" />
                </p>
                <button type="button" class="button remove-bundle-option"><?php _e('Remove', 'yury-bundle-quantity'); ?></button>
            </div>
        </script>
        <?php
    }

    public function add_bundle_to_cart() {
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
        $discount = isset($_POST['discount']) ? floatval($_POST['discount']) : 0;

        $cart_item_data = array(
            'bundle_option' => array(
                'quantity' => $quantity,
                'discount' => $discount
            )
        );

        $added = WC()->cart->add_to_cart($product_id, 1, 0, array(), $cart_item_data);

        if ($added) {
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }

        wp_die();
    }


}

new Yury_Bundle_Quantity();