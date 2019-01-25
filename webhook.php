<?php
/**
 * ComproPago Prestashop WebHook
 * @author Rolando Lucio <rolando@compropago.com>
 * @since 2.0.0
 */

require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/../../config/config.inc.php';
require_once __DIR__.'/../../init.php';
require_once __DIR__.'/../../classes/PrestaShopLogger.php';
require_once __DIR__.'/../../classes/order/Order.php';
require_once __DIR__.'/../../classes/order/OrderHistory.php';
require_once __DIR__.'/../../classes/order/OrderState.php';

if (!defined('_PS_VERSION_')) {
    die(json_encode([
        "status"     => "error",
        "message"    => "No se pudo inicializar Prestashop",
        "short_id"   => null,
        "reference"  => null
    ]));
}

use CompropagoSdk\Client;

class CompropagoWebhook
{
    private $data;
    private $publicKey;
    private $privateKey;
    private $mode;
    private $db;

    public function __construct()
    {
        $this->data         = @file_get_contents('php://input');
        $this->publicKey    = Configuration::get('COMPROPAGO_PUBLICKEY');
        $this->privateKey   = Configuration::get('COMPROPAGO_PRIVATEKEY');
        $this->mode         = Configuration::get('COMPROPAGO_MODE');
        $this->db           = Db::getInstance();

    }

    /**
     * Main action of the webhook
     * @throws Exception
     */
    public function execute()
    {
        $this->validateRequest();
        $transaction = $this->validateFromStore($this->data->id);

        switch ($transaction['api_version']) {
            case '2':
                $this->withApi2($transaction);
                break;
            default:
                $this->withApi1($transaction);
                break;
        }
    }

    /**
     * Validate the content of the request
     * @throws Exception
     */
    private function validateRequest()
    {
        if (empty($this->data)) {
            $message = 'Invalid request: empty value';
            throw new \Exception($message);
        }

        $this->data = json_decode($this->data);

        if (!isset($this->data->id) || empty($this->data->id)) {
            $message = 'Invalid request: empty value';
            throw new \Exception($message);
        }
    }

    /**
     * Validate if the webhook request is a test request
     * @param string $reference
     * @return bool
     */
    private function isTestMode($reference)
    {
        return ($reference == '000000');
    }

    /**
     * validate if the order is in the store
     * @param string $compropagoId
     * @return array|bool|null|object
     * @throws Exception
     */
    private function validateFromStore($compropagoId)
    {
        $query = "SELECT * FROM " . _DB_PREFIX_ . "compropago_orders WHERE compropagoId = '{$compropagoId}'";

        $row = $this->db->getRow($query);

        if (empty($row) || count($row) < 1) {
            $message = 'Order not found';
            throw new \Exception($message);
        }

        return $row;
    }

    /**
     * Flow with API 1
     * @param $transaction
     * @throws Exception
     */
    private function withApi1($transaction)
    {
        if ($this->isTestMode($this->data->short_id)) {
            echo json_encode([
                "status"    => "success",
                "message"   => "OK - TEST -" . $this->data->type,
                "short_id"  => $this->data->short_id,
                "reference" => $this->data->order_info->order_id
            ]);
            return;
        }

        $client = new Client($this->publicKey, $this->privateKey, $this->mode);
        $verified = $client->api->verifyOrder($transaction['compropagoId']);

        $status = '';

        switch ($verified->type) {
            case 'charge.pending':
                $status = 'COMPROPAGO_PENDING';
                break;
            case 'charge.success':
                $status = 'COMPROPAGO_SUCCESS';
                break;
            case 'charge.expired':
                $status = 'COMPROPAGO_EXPIRED';
                break;
        }

        $order = $this->updateOrderStatus($verified->order_info->order_id, $status);

        $data = [
            "compropagoId" => $verified->id,
            "compropagoStatus" => $verified->type,
            "status" => $status,
            "id" => $order->id,
            "lastStatus" => $transaction['compropagoStatus']
        ];

        $this->addTransaction($data, $verified);

        echo json_encode([
            "status"    => "success",
            "message"   => "OK - " . $verified->type,
            "short_id"  => $verified->short_id,
            "reference" => $verified->order_info->order_id
        ]);
        return;
    }

