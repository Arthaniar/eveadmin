<?php
require_once('includes/header.php');

if($request['action'] == 'group' AND $user->getCEOAccess()) {
	$settings_type = 'Group';

	if(isset($_POST['enable'])) {
		if($_POST['enable'] == 'voice') {
			$stmt = $db->prepare('UPDATE group_settings SET group_voice_integration = 1 WHERE gid = ?');
			$stmt->execute(array($user->getGroup()));
		} elseif($_POST['enable'] == 'forum') {
			$stmt = $db->prepare('UPDATE group_settings SET group_forum_integration = 1 WHERE gid = ?');
			$stmt->execute(array($user->getGroup()));
		} elseif($_POST['enable'] == 'slack') {
			$stmt = $db->prepare('UPDATE group_settings SET group_slack_integration = 1 WHERE gid = ?');
			$stmt->execute(array($user->getGroup()));
		}
	} elseif(isset($_POST['update'])) {
		if($_POST['update'] == 'slack') {
			$stmt = $db->prepare('UPDATE group_settings SET group_slack_address = ?, group_slack_auth_token = ?, group_slack_ops_notifications = ?, '.
					'group_slack_api_notifications = ?, group_slack_main_channel = ?, group_slack_api_channel = ?, group_slack_ops_channel = ? WHERE gid = ?');
			$stmt->execute(array($_POST['slack_address'], 
								 $_POST['slack_auth_token'], 
								 $_POST['slack_ops_notifications'],
								 $_POST['slack_api_notifications'],
								 $_POST['slack_main_channel'],
								 $_POST['slack_api_channel'],
								 $_POST['slack_ops_channel']));
		}
	}
} elseif($request['action'] == 'account' OR ($request['action'] == 'group' AND !$user->getCEOAccess())) {
	$settings_type = 'Account';
}
?>
<div class="opaque-container">

    <div class="row" style="width: 100%; margin-top: 20px; margin-bottom: 20px">
		<div class="col-md-12 opaque-section" style="padding: 0px">
			<div class="row box-title-section">
				<h1 style="text-align: center"><?php echo $settings_type; ?> Settings</h1>
				<?php if($request['action'] == 'group') {
					?>
					<h3 style="text-align: center">Showing all group settings for <?php echo $settings->getGroupName(); ?></h3>
					<?php
				}
				?>
			</div>
		</div>	
    </div>
    <?php
    if($settings_type == "Group") {
	    ?>
	    <div class="row" style="width: 100%; margin-top: 20px; margin-bottom: 20px">
	    	<div class="col-md-12 opaque-section">
		    	<div class="row box-title-section">
					<h1 style="text-align: center">Group API Settings</h1>
				</div>
				<div class="row">
					
				</div>
	    	</div>	
	    </div>
	    <!-- External Application Settings -->
	   	<div class="row" style="width: 100%; margin-top: 20px; margin-bottom: 20px">
	    	<div class="col-md-12">
	    		<div class="opaque-section" >
			    	<div class="row box-title-section">
						<h1 style="text-align: center">External Service Integrations</h1>
					</div>
					<div class="row">
						<!-- Voice Communications Settings -->
						<div class="col-md-4 col-sm-12" style="padding-left: 0px; padding-right: 5px">
							<div class="opaque-section" style="background-image: none; margin-top: 10px">
								<div class="row box-title-section">
									<h3 style="text-align: center">Voice Communications</h3>	
								</div>
								<div class="row">
									<?php
									if($settings->getVoiceIntegration()) {
										?>
										<h4 style="text-align: center">Status: <span style="color: #01b43a">Enabled</span></h4>
										<form method="post" action="/settings/group/" style="text-align: center">
											<formfield>
												<label>Voice Application:</label>
												<select name="voice_application" class="form-control" style="width: 80%; margin-left: auto; margin-right: auto">
												<?php
												if($settings->getVoiceCommunications != NULL) {
													?>
													<option style="background-color: rgb(23,23,23)" value="TS3" <?php if($settings->getVoiceCommunications() == 'TS3') { echo 'selected'; } ?>>Teamspeak 3</option>
													<option style="background-color: rgb(23,23,23)" value="Mumble" <?php if($settings->getVoiceCommunications() == 'Mumble') { echo 'selected'; } ?>>Mumble</option>
													<?php
												} else {
													?>
													<option style="background-color: rgb(23,23,23)" disabled>Select A Voice Application</option>
													<option style="background-color: rgb(23,23,23)" value="TS3">Teamspeak 3</option>
													<option style="background-color: rgb(23,23,23)" value="Mumble">Mumble</option>
													<?php
												}
												?>
												</select>
											</formfield>
											<formfield>
												<label>Voice Server Address</label>
												<input style="width: 80%; margin-left: auto; margin-right: auto" type="text" class="form-control" name="voice_address" value="<?php echo $settings->getVoiceAddress();?>">
											</formfield>
											<formfield>
												<label>Voice Server Port</label>
												<input style="width: 80%; margin-left: auto; margin-right: auto" type="text" class="form-control" name="voice_port" value="<?php echo $settings->getVoicePort();?>">
											</formfield>
											<formfield>
												<input style="margin-top: 10px; margin-bottom: 10px" class="btn btn-primary" type="submit" value="Update Voice Settings">
											</formfield>
										</form>
										<form method="post" action="/settings/group/" style="text-align: center">
											<input type="hidden" name="disable" value="voice">
											<input class="btn btn-danger" type="submit" value="Disable Voice Integration">
										</form>
										<?php
									} else {
										?>
										<h4 style="text-align: center">Status: <span style="color: red">Disabled</span></h4>
										<form method="post" action="/settings/group/" style="text-align: center">
											<input type="hidden" name="enable" value="voice">
											<input class="btn btn-primary" type="submit" value="Enable Voice Integration">
										</form>
										<?php
									}
									?>
								</div>
							</div>
						</div>
						<!-- Slack Chat Integration -->
						<div class="col-md-4 col-sm-12" style="padding-left: 5px; padding-right: 5px">
							<div class="opaque-section" style="background-image: none; margin-top: 10px">
								<div class="row box-title-section">
									<h3 style="text-align: center">Slack Messaging Integration</h3>	
								</div>
								<div class="row">
									<?php
									if($settings->getSlackIntegration()) {
										?>
										<h4 style="text-align: center">Status: <span style="color: #01b43a">Enabled</span></h4>
										<form method="post" action="/settings/group/" style="text-align: center">
											<input type="hidden" name="update" value="slack">
											<formfield>
												<label>Slack Address</label>
												<input style="width: 80%; margin-left: auto; margin-right: auto" type="text" class="form-control" name="slack_address" value="<?php echo $settings->getSlackAddress();?>">
											</formfield>
											<formfield>
												<label>Slack Auth Token</label>
												<input style="width: 80%; margin-left: auto; margin-right: auto" type="text" class="form-control" name="slack_auth_token" value="<?php echo $settings->getSlackAuthToken();?>">
											</formfield>
											<formfield>
												<label>Slack Primary Channel</label>
												<input style="width: 80%; margin-left: auto; margin-right: auto;" type="text" class="form-control" name="slack_main_channel" value="<?php echo $settings->getSlackMainChannel(); ?>">
											</formfield>
											<formfield>
												<label>Slack API Notifications</label>
												<select name="slack_api_notifications" class="form-control" style="width: 80%; margin-right: auto; margin-left: auto">
												<?php
													if($settings->getSlackAPINotifications()) {
														?>
														<option style="background-color: rgb(23,23,23)" value="1" selected>Enabled</option>
														<option style="background-color: rgb(23,23,23)" value="0">Disabled</option>
														<?php
													} else {
														?>
														<option style="background-color: rgb(23,23,23)" value="1">Enabled</option>
														<option style="background-color: rgb(23,23,23)" value="0" selected>Disabled</option>
														<?php
													}
												?>
												</select>
											</formfield>
											<formfield>
												<label>Slack API Notification Channel</label>
												<input style="width: 80%; margin-left: auto; margin-right: auto;" type="text" class="form-control" name="slack_api_channel" value="<?php echo $settings->getSlackAPIChannel(); ?>">
											</formfield>
											<formfield>
												<label>Slack Ops Notifications</label>
												<select name="slack_ops_notifications" class="form-control" style="width: 80%; margin-right: auto; margin-left: auto">
												<?php
													if($settings->getSlackOperationsNotifications()) {
														?>
														<option style="background-color: rgb(23,23,23)" value="1" selected>Enabled</option>
														<option style="background-color: rgb(23,23,23)" value="0">Disabled</option>
														<?php
													} else {
														?>
														<option style="background-color: rgb(23,23,23)" value="1">Enabled</option>
														<option style="background-color: rgb(23,23,23)" value="0" selected>Disabled</option>
														<?php
													}
												?>
												</select>
											</formfield>
											<formfield>
												<label>Slack Ops Notification Channel</label>
												<input style="width: 80%; margin-left: auto; margin-right: auto;" type="text" class="form-control" name="slack_ops_channel" value="<?php echo $settings->getSlackOpsChannel(); ?>">
											</formfield>
											<formfield>
												<input style="margin-top: 10px; margin-bottom: 10px" class="btn btn-primary" type="submit" value="Update Slack Settings">
											</formfield>
										</form>
										<form method="post" action="/settings/group/" style="text-align: center">
											<input type="hidden" name="disable" value="voice">
											<input class="btn btn-danger" type="submit" value="Disable Voice Integration">
										</form>
										<p>Please note that Notification channel names must be in the form of the unique hex identifier for the channel. Please see <a href="https://api.slack.com/methods/channels.list" target="blank">this API documentation</a> for how to find the correct channel ID.</p>
										<?php
									} else {
										?>
										<h4 style="text-align: center">Status: <span style="color: red">Disabled</span></h4>
										<form method="post" action="/settings/group/" style="text-align: center">
											<input type="hidden" name="enable" value="slack">
											<input class="btn btn-primary" type="submit" value="Enable Slack Integration">
										</form>
										<?php
									}
									?>
								</div>
							</div>
						</div>
						<!-- Forum Integration -->
						<div class="col-md-4 col-sm-12" style="padding-left: 5px; padding-right: 0px">
							<div class="opaque-section" style="background-image: none; margin-top: 10px">
								<div class="row box-title-section">
									<h3 style="text-align: center">Forum Integration</h3>	
								</div>
								<div class="row">
									<?php
									if($settings->getForumIntegration()) {
										?>

										<?php
									} else {
										?>
										<h4 style="text-align: center">Status: <span style="color: red">Disabled</span></h4>
										<form method="post" action="/settings/group/" style="text-align: center">
											<input type="hidden" name="enable" value="forum">
											<input class="btn btn-primary" type="submit" value="Enable Forum Integration">
										</form>
										<?php
									}
									?>
								</div>
							</div>
						</div>
					</div>
				</div>
	    	</div>	
	    </div>
	    <?php
	}
	?>

</div>
<?php
require_once('includes/footer.php');