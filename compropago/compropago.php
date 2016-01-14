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
	public $showLogo;
	
	public $compropagoConfig;
	public $compropagoClient;
	public $compropagoService;
	
	/**
	 * set & get module config
	 * @since 2.0.0
	 */
	public function __construct()
	{
		//Current module version & config
		$this->version = '2.0.0';
		$this->name = 'compropago';
		$this->tab = 'payments_gateways';
		$this->author = 'ComproPago';
		$this->controllers = array('payment', 'validation');
		$this->is_eu_compatible = 1;
		
		//currencies setup
		$this->currencies = true;
		$this->currencies_mode = 'checkbox';
		
		// have module been set
		$config = Configuration::getMultiple(array('COMPROPAGO_PUBLICKEY', 'COMPROPAGO_PRIVATEKEY', 'COMPROPAGO_MODE', 'COMPROPAGO_LOGOS'));
		if (isset($config['COMPROPAGO_PUBLICKEY']))
			$this->publicKey = $config['COMPROPAGO_PUBLICKEY'];
		if (isset($config['COMPROPAGO_PRIVATEKEY']))
			$this->privateKey = $config['COMPROPAGO_PRIVATEKEY'];
		$this->modoExec=(isset($config['COMPROPAGO_MODE']))?$config['COMPROPAGO_MODE']:false;
		$this->showLogo=(isset($config['COMPROPAGO_LOGOS']))?$config['COMPROPAGO_LOGOS']:false;
			

		$this->bootstrap = true;
		parent::__construct();
		
		//about ComproPago Module
		$this->displayName = $this->l('ComproPago');
		$this->description = $this->l('This module allows you to accept payments in Mexico stores like OXXO, 7Eleven and More.');
		$this->confirmUninstall = $this->l('Are you sure you want to uninstall ComproPago?');
		
		// need some keys
		if (( !isset($this->publicKey) || !isset($this->privateKey) || empty($this->publicKey) || empty($this->privateKey) ) ){
			$this->warning = $this->l('The Public Key and Private Key must be configured before using this module.');
		}
		//no operation mode defined?
		/*if ( !isset($this->modoExec)  ){
			$this->warning = $this->l('The Mode is required');
		}*/
		//Lets eval keys and mode
		if($this->active && isset($this->publicKey) && isset($this->privateKey) &&
			!empty($this->publicKey) && !empty($this->privateKey)  ){
			//$moduleLive=($this->modoExec=='yes')? true:false;
				$moduleLive=$this->modoExec;
			if($this->setComproPago($moduleLive)){
				try{
					//eval keys
					if(!$compropagoResponse = $this->compropagoService->evalAuth()){
						$this->warning .= $this->l('Invalid Keys, The Public Key and Private Key must be valid before using this module.');
					}else{
						if($compropagoResponse->mode_key != $compropagoResponse->livemode){
							// compropagoKey vs compropago Mode
							$this->warning .= $this->l('Your Keys and Your ComproPago account are set to different Modes.');
						}else{
							if($moduleLive != $compropagoResponse->livemode){
								// store Mode vs compropago Mode
								$this->warning .= $this->l('Your Store and Your ComproPago account are set to different Modes.');
							}else{
								if($moduleLive != $compropagoResponse->mode_key){
									// store Mode vs compropago Keys
									$this->warning .= $this->l('ComproPago ALERT:Your Keys are for a different Mode.');
								}else{
									if(!$compropagoResponse->mode_key && !$compropagoResponse->livemode){
										//can process orders but watch out, NOT live operations just testing
										$this->warning .= $this->l('WARNING: ComproPago account is Running in TEST Mode');
									}
								}
							}
						}
					}
				}catch (Exception $e) {
					//something went wrong on the SDK side
					$this->warning .= $e->getMessage(); //may not be show or translated
				}
			}else{
				$this->warning .= $this->l('Could not load ComproPago SDK instances.');
			}
		}
		if (!count(Currency::checkPaymentCurrencies($this->id)))
			$this->warning = $this->l('No currency has been set for this module.');

		/*$this->extra_mail_vars = array(
											'{COMPROPAGO_PUBLICKEY}' => Configuration::get('COMPROPAGO_PUBLICKEY'),
											'{COMPROPAGO_PRIVATEKEY}' => Configuration::get('COMPROPAGO_PRIVATEKEY'),
											'{COMPROPAGO_PRIVATEKEY_html}' => str_replace("\n", '<br />', Configuration::get('COMPROPAGO_PRIVATEKEY'))
											); */
	}
    /**
     * Config ComproPago SDK instance
     * @param boolean $moduleLive
     * @return boolean
     * @since 2.0.0
     */
	private function setComproPago($moduleLive){
		try{
			$this->compropagoConfig = array(
					'publickey'=>$this->publicKey,
					'privatekey'=>$this->privateKey,
					'live'=>$moduleLive,
					'contained'=>'plugin; cpps '.$this->version.';prestashop '._PS_VERSION_.';'
			);
			$this->compropagoClient = new Compropago\Client($this->compropagoConfig);
			$this->compropagoService = new Compropago\Service($this->compropagoClient);
			return true;
		}catch (Exception $e) {
			//something went wrong with the sdk
			return false;
		}
	}
	/**
	 * Check against SDK if module is valid for use
	 * @return boolean
	 * @since 2.0.0
	 */
	public function checkCompropago(){
		try {
			return Compropago\Utils\Store::validateGateway($this->compropagoClient);
		}catch (Exception $e) {
			//something went wrong with the sdk dont allow gateway method
			return false;
		}
	}
	/**
	 * Validate TPL file for view
	 * @return string Path to tpl
	 * @return false on error
	 * @since 2.0.0
	 */
	public function getViewPathCompropago($view){
		$tplPath=dirname(__FILE__).'/vendor/compropago/php-sdk/views/tpl/'.$view.'.tpl';
		if ( file_exists( $tplPath ) ){
			return $tplPath;
		}else{
			return false;
		}
	}
	/**
	 * get Providers View Config
	 * @return array  Providers TPL config array
	 * @return false  on Exception
	 * @since 2.0.0
	 */
	public function getProvidersCompropago(){
		try{
			$compropagoData['providers']=$this->compropagoService->getProviders(); //Call SDK for providers list
			$compropagoData['showlogo']=($this->showLogo)?'yes':'no';                              //(yes|no) logos or select
			$compropagoData['description']=$this->l('- ComproPago allows you to pay at Mexico stores like OXXO, 7Eleven and More.');  // Title to show
			$compropagoData['instrucciones']=$this->l('Select a Store');    // Instructions text
			return $compropagoData;
		}catch (Exception $e) {
			//something went wrong with the sdk
			return false;
		}
	}

	/**
	 * hook header options
	 * @param unknown $params
	 * @since 2.0.0
	 */
	public function hookDisplayHeader($params){
		//add css
		$this->context->controller->addCSS($this->_path.'vendor/compropago/php-sdk/assets/css/compropago.css', 'all');
	}
	/**
	 * Install Module
	 * @return boolean
	 * @since 2.0.0
	 */
	public function install()
	{
		if (version_compare(phpversion(), '5.3.0', '<')) {
			return false;
		}
		
		if (Shop::isFeatureActive())
			Shop::setContext(Shop::CONTEXT_ALL);
		
		$this->installOrderStates();
		//Lets be sure compropago tables are gone
		$queries=Compropago\Utils\Store::sqlDropTables(_DB_PREFIX_);
		foreach($queries as $drop){
			
			Db::getInstance()->execute($drop);
		}
		//creates compropago tables
		$queries=Compropago\Utils\Store::sqlCreateTables(_DB_PREFIX_);
		
		foreach($queries as $create){
			if(!Db::getInstance()->execute($create))
				die('Unable to Create ComproPago Tables, module cant be installed');
		}

		if (!parent::install() || !$this->registerHook('payment') || ! $this->registerHook('displayPaymentEU') 
			|| !$this->registerHook('paymentReturn') || !$this->registerHook('displayHeader'))
			return false;
		return true;
	}
	/**
	 * Vertify is compropago tables exists
	 * @return boolean
	 * @since 2.0.0
	 */
	public function verifyTables(){
		if(!Db::getInstance()->execute("SHOW TABLES LIKE '"._DB_PREFIX_ ."compropago_orders'") ||
				!Db::getInstance()->execute("SHOW TABLES LIKE '"._DB_PREFIX_ ."compropago_transactions'")
				){
					return false;
		}
		return true;
	}
	/**
	 * Install ComproPago Order Status 
	 * @return boolean
	 * @since 2.0.0
	 */
	protected function installOrderStates()
	{
		$values_to_insert = array(
				'invoice' => 0,
				'send_email' => 0,
				'module_name' => pSQL($this->name),
				'color' => 'RoyalBlue',
				'unremovable' => 0,
				'hidden' => 0,
				'logable' => 1,
				'delivery' => 0,
				'shipped' => 0,
				'paid' => 0,
				'deleted' => 0
		);
		if (! Db::getInstance()->autoExecute(_DB_PREFIX_ . 'order_state', $values_to_insert, 'INSERT'))
			return false;
			$id_order_state = (int) Db::getInstance()->Insert_ID();
			$languages = Language::getLanguages(false);
			foreach ($languages as $language)
				Db::getInstance()->autoExecute(_DB_PREFIX_ . 'order_state_lang', array(
						'id_order_state' => $id_order_state,
						'id_lang' => $language['id_lang'],
						'name' => $this->l('ComproPago - Pending Payment'),
						'template' => ''
				), 'INSERT');
				Configuration::updateValue('COMPROPAGO_PENDING', $id_order_state);
				unset($id_order_state);
		
				
				
		$values_to_insert = array(
				'invoice' => 0,
				'send_email' => 0,
				'module_name' => pSQL($this->name),
				'color' => 'RoyalBlue',
				'unremovable' => 0,
				'hidden' => 0,
				'logable' => 1,
				'delivery' => 0,
				'shipped' => 0,
				'paid' => 1,
				'deleted' => 0
		);
		if (! Db::getInstance()->autoExecute(_DB_PREFIX_ . 'order_state', $values_to_insert, 'INSERT'))
			return false;
			$id_order_state = (int) Db::getInstance()->Insert_ID();
			$languages = Language::getLanguages(false);
			foreach ($languages as $language)
				Db::getInstance()->autoExecute(_DB_PREFIX_ . 'order_state_lang', array(
						'id_order_state' => $id_order_state,
						'id_lang' => $language['id_lang'],
						'name' => $this->l('ComproPago - Payment received'),
						'template' => ''
				), 'INSERT');
				Configuration::updateValue('COMPROPAGO_SUCCESS', $id_order_state);
				unset($id_order_state);
				
			$values_to_insert = array(
					'invoice' => 0,
					'send_email' => 0,
					'module_name' => pSQL($this->name),
					'color' => 'RoyalBlue',
					'unremovable' => 0,
					'hidden' => 0,
					'logable' => 1,
					'delivery' => 0,
					'shipped' => 0,
					'paid' => 0,
					'deleted' => 0
			);
			if (! Db::getInstance()->autoExecute(_DB_PREFIX_ . 'order_state', $values_to_insert, 'INSERT'))
				return false;
				$id_order_state = (int) Db::getInstance()->Insert_ID();
				$languages = Language::getLanguages(false);
				foreach ($languages as $language)
					Db::getInstance()->autoExecute(_DB_PREFIX_ . 'order_state_lang', array(
							'id_order_state' => $id_order_state,
							'id_lang' => $language['id_lang'],
							'name' => $this->l('ComproPago - Expired'),
							'template' => ''
					), 'INSERT');
					Configuration::updateValue('COMPROPAGO_EXPIRED', $id_order_state);
					unset($id_order_state);

			$values_to_insert = array(
					'invoice' => 0,
					'send_email' => 0,
					'module_name' => pSQL($this->name),
					'color' => 'RoyalBlue',
					'unremovable' => 0,
					'hidden' => 0,
					'logable' => 1,
					'delivery' => 0,
					'shipped' => 0,
					'paid' => 0,
					'deleted' => 0
			);
			if (! Db::getInstance()->autoExecute(_DB_PREFIX_ . 'order_state', $values_to_insert, 'INSERT'))
				return false;
				$id_order_state = (int) Db::getInstance()->Insert_ID();
				$languages = Language::getLanguages(false);
				foreach ($languages as $language)
					Db::getInstance()->autoExecute(_DB_PREFIX_ . 'order_state_lang', array(
							'id_order_state' => $id_order_state,
							'id_lang' => $language['id_lang'],
							'name' => $this->l('ComproPago - Declined'),
							'template' => ''
					), 'INSERT');
					Configuration::updateValue('COMPROPAGO_DECLINED', $id_order_state);
					unset($id_order_state);
			
			$values_to_insert = array(
					'invoice' => 0,
					'send_email' => 0,
					'module_name' => pSQL($this->name),
					'color' => 'RoyalBlue',
					'unremovable' => 0,
					'hidden' => 0,
					'logable' => 1,
					'delivery' => 0,
					'shipped' => 0,
					'paid' => 0,
					'deleted' => 0
			);
			if (! Db::getInstance()->autoExecute(_DB_PREFIX_ . 'order_state', $values_to_insert, 'INSERT'))
				return false;
				$id_order_state = (int) Db::getInstance()->Insert_ID();
				$languages = Language::getLanguages(false);
				foreach ($languages as $language)
					Db::getInstance()->autoExecute(_DB_PREFIX_ . 'order_state_lang', array(
							'id_order_state' => $id_order_state,
							'id_lang' => $language['id_lang'],
							'name' => $this->l('ComproPago - Deleted'),
							'template' => ''
					), 'INSERT');
					Configuration::updateValue('COMPROPAGO_DELETED', $id_order_state);
					unset($id_order_state);
			
			$values_to_insert = array(
					'invoice' => 0,
					'send_email' => 0,
					'module_name' => pSQL($this->name),
					'color' => 'RoyalBlue',
					'unremovable' => 0,
					'hidden' => 0,
					'logable' => 1,
					'delivery' => 0,
					'shipped' => 0,
					'paid' => 0,
					'deleted' => 0
			);
			if (! Db::getInstance()->autoExecute(_DB_PREFIX_ . 'order_state', $values_to_insert, 'INSERT'))
				return false;
				$id_order_state = (int) Db::getInstance()->Insert_ID();
				$languages = Language::getLanguages(false);
				foreach ($languages as $language)
					Db::getInstance()->autoExecute(_DB_PREFIX_ . 'order_state_lang', array(
							'id_order_state' => $id_order_state,
							'id_lang' => $language['id_lang'],
							'name' => $this->l('ComproPago - Canceled'),
							'template' => ''
					), 'INSERT');
					Configuration::updateValue('COMPROPAGO_CANCELED', $id_order_state);
					unset($id_order_state);
						
	}
	/**
	 * Uninstall Module
	 * @return boolean
	 * @since 2.0.0
	 */
	public function uninstall()
	{
		if (!Configuration::deleteByName('COMPROPAGO_PUBLICKEY') || !Configuration::deleteByName('COMPROPAGO_PRIVATEKEY') 
		 || !Configuration::deleteByName('COMPROPAGO_MODE')  || ! Configuration::deleteByName('COMPROPAGO_PENDING') 
		 || ! Configuration::deleteByName('COMPROPAGO_SUCCESS') || ! Configuration::deleteByName('COMPROPAGO_EXPIRED') 
		 || ! Configuration::deleteByName('COMPROPAGO_DECLINED') ||	!parent::uninstall())
			return false;
		return true;
	}
	/**
	 * Validate module config form 
	 * @since 2.0.0
	 */
	private function _postValidation()
	{
		if (Tools::isSubmit('btnSubmit'))
		{
			if (!Tools::getValue('COMPROPAGO_PUBLICKEY')){
				$this->_postErrors[] = $this->l('The Public Key is required');
			}elseif (!Tools::getValue('COMPROPAGO_PRIVATEKEY')){
				$this->_postErrors[] = $this->l('The Private Key is required');
			}
			
		}
	}
	/**
	 *Refresh configed data after module config updated
	 * @since 2.0.0
	 */
	private function _postProcess()
	{
		if (Tools::isSubmit('btnSubmit'))
		{
			Configuration::updateValue('COMPROPAGO_PUBLICKEY', Tools::getValue('COMPROPAGO_PUBLICKEY'));
			Configuration::updateValue('COMPROPAGO_PRIVATEKEY', Tools::getValue('COMPROPAGO_PRIVATEKEY'));
			Configuration::updateValue('COMPROPAGO_MODE', Tools::getValue('COMPROPAGO_MODE'));
			Configuration::updateValue('COMPROPAGO_LOGOS', Tools::getValue('COMPROPAGO_LOGOS'));
		}
		$this->_html .= $this->displayConfirmation($this->l('Settings updated'));
	}
	/**
	 * Display Compropago description TPL at module configuration
	 * @since 2.0.0
	 */
	private function _displayCompropago()
	{
		return $this->display(__FILE__, 'infos.tpl');
	}
	/**
	 * Show Errors & load description, and after submit information at admin configuration page
	 * @return html
	 * @since 2.0.0
	 */
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
	/**
	 * Show Compropago as checkout payment method 
	 * display front end description
	 * @param unknown $params
	 * @since 2.0.0
	 */
	public function hookPayment($params)
	{
		if (!$this->active)
			return;
		if (!$this->checkCurrency($params['cart']))
			return;
		//if ComproPago is not a valid method for the operation disable it
		if(!$this->checkCompropago())
			return;

		$this->smarty->assign(array(
			'this_path' => $this->_path,
			'this_path_compropago' => $this->_path,
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/'
		));
		return $this->display(__FILE__, 'payment.tpl');
	}
	
	/**
	 * Hook Compropago 
	 * @param unknown $params
	 * @return void|array options
	 * @since 2.0.0
	 */
	public function hookDisplayPaymentEU($params)
	{
		if (!$this->active)
			return;
		if (!$this->checkCurrency($params['cart']))
			return;
		//if ComproPago is not a valid method for the operation disable it
		if(!$this->checkCompropago())
			return;

		$payment_options = array(
			'cta_text' => $this->l('Pay by ComproPago'),
			'logo' => Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/logo.png'),
			'action' => $this->context->link->getModuleLink($this->name, 'validation', array(), true)
		);

		return $payment_options;
	}
	/**
	 * After payment options selected
	 * @param unknown $params
	 * @return html
	 * @since 2.0.0
	 */
	public function hookPaymentReturn($params)
	{
		if (!$this->active)
			return;
		if(!$this->checkCompropago())
			return;
		
		$state = $params['objOrder']->getCurrentState();
		//COMPROPAGO Get order details
		
		if( !isset($_REQUEST['compropagoId']) || !isset($_REQUEST['id_cart']) || !isset($_REQUEST['id_order']) 
		|| empty($_REQUEST['compropagoId']) || empty($_REQUEST['id_cart']) || empty($_REQUEST['id_order']) ){
			$compropagoStatus='fail';
			$compropagoData=null;
		}else{
			$compropagoStatus='ok';
			//$compropagoData=$params;
			try{

				$sql = "SELECT * FROM "._DB_PREFIX_."compropago_orders	WHERE storeOrderId = '".$_REQUEST['id_order']."' AND  storeCartId = '".$_REQUEST['id_cart']."' AND compropagoId = '".$_REQUEST['compropagoId']."' AND storeExtra='COMPROPAGO_PENDING'";
				
				if ($row = Db::getInstance()->getRow($sql)){
					$compropagoData=json_decode(base64_decode($row['ioIn']));
				}
	
				//recheck vs ComproPago
				//$compropagoData=$this->compropagoService->verifyOrder($_REQUEST['compropagoId']);
			}catch (Exception $e){
				$compropagoData->status='exception';
				$compropagoData->exception=$e->getMessage();
				//throw new PrestaShopException
			}
			if($compropagoData->type=='error'){
				$compropagoData->status='error';		
			}
			switch ($compropagoData->status){
				case 'error':
					$compropagoTpl=$this->getViewPathCompropago('raw');
					$compropagoData->compropagoId=$_REQUEST['compropagoId'];
					$compropagoData->Help=$this->l('We have noticed that there is a problem with your order. If you think this is an error, you can contact our').
															' '.$this->l('customer service department.');
				break;
				case 'exception':
					$compropagoTpl=$this->getViewPathCompropago('raw');
					$compropagoData->Help=$this->l('We have noticed that there is a problem with your order. If you think this is an error, you can contact our').
					' '.$this->l('customer service department.');
				break;
				case 'pending':
					$compropagoData->exp_date=date('d-m-Y',$compropagoData->exp_date);
					$compropagoTpl=$this->getViewPathCompropago('receipt');
					
					
				break;
				default:
					$compropagoTpl=$this->getViewPathCompropago('raw');
					$compropagoStatus='fail';
					$compropagoData=null;
			}
			
			
		}
		
		
		if (in_array($state, array(Configuration::get('COMPROPAGO_PENDING'), Configuration::get('PS_OS_OUTOFSTOCK'), Configuration::get('PS_OS_OUTOFSTOCK_UNPAID'))))
		{
			$this->smarty->assign(array(
				'total_to_pay' => Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false),
				'status' => $compropagoStatus,
				'id_order' => $params['objOrder']->id,
				'compropagoReceiptLink'=>$this->l('Click Here to view full ComproPago Receipt Details'),
				'compropagoOrderTitle'=>$this->l('ComproPago Order Summary'),
				'compropagoDueDate'=>$this->l('Due Date'),
				'compropagoTpl' => $compropagoTpl,
				'compropagoData'=> $compropagoData
				
			));
			if (isset($params['objOrder']->reference) && !empty($params['objOrder']->reference))
				$this->smarty->assign('reference', $params['objOrder']->reference);
		}
		else
			$this->smarty->assign('status', 'failed');
		return $this->display(__FILE__, 'payment_return.tpl');
	}
	
	/**
	 * Check if currency is valid for the module
	 * @param unknown $cart
	 * @return boolean
	 * @since 2.0.0
	 */
	public function checkCurrency($cart)
	{
		//Compropago just accept  Mexican Peso as currency: MXN iso 484
		$currency_order = new Currency((int)($cart->id_currency));	
		if($currency_order->iso_code=='MXN')
			return true;
		
		return false; 		
	}
    /**
     * Config form for Admin configuration page
     * @return prestashop form helper
     * @since 2.0.0
     */
	public function renderForm()
	{
		$fields_form = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('ComproPago details'),
					'image' => '../modules/compropago/icono.png'
					//'icon' => 'icon-rocket'
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
							'type' => 'switch',
							'label' => $this->l('Live Mode'),
							'desc'      => $this->l('Are you on live or testing?,Change your Keys according to the mode').':<a href="https://compropago.com/panel/configuracion" target="_blank">'.$this->l('ComproPago Panel').'</a>',   
							'name' => 'COMPROPAGO_MODE',
							'is_bool' => true,
							'required' => true,
							'values' => array(
									array(
											'id' => 'active_on_bv',
											'value' => true,
											'label' => $this->l('Live Mode')
									),
									array(
											'id' => 'active_off_bv',
											'value' => false,
											'label' => $this->l('Testing Mode')
									)
							)
					),
					array(
							'type' =>'text',
							'label'=> $this->l('WebHook'),
							'name' => 'COMPROPAGO_WEBHOOK',
							'hint' => $this->l('Set this Url at ComproPago Panel to use it  to confirm to your store when a payment has been confirmed'),
							'desc' => $this->l('Copy & Paste this Url to WebHooks section of your ComproPago Panel to recive instant notifications when a payment is confirmed').':<a href="https://compropago.com/panel/webhooks" target="_blank">'.$this->l('ComproPago Panel').'</a>'			
					),
					array(
							'type' => 'switch',
							'label' => $this->l('Show Logos'),
							'desc'      => $this->l('Want to show store logos or a select box?'),
							'name' => 'COMPROPAGO_LOGOS',
							'is_bool' => true,
							'values' => array(
									array(
											'id' => 'active_on_lg',
											'value' => true,
											'label' => $this->l('Show store logos')
									),
									array(
											'id' => 'active_off_lg',
											'value' => false,
											'label' => $this->l('Show select box')
									)
							)
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
	/**
	 * get Module config array
	 * @return array
	 * @since 2.0.0
	 */
	public function getConfigFieldsValues()
	{
		return array(
			'COMPROPAGO_PUBLICKEY' => Tools::getValue('COMPROPAGO_PUBLICKEY', Configuration::get('COMPROPAGO_PUBLICKEY')),
			'COMPROPAGO_PRIVATEKEY' => Tools::getValue('COMPROPAGO_PRIVATEKEY', Configuration::get('COMPROPAGO_PRIVATEKEY')),
			'COMPROPAGO_MODE' => Tools::getValue('COMPROPAGO_MODE', Configuration::get('COMPROPAGO_MODE')),
			'COMPROPAGO_WEBHOOK' =>  Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/webhook.php',
			'COMPROPAGO_LOGOS' =>  Tools::getValue('COMPROPAGO_LOGOS', Configuration::get('COMPROPAGO_LOGOS')),
		);
	}
}
