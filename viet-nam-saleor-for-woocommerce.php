<?php

/**
 * Plugin Name: Viet Nam saleor for WooCommerce
 * Plugin URI: https://github.com/nvtienanh/viet-nam-saleor-for-woocommerce
 * Description: Plugin này thay đổi thông tin địa chỉ cho phù hợp với đơn vị hành chính tại Việt Nam, tích hợp tính năng giao hàng của các dịch vụ vận chuyển trong nước
 * Version: 1.2.3
 * Author: Anh Nguyễn
 * Author URI: https://nvtienanh.info
 * Developer: Anh Nguyễn
 * Developer URI: https://github.com/nvtienanh
 * Contributors: nvtienanh
 * Text Domain: vnsfw
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 4.0
 * Tested up to: 5.4
 * WC requires at least: 3.0.x
 * WC tested up to: 4.5.2
 */

/**
 * Die if accessed directly
 */
defined('ABSPATH') or die(__('You can not access this file directly!', 'vnsfw'));

/**
 * Check if WooCommerce is active
 */
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

    include_once plugin_dir_path(__FILE__) . '/class/ultility.php';
    class Viet_Nam_Saleor_WC 
    {
        use VNSFW_Ultility;

        const VERSION = '1.2.3';
        private $states;
        private $places;

        /**
         * Construct class
         */
        public function __construct()
        {            
            add_action('plugins_loaded', array($this, 'init'));            
        }

        /**
         * WC init
         */
        public function init()
        {
            $this->init_textdomain();
            $this->init_fields();
            $this->init_states();
            $this->init_places();
            $this->init_vnsettings();
            $this->init_shipping();
            $this->init_admin_shipping();
        }

        /**
         * Load text domain for internationalitation         
         */
        public function init_textdomain()
        {
            load_plugin_textdomain('vnsfw', FALSE, basename( dirname( __FILE__ ) ) . '/languages');
        }

        /**
         * WC Fields init
         */
        public function init_fields()
        {
            add_filter('woocommerce_default_address_fields', array($this, 'wc_change_state_and_city_order'), 10, 1);
            add_filter( 'woocommerce_checkout_fields', array( $this, 'wc_checkout_fields' ), 10, 1 );
        }

        /**
         * WC States init
         */
        public function init_states()
        {
            add_filter('woocommerce_states', array($this, 'wc_states'));
        }

        /**
         * VN Setting
         */
        public function init_vnsettings()
        {
            add_filter( 'woocommerce_get_country_locale_base', array($this, 'wc_get_country_locale_base'), 10, 1 );
        }

        /**
         * WC Places init
         */
        public function init_places()
        {
            add_filter('woocommerce_billing_fields', array($this, 'wc_billing_fields'), 10, 2);
            add_filter('woocommerce_shipping_fields', array($this, 'wc_shipping_fields'), 10, 2);
            add_filter('woocommerce_form_field_city', array($this, 'wc_form_field_city'), 10, 4);

            // Re-format address
            add_filter( 'woocommerce_localisation_address_formats', array( $this, 'wc_localisation_address_formats' ), 20, 1 );
            add_filter( 'woocommerce_formatted_address_replacements', array( $this, 'wc_formatted_address_replacements' ), 20, 2 );
            
            // Gõ bỏ các fields không sử dụng.
			add_filter( 'woocommerce_order_formatted_billing_address', array( $this, 'wc_order_formatted_billing_address' ), 10, 2 );
            add_filter( 'woocommerce_order_formatted_shipping_address', array( $this, 'wc_order_formatted_shipping_address' ), 10, 2 );
            
            add_filter('woocommerce_admin_billing_fields', array($this, 'admin_billing_city_select_field'), 10, 1);
            add_filter('woocommerce_admin_shipping_fields', array($this, 'admin_shipping_city_select_field'), 10, 1);
            add_filter('woocommerce_get_settings_general', array($this, 'custom_woocommerce_get_settings_general'), 10, 1 );

            add_action('wp_enqueue_scripts', array($this, 'load_scripts'));
            add_action('admin_enqueue_scripts', array($this, 'load_admin_scripts'));

            add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'wc_admin_order_data_after_shipping_address'), 10, 1);
            add_action( 'woocommerce_process_shop_order_meta', array($this, 'wc_process_shop_order_meta'), 10, 1);
        }

        function wc_checkout_fields( $fields ) {           

            $fields['billing']['billing_first_name']['label'] = __( 'First name', 'vnsfw' );
            $fields['shipping']['shipping_first_name']['label'] = __( 'First name', 'vnsfw' );

            $fields['billing']['billing_state']['label'] = __( 'Country / State', 'vnsfw' );
            $fields['shipping']['shipping_state']['label'] = __( 'Country / State', 'vnsfw' );

            $fields['billing']['billing_city']['label'] = __( 'City', 'vnsfw' );
            $fields['shipping']['shipping_city']['label'] = __( 'City', 'vnsfw' );

            // Billing fields
            unset( $fields['billing']['billing_company'] );   // remove company field
            unset( $fields['billing']['billing_address_2'] ); // remove address_2 field
            unset( $fields['billing']['billing_postcode'] );  // remove zip code field
            unset( $fields['billing']['billing_last_name'] ); // remove last_name field
            
            // Shipping fields
            unset( $fields['shipping']['shipping_company'] );   // remove company field
            unset( $fields['shipping']['shipping_address_2'] ); // remove address_2 field
            unset( $fields['shipping']['shipping_postcode'] );  // remove zip code field
            unset( $fields['shipping']['shipping_last_name'] ); // remove last_name field

            return $fields;

        }

        function wc_get_country_locale_base( $fields ) {
            $fields['state']['required'] = true;

			return $fields;
        }
        
        function wc_admin_order_data_after_shipping_address( $order ){ 
            // $shipping_date  = !empty( get_post_meta( $order->get_id(), 'shipping_date', true ) ) ? $shipping_date : date('Y-m-d');
            $shipping_phone = !empty( get_post_meta( $order->get_id(), 'shipping_phone', true ) ) ? $shipping_phone : $order->get_billing_phone();
         
            ?>
            <div class="address">
                <p<?php if( empty($shipping_phone) ) echo ' class="none_set"' ?>>
                    <strong> <?php esc_html__( 'Contact phone:', 'vnsfw' )?></strong>
                    <?php  $shipping_phone  ?>
                </p>
            </div>
            <div class="edit_address"><?php
                woocommerce_wp_text_input( array( 
                    'id'            => 'shipping_phone',
                    'label'         => __('Contact phone', 'vnsfw' ),
                    'wrapper_class' => 'form-field-first',
                    'value'         => $shipping_phone,
                    // 'style' => 'float:left',
                    // 'description' => 'This is the day, when the customer would like to receive his order.'
                ) );

                woocommerce_wp_select( array(
                    'id'      => 'shipping_service',
                    'label'   => __( 'Shipping service:', 'vnsfw' ),
                    'value'   => $gift_wrap,
                    'options' => array(
                        ''     => __( 'Please select', 'vnsfw' ),
                        'ghtk' => __( 'Giao hang tiet kiem', 'vnsfw' ),
                        'vtp'  => __( 'Viettel Post', 'vnsfw' )
                    ),
                    'wrapper_class' => 'form-field-last'
                ) );

                woocommerce_wp_radio( array(
                    'id' => 'pay_shipping_fee',
                    'label' => __( 'Who will pay shipping fee?', 'vnsfw' ),
                    'value' => '1',
                    'options' => array(
                        '0' => __( 'Customer', 'vnsfw' ),
                        '1' => __( 'Shop', 'vnsfw' )
                    ),
                    'style' => 'width:16px', // required for checkboxes and radio buttons
                    'wrapper_class' => 'form-field-first' // always add this class
                ) );

                

                // woocommerce_wp_text_input( array( 
                //     'id' => 'cod_value',
                //     'label' => 'COD value', 
                //     'wrapper_class' => 'form-field-last',
                //     'value' => $shipping_phone,
                //     // 'style' => 'float:left',
                //     // 'description' => 'This is the day, when the customer would like to receive his order.'
                // ) );

                

            ?></div><?php
        }

        function wc_process_shop_order_meta( $order_id ){
            update_post_meta( $order_id, 'shipping_service', wc_clean( $_POST[ 'shipping_service' ] ) );
            update_post_meta( $order_id, 'pay_shipping_fee', wc_clean( $_POST[ 'pay_shipping_fee' ] ) );
            update_post_meta( $order_id, 'shipping_phone', wc_clean( $_POST[ 'shipping_phone' ] ) );
        }

        function wc_order_formatted_billing_address( $address, $order ) {
			unset($address['address_2']);
            unset($address['postcode']);
            unset($address['last_name']);

			return $address;
		}

		function wc_order_formatted_shipping_address( $address, $order ) {
			unset($address['address_2']);
            unset($address['postcode']);
            unset($address['last_name']);

			return $address;
		}

        function wc_formatted_address_replacements( $array, $args ) {
            // Get address code
            $city_code    = $args['city'];
            $state_code   = $args['state'];
            $country_code = $args['country'];
            
            // Get address name by code
            $places = $this->get_places($country_code);
            $city_name = $places[$state_code][$city_code];
            $state_name = $this->wc_states()[$country_code][$state_code];

            $district_name = explode(" - ", $city_name)[0];
            $ward_name = explode(" - ", $city_name)[1];

            // Replace add code -> name
			if ( isset( $city_name ) ) {
				$array['{city}'] = $ward_name . ' - ' . $district_name;
            }            
            if ( isset( $state_name ) ) {
				$array['{state}'] = $state_name;
            }

			return $array;
		}

        function wc_localisation_address_formats( $array ) {
			$array['VN'] = "{first_name}\n{address_1}\n{city} - {state}";

			return $array;
		}

        /**
         * WC Vietnam shipping init
         */
        public function init_shipping()
        {
            add_action( 'woocommerce_shipping_init', array( $this, 'init_vnsfw_shipping_method' ) );
            add_action( 'woocommerce_shipping_methods', array( $this, 'register_shipping_methods' ) );
            add_filter( 'woocommerce_cart_shipping_packages', array( $this, 'add_shipping_packages' ) );
        }

        function init_vnsfw_shipping_method() {
            require_once plugin_dir_path(__FILE__) .'/class/shipping-method-ghtk.php';
            require_once plugin_dir_path(__FILE__) .'/class/shipping-method-vtp.php';
        }

        /**
         * WC Vietnam shipping init
         */
        public function init_admin_shipping()
        {
            add_action( 'woocommerce_order_actions', array( $this, 'add_order_meta_box_actions' ) );

            // Order action: Request shipper pickup my package at store
            add_action( 'woocommerce_order_action_request_shipper_pickup', array( $this, 'wc_order_action_request_shipper_pickup' ) );
            // Order action: Request cancel pickup my package
            add_action( 'woocommerce_order_action_request_cancel_pickup', array( $this, 'wc_order_action_request_cancel_pickup' ) );
        }

        /**
         * Add a custom action to order actions select box on edit order page
         * Only added for paid orders that haven't fired this action yet
         *
         * @param array $actions order actions array to display
         * @return array - updated actions
         */
        function add_order_meta_box_actions( $actions ) {
            $actions['request_shipper_pickup'] = __( 'Create tracking number', 'vnsfw' );
            $actions['request_cancel_pickup'] = __( 'Cancel tracking number', 'vnsfw' );
            return $actions;
        }

        /**
         * Add an order note when custom action is clicked
         * Add a flag on the order to show it's been run
         *
         * @param \WC_Order $order
         */
        function wc_order_action_request_shipper_pickup( $order ) {

            foreach( $order->get_items( 'shipping' ) as $item_id => $shipping_item_obj ){
                $shipping_method_id          = $shipping_item_obj->get_method_id(); // The method ID
                $shipping_method_instance_id = $shipping_item_obj->get_instance_id(); // The method instance ID
                
            }
            switch ((string)$shipping_method_id) {
                case 'vnsfw_ghtk':
                    $shipping_settings = get_option( 'woocommerce_vnsfw_ghtk_' . $shipping_method_instance_id . '_settings' );
                    $is_ok = $this->ghtk_request_pickup($order, $shipping_settings);
                    break;
                case 'vnsfw_vtp':
                    $shipping_settings = get_option( 'woocommerce_vnsfw_vtp_' . $shipping_method_instance_id . '_settings' );
                    $is_ok = $this->vtp_request_pickup($order, $shipping_settings);
                    break;
                default:
                    $shipping_settings = NULL;
                    $is_ok = false;
                    break;
            }

            if ($is_ok) {
                // Update shipping_status
                update_post_meta( $order->id, '_wc_shipping_status', 'requested_pickup' );
            }
            
        }

        /**
         * Add an order note when custom action is clicked
         * Add a flag on the order to show it's been run
         *
         * @param \WC_Order $order
         */
        function wc_order_action_request_cancel_pickup( $order ) {
            foreach( $order->get_items( 'shipping' ) as $item_id => $shipping_item_obj ){
                $shipping_method_id          = $shipping_item_obj->get_method_id(); // The method ID
                $shipping_method_instance_id = $shipping_item_obj->get_instance_id(); // The method instance ID
                
            }
            switch ((string)$shipping_method_id) {
                case 'vnsfw_ghtk':
                    $shipping_settings = get_option( 'woocommerce_vnsfw_ghtk_' . $shipping_method_instance_id . '_settings' );
                    $is_ok = $this->ghtk_cancel_pickup($order, $shipping_settings);
                    break;
                case 'vnsfw_vtp':
                    $shipping_settings = get_option( 'woocommerce_vnsfw_vtp_' . $shipping_method_instance_id . '_settings' );
                    $is_ok = $this->vtp_cancel_pickup($order, $shipping_settings);
                    break;
                default:
                    $shipping_settings = NULL;
                    $is_ok = false;
                    break;
            }

            if ($is_ok) {
                // Update shipping_status
                update_post_meta( $order->id, '_wc_shipping_status', 'canceled_pickup' );
            }            
           
        }

        /**
         * Register shipping methods
         */
        function register_shipping_methods( $methods ) {
            $methods['vnsfw_ghtk'] = 'VNSFW_Shipping_Method_GHTK';
            $methods['vnsfw_vtp'] = 'VNSFW_Shipping_Method_VTP';
            return $methods;
        }

        /**
         * Add shipping packages
         */
        function add_shipping_packages( $packages ) {

            $city  = WC()->customer->get_shipping_city();
            $state = WC()->customer->get_shipping_state();

            $packages[0]['destination']['country'] = 'VN';

            if ( $state ) {
                $packages[0]['destination']['state'] = $state;
            } else {
                $packages[0]['destination']['state'] = get_user_meta( get_current_user_id(), 'billing_state', true );
            }

            if ( $city ) {
                $packages[0]['destination']['city'] = $city;
            } else {
                $packages[0]['destination']['city'] = get_user_meta( get_current_user_id(), 'billing_city', true );
            }

            return $packages;
        }

        function custom_woocommerce_get_settings_general($settings)
        {
            $key = 0;
            foreach( $settings as $values ){
                // Inserting array just after the post code in "Store Address" section
                if($values['id'] == 'woocommerce_store_city'){
                    $country_code = WC()->countries->get_base_country();
                    $store_places = $this->get_places($country_code);

                    // if country have place list
                    if (is_array($store_places)){
                        $current_sc = WC()->countries->get_base_state();
                        $current_city =  WC()->countries->get_base_city();
                        if ($current_sc && array_key_exists($current_sc, $store_places)) {                        
                            $dropdown_places = $store_places[$current_sc];
                            $values['type'] = 'select';
                            $values['options'] = $dropdown_places;
                            // $values['default'] = $current_city;
                            $values['class'] = 'wc-enhanced-select';
                        } else if (is_array($store_places) && isset($store_places[0])) {
                            $dropdown_places = $store_places;
                            sort($dropdown_places);
                            $values['type'] = 'select';
                            // $values['default'] = $current_city;
                            $values['options'] = $dropdown_places;
                            $values['class'] = 'wc-enhanced-select';
                        } else {
                            $dropdown_places = $store_places;
                            $values['type'] = 'select';
                            // $values['default'] = $current_city;
                            $values['options'] = $dropdown_places;
                            $values['class'] = 'wc-enhanced-select';
                        }
                    }
                    $new_settings[$key] = $values;
                    $key++;
                } 
                elseif ($values['id'] == 'woocommerce_default_country') {
                    $country_code = WC()->countries->get_base_country();
                    $current_sc = WC()->countries->get_base_state();
                    $values['default'] = $country_code . ':' . $current_sc;
                    $new_settings[$key] = $values;
                    $key++;
                }
                else {
                    $new_settings[$key] = $values;
                    $key++;
                }
            }

            return $new_settings;
        }

        function admin_billing_city_select_field($fields)
        {
            // Get the action (Edit or create new Order)
            $screen_action = get_current_screen()->action;

            // Drop down for city in Order edit
            if (empty($screen_action)) // Update mode
            {
                $order = wc_get_order(get_the_ID());
                $billing_country = $order->get_billing_country();
                $billing_state = $order->get_billing_state();
                $billing_city = $order->get_billing_city();
                
                $store_places = $this->get_places($billing_country);
                $fields['city']['type'] = 'select';
                $fields['city']['options'] = $store_places[$billing_state];
                $fields['city']['class'] = 'wc-enhanced-select';
                $fields['city']['default'] = $billing_city;

            } else { //Create mode do nothing fornow

            }

            // Remove unuse fields
            unset($fields['address_2']);
            unset($fields['postcode']);
            unset($fields['company']);
            unset($fields['last_name']);

            // Reorder
            $order = array(
                "first_name",                
                "country",
                "state",
                "city",                             
                "address_1",  
                "email",                
                "phone",              
            );
            foreach($order as $field) {
                $ordered_fields[$field] = $fields[$field];        
            }
            $fields = $ordered_fields;

            return $fields;
        }

        function admin_shipping_city_select_field($fields)
        {
            // Get the action (Edit or create new Order)
            $screen_action = get_current_screen()->action;

             // Drop down for city in Order edit
             if (empty($screen_action)) // Update mode
            {
                $order = wc_get_order(get_the_ID());
                $shipping_country = $order->get_shipping_country();
                $shipping_state = $order->get_shipping_state();
                $shipping_city = $order->get_shipping_city();
                
                $store_places = $this->get_places($shipping_country);
                $fields['city']['type'] = 'select';
                $fields['city']['options'] = $store_places[$shipping_state];
                $fields['city']['class'] = 'wc-enhanced-select';
                $fields['city']['default'] = $shipping_city;

            } else { //Create mode

            }

            // Remove unuse fields
            unset($fields['address_2']);
            unset($fields['postcode']);
            unset($fields['company']);
            unset($fields['last_name']);

            // Reorder
            $order = array(
                "first_name",
                "country",
                "state",
                "city",                             
                "address_1",                
            );
            // var_dump($fields);
            foreach($order as $field) {
                $ordered_fields[$field] = $fields[$field];        
            }
            $fields = $ordered_fields;

            return $fields;
        }

        /**
         * Change the order of State and City fields to have more sense with the steps of form
         * @param mixed $fields
         * @return mixed
         */
        public function wc_change_state_and_city_order($fields)
        {
            $fields['first_name']['class'] = array('form-row-wide');
            $fields['state']['label'] = __( 'Country / State', 'vnsfw' );
            $fields['city']['label'] = __( 'City', 'vnsfw' );

            // Reorder
            $fields['first_name']['priority'] = 10;	
			$fields['email']['priority'] = 20; 	
			$fields['phone']['priority'] = 30; 	
			$fields['country']['priority'] = 40; 	
			$fields['state']['priority'] = 50; 	
			$fields['city']['priority'] = 60; 	
			$fields['address_1']['priority'] = 70; 	

            return $fields;
        }


        /**
         * Modify billing field
         * @param mixed $fields
         * @param mixed $country
         * @return mixed
         */
        public function wc_billing_fields($fields, $country)
        {
            $fields['billing_city']['type'] = 'city';
            $fields['billing_state']['required'] = true;
            $fields['billing_email']['required'] = false;

            return $fields;
        }

        /**
         * Modify shipping field
         * @param mixed $fields
         * @param mixed $country
         * @return mixed
         */
        public function wc_shipping_fields($fields, $country)
        {
            $fields['shipping_city']['type'] = 'city';
            $fields['shipping_state']['required'] = true;

            unset($fields['shipping_email']);
            unset($fields['shipping_company']);
            unset($fields['shipping_phone']);

            return $fields;
        }

        /**
         * Implement places/city field
         * @param mixed $field
         * @param string $key
         * @param mixed $args
         * @param string $value
         * @return mixed
         */
        public function wc_form_field_city($field, $key, $args, $value)
        {
            // Do we need a clear div?
            if ((!empty($args['clear']))) {
                $after = '<div class="clear"></div>';
            } else {
                $after = '';
            }

            // Required markup
            if ($args['required']) {
                $args['class'][] = 'validate-required';
                $required = ' <abbr class="required" title="' . esc_attr__('required', 'woocommerce') . '">*</abbr>';
            } else {
                $required = '';
            }

            // Custom attribute handling
            $custom_attributes = array();

            if (!empty($args['custom_attributes']) && is_array($args['custom_attributes'])) {
                foreach ($args['custom_attributes'] as $attribute => $attribute_value) {
                    $custom_attributes[] = esc_attr($attribute) . '="' . esc_attr($attribute_value) . '"';
                }
            }

            // Validate classes
            if (!empty($args['validate'])) {
                foreach ($args['validate'] as $validate) {
                    $args['class'][] = 'validate-' . $validate;
                }
            }

            // field p and label
            $field  = '<p class="form-row ' . esc_attr(implode(' ', $args['class'])) . '" id="' . esc_attr($args['id']) . '_field">';
            if ($args['label']) {
                $field .= '<label for="' . esc_attr($args['id']) . '" class="' . esc_attr(implode(' ', $args['label_class'])) . '">' . $args['label'] . $required . '</label>';
            }

            // Get Country
            $country_key = $key == 'billing_city' ? 'billing_country' : 'shipping_country';
            $current_cc  = WC()->checkout->get_value($country_key);

            $state_key = $key == 'billing_city' ? 'billing_state' : 'shipping_state';
            $current_sc  = WC()->checkout->get_value($state_key);

            // Get country places
            $places = $this->get_places($current_cc);

            if (is_array($places)) {

                $field .= '<select name="' . esc_attr($key) . '" id="' . esc_attr($args['id']) . '" class="city_select ' . esc_attr(implode(' ', $args['input_class'])) . '" ' . implode(' ', $custom_attributes) . ' placeholder="' . esc_attr($args['placeholder']) . '">';

                $field .= '<option value="">' . __('Select an option&hellip;', 'woocommerce') . '</option>';

                if ($current_sc && array_key_exists($current_sc, $places)) {
                    $dropdown_places = $places[$current_sc];
                } else if (is_array($places) && isset($places[0])) {
                    $dropdown_places = $places;
                    sort($dropdown_places);
                } else {
                    $dropdown_places = $places;
                }

                foreach ($dropdown_places as $idx=>$city_name) {
                    if (!is_array($city_name)) {
                        $field .= '<option value="' . esc_attr($idx) . '" ' . selected($value, $idx, false) . '>' . $city_name . '</option>';
                    }
                }

                $field .= '</select>';
            } else {

                $field .= '<input type="text" class="input-text ' . esc_attr(implode(' ', $args['input_class'])) . '" value="' . esc_attr($value) . '"  placeholder="' . esc_attr($args['placeholder']) . '" name="' . esc_attr($key) . '" id="' . esc_attr($args['id']) . '" ' . implode(' ', $custom_attributes) . ' />';
            }

            // field description and close wrapper
            if ($args['description']) {
                $field .= '<span class="description">' . esc_attr($args['description']) . '</span>';
            }

            $field .= '</p>' . $after;

            return $field;
        }

        /**
         * Load scripts
         */
        public function load_scripts()
        {
            if (is_cart() || is_checkout() || is_wc_endpoint_url('edit-address')) {

                $city_select_path = $this->get_plugin_url() . 'assets/js/place-select.js';
                wp_enqueue_script('wc-city-select', $city_select_path, array('jquery', 'woocommerce'), self::VERSION, true);

                $places = json_encode($this->get_places());
                wp_localize_script('wc-city-select', 'wc_city_select_params', array(
                    'cities' => $places,
                    'i18n_select_city_text'     => esc_attr__('Select an option&hellip;', 'vnsfw'),
                ));
            }
        }

        /**
         * Load admin scripts
         */
        public function load_admin_scripts()
        {
            $screen       = get_current_screen();
            $screen_id    = $screen ? $screen->id : '';
            
            $city_select_path = $this->get_plugin_url() . 'assets/js/admin-place-select.js';

            if ( $screen_id == 'woocommerce_page_wc-settings' || $screen_id == 'shop_order' ) {
                wp_enqueue_script('wc-admin-city-select', $city_select_path, array('jquery'), self::VERSION, true);

                $places = json_encode($this->get_places());
                wp_localize_script('wc-admin-city-select', 'wc_admin_city_select_params', array(
                    'cities'                    => $places,
                    'i18n_select_city_text'     => esc_attr__('Select an option&hellip;', 'vnsfw'),
					'i18n_no_matches'           => _x( 'No matches found', 'enhanced select', 'vnsfw' ),
					'i18n_ajax_error'           => _x( 'Loading failed', 'enhanced select', 'vnsfw' ),
					'i18n_input_too_short_1'    => _x( 'Please enter 1 or more characters', 'enhanced select', 'vnsfw' ),
					'i18n_input_too_short_n'    => _x( 'Please enter %qty% or more characters', 'enhanced select', 'vnsfw' ),
					'i18n_input_too_long_1'     => _x( 'Please delete 1 character', 'enhanced select', 'vnsfw' ),
					'i18n_input_too_long_n'     => _x( 'Please delete %qty% characters', 'enhanced select', 'vnsfw' ),
					'i18n_selection_too_long_1' => _x( 'You can only select 1 item', 'enhanced select', 'vnsfw' ),
					'i18n_selection_too_long_n' => _x( 'You can only select %qty% items', 'enhanced select', 'vnsfw' ),
					'i18n_load_more'            => _x( 'Loading more results&hellip;', 'enhanced select', 'vnsfw' ),
					'i18n_searching'            => _x( 'Searching&hellip;', 'enhanced select', 'vnsfw' ),
                ));
            }
        }

        public static function activate()
        {
            global $wpdb;
            $table_name = $wpdb->prefix . 'vnsfw';
            $vnsfw_db_version = '1.0.0';
            $charset_collate = $wpdb->get_charset_collate();

            if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) != $table_name ) {
                $sql = "CREATE TABLE $table_name (
                            id mediumint(9) NOT NULL AUTO_INCREMENT,
                            state_name text NOT NULL,
                            state_code varchar(4) NOT NULL,
                            district_ward_name text NOT NULL,
                            district_ward_code varchar(7) NOT NULL,
                            vtp_province_code smallint NOT NULL,
                            vtp_district_code smallint NOT NULL,
                            vtp_ward_code smallint NOT NULL,
                            PRIMARY KEY  (id)
                            ) $charset_collate;";

                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                dbDelta( $sql );
                add_option( 'vnsfw_db_version', $vnsfw_db_version );
            }

            $VN_1 = include plugin_dir_path(__FILE__) . '/assets/wp_vnsfw_1.php';
            self::wp_insert_rows($VN_1, $table_name);
            $VN_2 = include plugin_dir_path(__FILE__) . '/assets/wp_vnsfw_2.php';
            self::wp_insert_rows($VN_2, $table_name);
        }

        public static function deactivate() {
            global $wpdb;
            $table_name = $wpdb->prefix . 'vnsfw'; 

            $sql_delete_table = "DROP TABLE IF EXISTS $table_name";
            $sql_delete_options = 'DELETE FROM' . $wpdb->prefix . 'options WHERE option_name LIKE \'woocommerce_vnsfw_%\'';

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            $wpdb->query( $sql_delete_table );
            $wpdb->query( $sql_delete_options );
            delete_option('vnsfw_db_version'); 
        }

        /**
         * Insert rows
         * @param string $p_code(default:)
         * @return mixed INT
         */

        public static function wp_insert_rows($row_arrays = array(), $wp_table_name, $update = false, $primary_key = null) {
            global $wpdb;
            $wp_table_name = esc_sql($wp_table_name);
            // Setup arrays for Actual Values, and Placeholders
            $values        = array();
            $place_holders = array();
            $query         = "";
            $query_columns = "";
            
            $query .= "INSERT INTO `{$wp_table_name}` (";
            foreach ($row_arrays as $count => $row_array) {
                foreach ($row_array as $key => $value) {
                    if ($count == 0) {
                        if ($query_columns) {
                            $query_columns .= ", " . $key . "";
                        } else {
                            $query_columns .= "" . $key . "";
                        }
                    }
                    
                    $values[] = $value;
                    
                    $symbol = "%s";
                    if (is_numeric($value)) {
                        if (is_float($value)) {
                            $symbol = "%f";
                        } else {
                            $symbol = "%d";
                        }
                    }
                    if (isset($place_holders[$count])) {
                        $place_holders[$count] .= ", '$symbol'";
                    } else {
                        $place_holders[$count] = "( '$symbol'";
                    }
                }
                // mind closing the GAP
                $place_holders[$count] .= ")";
            }
            
            $query .= " $query_columns ) VALUES ";
            
            $query .= implode(', ', $place_holders);
            
            if ($update) {
                $update = " ON DUPLICATE KEY UPDATE $primary_key=VALUES( $primary_key ),";
                $cnt    = 0;
                foreach ($row_arrays[0] as $key => $value) {
                    if ($cnt == 0) {
                        $update .= "$key=VALUES($key)";
                        $cnt = 1;
                    } else {
                        $update .= ", $key=VALUES($key)";
                    }
                }
                $query .= $update;
            }
            
            $sql = $wpdb->prepare($query, $values);
            if ($wpdb->query($sql)) {
                return true;
            } else {
                return false;
            }
        }


    }

    function activate_vnsfw_plugin() {
        Viet_Nam_Saleor_WC::activate();
    }
     
    function deactivate_vnsfw_plugin() {
        Viet_Nam_Saleor_WC::deactivate();
    }

    register_activation_hook( __FILE__, 'activate_vnsfw_plugin' );
    register_deactivation_hook( __FILE__, 'deactivate_vnsfw_plugin' );
    /**
     * Instantiate class
     */
    $GLOBALS['viet_nam_saleor_wc'] = new Viet_Nam_Saleor_WC();
};
