<?php
/**
 * @author Rolando Lucio <rolando@compropago.com>
 * @since 2.0.0
 */

class CompropagoValidationModuleFrontController extends ModuleFrontController
{

    const PLUGIN_VERSION = "2.5.0.0";

	public function postProcess()
	{
		$cart = $this->context->cart;

		if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $authorized = false;

        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'compropago') {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            die($this->module->l('This payment method is not available.', 'validation'));
        }

        $customer = new Customer($cart->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $compropagoStore = (!isset($_REQUEST['compropagoProvider']) || empty($_REQUEST['compropagoProvider'])) ?
            'OXXO' : $_REQUEST['compropagoProvider'];

        $mailVars  = array('{compropago_msj}' => 'En breve recibirá un email de ComproPago con su orden de pago ');

        $this->module->validateOrder(
            (int)$cart->id,
            Configuration::get('COMPROPAGO_PENDING'),
            $cart->getOrderTotal(true, Cart::BOTH),
            $this->module->displayName,
            NULL,
            $mailVars,
            (int)$this->context->currency->id,
            false,
            $customer->secure_key
        );

        $cpId = null;

        try {
            if ($compropagoStore == 'SPEI') {
                $cpId = $this->proccessSpei();
            } else {
                $cpId = $this->proccessCash($compropagoStore);
            }
        } catch (Exception $e) {
            echo $e->getTraceAsString();
            die($this->module->l('This payment method is not available.', 'validation') . '<br>' . $e->getMessage());
        }

        $url = "index.php?controller=order-confirmation" .
            "&id_cart={$cart->id}" .
            "&id_module={$this->module->id}" .
            "&id_order={$this->module->currentOrder}" .
            "&compropagoId={$cpId}" .
            "&key={$customer->secure_key}";

        Tools::redirect($url);
        return;
	}

    /**
     * @throws Exception
     */
	private function proccessSpei()
    {
        $cart = $this->context->cart;
        $customer = new Customer($cart->id_customer);
        $orderName = 'Ref: ' . $this->module->currentOrder;

        $orderInfo = [
            "product" => [
                "id" => "{$this->module->currentOrder}",
                "price" => floatval($cart->getOrderTotal(true, Cart::BOTH)),
                "name" => $orderName,
                "url" => "",
                "currency" => $this->context->currency->iso_code
            ],
            "customer" => [
                "name" => $customer->firstname . ' ' . $customer->lastname,
                "email" => $customer->email,
                "phone" => ""
            ],
            "client" => [
                "name" => "prestashop",
                "version" => self::PLUGIN_VERSION
            ],
            "payment" =>  [
                "type" => "SPEI"
            ]
        ];

        $response = $this->speiRequest($orderInfo);

        if ($response->status != 'PENDING') {
            throw new \Exception($this->module->l('This payment method is not available.', 'validation'));
        }

        if (!$this->module->verifyTables()) {
            throw new \Exception($this->module->l('This payment method is not available.', 'validation'));
        }

        $this->addTransaction($cart, $response, $orderInfo, $response->status, 2);

        return $response->id;
    }

    /**
     * @param $data
     * @return string
     * @throws Exception
     */
    private function speiRequest($data)
    {
        $url = 'https://api.compropago.com/v2/orders';

        $auth = [
            "user" => Configuration::get('COMPROPAGO_PRIVATEKEY'),
            "pass" => Configuration::get('COMPROPAGO_PUBLICKEY')
        ];

        $response = CompropagoSdk\Tools\Request::post($url, $data, array(), $auth);

        if ($response->statusCode != 200) {
            throw new \Exception("SPEI Error #: {$response->statusCode}");
        }

        $body = json_decode($response->body);

        return $body->data;
    }

    /**
     * @param $store
     * @return string
     * @throws Exception
     */
	private function proccessCash($store)
    {
        $cart = $this->context->cart;
        $customer = new Customer($cart->id_customer);
        $orderName = 'Ref: ' . $this->module->currentOrder;

        $order_info = [
            'order_id' => $this->module->currentOrder,
            'order_name' => $orderName,
            'order_price' => $cart->getOrderTotal(true, Cart::BOTH),
            'customer_name' => $customer->firstname . ' ' . $customer->lastname,
            'customer_email' => $customer->email,
            'payment_type' => $store,
            'currency' => $this->context->currency->iso_code,
            'image_url' => null,
            'app_client_name' => 'prestashop',
            'app_client_version' => self::PLUGIN_VERSION
        ];

        $order = CompropagoSdk\Factory\Factory::getInstanceOf('PlaceOrderInfo', $order_info);

        $response = $this->module->client->api->placeOrder($order);

        echo json_encode($response) . "\n";

        if ($response->type != 'charge.pending') {
            echo "charge pending\n";
            throw new \Exception($this->module->l('This payment method is not available.', 'validation'));
        }

        if (!$this->module->verifyTables()) {
            echo "verify tables\n";
            throw new \Exception($this->module->l('This payment method is not available.', 'validation'));
        }

        $this->addTransaction($cart, $response, $order, $response->type, 1);

        return $response->id;
    }

    /**
     * @param $cart
     * @param $response
     * @param $order
     * @param $api
     */
    private function addTransaction($cart, $response, $order, $status, $api)
    {
        $recordTime = time();
        $ioIn       = base64_encode(serialize($response));
        $ioOut      = base64_encode(serialize($order));

        $type = 'INSERT';
        $tableOrders = _DB_PREFIX_ . 'compropago_orders';
        $tableTransactions = _DB_PREFIX_ . 'compropago_transactions';

        $insertOrder = array(
            'date'             => $recordTime,
            'modified'         => $recordTime,
            'compropagoId'     => $response->id,
            'compropagoStatus' => $status,
            'storeCartId'      => $cart->id,
            'storeOrderId'     => $this->module->currentOrder,
            'storeExtra'       => 'COMPROPAGO_PENDING',
            'ioIn'             => $ioIn,
            'ioOut'            => $ioOut,
            'api_version'      => $api
        );

        $insertTransaction = array(
            'orderId'              => $response->order_info->order_id,
            'date'                 => $recordTime,
            'compropagoId'         => $response->id,
            'compropagoStatus'     => $status,
            'compropagoStatusLast' => $status,
            'ioIn'                 => $ioIn,
            'ioOut'                => $ioOut
        );

        Db::getInstance()->autoExecute($tableOrders, $insertOrder, $type);
        Db::getInstance()->autoExecute($tableTransactions, $insertTransaction, $type);
    }
}