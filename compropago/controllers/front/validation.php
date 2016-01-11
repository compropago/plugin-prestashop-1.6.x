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
class CompropagoValidationModuleFrontController extends ModuleFrontController
{
	public function postProcess()
	{
		$cart = $this->context->cart;

		if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active)
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
				die($this->module->l('This payment method is not available.', 'validation'));

			$customer = new Customer($cart->id_customer);

			if (!Validate::isLoadedObject($customer))
				Tools::redirect('index.php?controller=order&step=1');

			$currency = $this->context->currency;
			$total = (float)$cart->getOrderTotal(true, Cart::BOTH);
		
			$mailVars =	array(
					'{cheque_name}' => Configuration::get('CHEQUE_NAME'),
					'{cheque_address}' => Configuration::get('CHEQUE_ADDRESS'),
					'{cheque_address_html}' => str_replace("\n", '<br />', Configuration::get('CHEQUE_ADDRESS')));
			
			//Place a ComproPago Order
			$compropagoStore=(!isset($_REQUEST['compropagoProvider']) || empty($_REQUEST['compropagoProvider']))?'OXXO':$_REQUEST['compropagoProvider'];
			$compropagoOrderData = array(
					'order_id'           => 'testorderid',            
					'order_price'        => $total,                
					'order_name'         => 'Test Order reference',      
					'customer_name'      => 'Compropago Prestashop Test',        
					'customer_email'     => 'rolando@compropago.com',    
					'payment_type'       => $compropagoStore           
			);
			try {
				//response JSON
				$compropagoResponse = $this->module->compropagoService->placeOrder($compropagoOrderData);
			
			}catch(Exception $e){
				die($this->module->l('This payment method is not available.', 'validation').'<br>'.$e->getMessage());
			}
			if(!isset($compropagoResponse->status) && $compropagoResponse->status!='pending'){
				echo '<pre>';
				print_r($compropagoResponse);
				echo '</pre>';
				die($this->module->l('This payment method is not available.', 'validation'));
			}
				
				
				$this->module->validateOrder((int)$cart->id, Configuration::get('COMPROPAGO_PENDING'), $total, $this->module->displayName, NULL, $mailVars, (int)$currency->id, false, $customer->secure_key);
				Tools::redirect('index.php?compropagoId='.$compropagoResponse->id.'&controller=order-confirmation&id_cart='.(int)$cart->id.'&id_module='.(int)$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);

			
	}
}
