<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( ! class_exists( 'WCEBL_Front' ) ) :
    class WCEBL_Front {
        public function __construct() {
            add_action('woocommerce_before_single_product', array( $this, 'disable_dates' ));
        }
        /**
         * Disables dates that are unavailable because its reserved. 
         * It's passed to the pickadate.js jQuery plugin when called.
         */
        public function disable_dates(){
            global $product;
            $id = $product->get_id();
            $order = wc_get_orders_by_products();
            ?>
            <script>
                window.padjsAdditionalData = [];
            </script>
            <?php
            for ($i=0; $i < count($order); $i++) { 
                foreach ($order[$i]->get_items() as $item_id => $item ) {
                    $start_date = wc_get_order_item_meta( $item_id, '_ebs_start_format', true);
                    $end_date = wc_get_order_item_meta( $item_id, '_ebs_end_format', true);
                    
                    $start_time  = strtotime($start_date);
                    $start_day   = date('d', $start_time);
                    $start_month = date('m', $start_time);
                    $start_year  = date('Y', $start_time);

                    $end_time  = strtotime($end_date);
                    $end_day   = date('d', $end_time);
                    $end_month = date('m', $end_time);
                    $end_year  = date('Y', $end_time);
                ?>
                    <script>
                        padjsAdditionalData.push( 
                            {
                                start: [
                                    parseInt('<?php echo $start_day; ?>'),
                                    parseInt('<?php echo $start_month-1; ?>'),
                                    parseInt('<?php echo $start_year; ?>'),
                                ],
                                end: [
                                    parseInt('<?php echo $end_day; ?>'),
                                    parseInt('<?php echo $end_month-1; ?>'),
                                    parseInt('<?php echo $end_year; ?>'),
                                ],
                            }
                        );
                    </script>
                <?php
                }
            }
        }
    }
    return new WCEBL_Front();
endif;