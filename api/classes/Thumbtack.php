<?php
/*
UTM parameters: ?utm_medium=partnership&utm_source=cma-consortia
*/
class Thumbtack {
	private $db = null;
	private $mode = null;
	private $apiUsername = null;
	private $apiPassword = null;
	private $logPath = null;
	private $apiEndpoint = ['Production' => 'https://pro-api.thumbtack.com/v1/partners/discoverylite/', 'Sandbox' => 'https://staging-pro-api.thumbtack.com/v1/partners/discoverylite/'];
	
	public function __construct($db, $mode="Sandbox") {
		$this->db = $db;
		$this->mode = $mode;
		
		$stmt = $this->db->prepare('SELECT * FROM `api_settings` WHERE `provider` = :provider AND `mode` = :mode');
		$stmt->bindValue(':provider', __CLASS__);
		$stmt->bindValue(':mode', $mode);
		$stmt->execute();
		$settings = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$this->logPath = $settings['log_path'];
		$this->username = $settings['api_username'];
		$this->password = $settings['api_password'];
	}
	public function getCategories($zip) {
		$url = $this->apiEndpoint[$this->mode] . 'homecare/checklist?zip_code=' . $zip . '&property_type=UNKNOWN&utm_medium=partnership&utm_source=cma-consortia';
		//$url = $this->apiEndpoint[$this->mode] . 'categories?utm_medium=partnership&utm_source=cma-consortia&zip_code=';
		
		$headers = ['Content-Type: application/json', 'Authorization: Basic '. base64_encode($this->username.":".$this->password)];
		
		$response = $this->sendRequest($url, $headers);
		
		return $response;
	}
	public function getPros($zip, $category) {
		$url = $this->apiEndpoint[$this->mode] . 'pros?zip_code=' . $zip . '&category=' . $category . '&utm_medium=partnership&utm_source=cma-consortia';
		
		$headers = ['Content-Type: application/json', 'Authorization: Basic '. base64_encode($this->username.":".$this->password)];
		
		$response = $this->sendRequest($url, $headers);
		
		return $response;
	}
	private function sendRequest($endpoint, $headers = []) {		
		$ch = curl_init($endpoint);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		$response = curl_exec($ch);
		curl_close($ch);
		
		$response = json_decode($response, true);
		
		$this->saveLogs($request, $response);
		
		return $response;
	}
	private function saveLogs($request, $response) {
		if(!$this->logPath) return;
		
        $path = rtrim($this->logPath,'/');
        $path .= '/'.date('Y').'/'.date('m').'/'.date('d');
        if(!is_dir($path)) mkdir($path, 0777, true);

        return file_put_contents($path . '/' . __CLASS__ . '.log', date('YmdHis')."\t".json_encode($request)."\t".json_encode($response) . "\r\n", FILE_APPEND);
    }
}