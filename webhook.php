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
//valid request type??
$request = @file_get_contents('php://input');
if(!$jsonObj = json_decode($request)){
	die('Tipo de Request no Valido');
}
//Include prestashop files
$prestaFiles= array(
		dirname(__FILE__).'/../../config/config.inc.php',
		dirname(__FILE__).'/../../init.php',
		dirname(__FILE__).'/../../classes/PrestaShopLogger.php'
);
foreach($prestaFiles as $prestaFile){
	if(file_exists($prestaFile)){
		include_once	$prestaFile;
	}else{
		echo "ComproPago Warning: No se encontro el archivo de Prestashop:".$prestaFile."<br>";
	}
}
//prestashop Rdy?
if (!defined('_PS_VERSION_')){
	die("No se pudo inicializar Prestashop");
}
//include ComproPago SDK & dependecies via composer autoload
$compropagoComposer= dirname(__FILE__).'/vendor/autoload.php';
if ( file_exists( $compropagoComposer ) ){
	require $compropagoComposer;
}else{
	die('No se encontro el autoload para Compropago y sus dependencias:'.$compropagoComposer);
}
//Compropago Plugin Installed?
if (!Module::isInstalled('compropago')){
	die('El módulo de ComproPago no se encuentra instalado');
}
//Get ComproPago Prestashop Config values
$config = Configuration::getMultiple(array('COMPROPAGO_PUBLICKEY', 'COMPROPAGO_PRIVATEKEY','COMPROPAGO_MODE'));
//keys set?
if (!isset($config['COMPROPAGO_PUBLICKEY']) || !isset($config['COMPROPAGO_PRIVATEKEY'])
	|| empty($config['COMPROPAGO_PUBLICKEY']) || empty($config['COMPROPAGO_PRIVATEKEY'])){
	die("Se requieren las llaves de compropago");
}

//Compropago SDK config
if($config['COMPROPAGO_MODE']==true){
	$moduleLive=true;
}else {
	$moduleLive=false;
}
$compropagoConfig= array(
		'publickey'=>$config['COMPROPAGO_PUBLICKEY'],
		'privatekey'=>$config['COMPROPAGO_PRIVATEKEY'],
		'live'=>$moduleLive,
		'contained'=>'plugin; cpps 2.0.0; prestashop '._PS_VERSION_.'; webhook;'		
);
// consume sdk methods
try{
	$compropagoClient = new Compropago\Client($compropagoConfig);
	$compropagoService = new Compropago\Service($compropagoClient);
	// Valid Keys?
	if(!$compropagoResponse = $compropagoService->evalAuth()){
		die("ComproPago Error: Llaves no validas");
	}
	// Store Mode Vs ComproPago Mode, Keys vs Mode & combinations
	if(! Compropago\Utils\Store::validateGateway($compropagoClient)){
		die("ComproPago Error: La tienda no se encuentra en un modo de ejecución valido");
	}
}catch (Exception $e) {
	//something went wrong at sdk lvl
	die($e->getMessage());
}
//api normalization
if($jsonObj->api_version=='1.0'){
	$jsonObj->id=$jsonObj->data->object->id;
	$jsonObj->short_id=$jsonObj->data->object->short_id;  
}
//webhook Test?
if($jsonObj->id=="ch_00000-000-0000-000000" || $jsonObj->short_id =="000000"){
	die("Probando el WebHook?, ruta correcta.");
}
try{
	$response = $compropagoService->verifyOrder($jsonObj->id);
	if($response->type=='error'){
		die('Error procesando el número de orden');
	}
	if(!Db::getInstance()->execute("SHOW TABLES LIKE '"._DB_PREFIX_ ."compropago_orders'") ||
			!Db::getInstance()->execute("SHOW TABLES LIKE '"._DB_PREFIX_ ."compropago_transactions'")
			){
				die('ComproPago Tables Not Found');
	}
	switch ($response->type){
		case 'charge.success':
			$nomestatus = "COMPROPAGO_SUCCESS";
			break;
		case 'charge.pending':
			$nomestatus = "COMPROPAGO_PENDING";
			break;    
		case 'charge.declined':
			$nomestatus = "COMPROPAGO_DECLINED"; 
			break;    
		case 'charge.expired':
			$nomestatus = "COMPROPAGO_EXPIRED";
			break;    
	    case 'charge.deleted':
			$nomestatus = "COMPROPAGO_DELETED";     
			break; 
		case 'charge.canceled':
			$nomestatus = "COMPROPAGO_CANCELED";     
			break; 
		default:
			die('Invalid Response type');
	}
	
	$sql = "SELECT * FROM "._DB_PREFIX_."compropago_orders	WHERE compropagoId = '".$response->id."' ";
	
	if ($row = Db::getInstance()->getRow($sql)){
		
		$id_order=intval($row['storeOrderId']);
		$recordTime=time();
		
		$extraVars = array();
		$history = new OrderHistory();
		$history->id_order = $id_order;
		$history->changeIdOrderState((int)Configuration::get($nomestatus),$history->id_order);
		//$history->addWithemail(true,$extraVars);
		$history->addWithemail();
		$history->save();
		
		$sql = "UPDATE `"._DB_PREFIX_."compropago_orders` 
				SET `modified` = '".$recordTime."', `compropagoStatus` = '".$response->type."', `storeExtra` = '".$nomestatus."' 
				 WHERE `id` = '".$row['id']."'";
		if(!Db::getInstance()->execute($sql)){
			die("Error Updating ComproPago Order Record at Store");
		}
		//bas64 cause prestashop db
		//webhook
		$ioIn=base64_encode(json_encode($jsonObj));
		//verify response
		$ioOut=base64_encode(json_encode($response));
		
		Db::getInstance()->autoExecute(_DB_PREFIX_ . 'compropago_transactions', array(
				'orderId' 			=> $row['id'],
				'date' 				=> $recordTime,
				'compropagoId'		=> $response->id,
				'compropagoStatus'	=> $response->type,
				'compropagoStatusLast'	=> $row['compropagoStatus'],
				'ioIn' 				=> $ioIn,
				'ioOut' 			=> $ioOut
		),'INSERT');
		
		echo('Orden '.$jsonObj->id.' Confirmada');
		
	}else{		
		die('El número de orden no se encontro en la tienda');
	}	
}catch (Exception $e){
	//something went wrong at sdk lvl
	die($e->getMessage());
}
			

