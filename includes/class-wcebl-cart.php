<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( ! class_exists( 'WCEBL_Cart' ) ) :
    class WCEBL_Cart {
        public function __construct() {
            add_filter( 'woocommerce_add_to_cart_validation', array($this, 'one_cart_item_at_the_time'), 10, 3 );
            add_filter( 'woocommerce_add_to_cart_redirect', array($this, 'add_to_cart_checkout_redirection'), 10, 1 );
            add_action( 'template_redirect', array($this, 'skip_cart_page_redirection_to_checkout'));
            add_filter( 'woocommerce_product_single_add_to_cart_text', array($this, 'custom_cart_button_text'));
            add_filter( 'wc_add_to_cart_message_html', '__return_null' );
            add_filter( 'woocommerce_add_to_cart_validation', array($this, 'validate_add_cart_item' ), 10, 5);
            if(get_option( 'wceb_is_product_sold_individually' ))
                add_filter( 'woocommerce_is_sold_individually', array($this, 'remove_all_quantity_fields' ), 10, 5);
        }
        /**
         * Limits the number of cart items to 1.
         */
        public function one_cart_item_at_the_time( $passed, $product_id, $quantity ) {
            if( ! WC()->cart->is_empty())
                WC()->cart->empty_cart();
            return $passed;
        }
        /**
         * Redirects directly from "Add to Cart", after clicking it, to the "Checkout" page.
         */
        public function add_to_cart_checkout_redirection( $url ) {
            return wc_get_checkout_url();
        }
        /** 
         * Redirects from the Cart page to the Checkout page. 
         */
        public function skip_cart_page_redirection_to_checkout() {
            if( is_cart() )
                wp_redirect( wc_get_checkout_url() );
        }
        /**
         * Changes the "Add to Cart" button text to the one you set in your settings.
         */
        public function custom_cart_button_text($inputText) {
            $class = new WCEBL_Settings();
            $text = get_option( 'wceb_add_to_cart_text' );
            if($text) return __($text, 'woocommerce');
            else return $inputText;
        }
        /**
         * Validates order details before adding it to the cart.
         */
        public function validate_add_cart_item( $passed, $product_id, $quantity, $variation_id = '', $variations= '' ) {
            $start = isset( $_POST['start_date_submit'] ) ? sanitize_text_field( $_POST['start_date_submit'] ) : ''; // Booking start date 'yyyy-mm-dd'
            $end   = isset( $_POST['end_date_submit'] ) ? sanitize_text_field( $_POST['end_date_submit'] ) : ''; // Booking end date 'yyyy-mm-dd'
            $orders = wc_get_orders_by_products($product_id);
            // do your validation, if not met switch $passed to false
            if(!(empty($start) && empty($end))){
                if ( are_date_ranges_overlapping($start, $end, $orders) ){
                    $passed = false;
                    wc_add_notice( __( 'You can not do that', 'textdomain' ), 'error' );
                }
                return $passed;
            }
            else{
                $passed = false;
                wc_add_notice( __( 'You can not do that', 'textdomain' ), 'error' );
            }
        }
        public function remove_all_quantity_fields( $return, $product ) {
            $cat = $product->get_categories();
            $product_cats_ids = wp_get_post_terms( $product->get_id(), 'product_cat' );
            for ($i=0; $i < count($product_cats_ids); $i++) { 
                if($product_cats_ids[$i]->slug==="cars"){
                return true;
                }
            }
        }
    }
    return new WCEBL_Cart();
endif;
