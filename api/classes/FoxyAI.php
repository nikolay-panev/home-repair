<?php
class FoxyAI {
	private $db = null;
	private $mode = null;
	private $apiUsername = null;
	private $apiPassword = null;
	private $logPath = null;
	private $apiEndpoint = ['Sandbox' => 'https://api.foxyai.com/', 'Production' => 'https://api.foxyai.com/'];
	private $clientId = null;
	
	public function __construct($db, $mode="Sandbox", $clientId=null) {
		$this->db = $db;
		$this->mode = $mode;
		$this->clientId = $clientId;
		
		$stmt = $this->db->prepare('SELECT * FROM `api_settings` WHERE `provider` = :provider AND `mode` = :mode');
		$stmt->bindValue(':provider', __CLASS__);
		$stmt->bindValue(':mode', $mode);
		$stmt->execute();
		$settings = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$this->logPath = $settings['log_path'];
		$this->username = $settings['api_username'];
		$this->password = $settings['api_password'];
	}
	/* Create logical grouping of images and keep track of metrics across multiple images */
	public function createImageGroup($name, $address = null, $meta = []) {
		$url = $this->apiEndpoint[$this->mode] . '/image-group';
		$headers = ['Content-Type: application/json', 'Authorization: Basic '. base64_encode($this->username.":".$this->password)];
		$request = [
			'name' => $name,
			'address' => $address,
			'metaData' => $meta
		];
		$response = $this->sendPostRequest($url, $request, $headers);
		
		return $response;
	}
	/* The image analysis allows you to post a image url and receive information about those images. */
	public function createImage($image_url, $image_group_id) {
		$url = $this->apiEndpoint[$this->mode] . '/image';
		$headers = ['Content-Type: application/json', 'Authorization: Basic '. base64_encode($this->username.":".$this->password)];
		$request = [
			'url' => $image_url,
			'models' => ["room_classification", "condition_score", "quality_score"],
			'imageGroups' => [$image_group_id]
		];
		$response = $this->sendPostRequest($url, $request, $headers);
		
		return $response;
	}
	/* Will do the work of converting an equirectangular image and split it into 4 individual photos (one for each direction), and processing these photos through the models. */
	public function create360Image($image_url, $image_group_id) {
		$url = $this->apiEndpoint[$this->mode] . '/image/360';
		$headers = ['Content-Type: application/json', 'Authorization: Basic '. base64_encode($this->username.":".$this->password)];
		$request = [
			'url' => $image_url,
			'models' => ["room_classification", "condition_score", "quality_score"],
			'imageGroups' => [$image_group_id]
		];
		$response = $this->sendPostRequest($url, $request, $headers);
		
		return $response;
	}
	/* Generate a 360 Property Valuation Report when given an address and an imageGroupId */
	public function create360Report($image_group_id, $address) {
		$url = $this->apiEndpoint[$this->mode] . '/360-report/' , $image_group_id;
		$headers = ['Content-Type: application/json', 'Authorization: Basic '. base64_encode($this->username.":".$this->password)];
		$request = [
			'address' => $address,
			'webhookUrl' => ''
		];
		$response = $this->sendPostRequest($url, $request, $headers);
		
		return $response;
	}
	public function get360Report($report_id) {
		$url = $this->apiEndpoint[$this->mode] . '/360-report/' , $report_id;
		$headers = ['Content-Type: application/json', 'Authorization: Basic '. base64_encode($this->username.":".$this->password)];
		
		$response = $this->sendGetRequest($url, $headers);
		if($response['status'] == 'completed') {
			if($this->clientId) {
				$downloadURL = $response['downloadUrl'];
				file_put_contents(rtrim($this->logPath, "/") . "/" . $this->clientId . "/" . $report_id . ".pdf", file_get_contents($downloadURL));
			}
			return $response;
		}
		return $response;
	}
	private function sendPostRequest($endpoint, $request, $headers = []) {
		$ch = curl_init($endpoint);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request));
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		$response = curl_exec($ch);
		curl_close($ch);
		
		$response = json_decode($response, true);
		
		$this->saveLogs($request, $response);
		
		return $response;
	}
	private function sendGetRequest($url, $headers = []) {
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
		
        $path = rtrim($this->logPath, "/");
        $path .= '/'.date('Y').'/'.date('m').'/'.date('d');
        if(!is_dir($path)) mkdir($path, 0777, true);

        return file_put_contents($path . '/' . __CLASS__ . '.log', date('YmdHis')."\t".json_encode($request)."\t".json_encode($response) . "\r\n", FILE_APPEND);
    }
}