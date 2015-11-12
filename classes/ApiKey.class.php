<?php

/**
 * Class to integrate ApiKey handling to EveAdmin Auth System
 * @author Josh Grancell <josh@joshgrancell.com>
 * @copyright (c) 2015 Josh Grancell
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2.0
 */

use Pheal\Pheal;
use Pheal\Core\Config;
Config::getInstance()->cache = new \Pheal\Cache\MemcacheStorage();
Config::getInstance()->access = new \Pheal\Access\StaticCheck();

class ApiKey {
	private $db;
	private $uid;
	private $keyID;
	private $vcode;
	private $accountStatus;
	private $accessMask;
	private $keyType;
	private $expires;
	private $keyStatus;
	private $characters;
	private $errorCode;


	public function __construct($keyID, $vcode, $user, $db) {
		$this->db = $db;

		if(is_object($user)) { 
			$this->uid = $user->getUID();
		} else {
			$this->uid = $user;
		}

		$this->keyID = $keyID;
		$this->vcode = $vcode;

		$pheal = new Pheal($keyID, $vcode, 'account');

		try {
			$response = $pheal->APIKeyInfo();
			$this->keyStatus = 1;
			$this->accessMask = $response->key->accessMask;
			$this->expires = $response->key->expires;
			$this->keyType = $response->key->type;

			if($response->key->expires == NULL) {
				$this->expires = 'No Expiration';
			} else {
				$this->expires = $response->key->expires;
			}

			$response2 = $pheal->AccountStatus();
			if($response2->paidUntil == NULL) {
				$this->accountStatus = "Account Unsubscribed";
			} else {
				$this->accountStatus = $response2->paidUntil;
			}
			$i = 1;
			foreach($response->key->characters as $character) {
				if($character->allianceID == "0"){
					$allianceID = 0;
					$allianceName = "No Alliance";
				} else {
					$allianceID = $character->allianceID;
					$allianceName = $character->allianceName;
				}

				$this->characters[$i] = array( 'characterName' => $character->characterName,
											   'characterID' => $character->characterID,
											   'corporationName' => $character->corporationName,
											   'corporationID' => $character->corporationID,
											   'allianceName' => $allianceName,
											   'allianceID' => $allianceID);
				$i++;
			}
		} catch (\Pheal\Exceptions\PhealException $e) {
			$this->keyError = $this->parseKeyError($e);

			$this->keyStatus = 0;
			setAlert('danger', 'API Key Error', $e->getMessage());
		}
	}

	public function updateAPIKey() {

		// Querying the API table to see if this key exists.
		$stmt = $this->db->prepare('SELECT * FROM user_apikeys WHERE userid = ? LIMIT 1');
		$stmt->execute(array($this->keyID));
		$apiKeyCheck = $stmt->fetch();

		if(isset($apiKeyCheck['uid']) AND $apiKeyCheck['uid'] != $this->uid) {
			// This key is already in use, forbidding.
			setAlert('danger', 'API Key Already In Use', 'The selected API key is already in use by another account. If you believe this is in error, please contact '.CONTACT.' with the keyID and vCode you are attempting to use.');
			return FALSE;
		} else {
			// They key itself is not in use, checking characters
			$return = TRUE;
			foreach($this->characters as $character) {
				$stmt = $this->db->prepare('SELECT * FROM characters WHERE charid = ? LIMIT 1');
				$stmt->execute(array($character['characterID']));
				$characterCheck = $stmt->fetch();

				if(isset($characterCheck['uid']) AND $characterCheck['uid'] != $this->uid) {
					// The character is already in use, failing.
					setAlert('danger', 'Character Already In Use','The character '.$character['characterName'].' is currently in use by another account .If you believe this is in error, please contact '.CONTACT.' with the keyID and vCode you are attempting to use.');
					$return = FALSE;
				}
			}

			if($return) {
				$stmt = $this->db->prepare('INSERT INTO user_apikeys (userid,vcode,mask,keyType,expires,keystatus,subscription,refreshed,uid) VALUES (?,?,?,?,?,?,?,?,?) '.
									 'ON DUPLICATE KEY UPDATE vcode=VALUES(vcode),mask=VALUES(mask),keyType=VALUES(keyType),expires=VALUES(expires),keystatus=VALUES(keystatus),'.
									 'subscription=VALUES(subscription),refreshed=VALUES(refreshed)');
				$stmt->execute(array($this->keyID,
									 $this->vcode,
									 $this->accessMask,
									 $this->keyType,
									 $this->expires,
									 $this->keyStatus,
									 $this->accountStatus,
									 time(),
									 $this->uid));

				$stmt = $this->db->prepare('INSERT INTO core_cron (uid,api_keyID,api_accessMask,api_vCode,cron_updated,cron_status) VALUES ( ?,?,?,?,?,?) ON DUPLICATE KEY UPDATE '.
										   'api_accessMask=VALUES(api_accessMask),api_vCode=VALUES(api_vCode),cron_updated=VALUES(cron_updated),cron_status=VALUES(cron_status)');
				$stmt->execute(array($this->uid, $this->keyID, $this->accessMask, $this->vcode, time(), 1));

				return TRUE;
			}


		}
	}

