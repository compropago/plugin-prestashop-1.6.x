<?php
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

    public $publicKey;
    public $privateKey;
    public $extra_mail_vars;
    public $modoExec;
    public $filterStores;

    public $client;

    /**
     * set & get module config
     * @since 2.0.0
     */
    public function __construct()
    {
        //Current module version & config
        $this->version          = '2.5.1.0';
        $this->name             = 'compropago';
        $this->tab              = 'payments_gateways';
        $this->author           = 'ComproPago';
        $this->controllers      = ['payment', 'validation'];
        $this->is_eu_compatible = 1;

        //currencies setup
        $this->currencies      = true;
        $this->currencies_mode = 'checkbox';

        // have module been set
        if (Tools::isSubmit('btnSubmit')) {
            $config = [
                'COMPROPAGO_PUBLICKEY'  => Tools::getValue('COMPROPAGO_PUBLICKEY'),
                'COMPROPAGO_PRIVATEKEY' => Tools::getValue('COMPROPAGO_PRIVATEKEY'),
                'COMPROPAGO_MODE'       => Tools::getValue('COMPROPAGO_MODE'),
                'COMPROPAGO_PROVIDER'   => Tools::getValue('COMPROPAGO_PROVIDER')
            ];
        } else {
            $config = Configuration::getMultiple([
                'COMPROPAGO_PUBLICKEY',
                'COMPROPAGO_PRIVATEKEY',
                'COMPROPAGO_MODE',
                'COMPROPAGO_PROVIDER'
            ]);
        }

        if (isset($config['COMPROPAGO_PUBLICKEY'])) {
            $this->publicKey = $config['COMPROPAGO_PUBLICKEY'];
        }

        if (isset($config['COMPROPAGO_PRIVATEKEY'])) {
            $this->privateKey = $config['COMPROPAGO_PRIVATEKEY'];
        }

        $this->modoExec=(isset($config['COMPROPAGO_MODE'])) ? $config['COMPROPAGO_MODE'] : false;

        //most load selected
        $this->filterStores = explode(',', $config['COMPROPAGO_PROVIDER'] );


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

        $this->setComproPago($this->modoExec);
        
        if ($this->context->employee) {
            $hook_data = $this->hookRetro(true, $this->publicKey, $this->privateKey, $this->modoExec);
            if ($hook_data[0]) {
                $this->warning = $this->l($hook_data[1]);
                $this->stop = $hook_data[2];
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
        $error = [false, '', 'yes'];

        if ($enabled) {
            if(!empty($publickey) && !empty($privatekey) ) {
                try {
                    $client = new CompropagoSdk\Client(
                        $publickey,
                        $privatekey,
                        $live
                    );
                    $compropagoResponse = CompropagoSdk\Tools\Validations::evalAuth($client);
                    //eval keys
                    if (!CompropagoSdk\Tools\Validations::validateGateway($client)) {
                        $error[1] = 'Llaves invalidas, la llave pública y privada deben ser válidas para usar este módulo.';
                        $error[0] = true;
                    } else {
                        if ($compropagoResponse->mode_key != $compropagoResponse->livemode) {
                            $error[1] = 'Tu tienda y tu cuenta de ComproPago se encuentran en diferentes modos.';
                            $error[0] = true;
                        } else {
                            if ($live != $compropagoResponse->livemode) {
                                $error[1] = 'Tu tienda y tu cuenta de ComproPago se encuentran en diferentes modos.';
                                $error[0] = true;
                            } else {
                                if ($live != $compropagoResponse->mode_key) {
                                    $error[1] = 'Alarta: Tus llaves del API pertenecen a un modo distinto del de tu tienda.';
                                    $error[0] = true;
                                } else {
                                    if (!$compropagoResponse->mode_key && !$compropagoResponse->livemode) {
                                        $error[1] = 'Advertencia: La cuenta ComproPago se esta operando en Modo Pruebas. Ninguna de las operaciones es real.';
                                        $error[0] = true;
                                    }
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    $error[2] = 'no';
                    $error[1] = $e->getMessage();
                    $error[0] = true;
                }
            } else {
                $error[1] = 'The Public Key and Private Key must be set before using ComproPago';
                $error[2] = 'no';
                $error[0] = true;
            }
        } else {
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
        try {
            $this->client = new CompropagoSdk\Client(
                $this->publicKey,
                $this->privateKey,
                $moduleLive
            );
        } catch(\Exception $e) {
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
        } catch (\Exception $e) {
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
        try {
            global $currency;

            $providers = $this->client->api->listProviders($limit, $currency->iso_code);
            $default = explode(",", Configuration::get('COMPROPAGO_PROVIDER'));
            $f_providers = [];

            foreach ($default as $def) {
                foreach ($providers as $prov) {
                    if ($def == $prov->internal_name) {
                        $f_providers [] = $prov;
                    }
                }
            }

            if (isset($f_providers[0]) && ($f_providers[0] == NULL)) {
                $provflag = 0;
                $f_providers = 0;
            } else {
                $provflag = 1;
            }

            $compropagoData['providers']     = $f_providers;
            $compropagoData['flag']			 = $provflag;
            $compropagoData['description']   = $this->l('ComproPago te permite pagar en tiendas de México como OXXO, 7Eleven y más.');
            $compropagoData['instrucciones'] = $this->l('Selecciona una tienda');

            return $compropagoData;
        } catch (Exception $e) {
            return NULL;
        }
    }

    /**
     * hook header options
     * @param mixed $params
     * @since 2.0.0
     */
    public function hookDisplayHeader($params)
    {
        $assets_path = "{$this->_path}views/assets/";
        
        # Add CSS
        $this->context->controller->addCSS("{$assets_path}css/cp-style.css", 'all');
        $this->context->controller->addCSS("{$assets_path}css/ps-default.css", 'all');

        # Add JS
        $this->context->controller->addJS("{$assets_path}js/ps-default.js", 'all');
        $this->context->controller->addJS("{$assets_path}js/specificAssest/providers.js", 'all');
    }

    /**
     * Install Module
     * @return boolean
     * @since 2.0.0
     */
    public function install()
    {
        $this->installOrderStates();
        $this->sqlDropTables();
        $this->sqlCreateTables();

        if (!parent::install() || !$this->registerHook('payment') || ! $this->registerHook('displayPaymentEU') || !$this->registerHook('paymentReturn') || !$this->registerHook('displayHeader')) {
            return false;
        }

        return true;
    }

    /**
     * Vertify is compropago tables exists
     * @return boolean
     * @since 2.0.0
     */
    public function verifyTables() {
        if (
            !Db::getInstance()->execute("SHOW TABLES LIKE '" . _DB_PREFIX_ . "compropago_orders'") ||
            !Db::getInstance()->execute("SHOW TABLES LIKE '" . _DB_PREFIX_ . "compropago_transactions'")) {
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
        $cp_order_states = array(
            array(
                'label'		=>		'ComproPago - Pending Payment',
                'value' 	=> 		'COMPROPAGO_PENDING',
                'color' 	=> 		[
                    'invoice'     => 0,
                    'send_email'  => 0,
                    'module_name' => pSQL($this->name),
                    'color'       => '#FFFF7F',
                    'unremovable' => 0,
                    'hidden'      => 0,
                    'logable'     => 1,
                    'delivery'    => 0,
                    'shipped'     => 0,
                    'paid'        => 0,
                    'deleted'     => 0
                ]
            ),
            array(
                'label'		=>		'ComproPago - Payment received',
                'value' 	=> 		'COMPROPAGO_SUCCESS',
                'color' 	=> 		[
                    'invoice'     => 0,
                    'send_email'  => 0,
                    'module_name' => pSQL($this->name),
                    'color'       => '#CCFF00',
                    'unremovable' => 0,
                    'hidden'      => 0,
                    'logable'     => 1,
                    'delivery'    => 0,
                    'shipped'     => 0,
                    'paid'        => 0,
                    'deleted'     => 0
                ]
            ),
            array(
                'label'		=>		'ComproPago - Expired',
                'value' 	=> 		'COMPROPAGO_EXPIRED',
                'color' 	=> 		[
                    'invoice'     => 0,
                    'send_email'  => 0,
                    'module_name' => pSQL($this->name),
                    'color'       => '#FF3300',
                    'unremovable' => 0,
                    'hidden'      => 0,
                    'logable'     => 1,
                    'delivery'    => 0,
                    'shipped'     => 0,
                    'paid'        => 0,
                    'deleted'     => 0
                ]
            )
        );

        /*
         *
         * Now we need to iterate each state to accomplish the following points:
         * 1. Insert order state color
         * 2. Insert Compropago order states and attach state color using identifier
         */
        foreach ($cp_order_states as $state) {
            # Check if we can insert order state color
            if (! Db::getInstance()->autoExecute(_DB_PREFIX_ . 'order_state', $state['color'], 'INSERT')) {
                return false;
            }

            # Get ID and insert compropago order state.
            $id_order_state = (int) Db::getInstance()->Insert_ID();
            $languages      = Language::getLanguages(false);

            foreach ($languages as $language) {
                Db::getInstance()->autoExecute(_DB_PREFIX_ . 'order_state_lang', [
                    'id_order_state'    => $id_order_state,
                    'id_lang'           => $language['id_lang'],
                    'name'              => $this->l($state['label']),
                    'template'          => ''
                ], 'INSERT');
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
        $this->sqlDropTables();

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
            if (!Tools::getValue('COMPROPAGO_PUBLICKEY')) {
                $this->_postErrors[] = $this->l('The Public Key is required');
            } elseif (!Tools::getValue('COMPROPAGO_PRIVATEKEY')) {
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
            $myproviders=implode(',',Tools::getValue('COMPROPAGO_PROVIDERS_selected'));
            Configuration::updateValue('COMPROPAGO_PROVIDER',$myproviders );
        }

        if ($this->stop) {
            if (!Tools::getValue('COMPROPAGO_PUBLICKEY') && !Tools::getValue('COMPROPAGO_PRIVATEKEY')) {
                return false;
            } else {
                try {
                    $this->client->api->createWebhook(Tools::getValue('COMPROPAGO_WEBHOOK'));
                    $this->_html .= $this->displayConfirmation($this->l('Opciones actualizadas'));
                } catch (\Exception $e) {
                    if ( !in_array($e->getMessage(), ['Request error: 409', 'Error: conflict.urls.create']) ) {
                        $this->_html .= $this->displayError($e->getMessage());
                    }
                }
            }
            $this->_html .= $this->displayError($this->warning);
        } else {
            try {
                $this->client->api->createWebhook(Tools::getValue('COMPROPAGO_WEBHOOK'));
                $this->_html .= $this->displayConfirmation($this->l('Opciones actualizadas'));
            } catch (\Exception $e) {
                if ($e->getMessage() != 'Error: conflict.urls.create') {
                    $this->_html .= $this->displayError($e->getMessage());
                }
            }
        }
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
            if (!count($this->_postErrors)) {
                $this->_postProcess();
            } else {
                foreach ($this->_postErrors as $err) {
                    $this->_html .= $this->displayError($err);
                }
            }
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

        $this->smarty->assign([
            'this_path'             => $this->_path,
            'this_path_compropago'  => $this->_path,
            'this_path_ssl'         => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/'
        ]);

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

        $payment_options = [
            'cta_text'  => $this->l('Pay by ComproPago'),
            'logo'      => Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/views/assets/img/logo.png'),
            'action'    => $this->context->link->getModuleLink($this->name, 'validation', [], true)
        ];

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

        if( !isset($_REQUEST['compropagoId']) || !isset($_REQUEST['id_cart']) || !isset($_REQUEST['id_order']) || empty($_REQUEST['compropagoId']) || empty($_REQUEST['id_cart']) || empty($_REQUEST['id_order']) ){
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
        return in_array(
            $currency_order->iso_code,
            ["MXN", "USD", "EUR", "GBP"]
        );
    }

    /**
     * Config form for Admin configuration page
     * @return prestashop form helper
     * @since 2.0.0
     */
    public function renderForm()
    {
        try {
            if(!$this->publicKey && !$this->privateKey){
                $this->client = new CompropagoSdk\Client(
                    $this->publicKey,
                    $this->privateKey,
                    $this->modoExec
                );
                $providers = $this->client->api->listDefaultProviders();
            } else {
                $providers = $this->client->api->listProviders();
            }

            if (Configuration::get('COMPROPAGO_SUCCESS') == false
                || Configuration::get('COMPROPAGO_PENDING') == false
                || Configuration::get('COMPROPAGO_EXPIRED') == false)
            {
                $this->installOrderStates();
            }
            
            $options = [];
            $flag = false;
            foreach ($providers as $provider) {
                $options[] = [
                    'id_option' => $provider->internal_name,
                    'name'      => $provider->name
                ];
            }
            global $smarty;
            $base_url =  ( isset( $smarty->tpl_vars['base_dir']->value ) ) ? $smarty->tpl_vars['base_dir']->value : __DIR__;

            $fields_form = array(
                'form' => array(
                    'legend' => [
                        'title' => $this->l('Configuración'),
                        'image' => '../modules/compropago/views/assets/img/icon-config.png'
                    ],
                    'input' => array(
                        [
                            'type'     => 'text',
                            'label'    => $this->l('Public Key'),
                            'name'     => 'COMPROPAGO_PUBLICKEY',
                            'required' => true
                        ],
                        array(
                            'type'     => 'text',
                            'label'    => $this->l('Private Key'),
                            'desc'     => $this->l('Get your keys at ComproPago').': <a href="https://panel.compropago.com/panel/configuracion" target="_blank">'.$this->l('ComproPago Panel').'</a>',
                            'name'     => 'COMPROPAGO_PRIVATEKEY',
                            'required' => true
                        ),
                        array(
                            'type'     => 'hidden',
                            'name'     => 'COMPROPAGO_WEBHOOK',
                            'required' => false
                        ),
                        array(
                            'type'     => 'switch',
                            'label'    => $this->l('Live Mode'),
                            'desc'     => $this->l('Are you on live or testing?,Change your Keys according to the mode').':<a href="https://panel.compropago.com/panel/configuracion" target="_blank">'.$this->l('ComproPago Panel').'</a>',
                            'name'     => 'COMPROPAGO_MODE',
                            'is_bool'  => true,
                            'required' => true,
                            'values'   => [
                                [
                                    'id'    => 'active_on_bv',
                                    'value' => true,
                                    'label' => $this->l('Live Mode')
                                ],
                                [
                                    'id'    => 'active_off_bv',
                                    'value' => false,
                                    'label' => $this->l('Testing Mode')
                                ]
                            ]
                        ),
                        array(
                            'type'     => 'swap',
                            'multiple' => true,
                            'label'    => $this->l('Tiendas:'),
                            'desc'     => $this->l('Seleccione las tiendas donde desea procesar pagos.'),
                            'name'     => 'COMPROPAGO_PROVIDERS',
                            'options'  => array(
                                'query' => $options, // $options contains the data itself.
                                'id'    => 'id_option', // The value of the 'id' key must be the same as the key for 'value' attribute of the <option> tag in each $options sub-array.
                                'name'  => 'name'     // The value of the 'name' key must be the same as the key for the text content of the <option> tag in each $options sub-array.
                            )
                        )
                    ),
                    'submit' => array(
                        'title' => $this->l('Save'),
                    )
                )
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
            $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)."&configure={$this->name}&tab_module={$this->tab}&module_name={$this->name}";
            $helper->token = Tools::getAdminTokenLite('AdminModules');
            $helper->tpl_vars = array(
                'fields_value'  => $this->getConfigFieldsValues(),
                'languages'     => $this->context->controller->getLanguages(),
                'id_language'   => $this->context->language->id
            );

            return $helper->generateForm(array($fields_form));
        } catch (\Exception $e) {
            die("Error al generar el formulario" . $e->getMessage());
        }
    }

    /**
     * get Module config array
     * @return array
     * @since 2.0.0
     */
    public function getConfigFieldsValues()
    {
        $providersDB=explode(',',Configuration::get('COMPROPAGO_PROVIDER') );

        return [
            'COMPROPAGO_PUBLICKEY'  => Tools::getValue('COMPROPAGO_PUBLICKEY', Configuration::get('COMPROPAGO_PUBLICKEY')),
            'COMPROPAGO_PRIVATEKEY' => Tools::getValue('COMPROPAGO_PRIVATEKEY', Configuration::get('COMPROPAGO_PRIVATEKEY')),
            'COMPROPAGO_MODE'       => Tools::getValue('COMPROPAGO_MODE', Configuration::get('COMPROPAGO_MODE')),
            'COMPROPAGO_WEBHOOK'    => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/webhook.php',
            'COMPROPAGO_PROVIDERS'  => Tools::getValue('COMPROPAGO_PROVIDERS_selected',$providersDB)
        ];
    }

    /**
     * Delete ComproPago tables
     */
    private function sqlDropTables()
    {
        $query = 'DROP TABLE IF EXISTS ' . _DB_PREFIX_ . 'compropago_orders;';
        Db::getInstance()->execute($query);

        $query = 'DROP TABLE IF EXISTS ' . _DB_PREFIX_ . 'compropago_transactions;';
        Db::getInstance()->execute($query);
    }

    /**
     * Create ComproPago Tables
     */
    private function sqlCreateTables()
    {
        $query = "create table if not exists " . _DB_PREFIX_ . "compropago_orders (
              id int(11) not null auto_increment,
              date int(11) not null,
              modified int(11) not null,
              compropagoId varchar(50) not null,
              compropagoStatus varchar(50) not null,
              storeCartId varchar(255) not null,
              storeOrderId varchar(255) not null,
              storeExtra varchar(255) not null,
              ioIn mediumtext,
              ioOut mediumtext,
              api_version varchar(50),
              primary key (id),
              unique key (compropagoId)
            );";

        Db::getInstance()->execute($query);

        $query = "create table if not exists " . _DB_PREFIX_ . "compropago_transactions (
            id int(11) not null auto_increment,
            orderId int(11) not null,
            date int(11) not null,
            compropagoId varchar(50) not null,
            compropagoStatus varchar(50) not null,
            compropagoStatusLast varchar(50) not null,
            ioIn mediumtext,
            ioOut mediumtext,
            primary key (id)
        );";

        Db::getInstance()->execute($query);
    }
}
