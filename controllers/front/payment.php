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
		//ComproPago valid currency?
		if (!$this->module->checkCurrency($cart)){
			Tools::redirect('index.php?controller=order');
		}
		//ComproPago valid config? 
		if (!$this->module->checkCompropago()){
			Tools::redirect('index.php?controller=order');
		}
		//TPL view exists?
		if( !$compropagoTpl=$this->module->getViewPathCompropago('providers')){
			Tools::redirect('index.php?controller=order');
		}
		//ok with tpl config?
		if( !$compropagoData=$this->module->getProvidersCompropago() ){
			Tools::redirect('index.php?controller=order');
		}

		$this->context->smarty->assign(array(
			'compropagoTpl' => $compropagoTpl,
			'compropagoData' => $compropagoData,
			'nbProducts' => $cart->nbProducts(),
			'cust_currency' => $cart->id_currency,
			'currencies' => $this->module->getCurrency((int)$cart->id_currency),
			'total' => $cart->getOrderTotal(true, Cart::BOTH),
			'isoCode' => $this->context->language->iso_code,
			/*'chequeName' => $this->module->publicKey,
			'chequeAddress' => Tools::nl2br($this->module->privateKey),*/
			'this_path' => $this->module->getPathUri(),
			'this_path_compropago' => $this->module->getPathUri(),
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/'
		));

		$this->setTemplate('payment_execution.tpl');
	}
}
