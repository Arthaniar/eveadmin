<?php
// Ship Fitting Class
Class Fitting {
	// Static variables
	private $db;
	private $doctrineID;
	private $groupID;

	// Fitting information variables
	private $fittingID;
	private $fittingName;
	private $fittingShipID;
	private $fittingShipName;
	private $role;
	private $priority;
	private $fittingNotes;

	// Fitting equipment variables
	private $highSlotCount;
	private $midSlotCount;
	private $lowSlotCount;
	private $rigSlotCount;
	private $subsystemCount;

	private $droneBayCount;

	// Generic module array
	private $moduleArray;

	public function __construct($fittingID, $db, $gid, $characterID) {
		global $eve;

		// Saving the database and group id into the class
		$this->db = $db;
		$this->groupID = $gid;
		$this->fittingID = $fittingID;

		// Fetching the Fitting information.
		$stmt = $db->prepare('SELECT * FROM doctrines_fits WHERE fittingid = ? AND gid = ? LIMIT 1');
		$stmt->execute(array($this->fittingID, $this->groupID));
		$fitting = $stmt->fetch(PDO::FETCH_ASSOC);
	
		// Populating the class with the fetched Fitting information.
		$this->fittingName = $fitting['fitting_name'];
		$this->fittingShipID = $fitting['fitting_ship'];
		$this->fittingShipName = $eve->getTypeName($this->fittingShipID);
		$this->role = $fitting['fitting_role'];
		$this->priority = $fitting['fitting_priority'];
		$this->doctrineID = $fitting['doctrineid'];
		$this->fittingNotes = $fitting['fitting_notes'];

		// Fetching the module information
		$stmt = $db->prepare('SELECT * FROM doctrines_fittingmods WHERE fittingid = ?');
		$stmt->execute(array($this->fittingID));
		$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

		// Setting the slow counts to 0 and defaulting the array
		$this->lowSlotCount = 0;
		$this->midSlotCount = 0;
		$this->highSlotCount = 0;
		$this->rigSlotCount = 0;
		$this->subsystemCount = 0;
		$this->moduleArray = array();

		// Building the Modules array and Slot counts
		foreach($modules as $module) {

			$moduleSlot = $module['module_slot'];
			if($moduleSlot == 'Subsystem') {
				$objectName = "subsystemCount";
			} elseif ($moduleSlot == 'Drone') {
				$objectName = 'droneBayCount';
			} else {
				$objectName = strtolower($moduleSlot)."SlotCount";
			}

			$slotCount = $this->$objectName;
			$this->$objectName++;

			$this->moduleArray[$moduleSlot][$slotCount] = array();

			if($module['type_id'] == 0) {
				$this->moduleArray[$moduleSlot][$slotCount]['prerequisites'] = 'None';
				$this->moduleArray[$moduleSlot][$slotCount]['character-skills'] = 'None';
				$this->moduleArray[$moduleSlot][$slotCount]['typeID'] = $module['type_id'];
				$this->moduleArray[$moduleSlot][$slotCount]['typeName'] = '[empty '.strtolower($moduleSlot).' slot]';
				$this->moduleArray[$moduleSlot][$slotCount]['quantity'] = $module['module_quantity'];
				$this->moduleArray[$moduleSlot][$slotCount]['slot'] = $moduleSlot;
				$this->moduleArray[$moduleSlot][$slotCount]['emptySlot'] = TRUE;		
			} else {
				$this->moduleArray[$moduleSlot][$slotCount]['prerequisites'] = $eve->getSkillRequirements($module['type_id']);
				$this->moduleArray[$moduleSlot][$slotCount]['character-skills'] = Character::checkSkillPreRequisites($this->moduleArray[$moduleSlot][$slotCount]['prerequisites'], $characterID);
				$this->moduleArray[$moduleSlot][$slotCount]['typeID'] = $module['type_id'];
				$this->moduleArray[$moduleSlot][$slotCount]['typeName'] = $eve->getTypeName($module['type_id']);
				$this->moduleArray[$moduleSlot][$slotCount]['quantity'] = $module['module_quantity'];
				$this->moduleArray[$moduleSlot][$slotCount]['slot'] = $moduleSlot;
				$this->moduleArray[$moduleSlot][$slotCount]['emptySlot'] = FALSE;			
			}

		}
	}

	public static function addFitting($doctrineID, $fittingRaw, $fittingRole, $fittingPriority, $fittingNotes, $groupID, $user) {
		global $db;
		global $eve;
		// Turning the raw fitting into an array
		$fittingArray = explode("\n", $fittingRaw);

		// Removing the ship name and fitting name from the array.
		$shipRaw = $fittingArray[0];
		unset($fittingArray[0]);

		// Breaking Apart the 
		preg_match('/(.+?),(.+?)\]/',$shipRaw, $shipMatches);

		$shipTypeName = trim(substr($shipMatches[1], 1));
		$shipTypeID = $eve->getTypeID($shipTypeName);
		$shipFittingName = trim(substr($shipMatches[2], 1));


		$stmt = $db->prepare('INSERT INTO doctrines_fits (gid,doctrineid,fitting_name,fitting_ship,fitting_role,fitting_priority,fitting_notes,fitting_value,'.
						'lastupdated_time,lastupdated_user) VALUES (?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE fitting_name=VALUES(fitting_name),'.
						'fitting_role=VALUES(fitting_role),fitting_priority=VALUES(fitting_priority),fitting_notes=VALUES(fitting_notes),fitting_value=VALUES(fitting_value),'.
						'lastupdated_time=VALUES(lastupdated_time),lastupdated_user=VALUES(lastupdated_user)');
		$stmt->execute(array($groupID,
							 $doctrineID,
							 $shipFittingName,
							 $shipTypeID,
							 $fittingRole,
							 $fittingPriority,
							 $fittingNotes,
							 0,
							 time(),
							 $user->getUsername()));

		$stmt = $db->prepare('SELECT fittingid FROM doctrines_fits WHERE fitting_name = ? AND doctrineid = ? AND gid = ?');
		$stmt->execute(array($shipFittingName, $doctrineID, $groupID));
		$fittingInfo = $stmt->fetch();

		foreach($fittingArray as $fitting) {
			$ammoCheck = strpos($fitting, ',');
			$emptyCheck = strpos($fitting, '[empty');

			if($ammoCheck !== FALSE) {
				$ammoArray = explode(",", $fitting);
				$fitting = $ammoArray[0];
			}

			if($emptyCheck !== FALSE) {
				$emptyArray = explode(" ", $fitting);
				$emptySlot = ucwords($emptyArray[1]);
				$stmt = $db->prepare('INSERT INTO doctrines_fittingmods (fittingid,type_id,module_quantity,module_slot) VALUEs (?,?,?,?)'.
									 ' ON DUPLICATE KEY UPDATE module_quantity = module_quantity + VALUES(module_quantity)');
				$stmt->execute(array($fittingInfo['fittingid'],
									 0,
									 1,
									 $emptySlot));
			} else {
				$fitting = trim($fitting);
				if($fitting != '' AND $fitting != 'Empty') {
					$fittingPreg = $fitting;
					preg_match('/^(.*)(\s+x)(\d+)?/', $fittingPreg, $matches);

					if(isset($matches[3]) and gettype($matches[3]) == 'string') {
						$quantity = $matches[3];
						$typeName = $matches[1];
					} else {
						$quantity = 1;
						$typeName = $fitting;
					}
					$typeID = $eve->getTypeID($typeName);

					if(strpos($typeName, 'Proteus') === FALSE AND strpos($typeName, 'Loki') === FALSE AND strpos($typeName, 'Legion') === FALSE AND strpos($typeName, 'Tengu') === FALSE) {
						$moduleSlot = Fitting::getModuleSlot($typeID);
					} else {
						$moduleSlot = 'Subsystem';
					}
					

					if($typeID == NULL) {
						setAlert('danger', 'Unidentified Module Detected', 'The module '.$typeName.' does not exist, and may have been renamed by CCP. Please correct this module name and try again.');
						$stmt = $db->prepare('DELETE FROM doctrines_fittingmods WHERE fittingid = ?');
						$stmt->execute(array($fittingInfo['fittingid']));

						$stmt = $db->prepare('DELETE FROM doctrines_fits WHERE fittingid = ? AND gid = ?');
						$stmt->execute(array($fittingInfo['fittingid'], $user->getGroup()));
						break;
					} else {
						$stmt = $db->prepare('INSERT INTO doctrines_fittingmods (fittingid,type_id,module_quantity,module_slot) VALUES (?,?,?,?)'.
							                 ' ON DUPLICATE KEY UPDATE module_quantity = module_quantity + VALUES(module_quantity)');
						$stmt->execute(array($fittingInfo['fittingid'],
											 $typeID,
											 $quantity,
											 $moduleSlot));	
					}			
				}
			}

		} 

		$fitting_value = Fitting::getFittingValue($fittingInfo['fittingid'], $shipTypeID);
		$stmt = $db->prepare('UPDATE doctrines_fits SET fitting_value = ? WHERE fittingid = ?');
		$stmt->execute(array($fitting_value, $fittingInfo['fittingid']));
	}

	public function getModuleColoring($module) {
		global $eve;
		$array = array();
		$character_skills = $module['character-skills'];
		if($character_skills === TRUE) {
			$array['color'] = 'goodColorBack';
			$array['badge'] = '<span style="font-size: 85%; font-weight: normal">Trained</span>';
			$array['icon'] = 'meetsreq';
		} elseif($character_skills === FALSE) {
			$preReqArray = array();
			$i = 0;

			foreach($module['prerequisites'] as $prereqs) {
			$skillName = $eve->getTypeName($prereqs['skillID']);
				$skillLevel = $prereqs['level'];
				$preReqArray[$i] = "Missing ".$skillName." to Level ".$skillLevel;
				$i++;
			}

			$missingSkills = implode("\n",$preReqArray);
			$array['icon'] = 'nottrained';
			$array['color'] = 'badColorBack';
			$array['badge'] = '<button type="button" style=" margin: 6px 0px 6px 0px;" class="btn btn-danger btn-sm" data-toggle="tooltip" data-placement="top" title="'.$missingSkills.'">Missing Skills</button>';
		} else {
			$array['icon'] = 'belowreq';
			$array['color'] = 'okayColorBack';
			$array['badge'] = '<span style="font-size: 85%; font-weight: normal;"">Skills Too Low</span>';
		}

		return $array;
	}

	// Getting the module's power slot
	public static function getModuleSlot($moduleID) {
		global $db;
		$stmt = $db->prepare('SELECT * FROM dgmTypeEffects WHERE typeID = ? AND (effectID = 11 OR effectID = 12 OR effectID = 13 OR effectID = 2663 OR effectID = 3772)');
		$stmt->execute(array($moduleID));
		$slotID = $stmt->fetch();

		switch($slotID['effectID']):
			case 11:
				$moduleSlot = 'Low';
				break;
			case 12:
				$moduleSlot = 'High';
				break;
			case 13:
				$moduleSlot = 'Mid';
				break;
			case 2663:
				$moduleSlot = 'Rig';
				break;
			case 3772:
				$moduleSlot = 'Subsystem';
				break;
			default:
				$moduleSlot = 'Drone';
				break;
		endswitch;

		if($moduleSlot == NULL) {
			setAlert('danger', 'Internal Server Error FC-01', 'An internal server error has occured. Please submit a bug detailing exactly what you have done or attempted to do that caused this error.');
		}
		return $moduleSlot;
	}



	public static function getDoctrineCompliance($characterID) {
		//Globalizing the DB variable
		global $db;

		// Getting the group ID
		$groupID = Character::fetchGroupID($characterID);
		$characterName = Character::fetchCharacterName($characterID);

		// Getting the list of doctrines
		$stmt = $db->prepare('SELECT * FROM doctrines WHERE gid = ? ORDER BY doctrineid ASC');
		$stmt->execute(array($groupID));
		$doctrines = $stmt->fetchAll(PDO::FETCH_ASSOC);

		// Creating some statements for future use with getting fitting and module information for each doctrine and fitting respectively
		$stmtFittings = $db->prepare('SELECT * FROM doctrines_fits WHERE doctrineid = ? ORDER BY fittingid ASC');
		$stmtModules = $db->prepare('SELECT * FROM doctrines_fittingmods WHERE fittingid = ? ORDER BY type_id ASC');

		//Looping through each doctrine to get fitting information
		foreach($doctrines as $doctrine) {
			$stmtFittings->execute(array($doctrine['doctrineid']));
			$fittings = $stmtFittings->fetchAll(PDO::FETCH_ASSOC);

			// Looping through each fitting to get module information
			foreach($fittings as $fitting) {
				// Setting the default failure, warning, and success levels for the fitting
				$fittingFailure = 0;
				$fittingSuccess = 0;
				$fittingWarning = 0;
				$fittingTotal = 0;

				$stmtModules->execute(array($fitting['fittingid']));
				$modules = $stmtModules->fetchAll(PDO::FETCH_ASSOC);

				// Getting skill information about the ship hull itself

				$hullCheck = Fitting::checkItemPrerequisites($fitting['fitting_ship'], $characterID);

				if($hullCheck === TRUE) {
					$fittingSuccess += 1;
				} elseif ($hullCheck == "Warning") {
					$fittingWarning += 1;
				} else {
					$fittingFailure += 1;
				}

				$fittingTotal += 1;

				// Looping through each module to get skill information, and compare it to the character in question
				foreach($modules as $module) {
					$moduleCheck = Fitting::checkItemPrerequisites($module['type_id'], $characterID);

					if($moduleCheck === TRUE) {
						$fittingSuccess += 1;
					} elseif ($hullCheck == "Warning") {
						$fittingWarning += 1;
					} else {
						$fittingFailure += 1;
					}

					$fittingTotal += 1;
				}

				if($fittingFailure >= 1) {
					$colorStatus = 'failure';
				} elseif ($fittingWarning >= 1) {
					$colorStatus = 'warning';
				} else {
					$colorStatus = 'success';
				}

				// Inputting the fitting into the tracking database
				$stmt = $db->prepare('INSERT INTO doctrines_tracking (charid,character_name,gid,doctrineid,fittingid,usable_items,total_items,color_status) VALUES (?,?,?,?,?,?,?,?)'.
						' ON DUPLICATE KEY UPDATE character_name=VALUES(character_name),gid=VALUES(gid),usable_items=VALUES(usable_items),total_items=VALUES(total_items),color_status=VALUES(color_status)');
				$stmt->execute(array($characterID, $characterName, $groupID, $doctrine['doctrineid'], $fitting['fittingid'], $fittingSuccess, $fittingTotal, $colorStatus));

			}
		}
	}

	// This checks to see if an item is usable. It returns TRUE if it can be used, FALSE if it is untrained, and WARNING if the skill is trained but not high enough.
	public static function checkItemPrerequisites($typeID, $characterID) {
		global $eve;
		$itemRequirements = $eve->getSkillRequirements($typeID);
		$itemCheck = Character::checkSkillPreRequisites($itemRequirements, $characterID);

		return $itemCheck;
	}

	public static function getFittingValue($fitting_id, $ship_id) {
		global $db;

		$eve_central = new EveCentral($db);
		$stmt = $db->prepare('SELECT * FROM doctrines_fittingmods WHERE fittingid = ?');
		$stmt->execute(array($fitting_id));
		$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$price_total = 0;

		$volume_total = 0;

		$price = $eve_central->lookupItem($ship_id, 'sell', 'Jita');

		$price_total += $price;

		foreach($modules as $module) {
			// Getting the price
			$price = $eve_central->lookupItem($module['type_id'], 'sell', 'Jita');

			$price_total += ($price * $module['module_quantity']);
		}

		return $price_total;
	}



	// Class method access endpoints

	public function getDoctrineID() {
		return $this->doctrineID;
	}

	public function getGroupID() {
		return $this->groupID;
	}

	public function getFittingID() {
		return $this->fittingID;
	}

	public function getFittingName() {
		return $this->fittingName;
	}

	public function getFittingRole() {
		return $this->role;
	}

	public function getFittingPriority() {
		return $this->priority;
	}

	public function getFittingNotes() {
		return $this->fittingNotes;
	}

	public function getHighSlotCount() {
		return $this->highSlotCount;
	}

	public function getMidSlotCount() {
		return $this->midSlotCount;
	}

	public function getLowSlotCount() {
		return $this->lowSlotCount;
	}

	public function getRigSlotCount() {
		return $this->rigSlotCount;
	}	

	public function getSubsystemCount() {
		return $this->subsystemCount;
	}

	public function getDroneBayCount() {
		return $this->droneBayCount;
	}

	public function getModules($slot) {
		if($slot == 'all') {
			return $this->moduleArray;
		} else {
			return $this->moduleArray[$slot];
		}
	}

	public function getFittingShipID() {
		return $this->fittingShipID;
	}

	public function getFittingShipName() {
		return $this->fittingShipName;
	}
}
