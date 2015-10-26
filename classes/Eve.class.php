<?php

use Pheal\Pheal;
use Pheal\Core\Config;
Config::getInstance()->cache = new \Pheal\Cache\MemcacheStorage();
Config::getInstance()->access = new \Pheal\Access\StaticCheck();

// Eve General Class
class Eve {

	// Static variables
	private $apiStatus;
	private $db;

	public function __construct($db) {
		// Adding the DB class to the class
		$this->db = $db;

		// Checking to see if the Eve Online API server is online
		$pheal = new Pheal();
		try {
			$response = $pheal->serverScope->ServerStatus();

			switch($response->serverOpen):
				default:
					$this->apiStatus = FALSE;
				case TRUE:
					$this->apiStatus = TRUE;
			endswitch;
		} catch (\Pheal\Exceptions\PhealException $phealException) {
			$this->apiStatus = FALSE;
		}
	}

	// Type Name and Type ID conversion functions
	public function getTypeID($typeName) {
		$stmt = $this->db->prepare('SELECT typeID FROM invTypes  WHERE typeName = ? LIMIT 1');
		$stmt->execute(array($typeName));
		$typeID = $stmt->fetch();

		return $typeID['typeID'];
	}

	public function getTypeName($typeID) {
		$stmt = $this->db->prepare('SELECT typeName FROM invTypes  WHERE typeID = ? LIMIT 1');
		$stmt->execute(array($typeID));
		$typeName = $stmt->fetch();

		return $typeName['typeName'];
	}

	public function getAPIStatus() {
		return $this->apiStatus;
	}

	public function getGroupID($typeID) {
		$stmt = $this->db->prepare('SELECT * FROM invTypes WHERE typeID = ? LIMIT 1');
		$stmt->execute(array($typeID));
		$groupID = $stmt->fetch(PDO::FETCH_ASSOC);

		return $groupID['groupID'];
	}

	public function getCategoryID($groupID) {
		$stmt = $this->db->prepare('SELECT * FROM invGroups WHERE groupID = ? LIMIT 1');
		$stmt->execute(array($groupID));
		$groupID = $stmt->fetch(PDO::FETCH_ASSOC);

		return $groupID['categoryID'];
	}

	public function checkForShipType($typeID) {
		$group = $this->getGroupID($typeID);
		$category = $this->getCategoryID($groupID);

		if($category == '6') {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	public function getSkillRequirements($typeID) {
		$attributeArray = array();
		$stmt = $this->db->prepare('SELECT * FROM dgmTypeAttributes WHERE typeID = ?');
		$stmt->execute(array($typeID));
		$attributes = $stmt->fetchAll(PDO::FETCH_ASSOC);
		foreach($attributes as $attribute) {
			if($attribute['valueInt'] == NULL) {
				$value = $attribute['valueFloat'];
			} else {
				$value = $attribute['valueInt'];
			}
			switch($attribute['attributeID']):
				case '182':
					$attributeArray[0]['skillID'] = $value;
					break;
				case '183':
					$attributeArray[1]['skillID'] = $value;
					break;
				case '184':
					$attributeArray[2]['skillID'] = $value;
					break;
				case '1285':
					$attributeArray[3]['skillID'] = $value;
					break;
				case '1289':
					$attributeArray[4]['skillID'] = $value;
					break;
				case '1290':
					$attributeArray[5]['skillID'] = $value;
					break;
				case '277':
					$attributeArray[0]['level'] = $value;
					break;
				case '278':
					$attributeArray[1]['level'] = $value;
					break;
				case '279':
					$attributeArray[2]['level'] = $value;
					break;
				case '1286':
					$attributeArray[3]['level'] = $value;
					break;
				case '1287':
					$attributeArray[4]['level'] = $value;
					break;
				case '1288':
					$attributeArray[5]['level'] = $value;
					break;
			endswitch;
		}
		return $attributeArray;
	}

	public function getStationName($stationID) {
		$stmt = $this->db->prepare('SELECT * FROM staStations WHERE stationID = ? LIMIT 1');
		$stmt->execute(array($stationID));
		$station = $stmt->fetch(PDO::FETCH_ASSOC);

		if(!isset($station['stationName']) OR $station['stationName'] == NULL) {
			return 'Unknown Station';
		} else {
			return $station['stationName'];
		}
	}
}