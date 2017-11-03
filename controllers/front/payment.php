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
 * controller para versiones >= 1.5
 * @author Rolando Lucio <rolando@compropago.com>
 * @since 2.0.0
 */
 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class CompropagoPaymentModuleFrontController extends ModuleFrontController
{
	public $ssl = true;
	public $display_column_left = false;

	/**
	 * @see FrontController::initContent()
	 */
	public function initContent()
	{

		$logger = new FileLogger(0); //0 == debug level, logDebug() won’t work without this.
		$logger->setFilename("/tmp/compropago.log");

		$compropagoData = NULL;

		parent::initContent();

		$cart = $this->context->cart;
        $order_total = $cart->getOrderTotal(true, Cart::BOTH);

		if (!$this->module->checkCurrency($cart)){
			$logger->logDebug( 'conf invalid [0].' );
			Tools::redirect('index.php?controller=order');
		}
		//ComproPago valid config?
		if (!$this->module->checkCompropago()){
			$logger->logDebug( 'conf invalid [1].' );
			Tools::redirect('index.php?controller=order');
		}


		$compropagoData = $this->module->getProvidersCompropago();
		if( empty($compropagoData) ){
			$logger->logDebug( "We haven't providers. Check what's happening." );
		}
		//$logger->logDebug( print_r( $compropagoData ,true) );


		//ok with tpl config?
		/*if( !$compropagoData = $this->module->getProvidersCompropago($order_total) ){
			$logger->logDebug( 'conf invalid [2].' );
			Tools::redirect('index.php?controller=order');
		}*/

		$logger->logDebug( 'experiment....' );

		$this->context->smarty->assign(array(
		    'providers'            => $compropagoData['providers'],
            'show_logos'           => $compropagoData['show_logos'],
            'description'          => $compropagoData['description'],
            'instructions'         => $compropagoData['instrucciones'],
			'nbProducts'           => $cart->nbProducts(),
			'cust_currency'        => $cart->id_currency,
			'currencies'           => $this->module->getCurrency((int)$cart->id_currency),
			'total'                => $order_total,
			'isoCode'              => $this->context->language->iso_code,
			'this_path'            => $this->module->getPathUri(),
			'this_path_compropago' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/',
			'this_path_ssl'        => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/'
		));

		$logger->logDebug( 'experiment...2222.' );

		$this->setTemplate('payment_execution.tpl');
	}
}
