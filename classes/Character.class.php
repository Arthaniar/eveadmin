<?php

/**
 * Class to integrate Character handling and processing to EveAdmin Auth System
 * @author Josh Grancell <josh@joshgrancell.com>
 * @copyright (c) 2015 Josh Grancell
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2.0
 */

use Pheal\Pheal;
use Pheal\Core\Config;
Config::getInstance()->cache = new \Pheal\Cache\MemcacheStorage();
Config::getInstance()->access = new \Pheal\Access\StaticCheck();

class Character {
	private $db;
	private $characterID;
	private $uid;
	private $gid;

	// API Key Information
	private $keyID;
	private $vCode;
	private $accessMask;

	// Character Lookup Information
	private $characterExists;
	private $showCharacter;

	// Character-specific information
	private $characterName;
	private $corporationName;
	private $corporationID;
	private $corporationTicker;
	private $allianceName;
	private $allianceID;
	private $currentSkill;
	private $currentLevel;
	private $endOfTraining;
	private $endOfQueue;
	private $skillQueueCount;
	private $accountBalance;
	private $currentShipTypeName;
	private $currentShipTypeID;
	private $skillPoints;
	private $lastKnownLocation;
	private $unreadEvemailCount;

	public function __construct($characterID, $keyID, $vCode, $accessMask, $db, $user) {
		// Saving the basic information for the character
		$this->db = $db;
		$this->keyID = $keyID;
		$this->vCode = $vCode;
		$this->characterID = $characterID;
		$this->accessMask = $accessMask;

		// Checking to see if we're using the logged in user, or if this is running through a cronjob.
		// The cronjob will always give the $user variable as a string with the user id.
		// The logged-in user will give $user as an ojbect

		if(gettype($user) == 'object') {
			$this->uid = $user->getUID();
			$this->gid = $user->getGroup();
		} else {
			$this->uid = $user;
			$stmt = $db->prepare('SELECT * FROM user_accounts WHERE uid = ? LIMIT 1');
			$stmt->execute(array($user));
			$userInfo = $stmt->fetch(PDO::FETCH_ASSOC);

			$this->gid = $userInfo['gid'];
		}

		// Looking up the character to confirm that 1) it does exist and
		$stmt = $db->prepare('SELECT * FROM characters WHERE charid = ? LIMIT 1');
		$stmt->execute(array($characterID));
		$characterLookup = $stmt->fetch(PDO::FETCH_ASSOC);

		// Confirming that the character exists in our system by verifying the uid values match, or verifying the character does not exist at all
		if(($stmt->rowCount() != 0 AND $characterLookup['uid'] == $this->uid)) {
			$this->showCharacter = $characterLookup['showchar'];
			$this->characterExists = TRUE;

			if($characterLookup['allyid'] == NULL) {
				$this->allianceName = 'No Alliance';
				$this->allianceID = 0;
				$this->allianceShortName = '---';
			} else {
				$this->allianceName = $characterLookup['alliance'];
				$this->allianceID = $characterLookup['allyid'];

				$stmt = $db->prepare('SELECT * FROM eve_alliance_list WHERE alliance_id = ? LIMIT 1');
				$stmt->execute(array($this->allianceID));
				$allianceList = $stmt->fetch();

				$this->allianceShortName = $allianceList['alliance_short_name'];
			}

			$stmt = $db->prepare('SELECT * FROM user_evemail WHERE character_id = ? AND unread = 1 AND evemail_sender != ? ORDER BY sent_date ASC');
			$stmt->execute(array($this->characterID, $this->characterName));

			$this->unreadEvemailCount = $stmt->rowCount();

			$this->characterName = $characterLookup['charactername'];
			$this->corporationName = $characterLookup['corporation'];
			$this->corporationID = $characterLookup['corpid'];
			$this->corporationTicker = $characterLookup['corporation_ticker'];
			$this->currentSkill = $characterLookup['skillid'];
			$this->currentLevel = $characterLookup['trainlevel'];
			$this->endOfTraining = $characterLookup['endtraining'];
			$this->endOfQueue = $characterLookup['endqueue'];
			$this->skillQueueCount = $characterLookup['numqueue'];
			$this->accountBalance = $characterLookup['balance'];
			$this->currentShipTypeName = $characterLookup['shipname'];
			$this->currentShipTypeID = $characterLookup['shipid'];
			$this->skillPoints = $characterLookup['skillpoints'];
			$this->lastKnownLocation = $characterLookup['lastlocation'];

		} elseif($stmt->rowCount() != 0 AND $characterLookup['uid'] != $this->uid) {
			$this->characterExists = 'Failure';
			setAlert('danger', 'The requested character is current being used by another account.', 'If you believe this is in error, please contact the site Administrator.');
		} else {
			$this->characterExists = FALSE;
			// The character does not exist, but can be created
		}
	}

