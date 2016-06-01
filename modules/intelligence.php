<?php
require_once('includes/header.php');

if($request['action'] == 'supercaps') {
	$page_subtitle = 'Hostile and Neutral Supercapital Intelligence and Tracker';

	// Various page forms and actions
	if(isset($_POST['action'])) {
		// Updating our current supercapital intelligence
		if($_POST['action'] == 'update') {
			// Using the zKillboard import method
			if($_POST['method'] == "zkillboard" AND $user->getUID() == "1") {
				$zkill = new zKillboard($db);

				$supercapital_array = array();
				$supercapital_array = ["22852",
									   "23913",
									   "23917",
									   "23919",
									   "671",
									   "3764",
									   "11567",
									   "23773",
									   "3514"];
				$i = 0;

				// Getting our newest killID so we can fetch from there
				$stmt = $db->prepare('SELECT kill_id FROM intel_supercap_tracker ORDER BY kill_id DESC LIMIT 1');
				$stmt->execute(array());
				$most_recent_fetched_kill = $stmt->fetch(PDO::FETCH_ASSOC);

				if($most_recent_fetched_kill['kill_id'] == NULL) {
					$starting_kill_id = 1;
				} else {
					$starting_kill_id = $most_recent_fetched_kill['kill_id'];
				}


				foreach($supercapital_array as $super_id) {
					$lookup = array();
					$lookup = [ "shipTypeID" => $super_id ];
					$supercap_lookup = $zkill->fetchKillmails($lookup, "kills", $starting_kill_id);

					foreach($supercap_lookup as $intelligence) {
						foreach($intelligence['attackers'] as $attacker) {
							if(in_array($attacker['shipTypeID'], $supercapital_array)) {

								if($attacker['allianceName'] == '' OR $attacker['allianceName'] == NULL) {
									$allianceName = 'No Alliance';
								} else {
									$allianceName = $attacker['allianceName'];
								}

								$stmt = $db->prepare('INSERT INTO  intel_supercap_tracker (character_id,character_name,solar_system,type_id,corporation,alliance,intelligence_type,kill_id,last_updated) VALUES (?,?,?,?,?,?,?,?,?)'.
									' ON DUPLICATE KEY UPDATE solar_system=VALUES(solar_system),type_id=VALUES(type_id),corporation=VALUES(corporation),alliance=VALUES(alliance),'.
									'intelligence_type=VALUES(intelligence_type),kill_id=VALUES(kill_id),last_updated=VALUES(last_updated)');
								$stmt->execute(array($attacker['characterID'],
										     $attacker['characterName'],
										     $eve->getSystemName($intelligence['solarSystemID']), 
										     $attacker['shipTypeID'],
										     $attacker['corporationName'], 
										     $allianceName, 
										     'Auto-Harvested', 
										     $intelligence['killID'],
										     strtotime($intelligence['killTime'])));
								$i++;
							}
						}
					}
				}

				setAlert('success', 'Supercapital Updates Complete', 'All known supercapital assets have been updated. '.$i.' total instances of supercapital usage have been identified and recorded.');
			} elseif($_POST['method'] == 'manual') {

			}
		} elseif($_POST['action'] == 'delete' AND $user->getUID() == "1") {
			$stmt = $db->prepare('DELETE FROM intel_supercap_tracker WHERE character_id = ?');
			$stmt->execute(array($_POST['character_id']));
			setAlert('success', 'Intelligence Information Deleted', 'The requested supercapital tracking asset has been deleted from our records.');
		} elseif($_POST['action'] == 'search') {
			switch($_POST['search_type']):
				case 'all':
					$stmt_search = $db->prepare('SELECT character_id,character_name,solar_system,type_id,corporation,alliance,intelligence_type,last_updated,kill_id FROM intel_supercap_tracker WHERE last_updated >= ? AND alliance != "Circle-Of-Two" AND alliance != "Goonswarm Federation" AND alliance != "RAZOR Alliance" AND alliance != "Fidelas Constans" AND alliance != "Get Off My Lawn" ORDER BY last_updated DESC');
					break;
				case 'character':
					$stmt_search = $db->prepare('SELECT character_id,character_name,solar_system,type_id,corporation,alliance,intelligence_type,last_updated,kill_id FROM intel_supercap_tracker WHERE character_name = ? AND last_updated >= ? AND alliance != "Circle-Of-Two" AND alliance != "Goonswarm Federation" AND alliance != "RAZOR Alliance" AND alliance != "Fidelas Constans" AND alliance != "Get Off My Lawn" ORDER BY last_updated DESC');
					break;
				case 'corporation':
					$stmt_search = $db->prepare('SELECT character_id,character_name,solar_system,type_id,corporation,alliance,intelligence_type,last_updated,kill_id FROM intel_supercap_tracker WHERE corporation = ? AND last_updated >= ? AND alliance != "Circle-Of-Two" AND alliance != "Goonswarm Federation" AND alliance != "RAZOR Alliance" AND alliance != "Fidelas Constans" AND alliance != "Get Off My Lawn" ORDER BY last_updated DESC');
					break;
				case 'alliance':
					$stmt_search = $db->prepare('SELECT character_id,character_name,solar_system,type_id,corporation,alliance,intelligence_type,last_updated,kill_id FROM intel_supercap_tracker WHERE alliance = ? AND last_updated >= ? AND alliance != "Circle-Of-Two" AND alliance != "Goonswarm Federation" AND alliance != "RAZOR Alliance" AND alliance != "Fidelas Constans" AND alliance != "Get Off My Lawn" ORDER BY last_updated DESC');
					break;
				case 'location':
					$stmt_search = $db->prepare('SELECT character_id,character_name,solar_system,type_id,corporation,alliance,intelligence_type,last_updated,kill_id FROM intel_supercap_tracker WHERE solar_system = ? AND last_updated >= ? AND alliance != "Circle-Of-Two" AND alliance != "Goonswarm Federation" AND alliance != "RAZOR Alliance" AND alliance != "Fidelas Constans" AND alliance != "Get Off My Lawn" ORDER BY last_updated DESC');
					break;
				case 'hull':
					$stmt_search = $db->prepare('SELECT character_id,character_name,solar_system,type_id,corporation,alliance,intelligence_type,last_updated,kill_id FROM intel_supercap_tracker WHERE type_id = ? last_updated >= ? AND alliance != "Circle-Of-Two" AND alliance != "Goonswarm Federation" AND alliance != "RAZOR Alliance" AND alliance != "Fidelas Constans" AND alliance != "Get Off My Lawn" ORDER BY last_updated DESC');
					break;
			endswitch;

			if($_POST['search_type'] != 'all') {
				$search_bool = TRUE;
			} else {
				$search_bool = FALSE;
			}
		}
	}
} elseif($request['action'] == 'tasks') {
	$page_subtitle = 'DOGFT Intelligence Tasking and Project Information';
}
?>
<div class="opaque-container">

    <div class="row" style="width: 100%; margin-top: 20px; margin-bottom: 20px">
		<div class="col-md-12 opaque-section" style="padding: 0px">
			<div class="row box-title-section">
				<h1 style="text-align: center">DOGFT Intelligence Service</h1>
				<h3 style="text-align: center"><?php echo $page_subtitle; ?></h3>
				<?php
				if($user->getUID() == "1" AND $request['action'] == 'supercaps') {
					?>
					<form method="post" action="/intelligence/supercaps/" style="text-align: center">
						<input type="hidden" name="method" value="zkillboard">
						<input type="hidden" name="action" value="update">
						<input type="submit" value="Refresh From zKillboard" class="btn btn-sm btn-primary" style="margin-bottom: 10px">
					</form>
					<?php
				}
				?>
			</div>
			<?php showAlerts(); 
			if($request['action'] == 'supercaps') {
				?>
				<div class="row" style="padding-left: 10px; padding-right: 10px; margin-top: 15px">
					<table class="table table-striped">
						<tr>
							<form method="post" action="/intelligence/supercaps/">
								<input type="hidden" name="action" value="search">
								<td></td>
								<td></td>
								<td><label for="search_type">What Type Of Search:</label></td>
								<td>
									<select name="search_type" class="form-control">
										<option style="background-color: rgb(21,21,21)" value="all">All Supercapitals</option>
										<option style="background-color: rgb(21,21,21)" value="character">Character</option>
										<option style="background-color: rgb(21,21,21)" value="corporation">Corporation</option>
										<option style="background-color: rgb(21,21,21)" value="alliance">Alliance</option>
										<option style="background-color: rgb(21,21,21)" value="location">Last Location</option>
										<option style="background-color: rgb(21,21,21)" value="hull">Hull Type</option>
									</select>
								</td>
								<td><input type="text" placeholder="Your Search" name="search_value" class="form-control"></td>
								<td>
									<select name="timespan" class="form-control">
										<option value="<?php echo time()-(60*60*24*7); ?>" style="background-color: rgb(21,21,21)">1 Week</option>
										<option value="<?php echo time()-(60*60*24*14); ?>" style="background-color: rgb(21,21,21)">2 Weeks</option>
										<option value="<?php echo time()-(60*60*24*21); ?>" style="background-color: rgb(21,21,21)">3 Weeks</option>
										<option value="<?php echo time()-(60*60*24*30); ?>" style="background-color: rgb(21,21,21)">1 Month</option>
										<option value="<?php echo time()-(60*60*24*60); ?>" style="background-color: rgb(21,21,21)">2 Months</option>
										<option value="<?php echo time()-(60*60*24*90); ?>" style="background-color: rgb(21,21,21)">3 Months</option>
										<option value="<?php echo time()-(60*60*24*120); ?>" style="background-color: rgb(21,21,21)">4 Months</option>
										<option value="<?php echo time()-(60*60*24*180); ?>" style="background-color: rgb(21,21,21)">6 Months</option>
										<option value="<?php echo time()-(60*60*24*365*1); ?>" style="background-color: rgb(21,21,21)">1 Year</option>
										<option value="<?php echo time()-(60*60*24*365*2); ?>" style="background-color: rgb(21,21,21)">2 Years</option>
										<option value="<?php echo time()-(60*60*24*365*3); ?>" style="background-color: rgb(21,21,21)">3 Years</option>
										<option value="<?php echo time()-(60*60*24*365*4); ?>" style="background-color: rgb(21,21,21)">4 Years</option>
										<option value="<?php echo 1; ?>" style="background-color: rgb(21,21,21)" selected>All Supercapitals</option>
									</select>
								</td>
								<td><input type="submit" value="Start Search" class="btn btn-sm btn-primary"></td>
							</form>
						</tr>
						<tr>
							<th style="text-align: center">Character Name</th>
							<th style="text-align: center">Supercapital</th>
							<th style="text-align: center">Last Known Location</th>
							<th style="text-align: center">Corporation</th>
							<th style="text-align: center">Alliance</th>
							<th style="text-align: center">Last Active</th>
							<?php 
							if($user->getUID() == "1") {
								?>
								<th style="text-align: center">Actions</th>
								<?php
							}
							?>
						</tr>
						<?php

						if(isset($_POST['timespan'])) {
							$cutoff_time = strtotime($_POST['timespan']);
						} else {
							$cutoff_time = time()-(60*60*24*30*4);
						}

						if(isset($search_bool) AND $search_bool) {
							$stmt_search->execute(array($_POST['search_value'], $cutoff_time));
						} else {
							$stmt_search = $db->prepare('SELECT character_id,character_name,solar_system,type_id,corporation,alliance,intelligence_type,last_updated,kill_id FROM intel_supercap_tracker WHERE last_updated >= ? AND alliance != "Circle-Of-Two" AND alliance != "Goonswarm Federation" AND alliance != "RAZOR Alliance" AND alliance != "Fidelas Constans" AND alliance != "Get Off My Lawn" ORDER BY last_updated DESC');
							$stmt_search->execute(array($cutoff_time));
						}
												
						$supercaps = $stmt_search->fetchAll(PDO::FETCH_ASSOC);

						foreach($supercaps as $supercap) {
							?>
							<tr style="text-align: center">
								<td><button type="button" class="btn btn-primary" onclick="CCPEVE.addContact(<?php echo $supercap['character_id']; ?>)"><?php echo $supercap['character_name']; ?></button></td>
								<td><?php echo $eve->getTypeName($supercap['type_id']); ?></td>
								<td><a href="https://zkillboard.com/kill/<?php echo $supercap['kill_id']; ?>/" target="blank"><?php echo $supercap['solar_system']; ?></a></td>
								<td><?php echo $supercap['corporation']; ?></td>
								<td><?php echo $supercap['alliance']; ?></td>
								<td><?php echo date('Y-m-d', $supercap['last_updated']); ?></td>
								<?php 
								if($user->getUID() == "1") {
									?>
									<th>
										<form method="post" action="/intelligence/supercaps/">
											<input type="hidden" name="character_id" value="">
											<input type="hidden" name="action" value="delete">
											<input type="submit" value="Delete" class="btn btn-sm btn-danger">
										</form>
									</th>
								<?php
								}
								?>
							</tr>
							<?php
						}
						?>
					</table>
				</div>
				<?php
			} elseif($request['action'] == 'tasks') {
				?>
				<div class="row" style="padding-left: 10px; padding-right: 10px; margin-top: 15px">
					<table class="table table-striped">
					</table>
				</div>
				<?php
			} 
			?>
		</div>	
    </div>
</div>
<?php
require_once('includes/footer.php');
