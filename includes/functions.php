<?php

use Pheal\Pheal;
use Pheal\Core\Config;
Config::getInstance()->cache = new \Pheal\Cache\MemcacheStorage();
Config::getInstance()->access = new \Pheal\Access\StaticCheck();

function standingsCheck($interactionName, $type) {
	global $db;

	$interactionName = trim($interactionName);

	$standingsArray = [
						"Legion of xXDEATHXx" => "-10",
						"Shadow of xXDEATHXx" => "-5",
						"SHOVEL.OF.DEATH" => "-5",
						"Pandemic Legion" => "-10",
						"Northern Coalition." => "-5",
						"Black Legion" => "-5",
						"Mordus Angels" => "-10",
						"Goonswarm Federation" => "10",
						"RAZOR Alliance" => "5",
						"SpaceMonkey's Alliance" => "5",
						"Tactical Narcotics Team" => "5",
						"The Bastion" => "5",
						"Fidelas Constans" => "5",
						"Executive Outcomes" => "5",
						"A Band Apart." => "5",
						"The Terrifying League Of Dog Fort" => "10",
						"Get Off My Lawn" => "10",
						"Garys Most Noble Army of Third Place Mediocrity" => "5",
						"Ashkrall" => "10",
						"Mapache Doom" => "10",
						"Matt18001" => "10",
					];	

	// Checking to see if the character name is in our standings
	if(isset($standingsArray[$interactionName])) {
		$value = $standingsArray[$interactionName];
	} else {

		// Creating the Pheal object for the Owner lookup
		$phealLookup = new Pheal(1, 1, 'eve');

		// Lookup OwnerID page
		$ownerInfo = $phealLookup->OwnerID(array('names' => $interactionName));

		// Geting the typeId and the object class
		$interactionClass = $ownerInfo->owners[0]->ownerGroupID;
		$interactionID = $ownerInfo->owners[0]->ownerID;

		//Guide to Interaction Classes:
		// 1 - character, 2 - corporation, 19 - faction, 32 - alliance

		if($interactionClass != '32' AND $interactionClass != '19' AND $interactionID != '0') {
			if($interactionClass == '1') {
				$lookupResponse = $phealLookup->CharacterAffiliation(array('ids' => $interactionID));

				$corporationID = $lookupResponse->characters[0]->corporationID;
			} else {
				$corporationID = $interactionID;
			}

			// Now we are on a corporation, so we're looking up their corporation ID
			$phealLookupCorp = new Pheal(1, 1, 'corp');
			$corporationInfo = $phealLookupCorp->CorporationSheet(array('corporationID' => $corporationID));
			$corporationName = $corporationInfo->corporationName;
			$allianceName = $corporationInfo->allianceName;
		} else {
			$value = 0;
		}

		// Checking to see if either the corporation or alliance name is in our standings
		if(isset($corporationName) AND isset($standingsArray[$corporationName])) {
			$value = $standingsArray[$corporationName];
		} elseif(isset($allianceName) AND isset($standingsArray[$allianceName])) {
			$value = $standingsArray[$allianceName];
		} else {
			$value = 0;
		}	
	}

	switch($value):
		case "10":
			$color = 'primary';
			$secondary = 'white-space: normal';
			break;
		case "5":
			$color = 'info';
			$secondary = 'white-space: normal';
			break;
		case "-5":

			$color = 'warning';
			$secondary = 'white-space: normal';
			break;
		case "-10":
			$color = 'danger';
			$secondary = 'white-space: normal';
			break;
		default:
			$color = 'default';
			$secondary = 'background-color: transparent; background-image: none; color: #f5f5f5';
			break;
	endswitch;

	if($type == 'button') {
		$return = 'btn-'.$color.'" style="'.$secondary;
	} else {
		$return = '<span class="label label-'.$color.'">';
	}

	return $return;
}