	public function updateAssets() {
		$pheal = new Pheal($this->keyID, $this->vCode, 'char');

		try {
			$phealAssets = $pheal->AssetList(array('characterID' => $this->characterID));

			$stmt = $this->db->prepare('DELETE FROM user_assets WHERE uid = ? AND characterID = ?');
			$stmt->execute(array($this->uid, $this->characterID));

			foreach($phealAssets->assets as $asset) {

				if($asset['singleton'] == 0) {
					$packaged = 1;
				} else {
					$packaged = 0;
				}

				if($asset['locationID'] == NULL) {
					var_dump($asset);
					die;
				}


				$stmt = $this->db->prepare('INSERT INTO user_assets (uid,characterID,itemID,typeID,locationID,quantity,flag,packaged,parent_item) VALUES (?,?,?,?,?,?,?,?,?) '.
								'ON DUPLICATE KEY UPDATE quantity=VALUES(quantity)');
				$stmt->execute(array($this->uid,
									 $this->characterID,
									 $asset['itemID'],
									 $asset['typeID'],
									 $asset['locationID'],
									 $asset['quantity'],
									 $asset['flag'],
									 $packaged,
									 NULL));

				if($packaged == 0) {
					if(isset($asset->contents)) {
						foreach($asset->contents as $item) {

							if($item['singleton'] == 0) {
								$packaged = 1;
							} else {
								$packaged = 0;
							}

							$stmt = $this->db->prepare('INSERT INTO user_assets (uid,characterID,itemID,typeID,quantity,flag,packaged,parent_item) VALUES (?,?,?,?,?,?,?,?) '.
											'ON DUPLICATE KEY UPDATE quantity=VALUES(quantity)');
							$stmt->execute(array($this->uid,
												 $this->characterID,
												 $item['itemID'],
												 $item['typeID'],
												 $item['quantity'],
												 $item['flag'],
												 $packaged,
												 $asset['itemID']));
						}
					}
				}
			}

			return TRUE;
		} catch (\Pheal\Exceptions\PhealException $phealException) {
			var_dump($phealException);

		}
	}

