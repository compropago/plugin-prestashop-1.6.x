<?php
use Compropago;
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
 * controller para versiones >= 1.5
 */
class CompropagoPaymentModuleFrontController extends ModuleFrontController
{
	public $ssl = true;
	public $display_column_left = false;
	
	/**
	 * @see FrontController::initContent()
	 */
	public function initContent()
	{
		parent::initContent();
	
		$cart = $this->context->cart;
		if (!$this->module->checkCurrency($cart))
			Tools::redirect('index.php?controller=order');
		
		try{
			$compropagoConfig = array(
									'publickey'=>Configuration::get('COMPROPAGO_PUBLICKEY'),
									'privatekey'=>Configuration::get('COMPROPAGO_PRIVATEKEY'),
									'live'=>true
								);
			$compropagoClient = new Compropago\Client($compropagoConfig);
			$compropagoService = new Compropago\Service($compropagoClient);
		} catch (Exception $e) {
			echo 'Compropago error: ' . $e->getMessage();
		}
	
		$compropagoData['providers']=$compropagoService->getProviders();
		
		$compropagoData['showlogo']=(Configuration::get('COMPROPAGO_LOGOS')==0)?'yes':'no';
		$compropagoData['description']='Plugin Descriptor compropago';
		$compropagoData['instrucciones']='Compropago Instrucciones';
	
		$compropagoTemplate= Compropago\Controllers\Views::loadView('providers',$compropagoData,'path','tpl');
					
			$this->context->smarty->assign(array(
					'compropagoTemplate'=> $compropagoTemplate,
					'compropagoCss'=>Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/'.'vendor/compropago/php-sdk/assets/css/compropago.css',
					'compropagoData'=>$compropagoData,
					'nbProducts' => $cart->nbProducts(),
					'cust_currency' => $cart->id_currency,
					'currencies' => $this->module->getCurrency((int)$cart->id_currency),
					'total' => $cart->getOrderTotal(true, Cart::BOTH),
					'this_path' => $this->module->getPathUri(),
					'this_path_bw' => $this->module->getPathUri(),
					'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/'
			));
	
			$this->setTemplate('payment_execution.tpl');
	}
}