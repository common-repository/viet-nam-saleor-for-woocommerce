<?php

trait VNSFW_Ultility {
    /**
     * Implement WC States
     * @param mixed $states
     * @return mixed
     */
    public function  wc_states()
    {
        global $wpdb;
        $states = array();
        $provinces = array();
        
        $result = $wpdb->get_results('SELECT DISTINCT state_name, state_code FROM ' . $wpdb->prefix . 'vnsfw ORDER BY state_code');
        if (!empty($result)) {
            foreach ($result as $row) {
                $provinces[$row->state_code] = $row->state_name;                
            }            
        }

        // For vietnam only
        $states["VN"] = $provinces;

        return $states;
    }


    /**
     * Get Store allowed countries
     * @return mixed
     */
    public function get_store_allowed_countries()
    {
        return array_merge(WC()->countries->get_allowed_countries(), WC()->countries->get_shipping_countries());
    }


    /**
     * Get plugin root path
     * @return mixed
     */
    public function get_plugin_path()
    {
        if (isset($this->plugin_path)) {
            return $this->plugin_path;
        }
        $path = $this->plugin_path = plugin_dir_path(dirname(__FILE__));

        return untrailingslashit($path);
    }


    /**
     * Get country places
     * @return mixed
     */
    public function load_country_places()
    {
        global $places, $wpdb;

        $result = $wpdb->get_results('
            SELECT state_code, district_ward_code, district_ward_name FROM ' . $wpdb->prefix . 'vnsfw ORDER BY state_code',
            ARRAY_A
        );

        $vn_places = array();
        foreach($result as $val) {
            $vn_places[$val['state_code']][$val['district_ward_code']] = $val['district_ward_name'];
        }
        
        $places['VN'] = $vn_places;
        $this->places = $places;

    }

    /**
     * Get plugin url
     * @return mixed
     */
    public function get_plugin_url()
    {

        if (isset($this->plugin_url)) {
            return $this->plugin_url;
        }

        return $this->plugin_url = plugin_dir_url(dirname(__FILE__));
    }

    /**
     * Get places
     * @param string $p_code(default:)
     * @return mixed
     */
    public function get_places($p_code = null)
    {
        if (empty($this->places)) {
            self::load_country_places();
        }

        if (!is_null($p_code)) {
            return isset($this->places[$p_code]) ? $this->places[$p_code] : false;
        } else {
            return $this->places;
        }
    }

    /**
     * To Viettel Post state code
     * @param string $p_code(default:)
     * @return mixed INT
     */
    public function to_vtp_code($district_ward_code)
    {
        global $wpdb;

        $result = $wpdb->get_results('
            SELECT vtp_province_code, vtp_district_code, vtp_ward_code FROM ' . $wpdb->prefix . 'vnsfw WHERE district_ward_code=\'' . $district_ward_code . '\'',
            ARRAY_A
        );

        if (!empty($result)) {
            return $result[0];
        } else {
            return array();
        }

    }

    /**
     * To Viettel Post state code
     * @param string $p_code(default:)
     * @return mixed INT
     */
    public function get_vtp_token($vtp_user, $vtp_pass)
    {
        $body = array (
            "USERNAME"   => $vtp_user,
            "PASSWORD"   => $vtp_pass
        );

        $response_service = wp_remote_post( 'https://partner.viettelpost.vn'."/v2/user/Login", array(
            'method' => 'POST',
            'timeout' => 2000,
            'body'    => json_encode( $body ),
            'headers' => array( 'Content-Type' => 'application/json; charset=utf-8'),
            )
        );

        if ( is_wp_error( $response_service ) ) {
            $error_message = $response_service->get_error_message();
            echo "Something went wrong: $error_message";
            
        } else {
            $json_data = json_decode( $response_service['body'] );
            if ( !$json_data->error ) {
                return $json_data->data->token;
            } else {
                return '';
            }
        }
    }

    /**
     * To Viettel Post get store info
     * @param string $p_code(default:)
     * @return mixed INT
     */
    public function get_vtp_inventory($vtp_token)
    {
        $response_service = wp_remote_get( 'https://partner.viettelpost.vn'."/v2/user/listInventory", array(
            'method' => 'GET',
            'timeout' => 2000,
            'headers' => array(
                    'Content-Type' => 'application/json; charset=utf-8',
                    'Token'        => $vtp_token
                ),
            )
        );

        if ( is_wp_error( $response_service ) ) {
            $error_message = $response_service->get_error_message();
            echo "Something went wrong: $error_message";
            
        } else {
            $json_data = json_decode( $response_service['body'] );
            if ( !$json_data->error ) {
                return $json_data->data[0];
            } else {
                return array();
            }
        }
    }