	public function updateCharacterInfo() {

				// Confirming that the character exists in our system by verifying the uid values match, or verifying the character does not exist at all
		if(($this->characterExists OR $this->characterExists == FALSE)) {

			// Creating the Pheal object
			$pheal = new Pheal($this->keyID, $this->vCode, 'char');

			// Starting a try/catch for Pheal
	        try {
	        	//Getting the Skill Queue information
	        	$phealSkillQueue = $pheal->SkillQueue(array("characterID" => $this->characterID));

	        	// Working through the skill queue to get Current Training Skill and Level, total number of skills in queue, and both end of current training and queue time
	        	$this->skillQueueCount = count($phealSkillQueue->skillqueue);
	        	if($this->skillQueueCount != 0) {
					$this->currentSkill = $phealSkillQueue->skillqueue[0]->typeID;
					$this->currentLevel = $phealSkillQueue->skillqueue[0]->level;
					if($phealSkillQueue->skillqueue[0]->endTime == '') {
						$this->endOfTraining = 'Training Paused';
						$this->endOfQueue = 'Queue Paused';
					} else {
						$finalSkill = $this->skillQueueCount - 1;
						$this->endOfTraining = $phealSkillQueue->skillqueue[0]->endTime;
						$this->endOfQueue = $phealSkillQueue->skillqueue[$finalSkill]->endTime;
					}	
				}

	        	// Creating a new Eve scope Pheal object
	        	$evePheal = new Pheal($this->keyID, $this->vCode, 'eve');

	        	// Accessing the CharacterInfo page of the Eve Scope
	        	$phealCharacterInfo = $evePheal->CharacterInfo(array("characterID" => $this->characterID));
	        	$this->accountBalance = $phealCharacterInfo->accountBalance;
	        	$this->currentShipTypeID = $phealCharacterInfo->shipTypeID;
	        	$this->characterName = $phealCharacterInfo->characterName;
	        	$this->corporationName = $phealCharacterInfo->corporation;
	        	$this->corporationID = $phealCharacterInfo->corporationID;
	        	$this->skillPoints = $phealCharacterInfo->skillPoints;
	        	$this->lastKnownLocation = $phealCharacterInfo->lastKnownLocation;

	        	// Getting the Corp ticker
	        	$corpPheal = new Pheal('', '', 'corp');
	        	$phealCorporationInfo = $corpPheal->CorporationSheet(array("corporationID" => $phealCharacterInfo->corporationID));

	        	$this->corporationTicker = $phealCorporationInfo->ticker;

	        	// Getting the Alliance information, or setting to none
	        	if($phealCharacterInfo->alliance != NULL) {
	        		$this->allianceName = $phealCharacterInfo->alliance;
	        		$this->allianceID = $phealCharacterInfo->allianceID;
	        	} else {
	        		$this->allianceName = 'No Alliance';
	        		$this->allianceID = 0;
	        	}

	        	// Checking to see if the character has been podded, or if they're in a capsule/ship
	        	if($this->currentShipTypeID == 0 ) {
	        		$this->currentShipTypeName = 'Out Of Capsule';
	        	} else {
	        		$this->currentShipTypeName = $phealCharacterInfo->shipTypeName;
	        	}

				$stmt = $this->db->prepare('INSERT INTO characters (charid,uid,gid,userid,showchar,charactername,corporation,corpid,corporation_ticker,alliance,allyid,skillid,trainlevel,endtraining,endqueue,numqueue,'.
									 'balance,shipname,shipid,skillpoints,lastlocation,mask) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE gid=VALUES(gid),userid=VALUES(userid),'.
									 'charactername=VALUES(charactername),corporation=VALUES(corporation),corpid=VALUES(corpid),corporation_ticker=VALUES(corporation_ticker),alliance=VALUES(alliance),allyid=VALUES(allyid),'.
									 'skillid=VALUES(skillid),trainlevel=VALUES(trainlevel),endtraining=VALUES(endtraining),endqueue=VALUES(endqueue),numqueue=VALUES(numqueue),'.
									 'balance=VALUES(balance),shipname=VALUES(shipname),shipid=VALUES(shipid),skillpoints=VALUES(skillpoints),lastlocation=VALUES(lastlocation),mask=VALUES(mask)');
				$stmt->execute(array($this->characterID,
									 $this->uid,
									 $this->gid,
									 $this->keyID,
									 1,
									 $this->characterName,
									 $this->corporationName,
									 $this->corporationID,
									 $this->corporationTicker,
									 $this->allianceName,
									 $this->allianceID,
									 $this->currentSkill,
									 $this->currentLevel,
									 $this->endOfTraining,
									 $this->endOfQueue,
									 $this->skillQueueCount,
									 $this->accountBalance,
									 $this->currentShipTypeName,
									 $this->currentShipTypeID,
									 $this->skillPoints,
									 $this->lastKnownLocation,
									 $this->accessMask));

			} catch (\Pheal\Exceptions\PhealException $phealException) {
				// Putting the pheal exception through our test function to determine if it's a key failure, or an API server failure.
				$this->testAPIKeyStatus($phealException);
			}
		} else {
			// The character exists, and does not belong to the requesting account. We're denying, and exiting here.
			setAlert('danger', 'The requested character already exists on another account', 'The API key that you have provided includes a character that exists on a different account. If this is not intentional, contact your Admin/CEO');
		}
	}

