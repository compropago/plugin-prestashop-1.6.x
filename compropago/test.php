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

//include ComproPago SDK & dependecies
$compropagoComposer= dirname(__FILE__).'/vendor/autoload.php';
if ( file_exists( $compropagoComposer ) ){
	require $compropagoComposer;
}else{
	die('No se encontro el autoload para Compropago y sus dependencias:'.$compropagoComposer);
}

$compropagoConfig= array(
		'publickey'=>'pk_live_570a6884d69e263',
		'privatekey'=>'sk_live_7ff93be105c732dc',
		'live'=>true
);


try {
$compropagoClient= new Compropago\Client($compropagoConfig);

//$compropagoService= new Compropago\Service($compropagoClient);
//Val keys vs mode here


//$response=$compropagoService->evalAuth();
//$response=$compropagoService->getProviders();


//$data=array('order_total'=>20000);
//$response=Compropago\Http\Rest::doExecute($compropagoClient,'providers',$data);

$response = (Compropago\Controllers\Store::validateGateway($compropagoClient))? 'vdd':'neg';
//$response= Compropago\Controllers\Store::test('otro param');

}catch (Exception $e){
	die($e->getMessage());
}
	
//if($response['responseCode']=='401'){
//	die('aca proceso msj');
//}
//if($response['responseCode']=='200'){
//	$response=$response['responseBody'];
//}


echo '<pre>';
print_r($response);
echo '</pre>';
