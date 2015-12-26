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
		
		$config = Configuration::getMultiple( array('COMPROPAGO_PUBLICKEY','COMPROPAGO_PRIVATEKEY','COMPROPAGO_LIVE') );
		if (!empty($config['COMPROPAGO_PUBLICKEY']))
			$this->publickey = $config['COMPROPAGO_PUBLICKEY'];
		if (!empty($config['COMPROPAGO_PRIVATEKEY']))
			$this->privatekey = $config['COMPROPAGO_PRIVATEKEY'];
		if (!empty($config['COMPROPAGO_LIVE']))
			$this->live = $config['COMPROPAGO_LIVE'];
					

		$this->bootstrap = true;
		
		parent::__construct();
		
		$this->page = basename(__FILE__, '.php');
		$this->displayName = $this->l('ComproPago');
		$this->description = $this->l('Con ComproPago puedes recibir pagos en OXXO, 7Eleven y muchas tiendas más en todo México');
		$this->confirmUninstall = $this->l('Esta seguro de remover ComproPago?');
		
		if (!isset($this->publickey) || !isset($this->privatekey) || !isset($this->live))
			$this->warning = $this->l('Se requiere ingresar sus llaves para usar el módulo de ComproPago');
		if (!count(Currency::checkPaymentCurrencies($this->id)))
			$this->warning = $this->l('No currency has been set for this module.');
		
				$this->extra_mail_vars = array(
						'{compropago_publickey}' => Configuration::get('COMPROPAGO_PUBLICKEY'),
						'{compropago_privatekey}' => Configuration::get('COMPROPAGO_PRIVATEKEY'),
						'{compropago_live}' => Configuration::get('COMPROPAGO_LIVE')
				);
		
	}
	
}