	public function updateCharacterSkills() {
		$db = $this->db;

		$pheal = new Pheal($this->keyID, $this->vCode, 'char');

		try {
			$response = $pheal->CharacterSheet(array('characterID' => $this->characterID));

			$stmt = $db->prepare('INSERT INTO user_skills (uid,gid,charid,userid,skill,skillid,level) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE gid=VALUES(gid),uid=VALUES(uid),userid=VALUES(userid),level=VALUES(level)');
			$stmtnew = $db->prepare('SELECT * FROM invTypes  WHERE typeID = ?');

			foreach($response->skills as $skill) {

				$skillID = $skill->typeID;
				$skillLevel = $skill->level;

				$stmtnew->execute(array($skill->typeID));
				$skillInfo = $stmtnew->fetchAll(PDO::FETCH_ASSOC);
				
				if ($skillInfo[0]['typeName'] === NULL) {
					$failure = "SDE Failure";
				} else {
					$stmt->execute(array($this->uid,
										 $this->gid,
										 $this->characterID,
										 $this->keyID,
										 $skillInfo[0]['typeName'],
										 $skillID,
										 $skillLevel));
				}
			}

			if(isset($failure)) {
				return $failure;
			} else {
				return TRUE;
			}
		} catch (\Pheal\Exceptions\PhealException $phealException) {
			// Putting the pheal exception through our test function to determine if it's a key failure, or an API server failure.
			$this->testAPIKeyStatus($phealException);
			return FALSE;
		}

	}

	public static function fetchGroupID($characterID) {
		global $db;
		$stmt = $db->prepare('SELECT * FROM characters WHERE charid = ? LIMIT 1');
		$stmt->execute(array($characterID));
		$groupInfo = $stmt->fetch(PDO::FETCH_ASSOC);

		return $groupInfo['gid'];
	}

	public static function deleteCharacter($characterID, $uid) {
		global $db;

		$stmt = $db->prepare('DELETE FROM characters WHERE charid = ? AND uid = ?');
		$stmt->execute(array($characterID, $uid));

		// Clearing out Evemails
		$stmt = $db->prepare("DELETE FROM user_evemail WHERE character_id = ?");
		$stmt->execute(array($characterID));

		// Clearing out the skills that are assigned to the characters
		$stmt = $db->prepare("DELETE FROM user_skills WHERE charid = ?");
		$stmt->execute(array($characterID));

		// Clearing out the contact list information
		$stmt = $db->prepare("DELETE FROM user_contactlist  WHERE character_id = ?");
		$stmt->execute(array($characterID));

		// Clearing out the contracts
		$stmt = $db->prepare("DELETE FROM user_contracts  WHERE character_id = ?");
		$stmt->execute(array($characterID));

		// learing out the wallet journal
		$stmt = $db->prepare("DELETE FROM user_walletjournal  WHERE character_id = ?");
		$stmt->execute(array($characterID));

		// Clearing out the skillplan tracking information
		$stmt = $db->prepare("DELETE FROM skillplan_tracking  WHERE charid = ?");
		$stmt->execute(array($characterID));

		$stmt = $db->prepare('DELETE FROM doctrines_tracking WHERE charid = ?');
		$stmt->execute(array($characterID));
	}

