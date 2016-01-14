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
		
		
			
			//Place a ComproPago Order
			$compropagoStore=(!isset($_REQUEST['compropagoProvider']) || empty($_REQUEST['compropagoProvider']))?'OXXO':$_REQUEST['compropagoProvider'];
			$cpOrderName=Configuration::get('PS_SHOP_NAME').', Ref:'. base64_encode($cart->id);
			$compropagoOrderData = array(
					'order_id'           => base64_encode($cart->id),            
					'order_price'        => $total,                
					'order_name'         => $cpOrderName,      
					'customer_name'      => $customer->firstname.' '.$customer->lastname,        
					'customer_email'     => $customer->email,    
					'payment_type'       => $compropagoStore,
					'app_client_name'	 =>	'prestashop',
					'app_client_version' => _PS_VERSION_
					
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
			
			if(!$this->module->verifyTables()){
				die($this->module->l('This payment method is not available.', 'validation').'<br>ComproPago Tables Not Found');
			}
			
			try{
				$mailVars =	array(
						'{compropago_url}' => 'https://www.compropago.com/comprobante/?confirmation_id='.$compropagoResponse->id,
				);
				//Prestashop add order 
				$result= $this->module->validateOrder((int)$cart->id, Configuration::get('COMPROPAGO_PENDING'), $total, $this->module->displayName, NULL, $mailVars, (int)$currency->id, false, $customer->secure_key);
				
				$recordTime=time();
				//add new compropago order to register
				$ioIn=base64_encode(json_encode($compropagoResponse));
				$ioOut=base64_encode(json_encode($compropagoOrderData));
				Db::getInstance()->autoExecute(_DB_PREFIX_ . 'compropago_orders', array(
						'date' 				=> $recordTime,
						'modified' 			=> $recordTime,
						'compropagoId'		=> $compropagoResponse->id,
						'compropagoStatus'	=> $compropagoResponse->status,
						'storeCartId'		=> $cart->id,
						'storeOrderId'		=> $this->module->currentOrder,
						'storeExtra'		=> 'COMPROPAGO_PENDING',
						'ioIn' 				=> $ioIn,
						'ioOut' 			=> $ioOut			
				),'INSERT');
				$idCompropagoOrder = (int) Db::getInstance()->Insert_ID();
				//record transaction
				Db::getInstance()->autoExecute(_DB_PREFIX_ . 'compropago_transactions', array(
						'orderId' 			=> $idCompropagoOrder,
						'date' 				=> $recordTime,
						'compropagoId'		=> $compropagoResponse->id,
						'compropagoStatus'	=> $compropagoResponse->status,
						'compropagoStatusLast'	=> $compropagoResponse->status,
						'ioIn' 				=> $ioIn,
						'ioOut' 			=> $ioOut
				),'INSERT');
				
			}catch (Exception $e){
				die($this->module->l('This payment method is not available.', 'validation').'<br>'.$e->getMessage());
			}	
			
			Tools::redirect('index.php?compropagoId='.$compropagoResponse->id.'&controller=order-confirmation&id_cart='.(int)$cart->id.'&id_module='.(int)$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);

			
	}
}
