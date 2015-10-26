<?php
require('includes/header-no-nav.php');

//Checking to see if we've already submitted registration
if(isset($_POST['username'])) {
	//We have, registering the account
	$keyAddition = new ApiKey($_POST['keyID'], $_POST['vCode'], 0, $db);
	$success = User::doRegistration($_POST['username'], $_POST['password'], $_POST['password_verify'], $_POST['default_id'], $db);

	if($success) {
		$stmt = $db->prepare('SELECT * FROM user_accounts WHERE username = ? LIMIT 1');
		$stmt->execute(array($_POST['username']));
		$accountInfo = $stmt->fetch();
		$keyAddition = new ApiKey($_POST['keyID'], $_POST['vCode'], $accountInfo['uid'], $db);
		$keyAddition->updateAPIKey();
		$char = new Character($_POST['default_id'], $keyAddition->getKeyID(), $keyAddition->getVCode(), $keyAddition->getAccessMask(), $db, $accountInfo['uid']);
		if($char->getExistance() OR $char->getExistance() == FALSE) {
		$char->updateCharacterInfo();
		}
		$register_success = TRUE;
	} else {
		$register_success = FALSE;
	}
}
?>

    <div class="opaque-container" style="height: 100%; padding-bottom: 50px; margin-top: 10%">

	    <div class="row" style="width: 100%;">
		<?php 
		if($request['action'] == '1' OR ! $request['action']) {
			?>
			<div class="col-md-offset-4 col-md-4 col-sm-offset-2 col-sm-8 mobile-reconfig" style="padding-right: 0px">
				<?php showAlerts(); ?>
				<div class="col-md-12 opaque-section" style="padding: 0px">
					<div class="row box-title-section">
						<h3 class="eve-text" style="text-align: center; font-size: 250%"><?php echo SITE_NAME; ?></h3>
					</div>
					<div class="row" style="padding-left: 10px; padding-right: 10px">
						<p class="eve-text" style="font-size: 150%; text-align: center">Registration requires a full, non-expiring, account wide API key. Please create a key using <a href="https://community.eveonline.com/support/api-key/CreatePredefined?accessMask=<?php echo MINIMUM_API; ?>" target="_blank">this link</a>, and submit the keyID and verification code below.</p>
						<form method="post" action="/register/2/" name="registerform" id="registerform" style="margin-bottom: 15px; margin-top: 15px">
							<fieldset>
								<input class="form-control" type="text" placeholder="API Key ID" name="keyID">
							</fieldset>
							<fieldset>
								<input class="form-control" type="text" placeholder="API Key Verification Code" name="vCode" style="margin-top: 5px">
							</fieldset>
							<input class="btn btn-primary btn-lg eve-text pull-right" style="margin-top: 5px; margin-bottom: 5px; border-radius: 0px" type="submit" name="register_step_1" value="Continue">
	
							<a style="color: #65a9cc; margin-top: 15px;" href="/login/" class="pull-left">Already have an account?</a>
						</form>
					</div>

				</div>
			</div>
		<?php
		} elseif($request['action'] == '2') {
			$key = new ApiKey($_POST['keyID'], $_POST['vCode'], 0, $db);
			if($key->getKeyStatus() == 1) {
				if($key->getAccessMask() & MINIMUM_API OR $key->getExpiration() != 'No Expiration' OR $key->getKeyType() != 'Account') {
					?>
					<div class="col-md-offset-3 col-md-6 col-sm-offset-2 col-sm-8 mobile-reconfig" style="padding-right: 0px">
						<?php showAlerts(); ?>
						<div class="col-md-12 opaque-section" style="padding: 0px">
							<div class="row box-title-section">
								<h3 class="eve-text" style="text-align: center; font-size: 250%"><?php echo SITE_NAME; ?></h3>
							</div>
							<div class="row" style="padding-left: 10px; padding-right: 10px">
								<p class="eve-text" style="font-size: 150%; text-align: center">Select the character you would like associated as your Main Character. This will be your TS3 name, Slack name, Forum name, and Auth name, so it is strongly recommended you go back and provide a key for your main character if it does not appear below.</p>
								<div class="row">
									<?php
									foreach($key->getCharacters() as $character) {
										?>
										<div class="col-md-4 col-sm-12" style="text-align: center">
											<form method="post" action="/register/3/">
												<input type="hidden" name="keyID" value="<?php echo $key->getKeyID();?>">
												<input type="hidden" name="vCode" value="<?php echo $key->getVCode();?>">
												<input type="hidden" name="characterID" value="<?php echo $character['characterID'];?>">
												<input type="hidden" name="characterName" value="<?php echo $character['characterName'];?>">
												<input type="image" src="https://image.eveonline.com/Character/<?php echo $character['characterID']; ?>_128.jpg" alt="Use Character">
											</form>
											<p><?php echo $character['characterName']; ?><br />
											<?php echo $character['corporationName']; ?><br />
											<?php echo $character['allianceName']; ?></p>
										</div>
										<?php
									}
									?>
								</div>
							</div>
						</div>
					</div>
					<?php
				} else {
					// The key is not valid, setting a warning and heading back to Step 1
					setAlert('danger', 'API Key Invalid', 'Please verify that the key you have provided is a full, account-wide API Key with no expiry.');
					?><META http-equiv="refresh" content="0;URL=/register/1/"><?php
				}

			} else {
				// The key is not valid, setting a warning and heading back to Step 1
				setAlert('danger', 'API Key Invalid', 'Please verify that the key you have provided is a full, account-wide API Key with no expiry.');
				?><META http-equiv="refresh" content="0;URL=/register/1/"><?php
			}
			?>
			<?php
		} elseif($request['action'] == '3') {
			?>

			<div class="col-md-offset-3 col-md-6 col-sm-offset-2 col-sm-8 mobile-reconfig" style="padding-right: 0px">
				<?php showAlerts(); ?>
				<div class="col-md-12 opaque-section" style="padding: 0px">
					<div class="row box-title-section">
						<h3 class="eve-text" style="text-align: center; font-size: 250%"><?php echo SITE_NAME; ?></h3>
					</div>
					<div class="row" style="padding-left: 10px; padding-right: 10px; text-align: center">
						<p class="eve-text" style="font-size: 150%; text-align: center">You are registering with the Main Character <?php echo $_POST['characterName']; ?></p>
						<p class="eve-text" style="font-size: 125%; text-align: center">Complete the registration form below to finalize your account.</p>
						<div class="row">
						        <form action="/register/4/" id="registerform" method="post" name="registerform">
						      		<fieldset style="margin-bottom: 15px">
										<span style="margin-bottom: 10px">Username: <?php echo $_POST['characterName']; ?> (cannot be changed)</span>
										<input type="hidden" name="username" id="username" value="<?php echo $_POST['characterName']; ?>" />
										<input type="hidden" name="default_id" id="default_id" value="<?php echo $_POST['characterID']; ?>" />
										<input type="hidden" name="keyID" id="keyID" value="<?php echo $_POST['keyID']; ?>" />
										<input type="hidden" name="vCode" id="vCode" value="<?php echo $_POST['vCode']; ?>" /><br /> 
						            </fieldset>
						            <fieldset>
										<label style="font-weight: normal" for="password">Password:</label>
										<input class="form-control" style="width: 60%; margin-left: auto; margin-right: auto" placeholder="Account Password" type="password" name="password" id="password" /><br />
									</fieldset>
									<fieldset>
										<label style="font-weight: normal" for="password_verify">Password:</label>
										<input class="form-control" style="width: 60%; margin-left: auto; margin-right: auto" placeholder="Verify Password" type="password" name="password_verify" id="password_verify" /><br />
									</fieldset>
									<fieldset style="margin-bottom: 15px; margin-left: auto; margin-right: auto; text-align: center">
										<input class="btn btn-success" type="submit" value="Register" /> 
										<input type="reset" class="btn btn-danger" name="reset" id="reset" value="Reset">
						          </fieldset>
						        </form>
						</div>
					</div>
				</div>
			</div>
			<?php
		} elseif($request['action'] == '4') {
			if($register_success) {
				?>
				<div class="col-md-offset-3 col-md-6 col-sm-offset-2 col-sm-8 mobile-reconfig" style="padding-right: 0px">
					<?php showAlerts(); ?>
					<div class="col-md-12 opaque-section" style="padding: 0px">
						<div class="row box-title-section">
							<h3 class="eve-text" style="text-align: center; font-size: 250%"><?php echo SITE_NAME; ?></h3>
						</div>
						<div class="row" style="padding-left: 10px; padding-right: 10px; text-align: center">
							<p class="eve-text" style="font-size: 150%; text-align: center">Your account has been successfully created.</p>
							<a href="/dashboard/" class="btn btn-primary eve-text" style="font-size: 125%; text-align: center; margin-bottom: 15px">Click Here To Log In</a>
						</div>
					</div>
				</div>
				<?php
			} else {
				// The character is being used, setting a warning and heading back to Step 1
				setAlert('danger', 'Registration Failed', 'The Main Character you have selected is already in use. Please contact your recruiter for assistance.');
				?><META http-equiv="refresh" content="0;URL=/register/1/"><?php
			}
		}
		?>
		</div>
	</div>
<?php
require('includes/footer.php');