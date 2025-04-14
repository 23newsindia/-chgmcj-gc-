<?php
/*
Plugin Name: Custom T-shirt Discount
Description: Applies custom discounts on T-shirts based on quantity and categories
Version: 1.0
Author: Your Name
*/

if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('CTD_VERSION', '1.0.0');
define('CTD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CTD_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include core classes
require_once CTD_PLUGIN_DIR . 'includes/class-ctd-db.php';
require_once CTD_PLUGIN_DIR . 'includes/class-ctd-admin.php';

// Initialize plugin
function ctd_init() {
    // Initialize admin
    if (is_admin()) {
        new CTD_Admin();
    }
    
    // Add discount calculation
    add_action('woocommerce_cart_calculate_fees', 'ctd_calculate_discount');
}
add_action('plugins_loaded', 'ctd_init');

// Activation hook
register_activation_hook(__FILE__, 'ctd_activate');

function ctd_activate() {
    // Create database tables
    if (!CTD_DB::create_tables()) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('Failed to create required database tables. Please check your database permissions.');
    }
}

// Calculate discount
function ctd_calculate_discount($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }
    
    $rules = CTD_DB::get_all_rules();
    if (empty($rules)) {
        return;
    }
    
    foreach ($rules as $rule) {
        $categories = json_decode($rule->categories);
        $excluded_products = json_decode($rule->excluded_products);
        $eligible_items = [];
        
        // Count eligible items
        foreach ($cart->get_cart() as $cart_item) {
            $product_id = $cart_item['product_id'];
            $product_cats = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);
            
            if (!in_array($product_id, $excluded_products) && 
                array_intersect($categories, $product_cats)) {
                for ($i = 0; $i < $cart_item['quantity']; $i++) {
                    $eligible_items[] = $product_id;
                }
            }
        }
        
        // Apply discount
        $sets = floor(count($eligible_items) / $rule->quantity);
        if ($sets > 0) {
            $regular_price = 499 * $rule->quantity; // Assuming regular price is 499
            $discount = ($regular_price - $rule->discount_price) * $sets;
            if ($discount > 0) {
                $cart->add_fee(
                    sprintf('T-shirt Discount (%d for ₹%d)', $rule->quantity, $rule->discount_price),
                    -$discount
                );
            }
        }
    }
}