function baseMaskCheck($accessMask) {
	if($accessMask & 8 AND $accessMask & 16777216 AND $accessMask & 262144 AND $accessMask & 131072 AND $accessMask & 33554432) {
		return TRUE;
	} else {
		return FALSE;
	}
}

function spycheck_sort($a ,$b) {

  if ($a['total'] > $b['total']) {
    return -1;
  } elseif  ($a['total'] < $b['total']) {
    return 1;
  } else {
    return strcmp($b['isk_xfer'], $a['isk_xfer']);
  }

}

function navCheck($loggedIn, $html, $optional) {
	if($loggedIn) {
		echo $html;
	} else {
		echo $optional;
	}
}

function setAlert($type, $main, $subtext) {
	$_SESSION['alert-type'] = $type;
	$_SESSION['alert'] = $main;
	$_SESSION['alert-subtext'] = $subtext;
}

function showAlerts() {
	if(isset($_SESSION['alert'])) {
		?>
			<div style="margin-top: 10px" id="solo-alert" class="col-md-offset-2 col-md-8 col-xs-12 alert <?php echo "alert-".$_SESSION['alert-type'];?> opaque-<?php echo $_SESSION['alert-type']; ?> alert-dismissable">
				<button class="close" type="button" data-dismiss="alert" aria-hidden="true">&times;</button>
				<h3 style="text-align: center"><?php echo $_SESSION['alert'];?></h3>
				<p style="text-align: center"><?php echo $_SESSION['alert-subtext'];?></p>
			</div>
		<?php
		unset($_SESSION['alert']);
		unset($_SESSION['alert-type']);
		unset($_SESSION['alert-subtext']);
	}
}

function generateRandom($length) {
	$random_string = '';
	$valid_chars = 'asdfghjklqwertyuiopzxcvbnm1234567890ASDFGHJKLQWERTYUIOPZXCVBNM1234567890!@#$%^&*()';
	$num_valid_chars = strlen($valid_chars);

	for($i = 0; $i < $length; $i++) {
		$random_pick = mt_rand(1,$num_valid_chars);
		$random_char = $valid_chars[$random_pick-1];
		$random_string .= $random_char;
	}
	return $random_string;
}

function timeConversion($queueDate) {
	date_default_timezone_set('UTC');
	$array = array();

	if($queueDate == 'Queue Paused' OR $queueDate == 'Training Paused') {

		if($queueDate == "Training Paused") {
			$array['timestring'] = '---';
		} else {
			$array['timestring'] = $queueDate;
		}
		$array['color'] = 'color: #ac2925"';
	} else {

		$doPlural = function($nb,$str){if($nb >= 1){ return $nb>1?$str.'s':$str; } else { return ''; }};

		$trainingEnd = strtotime($queueDate);
		$currentTime = time();

		$difference = $trainingEnd - $currentTime;

		if($difference >= 1) {
			$days = floor($difference / 86400);
			$remainder = $difference % 86400;

			$hours = floor($remainder / 3600);
			$remainder = $remainder % 3600;

			$minutes = floor($remainder / 60);
			$seconds = floor($remainder % 60);

			$format = array();
			if($days >= 1) {
				$format[] = $days.' '.$doPlural($days, "day");
			}

			if($hours >= 1) {
				$format[] = $hours.' '.$doPlural($hours, "hour");
			}

			if($minutes >= 1) {
				$format[] = $minutes.' '.$doPlural($minutes, "minute");
			}

			if($seconds >= 1) {
				$format[] = $seconds.' '.$doPlural($seconds, "second");
			}

		    unset($format[2]);
		    unset($format[3]);

		} else {
			$days = 0;
			$hours = 0;
			$minutes = 0;
			$seconds = 0;
		}

	    if($days >= 1 ){
	    	$array['color'] = 'color: #01b43a';
	    } elseif($hours >= 6){
	    	$array['color'] = 'color: #e67b0d';
	    	unset($format[3]);
	    } else {
	    	$array['color'] = 'color: #ac2925';
	    }

	    if(empty($format)) {
	    	$array['timestring'] = '---';
	    	$array['color'] = 'color: red';
	    } else {
	    	$array['timestring'] = implode(" and ", $format);
	    }
	}
    return $array;
}

