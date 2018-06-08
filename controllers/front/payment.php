<?php
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

		$compropagoData = NULL;

		parent::initContent();

		$cart = $this->context->cart;
        $order_total = $cart->getOrderTotal(true, Cart::BOTH);

		if (!$this->module->checkCurrency($cart)){
			Tools::redirect('index.php?controller=order');
		}
		//ComproPago valid config?
		if (!$this->module->checkCompropago()){
			Tools::redirect('index.php?controller=order');
		}

		// we need to validate if compropagoData is empty.
		$compropagoData = $this->module->getProvidersCompropago();
		if( empty($compropagoData) ){
			
		}

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

		$this->setTemplate('payment_execution.tpl');
	}
}
