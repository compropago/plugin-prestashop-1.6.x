<?php

/**
 * ComproPago Integration Library
 * Access ComproPago for payments integration
 *
 */
$GLOBALS["LIB_LOCATION"] = dirname(__FILE__);

class CP {

    const version = "1.0";

    private $public_key;
    private $secret_key;
	private $request;
	
    private $access_data;
    private $sandbox = FALSE;

    function __construct($public_key, $secret_key, $request) {
        $this->public_key = $public_key;
        $this->secret_key = $secret_key;
		$this->request = $request;
    }

    public function sandbox_mode($enable = NULL) {
        if (!is_null($enable)) {
            $this->sandbox = $enable === TRUE;
        }
        return $this->sandbox;
    }

    public function get_charge_request() {
    
        $headers = array('Accept: application/compropago+json',
             'Content-type: application/json'
        );
        $uri_prefix = $this->sandbox ? "/sandbox" : "";
        $access_data = CPRestClient::post($uri_prefix."/v1/charges", $this->public_key, $this->secret_key, $this->request, $headers);
        $this->access_data = $access_data['response'];

        return $access_data;
    }

    /**
     * Get Access Token for API use
     */
    public function get_access_token() {
	
		$headers = array('Accept: application/compropago+json',
			 'Content-type: application/json'
		);
        $uri_prefix = $this->sandbox ? "/sandbox" : "";
        $access_data = CPRestClient::post($uri_prefix."/v1/charges", $this->public_key, $this->secret_key, $this->request, $headers);
        $this->access_data = $access_data['response'];

        return $access_data;
    }

    /**
     * Get information for specific payment
     * @param int $id
     * @return array(json)
     */
    public function get_payment($id) {
        //$access_token = $this->get_access_token();

        $uri_prefix = $this->sandbox ? "/sandbox" : "";
        $payment_info = CPRestClient::get($uri_prefix."/v1/charges/" . $id , $this->secret_key);
        return $payment_info;
    }
    public function get_payment_info($id) {
        return $this->get_payment($id);
    }

    /**
     * Get information for specific authorized payment
     * @param id
     * @return array(json)
    */    
    public function get_authorized_payment($id) {
        $access_token = $this->get_access_token();

        $authorized_payment_info = CPRestClient::get("/authorized_payments/" . $id . "?access_token=" . $access_token);
        return $authorized_payment_info;
    }

    /**
     * Refund accredited payment
     * @param int $id
     * @return array(json)
     */
    public function refund_payment($id) {
        $access_token = $this->get_access_token();

        $refund_status = array(
            "status" => "refunded"
        );

        $response = CPRestClient::put("/collections/" . $id . "?access_token=" . $access_token, $refund_status);
        return $response;
    }

    /**
     * Cancel pending payment
     * @param int $id
     * @return array(json)
     */
    public function cancel_payment($id) {
        $access_token = $this->get_access_token();

        $cancel_status = array(
            "status" => "cancelled"
        );

        $response = CPRestClient::put("/collections/" . $id . "?access_token=" . $access_token, $cancel_status);
        return $response;
    }

    /**
     * Cancel preapproval payment
     * @param int $id
     * @return array(json)
     */
    public function cancel_preapproval_payment($id) {
        $access_token = $this->get_access_token();

        $cancel_status = array(
            "status" => "cancelled"
        );

        $response = CPRestClient::put("/preapproval/" . $id . "?access_token=" . $access_token, $cancel_status);
        return $response;
    }

    /**
     * Search payments according to filters, with pagination
     * @param array $filters
     * @param int $offset
     * @param int $limit
     * @return array(json)
     */
    public function search_payment($filters, $offset = 0, $limit = 0) {
        $access_token = $this->get_access_token();

        $filters["offset"] = $offset;
        $filters["limit"] = $limit;

        $filters = $this->build_query($filters);

        $uri_prefix = $this->sandbox ? "/sandbox" : "";
            
        $collection_result = CPRestClient::get($uri_prefix."/collections/search?" . $filters . "&access_token=" . $access_token);
        return $collection_result;
    }

    /**
     * Create a checkout preference
     * @param array $preference
     * @return array(json)
     */
    public function create_preference($preference) {
        $access_token = $this->get_access_token();
		
		$headers = array('Accept: application/compropago+json',
			 'Content-type: application/json'
		);

        //$preference_result = CPRestClient::post("http://api.compropago.com/v1/charges", $this->public_key, $this->secret_key, $this->request, $headers);

        return $access_token;
    }

