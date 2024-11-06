<?php
class ChatGPT {
	private $db = null;
	private $mode = null;
	private $apiKey = null;
	private $apiEndpoint = ['Sandbox' => 'https://api.openai.com/v1/chat/completions', 'Production' => 'https://api.openai.com/v1/chat/completions'];
	private $logPath = null;
	
	public function __construct($db, $mode="Production") {
		$this->db = $db;
		$this->mode = $mode;
		
		$stmt = $this->db->prepare('SELECT * FROM `api_settings` WHERE `provider` = :provider AND `mode` = :mode');
		$stmt->bindValue(':provider', __CLASS__);
		$stmt->bindValue(':mode', $mode);
		$stmt->execute();
		$settings = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$this->logPath = $settings['log_path'];
		$this->apiKey = $settings['api_key'];
	}
	public function sendMessage($message) {
		$url = $this->apiEndpoint[$this->mode];
		$headers = ['Authorization: Bearer '. $this->apiKey, 'Content-Type: application/json'];
		$request = [
			'model' => "gpt-4o-mini",
			'messages' => [["role" => "user", "content" => $message]],
			'temperature' => 0.3
		];
		$response = $this->sendPostRequest($url, $request, $headers);
		
		return $response;
	}
	private function sendPostRequest($endpoint, $request, $headers = []) {
		$ch = curl_init($endpoint);
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
	private function saveLogs($request, $response) {
		if(!$this->logPath) return;
		
        $path = rtrim($this->logPath, "/");
        $path .= '/'.date('Y').'/'.date('m').'/'.date('d');
        if(!is_dir($path)) mkdir($path, 0777, true);

        return file_put_contents($path . '/' . __CLASS__ . '.log', date('YmdHis')."\t".json_encode($request)."\t".json_encode($response) . "\r\n", FILE_APPEND);
    }
}