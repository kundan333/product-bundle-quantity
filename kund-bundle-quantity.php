<?php
/*
 * Plugin Name: WooCommerce Bundle Quantity Plugin
 * Description: Adds bundle quantity options with discounts to WooCommerce products.
 * Version: 2.2
 * Author: Kundan Bora
 * Plugin URI: kundankb.com
 * Author URI: kundankb.com
*/

const CSS_VER = '2.0.2';
const JS_VER = '2.0.2';


if (!defined('ABSPATH')) exit; // Exit if accessed directly

class Kund_Bundle_Quantity {
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

        add_action('woocommerce_before_calculate_totals', array($this, 'calculate_cart_item_price'), 10, 1);

        add_filter('woocommerce_cart_item_price', array($this, 'update_cart_item_price'), 10, 3);
        add_filter('woocommerce_cart_item_quantity', array($this, 'update_cart_item_quantity'), 10, 3);

        add_action('template_redirect', array($this, 'remove_default_woo_actions'),10);

        add_shortcode('kund_bundle_options', array($this, 'bundle_options_shortcode'));

    }


    public function bundle_options_shortcode() {
        ob_start();
        $this->display_bundle_options();
        return ob_get_clean();
    }

    public function enqueue_admin_assets($hook) {
        if ('post.php' != $hook || get_post_type() != 'product') return;
        wp_enqueue_style('kund-bundle-quantity-admin', plugins_url('assets/js/admin.css', __FILE__),false,CSS_VER);
        wp_enqueue_script('kund-bundle-quantity-admin', plugins_url('assets/js/admin.js', __FILE__), array('jquery'), JS_VER, true);

    }

    public function enqueue_frontend_assets() {
        if (!is_product()) return;
        wp_enqueue_style('kund-bundle-quantity-frontend', plugins_url('assets/css/frontend.css', __FILE__),false,CSS_VER);
        wp_enqueue_script('kund-bundle-quantity-frontend', plugins_url('assets/js/frontend.js', __FILE__), array('jquery'), JS_VER, true);

        wp_localize_script('kund-bundle-quantity-frontend',
            'wc_add_to_cart_params',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'cart_url' => wc_get_cart_url()
            )
        );

    }

    public function add_product_data_tab($tabs) {
        $tabs['bundle_quantity'] = array(
            'label' => __('Bundle Quantities', 'kund-bundle-quantity'),
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
                'label' => __('Enable Bundle Quantity', 'kund-bundle-quantity'),
                'description' => __('Enable bundle quantity options for this product', 'kund-bundle-quantity')
            ));
            ?>
            <div id="bundle_quantity_options_container">
                <?php
                $bundle_options = get_post_meta($post->ID, '_bundle_options', true);
                if ($bundle_options) {
                    foreach ($bundle_options as $index => $option) {
                        ?>
                        <div class="bundle-option" data-index="<?php echo $index; ?>">
                            <p class="form-field">
                                <label><?php _e('Quantity', 'kund-bundle-quantity'); ?></label>
                                <input type="number" name="bundle_options[<?php echo $index; ?>][quantity]" value="<?php echo esc_attr($option['quantity']); ?>" min="1" step="1" />
                            </p>
                            <p class="form-field">
                                <label><?php _e('Discount (%)', 'kund-bundle-quantity'); ?></label>
                                <input type="number" name="bundle_options[<?php echo $index; ?>][discount]" value="<?php echo esc_attr($option['discount']); ?>" min="0" max="100" step="0.01" />
                            </p>
                            <p class="form-field">
                                <label><?php _e('Image', 'kund-bundle-quantity'); ?></label>
                                <input type="hidden" class="bundle_image_id" name="bundle_options[<?php echo $index; ?>][image]" value="<?php echo esc_attr($option['image']); ?>" />
                                <img src="<?php echo wp_get_attachment_url($option['image']); ?>" alt="" class="bundle-image-preview" style="max-width: 100px; max-height: 100px; <?php echo $option['image'] ? '' : 'display: none;'; ?>" />
                                <button type="button" class="button upload_image_button" data-index="<?php echo $index; ?>"><?php _e('Upload/Add Image', 'kund-bundle-quantity'); ?></button>
                                <button type="button" class="button remove_image_button" data-index="<?php echo $index; ?>" <?php echo $option['image'] ? '' : 'style="display:none;"'; ?>><?php _e('Remove Image', 'kund-bundle-quantity'); ?></button>
                            </p>
                            <button type="button" class="button remove-bundle-option"><?php _e('Remove', 'kund-bundle-quantity'); ?></button>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
            <button type="button" id="add_bundle_option" class="button"><?php _e('Add Bundle Option', 'kund-bundle-quantity'); ?></button>
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




    public function remove_default_woo_actions() {
        if (!is_product()) {
            return; // Only run on single product pages
        }

        $product = wc_get_product();

        if (!$product) {
            return; // Exit if no product is found
        }

        $enable_bundle_quantity = get_post_meta($product->get_id(), '_enable_bundle_quantity', true);
        if ($enable_bundle_quantity !== 'yes') return;

        // Remove the default Add to Cart button and quantity input
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
        remove_action('woocommerce_simple_add_to_cart', 'woocommerce_simple_add_to_cart', 30);
        remove_action('woocommerce_grouped_add_to_cart', 'woocommerce_grouped_add_to_cart', 30);
        remove_action('woocommerce_variable_add_to_cart', 'woocommerce_variable_add_to_cart', 30);
        remove_action('woocommerce_external_add_to_cart', 'woocommerce_external_add_to_cart', 30);

        add_action('woocommerce_single_product_summary', array($this, 'display_bundle_options'), 35);
    }



    public function display_bundle_options() {
        global $product;
        $enable_bundle_quantity = get_post_meta($product->get_id(), '_enable_bundle_quantity', true);
        if ($enable_bundle_quantity !== 'yes') return;

        $bundle_options = get_post_meta($product->get_id(), '_bundle_options', true);
        if (!$bundle_options) return;



        ?>
        <div class="choose-your-package">
            <h2><?php _e('Choose Your Package', 'kund-bundle-quantity'); ?></h2>
            <div class="bundle-options">
                <?php foreach ($bundle_options as $index => $option) : ?>
                    <div class="bundle-option" data-image="<?php echo $option['image']?wp_get_attachment_url($option['image']):''; ?>" data-index="<?php echo $index; ?>" data-price="<?php echo $product->get_price(); ?>" data-quantity="<?php echo esc_attr($option['quantity']); ?>" data-discount="<?php echo esc_attr($option['discount']); ?>">
<!--                       make these 3 under transparent box-->

                        <div class="bundle-image" style="width: 50%;">
                            <img src="<?php echo $option['image']?wp_get_attachment_url($option['image']):'' ?>">
                        </div>
                        <div  class="bundle-option-product-info" >
                        <div class="quantity"><?php echo esc_html($option['quantity']); ?></div>
                        <div class="price"><?php echo wc_price($product->get_price() * (1 - $option['discount'] / 100)); ?></div>
                        <div class="per-unit"><?php _e('Per Unit', 'kund-bundle-quantity'); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="total-price">
                <span class="original-price"><?php echo wc_price($product->get_price() * $bundle_options[0]['quantity']); ?></span>
                <span class="discounted-price"><?php echo wc_price($product->get_price() * $bundle_options[0]['quantity'] * (1 - $bundle_options[0]['discount'] / 100)); ?></span>
                <span class="saving">Saving <?php echo esc_html($bundle_options[0]['discount']); ?>%</span>
            </div>
            <button class="add-to-cart-bundle" data-productid="<?php echo $product->get_id(); ?>" ><?php _e('ADD TO CART', 'kund-bundle-quantity'); ?></button>
            <div class="stock-info">
                <?php if ($product->is_in_stock()) : ?>
                    <span class="in-stock">✓ In Stock</span>
                <?php endif; ?>
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
            $discounted_price = $base_price * (1 - $discount / 100);
            return wc_price($discounted_price);
        }
        return $price;
    }


    public function add_bundle_option_template() {
        ?>
        <script type="text/template" id="tmpl-bundle-option-template">
            <div class="bundle-option">
                <p class="form-field">
                    <label><?php _e('Quantity', 'kund-bundle-quantity'); ?></label>
                    <input type="number" name="bundle_options[{{data.index}}][quantity]" value="" min="1" step="1" />
                </p>
                <p class="form-field">
                    <label><?php _e('Discount (%)', 'kund-bundle-quantity'); ?></label>
                    <input type="number" name="bundle_options[{{data.index}}][discount]" value="" min="0" max="100" step="0.01" />
                </p>
                <button type="button" class="button remove-bundle-option"><?php _e('Remove', 'kund-bundle-quantity'); ?></button>
            </div>
        </script>
        <?php
    }

    public function add_bundle_to_cart() {
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $bundle_index = isset($_POST['bundle_index']) ? intval($_POST['bundle_index']) : -1;

        if (!$product_id || $bundle_index < 0) {
            wp_send_json_error('Invalid product or bundle selection.');
            wp_die();
        }

        $bundle_options = get_post_meta($product_id, '_bundle_options', true);

        if (!is_array($bundle_options) || !isset($bundle_options[$bundle_index])) {
            wp_send_json_error('Bundle option not found.');
            wp_die();
        }

        $selected_bundle = $bundle_options[$bundle_index];
        $quantity = intval($selected_bundle['quantity']);
        $discount = floatval($selected_bundle['discount']);

        $cart_item_data = array(
            'bundle_option' => array(
                'quantity' => $quantity,
                'discount' => $discount,
                'bundle_index' => $bundle_index
            )
        );

        // Add to cart with the bundle quantity
        $added = WC()->cart->add_to_cart($product_id, $quantity, 0, array(), $cart_item_data);

        if ($added) {
            wp_send_json_success(array(
                'message' => sprintf(__('%d items added to cart with %s%% discount.', 'kund-bundle-quantity'), $quantity, $discount)
            ));
        } else {
            wp_send_json_error(__('Error adding to cart. Please try again.', 'kund-bundle-quantity'));
        }

        wp_die();
    }


    public function calculate_cart_item_price($cart_object) {
        if (is_admin() && !defined('DOING_AJAX')) return;

        foreach ($cart_object->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['bundle_option'])) {
                $product = $cart_item['data'];
                $base_price = $product->get_price();
                $quantity = $cart_item['bundle_option']['quantity'];
                $discount = $cart_item['bundle_option']['discount'];

                // Calculate the discounted price
                $discounted_price = $base_price * (1 - $discount / 100);

                // Set the new price
                $cart_item['data']->set_price($discounted_price);
            }
        }
    }




    public function update_cart_item_quantity($product_quantity, $cart_item_key, $cart_item) {
        if (isset($cart_item['bundle_option'])) {
            return $cart_item['bundle_option']['quantity'];
        }
        return $product_quantity;
    }


}

new Kund_Bundle_Quantity();