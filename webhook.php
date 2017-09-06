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
 
 use CompropagoSdk\Factory\Factory;
 use CompropagoSdk\Client;
 use CompropagoSdk\Tools\Validations;
 
 $request = @file_get_contents('php://input');
 header('Content-Type: application/json');
 
 if(!$resp_webhook = Factory::getInstanceOf('CpOrderInfo', $request)){
     echo json_encode([
       "status" => "error",
       "message" => "invalid request",
       "short_id" => null,
       "reference" => null
     ]);
 }
 
 
 $publickey     = $config['COMPROPAGO_PUBLICKEY'];
 $privatekey    = $config['COMPROPAGO_PRIVATEKEY'];
 $live          = ($config['COMPROPAGO_MODE']==true);
 
 try{
     $client = new Client($publickey, $privatekey, $live );

     if($resp_webhook->short_id == "000000"){
         echo json_encode([
           "status" => "success",
           "message" => "test success",
           "short_id" => $resp_webhook->short_id,
           "reference" => null
         ]);
     }
 
     $response = $client->api->verifyOrder($resp_webhook->id);

     switch ($response->type){
         case 'charge.success':
             // TODO: Actions on success payment
             break;
         case 'charge.pending':
             // TODO: Actions on pending payment
             break;
         case 'charge.expired':
             // TODO: Actions on expired payment
             break;
         default:
             echo json_encode([
               "status" => "error",
               "message" => "invalid request type",
               "short_id" => $response->short_id,
               "reference" => null
             ]);
     }
 
     echo json_encode([
       "status" => "success",
       "message" => "OK",
       "short_id" => $response->short_id,
       "reference" => 'internal-1234'
     ]);
 }catch (Exception $e) {
     echo json_encode([
       "status" => "error",
       "message" => $e->getMessage(),
       "short_id" => $resp_webhook->short_id,
       "reference" => null
     ]);
 }




// if (!defined('_PS_VERSION_')){
//     die("No se pudo inicializar Prestashop");
// }


// /**
//  * Se hace la inclucion directa de las siguientes 3 clases necesarias para validar la peticion
//  */
// use CompropagoSdk\Factory\Factory;
// use CompropagoSdk\Client;
// use CompropagoSdk\Tools\Validations;


// /**
//  * Se captura la informacion enviada desde compropago
//  */
// $request = @file_get_contents('php://input');


// /**
//  * Se valida el request y se transforma con la cadena a un objeto de tipo CpOrderInfo con el Factory
//  */
// if(empty($request) || !$resp_webhook = Factory::getInstanceOf("CpOrderInfo",$request)){
//     die('Tipo de Request no Valido');
// }



// /**
//  * Get Prestashop Configuratión
//  */
// $config = Configuration::getMultiple(array('COMPROPAGO_PUBLICKEY', 'COMPROPAGO_PRIVATEKEY','COMPROPAGO_MODE'));



// /**
//  * Gurdamos la informacion necesaria para el Cliente
//  * las llaves de compropago y el modo de ejecucion de la tienda
//  */
// $publickey     = $config['COMPROPAGO_PUBLICKEY'];
// $privatekey    = $config['COMPROPAGO_PRIVATEKEY'];
// $live          = ($config['COMPROPAGO_MODE']==true);


// /**
//  * Se valida que las llaves no esten vacias (No es obligatorio pero si recomendado)
//  */
// //keys set?
// if (empty($publickey) || empty($privatekey)){
//     die("Se requieren las llaves de compropago");
// }


// try{
//     /**
//      * Se incializa el cliente
//      */
//     $client = new Client(
//         $publickey,
//         $privatekey,
//         $live
//         //'plugin; cpps 2.0.0; prestashop '._PS_VERSION_.'; webhook;'
//     );

//     /**
//      * Validamos que nuestro cliente pueda procesar informacion
//      */
//     Validations::validateGateway($client);
// }catch (Exception $e) {
//     //something went wrong at sdk lvl
//     die($e->getMessage());
// }


// /**
//  * Verificamos si recivimos una peticion de prueba
//  */
// if($resp_webhook->id=="ch_00000-000-0000-000000"){
//     die("Probando el WebHook?, Ruta correcta.");
// }

// try{
//     /**
//      * Verificamos la informacion del Webhook recivido
//      */
//     $response = $client->api->verifyOrder($resp_webhook->id);


//     /**
//      * Comprovamos que la verificacion fue exitosa
//      */
//     if($response->type == 'error'){
//         die('Error procesando el numero de orden');
//     }


//     /**
//      * Validate transaction tables
//      */
//     if(
//         !Db::getInstance()->execute("SHOW TABLES LIKE '"._DB_PREFIX_ ."compropago_orders'") ||
//         !Db::getInstance()->execute("SHOW TABLES LIKE '"._DB_PREFIX_ ."compropago_transactions'")
//     ){
//         die('ComproPago Tables Not Found');
//     }



//     $sql = "SELECT * FROM "._DB_PREFIX_."compropago_orders	WHERE compropagoId = '".$response->id."' ";


//     if ($row = Db::getInstance()->getRow($sql)){

//         /**
//          * Generamos las rutinas correspondientes para cada uno de los casos posible del webhook
//          */
//         switch ($response->type){
//             case 'charge.success':
//                 $nomestatus = "COMPROPAGO_SUCCESS";
//                 break;
//             case 'charge.pending':
//                 $nomestatus = "COMPROPAGO_PENDING";
//                 break;
//             case 'charge.declined':
//                 $nomestatus = "COMPROPAGO_DECLINED";
//                 break;
//             case 'charge.expired':
//                 $nomestatus = "COMPROPAGO_EXPIRED";
//                 break;
//             case 'charge.deleted':
//                 $nomestatus = "COMPROPAGO_DELETED";
//                 break;
//             case 'charge.canceled':
//                 $nomestatus = "COMPROPAGO_CANCELED";
//                 break;
//             default:
//                 die('Invalid Response type');
//         }


//         /**
//          * Cambio de estatus para las ordenes
//          */
//         $id_order   = intval($response->order_info->order_id);
//         $recordTime = time();

//         $order   = new Order($id_order);
//         $history = new OrderHistory();

//         $history->id_order = (int)$order->id;
//         $history->changeIdOrderState((int)Configuration::get($nomestatus), (int)($order->id));

//         $history->addWithemail();
//         $history->save();



//         /**
//          * Actualizacion de base de datos
//          */
//         $prefix = _DB_PREFIX_;
//         $sql = "UPDATE `{$prefix}compropago_orders` SET `modified` = '$recordTime', `compropagoStatus` = '{$response->type}', `storeExtra` = '$nomestatus' WHERE `id` = '{$response->id}'";

//         if(!Db::getInstance()->execute($sql)){
//             die("Error Updating ComproPago Order Record at Store");
//         }

//         $ioIn  = base64_encode(serialize($resp_webhook));
//         $ioOut = base64_encode(serialize($response));

//         Db::getInstance()->autoExecute(_DB_PREFIX_ . 'compropago_transactions', array(
//             'orderId' 			   => $row['id'],
//             'date' 				   => $recordTime,
//             'compropagoId'		   => $response->id,
//             'compropagoStatus'	   => $response->type,
//             'compropagoStatusLast' => $row['compropagoStatus'],
//             'ioIn' 				   => $ioIn,
//             'ioOut' 			   => $ioOut
//         ),'INSERT');

//         echo('Orden '.$resp_webhook->id.' Confirmada');

//     }else{
//         die('El número de orden no se encontro en la tienda');
//     }

// }catch (Exception $e){
//     //something went wrong at sdk lvl
//     die($e->getMessage());
// }
