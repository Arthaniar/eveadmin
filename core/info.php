<?php

if($request['action'] == 'apis') {
	$page_type = "API";

	if($request['value'] == 'refresh') {

		$keyID = $request['value_2'];
		$stmt = $db->prepare('SELECT * FROM user_apikeys WHERE userid = ? LIMIT 1');
		$stmt->execute(array($keyID));
		$key_raw =  $stmt->fetch(PDO::FETCH_ASSOC);

		$key = new ApiKey($keyID, $key_raw['vcode'], $key_raw['uid'], $db);
		if($key->getKeyStatus() == 1 AND $key->getAccessMask() & MINIMUM_API) {
			$update = $key->updateApiKey();
			if($update) {
				$stmt = $db->prepare('SELECT * FROM characters WHERE uid = ? AND userid = ?');
				$stmt->execute(array($user->getUID(), $keyID));
				$characters = $stmt->fetchAll(PDO::FETCH_ASSOC);
				$character_array = array();
				foreach($characters as $character) {
					$character_array[$character['charid']] = $character['charid'];
				}

				foreach($key->getCharacters() as $character) {
					$char = new Character($character['characterID'], $key->getKeyID(), $key->getVCode(), $key->getAccessMask(), $db, $user);
					if($char->getExistance() OR $char->getExistance() == FALSE) {
						$char->updateCharacterInfo();
					}

					unset($character_array[$char->getCharacterID()]);
				}

				if(!empty($character_array)) {
					foreach($character_array as $delete_character) {
						Character::deleteCharacter($delete_character, $user->getUID());
					}
				}
				$refresh = $key->refreshAPIKey('refresh');
				setAlert('success', 'API Key Refreshed', 'The API key has been successfully refreshed.');
								
			}
		} elseif(!($key->getAccessMask() & MINIMUM_API) AND $key->getKeyStatus() == 1) {
			setAlert('danger', 'The API Key Does Not Meet Minimum Requirements', 'The required minimum Access Mask for API keys is '.MINIMUM_API.'. Please create a new key using the Create Key link.');
		}	
	} elseif($request['value'] == 'delete') {
		
	}

	if($user->getAdminAccess()) {
		$stmt = $db->prepare('SELECT * FROM user_apikeys JOIN user_accounts ON user_apikeys.uid = user_accounts.uid ORDER BY user_accounts.username ASC');
		$stmt->execute(array());
		$keys = $stmt->fetchAll(PDO::FETCH_ASSOC);
	} else {
		$stmt = $db->prepare('SELECT * FROM user_apikeys JOIN user_accounts ON user_apikeys.uid = user_accounts.uid WHERE user_accounts.gid = ? ORDER BY user_accounts.username ASC');
		$stmt->execute(array($user->getGroup()));
		$keys = $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
} elseif($request['action'] == 'characters') {
	$page_type = "Character";

	if($user->getAdminAccess()) {
		$stmt = $db->prepare('SELECT * FROM characters JOIN user_accounts ON characters.uid = user_accounts.uid ORDER BY user_accounts.username,characters.charactername ASC');
		$stmt->execute(array());
		$characters = $stmt->fetchAll(PDO::FETCH_ASSOC);
	} else {
		$stmt = $db->prepare('SELECT * FROM characters JOIN user_accounts ON characters.uid = user_accounts.uid WHERE user_accounts.gid = ? ORDER BY characters.charactername ASC');
		$stmt->execute(array($user->getGroup()));
		$characters = $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
}
require_once("info.view.php");