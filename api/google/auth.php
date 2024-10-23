<?
require_once(__DIR__ . "/../config.php");
require_once(__DIR__ . "/../classes/OAuth/vendor/autoload.php");

$stmt = $db->prepare('SELECT * FROM `oauth2` WHERE `service` = "Gmail" AND `mode` = "Production"');
$stmt->execute();
$auth = $stmt->fetch(PDO::FETCH_ASSOC);

if(@$auth['access_token'] && @auth['refresh_token'] /*&& strtotime(@$auth['last_updated']) > time() - 100*24*3600*/) {
	die('<h1 style="text-align:center;">You have already authorized.</h1>');
}
else {
	$redirectUri = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

	$params = [
		'clientId' => SMTP_OAUTH_CLIENT_ID,
		'clientSecret' => SMTP_OAUTH_CLIENT_SECRET,
		'redirectUri' => $redirectUri,
		'accessType' => 'offline'
	];
	$options = [
		'scope' => [
			'https://mail.google.com/'
		]
	];

	$provider = new \League\OAuth2\Client\Provider\Google($params);

	if(!isset($_GET['code'])) {
		//If we don't have an authorization code then get one
		$authUrl = $provider->getAuthorizationUrl($options);
		$_SESSION['oauth2state'] = $provider->getState();
		header('Location: ' . $authUrl);
		exit;
	}
	else {
		//Try to get an access token (using the authorization code grant)
		$accessToken = $provider->getAccessToken('authorization_code', ['code' => $_GET['code']]);
		$refreshToken = $accessToken->getRefreshToken();
		//Use this to interact with an API on the users behalf
		//Use this to get a new access token if the old one expires
		//echo 'Refresh Token: ', $token->getRefreshToken();
		
		$stmt = $db->prepare('INSERT INTO `oauth2`(`service`, `mode`, `access_token`, `refresh_token`, `last_updated`) VALUES (:service, :mode, :access_token, :refresh_token, :last_updated)');
		$stmt->bindValue(':service', 'Gmail');
		$stmt->bindValue(':mode', 'Production');
		$stmt->bindValue(':access_token', $accessToken);
		$stmt->bindValue(':refresh_token', $refreshToken);
		$stmt->bindValue(':last_updated', date('Y-m-d H:i:s'));
		$stmt->execute();
		
		die('<h1 style="text-align:center;">Thank you! You have successfully authorized this app with Gmail.</h1>');
	}
}