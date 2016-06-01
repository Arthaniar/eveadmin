<?php
require '../includes/config.php';

use Pheal\Core\Config;
use Pheal\Pheal;
Config::getInstance()->cache = new \Pheal\Cache\MemcacheStorage();
Config::getInstance()->access = new \Pheal\Access\StaticCheck();

$stmt = $db->prepare('DELETE FROM alliance_contracts WHERE 1=1');
$stmt->execute(array());

$stmt = $db->prepare('DELETE FROM alliance_contract_items WHERE 1=1');
$stmt->execute(array());

$corpKeyID = 4813754;
$corpVCode = 'TQb0AdlLKCwZcUoGkHbb6TTmZTAleIuxZtNdlHOqograHNNyLNerJewlonedsnqv';

$pheal = new Pheal($corpKeyID, $corpVCode, 'corp');

$contracts = $pheal->Contracts(array('corporationID' => '98098579'));

$stmt = $db->prepare('INSERT INTO alliance_contracts (contractID,issuerID,issuerName,corporationID,volume,title,price,status,fittingid,end_date) VALUEs (?,?,?,?,?,?,?,?,?,?)' .
    'ON DUPLICATE KEY UPDATE status=VALUES(status),fittingid=VALUES(fittingid),end_date=VALUES(end_date)');

$stmt_items = $db->prepare('INSERT INTO alliance_contract_items (contractID,itemID,quantity) VALUES (?,?,?) ON DUPLICATE KEY UPDATE quantity = quantity + ?');

$stmt_group = $db->prepare('SELECT groupID FROM invTypes WHERE typeID = ? LIMIT 1');
$stmt_category = $db->prepare('SELECT categoryID FROM invGroups WHERE groupID = ? LIMIT 1');

foreach ($contracts->contractList as $contract) {
    if ($contract['assigneeID'] == '150097440' and $contract['status'] == 'Outstanding' and $contract['startStationID'] == '60009259' and $contract['type'] == 'ItemExchange') {

        $parsed_string = get_string_between($contract['title'], '[', ']');

        if ($parsed_string != '' and $parsed_string != null) {
            $parsed_array = explode("-", $parsed_string);

            $doctrine = trim($parsed_array[0]);
            $ship = trim($parsed_array[1]);
        } else {
            $doctrine = 'Unknown';
            $ship = 'Unknown';
        }

        $stmt->execute(array($contract['contractID'],
            $contract['issuerID'],
            Character::lookupCharacterName($contract['issuerID'], $user),
            $contract['issuerCorpID'],
            $contract['volume'],
            $contract['title'],
            $contract['price'],
            $contract['status'],
            0,
            strtotime($contract['dateExpired'])));

        $contractItems = $pheal->ContractItems(array('contractID' => $contract['contractID']));

        $shipDetection = 0;
        $shipDetectedArray = array();

        foreach ($contractItems->itemList as $item) {
            $stmt_group->execute(array($item['typeID']));
            $group = $stmt_group->fetch(PDO::FETCH_ASSOC);

            $stmt_category->execute(array($group['groupID']));
            $category = $stmt_category->fetch(PDO::FETCH_ASSOC);

            if ($category['categoryID'] == 6) {
                $shipDetection++;
                $shipDetectedArray[] = $item['typeID'];
            }

            $stmt_items->execute(array($contract['contractID'], $item['typeID'], $item['quantity'], $item['quantity']));
        }

        if ($shipDetection == 1) {

            $stmt_doctrine_lookup = $db->prepare('SELECT doctrineid,fittingid FROM doctrines_fits WHERE fitting_ship = ?');
            $stmt_doctrine_lookup->execute(array($shipDetectedArray[0]));
            $detectedDoctrine = $stmt_doctrine_lookup->fetchAll(PDO::FETCH_ASSOC);

            $totalPossibleDoctrines = $stmt_doctrine_lookup->rowCount();

            if ($totalPossibleDoctrines == 1) {
                $stmt_update_doctrine = $db->prepare('UPDATE alliance_contracts SET fittingid = ? WHERE contractID = ?');
                $stmt_update_doctrine->execute(array($detectedDoctrine[0]['fittingid'], $contract['contractID']));
            } elseif ($totalPossibleDoctrines > 1) {
                foreach ($detectedDoctrine as $possibleDoctrine) {
                    $stmt_possible_fittings = $db->prepare('SELECT fitting_id_2,fitting_id_1 FROM doctrines_identical_fits WHERE fitting_id_1 = ? OR fitting_id_2 = ?');
                    $stmt_possible_fittings->execute(array($possibleDoctrine['fittingid'], $possibleDoctrine['fittingid']));
                    $stmt_possible_fittings->fetchAll(PDO::FETCH_ASSOC);

                    if ($stmt_possible_fittings->rowCount() >= 1) {
                        if ($stmt_possible_fittings->rowCount() + 1 == $totalPossibleDoctrines) {
                            $stmt_update_doctrine = $db->prepare('UPDATE alliance_contracts SET fittingid = ? WHERE contractID = ?');
                            $stmt_update_doctrine->execute(array($detectedDoctrine[0]['fittingid'], $contract['contractID']));
                        }
                    }
                }
            }
        }
    }
}
