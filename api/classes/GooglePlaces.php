<?php
class GooglePlaces {
	private $apiKey = null;
	private $logPath = null;
	private $db = null;
	
	public function __construct($db, $mode="Sandbox") {
		$this->db = $db;

		$stmt = $this->db->prepare('SELECT * FROM `api_settings` WHERE `provider` = :provider AND `mode` = :mode');
		$stmt->bindValue(':provider', __CLASS__);
		$stmt->bindValue(':mode', $mode);
		$stmt->execute();
		$settings = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$this->logPath = $settings['log_path'];
		$this->apiKey = $settings['api_key'];

		//$this->apiKey = 'AIzaSyCH2FpsCHYsIDQMcftWHuRf9eBNYto78a4';
	}
	
	public function getAddress($query) {
		$url = "https://maps.googleapis.com/maps/api/place/autocomplete/json?input=" . urlencode($query) . "&types=address&language=en&components=country:us&key=" . $this->apiKey;
		
		$response = $this->sendRequest($url, null);
		
		if(count($response['predictions']) > 0) {
			$items = [];
			foreach($response['predictions'] as $address) {
				$items[] = ['value' => $address['place_id'], 'title' => $address['description']];
			}
			
			return json_encode(['status' => 'success', 'items' => $items]);
		}
		
		return json_encode(['status' => 'fail', 'items' => null, 'debug' => $response]);
	}
	public function getAddressDetails($place_id) {
		$url = "https://maps.googleapis.com/maps/api/place/details/json?fields=address_components,formatted_address&place_id=" . $place_id ."&key=" . $this->apiKey;
		
		$response = $this->sendRequest($url, null);
		
		$zip = null;
		$formatted_address = @$response['result']['formatted_address'];
		
		foreach(@$response['result']['address_components'] as $component) {
			if(in_array('postal_code', $component['types'])) $zip = $component['short_name'];
		}
		
		return json_encode(['status' => 'success', 'zip' => $zip, 'formatted_address' => $formatted_address, 'debug' => $response]);
	}
	private function sendRequest($endpoint, $request) {		
		$ch = curl_init($endpoint);
		curl_setopt($ch, CURLOPT_HEADER, 0);
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