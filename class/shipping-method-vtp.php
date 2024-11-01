<?php
if ( class_exists( 'WC_Shipping_Method' ) ) {
    class VNSFW_Shipping_Method_VTP extends WC_Shipping_Method {

        use VNSFW_Ultility;

        /**
         * Constructor for your shipping class
         *
         * @access public
         *
         * @return void
         */

        public function __construct($instance_id = 0) {
            $this->id                 = 'vnsfw_vtp';
            $this->instance_id        = absint( $instance_id );
            $this->method_title       = esc_html__( 'Viettel Post', 'vnsfw' );
            $this->method_description = esc_html__( 'Enable shipping service Viettel Post', 'vnsfw' );
            $this->supports           = array(
                'shipping-zones',
                'instance-settings',
                'instance-settings-modal',
            );
            // Availability & Countries
            $this->availability = 'including';
            $this->countries = array(
                'VN',
            );
            $this->init();
        }

        /**
         * Init your settings
         *
         * @access public
         * @return void
         */
        function init() {
            // Load the settings API
            $this->init_form_fields();
            $this->init_settings();

            $this->enabled = $this->get_option('enabled');
            $this->title = $this->get_option('title');
            
            $this->sender_state   = $this->get_option('sender_state');// $this->get_option( 'sender_state ' );
            $this->sender_city    = $this->get_option('sender_city');//$this->get_option( 'sender_city' );
            $this->sender_name    = $this->get_option('sender_name');//$this->get_option( 'sender_city' );
            $this->sender_phone   = $this->get_option('sender_phone');//$this->get_option( 'sender_city' );           
            $this->sender_token   = $this->get_option('sender_token');//$this->get_option( 'sender_token' );
            $this->sender_address = $this->get_option('sender_address');//$this->get_option( 'sender_token' );

            // Save settings in admin if you have any defined
            add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
        }

        /**
         * Checking is gateway enabled or not
         *
         * @return boolean [description]
         */
        public function is_method_enabled() {
            return $this->enabled =='yes';
        }

        /**
         * Initialise Gateway Settings Form Fields
         *
         * @access public
         * @return void
         */
        function init_form_fields() {            
            $settings = get_option( 'woocommerce_' . $this->id . '_' . $this->instance_id . '_settings' ); 
            $store_places = $this->get_places('VN');

            $this->instance_form_fields = array(
                'enabled' => array(
                    'title'   => esc_html__( 'Activate shipping service Viettel Post', 'vnsfw' ),
                    'type'    => 'checkbox',
                    'label'   => esc_html__( 'Enable', 'vnsfw' ),
                    'default' => 'no'
                ),
                'title' => array(
                    'title'       => esc_html__( 'Title', 'vnsfw' ),
                    'type'        => 'text',
                    'description' => esc_html__( 'Name of service to display for customer.', 'vnsfw' ),
                    'default'     => esc_html__( 'Viettel Post', 'vnsfw' ),
                    'desc_tip'    => true,
                ),
                'sender_name' => array(
                    'title'       => esc_html__( 'First name', 'vnsfw' ),
                    'type'        => 'text',
                    'description' => '',
                    'default'     => $settings['sender_name'],
                    'desc_tip'    => true,
                ),
                'sender_phone' => array(
                    'title'       => esc_html__( 'Contact phone', 'vnsfw' ),
                    'type'        => 'text',
                    'description' => '',
                    'default'     => $settings['sender_phone'],
                    'desc_tip'    => true,
                ),
                'sender_state' => array(
                    'title'       => esc_html__( 'Country / State', 'vnsfw' ),
                    'type'        => 'select',
                    'options'     => $this->wc_states()['VN'],
                    'description' => '',
                    'default'     => $settings['sender_state'],
                    'desc_tip'    => true,
                    'class'       => 'wc-enhanced-select',
                ),
                'sender_city' => array(
                    'title'       => esc_html__( 'City', 'vnsfw' ),
                    'type'        => 'select',
                    'options'     => $store_places[$settings['sender_state']],
                    'description' => '',
                    'default'     => $settings['city'],
                    'desc_tip'    => true,
                    'class'       => 'wc-enhanced-select',
                ),
                'sender_address' => array(
                    'title'       => esc_html__( 'Address 1', 'vnsfw' ),
                    'type'        => 'text',
                    'description' => '',
                    'default'     => $settings['sender_address'],
                    'desc_tip'    => true,
                ),
                'sender_vtp_user' => array(
                    'title'       => esc_html__( 'Viettel Post account', 'vnsfw' ),
                    'type'        => 'text',
                    'description' => 'Email or phone number of Viettel Post account',
                    'default'     => $settings['sender_vtp_user'],
                    'desc_tip'    => true,
                ),
                'sender_vtp_pass' => array(
                    'title'       => esc_html__( 'Viettel Post Password', 'vnsfw' ),
                    'type'        => 'password',
                    'description' => 'Password of Viettel Post account',
                    'default'     => $settings['sender_vtp_pass'],
                    'desc_tip'    => true,
                ),
                'sender_token' => array(
                    'title'             => esc_html__( 'API Token', 'vnsfw' ),
                    'type'              => 'text',
                    'description'       => 'Viettel Post API Token generated by user and password',
                    'default'           => $settings['sender_token'],
                    'desc_tip'          => true,
                    'custom_attributes' => array('readonly'=>'readonly')
                ),
            );
        }

        /**
         * calculate_shipping function.
         *
         * @access public
         *
         * @param mixed $package
         *
         * @return void
         */
        public function calculate_shipping( $package = array() ) {

            $settings = get_option( 'woocommerce_' . $this->id . '_' . $this->instance_id . '_settings' );

            $products       = $package['contents'];
            $FromCity       = $settings['sender_city'];
            $FromState      = $settings['sender_state'];
            $vtp_user       = $settings['sender_vtp_user'];
            $vtp_pass       = $settings['sender_vtp_pass'];
            $ToCity         = $package['destination']['city'];
            $ToState        = $package['destination']['state'];
            $amount         = 0.0;

            if (!isset($settings['sender_token']) || empty($settings['sender_token'])){
                $vtp_token = $this->get_vtp_token($vtp_user, $vtp_pass);
                $first_inventory = $this->get_vtp_inventory($vtp_token);
                $settings['sender_token'] = $vtp_token;
                $settings['sender_vpt_groupaddress_id'] = $first_inventory->groupaddressId;
                $settings['sender_vpt_customer_id'] = $first_inventory->cusId;
                update_option('woocommerce_' . $this->id . '_' . $this->instance_id . '_settings', $settings);
            } else {
                $vtp_token = $settings['sender_token'];
            }

            if ( ! $this->is_method_enabled() ) {
                return;
            }

            if ( $products ) {
                $amount = $this->calculate_shipping_fee( $products, $FromState, $FromCity, $ToCity, $ToState, $vtp_token );
            }
            if ( $amount ) {
                $rate = array(
                    'id'    => $this->id . $this->instance_id,
                    'label' => $this->title,
                    'cost'  => $amount,
                    'package' => $package,
                );

                // Register the rate
                $this->add_rate( $rate );
            }

        }

        /**
         * Check if shipping for this product is enabled
         *
         * @param integet $product_id
         *
         * @return boolean
         */
        public static function is_product_disable_shipping( $product_id ) {
            $enabled = get_post_meta( $product_id, '_disable_shipping', true );

            if ( $enabled == 'yes' ) {
                return true;
            }

            return false;
        }

        /**
         * Check if seller has any shipping enable product in this order
         *
         * @since  2.4.11
         *
         * @param  array $products
         *
         * @return boolean
         */
        public function has_shipping_enabled_product( $products ) {

            foreach ( $products as $product ) {
                if ( !self::is_product_disable_shipping( $product['product_id'] ) ) {
                    return true;
                }
            }

            return false;
        }

        /**
         * Calculate shipping per seller
         *
         * @param  array $products
         * @param  array $destination
         *
         * @return float
         */
        public function calculate_shipping_fee( $products, $FromState, $FromCity, $ToCity, $ToState, $token ) {
            $total_weight   = 0.0;
            $product_weight = 0.0;
            $amount         = 0.0;

            foreach ( $products as $product ) {
                $product_data = wc_get_product( $product['product_id'] )->get_data() ;
                $weight       = $product_data['weight'];
                if ( $product['quantity'] > 1 && $weight > 0 ) {
                    $product_weight = $weight * $product['quantity'];
                } else {
                    $product_weight = $weight;
                }
                $total_weight = $total_weight + $product_weight;
            }

            $weight_unit = get_option('woocommerce_weight_unit'); 

            if ( $weight_unit == 'g' ) {
                $total_weight = $total_weight;
            } else {
                $total_weight = $total_weight*1000;
            }

            $vtp_code_from = $this->to_vtp_code($FromCity);
            $vtp_code_to   = $this->to_vtp_code($ToCity);

            $service = array (
                "SENDER_PROVINCE"   => $vtp_code_from['vtp_province_code'],
                "SENDER_DISTRICT"   => $vtp_code_from['vtp_district_code'],
                "RECEIVER_PROVINCE" => $vtp_code_to['vtp_province_code'],
                "RECEIVER_DISTRICT" => $vtp_code_to['vtp_district_code'],
                "PRODUCT_WEIGHT"    => intval($total_weight),
                "ORDER_SERVICE"     => "NCOD",
                "PRODUCT_TYPE"      => "HH",
                "ORDER_SERVICE_ADD" => "GTT",
                "NATIONAL_TYPE"     => 1
            );

            $response_service = wp_remote_post( 'https://partner.viettelpost.vn'."/v2/order/getPrice", array(
                'method' => 'POST',
                'timeout' => 5000,
				'body'    => json_encode( $service ),
				'headers' => array( 'Content-Type' => 'application/json; charset=utf-8', 'Token' => $token ),
                )
            );

            if ( is_wp_error( $response_service ) ) {
                $error_message = $response_service->get_error_message();
                echo "Something went wrong: $error_message";
                
            } else {
                $json_data = json_decode( $response_service['body'] );
                if ( !$json_data->error ) {
                    $amount = floatval($json_data->data->MONEY_TOTAL);
                }
            }
            return $amount;
        }
    }
}