	// Responsible for parsing API key errors
	public function parseKeyError($exception) {
		global $settings;

		$this->errorCode = $exception->code;
		$keyErrorMessage = $exception->getMessage();

		$slackDirectorNotification = FALSE;
		$slackMemberNotification = TRUE;

		switch($this->errorCode):
			// Bad Mask
			case '200':
			case '202':
			case '203':
			case '204':
			case '205':
			case '210':
			case '211':
			case '212':
			case '221':
			case '222':
				$slackDirectorNotification = TRUE;
				break;
			default:
				// We don't care about anything else, really.
				break;

		endswitch;

		$stmt = $this->db->prepare('SELECT * FROM user_accounts WHERE uid = ? LIMIT 1');
		$stmt->execute(array($this->uid));
		$accountInfo = $stmt->fetch();

		if($slackDirectorNotification AND $settings->getSlackIntegration()) {
			sendComplexSlackNotification($settings->getSlackAuthToken(), $settings->getGroupTicker().' Auth Notifications', $settings->getSlackAPIChannel(), "API Key Error for ".$accountInfo['username'].": ".$keyErrorMessage." Affected API keyID: ".$this->keyID, 'aura', 'chat.postMessage');
		}

		return $keyErrorMessage;
	}

	public function refreshAPIKey() {
		global $settings;
		if($this->keyStatus == 1 AND $this->accessMask == MINIMUM_API AND $this->expires == 'No Expiration' AND $this->keyType == 'Account') {

			$update = $this->updateAPIKey();

			if($update) {
				if($settings->getSlackIntegration() AND $settings->getSlackAPINotifications()) {
					sendComplexSlackNotification($settings->getSlackAuthToken(), $settings->getGroupTicker().' Auth Notifications', $settings->getSlackAPIChannel(), 'New API Key submitted by '.User::fetchUserName($this->uid).'.', 'aura', 'chat.postMessage');
				}

				$character_array = array();

				foreach($this->getCharacters() as $character) {
					$character_array[$character['characterID']] = $character['characterID'];

					$char = new Character($character['characterID'], $this->keyID, $this->vcode, $this->accessMask, $this->db, $this->uid);
					if($char->getExistance() OR $char->getExistance() == FALSE) {
						$char->updateCharacterInfo();
						$char->updateCharacterSkills();
					}
				}

				$stmt = $this->db->prepare('UPDATE core_cron SET cron_updated = 1 WHERE api_keyID = ?');
				$stmt->execute(array($this->keyID));

				$this->removeOrphanedCharacter($this->keyID, $this->uid, $character_array);
				return TRUE;

			}
		} elseif($this->keyStatus != 1) {
			if($settings->getSlackIntegration() AND $settings->getSlackAPINotifications()) {
				sendComplexSlackNotification($settings->getSlackAuthToken(), $settings->getGroupTicker().' Auth Notifications', $settings->getSlackAPIChannel(), 'API Key submitted by '.User::fetchUserName($this->uid).' has been rejected as it is invalid.', 'aura', 'chat.postMessage');
			}
			setAlert('danger', 'The API Key Is Invalid', 'The API Key provided is invalid and cannot be used. Please create a new API key, and ensure you have copied the keyID and verification code correctly.');
		} elseif(!($this->accessMask == MINIMUM_API) AND $this->getKeyStatus() == 1) {
			if($settings->getSlackIntegration() AND $settings->getSlackAPINotifications()) {
				sendComplexSlackNotification($settings->getSlackAuthToken(), $settings->getGroupTicker().' Auth Notifications', $settings->getSlackAPIChannel(), 'API Key submitted by '.User::fetchUserName($this->uid).' has been rejected due to an incorrect access mask.', 'aura', 'chat.postMessage');
			}
			setAlert('danger', 'The API Key Does Not Meet Minimum Requirements', 'The required minimum Access Mask for API keys is '.MINIMUM_API.'. Please create a new key using the Create Key link.');
		} elseif($this->expires != 'No Expiration') {
			if($settings->getSlackIntegration() AND $settings->getSlackAPINotifications()) {
				sendComplexSlackNotification($settings->getSlackAuthToken(), $settings->getGroupTicker().' Auth Notifications', $settings->getSlackAPIChannel(), 'API Key submitted by '.User::fetchUserName($this->uid).' has been rejected because it has an expiration.', 'aura', 'chat.postMessage');
			}
			setAlert('danger', 'The API Key Expires', 'The provided API Key has an expiration set. Please create a new key using the Create Key link and ensure you select the No Expiration checkbox.');
		} elseif($this->keyType != 'Account') {
			if($settings->getSlackIntegration() AND $settings->getSlackAPINotifications()) {
				sendComplexSlackNotification($settings->getSlackAuthToken(), $settings->getGroupTicker().' Auth Notifications', $settings->getSlackAPIChannel(), 'API Key submitted by '.User::fetchUserName($this->uid).' has been rejected because it is a single character key.', 'aura', 'chat.postMessage');
			}
			setAlert('danger', 'The API Key Provided is Single-Character', 'All API Keys must be account-wide. Please create a new key using the Create Key link, and do not change the key from an Account Key to a Single Character key.');
		}
	}

