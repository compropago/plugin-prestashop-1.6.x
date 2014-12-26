<?php

include(dirname(__FILE__) . '/includes/compropago.php');

if (!defined('_PS_VERSION_'))
    exit;

class ComproPago extends PaymentModule {

    private $_html = '';
    private $_postErrors = array();
    public $currencies;
    public $_botoes = array('buy_now_mlb.gif');

    public function __construct() {
        $this->name = 'compropago';
        $this->tab = 'payments_gateways';
        $this->version = '1.0';
        $this->currencies = true;
        $this->currencies_mode = 'radio';
        $this->need_instance = 1;

        parent::__construct();

        $this->page = basename(__file__, '.php');
        $this->displayName = $this->l('ComproPago');
        $this->description = $this->l('Receive Payments throw ComproPago');
        $this->confirmUninstall = $this->l('Are you sure that want to delete your datas?');
        $this->textshowemail = $this->l('You must follow ComproPago rules to your shop be valid');
    }

    public function install() {

        if (!Configuration::get('compropago_STATUS_1')) {
            $this->create_states();
		}
        if (
			!parent::install()
			OR !Configuration::updateValue('compropago_PUBLIC_KEY', '')
			OR !Configuration::updateValue('compropago_SECRET_KEY', '')
			OR !$this->registerHook('displayHeader')
			OR !$this->registerHook('payment')
			OR !$this->registerHook('paymentReturn')
        ) {
            return false;
		}

        return true;
    }

