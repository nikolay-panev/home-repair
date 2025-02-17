<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\OAuth;
use League\OAuth2\Client\Provider\Google;

require_once(__DIR__ . "/../config.php");
require_once(__DIR__ . "/../classes/PHPMailer/vendor/autoload.php");
require_once(__DIR__ . "/../classes/OAuth/vendor/autoload.php");

$stmt = $db->prepare('SELECT * FROM `oauth2` WHERE `service` = "Gmail" AND `mode` = "Production"');
$stmt->execute();
$auth = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $db->prepare('SELECT * FROM `emails` WHERE `status` IS NULL AND `attempts` <= 3 AND `recipient` != "" AND `deleted` IS NULL LIMIT 25');
$stmt->execute();
$results = $stmt->fetchAll();

foreach($results as $row) {
	if(strlen(trim($row['recipient'])) < 3) continue;
	
	$mail = new PHPMailer();
	try {
		$mail->isSMTP();
		$mail->Host = SMTP_HOST;
		$mail->Port = SMTP_PORT;
		$mail->SMTPDebug = SMTP::DEBUG_SERVER;
		$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
		$mail->SMTPAuth = true;
		
		$mail->AuthType = 'XOAUTH2';
		
		$provider = new Google(['clientId' => SMTP_OAUTH_CLIENT_ID, 'clientSecret' => SMTP_OAUTH_CLIENT_SECRET]);
		
		$mail->setOAuth(
			new OAuth([
				'provider' => $provider,
				'clientId' => SMTP_OAUTH_CLIENT_ID,
				'clientSecret' => SMTP_OAUTH_CLIENT_SECRET,
				'refreshToken' => $auth['refresh_token'],
				'userName' => SMTP_USER,
			])
		);
		
		$mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
		
		$recipients = explode(',', trim(str_replace(";",",",$row['recipient'])));
		foreach($recipients as $recipient) {
			$recipient = filter_var(trim($recipient), FILTER_VALIDATE_EMAIL);
			
			if($recipient!==false) $mail->AddAddress($recipient);
		}
		
		$mail->Subject = $row['subject'];
		$mail->AltBody = 'To view the message, please use an HTML compatible email viewer!';
		$mail->MsgHTML($row['message']);
		$sent = $mail->Send();
		$mail->ClearAddresses();
		
		if($sent) {
			$stmt = $db->prepare('UPDATE `emails` SET `status` = :status, `attempts` = :attempts WHERE `id` = :id');
			$stmt->bindValue(':status', 'OK');
			$stmt->bindValue(':attempts', $row['attempts']+1);
			$stmt->bindValue(':id', $row['id']);
			$stmt->execute();
		}
		else {
			$stmt = $db->prepare('UPDATE `emails` SET `attempts` = :attempts WHERE `id` = :id');
			$stmt->bindValue(':attempts', $row['attempts']+1);
			$stmt->bindValue(':id', $row['id']);
			$stmt->execute();
		}
	}
	catch(phpmailerException $e) {
		mail('nikolay.panev@gmail.com', 'RECONai Mailer Issue', 'Message '.$row['id'].' has the following issue: '. $e->getMessage());
		$stmt = $db->prepare('UPDATE `emails` SET `attempts` = :attempts, `status` = :status WHERE `id` = :id');
		$stmt->bindValue(':status', 'ERROR');
		$stmt->bindValue(':attempts', $row['attempts']+1);
		$stmt->bindValue(':id', $row['id']);
		$stmt->execute();
	}
	catch(Exception $e) {
		mail('nikolay.panev@gmail.com', 'RECONai Mailer Issue', 'Message '.$row['id'].' has the following issue: '. $e->getMessage());
		$stmt = $db->prepare('UPDATE `emails` SET `attempts` = :attempts, `status` = :status WHERE `id` = :id'); 
		$stmt->bindValue(':status', 'ERROR');
		$stmt->bindValue(':attempts', $row['attempts']+1);
		$stmt->bindValue(':id', $row['id']);
		$stmt->execute();
	}
}