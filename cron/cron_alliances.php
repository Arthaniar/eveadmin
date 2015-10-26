<?php
require('includes/config.php');

use Pheal\Pheal;
use Pheal\Core\Config;
Config::getInstance()->cache = new \Pheal\Cache\MemcacheStorage();
Config::getInstance()->access = new \Pheal\Access\StaticCheck();

$pheal = new Pheal('', '', 'eve');
$allianceLookup = $pheal->AllianceList(array());

$stmt = $db->prepare('INSERT INTO eve_alliance_list (alliance_name,alliance_id,alliance_short_name) VALUES (?,?,?) ON DUPLICATE KEY UPDATE alliance_name=VALUES(alliance_name)');

foreach($allianceLookup->alliances as $alliance) {
	$stmt->execute(array($alliance->name, $alliance->allianceID, $alliance->shortName));
}

echo "Alliance List Updated.";