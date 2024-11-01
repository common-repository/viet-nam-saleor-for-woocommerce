jQuery( function($) {

  // wc_admin_city_select_params is required to continue, ensure the object exists
  // wc_admin_city_select_params is used for select2 texts. This one is added by WC
  if ( typeof wc_admin_city_select_params === 'undefined' ) {
    return false;
  }

  function getEnhancedSelectFormatString() {
    var formatString = {
      formatMatches: function( matches ) {
        if ( 1 === matches ) {
          return wc_admin_city_select_params.i18n_matches_1;
        }

        return wc_admin_city_select_params.i18n_matches_n.replace( '%qty%', matches );
      },
      formatNoMatches: function() {
        return wc_admin_city_select_params.i18n_no_matches;
      },
      formatAjaxError: function() {
        return wc_admin_city_select_params.i18n_ajax_error;
      },
      formatInputTooShort: function( input, min ) {
        var number = min - input.length;

        if ( 1 === number ) {
          return wc_admin_city_select_params.i18n_input_too_short_1;
        }

        return wc_admin_city_select_params.i18n_input_too_short_n.replace( '%qty%', number );
      },
      formatInputTooLong: function( input, max ) {
        var number = input.length - max;

        if ( 1 === number ) {
          return wc_admin_city_select_params.i18n_input_too_long_1;
        }

        return wc_admin_city_select_params.i18n_input_too_long_n.replace( '%qty%', number );
      },
      formatSelectionTooBig: function( limit ) {
        if ( 1 === limit ) {
          return wc_admin_city_select_params.i18n_selection_too_long_1;
        }

        return wc_admin_city_select_params.i18n_selection_too_long_n.replace( '%qty%', limit );
      },
      formatLoadMore: function() {
        return wc_admin_city_select_params.i18n_load_more;
      },
      formatSearching: function() {
        return wc_admin_city_select_params.i18n_searching;
      }
    };

    return formatString;
  }

  // Select2 Enhancement if it exists
  if ( $().select2 ) {
    var wc_city_select_select2 = function() {
      $( 'select.city_select:visible' ).each( function() {
        var select2_args = $.extend({
          placeholderOption: 'first',
          width: '100%'
        }, getEnhancedSelectFormatString() );

        $( this ).select2( select2_args );
      });
    };

    wc_city_select_select2();

    $( document.body ).bind( 'city_to_select', function() {
      if (event.target.id === 'woocommerce_vnsfw_ghtk_sender_state' ||
          event.target.id === 'woocommerce_vnsfw_vtp_sender_state'){
        // Do not change in instance setting
        // wc_city_select_select2();
      } else {
        wc_city_select_select2();
      }
      
    });
  }

  /* City select boxes */
  var cities_json = wc_admin_city_select_params.cities.replace( /&quot;/g, '"' );
  var cities = $.parseJSON( cities_json );

  $( 'body' ).on( 'js_field-country_changing', function(e, country, $container) {
    var $statebox = $container.find( '#_billing_state, #_shipping_state' );
    var state = $statebox.val();

    $( document.body ).trigger( 'state_changing', [country, state, $container ] );
  });

  $("select[name='woocommerce_default_country']").change(function() {
    // console.log($( this ).val());
    var value = $( this ).val();
    var country_state = value.split(':');
    var country = country_state[0];
    var state = country_state[1];
    var $container = $( this ).closest( 'tbody' );
    $( document.body ).trigger( 'state_changing', [country, state, $container ] ); 
  });


  $( 'body' ).on( "change","select.wc-enhanced-select", function() {    
    // For woocommerce instance setting: shipping
    if (jQuery(this).attr("id") === 'woocommerce_vnsfw_ghtk_sender_state' ||
        jQuery(this).attr("id") === 'woocommerce_vnsfw_vtp_sender_state') {
      var $container = $( this ).closest( 'tbody' );
      var country = 'VN';
      var state = $(this).children("option:selected").val();
      $( document.body ).trigger( 'state_changing', [country, state, $container ] );
    } 
  });

  $( 'body' ).on( 'change', 'select.js_field-state', function() {
    var $container = $( this ).closest( 'div' );
    var country = $container.find( '#_billing_country, #_shipping_country', 
    '#woocommerce_vnsfw_ghtk_sender_state', '#woocommerce_vnsfw_vtp_sender_state' ).val();
    var state = $( this ).val();
    $( document.body ).trigger( 'state_changing', [country, state, $container ] );
  });

  $( 'body' ).on( 'state_changing', function(e, country, state, $container) {
    var $citybox = $container.find( 
      '#_billing_city, #_shipping_city, #woocommerce_store_city, \
      #woocommerce_vnsfw_ghtk_sender_city, #woocommerce_vnsfw_vtp_sender_city' 
      );
    if ( cities[ country ] ) {      
      /* if the country has no states */
      if( cities[country] instanceof Array) {        
        cityToSelect( $citybox, cities[ country ] );
      } else if ( state ) {
        if ( cities[ country ][ state ] ) {
          cityToSelect( $citybox, cities[ country ][ state ] );
        } else {
          cityToInput( $citybox );
        }
      } else {
        disableCity( $citybox );
      }
    } else {
      cityToInput( $citybox );
    }
  });

  /* Ajax replaces .cart_totals (child of .cart-collaterals) on shipping calculator */
  if ( $( '.cart-collaterals' ).length && $( '#calc_shipping_state' ).length ) {
    var calc_observer = new MutationObserver( function() {
      $( '#calc_shipping_state' ).change();
    });
    calc_observer.observe( document.querySelector( '.cart-collaterals' ), { childList: true });
  }

  function cityToInput( $citybox ) {
    if ( $citybox.is('input') ) {
      $citybox.prop( 'disabled', false );
      return;
    }

    var input_name = $citybox.attr( 'name' );
    var input_id = $citybox.attr( 'id' );
    var placeholder = $citybox.attr( 'placeholder' );

    $citybox.parent().find( '.select2-container' ).remove();

    $citybox.replaceWith( '<input type="text" class="input-text" name="' + input_name + '" id="' + input_id + '" placeholder="' + placeholder + '" />' );
  }

  function disableCity( $citybox ) {
    $citybox.val( '' ).change();
    $citybox.prop( 'disabled', true );
  }

  function cityToSelect( $citybox, current_cities ) {
    var value = $citybox.val();
    var input_name = $citybox.attr( 'name' );
    var input_id = $citybox.attr( 'id' );
    var placeholder = $citybox.attr( 'placeholder' );

    if ( $citybox.is('input') ) {
      
      $citybox.replaceWith( '<select name="' + input_name + '" id="' + input_id + '" class="city_select wc-enhanced-select" placeholder="' + placeholder + '"></select>' );
      //we have to assign the new object, because of replaceWith
      $citybox = $('#'+input_id);
    } else if ($citybox.is('select')) {
      // $citybox.replaceWith( '<select name="' + input_name + '" id="' + input_id + '" class="wc-enhanced-select" placeholder="' + placeholder + '"></select>' );
    } else {
      $citybox.prop( 'disabled', false );
    }

    var options = '';
    for( var index in current_cities ) {
      if ( current_cities.hasOwnProperty( index ) ) {
        var cityName = current_cities[ index ];
        if (input_id === 'woocommerce_store_city' ||
            input_id === 'woocommerce_vnsfw_ghtk_sender_city' ||
            input_id === 'woocommerce_vnsfw_vtp_sender_city') {
          options = options + '<option value="' + index + '">' + cityName + '</option>';
        } else {
          options = options + '<option value="' + cityName + '">' + cityName + '</option>';
        }        
      }
    }

    $citybox.html( '<option value="">' + wc_admin_city_select_params.i18n_select_city_text + '</option>' + options );

    if ( $('option[value="'+value+'"]', $citybox).length ) {
      $citybox.val( value ).change();
    } else {
      $citybox.val( '' ).change();
    }

    $( document.body ).trigger( 'city_to_select' );
  }
  
  // Update css
  $().ready(function() {
    // Billing edit
    $("p.form-field._billing_country_field").css({'float': 'right', 'clear': 'right'});
    $("p.form-field._billing_email_field").css({'float': 'left', 'clear': 'left', 'margin-top': '5px'});
    $("p.form-field._billing_phone_field").css({'float': 'right', 'clear': 'right', 'margin-top': '5px'});
    $("p.form-field._billing_city_field").css({'float': 'right', 'clear': 'right'});
    $("p.form-field._billing_state_field").css({'float': 'left', 'clear': 'left'});
    $("p.form-field._billing_address_1_field").css({
      'width': '100%',
      'margin': '9px 0 0'
    });
    // Shipping edit
    $("p.form-field._shipping_country_field").css({'float': 'right', 'clear': 'right'}); // Default is VN so make it disable
    $("p.form-field._shipping_city_field").css({'float': 'right', 'clear': 'right'});
    $("p.form-field._shipping_state_field").css({'float': 'left', 'clear': 'left'});
    $("p.form-field.shipping_phone_field.form-field-first").css({'float': 'left', 'clear': 'left'});
    // $("p.form-field.shipping_date_field.form-field-last").css({'float': 'right', 'clear': 'right'});
    $("p.form-field.pay_shipping_fee_field.form-field-first").css({'float': 'left', 'clear': 'left'});
    $("p.form-field.shipping_service_field.form-field-last").css({'float': 'right', 'clear': 'right'});
    $("p.form-field._shipping_address_1_field").css({
      'width': '100%',
      'margin': '9px 0 0'
    });
  });
  
});
