<?php
	include_once('../../../config/config.inc.php');
	include_once('compropago.php');

	$body = @file_get_contents('php://input');
    $event_json = json_decode($body);


    $payment_cp_id=$event_json->data->object->{'id'};
    if ( isset($payment_cp_id) ) {
    	if($payment_cp_id == "00000-000-0000-000000"){
			echo "Pruebas: El webhook esta correctamente instalado. ";
    	}else{
			$id = $payment_cp_id;
			//$public_key = Db::getInstance()->getRow("SELECT value FROM "._DB_PREFIX_."configuration WHERE name = 'compropago_PUBLIC_KEY'");
			$secret_key = Db::getInstance()->getRow("SELECT value FROM "._DB_PREFIX_."configuration WHERE name = 'compropago_SECRET_KEY'");
			$cp = new CP ($secret_key['value'], $secret_key['value']);
			$cp->sandbox_mode($sandbox['value'] == "active" ? true:false);
			$dados = $cp->get_payment_info ($id);
			$dados = $dados['response'];
			$order_id = $dados['data']['object']['payment_details']['product_id'];
			$order_status = $dados["type"];

			switch ($order_status) {
				case 'charge.success':
					$nomestatus = "compropago_STATUS_1";
					break;
				case 'charge.pending':
					$nomestatus = "compropago_STATUS_0";
					break;    
				case 'charge.declined':
					$nomestatus = "compropago_STATUS_2"; 
					break;    
				case 'charge.expired':
					$nomestatus = "compropago_STATUS_2";
					break;    
				case 'charge.canceled':
					$nomestatus = "compropago_STATUS_2";     
					break;  
			}
			
			// Get Id StatusDb::getInstance()->getRow("SELECT value FROM "._DB_PREFIX_."configuration WHERE name = 'compropago_PUBLIC_ID'");
			$result = Db::getInstance()->getRow("SELECT value FROM "._DB_PREFIX_."configuration WHERE name = '".$nomestatus."'");
			$state = $result['value'];
			
			
			// Update order
			Db::getInstance()->Execute("INSERT INTO "._DB_PREFIX_."order_history (`id_employee`, `id_order`, `id_order_state`, `date_add`) VALUES ('0', '".$order_id."', '". $state . "', NOW())");
			
			
			// Send email
			$extraVars = array();
			$history = new OrderHistory();
			$history->id_order = intval($order_id);
			$history->changeIdOrderState(intval($state),intval($order_id));
			$history->addWithemail(true,$extraVars);
			
    	}
		
	    
	}else{
		echo "Error: El id proporcioando es nulo.";
	} 
      
        


?>
    