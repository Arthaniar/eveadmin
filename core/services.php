<?php
require_once('includes/header.php');

if(isset($_POST['slack_email'])) {
    $sendSlackInvite = sendSlackInvite($_POST['slack_email'], $user->getUsername(), $settings->getSlackAuthToken());
    if($sendSlackInvite) {
      setAlert('success', 'Slack Invitation Sent', 'Check your email for your Slack invite, and remember to register with your Auth username (replacing spaces with underscores).');
    }
}
?>
<div class="opaque-container">

    <div class="row" style="width: 100%; margin-top: 20px; margin-bottom: 20px">
		<div class="col-md-12 opaque-section" style="padding: 0px">
			<div class="row box-title-section">
				<h1 style="text-align: center">External Services Authentication</h1>
			</div>
			<?php showAlerts(); ?>
			<div class="row" style="padding-left: 10px; padding-right: 10px; padding-top: 15px; padding-bottom: 15px">
				<!-- Voice Comms -->
				<div class="col-md-4 col-sm-12">
					<div class="row opaque-section" style="background-image: none; background-color: transparent">
						<div class="row box-title-section">
							<h3 style="text-align: center"><?php echo $settings->getGroupTicker().' '.$settings->getVoiceCommunications(); ?></h3>
							<h4 style="text-align: center; color: #01b43a">Synced</h4>
						</div>
						<div class="row" style="text-align: center; padding-top: 10px">
							<?php
							if($settings->getVoiceCommunications() == 'TS3') {
								$program = 'ts3server';
							} elseif($settings->getVoiceCommunications() == 'Mumble') {
								$program = 'mumble';
							}
							?>
							<a class="btn btn-primary" href="<?php echo $program; ?>://<?php echo $settings->getVoiceAddress().'/?port='.$settings->getVoicePort().'&nickname='.$user->getDefaultCharacter();?>">Connect To <?php echo $settings->getVoiceCommunications(); ?></a>
							<p style="margin-top: 5px">Username: <?php echo $user->getUsername(); ?><br />Password: None</p>
						</div>	
					</div>
				</div>
				<!-- Voice Comms -->
				<?php
		        $status = getSlackAccountStatus($user->getUsername(), $settings->getSlackAuthToken());
		        if($status['account']) {
		          $slackStatus = '<span style="color: #01b43a"> Registered</span>';
		          $slackRegistration = TRUE;
		        } else {
		          $slackStatus = '<span style="color: red"> Not Registered</span>';
		          $slackRegistration = FALSE;
		        }
		        if($status['2fa']) {
		          $twofaStatus = '<span style="color: #01b43a"> Enabled</span>';
		        } else {
		          $twofaStatus = '<span style="color: red"> Not Enabled</span>';
		        }  
				?>
				<div class="col-md-4 col-sm-12">
					<div class="row opaque-section" style="background-image: none; background-color: transparent">
						<div class="row box-title-section">
							<h3 style="text-align: center"><?php if($settings->getSlack()) { echo $settings->GetGroupTicker().' Slack'; } else { 'Slack Integration Disabled'; }  ?></h3>
							<?php
							if($slackRegistration) {
								?>
								<h4 style="text-align: center; color: #01b43a">Registered and Synced</h4>
								
								<?php
							} else {
								?>
								<h4 style="text-align: center; color: red">Unregistered</h4>
								<?php
							}
							?>
						</div>
						<div class="row" style="text-align: center; padding-top: 10px;">
							<?php 
					        if($slackRegistration) {
					        	?>
								<a href="<?php echo $settings->getSlackAddress(); ?>" class="btn btn-primary" target="blank">Open Slack</a>
								<p style="margin-top: 5px">Username: Your Email Address<br />
								Password: Your Chosen Password<br />
								Two Factor Auth: <?php echo $twofaStatus; ?></p>
					        	<?php
					        } else {
					        	?>
								<form action="/services/" method="post">
									<p>Registering will send a Slack invite to your email address. Ensure your Slack username matches your Auth username, with underscores for any spaces.</p>
									<label for="slack_email">Email Address: </label><input class="form-control" type="email" name="slack_email" id="slack_email">
									<input type="submit" class="btn btn-success" value="Register for Slack">
								</form>
					        	<?php
					        }
					        ?>
						</div>
					</div>
				</div>
				<!-- Voice Comms -->
				<div class="col-md-4 col-sm-12">
					<div class="row opaque-section" style="background-image: none; background-color: transparent">
						<div class="row box-title-section">
							<h3 style="text-align: center"><?php if($settings->getForums()){ echo $settings->getGroupTicker().' Forums'; } else { echo 'Forum Integration Disabled'; }; ?></h3>
							<h4 style="text-align: center; color: #01b43a">Registered and Synced</h4>
						</div>
						<div class="row" style="text-align: center; padding-top: 10px">
							<a href="<?php echo $settings->getForumsAddress(); ?>" target="blank" class="btn btn-primary">Go To Forums</a>
							<p style="margin-top: 5px">Username: <?php echo $user->getUsername(); ?><br />
							Password: Your Auth Password</p>
						</div>
					</div>
				</div>
			</div>
		</div>	
    </div>

</div>
<?php
require_once('includes/footer.php');