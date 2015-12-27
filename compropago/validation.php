<?php
/*
* Copyright 2015 Compropago.
*
* Licensed under the Apache License, Version 2.0 (the "License");
* you may not use this file except in compliance with the License.
* You may obtain a copy of the License at
*
*     http://www.apache.org/licenses/LICENSE-2.0
*
* Unless required by applicable law or agreed to in writing, software
* distributed under the License is distributed on an "AS IS" BASIS,
* WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
* See the License for the specific language governing permissions and
* limitations under the License.
*/
/**
 * @author Rolando Lucio <rolando@compropago.com>
 */

include(dirname(__FILE__).'/../../config/config.inc.php');
Tools::displayFileAsDeprecated();

include(dirname(__FILE__).'/../../header.php');
include(dirname(__FILE__).'/compropago.php');

$context = Context::getContext();
$cart = $context->cart;
$compropago = new Compropago();

if ($cart->id_customer == 0 OR $cart->id_address_delivery == 0 OR $cart->id_address_invoice == 0 OR !$cheque->active)
	Tools::redirect('index.php?controller=order&step=1');

// Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
$authorized = false;
foreach (Module::getPaymentModules() as $module)
	if ($module['name'] == 'compropago')
	{
		$authorized = true;
		break;
	}
if (!$authorized)
	die($compropago->l('This payment method is not available.', 'validation'));

$customer = new Customer($cart->id_customer);

if (!Validate::isLoadedObject($customer))
	Tools::redirect('index.php?controller=order&step=1');

$currency = $context->currency;
$total = (float)$cart->getOrderTotal(true, Cart::BOTH);

$compropago->validateOrder((int)$cart->id, Configuration::get('PS_OS_CHEQUE'), $total, $compropago->displayName, NULL, array(), (int)$currency->id, false, $customer->secure_key);

Tools::redirect('index.php?controller=order-confirmation&id_cart='.(int)($cart->id).'&id_module='.(int)($compropago->id).'&id_order='.$compropago->currentOrder.'&key='.$customer->secure_key);


