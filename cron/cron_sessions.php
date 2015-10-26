<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/includes/config.php');

$expire = SESSION_EXPIRATION * 60;

$stmt = $db->prepare('SELECT * FROM sessions');
$stmt->execute(array());
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare('DELETE FROM sessions WHERE sid = ?');

$session_iterative = 0;

foreach($sessions as $session) {
	if (time() >= $session['expire']+$expire) {
		$stmt->execute(array($session['sid']));
		$session_iterative++;
	}
}

if ($session_iterative >= 1) {
	echo date("Ymd H:i:s", time())." - cron_sessions.php - SUCCESS - ".$session_iterative." sessions have been removed.\n";
} else {
	echo date("Ymd H:i:s", time())." - cron_sessions.php - SUCCESS - No expired sessions to remove.\n";
}