	public function updateSkillPlanProgress() {
		$db = $this->db;

		$stmt = $db->prepare('SELECT * FROM skillplan_main WHERE gid = ?');
		$stmt->execute(array($this->gid));
		$skillPlans = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$stmt = $db->prepare('DELETE FROM skillplan_tracking WHERE charid = ?');
		$stmt->execute(array($this->characterID));

		if($skillPlans !== NULL AND $skillPlans !== FALSE) {
			// Setting up the Subgroup and individual Skill PDO requests
			$stmtSubgroups = $db->prepare('SELECT * FROM skillplan_subgroups WHERE skillplan_id = ?');
			$stmtSkills = $db->prepare('SELECT * FROM skillplan_skills WHERE subgroup_id = ?');
			$stmtSkillLookup = $db->prepare('SELECT * FROM user_skills  WHERE skillid = ? AND charid = ? LIMIT 1');
			$stmtInsertSubgroup = $db->prepare('INSERT INTO skillplan_tracking (charid,character_name,subgroup_id,skills_trained,skills_total) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE character_name=VALUES(character_name),skills_trained=VALUES(skills_trained),skills_total=VALUES(skills_total)');

			foreach($skillPlans as $plan) {
				// Pulling the subgroup list
				$stmtSubgroups->execute(array($plan['skillplan_id']));
				$subGroups = $stmtSubgroups->fetchAll(PDO::FETCH_ASSOC);

				foreach($subGroups as $subgroup) {
					// Setting the iteratives for both trained and total skill counts to 0
					$totalSkills = 0;
					$trainedSkills = 0;

					// Getting the skills for this specific subgroup
					$stmtSkills->execute(array($subgroup['subgroup_id']));
					$skillsList = $stmtSkills->fetchAll(PDO::FETCH_ASSOC);

					// Working through the skills list to see what's trained
					foreach($skillsList as $skill) {
						$totalSkills++;

						$stmtSkillLookup->execute(array($skill['skill_typeid'],
														$this->characterID));
						$trainedInfo = $stmtSkillLookup->fetch(PDO::FETCH_ASSOC);
						
						// Confirming that we have the skill trained at all
						if($trainedInfo !== NULL OR $trainedInfo !== FALSE) {
							//Comparing the trained level to the required level
							if($trainedInfo['level'] >= $skill['skill_level']) {
								$trainedSkills++;
							}
						}
					}

					$stmtInsertSubgroup->execute(array($this->characterID,
													   $this->characterName,
													   $subgroup['subgroup_id'],
													   $trainedSkills,
													   $totalSkills));
				}
			}
		return TRUE;
		} else {
			// There are no skill plans for this group
			return TRUE;
		}
	}