	private function removeOrphanedCharacter($keyID, $uid, $character_array) {
		$stmt = $this->db->prepare('SELECT charid FROM characters WHERE uid = ? and userid = ?');
		$stmt->execute(array($uid, $keyID));
		$characters = $stmt->fetchAll(PDO::FETCH_ASSOC);

		foreach($characters as $character) {
			unset($character_array[$character['charid']]);
		}

		if(!empty($character_array)) {
			foreach($character_array as $delete_character) {
				Character::deleteCharacter($delete_character, $uid);
			}
		}
	}

	public function disableAPIKey() {
		// Disabling the API Key on the key table
		$stmt = $this->db->prepare('INSERT INTO user_apikeys (userid,keystatus,uid) VALUES (?,?,?) ON DUPLICATE KEY UPDATE keystatus=VALUES(keystatus)');
		$stmt->execute(array($this->keyID,
							 $this->keyStatus,
							 $this->uid));

		// Disabling the cronjob for all associated characters
		$this->disableCronJobs();

		return TRUE;
	}

	public static function deleteKey($keyID, $user) {
		global $db;

		if(is_object($user)) {
			$uid = $user->getUID();
		} else {
			$uid = $user;
		}

		//Disabling the key, so that it won't be pulled anymore
		$stmt = $db->prepare("DELETE FROM user_apikeys WHERE userid = ? AND uid = ?");
		$stmt->execute(array($keyID, $uid));

		//Selecting all characters that were assigned to use this userid, so that we can remove all instances of them from the DB
		$stmt = $db->prepare("SELECT * FROM characters WHERE userid = ? AND uid = ?");
		$stmt->execute(array($keyID, $uid));
		$charRemove = $stmt->fetchAll(PDO::FETCH_ASSOC);

		//Deleting all of the characters that are assigned to this userid
		$stmt = $db->prepare("DELETE FROM characters WHERE userid = ? AND uid = ?");
		$stmt->execute(array($keyID, $uid));

		//Clearing out all associated character information
		foreach($charRemove as $remove) {
			// Clearing out Evemails
			$stmt = $db->prepare("DELETE FROM user_evemail WHERE character_id = ?");
			$stmt->execute(array($remove['charid']));

			// Clearing out the skills that are assigned to the characters
			$stmt = $db->prepare("DELETE FROM user_skills  WHERE userid = ? AND charid = ?");
			$stmt->execute(array($keyID, $remove['charid']));

			// Clearing out the contact list information
			$stmt = $db->prepare("DELETE FROM user_contactlist  WHERE character_id = ?");
			$stmt->execute(array($remove['charid']));

			// Clearing out the contracts
			$stmt = $db->prepare("DELETE FROM user_contracts  WHERE character_id = ?");
			$stmt->execute(array($remove['charid']));

			// learing out the wallet journal
			$stmt = $db->prepare("DELETE FROM user_walletjournal  WHERE character_id = ?");
			$stmt->execute(array($remove['charid']));

			// Clearing out the skillplan tracking information
			$stmt = $db->prepare("DELETE FROM skillplan_tracking  WHERE charid = ?");
			$stmt->execute(array($remove['charid']));

			$stmt = $db->prepare('DELETE FROM doctrines_tracking WHERE charid = ?');
			$stmt->execute(array($remove['charid']));
		}
		//Disabling the cronjobs
		$stmt = $db->prepare("DELETE FROM core_cron WHERE api_keyID = ?");
		$stmt->execute(array($keyID));
	}

