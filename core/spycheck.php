<?php
// We are using $defining UID here temporarily while testing.
if(isset($_POST['evaluation']) AND ($user->getDirectorAccess() OR $user->getHumanResourcesAccess())) {
	$userAccountID = $_POST['evaluation'];
} else {
	$userAccountID = $user->getUID();
}
// Getting the account information for the account we're trying to Spycheck
$stmt = $db->prepare('SELECT * FROM user_accounts WHERE uid = ? LIMIT 1');
$stmt->execute(array($userAccountID));
$accountInfo = $stmt->fetch(PDO::FETCH_ASSOC);
// Confirming that the user doing the checking is either a Director or HR and that they are in the same group as the person they're checking
if($user->getDirectorAccess() OR $user->getHumanResourcesAccess() AND $user->getGroup == $accountInfo['gid']) {
	// Getting the list of characters for future use
	$stmt = $db->prepare('SELECT * FROM characters WHERE uid = ? ORDER BY charactername ASC');
	$stmt->execute(array($userAccountID));
	$characterArray = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
	// They are not requesting someone they can access, so they get to see themselves instead!
	$stmt = $db->prepare('SELECT * FROM characters WHERE uid = ? ORDER BY charactername ASC');
	$stmt->execute(array($user->getUID()));
	$characterArray = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
// Getting our corporations standings for flagging specific badness. THIS IS PLACEHOLDER CODE FOR THE ACTUAL LOGIC
$standingsArray = array();


require_once('includes/header.php');
?>
<div class="opaque-container">

    <div class="row" style="width: 100%; margin-top: 20px; margin-bottom: 20px">
		<div class="col-md-12 opaque-section" style="padding: 0px">
			<div class="row box-title-section" style="margin-bottom: 25px">
				<h1 style="text-align: center">DOGFT Spychecker</h1>
				<h3 style="text-align: center">jackash, if you will...</h3>
			</div>
			<?php showAlerts(); ?>
			<div class="row" style="padding-left: 10px; padding-right: 10px">


			  	<!-- Spychecker Navigation Tabs -->
				<ul class="nav nav-pills nav-stacked col-md-2" role="tablist" style="border-bottom: none">
					<li role="presentation">
						<form action="/spycheck/" method="post">
							<select name="evaluation" onchange="this.form.submit()" style="color: #333; margin-bottom: 4px">
								<option value="">Select Account</option>
								<?php 
								$stmt = $db->prepare('SELECT * FROM user_accounts WHERE gid = ?');
								$stmt->execute(array($user->getGroup()));
								$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
								foreach($accounts as $account) {
									?><option value="<?php echo $account['uid'];?>"><?php echo $account['username']; ?></option><?php
								}
								?>
							</select>
						</form>
					</li>
			    	<li role="presentation" class="active"><a href="#tabX" aria-controls="tabX" role="tab" data-toggle="tab">Overview</a></li>
			    	<li role="presentation"><a href="#tabY" aria-controls="tabY" role="tab" data-toggle="tab">Interactions</a></li>
			    	<li role="presentation"><a href="#tabZ" aria-controls="tabZ" role="tab" data-toggle="tab">Skills Compliance</a></li>
			    	<li role="presentation"><a href="#tabW" aria-controls="tabW" role="tab" data-toggle="tab">Doctrine Compliance</a></li>
			    	<li role="presentation"><a href="#tabA" aria-controls="tabA" role="tab" data-toggle="tab">Assets / ISK</a></li>
			    	<li role="presentation"><a href="#tabB" aria-controls="tabB" role="tab" data-toggle="tab">Killboard Stats</a></li>
			    	<li role="presentation"><a href="#tabC" aria-controls="tabC" role="tab" data-toggle="tab">Forum Activity</a></li>
				</ul>

				<!-- Spychecker Panes -->
				<div class="tab-content col-md-10">
					<!-- Overview Pane -->
				    <div role="tabpanel" class="tab-pane active" id="tabX" style="margin-top: -25px">
				    	<h3 style="text-align: center">API Key Overview</h3>
				    	<table class="table">
				    		<tr>
				    			<th>KeyID</th>
				    			<th>vCode</th>
				    			<th>Access Mask</th>
				    			<th>Key Scope</th>
				    			<th>Expiration</th>
				    		</tr>
				    		<?php
				    		$stmt = $db->prepare('SELECT * FROM user_apikeys WHERE uid = ? ORDER BY userid ASC');
				    		$stmt->execute(array($userAccountID));
				    		$apiKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);
				    		foreach($apiKeys as $key) {
				    			if($key['mask'] == MINIMUM_API) {
				    				$mask = '<span class="label label-success">Correct Mask</span>';
				    			} else {
				    				$mask = '<span class="label label-danger">Invalid - '.$key['mask'].'</span>';
				    			}
				    			if($key['keyType'] == 'Account') {
				    				$keyType = '<span class="label label-success" style="margin-left: 3px">Account</span>';
				    			} else {
				    				$keyType = '<span class="label label-danger" style="margin-left: 3px">Single Character</span>';
				    			}
				    			if($key['expires'] == 'No Expiration') {
				    				$expires = '<span class="label label-success" style="margin-left: 3px">No Expiration</span>';
				    			} else {
				    				$expires = '<span class="label label-danger" style="margin-left: 3px">Key Expires</span>';
				    			}
				    			?>
				    			<tr>
				    				<td style="text-align: center"><?php echo $key['userid']; ?></td>
				    				<td style="text-align: center"><?php echo substr($key['vcode'], 0, 25); ?></td>
				    				<td style="text-align: center"><?php echo $mask; ?></td>
				    				<td style="text-align: center"><?php echo $keyType; ?></td>
				    				<td style="text-align: center"><?php echo $expires; ?></td>
				    			</tr>

				    			<?php
				    		}
				    		?>
				    	</table>	
				        <h3 style="text-align: center">Character Overview</h3>
				        <div class="row">
				        	<?php
				        	$characters = new CharacterDashboard($userAccountID, 1);
				        	foreach($characters->getCharacters() as $character) {
				        		?>
				                <div style="width: 50%; float: left; padding-bottom: 10px">
				                  <div style="float:left"> <!-- Character Portrait -->
				                    <form action="index.php" method="post" name="setdefault" id="setdefault">
				                      <input type="hidden" name="defaultname" id="defaultname" value="<?php echo $character->getCharacterName();?>" />
				                      <input type="hidden" name="defaultid" id="defaultid" value="<?php echo $character->getCharacterID();?>" />
				                      <input type="hidden" name="location" id="location" value="index" />
				                      <input style="border: none;" type="image" name="Submit" src="https://image.eveonline.com/Character/<?php echo $character->getCharacterID(); ?>_256.jpg" height="128" width="128" />
				                    </form>
				                  </div>
				                  <div style="float:left; padding-left: 5px; padding-right: 10px"> <!-- Character Info -->
				                    <div style="float:left"> <!-- Corp / Alliance Logos -->
				                      <img src="https://image.eveonline.com/Corporation/<?php echo $character->getCorporationID();?>_64.png" height="54" width="54" />
				                      <br />
				                      <img src="https://image.eveonline.com/Alliance/<?php echo $character->getAllianceID();?>_64.png" height="54" width="54" />                        
				                    </div>
				                    <div style="float:left"> <!-- Character Details -->
				                      <ul style="list-style: none; margin-left: -30px">
				                        <li>
				                          <?php echo $character->getCharacterName(); ?>
				                        </li>
				                        <li>
				                          <?php 
				                          	$coloring = standingsCheck($character->getCorporationName(), 'span');
				                            if((strlen($character->getCorporationName())) > 30) {
				                              echo $coloring.substr($character->getCorporationName(), 0, 20).'...</span>';
				                            } else {
				                              echo $coloring.$character->getCorporationName().'</span>';
				                            }
				                          ?>
				                        </li>
				                        <li>
				                          <?php
				                          	$coloring = standingsCheck($character->getAllianceName(), 'span');
				                            if((strlen($character->getAllianceName())) > 30) {
				                              echo $coloring.substr($character->getAllianceName(), 0, 20).'...</span>';
				                            } else {
				                              echo $coloring.$character->getAllianceName().'</span>';
				                            }
				                          ?>
				                        </li>
				                        <li>
				                          <?php echo number_format($character->getAccountBalance()).' ISK'; ?>
				                        </li>
				                        <li>
				                          <span>Location: <?php echo $character->getLastKnownLocation(); ?></span>
				                        </li>
				                        <li>
				                        <span>Flying: <?php echo $character->getActiveShipName(); ?></span>
				                        </li>
				                      </ul>
				                    </div>
				                  </div>
				                </div>
				        		<?php
				        	}
				        	?>     	
				        </div>
				    </div>

				    <!-- Interactions Pane -->
				    <div role="tabpanel" class="tab-pane" id="tabY" style="margin-top: -25px">
						<div class="row box-title-section" style="margin-bottom: 10px">
							<h3 style="text-align: center">Account Interactions</h3>
						</div>
						<div class="row">
					        <div class="col-md-12">
						        <div class="panel panel-default">
									<table class="table">
									<?php
										// This is a blank array we will populate with characters and corporations
										$interactionArray = array();
										// This is the blank contactList and standings array.
										// The applicant key is a sub-array, that includes a full list of the contacts that all characters on all accounts the applicant owns is set to
										// The hr key is a sub-array that includes any flagged groups that WE have set to trigger the interaction detection
										$standingsArray['applicant'] = array();
										// Looping through all of the characters to build out their contact list
										foreach($characterArray as $character) {
											$stmt = $db->prepare('SELECT * FROM user_contactlist WHERE character_id = ?');
											$stmt->execute(array($character['charid']));
											$contactList = $stmt->fetchAll(PDO::FETCH_ASSOC);
											if($stmt->rowCount() >= 1) {
												foreach($contactList as $contact) {
													$standingsArray['applicant'][$contact['contact_name']] = $contact['contact_standing'];
												}
											}
										}
										// Pulling all of the wallet journal information to build that.
										$stmt = $db->prepare('SELECT * FROM user_walletjournal WHERE character_id = ?');
										foreach($characterArray as $character) {
											$stmt->execute(array($character['charid']));
											$journalInteractions  = $stmt->fetchAll(PDO::FETCH_ASSOC);
											foreach($journalInteractions as $journalItem) {
												// Checking if this is a Player Donation
												if($journalItem['journal_type'] == 10) {
													// Setting up the main array and the character/group name
													$interactionArray[$journalItem['journal_fromname']]['name'] = $journalItem['journal_fromname'];
													$interactionArray[$journalItem['journal_toname']]['name'] = $journalItem['journal_toname'];
													// Adding to the FROM character's count
													if(isset($interactionArray[$journalItem['journal_fromname']]['isk_xfer'])) {
														$interactionArray[$journalItem['journal_fromname']]['isk_xfer']++;
													} else {
														$interactionArray[$journalItem['journal_fromname']]['isk_xfer'] = 1;
													}
													// Adding to the TO character's count
													if(isset($interactionArray[$journalItem['journal_toname']]['isk_xfer'])) {
														$interactionArray[$journalItem['journal_toname']]['isk_xfer']++;
													} else {
														$interactionArray[$journalItem['journal_toname']]['isk_xfer'] = 1;
													}
												// Checking if this is a Player Trade 
												} elseif($journalItem['journal_type'] == 1) {
													// Setting up the main array and the character/group name
													$interactionArray[$journalItem['journal_fromname']]['name'] = $journalItem['journal_fromname'];
													$interactionArray[$journalItem['journal_toname']]['name'] = $journalItem['journal_toname'];
													// Adding to the FROM character's count
													if(isset($interactionArray[$journalItem['journal_fromname']]['trade'])) {
														$interactionArray[$journalItem['journal_fromname']]['trade']++;
													} else {
														$interactionArray[$journalItem['journal_fromname']]['trade'] = 1;
													}
													// Adding to the TO character's count
													if(isset($interactionArray[$journalItem['journal_toname']]['trade'])) {
														$interactionArray[$journalItem['journal_toname']]['trade']++;
													} else {
														$interactionArray[$journalItem['journal_toname']]['trade'] = 1;
													}
												}
											}
										}
										$eveMailArray = array();
										// Evemail check
										$stmt = $db->prepare('SELECT * FROM user_evemail WHERE character_id = ? GROUP BY message_id');
										foreach($characterArray as $character) {
											$stmt->execute(array($character['charid']));
											$evemails  = $stmt->fetchAll(PDO::FETCH_ASSOC);
											foreach($evemails as $evemail) {
												$receiverArray = explode(',', $evemail['evemail_receiver']);
												$receiverArray = array_map('trim', $receiverArray);
												// Looking to see if our user is the sender or not.
												if($evemail['evemail_type'] == 'Sent') {
													// Looping through all receivers and adding them all to the interactions
													foreach($receiverArray as $receiver) {
														if(isset($interactionArray[$receiver]['evemail'])) {
															$interactionArray[$receiver]['evemail']++;
														} else {
															$interactionArray[$receiver]['name'] = $receiver;
															$interactionArray[$receiver]['evemail'] = 1;
														}
													}
												} else {
													foreach($receiverArray as $receiver) {
														if($receiver != $evemail['character_id']) {
															if(isset($interactionArray[$receiver]['evemail'])) {
																$interactionArray[$receiver]['evemail']++;
															} else {
																$interactionArray[$receiver]['name'] = $receiver;
																$interactionArray[$receiver]['evemail'] = 1;
															}		
														}
													}
												}
											}
										}
										?>
										<tr>
											<th>Contact Name and Standing</th>
											<th style="text-align: center">Total</th>
											<th style="text-align: center">ISK Xfer</th>
											<th style="text-align: center">Trades</th>
											<th style="text-align: center">Contracts</th>
											<th style="text-align: center">Mails</th>
											<th style="text-align: center">Contact</th>
										</tr>
										<?php
										// Unsetting any characters that are on these APIs from the listed
										foreach($characterArray as $character) {
											if(isset($interactionArray[$character['charactername']])) {
												unset($interactionArray[$character['charactername']]);
											}
										}
										// Outputting the interactions
										foreach($interactionArray as $interaction) {
											// Setting the trade total to 0 if it hasn't been set before
											if(!isset($interaction['trade'])) {
												$interactionArray[$interaction['name']]['trade'] = 0;
											}
											// Setting the isk donation total to 0 if it hasn't been set before
											if(!isset($interaction['isk_xfer'])) {
												$interactionArray[$interaction['name']]['isk_xfer'] = 0;
											}
											// Setting the evemail total to 0 if it hasn't been set before
											if(!isset($interaction['evemail'])) {
												$interactionArray[$interaction['name']]['evemail'] = 0;
											}
											$interactionArray[$interaction['name']]['total'] = $interactionArray[$interaction['name']]['trade']+$interactionArray[$interaction['name']]['isk_xfer']+$interactionArray[$interaction['name']]['evemail'];
										}
										usort($interactionArray, "spycheck_sort");
										foreach($interactionArray as $interaction) {
											if($interaction['name'] != '') {
												if(isset($standingsArray['applicant'][$interaction['name']])) {
													switch($standingsArray['applicant'][$interaction['name']]):
														case "10":
															$standing = '<span class="label label-primary" style="size: 60%">+10</span>';
															break;
														case "5":
															$standing = '<span class="label label-info" style="size: 60%">+5</span>';
															break;
														case "-5":
															$standing = '<span class="label label-warning" style="size: 60%">-5</span>';
															break;
														case "-10":
															$standing = '<span class="label label-danger" style="size: 60%">-10</span>';
															break;
														default:
															$standing = '<span class="label" style="size: 60%; background-color: transparent; background-image: none">0</span>';
															break;
													endswitch;
												} else {
													$standing = '<span class="label label-default" style="size: 60%">None</span>';
												}
												$hr_button = standingsCheck($interaction['name'], 'button');
												//$hr_button = 'btn-default" style="background-color: #333; color: #f5f5f5; white-space: normal';
												?>
												<tr style="text-align: center">
													<td style="text-align: center">
														<form action="http://evewho.com/" method="post" target="_blank">
															<input type="hidden" name="type" value="search">
															<input type="hidden" name="search" value="<?php echo $interaction['name']; ?>">
															<input class="btn btn-sm <?php echo $hr_button; ?>" type="submit" value="<?php echo $interaction['name']; ?>">
														<!--<span class="label <?php echo $hr_button; ?>" ><?php echo $interaction['name']; ?></span>-->
														</form>
													</td>
													<td><?php echo $interaction['total']; ?></td>
													<td><?php echo $interaction['isk_xfer']; ?></td>
													<td><?php echo $interaction['trade']; ?></td>
													<td>XX</td>
													<td><?php echo $interaction['evemail']; ?></td>
													<td><?php echo $standing; ?></td>
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

				    <!-- Plans Pane -->
				    <div role="tabpanel" class="tab-pane" id="tabZ" style="margin-top: -25px">
						<?php
							$stmt = $db->prepare('SELECT * FROM skillplan_main WHERE gid = ? ORDER BY skillplan_order ASC');
							$stmt->execute(array($user->getGroup()));
							$skillPlans = $stmt->fetchAll(PDO::FETCH_ASSOC);
							$stmtSubGroups = $db->prepare('SELECT * FROM skillplan_subgroups WHERE skillplan_id = ? ORDER BY subgroup_order ASC');
							$stmtSkills = $db->prepare('SELECT * FROM skillplan_tracking WHERE subgroup_id = ? AND charid = ?');
							$i = 0;
							foreach($skillPlans as $plan) {
								$stmtSubGroups->execute(array($plan['skillplan_id']));
								$subGroups = $stmtSubGroups->fetchAll(PDO::FETCH_ASSOC);
								?>
								<h3 style="text-align: center"><?php echo $plan['skillplan_name']; ?></h3>
									<?php
									foreach($subGroups as $subgroup) {
										?>

								        <div class="panel-group" id="<?php echo $subgroup['subgroup_name'].'-'.$plan['skillplan_name']; ?>" role="tablist" aria-multiselectable="true">
								          	<div class="panel panel-default" style="vertical-align: middle">
								            	<div class="panel-heading box-title-section" role="tab" id="heading<?php echo $i; ?>" style="text-align: center; background-image: none; background-color: transparent; font-size: 150%; padding-top: 5px; padding-bottom: 5px">
								              		<h2 class="panel-title" style="font-size: 120%">
								                		<a role="button" data-toggle="collapse" data-parent="<?php echo $subgroup['subgroup_name'].'-'.$plan['skillplan_name']; ?>" href="#collapse<?php echo $i; ?>" aria-expanded="true" aria-controls="collapse<?php echo $i; ?>"><?php echo $subgroup['subgroup_name']; ?></a>
								              		</h2>
								            	</div>
								            	<div id="collapse<?php echo $i; ?>" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="heading<?php echo $i; ?>">
								              		<table class="table table-striped">
										              	<tr>
										              		<th></th>
															<th style="text-align: center">Character Name</th>
															<th style="text-align: center">Skills Meeting Requirements</th>
															<th style="text-align: center">total Skills</th>
										                </tr>
														<?php
														foreach($characterArray as $character) {
															$stmtSkills->execute(array($subgroup['subgroup_id'], $character['charid']));
															$skillsInfo = $stmtSkills->fetchAll(PDO::FETCH_ASSOC);
															foreach($skillsInfo as $skill) {
																if($skill['skills_trained'] >= $skill['skills_total']) {
																	$iconClass = 'class="skill-meets-requirement"';
																	$colorClass = 'class="opaque-success"';
																} elseif($skill['skills_trained'] < $skill['skills_total'] AND $skill['skills_trained'] != 0) {
																	$iconClass = 'class="skill-below-requirement"';
																	$colorClass = 'class="opaque-warning"';
																} elseif($skill['skills_trained'] == 0) {
																	$iconClass = 'class="skill-not-trained"';
																	$colorClass = 'class="opaque-danger"';
																}
																?>
																<tr <?php echo $colorClass; ?>>
																	<td <?php echo $iconClass; ?>></td>
																	<td style="text-align: center;"><a href="group.php?page=plans&view=<?php echo $skill['charid'];?>" style="color: #f5f5f5"><?php echo $skill['character_name']; ?></a></td>
																	<td style="text-align: center; color: #f5f5f5"><?php echo $skill['skills_trained']; ?></td>
																	<td style="text-align: center; color: #f5f5f5"><?php echo $skill['skills_total']; ?></td>
																</tr>

																<?php
															}
														}
														?>
								              		</table>
								            	</div>
								          	</div>
								        </div>
										<?php
										$i++;
									}
									?>
								<hr /><br /><br />
								<?php
							}
						?>
				    </div>

					<!-- Doctrines Pane -->
				    <div role="tabpanel" class="tab-pane" id="tabW" style="margin-top: -25px">
						<?php
							$stmt = $db->prepare('SELECT * FROM skillplan_main WHERE gid = ? ORDER BY skillplan_order ASC');
							$stmt->execute(array($user->getGroup()));
							$skillPlans = $stmt->fetchAll(PDO::FETCH_ASSOC);
							$stmtSubGroups = $db->prepare('SELECT * FROM skillplan_subgroups WHERE skillplan_id = ? ORDER BY subgroup_order ASC');
							$stmtSkills = $db->prepare('SELECT * FROM skillplan_tracking WHERE subgroup_id = ? AND charid = ?');
							$i = 0;
							foreach($skillPlans as $plan) {
								$stmtSubGroups->execute(array($plan['skillplan_id']));
								$subGroups = $stmtSubGroups->fetchAll(PDO::FETCH_ASSOC);
								?>
								<h3 style="text-align: center"><?php echo $plan['skillplan_name']; ?></h3>
									<?php
									foreach($subGroups as $subgroup) {
										?>

								        <div class="panel-group" id="<?php echo $subgroup['subgroup_name'].'-'.$plan['skillplan_name']; ?>" role="tablist" aria-multiselectable="true">
								          	<div class="panel panel-default" style="vertical-align: middle">
								            	<div class="panel-heading box-title-section" role="tab" id="heading<?php echo $i; ?>" style="text-align: center">
								              		<h2 class="panel-title">
								                		<a role="button" data-toggle="collapse" data-parent="<?php echo $subgroup['subgroup_name'].'-'.$plan['skillplan_name']; ?>" href="#collapse<?php echo $i; ?>" aria-expanded="true" aria-controls="collapse<?php echo $i; ?>"><?php echo $subgroup['subgroup_name']; ?></a>
								              		</h2>
								            	</div>
								            	<div id="collapse<?php echo $i; ?>" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="heading<?php echo $i; ?>">
								              		<table class="table">
										              	<tr>
										              		<th></th>
															<th style="text-align: center">Character Name</th>
															<th style="text-align: center">Skills Meeting Requirements</th>
															<th style="text-align: center">total Skills</th>
										                </tr>
														<?php
														foreach($characterArray as $character) {
															$stmtSkills->execute(array($subgroup['subgroup_id'], $character['charid']));
															$skillsInfo = $stmtSkills->fetchAll(PDO::FETCH_ASSOC);
															foreach($skillsInfo as $skill) {
																if($skill['skills_trained'] >= $skill['skills_total']) {
																	$iconClass = 'class="meetsreq"';
																	$colorClass = 'class="goodColorBack"';
																} elseif($skill['skills_trained'] < $skill['skills_total'] AND $skill['skills_trained'] != 0) {
																	$iconClass = 'class="belowreq"';
																	$colorClass = 'class="okayColorBack"';
																} elseif($skill['skills_trained'] == 0) {
																	$iconClass = 'class="nottrained"';
																	$colorClass = 'class="badColorBack"';
																}
																?>
																<tr <?php echo $colorClass; ?>>
																	<td <?php echo $iconClass; ?>></td>
																	<td style="text-align: center;"><a href="group.php?page=plans&view=<?php echo $skill['charid'];?>" style="color: #f5f5f5"><?php echo $skill['character_name']; ?></a></td>
																	<td style="text-align: center; color: #f5f5f5"><?php echo $skill['skills_trained']; ?></td>
																	<td style="text-align: center; color: #f5f5f5"><?php echo $skill['skills_total']; ?></td>
																</tr>

																<?php
															}
														}
														?>
								              		</table>
								            	</div>
								          	</div>
								        </div>
										<?php
										$i++;
									}
									?>
								<hr /><br /><br />
								<?php
							}
						?>
				    </div>

				    <!--  Pane -->
				    <div role="tabpanel" class="tab-pane" id="tabA" style="margin-top: -25px">
				        <h3 style="text-align: center">Assets and ISK</h3>
				        <div class="panel panel-default">
							<table class="table">
								<tr>
									<th></th>
									<th style="text-align: center">Skill Name</th>
									<th style="text-align: center">Trained Level</th>
									<th style="text-align: center">Required Level</th>
								</tr>
								<tr>
									<td></td>
									<td style="text-align: center; color: #f5f5f5">Testing</td>
									<td style="text-align: center; color: #f5f5f5">Testing</td>
									<td style="text-align: center; color: #f5f5f5">Testing</td>
								</tr>
							</table>
				        </div>
				    </div>

				    <!--  Pane -->
				    <div role="tabpanel" class="tab-pane" id="tabB" style="margin-top: -25px">
				        <h3 style="text-align: center">Killboard Stats</h3>
				        <div class="panel panel-default">
							<table class="table">
								<tr>
									<th></th>
									<th style="text-align: center">Skill Name</th>
									<th style="text-align: center">Trained Level</th>
									<th style="text-align: center">Required Level</th>
								</tr>
								<tr>
									<td></td>
									<td style="text-align: center; color: #f5f5f5">Testing</td>
									<td style="text-align: center; color: #f5f5f5">Testing</td>
									<td style="text-align: center; color: #f5f5f5">Testing</td>
								</tr>
							</table>
				        </div>
				    </div>

				    <!--  Pane -->
				    <div role="tabpanel" class="tab-pane" id="tabC" style="margin-top: -25px">
				        <h3 style="text-align: center">Forum Activity</h3>
				        <div class="panel panel-default">
							<table class="table">
								<tr>
									<th></th>
									<th style="text-align: center">Skill Name</th>
									<th style="text-align: center">Trained Level</th>
									<th style="text-align: center">Required Level</th>
								</tr>
								<tr>
									<td></td>
									<td style="text-align: center; color: #f5f5f5">Testing</td>
									<td style="text-align: center; color: #f5f5f5">Testing</td>
									<td style="text-align: center; color: #f5f5f5">Testing</td>
								</tr>
							</table>
				        </div>
				    </div>


				</div>




			</div>
		</div>	
    </div>

</div>
<?php
require_once('includes/footer.php');