    public function create_states() {
        $this->order_state = array(
            array('25bffd', '00100', 'ComproPago - Transacción en curso', ''),
            array('8bc954', '11110', 'ComproPago - Transacción completada', 'payment'),
            array('faee6e', '11110', 'ComproPago - Transacción cancelada', 'order_canceled'),
            array('faee6e', '11110', 'ComproPago - Transacción rechazada', 'payment_error')
        );
        $languages = Db::getInstance()->ExecuteS('
		SELECT `id_lang`, `iso_code`
		FROM `' . _DB_PREFIX_ . 'lang`
		');
		print_r($this->order_state);
        foreach ($this->order_state as $key => $value) {
            Db::getInstance()->Execute
                    ('
			INSERT INTO `' . _DB_PREFIX_ . 'order_state` 
			( `invoice`, `send_email`, `color`, `unremovable`, `logable`, `delivery`) 
			VALUES
			(' . $value[1][0] . ', ' . $value[1][1] . ', \'#' . $value[0] . '\', ' . $value[1][2] . ', ' . $value[1][3] . ', ' . $value[1][4] . ');
		    ');
            $sql = 'SELECT MAX(id_order_state) FROM ' . _DB_PREFIX_ . 'order_state';
            $this->figura = Db::getInstance()->getValue($sql);
            foreach ($languages as $language_atual) {
                Db::getInstance()->Execute
                        ('
			    INSERT INTO `' . _DB_PREFIX_ . 'order_state_lang` 
			    (`id_order_state`, `id_lang`, `name`, `template`)
			    VALUES
			    (' . $this->figura . ', ' . $language_atual['id_lang'] . ', \'' . $value[2] . '\', \'' . $value[3] . '\');
		        ');
            }
            $file = (dirname(__file__) . "/icons/$key.gif");
            $newfile = (dirname(dirname(dirname(__file__))) . "/img/os/$this->figura.gif");
            if (!copy($file, $newfile)) {
                return false;
            }
            Configuration::updateValue("compropago_STATUS_$key", $this->figura);
        }
        return true;
    }

    public function uninstall() {
        if(
			!Configuration::deleteByName('compropago_PUBLIC_KEY')
			OR !Configuration::deleteByName('compropago_SECRET_KEY')
            OR !parent::uninstall()
        ){
            return false;
        }
        return true;
    }

    public function getContent() {
        $this->_html = '<h2>ComproPago</h2>';
	
        if (isset($_POST['submitcompropago'])) {
            if (!sizeof($this->_postErrors)) {
		if (!empty($_POST['compropago_PUBLIC_KEY'])) {
                    Configuration::updateValue('compropago_PUBLIC_KEY', $_POST['compropago_PUBLIC_KEY']);
                }
		
                if (!empty($_POST['compropago_SECRET_KEY'])) {
                    Configuration::updateValue('compropago_SECRET_KEY', $_POST['compropago_SECRET_KEY']);
                }
		
                $this->displayConf();
            }
            else
                $this->displayErrors();
        }

        $this->displaycompropago();
        $this->displayFormSettingscompropago();
        return $this->_html;
    }

    public function displayConf() {
        $this->_html .= '
		<div class="conf confirm">
			' . $this->l('Configuraciones actualizadas') . '
		</div>';
    }

    public function displayErrors() {
        $nbErrors = sizeof($this->_postErrors);
        $this->_html .= '
		<div class="alert error">
			<h3>' . ($nbErrors > 1 ? $this->l('There are') : $this->l('There is')) . ' ' . $nbErrors . ' ' . ($nbErrors > 1 ? $this->l('errors') : $this->l('error')) . '</h3>
			<ol>';
        foreach ($this->_postErrors AS $error)
            $this->_html .= '<li>' . $error . '</li>';
        $this->_html .= '
			</ol>
		</div>';
    }

    public function displaycompropago() {
        $this->_html .= '<div style="float:left;width:100%;margin: 0 0 20px 0;">';
		$this->_html .= '<img src="'.__PS_BASE_URI__.'modules/compropago/images/logo.png" style="float:left; margin-right:15px;" />';
		$this->_html .= '</div>';
		$this->_html .= '<strong>' . $this->l('Instruccion para instalación de ComproPago.') . '</strong><br /><br />';
		$this->_html .= '<strong>' . $this->l('Agregar la llave privada y llave pública, esta se puede encontrar en el apartado de configuración dentro del panel de control de ComproPago.') . ' <a href="https://compropago.com/panel/configuracion" target="_blank">https://compropago.com/panel/configuracion</a></strong><br />';
		$this->_html .= '<br /><br /><br />';
    }

    
    public function displayFormSettingscompropago() {
	
	
	$conf = Configuration::getMultiple(
		array(
			'compropago_PUBLIC_KEY',
			'compropago_SECRET_KEY'
		)
	);

	$public_key = array_key_exists('compropago_PUBLIC_KEY', $_POST) ? $_POST['compropago_PUBLIC_KEY'] : (array_key_exists('compropago_PUBLIC_KEY', $conf) ? $conf['compropago_PUBLIC_KEY'] : '');
	$secret_key = array_key_exists('compropago_SECRET_KEY', $_POST) ? $_POST['compropago_SECRET_KEY'] : (array_key_exists('compropago_SECRET_KEY', $conf) ? $conf['compropago_SECRET_KEY'] : '');


        $this->_html .= '
		<form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
		<fieldset>
			<legend><img src="../img/admin/contact.gif" />' . $this->l('Configuraciones') . '</legend>
			
			<label>' . $this->l('Llave privada') . ':</label>
			<div class="margin-form"><input type="text" size="33" name="compropago_SECRET_KEY" value="' . $secret_key . '" /></div>
			<br />
			
			<label>' . $this->l('Llave pública') . ':</label>
			<div class="margin-form"><input type="text" size="33" name="compropago_PUBLIC_KEY" value="' . htmlentities($public_key, ENT_COMPAT, 'UTF-8') . '" /></div>
            <br />
			
			<center><input type="submit" name="submitcompropago" value="' . $this->l('Actualizar') . '" class="button" /></center>
		</fieldset>
		</form>';

        $this->_html .= '
		
		</center>
		</fieldset>
		</form>';
    }
    
    //STEP - Select type method payment
    public function hookPayment($params) {
	
        global $smarty;
		
		if (!$this->active)
			return;
	
	//Send variables to payment.tpl
        $smarty->assign(
		array(
		    'imgBtn' => __PS_BASE_URI__."modules/compropago/images/logo.png",
		    'imgBannerSelectPayment' => $this->getBannerSelectPayment(),
		    'this_path' => $this->_path, 'this_path_ssl' => (Configuration::get('PS_SSL_ENABLED') ?
		    'https://' : 'http://') . htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8') . __PS_BASE_URI__ . 'modules/' . $this->name . '/'
		)
	    );
	
        return $this->display(__file__, 'payment.tpl');
    }

    //STEP - Confirm method payment selected
    public function execPayment($cart) {

        global $cookie, $smarty;
        $invoiceAddress = new Address(intval($cart->id_address_invoice));
        $customerPag = new Customer(intval($cart->id_customer));
        $currencies = Currency::getCurrencies();
        $currencies_used = array();
        $currency = $this->getCurrency();
        $currencies = Currency::getCurrencies();
		
		$payment_types = array(
			'OXXO' => 'OXXO',
			'SEVEN_ELEVEN' => 'SEVEN ELEVEN',
			'EXTRA' => 'EXTRA',
			'SORIANA' => 'SORIANA',
			'CHEDRAUI' => 'CHEDRAUI',
			'COPPEL' => 'COPPEL',
			'FARMACIA_BENAVIDES' => 'FARMACIA BENAVIDES',
			'FARMACIA_ESQUIVAR' => 'FARMACIA ESQUIVAR'
		);
	
        foreach ($currencies as $key => $currency) {
            $smarty->assign(
				array(
					'payment_types' => $payment_types,
					'currency_default' => new Currency(Configuration::get('PS_CURRENCY_DEFAULT')),
					'currencies' => $currencies_used,
					'imgBanner' => $this->getBanner(),
					'currency_default' => new Currency(Configuration::get('PS_CURRENCY_DEFAULT')),
					'currencies' => $currencies_used,
					'total' => number_format(Tools::convertPrice($cart->getOrderTotal(true, 3), $currency), 2, '.', ''),
					'this_path_ssl' => (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://') . htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8') . __PS_BASE_URI__ . 'modules/' . $this->name . '/'
				)
			);
		}
        return $this->display(__file__, 'confirm.tpl');
    }

    //STEP - generate link to pay
    public function hookPaymentReturn($params) {
		global $cookie, $smarty;
		
		$payment_type = $_GET['payment_type'];
		
        // datos de cliente
        $customer = new Customer(intval($cookie->id_customer));
        $ArrayCliente = $customer->getFields();
	
        // dados del pedido
        $DadosOrder = new Order($params['objOrder']->id);
        $ArrayListaProdutos = $DadosOrder->getProducts();
        
	
        //Force format YYYY-DD-MMTH:i:s
        $date_creation_user = date('Y-m-d', strtotime($ArrayCliente['date_add'])) . "T" . date('H:i:s',strtotime($ArrayCliente['date_add']));
		$address_invoice = new Address(intval($params['cart']->id_address_invoice));
		
		$phone = $address_invoice->phone;
		$phone .= $phone == "" ? "" : "|";
		$phone .= $address_invoice->phone_mobile;
        
        //items
		$image_url = "";
        // genera Descripcion
        foreach ($ArrayListaProdutos as $info) {
        	//print_r($info);
            $item = array(
                $zb[] = '#'. $info['id_order'] .' - '. $info['product_name'] . ' - ' . $info['product_quantity']
            );
	    
			//get object image on product object
			$id_image = $info['image'];
	    
			// get Image by id
			if (sizeof($id_image) > 0) {
				$image = new Image($id_image->id_image);
				// get image full URL
				$image_url = _PS_BASE_URL_._THEME_PROD_DIR_.$image->getExistingImgPath().".".$image->image_format;
			}
		}
	
		$descripcion = implode(" + ", $zb);
		$item_price = number_format($params['total_to_pay'], 2, '.', '');
		$currency = new Currency($DadosOrder->id_currency);
		
		$request = array(
			'currency' => $currency->iso_code,
			'product_price' => round($item_price, 2),
			'product_name' => utf8_encode($descripcion),
			'product_id'=> $params['objOrder']->id,
			'image_url'=> $image_url,
			'customer_name'=> $ArrayCliente['firstname'] . ' ' . $ArrayCliente['lastname'],
			'customer_email'=> $ArrayCliente['email'],
			'customer_phone'=> $phone,
			'payment_type'=> $payment_type,
			'send_sms'=> false
		);
        
        //set back url
        $back_urls = array(
            "pending" => Configuration::get('compropago_URLPROCESS'),
            "success" => Configuration::get('compropago_URLSUCCESFULL')
        );
        
        
        //mount array pref
        $public_key = Configuration::get('compropago_PUBLIC_KEY');
        $secret_key = Configuration::get('compropago_SECRET_KEY');
		
        

		$cp = new CP ($public_key, $secret_key, $request);
		$preferenceResult = $cp->get_charge_request();
		
		$botton = '';
	
		$smarty->assign(array(
			'totalApagar' => Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false),
			'status' => 'ok',
			'seller_op_id' => $params['objOrder']->id,
			'secure_key' => $params['objOrder']->secure_key,
			'id_module' => $this->id,
			'formcompropago' => $botton,
			'imgBanner' => $this->getBanner(),
			
			'description' => $preferenceResult['response']['payment_instructions']['description'],
			'step_1' => $preferenceResult['response']['payment_instructions']['step_1'],
			'step_2' => $preferenceResult['response']['payment_instructions']['step_2'],
			'step_3' => $preferenceResult['response']['payment_instructions']['step_3'],
			'note_extra_comition' => $preferenceResult['response']['payment_instructions']['note_extra_comition'],
			'note_expiration_date' => $preferenceResult['response']['payment_instructions']['note_expiration_date'],
			'note_confirmation' => $preferenceResult['response']['payment_instructions']['note_confirmation'],
		));



        return $this->display(__file__, 'payment_return.tpl');
    }

    function hookHome($params) {
        include(dirname(__FILE__) . '/includes/retorno.php');
    }

    function getStatus($param) {
        global $cookie;

        $sql_status = Db::getInstance()->Execute
                ('
			SELECT `name`
			FROM `' . _DB_PREFIX_ . 'order_state_lang`
			WHERE `id_order_state` = ' . $param . '
			AND `id_lang` = ' . $cookie->id_lang . '
			
		');

        return mysql_result($sql_status, 0);
    }

    public function enviar($mailVars, $template, $assunto, $DisplayName, $idCustomer, $idLang, $CustMail, $TplDir) {
        Mail::Send(intval($idLang), $template, $assunto, $mailVars, $CustMail, null, null, null, null, null, $TplDir);
    }

    public function getUrlByMyOrder($myOrder) {

        $module = Module::getInstanceByName($myOrder->module);
        $pagina_qstring = __PS_BASE_URI__ . "order-confirmation.php?id_cart="
                . $myOrder->id_cart . "&id_module=" . $module->id . "&id_order="
                . $myOrder->id . "&key=" . $myOrder->secure_key;

        if ($_SERVER['HTTPS'] != "on")
            $protocolo = "http";

        else
            $protocolo = "https";

        $retorno = $protocolo . "://" . $_SERVER['SERVER_NAME'] . $pagina_qstring;
        return $retorno;
    }

	public function hookDisplayHeader($params){
	  	$this->context->controller->addCSS($this->_path.'css/compropago.css', 'all');
	}      
    public function getBannerSelectPayment(){
        $banner = '<img src="'.__PS_BASE_URI__.'modules/compropago/images/logo.png" alt="ComproPago - Medios de pago" title="ComproPago - Medios de pago" />';
		return $banner;
    }
    
    public function getBanner(){
        $banner = '<img src="'.__PS_BASE_URI__.'modules/compropago/images/compropago.png" alt="ComproPago - Medios de pago" title="ComproPago - Medios de pago" width="468" height="60"/>';
		return $banner;

    }
}

?>