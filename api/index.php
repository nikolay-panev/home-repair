<?php
require_once(__DIR__ . "/config.php");
require_once(__DIR__ . "/classes/JWT.php");
require_once(__DIR__ . "/classes/MySQL.php");

$operations = ['select', 'update', 'insert', 'delete'];
$tables = [];

$headers = apache_request_headers();
$data = json_decode(file_get_contents('php://input'), true);

if(!array_key_exists('Authorization', $headers)) {
	$operations = ['select', 'insert'];
	$tables = ['leads'];
}
else {
	try {
		$jwt = new JWT();
		$payload = $jwt->decode(explode(" ", $headers['Authorization'])[1], JWT_AUTHORIZATION_SECRET, ['HS256']);

		if(!is_object($payload)) {
			// Not authorized
			$operations = ['select', 'insert'];
			$tables = ['leads'];
		}
		else {
			if(time() > @$payload->exp) {
				// Token expired
				$operations = ['select', 'insert'];
				$tables = ['leads'];
			}
		}
	}
	catch(Exception $e) {
		if($e->getMessage() == 'Expired token') {
			$operations = ['select', 'insert'];
			$tables = ['leads'];
		}
		else die($e->getMessage());
	}
}

if($data && is_array($data) && count($data) > 0) {
	if(in_array($data['operation'], $operations) && $data['table'] && (in_array($data['table'], $tables) || empty($tables))) {
		$api = new APIMySQL($db);
		$result = $api->get($data);
		
		die(json_encode($result));
	}
	else die("Invalid params");
}
else die("Invalid data");