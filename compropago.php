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

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__.'/vendor/autoload.php';




class Compropago extends PaymentModule
{
	private $_html = '';
	private $_postErrors = array();

    private $serviceFlag;

	public $publicKey;
	public $privateKey;
	public $extra_mail_vars;
	public $modoExec;
	public $showLogo;
	public $filterStores;

	public $client;

	/**
	 * set & get module config
	 * @since 2.0.0
	 */
	public function __construct()
	{
		//Current module version & config
		$this->version = ' 2.2.1.2';


		$this->name             = 'compropago';
		$this->tab              = 'payments_gateways';
		$this->author           = 'ComproPago';
		$this->controllers      = array('payment', 'validation');
		$this->is_eu_compatible = 1;

		//currencies setup
		$this->currencies      = true;
		$this->currencies_mode = 'checkbox';

		// have module been set
		$config = Configuration::getMultiple(array('COMPROPAGO_PUBLICKEY', 'COMPROPAGO_PRIVATEKEY', 'COMPROPAGO_MODE', 'COMPROPAGO_LOGOS','COMPROPAGO_PROVIDER'));

        if (isset($config['COMPROPAGO_PUBLICKEY'])) {
            $this->publicKey = $config['COMPROPAGO_PUBLICKEY'];
        }

		if (isset($config['COMPROPAGO_PRIVATEKEY'])) {
            $this->privateKey = $config['COMPROPAGO_PRIVATEKEY'];
        }

		$this->modoExec=(isset($config['COMPROPAGO_MODE']))?$config['COMPROPAGO_MODE']:false;
		$this->showLogo=(isset($config['COMPROPAGO_LOGOS']))?$config['COMPROPAGO_LOGOS']:false;

		//most load selected
		$this->filterStores = explode(',',$config['COMPROPAGO_PROVIDER'] );


		$this->bootstrap = true;

		parent::__construct();

		//about ComproPago Module
		$this->displayName      = $this->l('ComproPago');
		$this->description      = $this->l('This module allows you to accept payments in Mexico stores like OXXO, 7Eleven and More.');
		$this->confirmUninstall = $this->l('Are you sure you want to uninstall ComproPago?');

		// need some keys
		if (( !isset($this->publicKey) || !isset($this->privateKey) || empty($this->publicKey) || empty($this->privateKey) ) ){
			$this->warning = $this->l('The Public Key and Private Key must be configured before using this module.');
		}

        $this->serviceFlag = $this->setComproPago($this->modoExec);

   		$itsBE = null;

   	    // It's Back End?
        if($this->context->employee){
        	$itsBE = true;
        }


        if($itsBE){
            $hook_data = $this->hookRetro(true, $this->publicKey, $this->privateKey, $this->modoExec);

            if($hook_data[0]){
                $this->warning = $this->l($hook_data[1]);
            }
        }


		if (!count(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->l('No currency has been set for this module.');
        }

	}


    /**
     * Generacion de retro alimentacion de configuracion al guardar ;
     * necesita activarse en getcontent ... Evaluar
     * @return array
     * @since 2.0.2
     */
    public function hookRetro($enabled, $publickey, $privatekey, $live)
    {
        $error = array(
            false,
            '',
            'yes'
        );
        if($enabled){
            if(!empty($publickey) && !empty($privatekey) ){
                try{
                    $client = new CompropagoSdk\Client(
                        $publickey,
                        $privatekey,
                        $live
                    );
                    $compropagoResponse = CompropagoSdk\Tools\Validations::evalAuth($client);
                    //eval keys
                    if(!CompropagoSdk\Tools\Validations::validateGateway($client)){
                        $error[1] = 'Invalid Keys, The Public Key and Private Key must be valid before using this module.';
                        $error[0] = true;
                    }else{
                        if($compropagoResponse->mode_key != $compropagoResponse->livemode){
                            $error[1] = 'Your Keys and Your ComproPago account are set to different Modes.';
                            $error[0] = true;
                        }else{
                            if($live != $compropagoResponse->livemode){
                                $error[1] = 'Your Store and Your ComproPago account are set to different Modes.';
                                $error[0] = true;
                            }else{
                                if($live != $compropagoResponse->mode_key){
                                    $error[1] = 'ComproPago ALERT:Your Keys are for a different Mode.';
                                    $error[0] = true;
                                }else{
                                    if(!$compropagoResponse->mode_key && !$compropagoResponse->livemode){
                                        $error[1] = 'WARNING: ComproPago account is Running in TEST Mode, NO REAL OPERATIONS';
                                        $error[0] = true;
                                    }
                                }
                            }
                        }
                    }
                }catch (Exception $e) {
                    $error[2] = 'no';
                    $error[1] = $e->getMessage();
                    $error[0] = true;
                }
            }else{
                $error[1] = 'The Public Key and Private Key must be set before using ComproPago';
                $error[2] = 'no';
                $error[0] = true;
            }
        }else{
            $error[1] = 'ComproPago is not Enabled';
            $error[2] = 'no';
            $error[0] = true;
        }

        return $error;
    }



    /**
     * Config ComproPago SDK instance
     * @param boolean $moduleLive
     * @return boolean
     * @since 2.0.0
     */
	private function setComproPago($moduleLive)
	{
		try{
			$this->client = new CompropagoSdk\Client(
			    $this->publicKey,
                $this->privateKey,
                $moduleLive
                //'plugin; cpps '.$this->version.';prestashop '._PS_VERSION_.';'
            );
			return true;
		}catch (\Exception $e) {
			return false;
		}
	}



	/**
	 * Check against SDK if module is valid for use
	 * @return boolean
	 * @since 2.0.0
	 */
	public function checkCompropago()
	{
		try {
			return CompropagoSdk\Tools\Validations::validateGateway($this->client);
		}catch (\Exception $e) {
            return false;
		}
	}



	/**
	 * get Providers View Config
	 * @return mixed  Providers TPL config array
	 * @return false  on Exception
	 * @since 2.0.0
	 */
	public function getProvidersCompropago($limit = 0)
	{
		try{
			global $currency;
			$providers = $this->client->api->listProviders(true, $limit, $currency->iso_code);
			$default = explode(",", Configuration::get('COMPROPAGO_PROVIDER')); 
	        $f_providers = [];

	        foreach ($default as $def) {
	            foreach ($providers as $prov) {
	                if ($def == $prov->internal_name) {
	                    $f_providers [] = $prov;
	                }
	            }
	        }

	        if ($f_providers[0] == NULL){
	            $provflag = 0;
	            $f_providers = 0;
	        } else {
	            $provflag = 1;
	        }

            $compropagoData['providers']     = $f_providers;
            $compropagoData['flag']			 = $provflag;
			$compropagoData['show_logos']    = $this->showLogo;                              //(yes|no) logos or select
			$compropagoData['description']   = $this->l('ComproPago allows you to pay at Mexico stores like OXXO, 7Eleven and More.');  // Title to show
			$compropagoData['instrucciones'] = $this->l('Select a Store');    // Instructions text
			return $compropagoData;
		}catch (Exception $e) {
			return false;
		}
	}


	/**
	 * hook header options
	 * @param mixed $params
	 * @since 2.0.0
	 */
	public function hookDisplayHeader($params)
    {
		//add css
		//$this->context->controller->addCSS($this->_path.'vendor/compropago/php-sdk/assets/css/compropago.css', 'all');
        $this->context->controller->addCSS($this->_path.'specificAssest/cp-style.css', 'all');
		$this->context->controller->addCSS($this->_path.'specificAssest/ps-default.css', 'all');

        //add js
        $this->context->controller->addJS($this->_path.'specificAssest/ps-default.js', 'all');
        $this->context->controller->addJS($this->_path.'specificAssest/providers.js', 'all');
	}



	/**
	 * Install Module
	 * @return boolean
	 * @since 2.0.0
	 */
	public function install()
	{
		if (version_compare(phpversion(), '5.4.0', '<')) {
			return false;
		}

		if (Shop::isFeatureActive())
			Shop::setContext(Shop::CONTEXT_ALL);

		$this->installOrderStates();

		//Lets be sure compropago tables are gone
		$queries = CompropagoSdk\Extern\TransactTables::sqlDropTables(_DB_PREFIX_);

		foreach($queries as $drop){
			Db::getInstance()->execute($drop);
		}

		//creates compropago tables
		$queries=CompropagoSdk\Extern\TransactTables::sqlCreateTables(_DB_PREFIX_);

		foreach($queries as $create){
			if(!Db::getInstance()->execute($create)) {
                die('Unable to Create ComproPago Tables, module cant be installed');
            }
		}


		if (!parent::install() || !$this->registerHook('payment') || !$this->registerHook('displayPaymentEU') || !$this->registerHook('paymentReturn') || !$this->registerHook('displayHeader')) {
            return false;
        }

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

        $cp_order_states = [
            [
                'label' => 'ComproPago - Pending Payment',
                'value' => 'COMPROPAGO_PENDING'
            ],
            [
                'label' => 'ComproPago - Payment received',
                'value' => 'COMPROPAGO_SUCCESS'
            ],
            [
                'label' => 'ComproPago - Expired',
                'value' => 'COMPROPAGO_EXPIRED'
            ],
            [
                'label' => 'ComproPago - Declined',
                'value' => 'COMPROPAGO_DECLINED'
            ],
            [
                'label' => 'ComproPago - Deleted',
                'value' => 'COMPROPAGO_DELETED'
            ],
            [
                'label' => 'ComproPago - Canceled',
                'value' => 'COMPROPAGO_CANCELED'
            ],
        ];

		$values_to_insert = array(
			'invoice'     => 0,
			'send_email'  => 0,
			'module_name' => pSQL($this->name),
			'color'       => 'RoyalBlue',
			'unremovable' => 0,
			'hidden'      => 0,
			'logable'     => 1,
			'delivery'    => 0,
			'shipped'     => 0,
			'paid'        => 0,
			'deleted'     => 0
		);


        foreach ($cp_order_states as $state){
            if (! Db::getInstance()->autoExecute(_DB_PREFIX_ . 'order_state', $values_to_insert, 'INSERT')) {
                return false;
            }

            $id_order_state = (int) Db::getInstance()->Insert_ID();
            $languages = Language::getLanguages(false);

            foreach ($languages as $language) {
                Db::getInstance()->autoExecute(_DB_PREFIX_ . 'order_state_lang', array(
                    'id_order_state' => $id_order_state,
                    'id_lang'        => $language['id_lang'],
                    'name'           => $this->l($state['label']),
                    'template'       => ''
                ), 'INSERT');
            }

            Configuration::updateValue($state['value'], $id_order_state);
            unset($id_order_state);
        }

	}




	/**
	 * Uninstall Module
	 * @return boolean
	 * @since 2.0.0
	 */
	public function uninstall()
	{
        //Lets be sure compropago tables are gone
        $queries = CompropagoSdk\Extern\TransactTables::sqlDropTables(_DB_PREFIX_);

        foreach($queries as $drop){
            Db::getInstance()->execute($drop);
        }

		if (!Configuration::deleteByName('COMPROPAGO_PUBLICKEY')
            || !Configuration::deleteByName('COMPROPAGO_PRIVATEKEY')
            || !Configuration::deleteByName('COMPROPAGO_MODE')
            || !Configuration::deleteByName('COMPROPAGO_WEBHOOK')
            || !Configuration::deleteByName('COMPROPAGO_PENDING')
            || !Configuration::deleteByName('COMPROPAGO_SUCCESS')
            || !Configuration::deleteByName('COMPROPAGO_EXPIRED')
            || !Configuration::deleteByName('COMPROPAGO_DECLINED')
            || !parent::uninstall()
        ) {
            return false;
        }

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
			/**
			 * Update values at database from form values Tools::getValue(form_field)
			 */
			Configuration::updateValue('COMPROPAGO_PUBLICKEY', Tools::getValue('COMPROPAGO_PUBLICKEY'));
			Configuration::updateValue('COMPROPAGO_PRIVATEKEY', Tools::getValue('COMPROPAGO_PRIVATEKEY'));
			Configuration::updateValue('COMPROPAGO_MODE', Tools::getValue('COMPROPAGO_MODE'));
			Configuration::updateValue('COMPROPAGO_LOGOS', Tools::getValue('COMPROPAGO_LOGOS'));
			$myproviders=implode(',',Tools::getValue('COMPROPAGO_PROVIDERS_selected'));
			Configuration::updateValue('COMPROPAGO_PROVIDER',$myproviders );
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
	 * @return mixed
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
     * @param mixed $params
     * @since 2.0.0
     * @return bool
     */
	public function hookPayment($params)
	{
		if (!$this->active) {
            return false;
        }
		if (!$this->checkCurrency($params['cart'])){
			return false;
        }

		if(!$this->checkCompropago()) {
            return false;
        }

		$this->smarty->assign(array(
			'this_path' => $this->_path,
			'this_path_compropago' => $this->_path,
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/'
		));

		return $this->display(__FILE__, 'payment.tpl');
	}



	/**
	 * Hook Compropago
	 * @param mixed $params
	 * @return mixed
	 * @since 2.0.0
	 */
	public function hookDisplayPaymentEU($params)
	{
		if (!$this->active) {
            return false;
        }

		if (!$this->checkCurrency($params['cart'])) {
            return false;
        }

		if(!$this->checkCompropago()) {
            return false;
        }

		$payment_options = array(
			'cta_text' => $this->l('Pay by ComproPago'),
			'logo' => Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/logo.png'),
			'action' => $this->context->link->getModuleLink($this->name, 'validation', array(), true)
		);

		return $payment_options;
	}



	/**
	 * After payment options selected
	 * @param mixed $params
	 * @return mixed
	 * @since 2.0.0
	 */
	public function hookPaymentReturn($params)
	{
		if (!$this->active) {
            return false;
        }

		if(!$this->checkCompropago()) {
            return false;
        }

		$state = $params['objOrder']->getCurrentState();

		if( !isset($_REQUEST['compropagoId']) || !isset($_REQUEST['id_cart']) || !isset($_REQUEST['id_order'])
		|| empty($_REQUEST['compropagoId']) || empty($_REQUEST['id_cart']) || empty($_REQUEST['id_order']) ){
			$compropagoStatus = 'fail';
		}else{
			$compropagoStatus = 'ok';
		}


		if (in_array($state, array(Configuration::get('COMPROPAGO_PENDING'), Configuration::get('PS_OS_OUTOFSTOCK'), Configuration::get('PS_OS_OUTOFSTOCK_UNPAID'))))
		{
			$this->smarty->assign(array(
				'total_to_pay'          => Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false),
				'status'                => $compropagoStatus,
				'id_order'              => $params['objOrder']->id,
				'order_id'              => $_REQUEST['compropagoId'],
			));

			if (isset($params['objOrder']->reference) && !empty($params['objOrder']->reference)) {
                $this->smarty->assign('reference', $params['objOrder']->reference);
            }
		}else{
            $this->smarty->assign('status', 'failed');
        }

		return $this->display(__FILE__, 'payment_return.tpl');
	}



	/**
	 * Check if currency is valid for the module
	 * @param mixed $cart
	 * @return boolean
	 * @since 2.0.0
	 */
	public function checkCurrency($cart)
	{
		//Compropago just accept  Mexican Peso as currency: MXN iso 484
		$currency_order = new Currency((int)($cart->id_currency));
//Habilitar las monedas soportadas
  	if($currency_order->iso_code=='MXN' || $currency_order->iso_code=='USD' || $currency_order->iso_code=='EUR' || $currency_order->iso_code=='GBP')
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
        $providers = $this->client->api->listProviders();
		$oxxo[] = [
				'id_option' => "OXXO",
				'name' => "Oxxo"
			];
        $options = [];
		$flag = false;
        foreach ($providers as $provider){
            $options[] = [
                'id_option' => $provider->internal_name,
                'name'      => $provider->name
            ];

		if($provider->internal_name == "OXXO"){$flag = true;}
        
		}
		
		if(!$flag){
			$options = array_merge($oxxo,$options);		
			}


        global $smarty;
        $base_url = $smarty->tpl_vars['base_dir']->value;


		$fields_form = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('ComproPago details'),
					'image' => '../modules/compropago/icono.png'
				),
				'input' => array(
					array(
						'type'     => 'text',
						'label'    => $this->l('Public Key'),
						'name'     => 'COMPROPAGO_PUBLICKEY',
						'required' => true
					),
					array(
						'type'     => 'text',
						'label'    => $this->l('Private Key'),
						'desc'     => $this->l('Get your keys at ComproPago').': <a href="https://compropago.com/panel/configuracion" target="_blank">'.$this->l('ComproPago Panel').'</a>',
						'name'     => 'COMPROPAGO_PRIVATEKEY',
						'required' => true
					),
					array(
						'type'     => 'switch',
						'label'    => $this->l('Live Mode'),
						'desc'     => $this->l('Are you on live or testing?,Change your Keys according to the mode').':<a href="https://compropago.com/panel/configuracion" target="_blank">'.$this->l('ComproPago Panel').'</a>',
						'name'     => 'COMPROPAGO_MODE',
						'is_bool'  => true,
						'required' => true,
						'values'   => array(
							array(
								'id'    => 'active_on_bv',
								'value' => true,
								'label' => $this->l('Live Mode')
							),
							array(
								'id'    => 'active_off_bv',
								'value' => false,
								'label' => $this->l('Testing Mode')
							)
						)
					),
					array(
						'type'    => 'switch',
						'label'   => $this->l('Show Logos'),
						'desc'    => $this->l('Want to show store logos or a select box?'),
						'name'    => 'COMPROPAGO_LOGOS',
						'is_bool' => true,
						'values'  => array(
							array(
								'id'    => 'active_on_lg',
								'value' => true,
								'label' => $this->l('Show store logos')
							),
							array(
								'id'    => 'active_off_lg',
								'value' => false,
								'label' => $this->l('Show select box')
							)
						)
					),
                    array(
                        'type'  =>'text',
                        'label' => $this->l('WebHook'),
                        'name'  => 'COMPROPAGO_WEBHOOK',
                        'hint'  => $this->l('Set this Url at ComproPago Panel to use it  to confirm to your store when a payment has been confirmed'),
                        'desc'  => $this->l('Copy & Paste this Url to WebHooks section of your ComproPago Panel to recive instant notifications when a payment is confirmed').':<a href="https://compropago.com/panel/webhooks" target="_blank">'.$this->l('ComproPago Panel').'</a>',
                        'value' => $base_url
                    ),
					array(
				        'type'     => 'swap',
				        'multiple' => true,
				        'label'    => $this->l('Tiendas:'),
				        'desc'     => $this->l('Seleccione las tiendas'),
				        'name'     => 'COMPROPAGO_PROVIDERS',
				        'options'  => array(
				            'query' => $options, // $options contains the data itself.
				            'id'    => 'id_option', // The value of the 'id' key must be the same as the key for 'value' attribute of the <option> tag in each $options sub-array.
				            'name'  => 'name'     // The value of the 'name' key must be the same as the key for the text content of the <option> tag in each $options sub-array.
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
		$providersDB=explode(',',Configuration::get('COMPROPAGO_PROVIDER') );

		return array(
			'COMPROPAGO_PUBLICKEY' => Tools::getValue('COMPROPAGO_PUBLICKEY', Configuration::get('COMPROPAGO_PUBLICKEY')),
			'COMPROPAGO_PRIVATEKEY' => Tools::getValue('COMPROPAGO_PRIVATEKEY', Configuration::get('COMPROPAGO_PRIVATEKEY')),
			'COMPROPAGO_MODE' => Tools::getValue('COMPROPAGO_MODE', Configuration::get('COMPROPAGO_MODE')),
			'COMPROPAGO_WEBHOOK' =>  Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/webhook.php',
			'COMPROPAGO_LOGOS' =>  Tools::getValue('COMPROPAGO_LOGOS', Configuration::get('COMPROPAGO_LOGOS')),
			'COMPROPAGO_PROVIDERS' =>  Tools::getValue('COMPROPAGO_PROVIDERS_selected',$providersDB),
		);
	}
}