	public function disableCronJobs() {
		$stmt = $this->db->prepare('UPDATE core_cron SET cron_status = 0 WHERE api_keyID = ? AND uid = ?');
		$stmt->execute(array($this->keyID, $this->uid));
	}

	public function accessMaskCheck() {
		if($this->accessMask & 8 AND $this->accessMask & 16777216 AND $this->accessMask & 262144 AND $this->accessMask & 131072 AND $this->accessMask & 33554432) {
			return TRUE;
		} else {
			if($settings->getSlackIntegration()) {
				$stmt = $this->db->prepare('SELECT * FROM user_accounts WHERE uid = ? LIMIT 1');
				$stmt->execute(array($this->uid));
				$accountInfo = $stmt->fetch();

				sendComplexSlackNotification($settings->getSlackAuthToken(), $settings->getGroupTicker().' Auth Notifications', $settings->getSlackAPIChannel(), "API Key Error: Invalid Access Mask | User: ".$accountInfo['username']." | keyID: ".$this->keyID, 'aura', 'chat.postMessage');
			}
			return FALSE;
		}
	}

	public function getUID() {
		return $this->uid;
	}

	public function getKeyID() {
		return $this->keyID;
	}

	public function getVCode() {
		return $this->vcode;
	}

	public function getAccountStatus() {
		return $this->accountStatus;
	}

	public function getAccessMask() {
		return $this->accessMask;
	}

	public function getExpires() {
		return $this->expires;
	}

	public function getKeyStatus() {
		return $this->keyStatus;
	}

	public function getCharacters() {
		return $this->characters;
	}

	public function getErrorCode() {
		return $this->errorCode;
	}

}
