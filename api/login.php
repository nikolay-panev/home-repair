<?php
session_start();

require_once(__DIR__ . "/config.php");
require_once(__DIR__ . "/classes/JWT.php");

$data = json_decode(file_get_contents('php://input'), true);

if(is_array($data) && count($data) > 0) {
	if($data['action'] == 'login') {
		// Validate ReCaptcha if enabled
		if(RECAPTCHA_ENABLED) {
			$post = ['secret' => RECAPTCHA_SECRETKEY, 'response' => $data['captcha']];

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, RECAPTCHA_URL);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			$output = json_decode(curl_exec($ch), true);
			curl_close($ch);
			
			if($output !== null && (@$output['success'] != true || @$output['score'] < 0.1 || (@$output['hostname'] != 'localhost' && @$output['hostname'] != HOSTNAME))) {
				die(json_encode(['error' => "Captcha error. Reload the page and try again.", 'output'=> json_encode($output)]));
			}
		}
		
		// Prevent brute-force attacks
		$ip = (@$_SERVER['HTTP_X_FORWARDED_FOR'] ? explode(", ", $_SERVER['HTTP_X_FORWARDED_FOR'])[0] : $_SERVER['REMOTE_ADDR']);

		$stmt = $db->prepare('SELECT COUNT(*) AS attempts FROM `login` WHERE `login` = :email AND `time` > :login_time AND `ip_address` = :ip');
		$stmt->bindValue(':email', $data['email'], PDO::PARAM_STR);
		$stmt->bindValue(':login_time', date('Y-m-d H:i:s'));
		$stmt->bindValue(':ip', $ip);
		$stmt->execute();
		$badLogins = $stmt->fetch(PDO::FETCH_ASSOC);

		if($badLogins['attempts'] < 5) {
			$stmt = $db->prepare('SELECT * FROM `users` WHERE `email` = :email AND `status`=:status AND `deleted` IS NULL');
			$stmt->bindValue(':email', $data['email'], PDO::PARAM_STR);
			$stmt->bindValue(':status', "Active", PDO::PARAM_STR);
			$stmt->execute();
			$result = $stmt->fetch(PDO::FETCH_ASSOC);
			
			if(is_array($result)) {
				if(password_verify($data['password'], $result['password'])) {
					$exp = time() + 24*60*60;
					$payload = ['id' => $result['id'], /*'role' => $result['role'],*/ 'name' => $result['name'], 'exp' => $exp];
					$jwt = new JWT();
					$token = $jwt->encode($payload, JWT_AUTHORIZATION_SECRET, 'HS256');
					
					$stmt = $db->prepare('UPDATE `users` SET `last_login`=:login_time WHERE `id`=:id');
					$stmt->bindValue(':login_time', date('Y-m-d H:i:s'));
					$stmt->bindValue(':id', $result['id']);
					$stmt->execute();
				
					die(json_encode(['token' => $token, 'payload' => []]));
				}
				else {
					$stmt = $db->prepare('INSERT INTO `login` (`login`, `time`, `ip_address`) VALUES (:login, :login_time, :ip)');
					$stmt->bindValue(':login', $data['email'], PDO::PARAM_STR);
					$stmt->bindValue(':ip', $ip);
					$stmt->bindValue(':login_time', date('Y-m-d H:i:s'));
					$stmt->execute();
					
					die(json_encode(['token' => null, 'payload' => null, 'error'=>'Invalid username or password.']));
				}
			}
			else {
				$stmt = $db->prepare('INSERT INTO `login` (`login`, `time`, `ip_address`) VALUES (:login, :login_time, :ip)');
				$stmt->bindValue(':login', $data['email'], PDO::PARAM_STR);
				$stmt->bindValue(':ip', $ip);
				$stmt->bindValue(':login_time', date('Y-m-d H:i:s'));
				$stmt->execute();
				
				die(json_encode(['token' => null, 'payload' => null, 'error'=>'Invalid username.']));
			}
		}
		else {
			die(json_encode(['error' => "Your IP address was blocked because of too many login attempt failures!"]));
		}
	}
	else if($data['action'] == 'token-generate' && $data['email']) {
		$stmt = $db->prepare('SELECT `email` FROM `users` WHERE `email` = :email AND `status` = :status AND `deleted` IS NULL');
		$stmt->bindValue(':email', $data['email'], PDO::PARAM_STR);
		$stmt->bindValue(':status', "Active", PDO::PARAM_STR);
		$stmt->execute();
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		
		if(is_array($result) && $result['email']) {
			// Generate secure token
			$token = encrypt(substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyz0123456789abcdefghijklmnopqrstuvwxyz0123456789"), 0, 16) . strtotime("+24 hour"));
			
			$stmt = $db->prepare('UPDATE `users` SET `token` = :token WHERE `email` = :email');
			$stmt->bindValue(':email', $data['email'], PDO::PARAM_STR);
			$stmt->bindValue(':token', $token, PDO::PARAM_STR);
			$stmt->execute();
			
			// Get email template
			$stmt = $db->prepare('SELECT * FROM `email_templates` WHERE `name` = "Password Reset" AND `deleted` IS NULL');
			$stmt->execute();
			$template = $stmt->fetch(PDO::FETCH_ASSOC);
			
			$recipients = [];
			array_push($recipients, $data['email']);
			if($template['recipients']) {
				foreach(explode(',', str_replace(";", ",", $template['recipients'])) as $address) {
					if(FILTER_VAR(trim($address), FILTER_VALIDATE_EMAIL)) {
						array_push($recipients, trim($address));
					}
				}
			}
			$subject = $template['subject'];
			$message = $template['message'];
			$link = URL . "account/reset-password/" . $token;
			$message = str_ireplace("{link}", '<a href="'.$link.'" target="_blank" rel="noopener" aria-label="Reset Password">Reset Password</a>', $message);
			
			// Send email
			$stmt = $db->prepare('INSERT INTO `emails`(`recipient`, `subject`, `message`, `attempts`, `created`) VALUES (:recipient, :subject, :message, :attempts, :created)');
			$stmt->bindValue(':recipient', implode(",", $recipients), PDO::PARAM_STR);
			$stmt->bindValue(':subject', $subject, PDO::PARAM_STR);
			$stmt->bindValue(':message', $message);
			$stmt->bindValue(':attempts', "0");
			$stmt->bindValue(':created', date('Y-m-d H:i:s'));
			$stmt->execute();
			
			die(json_encode(['status' => 'success', 'message' => 'A confirmation email has been sent. Please check your mailbox and follow the instructions.']));
		}
		else {
			die(json_encode(['status' => 'fail', 'message' => 'Account does not exist or is disabled.']));
		}
	}
	else if($data['action'] == 'token-validate' && $data['token']) {
		$token = decrypt($data['token']);
		
		$exp = substr($token, 16);
		if(time() > $exp) {
			die(json_encode(['status' => 'fail', 'message' => 'Unable to proceed. Token has already expired.']));
		}
		else {
			$stmt = $db->prepare('SELECT `id` FROM `users` WHERE `token` = :token AND `status` = :status AND `deleted` IS NULL');
			$stmt->bindValue(':token', $data['token'], PDO::PARAM_STR);
			$stmt->bindValue(':status', "Active", PDO::PARAM_STR);
			$stmt->execute();
			$result = $stmt->fetch(PDO::FETCH_ASSOC);
			
			if(@$result['id'] > 0) {
				die(json_encode(['status' => 'success', 'message' => 'Link is valid.']));
			}
			else {
				die(json_encode(['status' => 'fail', 'message' => 'Unable to proceed. Link is not valid.']));
			}
		}
	}
	else if($data['action'] == 'reset-password' && $data['email'] && $data['password'] && $data['token']) {
		$stmt = $db->prepare('UPDATE `users` SET `password` = :password WHERE `email` = :email AND `token` = :token AND `status` = "Active" AND `deleted` IS NULL');
		$stmt->bindValue(':password', password_hash($data['password'], PASSWORD_DEFAULT));
		$stmt->bindValue(':email', $data['email']);
		$stmt->bindValue(':token', $data['token']);
		$stmt->execute();
		
		die(json_encode(['status' => 'success', 'message' => 'Password updated.']));
	}
	else {
		die(json_encode(['error' => "Invalid request."]));
	}
}

die(json_encode([]));