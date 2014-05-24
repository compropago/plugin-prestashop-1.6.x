<?php

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
include(dirname(__FILE__).'/compropago.php');

	if ( isset($_POST['payment_type']) ) {
		$payment_type =  $_POST['payment_type'];
		
		$currency = new Currency(intval(isset($_POST['currency_payement']) ? $_POST['currency_payement'] : $cookie->id_currency));
		
		$total = floatval(number_format($cart->getOrderTotal(true, 3), 2, '.', ''));
			
		$compropago = new compropago();

		$mailVars = array
		(
			'{bankwire_owner}' 		=> $compropago->textshowemail, 
			'{bankwire_details}' 	=> '', 
			'{bankwire_address}' 	=> ''
		);
				
		$compropago->validateOrder
		(
			$cart->id, 
			Configuration::get('compropago_STATUS_0'), 
			$total, 
			$compropago->displayName, 
			NULL, 
			$mailVars, 
			$currency->id
		);
						
		$order 		= new Order($compropago->currentOrder);
		$idCustomer = $order->id_customer;
		$idLang		= $order->id_lang;
		$customer 	= new Customer(intval($idCustomer));
		$CusMail	= $customer->email;


		Tools::redirectLink(__PS_BASE_URI__.'order-confirmation.php?id_cart='.$cart->id.'&id_module='.$compropago->id.'&id_order='.$compropago->currentOrder.'&key='.$order->secure_key.'&payment_type='.$payment_type);
	}

?>