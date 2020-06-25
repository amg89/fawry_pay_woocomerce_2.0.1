<?php

class wc_gateway_at_fawry_payment extends WC_Payment_Gateway {

    public function __construct() {
        global $woocommerce;
        $this->id = MY_FAW_PAYMENT_METHOD;
        //  $this->method_title =__( 'MyFawry','fawry_pay');
        $this->title = __('Fawry Pay', 'fawry_pay');
        $this->method_description = __('Fawry Pay Payment Gateway', 'fawry_pay');

        // $this->load_plugin_textdomain();
        $this->icon = MY_FAW_URL . '/images/logo.png';
        $this->has_fields = FALSE;
        if (is_admin()) {
            $this->has_fields = true;
            $this->init_form_fields();
        }


        $this->init_form_fields();
        $this->init_settings();

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'callback_handler'));
    }

    /**
     * Initialise Gateway Settings Form Fields
     *
     * @access public
     * @return void
     */
    function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'fawry_pay'),
                'type' => 'checkbox',
                'label' => __('Enable Fawry Pay gateway', 'fawry_pay'),
                'default' => 'yes'
            ),
            'description' => array(
                'title' => __('Description', 'fawry_pay'),
                'type' => 'text',
                'description' => __('أدفع عن طريق كارت الأئتمان او ماكينات الدفع الخاصة بفورى', 'fawry_pay'),
                'default' => __('Pay by Credit, Debit Card or through Fawry POS', 'fawry_pay')
            ),
            'merchant_identifier' => array(
                'title' => __('Merchant Identifier', 'fawry_pay'),
                'type' => 'text',
                'description' => __('Your Merchant Identifier', 'fawry_pay'),
                'default' => '',
                'desc_tip' => true,
                'placeholder' => ''
            ),
            'hash_code' => array(
                'title' => __('Hash Code', 'fawry_pay'),
                'type' => 'password',
                'description' => __('Your Hash Code ', 'fawry_pay') . '<br>' . __(' The Callback URL is  : ', 'fawry_pay')
                . '<strong>' . home_url() . '/wc-api/wc_gateway_at_fawry_payment</strong>'
                ,
                'default' => '',
                'desc_tip' => FALSE,
                'placeholder' => ''
            ),
            'is_staging' => array(
                'title' => __('Is Staging Environment', 'fawry_pay'),
                'type' => 'checkbox',
                'label' => __('Enable staging (Testing) Environment'),
                'default' => 'no'
            ),
            'unpaid_expire' => array(
                'title' => __('Unpaid Order Expiry(Hours)', 'fawry_pay'),
                'type' => 'number',
                'label' => __('Unpaid Order Expiration in hours(defualt is 48 hours)'),
                'default' => 'no'
            ),
            'order_complete_after_payment' => array(
                'label' => __('set order status to complete instead of processing after payment', 'fawry_pay'),
                'type' => 'checkbox',
                'title' => __('Complete Order after payment', 'fawry_pay'),
                'default' => 'no'
            ),
                'stupid_mode' => array(
                'label' => __('enable order calculations based only on total price (that includes taxes and shipping)', 'fawry_pay'),
                'type' => 'checkbox',
                'title' => __('Enable Stupid Mode', 'fawry_pay'),
                'default' => 'no'
            ),
        );
    }

    function process_payment($order_id) {
        global $woocommerce;
        $order = new WC_Order($order_id);

        $order->update_status('on-hold', __('Awaiting fawry payment Confirmation', 'fawry_pay'));

        // Reduce stock levels
        //this will enable stock timeout after the timeout the order is cancelled 
        //you can disable stock or change timeout in settings ->products->inventory
        $order->reduce_order_stock();

        // Remove cart
        $woocommerce->cart->empty_cart();

        // Return thankyou redirect
        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url($order)
        );
    }

    public function callback_handler() {
        //log the callback in the database
        global $wpdb;
        $res = $wpdb->replace(
                $wpdb->prefix . 'my_fawry_callback', array(
            'data_rec' => json_encode($_REQUEST)
                ), array(
            '%s',
                )
        );
  
		error_log('callback');
        // handle callback
        $options = get_option('woocommerce_' . MY_FAW_PAYMENT_METHOD . '_settings');

        $FawryRefNo = $_REQUEST['FawryRefNo']; //internal to fawry
        $MerchantRefNo = $_REQUEST['MerchantRefNo'];
        $OrderStatus = $_REQUEST['OrderStatus']; //New, PAID, CANCELED, DELIVERED, REFUNDED, EXPIRED
        $Amount = $_REQUEST['Amount'];
        $MessageSignature = $_REQUEST['MessageSignature'];
        
		error_log('server sign: '.$MessageSignature);
        $expected_signature = $this->generateSignature($FawryRefNo, $Amount, $MerchantRefNo, $OrderStatus);
		error_log('my sign: '.$expected_signature);
            $order = wc_get_order($MerchantRefNo);
			error_log ("order: ".$order);
        //echo $expected_signature;exit;
        //check signature
        if (strtoupper($expected_signature) === strtoupper($MessageSignature)) {
		error_log ("sign is the same");
            //get order
            //check amount and  order status PAID
            if ($Amount == $order->get_total() && $OrderStatus == 'PAID') {
                $order->payment_complete();
                if (trim($options['order_complete_after_payment']) === 'yes') {
                    $order->update_status('completed');
                }

                echo 'SUCCESS';
            } else {
                echo 'FAILD';
            }
        } else {
            echo 'INVALID_SIGNATURE';
        }
        exit;
    }

    private function generateSignature($fawryRefNo, $amount, $merchantRefNum, $orderStatus) {
        $options = get_option('woocommerce_' . MY_FAW_PAYMENT_METHOD . '_settings');

        $hashKey = trim($options['hash_code']);

        $buffer = $hashKey . $amount . $fawryRefNo . $merchantRefNum . $orderStatus ;
		
		error_log($buffer);
        return md5($buffer);
    }

}
