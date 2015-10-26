<?php
require_once('includes/header.php');

if(!isset($_POST['time_period'])) {
	$time_period = date('F Y');
} else {
	$time_period = $_POST['time_period'];
}

if($request['action'] == 'add' AND $user->getDirectorAccess()) {
	if($request['value'] == 'manual') {

		$stmt = $db->prepare('INSERT INTO user_participation (uid,participation_metric,time_period) VALUES (?,?,?) ON DUPLICATE KEY UPDATE participation_metric=VALUES(participation_metric)');
		$stmt->execute(array($request['value_2'], $_POST['participation_update'], $time_period));
	}
}

$stmt = $db->prepare('SELECT * FROM user_accounts WHERE gid = ? AND access != "No Access" AND access != "New Applicant" ORDER BY username ASC');
$stmt->execute(array($user->getGroup()));
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<div class="opaque-container">

    <div class="row" style="width: 100%; margin-top: 20px; margin-bottom: 20px">
		<div class="col-md-12 opaque-section" style="padding: 0px">
			<div class="row box-title-section">
				<h1 style="text-align: center">Participation Metrics</h1>
			</div>
			<?php showAlerts(); ?>
			<div class="row" style="padding-left: 10px; padding-right: 10px; margin-bottom: 35px">
				<div class="col-md-3 col-sm-6" style="text-align: center; margin-bottom; 15px">
					<h3 class="eve-text">Total Participation Numbers</h3>
						<?php
						$stmt = $db->prepare('SELECT sum(user_participation.participation_metric) FROM user_participation JOIN user_accounts ON user_accounts.uid = user_participation.uid WHERE user_accounts.gid = ? AND user_participation.time_period = ?');
						$stmt->execute(array($user->getGroup(), $time_period));
						$participation_sum = $stmt->fetch();

						if($participation_sum['0'] < 50) {
							$sum_color = 'danger';
						} elseif($participation_sum[0] > 150) {
							$sum_color = 'success';
						} else {
							$sum_color = 'warning';
						}
						?>
						<span class="label label-<?php echo $sum_color; ?>" style="font-size: 200%"><?php echo $participation_sum[0]; ?> Links</span>
				</div>
				<div class="col-md-3 col-sm-6" style="text-align: center; margin-bottom; 15px">
					<h3 class="eve-text">Human Participation</h3>
						<?php
						$stmt = $db->prepare('SELECT * FROM user_participation JOIN user_accounts ON user_accounts.uid = user_participation.uid WHERE user_accounts.gid = ? AND user_participation.time_period = ?');
						$stmt->execute(array($user->getGroup(), $time_period));
						$participation_humans = $stmt->rowCount();

						$stmt_humans = $db->prepare('SELECT * FROM user_accounts WHERE gid = ?');
						$stmt_humans->execute(array($user->getGroup()));
						$total_humans = $stmt_humans->rowCount();

						$participation_difference = $participation_humans / $total_humans;
						$participation_percentage = round((float)$participation_difference * 100 ) . '%';

						if($participation_difference < "0.4") {
							$sum_color = 'danger';
						} elseif($participation_difference > "0.7") {
							$sum_color = 'success';
						} else {
							$sum_color = 'warning';
						}
						?>
						<span class="label label-<?php echo $sum_color; ?>" style="font-size: 200%"><?php echo $participation_percentage; ?> Of Humans</span>
				</div>
				<div class="col-md-3 col-sm-6" style="text-align: center; margin-bottom; 15px">
					<h3 class="eve-text">5 Link Minimum Requirement</h3>
						<?php
						$stmt = $db->prepare('SELECT * FROM user_participation JOIN user_accounts ON user_accounts.uid = user_participation.uid WHERE user_accounts.gid = ? AND user_participation.time_period = ? AND user_participation.participation_metric >= "5"');
						$stmt->execute(array($user->getGroup(), $time_period));
						$participation_humans = $stmt->rowCount();

						$participation_difference = $participation_humans / $total_humans;
						$participation_percentage = round((float)$participation_difference * 100 ) . '%';

						if($participation_difference < "0.4") {
							$sum_color = 'danger';
						} elseif($participation_difference > "0.7") {
							$sum_color = 'success';
						} else {
							$sum_color = 'warning';
						}
						?>
						<span class="label label-<?php echo $sum_color; ?>" style="font-size: 200%"><?php echo $participation_percentage; ?> Meet Req</span>
				</div>
				<div class="col-md-3 col-sm-6" style="text-align: center; margin-bottom; 15px">
					<h3 class="eve-text">Total Humans</h3>
						<?php

						if($total_humans < 10) {
							$sum_color = 'danger';
						} elseif($participation_difference > 20) {
							$sum_color = 'success';
						} else {
							$sum_color = 'warning';
						}
						?>
						<span class="label label-<?php echo $sum_color; ?>" style="font-size: 200%"><?php echo $total_humans; ?> Total Humans</span>
				</div>
			</div>
			<div class="row" style="padding-left: 10px; padding-right: 10px">
				<table class="table table-striped">
					<tr>
						<th>User Name</th>
						<th>Account's Characters</th>
						<th>Participation for <?php echo $time_period; ?></th>
					</tr>
					<tr>
						<form method="post" action="/participation/">
							<td>Select Participation Timeframe:</td>
							<td>
								<select class="form-control">
									<option style="background-color: rgb(24,24,24)" value="August 2015">Aug 2015</option>
									<option style="background-color: rgb(24,24,24)" value="September 2015">Sept 2015</option>
									<option style="background-color: rgb(24,24,24)" value="October 2015" selected>Oct 2015</option>
									<option style="background-color: rgb(24,24,24)" value="November 2015">Nov 2015</option>
								</select>
							</td>
							<td><input type="submit" class="btn btn-success" value="Look Up Paps"></td>
						</form>
					</tr>
					<?php
					foreach($accounts as $account) {
						$stmt_participation = $db->prepare('SELECT * FROM user_participation WHERE uid = ? AND time_period = ? LIMIT 1');
						$stmt_participation->execute(array($account['uid'], $time_period));
						$participation = $stmt_participation->fetch(PDO::FETCH_ASSOC);

						$stmt = $db->prepare('SELECT * FROM characters WHERE uid = ? ORDER BY skillpoints DESC');
						$stmt->execute(array($account['uid']));
						$characters = $stmt->fetchAll(PDO::FETCH_ASSOC);

						if($stmt_participation->rowCount() != 1) {
							$participation_total = '0';
						} else {
							$participation_total = $participation['participation_metric'];
						}


						?>
						<tr>
							<td><?php echo $account['username']; ?></td>
							<?php
							if($user->getDirectorAccess()) {
								?>
								<td>
									<?php 
									$i = 1; 
									foreach($characters as $character) { 
										?>
										<img style="margin-left: 2px; margin-right: 2px" data-toggle="tooltip" data-placement="top" title="<?php echo $character['charactername']; ?> | <?php echo $character['corporation']; ?> | <?php echo $character['alliance']; ?>" src="<?php echo Character::getCharacterImage($character['charid'], 30); ?>">
										<?php 
										if($i == 5 OR $i == 10 OR $i == 15 OR $i == 20) { 
											echo "<br />"; 
										} 
										$i++;  
									} 
									?>
								</td>
								<td>
									<form method="post" action="/participation/add/manual/<?php echo $account['uid']; ?>/">
										<input type="text" class="form-control" style="width: 75%; float: left" name="participation_update" value="<?php echo $participation_total; ?>">
										<input type="hidden" name="participation_period" value="<?php echo $time_period; ?>">	
										<input type="submit" class="btn btn-primary btn-sm" style="margin-left: 5px; float: left; clear: right" value="Update">
									</form>
								</td>
								<?php
							} else {
								?>
								<td>You Cannot View This User's Characters</td>
								<td><?php if($participation_total == 0) { echo '<span class="label label-danger">No Participation</span>'; } elseif($participation_total < 5) { echo '<span class="label label-warning">'.$participation_total.' - Below Requirement</span>';} else { echo $participation_total; } ?></td>
								<?php
							}
							?>
						</tr>
						<?php
					}
					?>
				</table>
			</div>
		</div>	
    </div>

</div>
<?php
require_once('includes/footer.php');