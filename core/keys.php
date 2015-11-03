<?php

if($request['action'] != NULL) {
	if($request['action'] == 'refresh') {
		$keyID = $request['value'];
		$vCode = $request['value_2'];
		$key = new ApiKey($keyID, $vCode, $user, $db);
		if($key->getKeyStatus() == 1 AND $key->getAccessMask() == MINIMUM_API) {
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
						$char->updateCharacterSkills();
					}

					unset($character_array[$char->getCharacterID()]);
				}

				if(!empty($character_array)) {
					foreach($character_array as $delete_character) {
						Character::deleteCharacter($delete_character, $user->getUID());
					}
				}
				$refresh = $key->refreshAPIKey();
				setAlert('success', 'API Key Refreshed', 'The API key has been successfully refreshed.');
								
			}
		} elseif(!($key->getAccessMask() == MINIMUM_API) AND $key->getKeyStatus() == 1) {
			setAlert('danger', 'The API Key Does Not Meet Minimum Requirements', 'The required minimum Access Mask for API keys is '.MINIMUM_API.'. Please create a new key using the Create Key link.');
		}
	} elseif ($request['action'] == 'delete') {
		ApiKey::deleteKey($request['value'], $user);
	} elseif($request['action'] == 'add') {
		$keyID = $_POST['keyID'];
		$vCode = $_POST['vCode'];
		$key = new ApiKey($keyID, $vCode, $user, $db);
		if($key->getKeyStatus() == 1 AND $key->getAccessMask() == MINIMUM_API) {
			$update = $key->updateApiKey();
			if($update) {
			    if($settings->getSlackIntegration()) {
  					sendComplexSlackNotification($settings->getSlackAuthToken(), $settings->getGroupTicker().' Auth Notifications', 'uncle_toucheys', 'New API Key submitted by '.$user->getUserName().' with Access Mask of '.$key->getAccessMask().'.', 'aura', 'chat.postMessage');
				}
				foreach($key->getCharacters() as $character) {
					$char = new Character($character['characterID'], $key->getKeyID(), $key->getVCode(), $key->getAccessMask(), $db, $user);
					if($char->getExistance() OR $char->getExistance() == FALSE) {
						$char->updateCharacterInfo();
						$char->updateCharacterSkills();
					}
				}
				setAlert('success', 'API Key Added', 'The API key has been added. Character skills and other information will populate over the next 10-15 minutes.');			
			}
		} elseif(!($key->getAccessMask() == MINIMUM_API) AND $key->getKeyStatus() == 1) {
			setAlert('danger', 'The API Key Does Not Meet Minimum Requirements', 'The required minimum Access Mask for API keys is '.MINIMUM_API.'. Please create a new key using the Create Key link.');
		}
	}
} 

$stmt = $db->prepare('SELECT * FROM user_apikeys WHERE uid = ? ORDER BY userid ASC');
$stmt->execute(array($user->getUID()));
$apiKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once('includes/header.php');
?>
<div class="opaque-container" role="tablist" aria-multiselectable="true">

    <div class="row" style="width: 100%; margin-top: 20px; margin-bottom: 20px">
		<div class="col-md-12 opaque-section" style="padding: 0px">
			<div class="row box-title-section">
				<a class="box-title-link" style="text-decoration: none" >
					<h1 class="eve-text" style="margin-top: 10px; text-align: center; font-size: 200%; font-weight: 700">API Key Management</h1>
				</a>
			</div>
			<?php showAlerts(); ?>
			<div>
				<div class="col-md-12">
					<div class="row" style="padding-top: 20px;">
						<table class="table table-striped">
							<tr>
								<th>Key ID</th>
								<th>Verification Code</th>
								<th>Key Info</th>
								<th>Change Primary Character</th>
								<th>Actions</th>
							</tr>
							<tr>
								<form action="/keys/add/" method="post">
									<formfield>
										<td><input class="form-control" name="keyID" type="text"></td>
									</formfield>
									<formfield>
										<td><input class="form-control" name="vCode" type="text"></td>
									</formfield>
									<td>
										<a href="https://community.eveonline.com/support/api-key/CreatePredefined?accessMask=<?php echo MINIMUM_API; ?>" target="blank">
											<span style="font-size: 90%; color: #428bca">Create Key</span>
										</a>
									</td>
									<td></td>
									<td><input class="btn btn-primary btn-sm" type="submit" value="Add API Key"></td>
								</form>
							</tr>
							<?php
							foreach($apiKeys as $key) {

								// Getting the key status. We check to ensure the key is valid, that the mask is correct, and that the key is account-wide
								if($key['keystatus'] == 1 AND $key['mask'] == MINIMUM_API AND $key['keyType'] == 'Account') {
									$key_status = '<label class="label label-success">Valid Key</label>';
								} elseif ($key['keystatus'] != 1) {
									$key_status = '<label class="label label-danger">Invalid Key</label>';
								} elseif($key['mask'] != MINIMUM_API) {
									$key_status = '<label class="label label-danger">Incorrect Key Permissions - '.$key['mask'].'</label>';
								} elseif($key['keyType'] != 'Account') {
									$key_status = '<label class="label label-danger">Single-Character Key</label>';
								}

								// Now we are getting all of the characters for the key
								$stmt = $db->prepare('SELECT * FROM characters WHERE userid = ? AND uid = ? ORDER BY skillpoints DESC');
								$stmt->execute(array($key['userid'], $user->getUID()));
								$character_lookup = $stmt->fetchAll(PDO::FETCH_ASSOC);

								// Creating an empty character string to build the picture list for
								$character_string = '';
								foreach($character_lookup as $character) {
									$character_string = $character_string.'<a style="margin-left: 5px; margin-right: 5px;" href="/keys/setprimary/'.$character['charid'].'/"><img width="40" height="40" src="https://image.eveonline.com/Character/'.$character['charid'].'_64.jpg"></a>';
								}

								?>
									<tr style="vertical-align: center">
										<td><?php echo $key['userid']; ?></td>
										<td><?php echo $key['vcode']; ?></td>
										<td><?php echo $key_status; ?></td>
										<td><?php echo $character_string; ?></td>
										<td>
											<a href="/keys/refresh/<?php echo $key['userid'];?>/<?php echo $key['vcode']; ?>" class="btn btn-success">
												<span class="glyphicon glyphicon-refresh"></span>
											</a>
											<a href="/keys/delete/<?php echo $key['userid'];?>" class="btn btn-danger">
												<span class="glyphicon glyphicon-remove"></span>
											</a>
										</td>
									</tr>
								<?php
							}
							?>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
require_once('includes/footer.php');