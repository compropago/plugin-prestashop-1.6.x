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

if (!defined('_PS_VERSION_'))
	exit;

$compropagoComposer= dirname(__FILE__).'/vendor/autoload.php';
if ( file_exists( $compropagoComposer ) ){
	require $compropagoComposer;
}else{
	exit('No se encontro el autoload para Compropago y sus dependencias:'.$compropagoComposer);
}
class Compropago extends PaymentModule
{
	private $_html = '';
	private $_postErrors = array();

	public $publicKey;
	public $privateKey;
	public $extra_mail_vars;
	public $modoExec;
	

	public function __construct()
	{
		$this->name = 'compropago';
		$this->tab = 'payments_gateways';
		$this->version = '2.0.0';
		$this->author = 'ComproPago';
		$this->controllers = array('payment', 'validation');
		$this->is_eu_compatible = 1;

		$this->currencies = true;
		$this->currencies_mode = 'checkbox';

		$config = Configuration::getMultiple(array('COMPROPAGO_PUBLICKEY', 'COMPROPAGO_PRIVATEKEY'));
		if (isset($config['COMPROPAGO_PUBLICKEY']))
			$this->publicKey = $config['COMPROPAGO_PUBLICKEY'];
		if (isset($config['COMPROPAGO_PRIVATEKEY']))
			$this->privateKey = $config['COMPROPAGO_PRIVATEKEY'];
		if (isset($config['COMPROPAGO_MODE']))
			$this->modoExec = $config['COMPROPAGO_MODE'];

		$this->bootstrap = true;
		parent::__construct();

		$this->displayName = $this->l('ComproPago');
		$this->description = $this->l('This module allows you to accept payments in Mexico stores like OXXO, 7Eleven and More.');
		$this->confirmUninstall = $this->l('Are you sure you want to uninstall ComproPago?');

		if ((!isset($this->publicKey) || !isset($this->privateKey) || empty($this->publicKey) || empty($this->privateKey))){
			$this->warning = $this->l('The Public Key and Private Key must be configured before using this module.');
			$this->active=false;
		}
			
		if(isset($this->publicKey) && isset($this->privateKey)){
			$moduleLive=($this->modoExec=='yes')? true:false;
			try{
				$compropagoConfig= array(
						'publickey'=>$this->publicKey,
						'privatekey'=>$this->privateKey,
						'live'=>$moduleLive
				);
				$compropagoClient = new Compropago\Client($compropagoConfig);
				$compropagoService = new Compropago\Service($compropagoClient);
				if(!$compropagoResponse = $compropagoService->evalAuth()){
					$this->warning .= $this->l('Invalid Keys, The Public Key and Private Key must be valid before using this module.');
					$this->active=false;
				}else{
					
					
					if($compropagoResponse->mode_key != $compropagoResponse->livemode){
						// store vs compropago Modes
						$this->warning .= $this->l('Your Keys and Your ComproPago account are set to different Modes.');
						$this->active=false;
					}else{
						/*if($moduleLive != $compropagoResponse->livemode){
						 // store vs compropago Modes
						 $this->warning .= $this->l('Your Store and Your ComproPago account are set to different Modes.');
						 }
						 if($moduleLive != $compropagoResponse->mode_key){
						 // store vs Keys
						 $this->warning .= $this->l('ComproPago ALERT:Your Keys are for a different Mode.');
						 }
						 */
						if( ( $compropagoResponse->livemode && $compropagoResponse->mode_key ) ||
								( !$compropagoResponse->livemode && !$compropagoResponse->mode_key ) ){
									//same mode
						}
					}
						
				}
			}catch (Exception $e) {
				die($e->getMessage());
			}
		}
		if (!count(Currency::checkPaymentCurrencies($this->id)))
			$this->warning = $this->l('No currency has been set for this module.');

		$this->extra_mail_vars = array(
											'{COMPROPAGO_PUBLICKEY}' => Configuration::get('COMPROPAGO_PUBLICKEY'),
											'{COMPROPAGO_PRIVATEKEY}' => Configuration::get('COMPROPAGO_PRIVATEKEY'),
											'{COMPROPAGO_PRIVATEKEY_html}' => str_replace("\n", '<br />', Configuration::get('COMPROPAGO_PRIVATEKEY'))
											);
	}
	
