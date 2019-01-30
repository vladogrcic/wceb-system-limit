<?php
/*
Plugin Name: Woocommerce Easy Booking Limit
Description: Limits dates available for products provided by the "WooCommerce Easy Booking plugin".
Version: 1.0.0
Author: Vlado Grčić
Author URI: https://vladogrcic.com
License: GPL2
*/

/*------------------------------------------------------------------------------------------------------------------------*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/**
 * Checks whether the "WooCommerce" or "WooCommerce Easy Booking" plugin are activated. 
 */
$is_wc_active   = in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' )));
$is_wceb_active = in_array( 'woocommerce-easy-booking-system/woocommerce-easy-booking.php', apply_filters( 'active_plugins', get_option( 'active_plugins' )));

if ( $is_wc_active && $is_wceb_active ):
    /**
     * Compares date ranges whether they overlap or intersect and outputs those dates or boolean.
     */
    include 'includes/class-dateinterval.php'; 

    /**
     * --------------------------------------------------------------------------------------------
     */

    /**
     * Loads scripts, files and various other things.
     */
    include 'includes/class-wcebl-main.php';
    /**
     * Methods that concern directly to the front-end of the site.
     */
    include 'includes/class-wcebl-front.php';
    /**
     * Validating order info, disables or redirects the cart page or similar.
     */
    include 'includes/class-wcebl-cart.php';  
    /**
     * Overrides methods in the main plugins "class-wceb-ajax.php" files and WCEB_Ajax class
     */     
    include 'includes/class-wcebl-override-ajax.php';    
    /**
     * Adds options to the main plugins Settings admin menu page.
     */   
    include 'includes/class-wcebl-settings.php';       
    /**
     * Various helper functions.
     */
    include 'includes/class-wcebl-helper.php';  
endif;
/**
 * Shows an admin notice if "WooCommerce" plugin is not activated.
 */
if ( !$is_wc_active ):
    function wc_admin_notice() {
	if(get_current_screen()->id == "update") return;
        $plugin_name = 'woocommerce';
        $plugin_title = 'Woocommerce';
        $install_link = '<a href="' . esc_url( network_admin_url('plugin-install.php?tab=plugin-information&plugin=' . $plugin_name ) ) . '" class="thickbox" title="More info about ' . $plugin_name . '">Install ' . $plugin_title . '</a>';
        ?>
        <div class="error notice">
            <p><?php _e( 'To use the "Woocommerce Easy Booking Limit" plugin, you need the "Woocommerce" plugin installed or activated if you have it already installed.', 'wc-required-install' ); ?></p>
            <p><?php echo $install_link ?></p>
        </div>
        <?php
    }
    add_action( 'admin_notices', 'wc_admin_notice' );
endif;
/**
 * Shows an admin notice if "WooCommerce Easy Booking" plugin is not activated.
 */
if ( !$is_wceb_active ):
    function wceb_admin_notice() {
	if(get_current_screen()->id == "update") return;
        $plugin_name = 'woocommerce-easy-booking-system';
        $plugin_title = 'Woocommerce Easy Booking System';
        $install_link = '<a href="' . esc_url( network_admin_url('plugin-install.php?tab=plugin-information&plugin=' . $plugin_name ) ) . '" class="thickbox" title="More info about ' . $plugin_name . '">Install ' . $plugin_title . '</a>';
        ?>
        <div class="error notice">
            <p><?php _e( 'To use the "Woocommerce Easy Booking Limit" plugin you need the "Woocommerce Easy Booking" plugin installed or activated if you have it already installed.', 'wceb-required-install' ); ?></p>
            <p><?php echo $install_link ?></p>
        </div>
        <?php
    }
    add_action( 'admin_notices', 'wceb_admin_notice' );
endif;
