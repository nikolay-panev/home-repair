<?php
require_once(__DIR__ . "/config.php");
require_once(__DIR__ . "/classes/MySQL.php");

$operations = ['select', 'update', 'insert'];
$tables = [];

if($data && is_array($data) && count($data) > 0) {
	if(in_array($data['operation'], $operations) && $data['table'] && (in_array($data['table'], $tables) || empty($tables))) {
		$api = new APIMySQL($db);
		$result = $api->get($data);
		
		die(json_encode($result));
	}
	else die("Invalid params");
}
else die("Invalid data");