	/*private function setCompropagoConfig(){
		
		$this->compropagoConfig = array(
				'publickey'=>$this->publickey,
				'privatekey'=>$this->privatekey,
				'live'=>($this->modoExec=='yes')? true:false,
				'contained'=>'plugin; cpps '.$this->version.';prestashop '._PS_VERSION_.';'
		);
	}*/
	public function install()
	{
		if (!parent::install() || !$this->registerHook('payment') || ! $this->registerHook('displayPaymentEU') || !$this->registerHook('paymentReturn'))
			return false;
		return true;
	}

	public function uninstall()
	{
		if (!Configuration::deleteByName('COMPROPAGO_PUBLICKEY') || !Configuration::deleteByName('COMPROPAGO_PRIVATEKEY') || !parent::uninstall())
			return false;
		return true;
	}

	private function _postValidation()
	{
		if (Tools::isSubmit('btnSubmit'))
		{
			if (!Tools::getValue('COMPROPAGO_PUBLICKEY'))
				$this->_postErrors[] = $this->l('The Public Key is required');
			elseif (!Tools::getValue('COMPROPAGO_PRIVATEKEY'))
				$this->_postErrors[] = $this->l('The Private Key is required');
		}
	}

	private function _postProcess()
	{
		if (Tools::isSubmit('btnSubmit'))
		{
			Configuration::updateValue('COMPROPAGO_PUBLICKEY', Tools::getValue('COMPROPAGO_PUBLICKEY'));
			Configuration::updateValue('COMPROPAGO_PRIVATEKEY', Tools::getValue('COMPROPAGO_PRIVATEKEY'));
			Configuration::updateValue('COMPROPAGO_MODE', Tools::getValue('COMPROPAGO_MODE'));
		}
		$this->_html .= $this->displayConfirmation($this->l('Settings updated'));
	}

	private function _displayCompropago()
	{
		return $this->display(__FILE__, 'infos.tpl');
	}

	public function getContent()
	{
		$this->_html = '';

		if (Tools::isSubmit('btnSubmit'))
		{
			$this->_postValidation();
			if (!count($this->_postErrors))
				$this->_postProcess();
			else
				foreach ($this->_postErrors as $err)
					$this->_html .= $this->displayError($err);
		}

		$this->_html .= $this->_displayCompropago();
		$this->_html .= $this->renderForm();

		return $this->_html;
	}

	public function hookPayment($params)
	{
		if (!$this->active)
			return;
		if (!$this->checkCurrency($params['cart']))
			return;

		$this->smarty->assign(array(
			'this_path' => $this->_path,
			'this_path_compropago' => $this->_path,
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/'
		));
		return $this->display(__FILE__, 'payment.tpl');
	}

	public function hookDisplayPaymentEU($params)
	{
		if (!$this->active)
			return;
		if (!$this->checkCurrency($params['cart']))
			return;

		$payment_options = array(
			'cta_text' => $this->l('Pay by ComproPago'),
			'logo' => Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/logo.png'),
			'action' => $this->context->link->getModuleLink($this->name, 'validation', array(), true)
		);

		return $payment_options;
	}

