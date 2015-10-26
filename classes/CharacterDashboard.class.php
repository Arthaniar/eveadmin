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
	public function __construct($user, $mainCharacterID) {
		global $db;

		if(is_object($user)) {
			$uid = $user->getUID();
		} else {
			$uid = $user;
		}

		$stmt = $db->prepare('SELECT * FROM characters WHERE uid = ? AND charid != ? AND showchar = 1 ORDER BY skillpoints DESC');
		$stmt->execute(array($uid, $mainCharacterID));
		$allFetch = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$this->characters = array();

		$i = 0;

		$stmt = $db->prepare('SELECT * FROM characters WHERE charid = ? LIMIT 1');
		$stmtKeys = $db->prepare('SELECT * FROM user_apikeys WHERE userid = ? LIMIT 1');

		foreach($allFetch as $character){
			$stmt->execute(array($character['charid']));
			$characterInfo = $stmt->fetch();

			$stmt->execute(array($characterInfo['userid']));
			$keyInfo = $stmt->fetch();

			$this->characters[$i] = new Character($character['charid'], $characterInfo['userid'], $keyInfo['vcode'], $keyInfo['mask'], $db, $user);
			$i++;
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