    /**
     * Request Giao hang tiet kiem shipper pickup
     * @param string $p_code(default:)
     * @return mixed INT
     */
    public function ghtk_request_pickup($order, $shipping_settings){
        $is_ok = false;
        $order_total = $order->get_total();
        $shipping_total = $order->get_shipping_total();
        $products = array();

        $who_pay_shipping_fee = get_post_meta( $order->get_id(), 'pay_shipping_fee', true );

        if ($who_pay_shipping_fee == '1'){
            $COD = $order_total - $shipping_total;
        } else {
            $COD = $order_total;
        }

        $products    = array();
        $weight_unit = get_option('woocommerce_weight_unit'); 

        foreach( $order->get_items() as $item_id => $product_item ){
            $quantity       = $product_item->get_quantity(); // get quantity
            $product        = $product_item->get_product(); // get the WC_Product object
            $product_weight = $product->get_weight(); // get the product weight
            $base_price     = $product->get_price(); // get the product price
            $product_price  = intval(floatval( $base_price ) * $quantity);

            // Add the line item weight to the total weight calculation (kg)
            $total_weight += floatval( $product_weight ) * $quantity ;
            if ( $weight_unit == 'g' ) {
                $total_weight = $total_weight/1000;
            } else {
                $total_weight = $total_weight;
            }
            
            $products[] = array(
                'name'     => $product->get_name(),
                'weight'   => $total_weight,
                'quantity' => $quantity,
                'price'    => $product_price,
            );
        }

        $pick_state_name    = $this->wc_states()['VN'][$shipping_settings['sender_state']];
        $pick_states        = $this->get_places('VN')[$shipping_settings['sender_state']];
        $pick_district_ward = $pick_states[$shipping_settings['sender_city']];
        $pick_district_name = explode(" - ", $pick_district_ward)[0]; // District
        $pick_ward_name     = explode(" - ", $pick_district_ward)[1]; // Ward
        $token              = $shipping_settings['sender_token'];

        $recipient_state_name    = $this->wc_states()['VN'][$order->get_shipping_state()];
        $recipient_states        = $this->get_places('VN')[$order->get_shipping_state()];
        $recipient_district_ward = $recipient_states[$order->get_shipping_city()];
        $recipient_district_name = explode(" - ", $recipient_district_ward)[0]; // District
        $recipient_ward_name     = explode(" - ", $recipient_district_ward)[1]; // Ward

        $package_details = array(
            'products' => $products,
            'order'    => array (
                'id'            => $order->get_id(),
                'pick_name'     => $shipping_settings['sender_name'],
                'pick_address'  => $shipping_settings['sender_address'],
                'pick_province' => $pick_state_name,
                'pick_district' => $pick_district_name,
                'pick_ward'     => $pick_ward_name,
                'pick_tel'      => $shipping_settings['sender_phone'],
                'tel'           => get_post_meta( $order->get_id(), 'shipping_phone', true ),
                'name'          => $order->get_shipping_first_name(),
                'address'       => $order->get_shipping_address_1(),
                'province'      => $recipient_state_name,
                'district'      => $recipient_district_name,
                'ward'          => $recipient_ward_name,
                "hamlet"        => "Khác",
                "email"         => $order->get_billing_email(),
                'is_freeship'   => get_post_meta( $order->get_id(), 'pay_shipping_fee', true ),
                'pick_money'    => $COD,
                'note'          => $order->get_customer_note(),
                'use_return_address' => '0',
            )
        );
        
        $response_service = wp_remote_post( 'https://services.giaohangtietkiem.vn' . '/services/shipment/order', array(
            'method'  => 'POST',
            'timeout' => 5000,
            'body'    => json_encode( $package_details ),
            'headers' => array( 'Content-Type' => 'application/json; charset=utf-8', 'Token' => $token ),
            )
        );

        
        if ( is_wp_error( $response_service ) ) {
            $error_message = $response_service->get_error_message();
            echo "Lỗi: $error_message";
        } else {
            $success = json_decode( $response_service['body'] )->success;
            if ( $success ) {
                $result = json_decode( $response_service['body'] )->order;
                $message = sprintf( __( 'GHTK: Created tracking number: %s', 'vnsfw' ), $result->label );
                update_post_meta( $order->id, '_ghtk_code', $result->label );
                $order->add_order_note( $message );
                $is_ok = true;

            } else {
                $error = json_decode( $response_service['body'] )->message;
                $message = sprintf( __( 'GHTK: has an error: %s', 'vnsfw' ), $error );	            	
                $order->add_order_note($message);
            }
        }

        return $is_ok;

    }

