(function( $ ) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

    //M2 Calculator function for simple products
    function simple_m2_calculator_simple_product_js () {
        // get m2 and price
        var old_area_actual = $('#area_actual').text();
        var old_price = ($('.total_price').text()).replace("$", "");
        // calculate new price, quantity and actual m2 on new m2 input
        $('#area_needed').on('change', function() {
            var new_qty = Math.ceil(this.value/old_area_actual);
            var new_area_actual = (new_qty*old_area_actual).toFixed(2);
            var new_price = (old_price*new_qty).toFixed(2);
            $('#area_actual').text(new_area_actual);
            $('.qty').val(new_qty);
            $('.total_price').text('$ '+new_price);
        });
    
        // calculate new price and actual m2 on new quantity
        $('.qty').on('change', function() {
            var new_area_actual = (this.value*old_area_actual).toFixed(2);
            var new_price = (old_price*this.value).toFixed(2);
            $('#area_actual').text(new_area_actual);
            $('#area_needed').val(new_area_actual);
            $('.total_price').text('$ '+new_price);
        });
        $(window).on('load', function() {
            var old_qty = $('.qty').val();
            if (old_qty != 1) {
                var new_area_actual = (old_qty*old_area_actual).toFixed(2);
                var new_price = (old_price*old_qty).toFixed(2);
                $('#area_actual').text(new_area_actual);
                $('#area_needed').val(new_area_actual);
                $('.total_price').text('$ '+new_price);
            }
        });
    }

    function simple_m2_calculator_variation_edit_price(itemType) {
        //Edit variation price
        setTimeout(
            function() 
            {
                //do something special
                if ( (! $( "#simple-m2-per-bag" ).length) && ((typeof itemType !== "undefined") && (itemType != '')) ) {
                    $( "<p style='display:inline;' id='simple-m2-per-bag'> per " + itemType +"</p>" ).insertAfter( $( ".woocommerce-variation-price" ) );
                }
            }, 100);
    }
    
    function simple_m2_calculator_variation_product_js () {
        // Fires whenever variation selects are changed
        $( ".variations_form" ).on( "woocommerce_variation_select_change", function () {
            // get m2 and price
            var old_price;
            var old_area_actual;
            var qty = 1;
            $(this).on( 'found_variation', function( event, variation ) {
                // hide m2 calculator when no m2 is set
                qty = variation.min_qty;
                var itemType = '';
                if (typeof variation.itemType !== 'undefined') {
                    itemType = variation.itemType[0];
                }
                simple_m2_calculator_variation_edit_price(itemType);
                old_price = ($('.total_price').text()).replace("$", "");
                if (typeof variation.meta_data == 'undefined') {
                    $("#price_calculator-2").hide();
                    return;
                }
                if(! variation.meta_data.some(e => e.key == 'm2_price_calculator_var')) {
                    $("#price_calculator-2").hide();
                    return;
                }
                for(var i=0; i<variation.meta_data.length; i++){
                    if (variation.meta_data[i].key == 'm2_price_calculator_var') {
                        if (variation.meta_data[i].value == '') {
                            $("#price_calculator-2").hide();
                            return;
                        }
                        else {
                            $('#area_actual').text(((variation.meta_data[i].value)*qty).toFixed(2));
                            $('#area_needed').val(((variation.meta_data[i].value)*qty).toFixed(2));
                            old_area_actual = variation.meta_data[i].value;
                        }
                    }
                }
                // set m2 calculator details on selected variation
                var price = (variation.display_price);
                old_price = variation.display_price;
                $('#m2-price-value').text((price/old_area_actual).toFixed(2));
                $('.qty').val(qty);
                $('.total_price').text('$ '+((price)*qty.toFixed(2)));
                $("#price_calculator-2").show();
            });

            // reset data when no variation is selected
            $(this).on( 'reset_data', function() {
                $("#price_calculator-2").hide();
            });

            // calculate new price, quantity and actual m2 on new m2 input
            $('#area_needed').on('change', function() {
                var new_qty = Math.ceil(this.value/old_area_actual);
                var new_area_actual = (new_qty*old_area_actual).toFixed(2);
                var new_price = (old_price*new_qty).toFixed(2);
                $('#area_actual').text(new_area_actual);
                $('.qty').val(new_qty);
                $('.total_price').text('$ '+new_price);
            });
            
            // calculate new price and actual m2 on new quantity
            $('.qty').on('change', function() {
                var new_area_actual = (this.value*old_area_actual).toFixed(2);
                var new_price = (old_price*this.value).toFixed(2);
                $('#area_actual').text(new_area_actual);
                $('#area_needed').val(new_area_actual);
                $('.total_price').text('$ '+new_price);
            });
        } );
        $( ".single_variation_wrap" ).on( "show_variation", function ( event, variation ) {
            // Fired when the user selects all the required dropdowns / attributes
            // and a final variation is selected / shown
            // get m2 and price
            var old_price;
            var old_area_actual;
            var qty = 1;
            qty = variation.min_qty;
            var itemType = '';
            if (typeof variation.itemType !== 'undefined') {
                itemType = variation.itemType[0];
            }
            simple_m2_calculator_variation_edit_price(itemType);
            old_price = ($('.total_price').text()).replace("$", "");
            if (typeof variation.meta_data == 'undefined') {
                $("#price_calculator-2").hide();
                return;
            }
            if(! variation.meta_data.some(e => e.key == 'm2_price_calculator_var')) {
                $("#price_calculator-2").hide();
                return;
            }
            for(var i=0; i<variation.meta_data.length; i++){
                if (variation.meta_data[i].key == 'm2_price_calculator_var') {
                    if (variation.meta_data[i].value == '') {
                        $("#price_calculator-2").hide();
                        return;
                    }
                    else {
                        $('#area_actual').text(((variation.meta_data[i].value)*qty).toFixed(2));
                        $('#area_needed').val(((variation.meta_data[i].value)*qty).toFixed(2));
                        old_area_actual = variation.meta_data[i].value;
                    }
                }
            }
            // set m2 calculator details on selected variation
            var price = (variation.display_price);
            old_price = variation.display_price;
            $('#m2-price-value').text((price/old_area_actual).toFixed(2));
            $('.qty').val(qty);
            $('.total_price').text('$ '+((price)*qty.toFixed(2)));
            $("#price_calculator-2").show();

            // reset data when no variation is selected
            $(this).on( 'reset_data', function() {
                $("#price_calculator-2").hide();
            });

            // calculate new price, quantity and actual m2 on new m2 input
            $('#area_needed').on('change', function() {
                var new_qty = Math.ceil(this.value/old_area_actual);
                var new_area_actual = (new_qty*old_area_actual).toFixed(2);
                var new_price = (old_price*new_qty).toFixed(2);
                $('#area_actual').text(new_area_actual);
                $('.qty').val(new_qty);
                $('.total_price').text('$ '+new_price);
            });
            
            // calculate new price and actual m2 on new quantity
            $('.qty').on('change', function() {
                var new_area_actual = (this.value*old_area_actual).toFixed(2);
                var new_price = (old_price*this.value).toFixed(2);
                $('#area_actual').text(new_area_actual);
                $('#area_needed').val(new_area_actual);
                $('.total_price').text('$ '+new_price);
            });
        } );
    }

    $(document).ready(function() {
        simple_m2_calculator_simple_product_js();
        simple_m2_calculator_variation_product_js();
    });

})( jQuery );


