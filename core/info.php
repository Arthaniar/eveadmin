<?php
require_once('includes/header.php');

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
				$refresh = $key->refreshAPIKey();
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
?>
<div class="opaque-container">

    <div class="row" style="width: 100%; margin-top: 20px; margin-bottom: 20px">
		<div class="col-md-12 opaque-section" style="padding: 0px">
			<div class="row box-title-section">
				<h1 style="text-align: center"><?php echo $page_type; ?> Information</h1>
			</div>
			<?php showAlerts(); ?>
			<div class="row" style="padding-left: 10px; padding-right: 10px">
				<table class="table table-striped">
					<?php
					if($page_type == 'API') {
						?>
						<tr>
							<th style="text-align: center">Key ID</th>
							<th style="text-align: center">Key Owner</th>
							<th style="text-align: center">Characters</th>
							<th style="text-align: center">Key Status</th>
							<th style="text-align: center">Actions</th>
						</tr>
						<?php
						foreach($keys as $key) {
							$stmt = $db->prepare('SELECT * FROM characters where userid = ? ORDER BY skillpoints DESC');
							$stmt->execute(array($key['userid']));
							$characters = $stmt->fetchAll(PDO::FETCH_ASSOC);

							if($key['keystatus'] == 1) {
								if($key['mask'] == MINIMUM_API) {
									if($key['expires'] == 'No Expiration') {
										if($key['keyType'] == 'Account') {
											$key_status = '<span class="label label-success">Valid And Correct API Key</span>';
										} else {
											$key_status = '<span class="label label-danger">Single Character Key</span>';
										}
									} else {
										$key_status = '<span class="label label-danger">Key Expires</span>';
									}
								} else {
									$key_status = '<span class="label label-danger" data-toggle="tooltip" data-placement="top" title="'.$key['mask'].'">Incorrect Mask</span>';
								}
							} else {
								$key_status = '<span class="label label-danger">Invalid Key</span>';
							}

							?>
							<tr style="text-align: center">
								<td><?php echo $key['userid']; ?></td>
								<td><?php echo $key['username']; ?></td>
								<td><?php foreach($characters as $character) { ?><img style="margin-left: 2px; margin-right: 2px" data-toggle="tooltip" data-placement="top" title="<?php echo $character['charactername']; ?> | <?php echo $character['corporation']; ?> | <?php echo $character['alliance']; ?>" src="<?php echo Character::getCharacterImage($character['charid'], 30); ?>"><?php } ?></td>
								<td><?php echo $key_status; ?></td>
								<td>
									<a href="/info/apis/refresh/<?php echo $key['userid']; ?>/" class="btn btn-primary"><span class="glyphicon glyphicon-refresh"></span></a>
									<a href="/info/apis/delete/<?php echo $key['userid']; ?>/" class="btn btn-danger"><span class="glyphicon glyphicon-remove"></span></a>
								</td>
							</tr>
							<?php
						}
					} elseif($page_type = "Characters") {
						?>
						<tr>
							<th style="text-align: center">Character</th>
							<th style="text-align: center">Owner</th>
							<th style="text-align: center">Corporation</th>
							<th style="text-align: center">Alliance</th>
						</tr>
						<?php
						foreach($characters as $character) {

							?>
							<tr style="text-align: center">
								<td><?php echo $character['charactername']; ?></td>
								<td><?php echo $character['username']; ?></td>
								<td><?php echo $character['corporation']; ?></td>
								<td><?php echo $character['alliance']; ?></td>
							</tr>
							<?php
						}
					}
					?>
				</table>
			</div>
		</div>	
    </div>

</div>
<?php
require_once('includes/footer.php');