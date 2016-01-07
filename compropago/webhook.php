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
 */
//El request es valido?
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
//include ComproPago SDK & dependecies
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
//ComproPago Config Keys
$config = Configuration::getMultiple(array('COMPROPAGO_PUBLICKEY', 'COMPROPAGO_PRIVATEKEY'));
if (!isset($config['COMPROPAGO_PUBLICKEY']) || !isset($config['COMPROPAGO_PRIVATEKEY'])
	|| empty($config['COMPROPAGO_PUBLICKEY']) || empty($config['COMPROPAGO_PRIVATEKEY'])){
	die("Se requieren las llaves de compropago");
}
$compropagoConfig= array(
		'publickey'=>$config['COMPROPAGO_PUBLICKEY'],
		'privatekey'=>$config['COMPROPAGO_PRIVATEKEY']
		//'live'=>false
);

$compropagoClient= new Compropago\Client($compropagoConfig);
$compropagoService= new Compropago\Service($compropagoClient);
//Val keys vs mode here

$response=Compropago\Http\Rest::doExecute($compropagoClient,'users/auth/',$data);
//Ill check my self i dont belive


var_dump($response);
