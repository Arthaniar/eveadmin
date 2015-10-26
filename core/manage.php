<?php
require_once('includes/header.php');
if($request['action'] == 'users') {
	$title = "Account Management";

	if(isset($_POST['action'])) {
		if($_POST['action'] == 'delete') {
			// Deleting the User's account
			User::deleteUser($_POST['uid']);
		} elseif($_POST['action'] == "change_permission") {
			// Granting a new permission
			switch($_POST['permission_level']):
				case 'No Access':
				case 'New Applicant':
				case 'Member':
					$required_permission = $user->getDirectorAccess();
					break;
				case 'Director':
				case 'CEO':
					$required_permission = $user->getCEOAccess();
					break;
				case 'Admin':
					$required_permission = $user->getAdminAccess();
					break;
			endswitch;

			if($required_permission) {
				if($user->getAdminAccess()) {
					// Admins can change permissions for anyone in the auth
					$stmt = $db->prepare('UPDATE user_accounts SET access = ? WHERE uid = ?');
					$stmt->execute(array($_POST['permission_level'], $_POST['uid']));
				} else {
					// Non-Admins can only change permissions for people in their group, and they cannot change the permissions of Admins
					$stmt = $db->prepare('UPDATE user_accounts SET access = ? WHERE uid = ? AND gid = ? AND access != "Admin"');
					$stmt->execute(array($_POST['permission_level'], $_POST['uid'], $user->getGroup()));
				}
			}
		} elseif($_POST['action'] == "change_group") {
			// Changing the group that an account belongs to
			if($user->getDirectorAccess()) {
				// Only Directors and higher can change group accesses.
				$stmt = $db->prepare('UPDATE user_accounts SET gid = ? WHERE uid = ?');
				$stmt->execute(array($_POST['new_group_id'], $_POST['uid']));
			}
		} elseif($_POST['action'] == "lockdown") {

			if($user->getAdminAccess()) {
				// Admin accounts can only be locked by other Admins
				$stmt = $db->prepare('UPDATE user_accounts SET lockdown = ? WHERE uid = ?');
				$stmt->execute(array($_POST['lockdown_status'], $_POST['uid']));
			} else {
				$stmt = $db->prepare('UPDATE user_accounts SET lockdown = ? WHERE uid = ? AND access != "Admin"');
				$stmt->execute(array($_POST['lockdown_status'], $_POST['uid']));
			}
		}
	}
} elseif($request['action'] == 'groups') {
	$title = 'Group Management';
}