function getPreReqs($typeID) {
	global $db;
	$stmt = $db->prepare("SELECT * FROM dgmTypeAttributes WHERE typeID = ?");
	$stmt->execute(array($typeID));
	$typeInfo = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$skill = array();

	foreach($typeInfo as $info) {

		// Working through the attributes to get the skill IDs and levels
		if($info['attributeID'] == "182") {
			if($info['valueInt'] === NULL) {
				$skill[0]['id'] = $info['valueFloat'];
			} else {
				$skill[0]['id'] = $info['valueInt'];
			}
		} elseif($info['attributeID'] == "183") {
			if($info['valueInt'] === NULL) {
				$skill[1]['id'] = $info['valueFloat'];
			} else {
				$skill[1]['id'] = $info['valueInt'];
			}
		} elseif($info['attributeID'] == "184") {
			if($info['valueInt'] === NULL) {
				$skill[2]['id'] = $info['valueFloat'];
			} else {
				$skill[2]['id'] = $info['valueInt'];
			}
		} elseif($info['attributeID'] == "277") {
			if($info['valueInt'] === NULL) {
				$skill[0]['level'] = $info['valueFloat'];
			} else {
				$skill[0]['level'] = $info['valueInt'];
			}
		} elseif($info['attributeID'] == "278") {
			if($info['valueInt'] === NULL) {
				$skill[1]['level'] = $info['valueFloat'];
			} else {
				$skill[1]['level'] = $info['valueInt'];
			}
		} elseif($info['attributeID'] == "279") {
			if($info['valueInt'] === NULL) {
				$skill[2]['level'] = $info['valueFloat'];
			} else {
				$skill[2]['level'] = $info['valueInt'];
			}
		}
	}
	return $skill;
}

function testPreReqs($typeID,$charID) {
	global $db;
	global $eve;
	$preRequisiteArray = array();
	$preReqRawArray = array();

	// Getting the prerequisites for the skill
	$skill = getPreReqs($typeID);

	// The TypeID check for 164 here specifically looks for the Clone Grade Alpha/Beta skill. If Alpha is a prerequisite, then it means there are no pre-requisites. If Beta is a prerequisite, it just means it cannot be trained on a trial account. Oh, CCP...
	if(isset($skill[0]['id']) AND $skill[0]['id'] != "164" AND $skill[0]['id'] != "165") {
		$stmt = $db->prepare("SELECT * FROM user_skills  WHERE skillid = ? AND charid = ? LIMIT 1");

		$warningCount = 0;

		// Looping through each skill requirement
		foreach($skill as $requirement) {
			// Fetching our character's skills for this requirement
			$stmt->execute(array($requirement['id'], $charID));
			$requirementInfo = $stmt->fetch(PDO::FETCH_ASSOC);

			if($requirementInfo == FALSE OR $requirementInfo['level'] === '') {
				$trainedLevel = 'Untrained';
			} else {
				$trainedLevel = $requirementInfo['level'];
			}


			if(!isset($requirementInfo['level']) OR $requirementInfo['level'] < $requirement['level'] OR $trainedLevel == 'Untrained') {

				$warningCount++;
				$preRequisiteArray[$warningCount]['skillID'] = $requirement['id'];
				$preRequisiteArray[$warningCount]['required_level'] = $trainedLevel;

				$preReqRawArray[] = $eve->getTypeName($requirement['id']).' '.$requirement['level'].' ('.$trainedLevel.')';
			}
		}

		if($warningCount >= 1) {
			$preRequisiteArray['status'] = FALSE;
		} else {
			$preRequisiteArray['status'] = TRUE;
		}
	} else {
		$preRequisiteArray['status'] = TRUE;
	}

	$preRequisiteArray['required-skills-string'] = implode(', ', $preReqRawArray);

	return $preRequisiteArray;
}