	// Updates contact list, market, and contracts for a character
	public function updateMarketInformation() {
		if(2097152 & $this->accessMask AND 67108864 & $this->accessMask) {
			$pheal = new Pheal($this->keyID, $this->vCode, 'char');
			try {
				// Wallet Journal pull
				$response = $pheal->WalletJournal(array("characterID" => $this->characterID,
														 "rowCount" => 2560));
				$stmtJournal = $this->db->prepare('INSERT INTO user_walletjournal (character_id,journal_id,journal_date,journal_type,journal_fromname,journal_fromid,journal_toname,journal_toid,journal_specialid,journal_specialname,journal_amount,journal_balance,journal_reason) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE journal_amount=VALUES(journal_amount)');
				foreach($response->transactions as $transaction) {
					$stmtJournal->execute(array($this->characterID,
								 $transaction->refID,
								 $transaction->date,
								 $transaction->refTypeID,
								 $transaction->ownerName1,
								 $transaction->ownerID1,
								 $transaction->ownerName2,
								 $transaction->ownerID2,
								 $transaction->argID1,
								 $transaction->argName1,
								 $transaction->amount,
								 $transaction->balance,
								 $transaction->reason));
				}

				// Contracts pull
				$response = $pheal->Contracts(array('characterID' => $this->characterID));

				$stmtContract = $this->db->prepare('INSERT INTO user_contracts (character_id,contract_id,contract_issuerid,contract_issuercorpid,contract_assigneeid,contract_acceptorid,contract_startstationid,'.
					'contract_endstationid,contract_type,contract_status,contract_title,contract_forcorp,contract_availability,contract_dateissued,contract_dateexpired,contract_dateaccepted,'.
					'contract_numdays,contract_datecompleted,contract_price,contract_reward,contract_collateral,contract_buyout,contract_volume) VALUES '.
					'(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE contract_acceptorid=VALUES(contract_acceptorid),contract_status=VALUES(contract_status),'.
					'contract_dateaccepted=VALUES(contract_dateaccepted),contract_dateexpired=VALUES(contract_dateexpired),contract_datecompleted=VALUES(contract_datecompleted)');

				foreach($response->contractList as $contract) {
					$stmtContract->execute(array($this->characterID,
												 $contract->contractID,
												 $contract->issuerID,
												 $contract->issuerCorpID,
												 $contract->assigneeID,
												 $contract->acceptorID,
												 $contract->startStationID,
												 $contract->endStationID,
												 $contract->type,
												 $contract->status,
												 $contract->title,
												 $contract->forCorp,
												 $contract->availability,
												 $contract->dateIssued,
												 $contract->dateExpired,
												 $contract->dateAccepted,
												 $contract->numDays,
												 $contract->dateCompleted,
												 $contract->price,
												 $contract->reward,
												 $contract->collateral,
												 $contract->buyout,
												 $contract->volume));
				}

				// Contact List pull
				$response = $pheal->ContactList(array("characterID" => $this->characterID));

				$stmtJournal = $this->db->prepare('INSERT INTO user_contactlist (character_id,contact_type,contact_id,contact_name,contact_inwatchlist,contact_standing) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE contact_inwatchlist=VALUES(contact_inwatchlist),contact_standing=VALUES(contact_standing)');
				foreach($response->contactList as $contact) {
					$stmtJournal->execute(array($this->characterID,
								 'personal',
								 $contact->contactID,
								 $contact->contactName,
								 $contact->inWatchlist,
								 $contact->standing));
				}
				
				return TRUE;
			} catch (\Pheal\Exceptions\PhealException $phealException) {
				// Putting the pheal exception through our test function to determine if it's a key failure, or an API server failure.
				$this->testAPIKeyStatus($phealException);
				return FALSE;
			}
		} else {
			return TRUE;
		}
	}

	// Updates email for a specific character
	public function updateEveMail() {
		if(2048 & $this->accessMask AND 1024 & $this->accessMask) {
			// Starting the Message Header pulls
	        $userid = $this->keyID;
	        $vcode = $this->vCode;
	        $pheal = new Pheal($userid, $vcode, 'char');

	        try {
		        $response = $pheal->MailMessages(array("characterID" => $this->characterID));

		        // Setting up the message ID list for later message body pulls
		        $messageIDList = '';

		        // Looping through the messages
		        foreach($response->messages as $mail) {

		            $phealLookup = new Pheal($userid, $vcode, 'eve');
		            $phealCharLookup = new Pheal($userid, $vcode, 'char');

		        	// Categorizing the evemails
		        	if($mail->senderID == $this->characterID) {
		        		// This is a sent evemail, so we will get the character names and set them as the receiver
		        		$evemail_type = 'Sent';
		        	} elseif(strpos($mail->toCharacterIDs, $this->characterID) !== FALSE OR ($mail->toAllianceIDs = '' AND $mail->toCorporationIDs = '')) {
		        		$evemail_type = "Inbox";
		        	} elseif(strpos($mail->toCorpOrAllianceID, $this->corporationID) !== FALSE OR ($mail->toCharacterIDs = '' AND $mail->toAllianceIDs = '')) {
		        		$evemail_type = 'Corporation';
		        	} elseif(strpos($mail->toCorpOrAllianceID, $this->allianceID) !== FALSE OR ($mail->toCharacterIDs = '' AND $mail->toCorporationIDs = '')) {
		        		$evemail_type = 'Alliance';
		        	} else {
		        		$evemail_type = 'Mailing List';
		        	}

		        	$evemail_receiver = rtrim(mailAffiliation(implode(',', array($mail->toCharacterIDs, $mail->toCorpOrAllianceID, $mail->toListID)), $phealLookup), ', ');

		            $messageID = $mail->messageID;
		            $senderID = $mail->senderID;
		            $sentDate = $mail->sentDate;
		            $title = $mail->title;

		            $messageIDList = $messageIDList.$messageID.',';

		            $lookupResponse = $phealLookup->CharacterAffiliation(array('ids' => $senderID));

		            $senderName = $lookupResponse->characters[0]->characterName;


					// Adding the message to the DB
					$sql = "INSERT INTO user_evemail (message_id,uid,gid,character_id,evemail_sender,sent_date,evemail_title,evemail_type,evemail_receiver) VALUES (?,?,?,?,?,?,?,?,?)".
								" ON DUPLICATE KEY UPDATE message_id=VALUES(message_id)";
					$stmt = $this->db->prepare($sql);
					$stmt->execute(array($messageID,
										 $this->uid,
										 $this->gid,
										 $this->characterID,
										 $senderName,
										 $sentDate,
										 $title,
										 $evemail_type,
										 trim($evemail_receiver)));
		        }

		        $this->updateMailBodies($messageIDList);

				return TRUE;
			} catch (\Pheal\Exceptions\PhealException $phealException) {
				// Putting the pheal exception through our test function to determine if it's a key failure, or an API server failure.
				$this->testAPIKeyStatus($phealException);
				return FALSE;
			}

		} else {
			// Does not support Evemail
			return FALSE;
		}
	}