    /**
     * Update a checkout preference
     * @param string $id
     * @param array $preference
     * @return array(json)
     */
    public function update_preference($id, $preference) {
        $access_token = $this->get_access_token();

        $preference_result = CPRestClient::put("/checkout/preferences/{$id}?access_token=" . $access_token, $preference);
        return $preference_result;
    }

    /**
     * Get a checkout preference
     * @param string $id
     * @return array(json)
     */
    public function get_preference($id) {
        $access_token = $this->get_access_token();

        $preference_result = CPRestClient::get("/checkout/preferences/{$id}?access_token=" . $access_token);
        return $preference_result;
    }

    /**
     * Create a preapproval payment
     * @param array $preapproval_payment
     * @return array(json)
     */
    public function create_preapproval_payment($preapproval_payment) {
        //$access_token = $this->get_access_token();

        //$preapproval_payment_result = CPRestClient::post("/preapproval?access_token=" . $access_token, $preapproval_payment);
        //return $preapproval_payment_result;
		return true;
    }

    /**
     * Get a preapproval payment
     * @param string $id
     * @return array(json)
     */
    public function get_preapproval_payment($id) {
        $access_token = $this->get_access_token();

        $preapproval_payment_result = CPRestClient::get("/preapproval/{$id}?access_token=" . $access_token);
        return $preapproval_payment_result;
    }

	/**
     * Update a preapproval payment
     * @param string $preapproval_payment, $id
     * @return array(json)
     */	
	
	public function update_preapproval_payment($id, $preapproval_payment) {
        $access_token = $this->get_access_token();

        $preapproval_payment_result = CPRestClient::put("/preapproval/" . $id . "?access_token=" . $access_token, $preapproval_payment);
        return $preapproval_payment_result;
    }

    /* **************************************************************************************** */

    private function build_query($params) {
        if (function_exists("http_build_query")) {
            return http_build_query($params, "", "&");
        } else {
            foreach ($params as $name => $value) {
                $elements[] = "{$name}=" . urlencode($value);
            }

            return implode("&", $elements);
        }
    }

}

/**
 * ComproPago cURL RestClient
 */
class CPRestClient {

    private static function get_connect($uri, $method, $public_key, $secret_key, $request, $content_type) {
        
        $domainUri="https://api.compropago.com";
		$connect = curl_init();
		curl_setopt($connect, CURLOPT_USERPWD, $secret_key.":");
		curl_setopt($connect, CURLOPT_URL, $domainUri.$uri);//https://api.compropago.com/v1/charges");			 // URL API 
		curl_setopt($connect, CURLOPT_HTTPHEADER, $content_type);  // Cabeceras API
		curl_setopt($connect, CURLOPT_SSL_VERIFYPEER, false); // No verificamos certificado SSL (no body cares).
 		curl_setopt($connect, CURLOPT_HEADER,0);  			 //Retornar cabeceras 
 		curl_setopt($connect, CURLOPT_RETURNTRANSFER  ,1);    //Retornar datos de llamada
        if($method == "POST"){
            curl_setopt($connect, CURLOPT_POST, 1);                // Peticiones POST
            curl_setopt($connect, CURLOPT_POSTFIELDS, $request);   // Mandamos el Json
        }
        return $connect;
    }

    private static function exec($method, $uri, $public_key, $secret_key, $request, $content_type) {
        if(is_array($request)){	
			$request = json_encode($request);
		}
        $connect = self::get_connect($uri, $method, $public_key, $secret_key, $request, $content_type);
        $api_result = curl_exec($connect);
        $api_http_code = curl_getinfo($connect, CURLINFO_HTTP_CODE);
        $response = array(
            "status" => $api_http_code,
            "response" => json_decode($api_result, true)
        );

        if ($response['status'] >= 400) {
            throw new Exception ($response['response']['message'], $response['status']);
        }
        curl_close($connect);
        return $response;
    }

    public static function get($uri, $secret_key, $content_type = "application/json") {
        return self::exec("GET", $uri, null, $secret_key, null, $content_type);
    }

    public static function post($uri, $public_key, $secret_key, $request, $content_type = array('Accept: application/compropago+json','Content-type: application/json')) {
        return self::exec("POST", $uri, $public_key, $secret_key, $request, $content_type);
    }

    public static function put($uri, $public_key, $secret_key, $content_type = array('Accept: application/compropago+json','Content-type: application/json')) {
        return self::exec("PUT", $uri, $public_key, $secret_key, $content_type);
    }

}

?>
