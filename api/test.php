<?php
require_once(__DIR__ . "/config.php");
require_once(__DIR__ . "/classes/Thumbtack.php");

$api = new Thumbtack($db, 'Sandbox');
echo $api->getPros('92105', 'inground_swimming_pool_companies');
