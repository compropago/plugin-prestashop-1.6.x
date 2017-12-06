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

if (!defined('_PS_VERSION_')){
    die("No se pudo inicializar Prestashop");
}

use CompropagoSdk\Client;
use CompropagoSdk\Factory\Factory;
use CompropagoSdk\Tools\Validations;

$request = @file_get_contents('php://input');

if(!$resp_webhook = Factory::getInstanceOf('CpOrderInfo', $request)){
    echo json_encode([
      "status" => "error",
      "message" => "invalid request",
      "short_id" => null,
      "reference" => null
    ]);
}

$config = Configuration::getMultiple(array('COMPROPAGO_PUBLICKEY', 'COMPROPAGO_PRIVATEKEY','COMPROPAGO_MODE'));
$publickey     = $config['COMPROPAGO_PUBLICKEY'];
$privatekey    = $config['COMPROPAGO_PRIVATEKEY'];
$live          = ($config['COMPROPAGO_MODE']==true);

try{
    $client = new Client($publickey, $privatekey, $live);
    Validations::validateGateway($client);
}catch (Exception $e) {
    die($e->getMessage());
}

try{
    $client = new Client($publickey, $privatekey, $live);

    if($resp_webhook->id == "ch_00000-000-0000-000000"){
        die(json_encode([
            "status" => "success",
            "message" => "test success",
            "short_id" => null,
            "reference" => null
            ]));
    }

   if(
       !Db::getInstance()->execute("SHOW TABLES LIKE '"._DB_PREFIX_ ."compropago_orders'") ||
       !Db::getInstance()->execute("SHOW TABLES LIKE '"._DB_PREFIX_ ."compropago_transactions'")
   ){
       die('ComproPago Tables Not Found');
   }

   $response = $client->api->verifyOrder($resp_webhook->id);
   
   $sql = "SELECT * FROM "._DB_PREFIX_."compropago_orders  WHERE compropagoId = '".$response->id."' ";
   if ($row = Db::getInstance()->getRow($sql)){
    switch ($response->type){
        case 'charge.success':
           $nomestatus = "COMPROPAGO_SUCCESS";
            break;
        case 'charge.pending':
           $nomestatus = "COMPROPAGO_PENDING";
            break;
        case 'charge.expired':
           $nomestatus = "COMPROPAGO_EXPIRED";
            break;
        default:
            echo json_encode([
              "status" => "error",
              "message" => "invalid request type",
              "short_id" => $response->id,
              "reference" => null
            ]);
            }
           $id_order   = intval($response->order_info->order_id);
           $recordTime = time();
   
           $order   = new Order($id_order);
           $history = new OrderHistory();
           $history->id_order = (int)$order->id;
           $history->changeIdOrderState((int)Configuration::get($nomestatus), (int)($order->id));
   
           $history->addWithemail();
           $history->save();
   
           $prefix = _DB_PREFIX_;
           $sql = "UPDATE `{$prefix}compropago_orders` SET `modified` = '$recordTime', `compropagoStatus` = '{$response->type}', `storeExtra` = '$nomestatus' WHERE `id` = '{$response->id}'";
   
           if(!Db::getInstance()->execute($sql)){
               die("Error Updating ComproPago Order Record at Store");
           }
   
           $ioIn  = base64_encode(serialize($resp_webhook));
           $ioOut = base64_encode(serialize($response));
   
           Db::getInstance()->autoExecute(_DB_PREFIX_ . 'compropago_transactions', array(
                'orderId' 			   => $row['id'],
                'date' 				   => $recordTime,
                'compropagoId'		   => $response->id,
                'compropagoStatus'	   => $response->type,
                'compropagoStatusLast' => $row['compropagoStatus'],
                'ioIn' 				   => $ioIn,
                'ioOut' 			   => $ioOut
            ),'INSERT');
   
           echo json_encode([
               "status" => "success",
               "message" => "OK",
               "short_id" => $response->id,
               "reference" => $id_order
           ]);
   
       }else{
           die('El número de orden no se encontro en la tienda');
       }
       
}catch (Exception $e) {
    echo json_encode([
      "status" => "error",
      "message" => $e->getMessage(),
      "short_id" => null,
      "reference" => null
    ]);
}