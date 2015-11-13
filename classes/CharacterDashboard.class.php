<?php

class CharacterDashboard {

	private $db;

	// This is an array of Character-class objects.
	private $characters;

	/**
     * @param $user User object or user id
     * @param $mainCharacterID string
     * The $mainCharacterID string can equal 1 if you want the main character included as part of the returned value
	 */
	public function __construct($user, $mainCharacterID, $spycheck) {
		global $db;

		if(is_object($user)) {
			$uid = $user->getUID();
		} else {
			$uid = $user;
		}

		$stmt = $db->prepare('SELECT character_group_id,character_group_order,character_group_name FROM user_character_groups WHERE uid = ? ORDER BY character_group_order ASC');
		$stmt->execute(array($uid));
		$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

		if($stmt->rowCount() >= 1 AND !$spycheck) {
			foreach($groups as $group) {
				$stmt = $db->prepare('SELECT character_id FROM user_character_group_assignment WHERE uid = ? AND character_group_id = ? and character_id != ?');
				$stmt->execute(array($uid, $group['character_group_id'], $mainCharacterID));
				$fetchCharacterIDs= $stmt->fetchAll(PDO::FETCH_ASSOC);

				$this->characters = array();
				$this->characters[$group['character_group_name']] = array();
				$this->characters[$group['character_group_name']]['name'] = $group['character_group_name'];

				$i = 0;

				foreach($fetchCharacterIDs as $character) {
					$stmt = $db->prepare('SELECT characters.charid,user_apikeys.userid,user_apikeys.vcode,user_apikeys.mask FROM characters JOIN user_apikeys ON characters.userid = user_apikeys.userid WHERE charid = ? AND showchar = 1 ORDER BY skillpoints DESC');
					$stmt->execute(array($character['character_id']));
					$fetchCharacter = $stmt->fetch(PDO::FETCH_ASSOC);	

					$this->characters[$group['character_group_name']][$i] = new Character($fetchCharacter['charid'], $fetchCharacter['userid'], $fetchCharacter['vcode'], $fetchCharacter['mask'], $db, $user);
					$i++;			
				}
			}

			$stmt = $db->prepare('SELECT characters.charid,user_apikeys.userid,user_apikeys.vcode,user_apikeys.mask FROM characters JOIN user_apikeys ON characters.userid = user_apikeys.userid WHERE characters.uid = ? AND characters.charid != ? AND characters.showchar = 1 ORDER BY characters.skillpoints DESC');
			$stmt->execute(array($uid, $mainCharacterID));
			$allFetch = $stmt->fetchAll(PDO::FETCH_ASSOC);

			$this->characters = array();
			$this->characters['All Characters'] = array();
			$this->characters['All Characters']['name'] = 'All Characters';

			$i = 0;

			$stmt = $db->prepare('SELECT * FROM characters WHERE charid = ? LIMIT 1');
			$stmtKeys = $db->prepare('SELECT * FROM user_apikeys WHERE userid = ? LIMIT 1');

			foreach($allFetch as $character){
				$this->characters['All Characters'][$i] = new Character($character['charid'], $character['userid'], $character['vcode'], $character['mask'], $db, $user);
				$i++;
			}

		} else {
			$stmt = $db->prepare('SELECT characters.charid,user_apikeys.userid,user_apikeys.vcode,user_apikeys.mask FROM characters JOIN user_apikeys ON characters.userid = user_apikeys.userid WHERE characters.uid = ? AND characters.charid != ? AND characters.showchar = 1 ORDER BY skillpoints DESC');
			$stmt->execute(array($uid, $mainCharacterID));
			$allFetch = $stmt->fetchAll(PDO::FETCH_ASSOC);

			$this->characters = array();
			$this->characters['All Characters'] = array();
			$this->characters['All Characters']['name'] = 'All Characters';

			$i = 0;

			$stmt = $db->prepare('SELECT * FROM characters WHERE charid = ? LIMIT 1');
			$stmtKeys = $db->prepare('SELECT * FROM user_apikeys WHERE userid = ? LIMIT 1');

			foreach($allFetch as $character){
				$this->characters['All Characters'][$i] = new Character($character['charid'], $character['userid'], $character['vcode'], $character['mask'], $db, $user);
				$i++;
			}
		}
	}

	public function getTrainingTime($time, $var1, $var2, $type, $id) {
	global $eve;

		$training = array();
		$training['Color'] = '';

        switch ($time):
        	case NULL:
			case '':
				$mod = '<span style="color:red"><?php echo $precursor; ?>Training Queue Inactive</span>';
				break;
			case 'Training Paused':
				$mod = 'Training paused</span> for '.$eve->getTypeName($var1).' - Level '.$var2;
				break;
			case 'Queue Paused':
				$mod = 'Queue Paused</span> with '.$var1.' skills queued';
				break;
			default:
				$training = timeConversion($time);
				$mod = ' id="'.$id.'"></span> ';
				switch ($type):
					case "Training":
        				$mod .= 'for '.$eve->getTypeName($var1).' - Level '.$var2;
        				break;
        			case "Skill Queue":
                        $mod .= 'left with '.$var1.' skills queued.';
        				break;
        		endswitch;
        		break;
        endswitch;

        echo $training['Color'].$mod;
	}

	public function getCharacters() {
		return $this->characters;
	}
}
