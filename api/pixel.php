<?php
require_once(__DIR__ . "/config.php");

$ip = (@$_SERVER['HTTP_X_FORWARDED_FOR'] ? explode(", ", $_SERVER['HTTP_X_FORWARDED_FOR'])[0] : $_SERVER['REMOTE_ADDR']);
$customer = $_GET['customer'] ? $_GET['customer'] : "RECONai";

$stmt = $db->prepare('INSERT INTO `hits` (`customer_id`, `ip_address`, `created`) VALUES (:customer_id, :ip, :created)');
$stmt->bindValue(':customer_id', $customer);
$stmt->bindValue(':ip', $ip);
$stmt->bindValue(':created', date("Y-m-d H:i:s"));
$stmt->execute();


header("Content-Type: image/jpeg");
header('Content-Length: ' . filesize(__DIR__ . "/images/pixel.jpg"));
readfile(__DIR__ . "/images/pixel.jpg");