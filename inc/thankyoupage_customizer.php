<?php

add_filter('woocommerce_thankyou_order_received_text', 'my_faw_woo_change_order_received_text', 10, 2);

/**
 * change the thank you text 
 *
 * @param  string $str 
 * @param  WC_Order $order
 */
function my_faw_woo_change_order_received_text($str, $order) {

	$data = $_REQUEST['chargeResponse'];
	$data = str_replace('\\', '', $data);
	$chargeResponse = json_decode($data);
	
	if($chargeResponse != null && (isset($chargeResponse->merchantRefNumber))){
		$output = ['status' => 10];
		$merchantRefNum = $chargeResponse->merchantRefNumber;
		if ($merchantRefNum) {
			$order = wc_get_order($merchantRefNum);
			if ($order->get_user_id() === get_current_user_id()) {
				$order->update_meta_data('_rec_faw_pay', 1);
				$order->save();//dont forget
				$output = ['status' => 20];
			}
		}
	}
	
	if($chargeResponse != null && ($chargeResponse->paymentMethod == 'CARD' ||$chargeResponse->paymentMethod == 'PAYATFAWRY')){
		$str .= '<br><br>' . '<Strong>Order Number: </Strong>' . $chargeResponse->merchantRefNumber ;
		$str .= '<br>' . '<Strong>Fawry Refrence Number: </Strong>' . $chargeResponse->fawryRefNumber ;
		$str .= '<br>' . '<Strong>Payment Method: </Strong>' . $chargeResponse->paymentMethod ;
		$str .= '<br>' . '<Strong>Total Amount: </Strong>' . $order->total ;
		return $str;
	} elseif ($order != null && $order->payment_method == MY_FAW_PAYMENT_METHOD && $order->status == 'on-hold') {
        if ($order->get_meta('_rec_faw_pay', TRUE) == 1) {
            return $str;
        }

    } else {
        return $str;
    }
    $new_str = __('<h2>Please Pay for the order using the below Button</h2>', 'my_faw');
    $options = get_option('woocommerce_' . MY_FAW_PAYMENT_METHOD . '_settings');
    $expire_hours = $options['unpaid_expire'];
    if (!trim($expire_hours)) {
        $expire_hours = '48';
    }
    $new_str .= '<script> '
			. 'var language= "en-gb";'
            . 'var merchant= "' . $options['merchant_identifier'] . '";'
            . 'var merchantRefNum= "' . $order->get_id() . '";'
            . 'var productsJSON=' . getProductsJson($order,$options) . ';'
			. 'var customer=' . getCustomer($order) . ';'
            . 'var customerName= "' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() . '";'
            . 'var  mobile = "' . $order->get_billing_phone() . '";'
            . 'var  email = "' . $order->get_billing_email() . '";'
            . 'var  customerId = "' . $order->get_customer_id() . '";'
            . 'var  orderExpiry = "' . $expire_hours . '";'
            . 'var  locale = "' . ( (strpos(get_locale(), 'ar') !== false) ? 'ar-eg' : 'en-gb') . '";'
			. 'var  callBack = "' . home_url() . '/checkout/order-received/' . $order->get_id() . '/";'
			. 'var  failCallBack = "' . home_url() . '";'
            . '</script>';

    if (wp_get_theme() == 'Avada') {
        $new_str .= do_shortcode('[fusion_button link="#" text_transform="" title="" target="_self" link_attributes="" alignment="center" modal="" hide_on_mobile="small-visibility,medium-visibility,large-visibility" class="" id="faw_checkout" color="custom" button_gradient_top_color="#ffd205" button_gradient_bottom_color="#eac804" button_gradient_top_color_hover="#ffd205" button_gradient_bottom_color_hover="#ffd205" accent_color="" accent_hover_color="" type="3d" bevel_color="#049bce" border_width="" size="large" stretch="default" shape="pill" icon="" icon_position="left" icon_divider="no" animation_type="shake" animation_direction="left" animation_speed="0.3" animation_offset=""]'
                . '  <img  src="' . MY_FAW_URL . 'images/logo.png">'
                . '[/fusion_button]');
    } else {
        $new_str .= '<br>' . '<button id="faw_checkout" style="background-color: #ffd205;border: 1px solid #e7bf08;">
          <img  src="' . MY_FAW_URL . 'images/logo.png"></button>';
    }
	
    return $new_str;
}

function getCustomer($order){
	if($order == null) return;
	$customer = new stdClass();
	$customer->customerName = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
	$customer->customerMobile = $order->get_billing_phone();
	$customer->customerEmail = $order->get_billing_email();
	$customer->customerId = $order->get_customer_id();
	return json_encode($customer);
}

/**
 * return the products as JSON array
 * 
 * @param WC_Order $order
 */
function getProductsJson($order,$options) {
    $stupid_mode = $options['stupid_mode'];
    if($stupid_mode=='yes'){
         $arr[] = [
            'productSKU' => $order->get_id(),
            'description' => $order->get_id(),
            'quantity' => 1,
            'price' => $order->get_total()
        ];
    }else{
		$items=$order->get_items();
		$arr = [];
		foreach ($items as $item) {
			$data = $item->get_data();
    
			$arr[] = [
				'productSKU' => $data['product_id'],
				'description' => $data['name'],
				'quantity' => $data['quantity'],
				'price' => $data['total'] / $data['quantity'],
			];
		}
    }
	
	$orderItems = array("orderItems" => $arr);
    return json_encode($orderItems);
}

//add fawry js
function my_faw_scripts() {
    $options = get_option('woocommerce_' . MY_FAW_PAYMENT_METHOD . '_settings');
    $isStaging = $options['is_staging'] == 'no' ? FALSE : TRUE;
    $php_vars = array(
        'siteurl' => get_option('siteurl'),
        'ajaxurl' => admin_url('admin-ajax.php'),
    );

    if (is_page('checkout')) {
        if ($isStaging) {
            //wp_enqueue_script('fawry_js', 'https://atfawrystaging.atfawry.com/ECommercePlugin/scripts/V2/FawryPay.js');
			wp_enqueue_script('fawry_js', 'https://atfawry.fawrystaging.com/ECommercePlugin/scripts/V2/FawryPay.js');
        } else {
            wp_enqueue_script('fawry_js', 'https://www.atfawry.com/ECommercePlugin/scripts/V2/FawryPay.js');
        }

        wp_enqueue_script('faw_checkout', plugin_dir_url(__DIR__) . 'scripts/faw_checkout.js', array('jquery', 'fawry_js'));
        wp_localize_script('faw_checkout', 'FAW_PHPVAR', $php_vars); //FAW_PHPVAR name must be unqiue
    }
}

add_action('wp_enqueue_scripts', 'my_faw_scripts');
