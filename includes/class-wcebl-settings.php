<?php
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
            add_action( 'admin_init', array( $this, 'page_init' ), 20 );
            $this->options['wceb_add_to_cart_text'] = get_option( 'wceb_add_to_cart_text' );
        }
        /**
         * Register and add settings
         */
        public function page_init()
        {        
            $page = 'easy_booking_general_settings';
            $option_group = 'easy_booking_general_settings';
            $option_name = 'wceb_add_to_cart_text';
            $section = 'easy_booking_item_text_settings';

            register_setting(
                    $option_group,
                    $option_name,
                    array( $this, 'sanitize' ) // Sanitize
            );
            add_settings_section(
                $section,
                __( 'Button and other item texts settings', 'woocommerce-easy-booking-system' ),
                array( $this, 'general_settings_section' ),
                $page
            );
            
            add_settings_field(
                'wceb_add_to_cart_text',
                __( ucfirst( 'Add to cart button text' ), 'woocommerce-easy-booking-system' ),
                array( $this, 'setting_item_callback' ),
                $page,
                $section
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
        public function sanitize( $input )
        {
            $new_input = '';
            if( isset( $input ) )
                $new_input = sanitize_text_field( $input );

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
        public function setting_item_callback()
        {
            printf(
                '<input type="text" id="wceb_add_to_cart_text" name="wceb_add_to_cart_text" value="%s" />',
                ( $this->options['wceb_add_to_cart_text'] ) ? esc_attr( $this->options['wceb_add_to_cart_text']) : ''
            );
        }
    }
    if( is_admin() )
        return new WCEBL_Settings();
endif;