	public function hookPaymentReturn($params)
	{
		if (!$this->active)
			return;

		$state = $params['objOrder']->getCurrentState();
		//PS_OS_CHEQUE 2 PS_OS_COMPROPAGO?
		if (in_array($state, array(Configuration::get('PS_OS_COMPROPAGO'), Configuration::get('PS_OS_OUTOFSTOCK'), Configuration::get('PS_OS_OUTOFSTOCK_UNPAID'))))
		{
			$this->smarty->assign(array(
				'total_to_pay' => Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false),
				//'publicKey' => $this->publicKey,
				//'privateKey' => Tools::nl2br($this->privateKey),
				'status' => 'ok',
				'id_order' => $params['objOrder']->id
			));
			if (isset($params['objOrder']->reference) && !empty($params['objOrder']->reference))
				$this->smarty->assign('reference', $params['objOrder']->reference);
		}
		else
			$this->smarty->assign('status', 'failed');
		return $this->display(__FILE__, 'payment_return.tpl');
	}

	public function checkCurrency($cart)
	{
		$currency_order = new Currency((int)($cart->id_currency));
		$currencies_module = $this->getCurrency((int)$cart->id_currency);

		if (is_array($currencies_module))
			foreach ($currencies_module as $currency_module)
				if ($currency_order->id == $currency_module['id_currency'])
					return true;
		return false;
	}

	public function renderForm()
	{
		$fields_form = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('ComproPago details'),
					//crear icono 16x16	
					'icon' => 'icon-rocket'
				),
				'input' => array(
					array(
						'type' => 'text',
						'label' => $this->l('Public Key'),
						'name' => 'COMPROPAGO_PUBLICKEY',
						'required' => true
					),
					array(
						'type' => 'text',
						'label' => $this->l('Private Key'),
						'desc' => $this->l('Get your keys at ComproPago').': <a href="https://compropago.com/panel/configuracion" target="_blank">'.$this->l('ComproPago Panel').'</a>',
						'name' => 'COMPROPAGO_PRIVATEKEY',
						'required' => true
					),
					array(
							'type'      => 'radio',                               // This is an <input type="checkbox"> tag.
							'label'     => $this->l('Mode'),    			    // The <label> for this <input> tag.
							'desc'      => $this->l('Are you on live or testing?,Change your Keys according to the mode'),   // A help text, displayed right next to the <input> tag.
							'name'      => 'COMPROPAGO_MODE',                     // The content of the 'id' attribute of the <input> tag.
							'required'  => true,                                  // If set to true, this option must be set.
							'is_bool'   => true,                                  // If set to true, this means you want to display a yes/no or true/false option.
							
							'values'    => array(                                 // $values contains the data itself.
									array(
											'id'    => 'active_on',                           // The content of the 'id' attribute of the <input> tag, and of the 'for' attribute for the <label> tag.
											'value' => 'yes',                                     // The content of the 'value' attribute of the <input> tag.
											'label' => $this->l('Live Mode')                    // The <label> for this radio button.
									),
									array(
											'id'    => 'active_off',
											'value' => 'no',
											'label' => $this->l('Testing Mode')
									)
							),
					),
						
						///END OF FIELDS
				),
				'submit' => array(
					'title' => $this->l('Save'),
				)
			),
		);

		$helper = new HelperForm();
		$helper->show_toolbar = false;
		$helper->table = $this->table;
		$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
		$helper->default_form_language = $lang->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
		$this->fields_form = array();
		$helper->id = (int)Tools::getValue('id_carrier');
		$helper->identifier = $this->identifier;
		$helper->submit_action = 'btnSubmit';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFieldsValues(),
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id
		);

		return $helper->generateForm(array($fields_form));
	}

	public function getConfigFieldsValues()
	{
		return array(
			'COMPROPAGO_PUBLICKEY' => Tools::getValue('COMPROPAGO_PUBLICKEY', Configuration::get('COMPROPAGO_PUBLICKEY')),
			'COMPROPAGO_PRIVATEKEY' => Tools::getValue('COMPROPAGO_PRIVATEKEY', Configuration::get('COMPROPAGO_PRIVATEKEY')),
			'COMPROPAGO_MODE' => Tools::getValue('COMPROPAGO_MODE', Configuration::get('COMPROPAGO_MODE')),
		);
	}
}