    /**
     * Cancel request GHTK shipper pickup
     * @param string $p_code(default:)
     * @return mixed INT
     */
    public function ghtk_cancel_pickup($order, $shipping_settings){
        $token = $shipping_settings['sender_token'];
        $tracking_id = get_post_meta( $order->id, '_ghtk_code', true);
        $is_ok = false;

        if (isset($tracking_id)) {
            $response_service = wp_remote_post( 'https://services.giaohangtietkiem.vn' . '/services/shipment/cancel/' . $tracking_id, array(
                'method'  => 'POST',
                'timeout' => 5000,
                // 'body'    => json_encode('{}'),
                'headers' => array( 'Content-Type' => 'application/json', 'Token' => $token ),
                )
            );

            if ( is_wp_error( $response_service ) ) {
                $error_message = $response_service->get_error_message();
            } else {
                $success = json_decode( $response_service['body'] )->success;
                if ( $success ) {
                    $result = json_decode( $response_service['body'] )->message;
                    $message = sprintf( __( 'GHTK: %s', 'vnsfw' ), $result );
                    $order->add_order_note( $message );
                    $is_ok = true;

                } else {
                    $error = json_decode( $response_service['body'] );
                    $message = sprintf( __( 'GHTK: Cancel request has error: %s', 'vnsfw' ), $error );	            	
                    $order->add_order_note($message);
                }
            }
            // Update shipping_status
            
        } else {
            $message = sprintf( __( 'GHTK: This order doesn\'t have tracking id', 'vnsfw' ));
            $order->add_order_note($message);
        }

    }

