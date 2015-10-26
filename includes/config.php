<?php
$version = "0.1.0";
$start_time = MICROTIME(TRUE);

//Including the various user-defined settings
require_once('settings.php');

//Creating the session
if(session_id() == '') {
	session_start();
}

// If debug mode has been set, this loads it.
if(DEBUG_MODE) {
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
}

// Loading the PhealNG API Library and Namespace
require_once(DOCUMENT_ROOT.'/vendor/autoload.php');
use Pheal\Pheal;
use Pheal\Core\Config;
Config::getInstance()->cache = new \Pheal\Cache\MemcacheStorage();
Config::getInstance()->access = new \Pheal\Access\StaticCheck();

// Loading the non-OOP functions
require_once('functions.php');
require_once('new_functions.php');

// Setting up our Class autoloader
require_once(DOCUMENT_ROOT.'/classes/autoloader.php');
spl_autoload_register('Autoloader::load', TRUE);

// Loading the Password Compatability file for PHP systems below 5.5.
if(version_compare(phpversion(), '5.5.0', '<')) {
	require_once('password.php');
}

// Establishing the database connection
try {
	$db = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8', DB_USER, DB_PASSWORD,
							array(PDO::ATTR_EMULATE_PREPARES => false,
								  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)	
				);
} catch(PDOException $ex) {
	echo "Database connection failed: ". $ex->getMessage();
	exit();
}

// Creating the Eve class object
$eve = new Eve($db);

// Scrubbing the HTTP headers to get the user's IP Address
$headers = getallheaders();

// Checking the headers to see if the traffic is coming through a proxy such as CloudFlare or direct
if(isset($headers['X-Forwarded-For'])) {
	$ip = $headers['X-Forwarded-For'];
} else {
	$ip = $_SERVER['REMOTE_ADDR'];
}

// Doing the login functionality of a login POST attempt is found
if(isset($_POST['login'])) {
	if(BRUTE_MODE) {
		User::doLogin($_POST['username'], $_POST['password'], User::bruteCheck($ip, $db), $ip, $db);
	} else {
		User::doLogin($_POST['username'], $_POST['password'], TRUE, $ip, $db);
	}
}

// Creating the user class object
$user = new User($db);
$settings = new Settings($db, $user->getGroup());