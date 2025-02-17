<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . "/../classes/Google/vendor/autoload.php");
require_once(__DIR__ . "/../config.php");

$stmt = $db->prepare('SELECT * FROM `oauth2` WHERE `service` = "Gmail" AND `mode` = "Production"');
$stmt->execute();
$auth = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $db->prepare('SELECT * FROM `emails` WHERE (`status` <> "Sent" OR `status` IS NULL) AND `attempts` <= 3 AND `recipient` != "" AND `deleted` IS NULL LIMIT 5');
$stmt->execute();
$results = $stmt->fetchAll();

if(count($results) > 0) {
	$client = new Google\Client();
	$client->setApplicationName('RECONai');
	$client->setClientId(SMTP_OAUTH_CLIENT_ID);
	$client->setClientSecret(SMTP_OAUTH_CLIENT_SECRET);
	$client->setAccessType('offline');
	$client->setAccessToken([
		'access_token' => $auth['access_token'],
		'refresh_token' => $auth['refresh_token'],
		'token_type' => 'Bearer',
		'expires_in' => 3600,
		'created' => strtotime($auth['expires']),
	]);

	if($client->isAccessTokenExpired()) {
		if($client->getRefreshToken()) {
			$client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
		}
		else {
			die("Token expired and no refresh token available.");
		}
	}

	$service = new Google\Service\Gmail($client);

	foreach($results as $row) {
		if(!filter_var(trim($row['recipient']), FILTER_VALIDATE_EMAIL)) continue;

		try {
			$message = new Google\Service\Gmail\Message();
			
			$recipient = trim($row['recipient']);

			$rawMessage = "From: \"" . SMTP_FROM_NAME . "\" <" . SMTP_USER . ">" . "\r\n";
			$rawMessage .= "To: " . $recipient . "\r\n";
			$rawMessage .= "Reply-To: " . SMTP_FROM_EMAIL . "\r\n";
			$rawMessage .= "Subject: " . $row['subject'] . "\r\n";
			$rawMessage .= "MIME-Version: 1.0\r\n";
			$rawMessage .= "Content-Type: text/html; charset=utf-8\r\n\r\n"; // Important for correct formatting
			$rawMessage .= $row['message'];

			$message->setRaw(str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($rawMessage)));

			$sentMessage = $service->users_messages->send(SMTP_FROM_EMAIL, $message);
			
			$stmt = $db->prepare('UPDATE `emails` SET `status` = :status, `attempts` = :attempts, `sent` = :sent WHERE `id` = :id');
			$stmt->bindValue(':status', 'Sent');
			$stmt->bindValue(':attempts', $row['attempts']+1);
			$stmt->bindValue(':sent', date('Y-m-d H:i:s'));
			$stmt->bindValue(':id', $row['id']);
			$stmt->execute();

			echo 'Message sent successfully. Message ID: ' . $sentMessage->getId();

		} catch (Exception $e) {
			echo 'An error occurred: ' . $e->getMessage();
			
			$stmt = $db->prepare('UPDATE `emails` SET `attempts` = :attempts, `status` = :status WHERE `id` = :id');
			$stmt->bindValue(':attempts', $row['attempts']+1);
			$stmt->bindValue(':status', $e->getMessage());
			$stmt->bindValue(':id', $row['id']);
			$stmt->execute();
		}
	}
}
