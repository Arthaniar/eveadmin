<?php
// This is not a true cronjob. It takes the current Static Data Dump and parses it to build out the rawskilltree table. This only should be run when a new SDD is released.
// The SDD tables required are: invGroups, invTypes .
require_once('../includes/config.php');

$stmt_skill = $db->prepare('SELECT * FROM invTypes WHERE groupID = ? AND published = 1');
$stmt_update = $db->prepare('INSERT INTO rawskilltree (typeID, typeName, groupName) VALUES (?,?,?) ON DUPLICATE KEY UPDATE typeName=VALUES(typeName),groupName=VALUES(groupName)');

$stmt = $db->prepare('SELECT * FROM invGroups WHERE categoryID = ? and published = 1');
$stmt->execute(array(16));
$groupsFetch = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach($groupsFetch as $group) {
	$stmt_skill->execute(array($group['groupID']));
	$skillsFetch = $stmt_skill->fetchAll(PDO::FETCH_ASSOC);
	foreach($skillsFetch as $skill) {
		$stmt_update->execute(array($skill['typeID'], $skill['typeName'], $group['groupName']));
		echo "Skill ".$skill['typeName']." added!<br />";
	}
}