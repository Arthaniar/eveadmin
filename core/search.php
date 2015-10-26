<?php
require_once('includes/header.php');

if($request['action'] == 'skills') {
	$title = 'Skills Search';

	if(isset($_POST['search'])) {
		$stmt = $db->prepare('SELECT * FROM user_skills WHERE gid = ? AND skillid = ? AND level >= ? ORDER BY level DESC');
		$stmt->execute(array($user->getGroup(), $_POST['search'], $_POST['constraint']));
		$lookup_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
} elseif($request['action'] == 'assets') {
	$title = 'Assets Search';

	if(isset($_POST['search'])) {
		$stmt = $db->prepare('SELECT * FROM user_assets WHERE typeID = ? ORDER BY uid ASC');
		$stmt->execute(array($eve->getTypeID($_POST['search'])));
		$lookup_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
}
?>
<div class="opaque-container">

    <div class="row" style="width: 100%; margin-top: 20px; margin-bottom: 20px">
		<div class="col-md-12 opaque-section" style="padding: 0px">
			<div class="row box-title-section">
				<h1 style="text-align: center"><?php echo $title; ?></h1>
			</div>
			<?php showAlerts(); ?>
			<div class="row" style="padding-left: 10px; padding-right: 10px; padding-top: 15px;">
				<table class="table table-striped">
				<?php
				if($request['action'] == 'skills') {
					?>
					<tr>
						<form method="post" action="/search/skills/">
							<td></td>
							<td>
								<select name="search" class="form-control">
									<?php
									$stmt = $db->prepare('SELECT * FROM rawskilltree ORDER BY typeName ASC');
									$stmt->execute(array());
									$skilltree = $stmt->fetchAll(PDO::FETCH_ASSOC);
									foreach($skilltree as $skill) {
										?>
										<option style="background-color: rgb(24,24,24)" value="<?php echo $skill['typeID']; ?>"><?php echo $skill['typeName']; ?></option>
										<?php
									}
									?>
								</select>
							</td>
							<td>
								<select name="constraint" class="form-control">
									<option value="1" style="background-color: rgb(24,24,24)">1</option>
									<option value="2" style="background-color: rgb(24,24,24)">2</option>
									<option value="3" style="background-color: rgb(24,24,24)">3</option>
									<option value="4" style="background-color: rgb(24,24,24)">4</option>
									<option value="5" style="background-color: rgb(24,24,24)">5</option>
								</select>						
							</td>
							<td style="text-align: center"><input type="submit" class="btn btn-primary" value="Search Skills"></td>
						</form>
					</tr>
					<tr style="background-color: rgb(48,48,48)">
						<td style="text-align: center">#</td>
						<td style="text-align: center">Character</td>
						<td style="text-align: center">Owner</td>
						<td style="text-align: center">Level</td>
					</tr>
					<?php
					if(isset($lookup_results)) {
						$i = 1;
						foreach($lookup_results as $result) {
							$stmt_acct = $db->prepare('SELECT * FROM characters WHERE charid = ? LIMIT 1');
							$stmt_acct->execute(array($result['charid']));
							$character = $stmt_acct->fetch(PDO::FETCH_ASSOC);

							$stmt_username = $db->prepare('SELECT * FROM user_accounts WHERE uid = ? LIMIT 1');
							$stmt_username->execute(array($character['uid']));
							$account = $stmt_username->fetch(PDO::FETCH_ASSOC);
							?>
							<tr style="text-align: center">
								<td style="width: 10%"><?php echo $i; ?></td>
								<td><?php echo Character::fetchCharacterName($result['charid']); ?></td>
								<td><?php echo $account['username']; ?></td>
								<td><?php echo $result['level']; ?></td>
							</tr>
							<?php
							$i++;
						}
					}
				} elseif($request['action'] == 'assets') {
					?>
					<tr style="background-color: rgb(48,48,48)">
						<td style="text-align: center">Character</td>
						<td style="text-align: center">Quantity</td>
						<td style="text-align: center">Location</td>
						<td style="text-align: center">Owner</td>
					</tr>
					<tr>
						<form method="post" action="/search/assets/">
							<td></td>
							<td></td>
							<td><input type="text" class="form-control" name="search" placeholder="Item Name"></td>
							<td style="text-align: center"><input type="submit" class="btn btn-primary" value="Search Assets"></td>
						</form>
					</tr>
					<?php
					if(isset($lookup_results)) {
						// Pre-preparing the statement for looping.
						$stmt = $db->prepare('SELECT * FROM characters WHERE charid = ? LIMIT 1');
						$stmt_account = $db->prepare('SELECT * FROM user_accounts WHERE uid = ? LIMIT 1');

						foreach($lookup_results as $asset) {
							$stmt->execute(array($asset['characterID']));
							$character = $stmt->fetch(PDO::FETCH_ASSOC);

							$stmt_account->execute(array($character['uid']));
							$account = $stmt_account->fetch(PDO::FETCH_ASSOC);

							if($account['gid'] == $user->getGroup()) {
								?>
									<tr>
										<td><?php echo $character['charactername']; ?></td>
										<td><?php echo $asset['quantity']; ?></td>
										<td><?php echo $eve->getStationName($asset['locationID']); ?></td>
										<td><?php echo $account['username']; ?></td>
									</tr>
								<?php
							}
						}
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