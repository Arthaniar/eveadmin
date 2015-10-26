<?php
if($request['action'] != NULL) {
	if($request['action'] == 'setprimary') {
		$user->setDefault($_POST['characterName'], $_POST['characterID']);
	} elseif($request['action'] == 'hide') {
		$stmt = $db->prepare("UPDATE characters SET showchar = 0 WHERE charid = ?");
		$stmt->execute(array($_POST['characterID']));
	} elseif($request['action'] == 'restore') {
		$stmt = $db->prepare('UPDATE characters SET showchar = 1 WHERE uid = ?');
		$stmt->execute(array($user->getUID()));
	}
}

require_once('includes/header.php');



// Getting all characters except the Main Character for output in the Dashboard
$characters = new CharacterDashboard($user, $user->getDefaultID());

// Getting the main character specifically
$mainCharacter = new Character($user->getDefaultID(), $user->getDefaultKeyID(), $user->getDefaultVCode(), $user->getDefaultAccessMask(), $db, $user);
?>
<div class="opaque-container">

    <div class="row" style="width: 100%; padding-top: 20px; padding-bottom: 15px">
    <!-- Main Character Box -->
    	<?php
    	// This is where we do all of the logic for the Main Character output

    	// Getting the correct string for what our Main Character is flying and where he/she is
		if($mainCharacter->getActiveShipName() != 'Out Of Capsule') {
			$prepositionLookup = $mainCharacter->getActiveShipName();
			$first = $prepositionLookup[0];
			$preposition = '';
			if(preg_match('/^[aeiou]\z/i', $first)) {
				$preposition = "n";
			}
			$locationString = 'Location: In '.$mainCharacter->getLastKnownLocation()." flying a".$preposition." ".$mainCharacter->getActiveShipName();
		} else {
			$locationString = 'Character is currently outside of their pod in '.$mainCharacter->getLastKnownLocation();
		}

		//Looking up the ISK balance for all characters
		$stmt = $db->prepare('SELECT sum(balance) as GlobalBalance FROM characters WHERE uid = ?');
		$stmt->execute(array($user->getUID()));
		$globalISKSum = $stmt->fetch();

		// Main Character active training
		$trainingTime = timeConversion($mainCharacter->getEndOfTrainingTime());
		$queueTime = timeConversion($mainCharacter->getEndOfQueueTime());
		
    	?>
		<div class ="col-md-8 col-sm-12 opaque-section">
			<div class="row">
				<div class="col-lg-2 col-md-3 hidden-xs-sm character-image-section">
					<ul style="list-style: none; margin-left: -20px; margin-bottom: 0px;">
						<li>
							<img width="100%" src="https://image.eveonline.com/Corporation/<?php echo $mainCharacter->getCorporationID(); ?>_256.png" style="margin-right: 2px" />
						</li>
						<li>
							<img width="100%" src="https://image.eveonline.com/Alliance/<?php echo $mainCharacter->getAllianceID(); ?>_128.png" style="margin-left: 2px" />
						</li>
					</ul>
				</div>
				<div class="col-lg-2 col-md-3 hidden-xs-sm character-image-section">
					<ul style="list-style: none; margin-left: -20px; margin-bottom: 0px">
						<li>
							<img width="100%" src="https://image.eveonline.com/Character/<?php echo $mainCharacter->getCharacterID();?>_256.jpg"  />
						</li>
						<li>
							<img width="100%" src="https://image.eveonline.com/Render/<?php echo $mainCharacter->getActiveShipTypeID(); ?>_256.png" style="margin-left: 2px" />
						</li>
					</ul>
					
				</div>
				<div class="col-lg-8 col-md-6 col-sm-12 main-character-text" style="padding-left: 5px">
            		<ul style="list-style: none; padding-left: 5px">
              			<li><span class="eve-text" style="font-size: 270%;"><?php echo $mainCharacter->getCharacterName();?></span></li>
              			<li><span class="eve-text" style="font-size: 180%"><?php echo $mainCharacter->getCorporationName();?> / <?php echo $mainCharacter->getAllianceName();?></span></li>
              			<li>Skillpoints: <?php echo number_format($mainCharacter->getSkillPoints()); ?></li>
              			<li>Training: <span style="<?php echo $trainingTime['color']; ?>" id="<?php echo $mainCharacter->getCharacterID(); ?>" ><?php echo $trainingTime['timestring']; ?></span> for <?php echo $eve->getTypeName($mainCharacter->getCurrentSkillTraining()); ?> to Level <?php echo $mainCharacter->getCurrentTrainingLevel(); ?></li>
              			<li>Skill Queue: <span style="<?php echo $queueTime['color']; ?>" id="<?php echo $mainCharacter->getCharacterID(); ?>" ><?php echo $queueTime['timestring']; ?></span> remaining for <?php echo $mainCharacter->getNumberOfQueuedSkills(); ?> skills.</li>
              			<li><?php echo $locationString; ?></li>
						<li>Wallet Balance: <?php echo number_format($mainCharacter->getAccountBalance()); ?> ISK</li>
						<li>Combined Total Balance: <?php echo number_format($globalISKSum['GlobalBalance']); ?> ISK</li>
					</ul>		
				</div>
			</div>
		</div>
	<!-- Operations Calendar Box -->
		<?php
		// This is where we do all the logic for the Operations Calendar output.

		$stmt = $db->prepare('SELECT * FROM group_operations WHERE gid = ? AND operation_timestamp >= ? ORDER BY operation_timestamp ASC LIMIT 3');
		$stmt->execute(array($user->getGroup(), time()));
		$operations = $stmt->fetchAll(PDO::FETCH_ASSOC);
		?>
		<div class="col-md-4 col-sm-12 mobile-reconfig" style="padding-right: 0px">
			<div class="col-md-12 opaque-section" style="padding: 0px">
				<div class="row box-title-section">
					<h3 style="text-align: center"><a class="box-title-link" href="/operations/view/" style="text-decoration: none;">Operations Calendar</a></h3>
				</div>
					<div class="row" style="padding-left: 10px; padding-right: 10px">
					<?php
					if(!empty($operations)) {
						?>
						<table class="table table-striped" style="margin-bottom: 16px; margin-top: 10px">
				      		<tr class="eve-text">
				      			<th class="eve-table-header">Operation Name</th>
				      			<th class="eve-table-header">Type</th>
				      			<th class="eve-table-header">Time</th>
				      		</tr>
							<?php
							foreach($operations as $operation){
								$operationTime = timeConversion(date('Y-m-d H:i:s', $operation['operation_timestamp']));
								if($operation['operation_type'] == 'CTA Op') {
									$classes = 'eve-text strat-op';
								} else {
									$classes = 'eve-text';
								}
								?>
					      		<tr style="text-align: center; font-size: 125%" class="<?php echo $classes; ?>">
					      			<td><?php echo $operation['operation_name'];?></td>
					      			<td><?php echo $operation['operation_type'];?></td>
					      			<td><?php echo $operationTime['timestring']; ?></span></td>
					      		</tr>
								<?php
							}
							?>
						</table>
						<?php 
						if($user->getFleetCommanderAccess() OR $user->getDirectorAccess()) { 
							?>
							<h4 class="eve-text" style="text-align: center; margin-bottom: 20px;"><a class="box-title-link" style="text-decoration: none" href="/operations/schedule/">Schedule An Operation</a></h4>
							<?php
						}
					} else {
						?>
						<h3 class="eve-text" style="text-align: center; margin-bottom: 20px">No Operations Currently Scheduled.<br/>Check Back Later <?php if($user->getFleetCommanderAccess() OR $user->getDirectorAccess()) { echo 'Or <a class="box-title-link" style="text-decoration: none" href="/operations/schedule/">Schedule A Fleet.</a>'; }  else { echo 'Or Poke An FC.'; } ?></h3>
						<?php
					}
					?>
				</div>
			</div>
		</div>
	</div>
	<!-- Character Information Section -->
    <div class="row" style="width: 100%; margin-top: 20px; margin-bottom: 20px">
		<div class="col-md-12 hidden-xs-sm opaque-section" style="padding: 0px">
			<div class="row box-title-section">
				<h3 style="text-align: center">Character Information<h3>
			</div>
			<div class="row" style="padding-left: 10px; padding-right: 10px">
				<table class="table table-striped" style="margin-bottom: 16px; margin-top: 10px">
		      		<tr class="eve-text">
		      			<th class="eve-table-header">Character Name</th>
		      			<th class="eve-table-header">Corporation</th>
		      			<th class="eve-table-header">Current Training</th>
		      			<th class="eve-table-header">Training Time Left</th>
		      			<th class="eve-table-header">Total Queue Length</th>
		      			<th class="eve-table-header">ISK Balance</th>
		      			<th class="eve-table-header">Location</th>
		      			<th class="eve-table-header"></th>
		      		</tr>

		      		<?php 
		      		foreach($characters->getCharacters() as $character) {

				    	// Getting the correct string for what our Main Character is flying and where he/she is
						if($character->getActiveShipName() != 'Out Of Capsule' AND $character->getActiveShipName() != "Capsule - Genolution 'Auroral' 197-variant") {
							$locationString = $character->getLastKnownLocation().": ".$character->getActiveShipName();
						} else {
							$locationString = $character->getLastKnownLocation().': Capsule';
						}

						$trainingTime = timeConversion($character->getEndOfTrainingTime());
						$queueTime = timeConversion($character->getEndOfQueueTime());

		      			?>
			      		<tr style="text-align: center">
			      			<td><?php echo $character->getCharacterName(); ?></td>
			      			<td><?php if($character->getCorporationTicker() == NULL) { echo "---"; } else { echo $character->getCorporationTicker(); } ?></td>
			      			<td><?php if($character->getEndOfTrainingTime() == NULL) { echo "---"; } else { echo $eve->getTypeName($character->getCurrentSkillTraining()).' '.$character->getcurrentTrainingLevel(); } ?></td>
			      			<td><span style="<?php echo $trainingTime['color']; ?>" id="<?php echo $character->getCharacterID(); ?>" ><?php echo $trainingTime['timestring']; ?></span></td>
							<td><span style="<?php echo $queueTime['color']; ?>" id="<?php echo $character->getCharacterID(); ?>" ><?php echo $queueTime['timestring']; ?></span></td>
			      			<td><?php echo number_format($character->getAccountBalance()); ?> ISK</td>
			      			<td><?php echo $locationString; ?></td>
			      			<td>
				      			<form action="/dashboard/setprimary/" method="post" style="float: left">
				      				<input type="hidden" name="characterID" value="<?php echo $character->getCharacterID(); ?>">
				      				<input type="hidden" name="characterName" value="<?php echo $character->getCharacterName(); ?>">
				      				<input type="submit" value="Set Primary" class="btn btn-sm btn-primary" name="change_primary_character">
				      			</form>
				      			<form action="/dashboard/hide/" method="post" style="float: left; clear: right; margin-left: 3px">
				      				<input type="hidden" name="characterID" value="<?php echo $character->getCharacterID(); ?>">
				      				<input type="submit" value="Hide" class="btn btn-sm btn-warning" name="hide_character">
				      			</form>
			      			</td>
			      		</tr>
		      			<?php
		      		}
		      		?>
		      		<tr>
		      			<td></td>
		      			<td></td>
		      			<td></td>
		      			<td></td>
		      			<td></td>
		      			<td></td>
		      			<td></td>
		      			<td>
		      				<form method="post" action="/dashboard/restore/">
		      					<input class="btn btn-primary reskinned-button pull-right" type="submit" value="Restore Hidden Characters" name="restore_characters">
		      				</form>
		      			</td>
		      		</tr>

		      	</table>
			</div>
		</div>
		<div class="col-sm-12 hidden-lg hidden-md">
			<?php
	  		foreach($characters->getCharacters() as $character) {

		    	// Getting the correct string for what our Main Character is flying and where he/she is
				if($character->getActiveShipName() != 'Out Of Capsule' AND $character->getActiveShipName() != "Capsule - Genolution 'Auroral' 197-variant") {
					$locationString = $character->getLastKnownLocation().": ".$character->getActiveShipName();
				} else {
					$locationString = $character->getLastKnownLocation().': Capsule';
				}

	  			?>
	  			<div class="row" style="margin-bottom: 10px">
	      			<div class="col-sm-offset-3 col-sm-6 col-xs-12 opaque-section" style="text-align: center">
		      			<img width="100%" src="https://image.eveonline.com/Character/<?php echo $character->getCharacterID();?>_256.jpg"  /><br />
		      			<h2 class="eve-text" style="color: #f5f5f5"><?php echo $character->getCharacterName(); ?></h2><br />
		      			<span class="eve-text" style="font-size: 180%"><?php if($character->getEndOfTrainingTime() == NULL) { echo "Not Currently Training"; } else { echo 'Training '.$eve->getTypeName($character->getCurrentSkillTraining()).' '.$character->getcurrentTrainingLevel(); } ?></span>
	      			</div>
	      		</div>
	  			<?php
	  		}
	  		?>	
		</div>	
    </div>
</div>
<?php
require_once('includes/footer.php');