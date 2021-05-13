<?php
/*
Plugin Name: Simple M2 Calculator
Description: Calculate M2.
Author:      Quinth Anthony Razuman
Version:     1.0
License:     GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.txt

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version
2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
with this program. If not, visit: https://www.gnu.org/licenses/

*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! defined( 'ABSPATH' )) {

    exit;

}

// Check if current user is an admin to activate plugin
function simple_m2_calculator_on_activation () {
    if ( ! current_user_can( 'activate_plugins' ) ) return;
}
register_activation_hook(__FILE__, 'simple_m2_calculator_on_activation');

// Check if current user is an admin to deactivate plugin
function simple_m2_calculator_on_deactivation () {
    if ( ! current_user_can( 'activate_plugins' ) ) return;
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'simple_m2_calculator_on_deactivation');

function simple_m2_calculator_on_uninstall () {
    // Check if current user is an admin to uninstall plugin
    if ( ! current_user_can( 'activate_plugins' ) ) return;
    
    // If uninstall not called from WordPress, then exit.
    if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
        exit;
    }
}
register_uninstall_hook(__FILE__, 'simple_m2_calculator_on_uninstall');

// Check if woocommerce is active 
if( in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

    // Actions when in the dashboard
    if ( is_admin() ) {
        add_action( 'woocommerce_product_options_inventory_product_data', 'simple_m2_calculator_wc_product_field');
        add_action( 'woocommerce_product_after_variable_attributes', 'simple_m2_calculator_variation_settings_fields', 10, 3 );
        if( in_array('minmax-quantity-for-woocommerce/woocommerce-minmax-quantity.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            add_action( 'save_post', 'simple_m2_calculator_wc_save_product_minmax'  );
            add_action( 'woocommerce_save_product_variation', 'simple_m2_calculator_save_variation_settings_fields_minmax', 100, 1 );
        }
        else{
            add_action( 'save_post', 'simple_m2_calculator_wc_save_product'  );
            add_action( 'woocommerce_save_product_variation', 'simple_m2_calculator_save_variation_settings_fields', 10, 3 );
        }
    }
    // Actions when in the frontend
    else {
        add_action('wp_enqueue_scripts', 'simple_m2_calculator_enqueue_styles');
        add_action( 'woocommerce_single_product_summary', 'simple_m2_calculator_simple_product', 25 );
        add_action( 'woocommerce_single_product_summary', 'simple_m2_calculator_wc_variation_product_page_price_edit', 9 );
        add_action( 'woocommerce_single_product_summary', 'simple_m2_calculator_wc_simple_product_page_price_edit', 11 );
        add_action( 'woocommerce_single_variation', 'simple_m2_calculator_variation_product', 15 );
        add_filter( 'woocommerce_get_price_html', 'simple_m2_calculator_change_simple_product_price_display', 100, 2 );
        add_filter( 'woocommerce_get_price_html', 'simple_m2_calculator_change_variation_product_price_display', 100, 2 );
    }
    
    //Enqueue plugin css and js
    function simple_m2_calculator_enqueue_styles() {
        wp_enqueue_style( 'simple-m2-calculator-css', plugin_dir_url( __FILE__ ) . 'includes/simple-m2-calculator.css', false, null, 'all' );
        wp_enqueue_script( 'simple-m2-calculator-js', plugin_dir_url( __FILE__ ) . 'includes/simple-m2-calculator.js', false, null, 'all' );
    }

    //Display m2 input in the product admin dashboard for simple products
    function simple_m2_calculator_wc_product_field() {
        global $post;
        $options = array(""=>"", 'pack'=>"pack", "bag"=>"bag", "tile"=>"tile", 'item'=>"item");
        wp_nonce_field('simple_m2_calculator', 'product_edit');
        woocommerce_wp_text_input( 
            array(  
                'id' => 'm2_price_calculator', 
                'class' => 'wc_input_stock short', 
                'label' => __( 'Actual Area (m2)', 'm2' ),
                'type' => 'number',
                'custom_attributes' => array('min' => '1', 'step' => '0.01'),
                'wrapper_class' => 'show_if_simple'
            ) 
        );
        $value = get_post_meta( $post->ID, 'simple-m2-calculator-item-type', true );
        if( empty( $value ) ) $value = '';
        woocommerce_wp_select( 
            array( 
                'id'      => 'simple-m2-calculator-item-type', 
                'label'   => __( 'Item Type', 'simple-m2-calculator-item-type' ),
                'options' =>  $options,
                'value' => $value,
            )
        );
    }

    //Save m2 input in the product admin dashboard for simple products
    function simple_m2_calculator_wc_save_product( $product_id ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if( empty($_REQUEST['product_edit']) || ! wp_verify_nonce($_REQUEST['product_edit'], 'simple_m2_calculator') ) {
            return;
        }
        if ( isset( $_POST['m2_price_calculator'] ) ) {
            update_post_meta( $product_id, 'm2_price_calculator', $_POST['m2_price_calculator'] );
        }
        if ( isset( $_POST['simple-m2-calculator-item-type'] ) ) {
            update_post_meta( $product_id, 'simple-m2-calculator-item-type', $_POST['simple-m2-calculator-item-type'] );
        }
    }

    function simple_m2_calculator_wc_save_product_minmax( $product_id ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if( empty($_REQUEST['product_edit']) || ! wp_verify_nonce($_REQUEST['product_edit'], 'simple_m2_calculator') ) {
            return;
        }
        if ( isset( $_POST['m2_price_calculator'] ) ) {
            update_post_meta( $product_id, 'm2_price_calculator', $_POST['m2_price_calculator'] );
        }
        if ( isset( $_POST['simple-m2-calculator-item-type'] ) ) {
            update_post_meta( $product_id, 'simple-m2-calculator-item-type', $_POST['simple-m2-calculator-item-type'] );
        }
        if ( isset( $_POST['min_quantity'] ) ) {
            update_post_meta( $product_id, 'min_quantity', $_POST['min_quantity'] );
        }
        if ( isset( $_POST['max_quantity'] ) ) {
            update_post_meta( $product_id, 'max_quantity', $_POST['max_quantity'] );
        }
    }

    //Display m2 input in the product admin dashboard for variation products
    function simple_m2_calculator_variation_settings_fields( $loop, $variation_data, $variation ) {
        wp_nonce_field('simple_m2_calculator', 'variation_edit');
        woocommerce_wp_text_input( 
            array( 
                'id'                => 'm2_price_calculator_var[' . $variation->ID . ']', 
                'label'             => __( 'Actual Area (m2)', 'm2' ),
                'value'             => get_post_meta( $variation->ID, 'm2_price_calculator_var', true ),
                'type'              => 'number',
                'custom_attributes' => array('min' => '1', 'step' => '0.01'),
                'wrapper_class'     => 'show_if_variable',
            )
        );
    }

    //Save m2 input in the product admin dashboard for variation products
    function simple_m2_calculator_save_variation_settings_fields( $post_id ) {
        if( empty($_REQUEST['variation_edit']) || ! wp_verify_nonce($_REQUEST['variation_edit'], 'simple_m2_calculator') ) {
            return;
        }
        if( isset( $_POST['m2_price_calculator_var'][ $post_id ] ) ) {
            update_post_meta( $post_id, 'm2_price_calculator_var', $_POST['m2_price_calculator_var'][ $post_id ] );
        }
    }

    //Save m2 input in the product admin dashboard for variation products
    function simple_m2_calculator_save_variation_settings_fields_minmax( $post_id ) {
        if( empty($_REQUEST['variation_edit']) || ! wp_verify_nonce($_REQUEST['variation_edit'], 'simple_m2_calculator') ) {
            return;
        }
        if( isset( $_POST['m2_price_calculator_var'][ $post_id ] ) ) {
            update_post_meta( $post_id, 'm2_price_calculator_var', $_POST['m2_price_calculator_var'][ $post_id ] );
        }
        if( isset( $_POST['min_quantity_var'][ $post_id ] ) ) {
            update_post_meta( $post_id, 'min_quantity_var', $_POST['min_quantity_var'][ $post_id ] );
        }
        if( isset( $_POST['max_quantity_var'][ $post_id ] ) ) {
            update_post_meta( $post_id, 'max_quantity_var', $_POST['max_quantity_var'][ $post_id ] );
        }
    }

    //Display m2 calculator in the product page for simple products
    function simple_m2_calculator_simple_product() {
        global $product;
        $id = $product->get_id();
        $product_attr = get_post_meta( $id, 'm2_price_calculator' );
        $min_qty_attr = (int)get_post_meta( $id, 'min_quantity' );
        $price = wc_get_price_to_display($product);
        $insulation_required = "Insulation required <br>";
        if (! $product->is_type( 'simple' ) || ! $product->is_purchasable() ) {
            return;
        }
        if( empty($product_attr) ){
            return;
        }
        if(empty($product_attr[0]) || $product_attr == '') {
            return;
        }
        if(empty($min_qty_attr)){
            $min_qty_attr = 1;
        }
        if( has_term( 'Batts', 'product_tag', $id ) ) {
            $insulation_required = "Insulation required <br><i>*allow 10% less for framing</i> (sq m)";
        }
        $m2_price = number_format(floatval($price) / floatval($product_attr[0]), 2, '.', '');
        $total_area = number_format($product_attr[0]*$min_qty_attr, 2, '.', ''); 
        $total_price = number_format($price*$min_qty_attr, 2, '.', '');
        $content = "
                        <table id='price_calculator-1' class='simple_m2_calculator'>
                            <tr class='price-table-row m2-price'>
                                <td>
                                    $ <span id='m2-price-value'>$m2_price</span> p/m2 inc. GST
                                </td>
                            </tr>
                            <tr class='price-table-row area-input'>
                                <td>
                                    <label for='area_needed'>
                                        $insulation_required			
                                    </label>
                                </td>
                                <td class='td-area'>
                                    <input type='number' step='0.01' min='1' data-unit='sq m' data-common-unit='sq m' name='area_needed' id='area_needed' class='amount_needed' autocomplete='off' value='$total_area'/>
                                </td>
                            </tr>
                            <tr class='price-table-row total-amount'>
                                <td>
                                    Actual Area (sq m)		</td>
                                <td class='td-area'>
                                    <span id='area_actual' class='amount_actual' data-unit='sq m'>$total_area</span>
                                </td>
                            </tr>

                            <tr class='price-table-row calculated-price'>
                                <td>
                                    Total Price		</td>
                                <td class='td-area'>
                                    <span class='total_price'>$ $total_price</span>
                                </td>
                            </tr>
                        </table>
                ";
            
        echo $content;
    }
    
    //Include meta data on variation data
    function custom_woocommerce_available_variation($variations, $product, $variation)
    {
        $metadata = $variation->get_meta_data();
        $id = $product->get_id();
        $itemType = get_post_meta( $id, 'simple-m2-calculator-item-type' );
        if (!empty($metadata) && (!empty($itemType))) {
            $variations = array_merge($variations, [
                'meta_data' => $metadata,
                'itemType' => $itemType,
            ]);
        }
        elseif (empty($metadata) && (!empty($itemType))) {
            $variations = array_merge($variations, [
                'itemType' => $itemType,
            ]);
        }
        elseif (!empty($metadata) && (empty($itemType))) {
            $variations = array_merge($variations, [
                'meta_data' => $metadata,
            ]);
        }

        return $variations;
    }
    add_filter('woocommerce_available_variation', 'custom_woocommerce_available_variation', 10, 3);

    //Display m2 calculator in the product page for variation products
    function simple_m2_calculator_variation_product() {
        global $product;
        $id = $product->get_id();
        $insulation_required = "Insulation required <br>";
        if( has_term( 'Batts', 'product_tag', $id ) ) {
            $insulation_required = "Insulation required <br><i>*allow 10% less for framing</i> (sq m)";
        }
        if ( $product->is_type( 'variable' ) ) {
            
            $content = "
                            <table id='price_calculator-2' class='wc-measurement-price-calculator-price-table variable_price_calculator quantity-based-mode' style='display:none;'>
                                <tr class='price-table-row m2-price'>
                                    <td>
                                        $ <span id='m2-price-value'></span> p/m2 inc. GST
                                    </td>
                                </tr>
                                <tr class='price-table-row area-input'>
                                    <td>
                                        <label for='area_needed'>
                                            $insulation_required			
                                        </label>
                                    </td>
                                    <td>
                                        <input type='number' step='0.01' min='1' data-unit='sq m' data-common-unit='sq m' name='area_needed' id='area_needed' class='amount_needed' autocomplete='off' value=''/>
                                    </td>
                                </tr>

                                <tr class='price-table-row total-amount'>
                                    <td>
                                        Actual Area (sq m)		</td>
                                    <td class='td-area'>
                                        <span id='area_actual' class='amount_actual' data-unit='sq m'></span>
                                    </td>
                                </tr>

                                <tr class='price-table-row calculated-price'>
                                    <td>
                                        Total Price		</td>
                                    <td class='td-area'>
                                        <span class='total_price'></span>
                                    </td>
                                </tr>
                            </table>
                    ";
                
            echo $content;
        }
    }

    function simple_m2_calculator_wc_variation_product_page_price_edit() {
        global $product;
        if (! is_product()) {
            return;
        }
        if ( $product->is_type( 'variable' ) && $product->is_purchasable() ) {
            $content = "<p class='from' style='display:inline'><span>From </span></p>";
                
            echo $content;
        }
    }

    function simple_m2_calculator_wc_simple_product_page_price_edit() {
        global $product;
        if (! is_product()) {
            return;
        }
        $id = $product->get_id();
        $itemType = get_post_meta( $id, 'simple-m2-calculator-item-type' );
        if (( $product->is_type( 'simple' ) && $product->is_purchasable() ) && (!empty($itemType) && $itemType[0] != '')) {
            $content = "<p class='from' style='display:inline'><span>per $itemType[0] </span></p>";
            echo $content;
        }
    }

    function simple_m2_calculator_change_simple_product_price_display( $price,  $product ) {
        if (is_product()) {
            return $price;
        }
        if (! $product->is_type( 'simple' ) ) {
            return $price;
        }
        
        if (! $product->is_purchasable() ){
            return $price;
        }
        $id = $product->get_id();
        $product_attr = get_post_meta( $id, 'm2_price_calculator' );
        $product_price = wc_get_price_to_display($product);
        if (! empty($product_attr[0]) ){
            $m2_price = floatval($product_price)/floatval($product_attr[0]);
            $m2_price = number_format($m2_price, 2, '.', '');
            return "From <span class='woocommerce-Price-amount amount'><bdi><span class='woocommerce-Price-currencySymbol'>&dollar;</span>$m2_price</bdi></span> p/m2 inc GST";
        }
        return $price;
      }

      function simple_m2_calculator_change_variation_product_price_display( $price, $product ) {
        if (is_product()) {
            return $price;
        }
        if (! $product->is_type( 'variable' ) ) {
            return $price;
        }
        if (! $product->is_purchasable() ){
            return $price;
        }
        $product_price = wc_get_price_to_display($product);
        $current_products_id = $product->get_children();
        foreach($current_products_id as $current_product_id) {
            $current_product = wc_get_product($current_product_id);
            $current_product_price = wc_get_price_to_display($current_product);
            if($current_product_price == $product_price){
                $product_attr = get_post_meta( $current_product_id, 'm2_price_calculator_var' );
                if (empty($product_attr[0])){
                    return $price;
                }
                $m2_price = floatval($product_price)/floatval($product_attr[0]);
                $m2_price = number_format($m2_price, 2, '.', '');
                return "From <span class='woocommerce-Price-amount amount'><bdi><span class='woocommerce-Price-currencySymbol'>&dollar;</span>$m2_price</bdi></span> p/m2 inc GST";
            }
        }
        return $price;
      }
      
}

