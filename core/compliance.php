<?php
require_once('includes/header.php');
use Pheal\Pheal;
use Pheal\Core\Config;
Config::getInstance()->cache = new \Pheal\Cache\MemcacheStorage();
Config::getInstance()->access = new \Pheal\Access\StaticCheck();

// Getting the compliance type
if($request['action'] == 'api') {

	if($request['value'] == 'refresh') {
		$key = new ApiKey($_POST['keyID'], $_POST['vCode'], $_POST['uid'], $db);
		if($key->getKeyStatus() == 1 AND $key->getAccessMask() & MINIMUM_API) {
			$update = $key->updateApiKey();
			if($update) {
				foreach($key->getCharacters() as $character) {
					$char = new Character($character['characterID'], $key->getKeyID(), $key->getVCode(), $key->getAccessMask(), $db, $user);
					if($char->getExistance() OR $char->getExistance() == FALSE) {
						$char->updateCharacterInfo();
					}
				}
				$refresh = $key->refreshAPIKey();
				setAlert('success', 'API Key Refreshed', 'The API key has been successfully refreshed.');
								
			}
		} elseif(!($key->getAccessMask() & MINIMUM_API) AND $key->getKeyStatus() == 1) {
			setAlert('danger', 'The API Key Does Not Meet Minimum Requirements', 'The required minimum Access Mask for API keys is '.MINIMUM_API.'. Please create a new key using the Create Key link.');
		}
	} 
	// We're doing API compliance
	$compliance_type = "API";

	// Getting a full API-pulled member list
    $pheal = new Pheal($settings->getCorpUserID(), $settings->getCorpVCode(), 'corp');
    $response = $pheal->MemberTracking(array("extended" => 1));
    $memberList = array();
    foreach($response->members as $member) {
      $memberList[$member->name]['name'] = $member->name;
      $memberList[$member->name]['id'] = $member->characterID;
    }
    sort($memberList);

	// Working through the member list
	$stmt = $db->prepare('SELECT * FROM characters WHERE charid = ?');
	$stmt_api = $db->prepare('SELECT * FROM user_apikeys WHERE userid = ?');

} elseif($request['action'] == 'doctrine') {
	$compliance_type = 'doctrine';

} elseif($request['action'] == 'skill') {
	$compliance_type = 'skill';
	
}
?>
<div class="opaque-container">
    <div class="row" style="width: 100%; margin-top: 20px; margin-bottom: 20px">
		<div class="col-md-12 opaque-section" style="padding: 0px">
			<div class="row box-title-section">
				<h1 style="text-align: center"><?php echo $compliance_type; ?> Compliance<h1>
			</div>
			<?php showAlerts(); ?>
			<div class="row" style="padding-left: 10px; padding-right: 10px">
				<table class="table table-striped">
					<?php
					if($compliance_type == "API") {
						?>
						<thead>
							<tr>
								<th>Character</th>
								<th style="text-align: center">User Account</th>
								<th style="text-align: center">API Key ID</th>
								<th style="text-align: center">API Access Mask</th>
								<th style="text-align: center">Key Type</th>
								<th style="text-align: center">Key Status</th>
								<th style="text-align: center">Key Actions</th>
							</tr>
						</thead>
						<tbody>
						<?php
						foreach($memberList as $member) {

							$failure = 0;

							$stmt->execute(array($member['id']));
							$character = $stmt->fetch(PDO::FETCH_ASSOC);

							$stmt_api->execute(array($character['userid']));
							$apikey = $stmt_api->fetch(PDO::FETCH_ASSOC);

							if(isset($character['uid'])) {
			                    $stmt_acct = $db->prepare('SELECT * from user_accounts WHERE uid = ? LIMIT 1');
			                    $stmt_acct->execute(array($character['uid']));
			                    $account_name = $stmt_acct->fetch(PDO::FETCH_ASSOC);

			                    // Checking the Mask to confirm it's what we're looking for
			                    if(isset($apikey['mask'])) {
			                    	if($apikey['mask'] == MINIMUM_API) {
			                    		$mask_display = '<span style="color: #01b43a">Full API Key</span>';
			                    	} else {
			                    		$mask_display = '<span style="color: #ac2925">'.$apikey['mask'].'</span>';
			                    		$failure = TRUE;
			                    	}
			                    } else {
			                    	$mask_display = '<span style="color: #ac2925">No API Submitted</span>';
			                    	$failure = TRUE;
			                    }

			                    // Checking the Key Type to confirm it's account-wide
			                    if(isset($apikey['keyType'])) {
			                    	if($apikey['keyType'] == 'Account') {
			                    		$type_display = '<span style="color: #01b43a">Account</span>';
			                    	} else {
			                    		$type_display = '<span style="color: #ac2925">Character</span>';
			                    		$failure = TRUE;
			                    	}
			                    } else {
			                    	$type_display = '---';
			                    	$failure = TRUE;
			                    }

			                    if(isset($apikey['keystatus'])) {
			                    	if($apikey['keystatus'] == 1) {
			                    		$status_display = '<span style="color: #01b43a">Valid Key</span>';
			                    	} else {
			                    		$status_display = '<span style="color: #ac2925">Invalid Key</span>';
			                    		$failure = TRUE;
			                    	}
			                    } else {
			                    	$status_display = '---';
			                    	$failure = TRUE;
			                    }

			                    if($failure) {
			                    	$background = 'class="opaque-danger"';
			                    } else {
			                    	$background = '';
			                    }

			                    // Checking the validity of the key
			                    ?>
			                    	<tr <?php echo $background; ?>>
			                    		<td><?php echo $member['name']; ?></td>
			                    		<td style="text-align: center"><a href="/spycheck/<?php echo $character['uid']; ?>/"><?php echo $account_name['username']; ?></a></td>
			                    		<td style="text-align: center"><?php echo $apikey['userid']; ?></td>
			                    		<td style="text-align: center"><?php echo $mask_display;?></td>
			                    		<td style="text-align: center"><?php echo $type_display;?></td>
			                    		<td style="text-align: center"><?php echo $status_display;?></td>
			                    		<td style="text-align: center">
			                    			<form method="post" action="/compliance/api/refresh/">
			                    				<input type="hidden" name="keyID" value="<?php echo $apikey['userid']; ?>">
			                    				<input type="hidden" name="vCode" value="<?php echo $apikey['vcode']; ?>">
			                    				<input type="hidden" name="uid" value="<?php echo $apikey['uid']; ?>">
			                    				<button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-refresh"></span></button>
			                    			</form>
			                    		</td>
			                    	</tr>
			                    <?php
							}
						}
						?>
						</tbody>
						<?php
					} elseif($compliance_type == "doctrine") {

					} elseif($compliance_type == 'skill')
					?>
				</table>
			</div>
		</div>	
    </div>

</div>
<?php
require_once('includes/footer.php');