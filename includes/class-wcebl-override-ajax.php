<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( ! class_exists( 'WCEBL_Override_Ajax' ) ) :
    class WCEBL_Override_Ajax{
        public function __construct() {
            remove_action( 'wp_ajax_add_new_price', array( 'WCEB_Ajax', 'wceb_get_new_price' ) );
            remove_action( 'wp_ajax_nopriv_add_new_price', array( 'WCEB_Ajax', 'wceb_get_new_price' ) );
            remove_action( 'wceb_ajax_add_new_price', array( 'WCEB_Ajax', 'wceb_get_new_price' ) );

            add_action( 'wp_ajax_add_new_price', array( $this, 'wceb_get_new_price' ) );
            add_action( 'wp_ajax_nopriv_add_new_price', array( $this, 'wceb_get_new_price' ) );
            add_action( 'wceb_ajax_add_new_price', array( $this, 'wceb_get_new_price' ) );
        }
        /**
         * Calculates new price, update product meta and refresh fragments.
         */
        public static function wceb_get_new_price() {

            check_ajax_referer( 'set-dates', 'security' );

            $product_id   = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : ''; // Product ID
            $variation_id = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : ''; // Variation ID
            $children     = isset( $_POST['children'] ) ? array_map( 'absint', $_POST['children'] ) : array(); // Product children for grouped and variable products

            $id = ! empty( $variation_id ) ? $variation_id : $product_id; // Product or variation id

            $calc_mode = get_option( 'wceb_booking_mode' ); // Calculation mode (Days or Nights)

            $start = isset( $_POST['start_format'] ) ? sanitize_text_field( $_POST['start_format'] ) : ''; // Booking start date 'yyyy-mm-dd'
            $end   = isset( $_POST['end_format'] ) ? sanitize_text_field( $_POST['end_format'] ) : ''; // Booking end date 'yyyy-mm-dd'

            $product  = wc_get_product( $product_id ); // Product object
            $_product = ( $product_id !== $id ) ? wc_get_product( $id ) : $product; // Product or variation object

            if ( ! $_product ) {
                return;
            }

            // If product is variable and no variation was selected
            if ( $product->is_type( 'variable' ) && empty( $variation_id ) ) {
                self::wceb_throw_error( 3 );
            }

            // If product is grouped and no quantity was selected for grouped products
            if ( $product->is_type( 'grouped' ) && empty( $children ) ) {
                self::wceb_throw_error( 4 );
            }

            $number_of_dates = wceb_get_product_booking_dates( $_product );

            // If date format is "one", check only one date is set
            if ( $number_of_dates === 'one' ) {
                
                $dates = 'one_date';
                $duration = 1;

                // If end date is set
                if ( ! empty( $end ) ) {
                    self::wceb_throw_error( 5 );
                }

                // If date is empty
                if ( empty( $start ) ) {
                    self::wceb_throw_error( 6 );
                }

            } else { // "Two" dates check

                $dates = 'two_dates';

                // If one date is empty
                if ( empty( $start ) || empty( $end ) ) {
                    self::wceb_throw_error( 2 );
                }

                $start_time = strtotime( $start );
                $end_time   = strtotime( $end );

                // If end date is before start date
                if ( $end_time < $start_time ) {
                    self::wceb_throw_error( 1 );
                }

                // Get booking duration in days
                $duration = absint( ( $start_time - $end_time ) / 86400 );

                if ( $duration == 0 ) {
                    $duration = 1;
                }

                // If booking mode is days and calculation mode is set to "Days", add one day
                if ( $calc_mode === 'days' && ( $start != $end ) ) {
                    $duration += 1 ;
                }

                $booking_duration = wceb_get_product_booking_duration( $_product );

                // If booking mode is weeks and duration is a multiple of 7
                if ( $booking_duration === 'weeks' ) {

                    if ( $calc_mode === 'nights' && $duration % 7 === 0 ) { // If in weeks mode, check that the duration is a multiple of 7
                        $duration /= 7;
                    } else if ( $calc_mode === 'days' && $duration % 6 === 0 ) { // Or 6 in "Days" mode
                        $duration /= 6;
                    } else { // Otherwise throw an error
                        self::wceb_throw_error( 1 );
                    }
                    
                } else if ( $booking_duration === 'custom' ) {

                    $custom_booking_duration = wceb_get_product_custom_booking_duration( $_product );

                    if ( $duration % $custom_booking_duration === 0 ) {
                        $duration /= $custom_booking_duration;
                    } else {
                        self::wceb_throw_error( 1 );
                    }

                }

                // If number of days is inferior to 0
                if ( $duration <= 0 ) {
                    self::wceb_throw_error( 1 );
                }

            }

            // Store data in array
            $data = array(
                'start' => $start
            );

            if ( isset( $duration ) && ! empty( $duration ) ) {
                $data['duration'] = $duration;
            }

            if ( isset( $end ) && ! empty( $end ) ) {
                $data['end'] = $end;
            }

            $booking_data = array();

            $new_price = 0;
            $new_regular_price = 0;

            // Grouped or Bundle product types
            if ( $product->is_type( 'grouped' ) || $product->is_type( 'bundle' ) ) {

                if ( ! empty( $children ) ) foreach ( $children as $child_id => $quantity ) {

                    if ( $quantity <= 0 || ( $child_id === $id ) ) {
                        continue;
                    }

                    $child = wc_get_product( $child_id );

                    $children_prices[$child_id] = wceb_get_product_price( $product, $child, false, 'array' );

                    // Multiply price by duration only if children is bookable
                    if ( $children_prices[$child_id] ) {
                        
                        if ( wceb_is_bookable( $child ) ) {

                            if ( $children_prices[$child_id] ) foreach ( $children_prices[$child_id] as $price_type => $price ) {

                                if ( $price === "" ) {
                                    continue;
                                }

                                if ( $number_of_dates === 'two' ) {
                                    $price *= $duration;
                                }

                                ${'child_new_' . $price_type} = apply_filters(
                                    'easy_booking_' . $dates . '_price',
                                    wc_format_decimal( $price ), // Regular or sale price for x days
                                    $product, $child, $data, $price_type
                                );

                            }

                        } else {

                            $child_new_price = wc_format_decimal( $children_price[$child_id]['price'] );

                            if ( isset( $children_price[$child_id]['regular_price'] ) ) {
                                $child_new_regular_price = wc_format_decimal( $children_price[$child_id]['regular_price'] );
                            }

                        }

                    } else {

                        // Tweak for not individually sold bundled products
                        $child_new_price = 0;
                        $child_new_regular_price = 0;

                    }

                    // Maybe add additional costs
                    if ( ! wceb_pao_version_3() ) {

                        $child_new_price = self::wceb_add_additional_costs( $child_new_price, $duration, $child_id );

                        if ( isset( $child_new_regular_price ) ) {
                            $child_new_regular_price = self::wceb_add_additional_costs( $child_new_regular_price, $duration, $child_id );
                        }

                    } else {

                        $additional_costs = self::wceb_get_additional_costs( $child_id, $duration, $child_new_price );

                    }

                    // Maybe add additional costs from parent product
                    if ( $product->is_type( 'grouped' ) ) {

                        if ( ! wceb_pao_version_3() ) {

                            $child_new_price = self::wceb_add_additional_costs( $child_new_price, $duration, $id );

                            if ( isset( $child_new_regular_price ) ) {
                                $child_new_regular_price = self::wceb_add_additional_costs( $child_new_regular_price, $duration, $id );
                            }

                        }

                    }

                    $data['new_price'] = $child_new_price;

                    if ( isset( $child_new_regular_price ) && ! empty( $child_new_regular_price ) ) {
                        $data['new_regular_price'] = $child_new_regular_price;
                    }

                    if ( true === wceb_pao_version_3() && isset( $additional_costs ) ) {
                        $data['additional_costs'] = $additional_costs;
                    }

                    // Store parent produt for bundled items
                    if ( $product->is_type( 'bundle' ) ) {
                        $data['grouped_by'] = $product;
                    }

                    $booking_data[$child_id] = $data;

                    if ( $product->is_type( 'grouped' ) ) {

                        $new_price += wc_format_decimal( $child_new_price * $quantity );

                        if ( isset( $child_new_regular_price ) ) {
                            $new_regular_price += wc_format_decimal( $child_new_regular_price * $quantity );
                        }

                    }

                }

                if ( $product->is_type( 'grouped' ) ) {

                    if ( true === wceb_pao_version_3() ) {
                        $additional_costs = self::wceb_get_additional_costs( $id, $duration, $new_price );
                    }
                    
                }

                if ( $product->is_type( 'bundle' ) ) {

                    $prices = (array) wceb_get_product_price( $product, false, false, 'array' );

                    if ( $prices ) foreach ( $prices as $price_type => $price ) {

                        if ( $price === "" ) {
                            continue;
                        }

                        if ( $number_of_dates === 'two' ) {
                            $price *= $duration;
                        }

                        ${'new_' . $price_type} = apply_filters(
                            'easy_booking_' . $dates . '_price',
                            wc_format_decimal( $price ), // Regular or sale price for x days
                            $product, $_product, $data, $price_type
                        );

                        if ( ! wceb_pao_version_3() ) {
                            ${'new_' . $price_type} = self::wceb_add_additional_costs( ${'new_' . $price_type}, $duration, $id );
                        } else {
                            $additional_costs = self::wceb_get_additional_costs( $id, $duration, $price );
                        }

                    }
                    
                }

            } else {

                // Get product price and (if on sale) regular price
                $prices = (array) wceb_get_product_price( $_product, false, false, 'array' );

                if ( $prices ) foreach ( $prices as $price_type => $price ) {

                    if ( $price === "" ) {
                        continue;
                    }

                    if ( $number_of_dates === 'two' ) {
                        $price *= $duration;
                    }

                    ${'new_' . $price_type} = apply_filters(
                        'easy_booking_' . $dates . '_price',
                        wc_format_decimal( $price ), // Regular or sale price for x days
                        $product, $_product, $data, $price_type
                    );
                    
                    if ( ! wceb_pao_version_3() ) {
                        ${'new_' . $price_type} = self::wceb_add_additional_costs( ${'new_' . $price_type}, $duration, $product_id );
                    } else {
                        $additional_costs = self::wceb_get_additional_costs( $id, $duration, $price );
                    }

                }
                
            }

            $data['new_price'] = $new_price;

            if ( isset( $new_regular_price ) && ! empty( $new_regular_price ) && ( $new_regular_price !== $new_price ) ) {
                $data['new_regular_price'] = $new_regular_price;
            } else {
                unset( $data['new_regular_price'] ); // Unset value in case it was set for a child product
            }

            if ( true === wceb_pao_version_3() && isset( $additional_costs ) ) {
                $data['additional_costs'] = $additional_costs;
            }

            $booking_data[$id] = $data;

            // Update session data
            if ( ! WC()->session->has_session() ) {
                WC()->session->set_customer_session_cookie( true );
            }

            WC()->session->set( 'booking', $booking_data );

            $start_time;
            // Return fragments
            self::wceb_new_price_fragment( $id, $children, $booking_data );

            die();

        }
        
        /**
        *
        * Gets error messages
        *
        * @param int $error_code
        * @return str $err - Error message
        *
        **/
        private static function wceb_get_date_error( $error_code ) {

            switch ( $error_code ) {
                case 1:
                    $err = __( 'Please choose valid dates', 'woocommerce-easy-booking-system' );
                break;
                case 2:
                    $err = __( 'Please choose two dates', 'woocommerce-easy-booking-system' );
                break;
                case 3:
                    $err = __( 'Please select product option', 'woocommerce-easy-booking-system' );
                break;
                case 4:
                    $err = __( 'Please choose the quantity of items you wish to add to your cart&hellip;', 'woocommerce' );
                break;
                case 5:
                    $err = __( 'You can only select one date', 'woocommerce-easy-booking-system' );
                break;
                case 6:
                    $err = __( 'Please choose a date', 'woocommerce-easy-booking-system' );
                break;
                default:
                    $err = '';
                break;
            }

            return $err;
        }

        /**
        *
        * Throws an error message
        * @param int $error_code
        *
        **/
        public static function wceb_throw_error( $error_code ) {
            $error_message = self::wceb_get_date_error( $error_code );

            WCEB_Ajax::wceb_error_fragment( $error_message );
            WCEB_Ajax::wceb_clear_booking_session();
            die();
        }
        /**
        *
        * Adds additional costs (compatibility with WooCommerce Product Addons)
        * @param int - $price - Product price
        * @param array - $additional_cost
        * @param int - $id
        * @return int - $price
        *
        **/
        public static function wceb_add_additional_costs( $price, $duration, $id ) {

            // Get additional costs (for WooCommerce Product Addons)
            $additional_costs = self::wceb_get_additional_costs( $id, $duration, $price );

            if ( ! empty( $additional_costs ) ) {
                $price += $additional_costs;
            }

            return apply_filters( 'easy_booking_additional_costs', $price, $additional_costs, $id );

        }
        /**
        *
        * Get a simplified array of product add-ons
        * @param WC_Product - $product
        * @return array|bool - $addons
        *
        **/
        public static function wceb_get_product_addons( $product_id ) {

            // Make sure Product Addons function exists
            if ( function_exists( 'get_product_addons' ) ) {
                $product_addons = get_product_addons( $product_id );
            } else if ( class_exists( 'WC_Product_Addons_Helper' ) && method_exists( 'WC_Product_Addons_Helper', 'get_product_addons' ) ) {
                $product_addons = WC_Product_Addons_Helper::get_product_addons( $product_id );
            } else {
                return false;
            }

            $addons = array();

            if ( ! empty( $product_addons ) ) foreach ( $product_addons as $index => $addon ) {

                // PAO 3.0.0 uses 'field_name'
                $field_name = isset( $addon['field_name'] ) ? $addon['field_name'] : $addon['field-name'];

                foreach ( $addon['options'] as $index => $option ) {

                    $addons[$field_name][$index] = array(
                        'multiply' => isset( $option['multiply'] ) ? $option['multiply'] : 0,
                        'type'     => isset( $option['price_type'] ) ? $option['price_type'] : 'flat_fee'
                    );

                }

            }

            return $addons;
        }
        /**
        *
        * Calculates and formats additional costs (compatibility with WooCommerce Product Addons)
        * @param WC_Product - $_product - Product or variation object
        * @param int - $duration
        * @return array - $additional_cost
        *
        **/
        public static function wceb_get_additional_costs( $id, $duration, $price ) {

            $_product = wc_get_product( $id );

            // Get additional costs
            $additional_costs = isset( $_POST['additional_cost'] ) ? $_POST['additional_cost'] : array();     

            // Get product addons
            $product_addons = self::wceb_get_product_addons( $id );

            if ( ! $product_addons || empty( $product_addons ) ) {
                return false;
            }

            $additional_cost = 0;
            foreach ( $additional_costs as $addon_field_name => $addon ) {

                if ( ! is_array( $addon ) ) {
                    continue;
                }

                // Sanitize
                $addon = array_map( 'absint', $addon );

                foreach ( $addon as $index => $cost ) {

                    if ( isset( $product_addons[$addon_field_name] ) ) {

                        // Multiply addon cost by booking duration?
                        $maybe_multiply = isset( $product_addons[$addon_field_name][$index]['multiply'] ) ? absint( $product_addons[$addon_field_name][$index]['multiply'] ) : 0;

                        // Backward compatibility - Pass true to filter to multiply additional costs by booking duration (default: false)
                        if ( ! $maybe_multiply && true === apply_filters( 'easy_booking_multiply_additional_costs', false ) ) {
                            $maybe_multiply = 1;
                        }

                        if ( $product_addons[$addon_field_name][$index]['type'] === 'percentage_based' ) {
                            $cost = ( ( ( $price / $duration ) * $cost ) / 100 );
                        }

                        if ( $maybe_multiply ) {
                            $cost *= $duration;
                        }

                        // Store total addons costs for each product ID
                        $additional_cost += $cost;
                    }

                }

            }

            $prices_include_tax = get_option( 'woocommerce_prices_include_tax' );
            $tax_display_mode   = get_option( 'woocommerce_tax_display_shop' );
        
            // Get additional costs including or excluding taxes (for WooCommerce Product Addons)
            if ( ! empty( $additional_cost ) && $additional_cost > 0 ) {

                if ( $_product->is_taxable() ) {

                    $rates = WC_Tax::get_base_tax_rates( $_product->get_tax_class() );

                    if ( $prices_include_tax === 'yes' && $tax_display_mode === 'excl' ) {

                        $taxes = WC_Tax::calc_exclusive_tax( $additional_cost, $rates );

                        if ( $taxes ) foreach ( $taxes as $tax ) {
                            $additional_cost += $tax;
                        }

                    } else if ( $prices_include_tax === 'no' && $tax_display_mode === 'incl' ) {

                    $taxes = WC_Tax::calc_inclusive_tax( $additional_cost, $rates );

                    if ( $taxes ) foreach ( $taxes as $tax ) {
                            $additional_cost -= $tax;
                        }

                    }

                }
                
            }

            return $additional_cost;
        }
        /**
        *
        * Updates price fragment
        * @param int - $id - Product or variation ID
        * @param array - $children - Product chilren (for grouped and bundled products)
        * @param array - $booking_data
        *
        **/
        public static function wceb_new_price_fragment( $id, $children, $booking_data ) {

            $session = false;
            $product = wc_get_product( $id );
            
            if ( ! $product ) {
                self::wceb_error_fragment( __( 'Sorry there was a problem. Please try again.', 'woocommerce-easy-booking-system' ) );
            }

            if ( ! isset( $booking_data[$id] ) ) {
                self::wceb_error_fragment( __( 'Sorry there was a problem. Please try again.', 'woocommerce-easy-booking-system' ) );
            }

            $new_price = $booking_data[$id]['new_price']; // New booking price
            $new_regular_price = isset( $booking_data[$id]['new_regular_price'] ) ? $booking_data[$id]['new_regular_price'] : $new_price;

            // If it is a bundle product, add children's prices to the final booking price
            if ( $product->is_type( 'bundle') && ! empty( $children ) ) {

                foreach ( $children as $child_id => $qty ) {

                    if ( isset( $booking_data[$child_id] ) && $child_id !== $id ) {
                        $new_price += ( $booking_data[$child_id]['new_price'] * $qty );

                        if ( isset( $booking_data[$child_id]['new_regular_price'] ) ) {
                            $new_regular_price += ( $booking_data[$child_id]['new_regular_price'] * $qty );
                        } else {
                            $new_regular_price += ( $booking_data[$child_id]['new_price'] * $qty );
                        }

                        if ( isset( $booking_data[$child_id]['additional_costs'] ) ) {
                            $new_price += $booking_data[$child_id]['additional_costs'];
                            $new_regular_price += $booking_data[$child_id]['additional_costs'];
                        }
                    }

                }

            }

            $tax_display_mode = get_option( 'woocommerce_tax_display_shop' );

            // Regular price is product is on sale
            if ( isset( $new_regular_price ) && ( $new_regular_price != $new_price ) ) {

                $args = array( 'price' => $new_regular_price );

                if ( $tax_display_mode === 'incl' ) {

                    $new_regular_price = function_exists( 'wc_get_price_including_tax' ) ? wc_get_price_including_tax( $product, $args ) : $product->get_price_including_tax( 1, $new_regular_price );

                } else {

                    $new_regular_price = function_exists( 'wc_get_price_excluding_tax' ) ? wc_get_price_excluding_tax( $product, $args ) : $product->get_price_excluding_tax( 1, $new_regular_price );

                }

            } else {
                $new_regular_price = '';
            }

            $args = array( 'price' => $new_price );

            if ( $tax_display_mode === 'incl' ) {

                $new_price = function_exists( 'wc_get_price_including_tax' ) ? wc_get_price_including_tax( $product, $args ) : $product->get_price_including_tax( 1, $new_price );

            } else {

                $new_price = function_exists( 'wc_get_price_excluding_tax' ) ? wc_get_price_excluding_tax( $product, $args ) : $product->get_price_excluding_tax( 1, $new_price );

            }

            if ( isset( $booking_data[$id]['additional_costs'] ) ) {

                $new_price += $booking_data[$id]['additional_costs'];

                if ( ! empty( $new_regular_price ) ) {
                    $new_regular_price += $booking_data[$id]['additional_costs'];
                }

            }

            $details = '';

            if ( wceb_get_product_booking_dates( $product ) === 'two' ) {

                $duration = $booking_data[$id]['duration'];
                $average_price = floatval( $new_price / $duration );

                $calc_mode = get_option( 'wceb_booking_mode' ); // Calculation mode (Days or Nights)

                $booking_duration = wceb_get_product_booking_duration( $product );
                
                if ( $booking_duration === 'custom' ) {
                    $custom_duration = wceb_get_product_custom_booking_duration( $product );
                    $duration *= $custom_duration;
                }

                if ( $booking_duration === 'weeks' ) {
                    $unit = _n( 'week', 'weeks', $duration, 'woocommerce-easy-booking-system' );
                } else {
                    $unit = $calc_mode === 'nights' ? _n( 'night', 'nights', $duration, 'woocommerce-easy-booking-system' ) : _n( 'day', 'days', $duration, 'woocommerce-easy-booking-system' );
                }

                $details .= sprintf(
                    __( 'Total booking duration: %s %s', 'woocommerce-easy-booking-system' ),
                    absint( $duration ),
                    esc_html( $unit )
                );

                // Maybe display average price (if there are price variations. E.g Duration discounts or custom pricing)
                if ( true === apply_filters( 'easy_booking_display_average_price', false, $id ) ) {
                    $details .= '<br />';
                    $details .= sprintf(
                        __( 'Average price %s: %s', 'woocommerce-easy-booking-system' ),
                        wceb_get_price_html( $product ),
                        wc_price( $average_price )
                    );
                }
                
            }

            $details = apply_filters(
                'easy_booking_booking_price_details',
                $details,
                $product,
                $booking_data[$id]
            );

            ob_start();
            $data = ob_get_clean();

                $data = array(
                    'fragments' => apply_filters( 'easy_booking_fragments', array(
                        'session'               => true,
                        'booking_price'         => esc_attr( $new_price ),
                        'booking_regular_price' => isset( $new_regular_price ) ? esc_attr( $new_regular_price ) : '',
                        'input.wceb_nonce'      => '<input type="hidden" name="_wceb_nonce" class="wceb_nonce" value="' . wp_create_nonce( 'set-dates' ) . '">'
                        )
                    )
                );

            $orders = wc_get_orders_by_products($id);
            if(!are_date_ranges_overlapping($booking_data[$id]['start'], $booking_data[$id]['end'], $orders)){
                if ( isset( $details ) ) {
                    $data['fragments']['p.booking_details'] = '<p class="booking_details">' . wp_kses_post( $details ) . '</p>';
                }
            }
            else{
                $data['fragments']['p.booking_details'] = '<p class="booking_details">The date range you picked has unavailable dates. <br> It should contain only available dates.</p>';
                $data['fragments']['booking_price'] = 0;
            }

            wp_send_json( $data );
            die();

        }
    }
    return new WCEBL_Override_Ajax();
endif;