    /**
     * Request Viettel Post shipper pickup
     * @param string $p_code(default:)
     * @return mixed INT
     */
    public function vtp_request_pickup($order, $shipping_settings)
    {
        $is_ok = false;
        $order_total = $order->get_total();
        $shipping_total = $order->get_shipping_total();
        $products = array();
        $product_details = '';
        $products_weight = 0;

        $who_pay_shipping_fee = get_post_meta( $order->get_id(), 'pay_shipping_fee', true );

        if ($who_pay_shipping_fee == '1'){
            $ORDER_PAYMENT = 3;
        } else {
            $ORDER_PAYMENT = 2;
        }

        $products    = array();
        $weight_unit = get_option('woocommerce_weight_unit'); 

        foreach( $order->get_items() as $item_id => $product_item ){
            $quantity       = $product_item->get_quantity(); // get quantity
            $product        = $product_item->get_product(); // get the WC_Product object
            $product_weight = $product->get_weight(); // get the product weight
            $base_price     = $product->get_price(); // get the product price
            $product_price  = intval(floatval( $base_price ) * $quantity);

            // Add the line item weight to the total weight calculation (kg)
            $total_weight += floatval( $product_weight ) * $quantity ;
            if ( $weight_unit == 'g' ) {
                $total_weight = $total_weight/1000;
            } else {
                $total_weight = $total_weight;
            }

            $products_weight = $products_weight + intval($total_weight);
            
            $products[] = array(
                'PRODUCT_NAME'     => $product->get_name(),
                'PRODUCT_WEIGHT'   => (int)$total_weight,
                'PRODUCT_QUANTITY' => $quantity,
                'PRODUCT_PRICE'    => $product_price
            );
            $product_details = $product_details . $product->get_name() . ' x ' . $quantity;
        }

        $sender_city_code  = $shipping_settings['sender_city'];
        $token              = $shipping_settings['sender_token'];
        $receiver_city_code = $order->get_shipping_city();

        $vtp_code_sender  = $this->to_vtp_code($sender_city_code);
        $vtp_code_receiver = $this->to_vtp_code($receiver_city_code);

        $package_details = array(
            'LIST_ITEM'           => $products,            
            'ORDER_NUMBER'        => $order->get_id(),
            "GROUPADDRESS_ID"     => $shipping_settings['sender_vpt_groupaddress_id'],
            "CUS_ID"              => $shipping_settings['sender_vpt_customer_id'],
            'SENDER_FULLNAME'     => $shipping_settings['sender_name'],
            'SENDER_ADDRESS'      => $shipping_settings['sender_address'],
            'SENDER_PROVINCE'     => (int)$vtp_code_sender['vtp_province_code'],
            'SENDER_DISTRICT'     => (int)$vtp_code_sender['vtp_district_code'],
            'SENDER_WARD'         => (int)$vtp_code_sender['vtp_ward_code'],
            'SENDER_PHONE'        => $shipping_settings['sender_phone'],
            // "SENDER_EMAIL" : "vanchinh.libra@gmail.com",
            "RECEIVER_FULLNAME"   => $order->get_shipping_first_name(),
            "RECEIVER_ADDRESS"    => $order->get_shipping_address_1(),
            "RECEIVER_PHONE"      => get_post_meta( $order->get_id(), 'shipping_phone', true ),
            "RECEIVER_EMAIL"      => $order->get_billing_email(),
            "RECEIVER_WARD"       => (int)$vtp_code_receiver['vtp_ward_code'],
            "RECEIVER_DISTRICT"   => (int)$vtp_code_receiver['vtp_district_code'],
            "RECEIVER_PROVINCE"   => (int)$vtp_code_receiver['vtp_province_code'],
            "PRODUCT_NAME"        => $product_details,
            "PRODUCT_DESCRIPTION" => $product_details,
            "PRODUCT_QUANTITY"    => 1,
            "ORDER_PAYMENT"       => $ORDER_PAYMENT,  //COD
            "PRODUCT_PRICE"       => (int)$order->get_subtotal(),
            "PRODUCT_WEIGHT"      => (int)$products_weight,
            "ORDER_SERVICE"       => "NCOD",
            "ORDER_SERVICE_ADD"   => "GTT",
            "PRODUCT_TYPE"        => "HH",       
        );

        $response_service = wp_remote_post( 'https://partner.viettelpost.vn' . '/v2/order/createOrder', array(
            'method'  => 'POST',
            'timeout' => 5000,
            'body'    => json_encode( $package_details ),
            'headers' => array( 'Content-Type' => 'application/json; charset=utf-8', 'Token' => $shipping_settings['sender_token'] ),
            )
        );

        
        if ( is_wp_error( $response_service ) ) {
            $error_message = $response_service->get_error_message();
            $message = sprintf( __( 'VTP: Error %s', 'vnsfw' ), $error_message );
            $order->add_order_note( $message );
        } else {
            $failed = json_decode( $response_service['body'] )->error;
            if ( !$failed ) {
                $result = json_decode( $response_service['body'] )->data;
                $message = sprintf( __( 'VTP: Create tracking number: %s', 'vnsfw' ), $result->ORDER_NUMBER );
                update_post_meta( $order->id, '_vtp_code', $result->ORDER_NUMBER );
                $order->add_order_note( $message );

                $is_ok = true;

            } else {
                $error = json_decode( $response_service['body'] )->message;
                $message = sprintf( __( 'VTP has an error: %s', 'vnsfw' ), $error );	            	
                $order->add_order_note($message);
            }
        }
        return $is_ok;
    }

    /**
     * Cancel request Viettel Post shipper pickup
     * @param string $p_code(default:)
     * @return mixed INT
     */
    public function vtp_cancel_pickup($order, $shipping_settings){
        $tracking_id = get_post_meta( $order->id, '_vtp_code', true);
        $is_ok = false;

        $body = array(            
            "TYPE"         => 4,
            "ORDER_NUMBER" => $tracking_id,
            "NOTE"         => "Hủy đơn"
        );

        $response_service = wp_remote_post( 'https://partner.viettelpost.vn' . '/v2/order/UpdateOrder', array(
            'method'  => 'POST',
            'timeout' => 5000,
            'body'    => json_encode( $body ),
            'headers' => array( 'Content-Type' => 'application/json; charset=utf-8', 'Token' => $shipping_settings['sender_token'] ),
            )
        );
        
        if ( is_wp_error( $response_service ) ) {
            $error_message = $response_service->get_error_message();
            $message = sprintf( __( 'VTP: Error: %s', 'vnsfw' ), $error_message );
            $order->add_order_note( $message );
        } else {
            $failed = json_decode( $response_service['body'] )->error;
            if ( !$failed ) {
                $result = json_decode( $response_service['body'] )->data;
                $message = sprintf( __( 'VTP: Canceled tracking number: %s', 'vnsfw' ), $tracking_id );
                $order->add_order_note( $message );
                $is_ok = true;
            } else {
                $error = json_decode( $response_service['body'] )->message;
                $message = sprintf( __( 'VTP: Error: %s', 'vnsfw' ), $error );	            	
                $order->add_order_note($message);
            }
        }
        return $is_ok;
    }

}
