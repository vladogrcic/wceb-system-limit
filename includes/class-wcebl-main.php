<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( ! class_exists( 'WCEBL_Main' ) ) :
    class WCEBL_Main {
        public function __construct() { 
            add_filter('woocommerce_product_data_store_cpt_get_products_query', array( $this, 'handle_custom_query_var'), 10, 2 );
            add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts'), 20, 1);
        }
        public function load_scripts() {
            wp_register_script('main_script', plugins_url('js/script.js', dirname(__FILE__)),array('jquery'),'1.1', true);
            wp_enqueue_script('main_script');
        }
        /**
         * Handle a custom 'bookable' query var to get products with the 'bookable' meta.
        **/
        public function handle_custom_query_var( $query, $query_vars ) {
            if ( ! empty( $query_vars['bookable'] ) ) {
                $query['meta_query'][] = array(
                    'key' => '_booking_option',
                    'value' => esc_attr( $query_vars['bookable'] ),
                );
            }
            return $query;
        }
    }
    return new WCEBL_Main();
endif;