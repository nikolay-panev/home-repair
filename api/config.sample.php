<?php
ini_set('display_errors', 1);
error_reporting(E_ERROR);
date_default_timezone_set('UTC');

define('HEADERS',[
	'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS',
    'Access-Control-Allow-Origin: '.(isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : (isset($_SERVER['HTTP_REFERER']) ? rtrim($_SERVER['HTTP_REFERER'],'/\\') : 'http://localhost:3000')),
    'Access-Control-Allow-Headers: content-type,authorization',
    'Access-Control-Allow-Credentials: true',
	'Strict-Transport-Security: max-age=16070400; includeSubDomains'
]);

define("SQL_DEBUG", false);
define("JWT_AUTHORIZATION_SECRET", "");

define("HOSTNAME", "");
define("URL", 'https://app.reconai.realestate');
define("API", 'https://app.reconai.realestate/api/');
define("STORAGE_URL", "https://app.reconai.realestate/api/storage/");
define("STORAGE_PATH", __DIR__ . "/storage");
// SMTP
define("SMTP_HOST", "smtp.gmail.com");
define("SMTP_USER", "");
define("SMTP_PASS", "");
define("SMTP_PORT", "465");
define("SMTP_FROM_EMAIL", "");
define("SMTP_FROM_NAME", "RECONai");
define("SMTP_OAUTH_CLIENT_ID", "");
define("SMTP_OAUTH_CLIENT_SECRET", "");

// Encryption Key
define('ENCRYPTION_KEY', "");
define('ENCRYPTION_IV', "");

// ReCAPTCHA
define('RECAPTCHA_ENABLED', false);
define('RECAPTCHA_URL', null);
define('RECAPTCHA_SECRETKEY', null);

// DB
$db_host = 'localhost';
$db_name =  'reconai';
$db_user = '';
$db_pass = '';
$db_port = '3306';
$db_charset = 'utf8';

// === Establish DB Connection ===
$db = null;
$dsn = ['mysql:host='.$db_host.';dbname='.$db_name.';port='.$db_port.';charset='.$db_charset, $db_user, $db_pass];
$pdo_options = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8");

try {
	$db = new PDO($dsn[0], $dsn[1], $dsn[2], $pdo_options);
	
	foreach(HEADERS as $header) header($header);
}
catch(PDOException $e) {
	echo $e->getMessage();
}

function safe_b64encode($string) {
    $data = base64_encode($string);
    $data = str_replace(array('+','/','='),array('-','_',''),$data);
	return $data;
}
function safe_b64decode($string) {
	$data = str_replace(array('-','_'),array('+','/'),$string);
	$mod4 = strlen($data) % 4;
	if ($mod4) {
		$data .= substr('====', $mod4);
	}
	return base64_decode($data);
}
function encrypt($value) {
	if(!$value) return false;
	
	return trim(safe_b64encode(openssl_encrypt($value, 'aes-128-cbc', ENCRYPTION_KEY, 0, substr(hash('sha256', ENCRYPTION_IV), 0, 16))));
}
function decrypt($value) {
	if(!$value) return false;

    return trim(openssl_decrypt(safe_b64decode($value), 'aes-128-cbc', ENCRYPTION_KEY, 0, substr(hash('sha256', ENCRYPTION_IV), 0, 16)));
}