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

class compropago  extends PaymentModule{
	protected $_html = '';
	protected $_postErrors = array();
	
	public $publickey;
	public $privatekey;
	public $live;
	public $extra_mail_vars;
	
	public function __construct(){
		
		$this->name = 'compropago';
		$this->tab = 'payments_gateways';
		$this->version ='1.0.1';
		$this->author='ComproPago';
		
		$this->controllers = array('payment', 'validation');
		$this->is_eu_compatible = 1;
		
		$this->currencies = true;
		$this->currencies_mode = 'checkbox';
		
		$config = Configuration::getMultiple( array('COMPROPAGO_PUBLICKEY','COMPROPAGO_PRIVATEKEY') );
		if (!empty($config['COMPROPAGO_PUBLICKEY']))
			$this->publickey = $config['COMPROPAGO_PUBLICKEY'];
		if (!empty($config['COMPROPAGO_PRIVATEKEY']))
			$this->privatekey = $config['COMPROPAGO_PRIVATEKEY'];
							

		$this->bootstrap = true;
		
		parent::__construct();
		
		$this->page = basename(__FILE__, '.php');
		$this->displayName = $this->l('ComproPago');
		$this->description = $this->l('Con ComproPago puedes recibir pagos en OXXO, 7Eleven y muchas tiendas más en todo México');
		$this->confirmUninstall = $this->l('Esta seguro de remover ComproPago?');
		
		if (!isset($this->publickey) || !isset($this->privatekey) )
			$this->warning = $this->l('Se requiere ingresar sus llaves para usar el módulo de ComproPago');
		if (!count(Currency::checkPaymentCurrencies($this->id)))
			$this->warning = $this->l('No currency has been set for this module.');
		
				$this->extra_mail_vars = array(
						'{compropago_publickey}' => Configuration::get('COMPROPAGO_PUBLICKEY'),
						'{compropago_privatekey}' => Configuration::get('COMPROPAGO_PRIVATEKEY')
				);	
	}

	public function install()
	{
		if (!parent::install() || !$this->registerHook('payment') || ! $this->registerHook('displayPaymentEU') || !$this->registerHook('paymentReturn'))
			return false;
			return true;
	}
	
	public function uninstall()
	{
		if (!Configuration::deleteByName('COMPROPAGO_PUBLICKEY')
				|| !Configuration::deleteByName('COMPROPAGO_PRIVATEKEY')
				|| !parent::uninstall())
			return false;
			return true;
	}	
	
	protected function _postValidation()
	{
		if (Tools::isSubmit('btnSubmit'))
		{
			if (!Tools::getValue('COMPROPAGO_PUBLICKEY'))
				$this->_postErrors[] = $this->l('Se requiere la Llave Pública.');
				elseif (!Tools::getValue('COMPROPAGO_PRIVATEKEY'))
				$this->_postErrors[] = $this->l('Se requiere la Llave Privada.');
		}
	}
	
	protected function _postProcess()
	{
		if (Tools::isSubmit('btnSubmit'))
		{
			Configuration::updateValue('COMPROPAGO_PUBLICKEY', Tools::getValue('COMPROPAGO_PUBLICKEY'));
			Configuration::updateValue('COMPROPAGO_PRIVATEKEY', Tools::getValue('COMPROPAGO_PRIVATEKEY'));
		}
		$this->_html .= $this->displayConfirmation($this->l('Configuración de ComproPago Actualizada'));
	}
	
	protected function _displayComproPago()
	{
		return $this->display(__FILE__, 'infos.tpl');
	}
	
	public function getContent()
	{
		if (Tools::isSubmit('btnSubmit'))
		{
			$this->_postValidation();
			if (!count($this->_postErrors))
				$this->_postProcess();
				else
					foreach ($this->_postErrors as $err)
						$this->_html .= $this->displayError($err);
		}
		else
			$this->_html .= '<br />';
	
			$this->_html .= $this->_displayComproPago();
			$this->_html .= $this->renderForm();
	
			return $this->_html;
	}
	
	public function checkCurrency($cart)
	{
		$currency_order = new Currency($cart->id_currency);
		$currencies_module = $this->getCurrency($cart->id_currency);
	
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
								'title' => $this->l('Configuración de ComproPago'),
								//'image' => '../img/admin/icon_to_display.gif',  //16x16
								'icon' => 'icon-rocket'
						),
						'input' => array(
								array(
										'type' => 'text',
										'label' => $this->l('Llave Pública'),
										'name' => 'COMPROPAGO_PUBLICKEY',
										'desc' => 'Obten tu llave pública: <a href="https://compropago.com/panel/configuracion" target="_blank">Panel de Compropago</a>',
										'required' => true
								),
								array(
										'type' => 'text',
										'label' => $this->l('Llave Privada'),
										'name' => 'COMPROPAGO_PRIVATEKEY',
										'desc' => 'Obten tu llave privada: <a href="https://compropago.com/panel/configuracion" target="_blank">Panel de Compropago</a>',
										'required' => true
								),
								

								array(
										'type'      => 'radio',                        
										'label'     => $this->l('Estilo'),        
										'desc'      => $this->l('Como se muestra la lista de tiendas donde realizar el pago'),   
										'name'      => 'COMPROPAGO_LOGOS',                         
										
										                              
										'is_bool'   => true,                                
										
										'values'    => array(                                
												array(
														'id'    => 'active_on',                      
														'value' => 0,                                
														'label' => $this->l('Logos')                 
												),
												array(
														'id'    => 'active_off',
														'value' => 1,
														'label' => $this->l('Lista')
												)
										),
								),
								array(
										'type' => 'radio',
										'label' => $this->l('Modo de Pruebas'),
										'name' => 'COMPROPAGO_MODOPRUEBAS',
										'desc' => 'Al activar el Modo de pruebas <b>es necesario que <span style="color:red;">cambie sus llaves por las de Modo Prueba</span></b>',
										'is_bool'=> true,
										'values' => array(
												array(
														'id'    => 'active_on',
														'value' => 0,
														'label' => $this->l('Inactivo')
												),
												array(
														'id'    => 'active_off',
														'value' => 1,
														'label' => $this->l('Activo')
												)
										),
								),
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
				'COMPROPAGO_PRIVATEKEY' => Tools::getValue('COMPROPAGO_PRIVATEKEY', Configuration::get('COMPROPAGO_PRIVATEKEY'))
		);
	}
}