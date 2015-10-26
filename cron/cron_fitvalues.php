<?php
require('../includes/config.php');

$cache_timer = time() - 86400;

$stmt = $db->prepare('SELECT * FROM doctrines WHERE value_fetched <= ? LIMIT 1');
$stmt->execute(array($cache_timer));
$doctrine = $stmt->fetch(PDO::FETCH_ASSOC);

if(isset($doctrine['doctrineid'])) {
	$stmt = $db->prepare('SELECT * FROM doctrines_fits WHERE doctrineid = ?');
	$stmt->execute(array($doctrine['doctrineid']));
	$fittings = $stmt->fetchAll(PDO::FETCH_ASSOC);


	foreach($fittings as $fitting) {
		echo "Previous fitting value: ".number_format($fitting['fitting_value'])."<br />";
		$fitting_value = Fitting::getFittingValue($fitting['fittingid'], $fitting['fitting_ship']);

		$stmt = $db->prepare('UPDATE doctrines_fits SET fitting_value = ? WHERE fittingid = ?');
		$stmt->execute(array($fitting_value, $fitting['fittingid']));

		echo "Fitting value added for Fitting ".number_format($fitting['fittingid']).'<br />';
		echo "New fitting value: ".number_format($fitting_value)."<br />";
	}

	$stmt = $db->prepare('UPDATE doctrines SET value_fetched = ? WHERE doctrineid = ?');
	$stmt->execute(array(time(), $doctrine['doctrineid']));
} else {
	echo 'Nothing outside of 24 hour cache timer';
}

	