?>
<div class="opaque-container">

    <div class="row" style="width: 100%; margin-top: 20px; margin-bottom: 20px">
		<div class="col-md-12 opaque-section" style="padding: 0px">
			<div class="row box-title-section">
				<h1 style="text-align: center"><?php echo $title; ?></h1>
			</div>
			<?php showAlerts(); ?>
			<div class="row" style="padding-left: 10px; padding-right: 10px">
				<?php
				if($request['action'] == 'users') {
					if($user->getAdminAccess()) {
						// Admins can see all users registered in the Auth
						$stmt = $db->prepare('SELECT * FROM user_accounts ORDER BY access,uid ASC');
						$stmt->execute(array());
						$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
					} else {
						// Directors and CEOs can only see their own members
						$stmt = $db->prepare('SELECT * FROM user_accounts WHERE gid = ? OR gid = 0 ORDER BY access,uid ASC');
						$stmt->execute(array($user->getGroup()));
						$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
					}

					// Preparing the Group name lookup in advance
					$stmt_groups = $db->prepare('SELECT * FROM group_groups WHERE gid = ? LIMIT 1');
					?>
					<table class="table table-striped" style="margin-top: 10px">
						<tr style="text-align: center">
							<th style="text-align: center">Account ID</th>
							<th style="text-align: center">Username</th>
							<th style="text-align: center">Main Character</th>
							<th style="text-align: center">Group Membership</th>
							<th style="text-align: center">Access Level</th>
							<th style="text-align: center">Last Login</th>
							<th style="text-align: center">Actions</th>
						</tr>
						<?php
						foreach($accounts as $account) {
							$stmt_groups->execute(array($account['gid']));
							$group = $stmt_groups->fetch(PDO::FETCH_ASSOC);

							$no_group_selected = '';
							$group_selected = '';

							if(empty($group)) {
								$group_membership = 'No Group';
								$no_group_selected = 'selected';
							} else {
								$group_membership = $group['groupticker'];
								$group_selected = $group['gid'];
							}

							$stmt = $db->prepare('SELECT * FROM group_groups ORDER BY groupticker ASC');
							$stmt->execute(array());
							$grouplist = $stmt->fetchAll(PDO::FETCH_ASSOC);
							?>
							<tr style="text-align: center">
								<td><?php echo $account['uid']; ?></td>
								<td><?php echo $account['username']; ?></td>
								<td><?php echo $account['defaultname']; ?></td>
								<td>
									<form method="post" action="/manage/users/">
										<formfield>
											<input type="hidden" name="action" value="change_group">
											<input type="hidden" name="uid" value="<?php echo $account['uid']; ?>">
										</formfield>
										<formfield>
											<select class="form-control" name="new_group_id" onchange="this.form.submit()">
												<?php
												foreach($grouplist as $available_group) {
													?><option <?php if($available_group['gid'] == $group_membership) { echo 'selected'; } ?> style="background-color: rgb(23,23,23)" value="<?php echo $available_group['gid']; ?>"><?php echo $available_group['groupticker']; ?></option><?php
												}
												?>
												<option style="background-color: rgb(23,23,23)" value="0" <?php echo $no_group_selected; ?>>No Group</option>
												
											</select>
										</formfield>
									</form>
								</td>
								<td>
									<form method="post" action="/manage/users/" >
										<formfield>
											<input type="hidden" name="action" value="change_permission">
											<input type="hidden" name="uid" value="<?php echo $account['uid']; ?>">
										</formfield>
										<formfield>
											<select class="form-control" name="permission_level" onchange="this.form.submit()">
												<option style="background-color: rgb(23,23,23)" value="No Access"<?php if ($account['access'] == 'No Access') { echo ' selected '; } ?>>No Access</option>
												<option style="background-color: rgb(23,23,23)" value="New Applicant"<?php if ($account['access'] == 'New Applicant') { echo ' selected '; } ?>>New Applicant</option>
												<option style="background-color: rgb(23,23,23)" value="Member"<?php if ($account['access'] == 'Member') { echo ' selected '; } ?>>Member</option>
												<option style="background-color: rgb(23,23,23)" value="Director"<?php if ($account['access'] == 'Director') { echo ' selected '; } ?>>Director</option>
												<option style="background-color: rgb(23,23,23)" value="CEO"<?php if ($account['access'] == 'CEO') { echo ' selected '; } ?>>CEO</option>
												<option style="background-color: rgb(23,23,23)" value="Admin"<?php if ($account['access'] == 'Admin') { echo ' selected '; } ?>>Administrator</option>
											</select>
										</formfield>
									</form>
								</td>
								<td><?php if($account['last_login'] == NULL) { echo '<span style="color: red">Never</span>'; } else { echo date('Y-m-d H:i', $account['last_login']); } ?></td>
								<td>
									<form style="float: left; text-align: right" method="post" action="/manage/users/">
										<formfield>
											<input type="hidden" name="action" value="lockdown">
											<input type="hidden" name="uid" value="<?php echo $account['uid']; ?>">
										</formfield>
										<?php
										if($account['lockdown'] == 0) {
											?>
											<formfield>
												<input type="hidden" name="lockdown_status" value="1">
											</formfield>
											<formfield>
												<input type="submit" class="btn btn-primary" value="Lock Account">
											</formfield>
											<?php
										} else {
											?>
											<formfield>
												<input type="hidden" name="lockdown_status" value="0">
											</formfield>
											<formfield>
												<input type="submit" class="btn btn-warning" value="Unlock Account">
											</formfield>
											<?php
										}
										?>
									</form>
									<form style="float: left; clear:right; margin-left: 10px; text-align: right" method="post" action="/manage/users/">
										<formfield>
											<input type="hidden" name="action" value="delete">
											<input type="hidden" name="uid" value="<?php echo $account['uid']; ?>">
										</formfield>
										<formfield>
											<input type="submit" class="btn btn-danger" value="Delete Account">
										</formfield>
									</form>
								</td>
							</tr>
							<?php
						}
						?>
					</table>
					<?php
				} elseif($request['action'] == 'groups') {
					$stmt = $db->prepare('SELECT * FROM group_groups ORDER BY groupname ASC');
					$stmt->execute(array());
					$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
					?>
					<table class="table table-striped" style="margin-top: 10px">
						<tr style="text-align: center">
							<th style="text-align: center">Group ID</th>
							<th style="text-align: center">Group Name</th>
							<th style="text-align: center">Ticker</th>
							<th style="text-align: center">Owner</th>
							<th style="text-align: center">Members</th>
							<th style="text-align: center">Actions</th>
						</tr>
						<?php
						foreach($groups as $group) {
							$stmt = $db->prepare('SELECT * FROM user_accounts WHERE uid = ? LIMIT 1');
							$stmt->execute(array($group['owner']));
							$owner = $stmt->fetch(PDO::FETCH_ASSOC);

							$stmt = $db->prepare('SELECT * FROM user_accounts WHERE gid = ?');
							$stmt->execute(array($group['gid']));
							$member_count = $stmt->rowCount();

							$owner_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
							?>
							<tr style="text-align: center">
								<td><?php echo $group['gid']; ?></td>
								<td><?php echo $group['groupname']; ?></td>
								<td><?php echo $group['groupticker']; ?></td>
								<td>
									<form method="post" action="/manage/groups/">
										<formfield>
											<input type="hidden" name="action" value="change_owner">
											<input type="hidden" name="gid" value="<?php echo $group['gid']; ?>">
										</formfield>
										<formfield>
											<select class="form-control" name="new_owner" onchange="this.form.submit()">
												<?php
												foreach($owner_list as $new_owner) {
													?>
													<option style="background-color: rgb(24,24,24)" value="<?php echo $new_owner['uid']; ?>" <?php if($new_owner['uid'] == $group['owner']) { echo 'selected'; } ?>><?php echo $new_owner['username']; ?></option>
													<?php
												}
												?>
											</select>
										</formfield>
									</form>
								</td>
								<td><?php echo $member_count; ?></td>
								<td>
									<form method="post" action="/manage/groups/">
										<formfield>
										</formfield>
										<formfield>
											<input type="hidden" name="gid" value="<?php echo $group['gid']; ?>">
										</formfield>
										<formfield>
											<button type="submit" class="btn btn-danger"><span class="glyphicon glyphicon-remove"></span></button>
										</formfield>
									</form>
								</td>
							</tr>
							<?php
						}
						?>
					</table>
					<?php
				}
				?>

			</div>
		</div>	
    </div>

</div>

<?php
require_once('includes/footer.php');