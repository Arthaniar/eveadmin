<?php
require_once('../includes/config.php');
use Pheal\Pheal;
use Pheal\Core\Config;
Config::getInstance()->cache = new \Pheal\Cache\MemcacheStorage();
Config::getInstance()->access = new \Pheal\Access\StaticCheck();

// Confirming that the API server is responding
if($eve->getAPIStatus()) {
	// Setting the lookup cache limit to 1 hour / 3600 seconds
	$cacheLimit = time() - 3600;

	// Getting the oldest key that isn't set to 999 (disabled) and 
	$stmt = $db->prepare('SELECT * FROM core_cron WHERE cron_updated < ? AND cron_status = 1 ORDER BY cron_updated ASC LIMIT 1');
	$stmt->execute(array($cacheLimit));

	$apiLookup = $stmt->fetch(PDO::FETCH_ASSOC);

	// Checking to see if anything is out of cache
	if(isset($apiLookup['api_keyID'])) {

		// Checking the API key
		$key = new ApiKey($apiLookup['api_keyID'], $apiLookup['api_vCode'], $apiLookup['uid'], $db);

		// Checking to see if the key is valid
		if($key->getKeyStatus() == 1) {
			// Key is valid, updating it
			$updateKey = $key->updateAPIKey();



			// Checking the access mask for the key
			if($key->accessMaskCheck()) {
				// Looping through the characters
				foreach($key->getCharacters() as $character) {
					$char = new Character($character['characterID'], $key->getKeyID(), $key->getVCode(), $key->getAccessMask(), $db, $apiLookup['uid']);

					if($char->getExistance() OR $char->getExistance() == FALSE) {
						$char->updateCharacterInfo();
						/*
						 * SKILLS UPDATE SECTION
						*/
						$skills = $char->updateCharacterSkills();

						// Checking for skills update success
						if ($skills === "SDE Failure") {
							echo date('Ymd H:i:s', time())." - cron_update.php - FAILURE - Skill detected that does not exist in SDE for charid ".$character['characterID']."\n";
						} elseif ($skills === FALSE) {
							echo date('Ymd H:i:s', time())." - cron_update.php - FAILURE - General processing error for charid ".$character['characterID']."\n";
						}

						/*
						 * SKILLPLAN UPDATE SECTION
						*/

						$skillPlans = $char->updateSkillPlanProgress();

						/*
						 * DOCTRINE COMPLIANCE SECTION
						*/

						Fitting::getDoctrineCompliance($character['characterID']);
						/*
						 * MARKET UPDATE SECTION
						*/

						$marketUpdate = $char->updateMarketInformation();

						/*
						 * Asset Update Section
						 */

						$assets = $char->updateAssets();
						/*
						 * EVEMAIL UPDATE SECTION
						*/

						$evemailUpdate = $char->updateEveMail();

						if($marketUpdate === TRUE and $skillPlans === TRUE AND is_object($char) AND $evemailUpdate === TRUE) {
							echo date('Ymd H:i:s', time())." - cron_update.php - SUCCESS - Updates completed for ".$char->getCharacterID().".\n";
						} elseif ($skillPlans === TRUE AND is_object($char)) {
							echo date('Ymd H:i:s', time())." - cron_update.php - WARNING - Updates completed for ".$char->getCharacterID().". Market information and Evemail were not updated.\n";
						} else {
							echo date('Ymd H:i:s', time())." - cron_update.php - FAILURE - Updates failed for ".$char->getCharacterID().".\n";
						}
					} else {
						echo date("Ymd H:i:s", time())." - cron_update.php - WARNING - API Key Invalid for ".$apiLookup['api_keyID'].".\n";
					}
					
				}
			} else {
				if($key->getErrorCode() != NULL) {
					$key->disableCronJobs();
					echo date("Ymd H:i:s", time())." - cron_update.php - WARNING - API Key ".$key->getKeyID()." does not have the proper permissions.\n";
				}
			}

		} else {
			if($key->getErrorCode() != NULL) {
				$disableKey = $key->disableAPIKey();
				echo date("Ymd H:i:s", time())." - cron_update.php - WARNING - API Key ".$key->getKeyID()." is not valid or has expired.\n";
			}
		}
	} else {
		echo date("Ymd H:i:s", time())." - cron_update.php - SUCCESS - Nothing outside of cache.\n";
	}


} else {
	echo date("Ymd H:i:s", time())." - cron_update.php - FAILURE - CCP API Server is not responding.\n";
}