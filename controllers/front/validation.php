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
 * @since 2.0.0
 */

use CompropagoSdk\Factory\Factory;

class CompropagoValidationModuleFrontController extends ModuleFrontController
{
	public function postProcess()
	{
		$cart = $this->context->cart;

		if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'compropago') {
                $authorized = true;
                break;
            }
        }
        
        if (!$authorized) {
            die($this->module->l('This payment method is not available.', 'validation'));
        }
        
        $customer = new Customer($cart->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $currency = $this->context->currency;
        $total    = (float)$cart->getOrderTotal(true, Cart::BOTH);

        $compropagoStore = (!isset($_REQUEST['compropagoProvider']) || empty($_REQUEST['compropagoProvider'])) ? 'OXXO' : $_REQUEST['compropagoProvider'];
        $mailVars        = array('{compropago_msj}' => 'En breve recibirá un email de ComproPago con su orden de pago ');
        $result          = $this->module->validateOrder((int)$cart->id, Configuration::get('COMPROPAGO_PENDING'), $total, $this->module->displayName, NULL, $mailVars, (int)$currency->id, false, $customer->secure_key);
        $cpOrderName     = Configuration::get('PS_SHOP_NAME') . ', Ref:' . $this->module->currentOrder;
        
            $order_info = [
                'order_id' => $this->module->currentOrder,
                'order_name' => $cpOrderName,
                'order_price' => $total,
                'customer_name' => $customer->firstname . ' ' . $customer->lastname,
                'customer_email' => $customer->email,
                'payment_type' => $compropagoStore,
                'currency' => $currency->iso_code,
            ];

            $order = Factory::getInstanceOf('PlaceOrderInfo', $order_info);
        try {
            $response = $this->module->client->api->placeOrder($order);
        } catch (Exception $e) {
            die($this->module->l('This payment method is not available .', 'validation') . '<br>' . $e->getMessage());
        }

        if ($response->type != 'charge.pending') {
            
			// echo '<pre>';
            // var_dump($response);
            // echo '</pre>';
						
            die($this->module->l('This payment method is not available.', 'validation'));
        }

        if (!$this->module->verifyTables()) {
            die($this->module->l('This payment method is not avaidlable.', 'validation') . '<br>ComproPago Tables Not Found');
        }

        try {
            $recordTime = time();
            $ioIn = base64_encode(serialize($response));
            $ioOut = base64_encode(serialize($order));

            Db::getInstance()->autoExecute(_DB_PREFIX_ . 'compropago_orders', array(
                'date'             => $recordTime,
                'modified'         => $recordTime,
                'compropagoId'     => $response->id,
                'compropagoStatus' => $response->status,
                'storeCartId'      => $cart->id,
                'storeOrderId'     => $this->module->currentOrder,
                'storeExtra'       => 'COMPROPAGO_PENDING',
                'ioIn'             => $ioIn,
                'ioOut'            => $ioOut
            ), 'INSERT');


            Db::getInstance()->autoExecute(_DB_PREFIX_ . 'compropago_transactions', array(
                'orderId'              => $response->order_info->order_id,
                'date'                 => $recordTime,
                'compropagoId'         => $response->id,
                'compropagoStatus'     => $response->status,
                'compropagoStatusLast' => $response->status,
                'ioIn'                 => $ioIn,
                'ioOut'                => $ioOut
            ), 'INSERT');

        } catch (Exception $e) {
            die($this->module->l('This payment method is not avaislable.', 'validation') . '<br>' . $e->getMessage());
        }

        Tools::redirect('index.php?compropagoId=' . $response->id . '&controller=order-confirmation&id_cart=' . (int)$cart->id . '&id_module=' . (int)$this->module->id . '&id_order=' . $this->module->currentOrder . '&key=' . $customer->secure_key);
	}
}