function convertToNumeral($num) {
	switch($num):
		case 1:
			return "I";
		case 2:
			return "II";
		case 3:
			return "III";
		case 4:
			return "IV";
		case 5:
			return "V";
		default:
			return 0;
	endswitch;
}

function getCountdownTimer($id,$datestring) {
	?>
	  <script type="text/javascript">
	      $('#<?php echo $id; ?>').countdown('<?php echo $datestring; ?>', function(event) {
	      	if(event.offset.totalDays >= 1) {
	        	$(this).html(event.strftime('%-D day%!D, %-H hour%!H'));
	        } else if (event.offset.hours >= 1 ) {
	        	$(this).html(event.strftime('%-H hour%!H and %-M minute%!M'));
	        } else {
	        	$(this).html(event.strftime('%-M minute%!M'));
	        }
	      });
	  </script>
	<?php
}

function mailAffiliation($ids, $phealLookup) {
	$lookupResponse = $phealLookup->CharacterAffiliation(array('ids' => $ids));

	$list = '';
	foreach($lookupResponse->characters as $corps) {
		$list = $list . $corps->characterName.', ';
	}
	
	return $list;
}

function sendSlackNotification($botName, $channel, $message, $icon, $url) {

	$fieldstring = '{"channel": "#'.$channel.'", "username": "'.$botName.'", "text": "'.$message.'", "icon_emoji": ":'.$icon.':"}';

	$ch = curl_init();

	curl_setopt($ch,CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 'payload');
	curl_setopt($ch, CURLOPT_POSTFIELDS, $fieldstring);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

	$result = curl_exec($ch);

	curl_close($ch);
}

function sendComplexSlackNotification($token, $botName, $channel, $message, $icon, $apiNode) {
	$emoji = urlencode(':'.$icon.':');

	$url = 'https://slack.com/api/'.$apiNode.'?token='.$token.'&channel='.$channel.'&text='.urlencode($message).'&username='.urlencode($botName).'&icon_emoji='.$emoji;

	$ch = curl_init();

	curl_setopt($ch,CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

	$result = curl_exec($ch);

	curl_close($ch);
}

function sendSlackInvite($email, $username, $token) {

	$url = 'https://dogft.slack.com/api/users.admin.invite?t='.time();

	$fields = array(
			'email' => urlencode($email),
			'channels' => urlencode('C09E811J5,C09E883ED'),
			'first_name' => $username,
			'token' => $token,
			'set_active' => urlencode('true'),
			'_attempts' => '1'
		);

	$fields_string = '';

	foreach($fields as $key => $value) {
		$fields_string .= $key.'='.$value.'&';
	}

	rtrim($fields_string, '&');

	$ch = curl_init();

    curl_setopt($ch,CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch,CURLOPT_POST, count($fields));
    curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);

    $replyRaw = curl_exec($ch);
    $reply=json_decode($replyRaw,true);
    if($reply['ok']==false) {
            return FALSE;
    }
    else {
            return TRUE;
    }

}

function getSlackAccountStatus($username, $token) {

	$url = 'https://slack.com/api/users.list';
	$fields = array( 'token' => $token, 'presence' => 0);

	$fields_string = '';

	foreach($fields as $key => $value) {
		$fields_string .= $key.'='.$value.'&';
	}

	rtrim($fields_string, '&');

	$ch = curl_init();

    curl_setopt($ch,CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch,CURLOPT_POST, count($fields));
    curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);


    $replyRaw = curl_exec($ch);
    $reply=json_decode($replyRaw,true);

    $return = array();
    $return['account'] = FALSE;
    $return['2fa'] = FALSE;

    foreach($reply['members'] as $member) {
    	if($member['name'] == str_replace(' ', '_', strtolower($username)) OR $member['name'] == str_replace(' ', '', strtolower($username))) {
    		$return['account'] = TRUE;
    		$return['2fa'] = $member['has_2fa'];
    	}
    }

    return $return;
}

?>
