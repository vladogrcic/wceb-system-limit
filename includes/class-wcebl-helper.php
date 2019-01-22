<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/** 
 * Gets the absolute path to this plugin directory.
 */
function plugin_path() {
    return untrailingslashit( plugin_dir_path( __FILE__ ) );
}
/**
 * Gets an array of orders for a specific product.
 */
function wc_get_orders_by_products($id=null, $orders_args=[]){
    global $product;
    if( $product == null && $id !== null ){
        $product = wc_get_product( $id );
    }
    $id = $product->get_id();
    $orders = wc_get_orders($orders_args);
    $orders_by_products = [];
    for ($i=0; $i < count($orders); $i++) { 
        foreach ($orders[$i]->get_items() as $item_id => $item ) {
            if(!((int)wc_get_order_item_meta( $item_id, '_product_id', true)==$id))continue 2;
            $orders_by_products[] = $orders[$i];
        }
    }
    return $orders_by_products;
}
/**
 * Checks whether the given date ranges are overlapping. 
 * For instance if one date range contains dates from another.
 */
function are_date_ranges_overlapping($start, $end, $orders){
    $date_format = 'Y-m-j';
    $userStart = DateTime::createFromFormat($date_format, $start);
    $userEnd = DateTime::createFromFormat($date_format, $end);

    for ($k=0; $k < count($orders); $k++) { 
        foreach ($orders[$k]->get_items() as $item_id => $item ) {
            $start_date = wc_get_order_item_meta( $item_id, '_ebs_start_format', true);
            $seasonStart = DateTime::createFromFormat($date_format, $start_date);
    
            $end_date = wc_get_order_item_meta( $item_id, '_ebs_end_format', true);
            $seasonEnd = DateTime::createFromFormat($date_format, $end_date);
            
            $i1 = new ProperDateInterval($seasonStart, $seasonEnd);
            $i2 = new ProperDateInterval($userStart, $userEnd);

            $is_overlapping = $i1->overlapBool($i2);
            if($is_overlapping) break 2;
        }
    }
    return $is_overlapping;
}