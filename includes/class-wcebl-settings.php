<?php
use EasyBooking\Settings;
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( ! class_exists( 'WCEBL_Settings' ) ) :
    class WCEBL_Settings {
        /**
         * Holds the values to be used in the fields callbacks
         */
        private $options;
        /**
         * Start up
         */
        public function __construct()
        {
            add_action( 'admin_init', array( $this, 'button_and_item_texts' ), 20 );
            add_action( 'admin_init', array( $this, 'products' ), 20 );
            $this->options['wceb_add_to_cart_text'] = get_option( 'wceb_add_to_cart_text' );
            $this->options['wceb_is_product_sold_individually'] = get_option( 'wceb_is_product_sold_individually' );
        }
        /**
         * Register and add settings
         */
        public function button_and_item_texts()
        {        
            $page = 'easy_booking_general_settings';
            $option_group = 'easy_booking_general_settings';
            $option_name = 'wceb_add_to_cart_text';
            $section = 'easy_booking_item_text_settings';

            register_setting(
                    $option_group,
                    $option_name,
                    array( $this, 'sanitize_text' ) // Sanitize
            );
            add_settings_section(
                $section,
                __( 'Button and other item text settings', 'woocommerce-easy-booking-system' ),
                array( $this, 'general_settings_section' ),
                $page
            );
            
            add_settings_field(
                $option_name,
                __( ucfirst( 'Add to cart button text' ), 'woocommerce-easy-booking-system' ),
                array( $this, 'setting_item_callback' ),
                $page,
                $section,
                $option_name
            );      
        }
        /**
         * Register and add settings
         */
        public function products()
        {        
            $page = 'easy_booking_general_settings';
            $option_group = 'easy_booking_general_settings';
            $option_name = 'wceb_is_product_sold_individually';
            $section = 'easy_booking_product_settings';

            register_setting(
                    $option_group,
                    $option_name,
                    array( $this, 'sanitize_bool' ) // Sanitize
            );
            add_settings_section(
                $section,
                __( 'Product settings', 'woocommerce-easy-booking-system' ),
                array( $this, 'general_settings_section' ),
                $page
            );
            
            add_settings_field(
                $option_name,
                __( ucfirst( 'Is the product sold individually?' ), 'woocommerce-easy-booking-system' ),
                array( $this, 'setting_item_callback' ),
                $page,
                $section,
                $option_name
            );      
        }
        /**
         *
         * General settings section description.
         *
         */
        public function general_settings_section() {
            echo '';
        }
        /**
         * Sanitize each setting field as needed
         *
         * @param array $input Contains all settings fields as array keys
         */
        public function sanitize_text( $input )
        {
            $new_input = '';
            $new_input = sanitize_text_field( $input );

            return $new_input;
        }
        public function sanitize_bool( $input )
        {
            $new_input = '';
            $new_input = filter_var($input, FILTER_VALIDATE_BOOLEAN);

            return $new_input;
        }
        /** 
         * Print the Section text
         */
        public function print_section_info()
        {
            print 'Enter your settings below:';
        }
        /** 
         * Get the settings option array and print one of its values
         */
        public function setting_item_callback($item)
        {
            if($item === 'wceb_add_to_cart_text'){
                printf(
                    '<input type="text" id="wceb_add_to_cart_text" name="wceb_add_to_cart_text" value="%s" />',
                    ( $this->options['wceb_add_to_cart_text'] ) ? esc_attr( $this->options['wceb_add_to_cart_text']) : ''
                );
            }
            if($item === 'wceb_is_product_sold_individually'){
                print(
                    '<input type="checkbox" id="wceb_is_product_sold_individually" name="wceb_is_product_sold_individually" '.checked( esc_attr( get_option( 'wceb_is_product_sold_individually' ) ), true, false ).' />'
                );
            }
        }
    }
    if( is_admin() )
        return new WCEBL_Settings();
endif;