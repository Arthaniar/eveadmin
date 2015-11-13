<?php
require('../includes/config.php');

use Pheal\Pheal;
use Pheal\Core\Config;
Config::getInstance()->cache = new \Pheal\Cache\MemcacheStorage();
Config::getInstance()->access = new \Pheal\Access\StaticCheck();

$stmt = $db->prepare('DELETE FROM alliance_contracts WHERE 1=1');
$stmt->execute(array());

$stmt = $db->prepare('DELETE FROM alliance_contract_items WHERE 1=1');
$stmt->execute(array());

$corpKeyID = $settings->getCorpUserID();
$corpVCode = $settings->getCorpVCode();

$pheal = new Pheal($corpKeyID, $corpVCode, 'corp');

$contracts = $pheal->Contracts(array('corporationID' => '98098579'));

$stmt = $db->prepare('INSERT INTO alliance_contracts (contractID,issuerID,issuerName,corporationID,volume,title,price,status,doctrine,ship,end_date) VALUEs (?,?,?,?,?,?,?,?,?,?,?)'.
					'ON DUPLICATE KEY UPDATE status=VALUES(status),doctrine=VALUES(doctrine),ship=VALUES(ship),end_date=VALUES(end_date)');

$stmt_items = $db->prepare('INSERT INTO alliance_contract_items (contractID,itemID,quantity) VALUES (?,?,?) ON DUPLICATE KEY UPDATE quantity = quantity + ?');

foreach($contracts->contractList as $contract) {
	if($contract['assigneeID'] == '150097440' AND $contract['status'] == 'Outstanding' AND $contract['startStationID'] == '61000829' AND $contract['type'] == 'ItemExchange') {

		$parsed_string = get_string_between($contract['title'], '[', ']');

		if($parsed_string != '' AND $parsed_string != NULL) {
			$parsed_array = explode("-", $parsed_string);

			$doctrine = trim($parsed_array[0]);
			$ship = trim($parsed_array[1]);
		} else {
			$doctrine = 'Unknown';
			$ship = 'Unknown';
		}

		$stmt->execute(array($contract['contractID'],
							 $contract['issuerID'],
							 Character::lookupCharacterName($contract['issuerID'],$user),
							 $contract['issuerCorpID'],
							 $contract['volume'],
							 $contract['title'],
							 $contract['price'],
							 $contract['status'],
							 $doctrine,
							 $ship,
							 strtotime($contract['dateExpired'])));

		$contractItems = $pheal->ContractItems(array('contractID' => $contract['contractID'] ));

		foreach($contractItems->itemList as $item) {
			$stmt_items->execute(array($contract['contractID'], $item['typeID'], $item['quantity'], $item['quantity']));
		}
	}
}