	public function updateMailBodies($messageIDList) {
        if(isset($messageIDList) && $messageIDList != '') {
			// Starting the Message Body pulls
			$ids = trim($messageIDList, ',');

			//Working through each message to add to the DB
			// PLACEHOLDER - Needs replacement with Phealng text when i can figure that out.

			$url = "https://api.eveonline.com/char/MailBodies.xml.aspx?keyID=".$this->keyID."&vCode=".$this->vCode."&characterID=".$this->characterID."&ids=".$ids;
			$xml = simplexml_load_file($url);
			$rows = $xml->xpath('/eveapi/result/rowset');
			foreach($rows[0]->row as $row) {
				$body = $row;
				$messageID = $row->attributes()->messageID;

				$stmt = $this->db->prepare("INSERT INTO user_evemail (message_id,character_id,evemail_body) VALUES (?,?,?) ON DUPLICATE KEY UPDATE evemail_body=VALUES(evemail_body)");
				$stmt->execute(array($messageID,
									 $this->characterID,
									 $body));
			}
		}
	}

	/* 
		The checkSkillPreRequisites() function takes an array of skills in the format:
		array(
			[0] =>
				skillID => 12345,
				level => 5,
			[1] =>
				skillID => 12345,
				level => 5,
		);
		This function also requires the characterID to be passed. The Fittings::getPreRequisites() function builds 
		the correct skills array when it is passed a typeID as an argument, and can be publically called.

		Implemented: Version 0.1.0
	*/
	public static function checkSkillPreRequisites($skillArray, $characterID) {
		global $db;

		$stmt = $db->prepare('SELECT * FROM user_skills  WHERE charid = ?');
		$stmt->execute(array($characterID));
		$skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$characterSkillArray = array();
		$result = TRUE;

		foreach($skills as $trainedSkill) {
			$characterSkillArray[$trainedSkill['skillid']] = $trainedSkill['level'];
		}

		foreach($skillArray as $requiredSkill) {
			// Confirming the skill is trained
			if(isset($characterSkillArray[$requiredSkill['skillID']]) AND $characterSkillArray[$requiredSkill['skillID']] != NULL) {
				if($characterSkillArray[$requiredSkill['skillID']] < $requiredSkill['level']) {
					$result = 'Warning';
				}
			} else {
				// Skill is not trained, failing it.
				$result = FALSE;
				break;
			}
		}

		return $result;
	}

