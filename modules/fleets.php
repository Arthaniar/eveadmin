<?php
require_once('includes/header.php');

if($request['value'] != NULL) {
	$last_kill_id = $request['value'];
} else { 
	$last_kill_id = '49942541';
}

if($request['value_2'] != NULL) {
	$last_loss_id = $request['value_2'];
} else { 
	$last_loss_id = '49942541';
}

if($request['action'] == 'fetch') {
	$zKill = new zKillboard($db);

	$target_alliance = 'V.e.G.A.';
	$target_alliance_id = '99002107';

	$our_alliance = 'Get Off My Lawn';
	$our_alliance_id = '150097440';
	$participant_threshhold = '15';

	$lookup = [ 'allianceID' => $target_alliance_id ];

	$zKill_lookup = $zKill->fetchKillmails($lookup, 'losses', $last_kill_id);

	foreach($zKill_lookup as $killmail) {
		$our_alliance_participants = 0;

			foreach($killmail['attackers'] as $attacker) {
				if($attacker['allianceName'] == $our_alliance) {
					$our_alliance_participants++;

					switch($eve->getTypeName($attacker['shipTypeID'])):
						case 'Proteus':
							$opposing_fleet_comp = 'Legion';
							break;
						case 'Legion':
							$opposing_fleet_comp = 'Legion';
							break;
						case 'Hurricane':
							$opposing_fleet_comp = 'Hurricane';
							break;
						case 'Zealot':
							$opposing_fleet_comp = 'Zealot';
							break;
						default:
							break;
					endswitch;
				}
			}

			if(!isset($opposing_fleet_comp)) {
				$opposing_fleet_comp = 'Unknown';
			}


				$stmt = $db->prepare('INSERT INTO mod_fleets (kill_id,kill_date,kill_system,kill_region,kill_char,kill_corp,kill_alliance,kill_ship,kill_value,kill_participants,kill_relevant,kill_opposing_comp) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'.
								' ON DUPLICATE KEY UPDATE kill_opposing_comp=VALUES(kill_opposing_comp)');
				$stmt->execute(array($killmail['killID'],
									 strtotime($killmail['killTime']),
									 $eve->getSystemName($killmail['solarSystemID']),
									 $eve->getSystemRegion($killmail['solarSystemID'], 'id'),
									 $killmail['victim']['characterName'],
									 $killmail['victim']['corporationName'],
									 $killmail['victim']['allianceName'],
									 $eve->getTypeName($killmail['victim']['shipTypeID']),
									 $killmail['zkb']['totalValue'],
									 count($killmail['attackers']),
									 $our_alliance_participants,
									 $opposing_fleet_comp));
		

		$last_kill_id = $killmail['killID'];
	}

	$lookup = [ 'allianceID' => $our_alliance_id ];

	$zKill_lookup = $zKill->fetchKillmails($lookup, 'losses', $last_loss_id);

	foreach($zKill_lookup as $killmail) {
		$target_alliance_participants = 0;

			foreach($killmail['attackers'] as $attacker) {
				if($attacker['allianceName'] == $target_alliance) {
					$target_alliance_participants++;

					switch($eve->getTypeName($attacker['shipTypeID'])):
						case 'Proteus':
							$opposing_fleet_comp = 'Protues';
							break;
						case 'Legion':
							$opposing_fleet_comp = 'Proteus';
							break;
						case 'Ishtar':
							$opposing_fleet_comp = 'Ishtar';
							break;
						case ' Augoror Navy Issue':
							$opposing_fleet_comp = 'Augoror Navy Issue';
							break;
						default:
							break;
					endswitch;
				}
			}

			if(!isset($opposing_fleet_comp)) {
				$opposing_fleet_comp = 'Unknown';
			}

			if($target_alliance_participants >= $participant_threshhold) {

				$stmt = $db->prepare('INSERT INTO mod_fleets (kill_id,kill_date,kill_system,kill_region,kill_char,kill_corp,kill_alliance,kill_ship,kill_value,kill_participants,kill_relevant,kill_opposing_comp) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'.
								' ON DUPLICATE KEY UPDATE kill_opposing_comp=VALUES(kill_opposing_comp)');
				$stmt->execute(array($killmail['killID'],
									 strtotime($killmail['killTime']),
									 $eve->getSystemName($killmail['solarSystemID']),
									 $eve->getSystemRegion($killmail['solarSystemID'], 'id'),
									 $killmail['victim']['characterName'],
									 $killmail['victim']['corporationName'],
									 $killmail['victim']['allianceName'],
									 $eve->getTypeName($killmail['victim']['shipTypeID']),
									 $killmail['zkb']['totalValue'],
									 count($killmail['attackers']),
									 $target_alliance_participants,
									 $opposing_fleet_comp));
			}
		

		$last_loss_id = $killmail['killID'];
	}
}
?>
<div class="opaque-container">

    <div class="row" style="width: 100%; margin-top: 20px; margin-bottom: 20px">
		<div class="col-md-12 opaque-section" style="padding: 0px">
			<div class="row box-title-section" style="text-align: center">
				<h1 style="text-align: center">Fleet Reporter</h1>
				<a style="margin-bottom: 15px" class="btn btn-primary" href="/fleets/fetch/<?php echo $last_kill_id.'/'.$last_loss_id.'/'; ?>">Fetch Fleets</a>
			</div>
			<?php showAlerts(); ?>
			<div class="row" style="padding-left: 10px; padding-right: 10px; padding-top: 10px">
				<?php

				$total_hostile_ship_losses = 0;
				$total_hostile_isk_loss = 0;

				$total_our_ship_losses = 0;
				$total_our_isk_loss = 0;

				$battle_reports = array();

				$stmt = $db->prepare('SELECT * FROM mod_fleets WHERE kill_region = "Vale Of The Silent" OR kill_region = "Geminate" ORDER BY kill_date ASC');
				$stmt->execute(array());
				$killmails = $stmt->fetchAll(PDO::FETCH_ASSOC);

				foreach($killmails as $kill) {
					$kill_date = new DateTime();
					$kill_date->setTimestamp($kill['kill_date']);
					$kill_day = $kill_date->format('Y-m-d');
					$kill_hour = $kill_date->format('H');
					$kill_system = $kill['kill_system'];

					if(isset($battle_reports[$kill_day][($kill_hour - 1)])) {
						$kill_hour -= 1 ;
					}

					$battle_reports[$kill_day][$kill_hour][$kill_system][] = $kill;

					$battle_reports[$kill_day][$kill_hour]['killID'] = $kill['kill_id'];
					$battle_reports[$kill_day][$kill_hour]['kill_system'] = $eve->getSystemID($kill['kill_system']);
					$battle_reports[$kill_day][$kill_hour]['battle_time'] = $kill_day.' '.$kill_hour.':00';
					$battle_reports[$kill_day][$kill_hour]['zkill_related'] = str_replace('-', '',  $kill_day).$kill_hour.'00';

					if($kill['kill_alliance'] == 'Northern Coalition.') {

						if($kill['kill_ship'] == 'Proteus' OR $kill['kill_ship'] == 'Legion') {
							$battle_reports[$kill_day][$kill_hour]['hostile_fleet_comp'] = 'Proteus Fleet';
						} elseif($kill['kill_ship'] == 'Ishtar') {
							$battle_reports[$kill_day][$kill_hour]['hostile_fleet_comp'] = 'Ishtar Fleet';
						} elseif($kill['kill_ship'] == 'Augoror Navy Issue') {
							$battle_reports[$kill_day][$kill_hour]['hostile_fleet_comp'] = 'ANI Fleet';
						} 

						if($kill['kill_opposing_comp'] != 'Unknown') {
							$battle_reports[$kill_day][$kill_hour]['friendly_fleet_comp'] = $kill['kill_opposing_comp'].' Fleet';
						} 

						if(!isset($battle_reports[$kill_day][$kill_hour]['hostile_kills'])) {
							$battle_reports[$kill_day][$kill_hour]['hostile_kills'] = 1;
							$total_hostile_ship_losses++;
							$battle_reports[$kill_day][$kill_hour]['hostile_value'] = $kill['kill_value'];
							$total_hostile_isk_loss += $kill['kill_value'];
						} else {
							$battle_reports[$kill_day][$kill_hour]['hostile_kills']++;
							$total_hostile_ship_losses++;
							$battle_reports[$kill_day][$kill_hour]['hostile_value'] += $kill['kill_value'];
							$total_hostile_isk_loss += $kill['kill_value'];
						}

					} elseif($kill['kill_alliance'] == 'Get Off My Lawn') {
						if($kill['kill_ship'] == 'Proteus' OR $kill['kill_ship'] == 'Legion') {
							$battle_reports[$kill_day][$kill_hour]['friendly_fleet_comp'] = 'Legion Fleet';
						} elseif($kill['kill_ship'] == 'Zealot') {
							$battle_reports[$kill_day][$kill_hour]['friendly_fleet_comp'] = 'Zealot Fleet';
						} elseif($kill['kill_ship'] == 'Hurricane') {
							$battle_reports[$kill_day][$kill_hour]['friendly_fleet_comp'] = 'Hurricane Fleet';
						} 

						if($kill['kill_opposing_comp'] != 'Unknown') {
							$battle_reports[$kill_day][$kill_hour]['hostile_fleet_comp'] = $kill['kill_opposing_comp'].' Fleet';
						}

						if(!isset($battle_reports[$kill_day][$kill_hour]['friendly_losses'])) {
							$battle_reports[$kill_day][$kill_hour]['friendly_losses'] = 1;
							$total_our_ship_losses++;
							$battle_reports[$kill_day][$kill_hour]['friendly_value'] = $kill['kill_value'];
							$total_our_isk_loss += $kill['kill_value'];
						} else {
							$battle_reports[$kill_day][$kill_hour]['friendly_losses']++;
							$total_our_ship_losses++;
							$battle_reports[$kill_day][$kill_hour]['friendly_value'] += $kill['kill_value'];
							$total_our_isk_loss += $kill['kill_value'];
						}
					}

				}

				?>
				<table class="table table-striped">
					<tr>
						<th style="text-align: center">Battle Date</th>
						<th style="text-align: center">NC. Losses</th>
						<th style="text-align: center">NC. Fleet Type</th>
						<th style="text-align: center">LAWN Losses</th>
						<th style="text-align: center">LAWN Fleet Type</th>
						<th style="text-align: center">zKillboard Link</th>
						<th style="text-align: center">BRDOC Link</th>
					</tr>
					<?php
					foreach($battle_reports as $battle_day) {
						foreach($battle_day as $battle) {
							?>
							<tr style="text-align: center">
								<td><?php echo $battle['battle_time']; ?></td>
								<td><?php if(isset($battle['hostile_kills'])) { ?><span data-toggle="tooltip" data-placement="top" title="<?php echo number_format($battle['hostile_value'] / 1000000000, '2').' Billion Lost'; ?>"><?php echo $battle['hostile_kills']; ?></span><?php } else { echo "No kills"; } ?></td>
								<td><?php if(!isset($battle['hostile_fleet_comp'])) { echo 'Unknown Fleet'; } else { echo $battle['hostile_fleet_comp']; } ?></td>
								<td><?php if(isset($battle['friendly_losses'])) { ?><span data-toggle="tooltip" data-placement="top" title="<?php echo number_format($battle['friendly_value'] / 1000000000, '2').' Billion Lost'; ?>"><?php echo $battle['friendly_losses']; ?></span><?php } else { echo "No losses"; } ?></td>
								<td><?php if(!isset($battle['friendly_fleet_comp'])) { echo 'Unknown Fleet'; } else { echo $battle['friendly_fleet_comp']; } ?></td>
								<td><?php echo '<a target="blank" href="https://zkillboard.com/related/'.$battle['kill_system'].'/'.$battle['zkill_related'].'/">zKill Link</a>'; ?></td>
								<td>Work In Progress</td>
							</tr>
							<?php
						}
					}
					?>
				</table>
			</div>
			<div class="row" style="padding-left: 10px; padding-right: 10px; padding-top: 10px">
				<table class="table table-striped">
					<tr>
						<th style="text-align: center">Hostile Ships Lost</th>
						<th style="text-align: center">Hostile ISK Lost</th>
						<th style="text-align: center">Hostile ISK Efficiency</th>
						<th style="text-align: center">Our Ships Lost</th>
						<th style="text-align: center">Our ISK Lost</th>
						<th style="text-align: center">Our Isk Efficiency</th>
						<th style="text-align: center">Percentage Of Fucks We Give About Stats</th>
					</tr>
					<tr style="text-align: center">
						<td><?php echo number_format($total_hostile_ship_losses); ?></td>
						<td><?php echo number_format($total_hostile_isk_loss/1000000000, '2').' Billion ISK'; ?></td>
						<td><?php echo number_format($total_our_isk_loss / ($total_hostile_isk_loss + $total_our_isk_loss) * 100).'%'; ?></td>
						<td><?php echo number_format($total_our_ship_losses); ?></td>
						<td><?php echo number_format($total_our_isk_loss/1000000000, '2').' Billion ISK'; ?></td>
						<td><?php echo number_format($total_hostile_isk_loss / ($total_our_isk_loss + $total_hostile_isk_loss) * 100).'%'; ?></td>
						<td><span style="color: red; font-size: 200%">0%</span></td>
					</tr>
				</table>
			</div>
		</div>	
    </div>

</div>
<?php
require_once('includes/footer.php');