    /**
     * Flow with API 2
     * @param array $transaction
     * @throws Exception
     */
    private function withApi2($transaction)
    {
        $shortId = isset($this->data->shortId) ? $this->data->shortId : $this->data->short_id;
        $status = isset($this->data->status) ? $this->data->status : $this->data->type;

        if ($this->isTestMode($shortId)) {
            echo json_encode([
                "status"    => "success",
                "message"   => "OK - TEST -" . $status,
                "short_id"  => $shortId,
                "reference" => ''
            ]);
            return;
        }

        $verified = $this->verifyOrder2($transaction['compropagoId']);

        $status = '';

        switch ($verified->status) {
            case 'PENDING':
                $status = 'COMPROPAGO_PENDING';
                break;
            case 'ACCEPTED':
                $status = 'COMPROPAGO_SUCCESS';
                break;
            case 'EXPIRED':
                $status = 'COMPROPAGO_EXPIRED';
                break;
        }

        $order = $this->updateOrderStatus($verified->product->id, $status);

        $data = [
            "compropagoId" => $verified->id,
            "compropagoStatus" => $verified->status,
            "status" => $status,
            "id" => $order->id,
            "lastStatus" => $transaction['compropagoStatus']
        ];

        $this->addTransaction($data, $verified);

        echo json_encode([
            "status"    => "success",
            "message"   => "OK - " . $verified->status,
            "short_id"  => $verified->shortId,
            "reference" => $verified->product->id
        ]);
        return;
    }

    /**
     * Update status of the order
     * @param string $orderId
     * @param string $status
     * @return Order
     */
    private function updateOrderStatus($orderId, $status)
    {
        $order   = new Order($orderId);
        $history = new OrderHistory();

        $history->id_order = (int)$order->id;
        $history->changeIdOrderState(
            (int)Configuration::get($status),
            (int)($order->id)
        );
        $history->addWithemail();
        $history->save();

        return $order;
    }

    /**
     * Add tranaction information
     * @param array $data
     * @param mixed $verified
     */
    private function addTransaction($data, $verified)
    {
        $recordTime = time();

        $tableOrders = _DB_PREFIX_ . "compropago_orders";
        $tableTransactions = _DB_PREFIX_ . "compropago_transactions";

        $where = "compropagoId = '{$data['compropagoId']}'";

        $this->db->update(
            $tableOrders,
            [
                "modified"          => $recordTime,
                "compropagoStatus"  => $data['compropagoStatus'],
                "storeExtra"        => $data['status']
            ],
            $where
        );

        $this->db->insert(
            $tableTransactions,
            [
                "orderId"               => $data['id'],
                "date"                  => $recordTime,
                "compropagoId"          => $data['compropagoId'],
                "compropagoStatus"      => $data['compropagoStatus'],
                "compropagoStatusLast"  => $data['lastStatus'],
                "ioIn"                  => base64_encode(serialize($this->data)),
                "ioOut"                 => base64_encode(serialize($verified))
            ]
        );
    }

    /**
     * @param $orderId
     * @return object
     * @throws Exception
     */
    private function verifyOrder2($orderId)
    {
        $url = 'https://api.compropago.com/v2/orders/' . $orderId;

        $auth = [
            "user" => $this->privateKey,
            "pass" => $this->publicKey
        ];

        $response = CompropagoSdk\Tools\Request::get($url, array(), $auth);

        if ($response->statusCode != 200) {
            $message = "Can't verify order";
            throw new \Exception($message);
        }

        $body = json_decode($response->body);

        return $body->data;
    }
}

try {
    header('Content-Type: application/json');
    $webhook = new CompropagoWebhook();
    $webhook->execute();
} catch (\Exception $e) {
    die(json_encode([
        "status"     => "error",
        "message"    => $e->getMessage(),
        "short_id"   => null,
        "reference"  => null
    ]));
}