	// Function to test a Pheal exception and disable the key if a non-connection API error is detected
	public function testAPIKeyStatus($exception) {

		// Testing the exception text to see if it was a connection error to the API server
		if(strpos($exception, 'ConnectionException') !== FALSE) {
			// Setting the alert to indicate the error exists
			setAlert('danger', 'API Server Unavailable', 'Unable to connect to the API server to fetch information for '.$this->characterID);

			// Taking the current time and subtracting 45 minutes from it to make ALL cronjobs wait 15 minutes before attempting to connect again.
			$delayedCronTime = time() - 2700;

			// Updating the cron to push back checking all keys by 15 minutes.
			$stmt = $this->db->prepare('UPDATE core_cron SET cron_updated = ? WHERE 1=1');
			$stmt->execute(array($delayedCronTime));

			return FALSE;
		} else {
			// Updating the cronjob to not pull this again
			$stmt = $this->db->prepare('UPDATE core_cron SET cron_status = 0 WHERE api_keyID = ?');
			$stmt->execute(array($this->keyID));

			// Disabling the key.
			$stmt = $this->db->prepare('UPDATE core_cron SET keystatus = 0 WHERE api_keyID = ?');
			$stmt->execute(array($this->keyID));

			// Adding the error output to our error_logging DB table
			$stmt = $this->db->prepare('INSERT INTO error_logging (userid,characters,errortext) VALUES (?,?,?) ON DUPLICATE KEY UPDATE errortext=VALUES(errortext)');
			$stmt->execute(array($this->keyID, $this->characterID, $exception));
			setAlert('danger', 'Error Processing Character', 'There has been an error processing one of the characters on this API Key. Please see your Admin/CEO for correction.');
			return FALSE;
		}

	}

	// Depreciated
	public static function fetchCharacterName($characterID) {
		global $db;
		$stmt = $db->prepare('SELECT * FROM characters WHERE charid = ? LIMIT 1');
		$stmt->execute(array($characterID));
		$characterInfo = $stmt->fetch(PDO::FETCH_ASSOC);
		return $characterInfo['charactername'];
	}

	public static function lookupCharacterName($characterID, $user) {
		global $db;
		$pheal = new Pheal($user->getDefaultKeyID(), $user->getDefaultVCode(), 'eve');
		$characterName = $pheal->CharacterName(array('ids' => $characterID));

		return $characterName->characters[0]->name;
	}

	public static function getCharacterImage($characterID, $size) {
		$url = 'https://image.eveonline.com/Character/'.$characterID.'_'.$size.'.jpg';
		return $url;
	}


	public function getUID() {
		return $this->uid;
	}

	public function getGroupID() {
		return $this->gid;
	}

	public function getCharacterID() {
		return $this->characterID;
	}

	public function getkeyID() {
		return $this->keyID;
	}

	public function getVCode() {
		return $this->vCode;
	}

	public function getAccessMask() {
		return $this->accessMask;
	}

	public function getExistance() {
		return $this->characterExists;
	}

	public function getCharacterStatus() {
		return $this->showCharacter;
	}

	public function getCharacterName() {
		return $this->characterName;
	}

	public function getCorporationName() {
		return $this->corporationName;
	}

	public function getCorporationID() {
		return $this->corporationID;
	}

	public function getCorporationTicker() {
		return $this->corporationTicker;
	}

	public function getAllianceName() {
		return $this->allianceName;
	}

	public function getAllianceID() {
		return $this->allianceID;
	}

	public function getAllianceShortName() {
		return $this->allianceShortName;
	}

	public function getCurrentSkillTraining() {
		return $this->currentSkill;
	}

	public function getCurrentTrainingLevel() {
		return $this->currentLevel;
	}

	public function getEndOfTrainingTime() {
		return $this->endOfTraining;
	}

	public function getEndOfQueueTime() {
		return $this->endOfQueue;
	}

	public function getNumberOfQueuedSkills() {
		return $this->skillQueueCount;
	}

	public function getAccountBalance() {
		return $this->accountBalance;
	}

	public function getActiveShipName() {
		return $this->currentShipTypeName;
	}

	public function getActiveShipTypeID() {
		return $this->currentShipTypeID;
	}

	public function getSkillPoints() {
		return $this->skillPoints;
	}

	public function getLastKnownLocation() {
		return $this->lastKnownLocation;
	}

	public function getUnreadEvemailCount() {
		return $this->unreadEvemailCount;
	}
}