<?php
require_once(__DIR__ . "/config.php");
require_once(__DIR__ . "/classes/OAuth/vendor/autoload.php");

use League\OAuth2\Client\Provider\Google;

session_start();

$stmt = $db->prepare('SELECT * FROM `oauth2` WHERE `service` = "Gmail" AND `mode` = "Production"');
$stmt->execute();
$auth = $stmt->fetch(PDO::FETCH_ASSOC);
/*
if(@$auth['access_token'] && @$auth['refresh_token']) {
	die('<h1 style="text-align:center;">You have already authorized.</h1>');
}
else {
*/
	$redirectUri = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

	$params = [
		'clientId' => SMTP_OAUTH_CLIENT_ID,
		'clientSecret' => SMTP_OAUTH_CLIENT_SECRET,
		'redirectUri' => $redirectUri,
		'include_granted_scopes' => true,
		'accessType' => 'offline',
		'response_type' => 'code',
	];
	$options = [
		'scope' => [
			'https://mail.google.com/'
			'https://www.googleapis.com/auth/gmail.send	',
		],
		'prompt' => 'consent'
	];

	$provider = new Google($params);

	if (!empty($_GET['error'])) {
		// Got an error, probably user denied access
		exit('Got error: ' . htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8'));
	}
	elseif (empty($_GET['code'])) {
		// If we don't have an authorization code then get one
		$authUrl = $provider->getAuthorizationUrl($options);
		$_SESSION['oauth2state'] = $provider->getState();
		header('Location: ' . $authUrl);
		exit;
	}
	elseif (empty($_GET['state']) || !isset($_SESSION['oauth2state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
		// State is invalid, possible CSRF attack in progress
		unset($_SESSION['oauth2state']);
		exit('Invalid state');
	}
	else {
		//Try to get an access token (using the authorization code grant)
		$token = $provider->getAccessToken('authorization_code', ['code' => $_GET['code']]);
		
		// Use this to interact with an API on the users behalf
		$accessToken = $token->getToken();
		// Use this to get a new access token if the old one expires
		$refreshToken = $token->getRefreshToken();
		// Unix timestamp at which the access token expires
		$expires = $token->getExpires();
		
		if (!$refreshToken) {
            die('<h1 style="text-align:center;">Error: No refresh token received. Try revoking access and reauthorizing.</h1>');
        }
		
		$stmt = $db->prepare('INSERT INTO `oauth2`(`service`, `mode`, `access_token`, `refresh_token`, `expires`, `last_updated`) VALUES (:service, :mode, :access_token, :refresh_token, :expires, :last_updated) ON DUPLICATE KEY UPDATE `access_token` = VALUES(access_token), `refresh_token` = VALUES(refresh_token), `expires` = VALUES(expires), `last_updated` = VALUES(last_updated)');
		$stmt->bindValue(':service', 'Gmail');
		$stmt->bindValue(':mode', 'Production');
		$stmt->bindValue(':access_token', $accessToken);
		$stmt->bindValue(':refresh_token', $refreshToken);
		$stmt->bindValue(':expires', date('Y-m-d H:i:s', $expires));
		$stmt->bindValue(':last_updated', date('Y-m-d H:i:s'));
		$stmt->execute();
		
		unset($_SESSION['oauth2state']);
		
		die('<h1 style="text-align:center;">Thank you! You have successfully authorized this app with Gmail.</h1>');
	}
//}