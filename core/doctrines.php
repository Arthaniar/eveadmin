<?php

require_once('includes/header.php');

// Checking to see what page we've requested
if($request['action'] != 'view') {

	if($request['action'] == 'add') {
		$stmt = $db->prepare('INSERT INTO doctrines (gid,doctrine_name,doctrine_owner,doctrine_use,doctrine_requirement,doctrine_staging) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE doctrine_owner=VALUES(doctrine_owner),doctrine_use=VALUES(doctrine_use),doctrine_requirement=VALUES(doctrine_requirement),doctrine_staging=VALUES(doctrine_staging)');
		$stmt->execute(array($user->getGroup(), $_POST['doctrine_name'], $_POST['doctrine_owner'], $_POST['doctrine_use'], $_POST['doctrine_requirement'],$_POST['doctrine_staging']));
	}

	$stmt = $db->prepare('SELECT * FROM doctrines WHERE gid = ? ORDER BY doctrine_owner,doctrine_name ASC');
	$stmt->execute(array($user->getGroup()));
	$doctrines = $stmt->fetchAll(PDO::FETCH_ASSOC);
	?>
	<div class="opaque-container" role="tablist" aria-multiselectable="true">

	    <div class="row" style="width: 100%; margin-top: 20px; margin-bottom: 20px">
			<div class="col-md-12 opaque-section" style="padding: 0px">
				<div class="row box-title-section">
					<a class="box-title-link" style="text-decoration: none" >
						<h1 class="eve-text" style="margin-top: 10px; text-align: center; font-size: 200%; font-weight: 700">Fleet Doctrines</h1>
					</a>
				</div>
				<div>
					<div class="col-md-12">
						<div class="row" style="padding-top: 20px;">
							<table class="table table-striped">
								<tr>
									<th>Doctrine Name</th>
									<th>Priamry Users</th>
									<th>Fleet Type</th>
									<th>Requirement</th>
									<th>Staging Location</th>
								</tr>
								<?php
								foreach($doctrines as $doctrine) {


									if($doctrine['doctrine_owner'] == 'group') {
										$owner = $settings->getGroupTicker();
									} else {
										$owner = $doctrine['doctrine_owner'];
									}
									?>
									<tr>
										<td><a href="/doctrines/view/<?php echo $doctrine['doctrineid']; ?>"><?php echo $doctrine['doctrine_name'];?></a></td>
										<td><?php echo $owner; ?></td>
										<td><?php echo $doctrine['doctrine_use'];?></td>
										<td><?php echo $doctrine['doctrine_requirement'];?></td>
										<td><?php echo $doctrine['doctrine_staging'];?></td>
									</tr>
									<?php
								}
								if($user->getDirectorAccess()) {
									?>
									<tr>
										<form action="/doctrines/add/" method="post">
											<formfield>
												<td><input class="form-control" name="doctrine_name" type="text" placeholder="Doctrine Name"></td>
											</formfield>
											<formfield>
												<td><input class="form-control" name="doctrine_owner" type="text"></td>
											</formfield>
											<formfield>
												<td><input class="form-control" name="doctrine_use" type="text" placeholder="Doctrine Usage"></td>
											</formfield>
											<formfield>
												<td><input class="form-control" name="doctrine_requirement" type="text" placeholder="Doctrine Requirement"></td>
											</formfield>
											<td>
												<formfield><input class="form-control" name="doctrine_staging" type="text" placeholder="Doctrine Staging System"></formfield>
												<input class="btn btn-primary pull-right" style="margin-top: 5px" type="submit" value="Create Doctrine"></td>
										</form>
									</tr>
									<?php
								}
								?>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
} else {

	if($user->getDirectorAccess()) {
		if($request['value_2'] == 'addfit') {
			Fitting::addFitting($request['value'], $_POST['fitting_raw'], $_POST['fitting_role'], $_POST['fitting_priority'], $_POST['fitting_notes'], $user->getGroup(), $user);
		} elseif($request['value_2'] == 'deletefit') {
	        $stmt = $db->prepare('DELETE FROM doctrines_fittingmods WHERE fittingid = ?');
	        $stmt->execute(array($_POST['fitting_id']));
	        $stmt = $db->prepare('DELETE FROM doctrines_fits WHERE fittingid = ?');
	        $stmt->execute(array($_POST['fitting_id']));
		} elseif($request['value_2'] == 'editfit') {
			$stmt = $db->prepare('UPDATE doctrines_fits SET fitting_name = ?, fitting_role = ?, fitting_priority = ?, fitting_notes = ? WHERE fittingid = ?');
			$stmt->execute(array($_POST['fitting_name'], $_POST['fitting_role'], $_POST['fitting_priority'], $_POST['fitting_notes'], $_POST['fitting_id']));
			setAlert('success', 'Fitting Updated', 'The '.$_POST['fitting_name'].' fit has been successfully edited.');
		} elseif($request['value_2'] == 'editdoctrine') {
			$stmt = $db->prepare('UPDATE doctrines SET doctrine_name = ?, doctrine_use = ?, doctrine_requirement = ?, doctrine_owner = ?, doctrine_staging = ? WHERE doctrineid = ?');
			$stmt->execute(array($_POST['doctrine_name'],$_POST['doctrine_use'],$_POST['doctrine_requirement'],$_POST['doctrine_owner'],$_POST['doctrine_staging'],$_POST['doctrine_id']));
		}
	}

	$fittings = array();
	$fitting_prerequsites = array();

	$stmt = $db->prepare('SELECT * FROM doctrines WHERE doctrineid = ? AND gid = ? LIMIT 1');
	$stmt->execute(array($request['value'], $user->getGroup()));
	$doctrine = $stmt->fetch(PDO::FETCH_ASSOC);

	$stmt = $db->prepare('SELECT * FROM doctrines_fits WHERE doctrineid = ? AND fitting_role = "Logistics" AND gid = ? ORDEr BY fitting_priority DESC');
	$stmt->execute(array($request['value'], $user->getGroup()));
	$fittings['logistics'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

	$stmt = $db->prepare('SELECT * FROM doctrines_fits WHERE doctrineid = ? AND fitting_role = "Mainline" AND gid = ? ORDEr BY fitting_priority DESC');
	$stmt->execute(array($request['value'], $user->getGroup()));
	$fittings['mainline'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

	$stmt = $db->prepare('SELECT * FROM doctrines_fits WHERE doctrineid = ? AND fitting_role = "DPS" AND gid = ? ORDEr BY fitting_priority DESC');
	$stmt->execute(array($request['value'], $user->getGroup()));
	$fittings['dps'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

	$stmt = $db->prepare('SELECT * FROM doctrines_fits WHERE doctrineid = ? AND fitting_role != "Logistics" AND fitting_role != "Mainline" AND fitting_role != "DPS" AND gid = ? ORDEr BY fitting_priority DESC');
	$stmt->execute(array($request['value'], $user->getGroup()));
	$fittings['other'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

	// The stuff below is the modal section, which will be called later in the page
	foreach($fittings as $fitting_group) {
		foreach($fitting_group as $fitting) {
			$fitting_prerequsites[$fitting['fittingid']] = array();
			$fitting_prerequsites[$fitting['fittingid']]['warning'] = 0;
			$fitting_prerequsites[$fitting['fittingid']]['danger'] = 0;
			?>
			<div class="modal fade" id="viewFitting<?php echo $fitting['fittingid']; ?>" tabindex="-1" role="dialog" aria-labelledby="viewFittingLabel<?php echo $fitting['fittingid']; ?>" aria-hidden="true" >
				<div class="modal-dialog">
	              		<div class="modal-content">
	              			<!-- Modal Header -->
	                		<div class="modal-header">
	                  			<button type="button" class="close" data-dismiss="modal" aria-label="Close">
	                  				<span aria-hidden="true">&times;</span>
	                  			</button>
	                  			<h4 class="modal-title" id="viewFittingLabel<?php echo $fitting['fittingid']; ?>"><?php echo $fitting['fitting_name'].' - '.$doctrine['doctrine_name']; ?></h4>
	                		</div>
	                		<!-- Modal Body -->
	                		<div class="modal-body" style="text-align: center">
	                			<!-- Modal Navigation -->
	                			<ul class="nav nav-pills" role="tablist">
								    <li role="presentation" class="active" style="border-top: 5px">
								    	<a class="navigation-option" href="#fitting<?php echo $fitting['fittingid']; ?>" aria-controls="#fitting<?php echo $fitting['fittingid']; ?>" role="tab" data-toggle="tab">
								    		Ship Fitting</a>
								    </li>
								    <li role="presentation">
								    	<a class="navigation-option" href="#details<?php echo $fitting['fittingid']; ?>" aria-controls="details<?php echo $fitting['fittingid']; ?>" role="tab" data-toggle="tab">
								    		Fitting Details
								    	</a>
								    </li>
								    <li role="presentation">
								    	<a class="navigation-option" href="#skills<?php echo $fitting['fittingid']; ?>" aria-controls="skills<?php echo $fitting['fittingid']; ?>" role="tab" data-toggle="tab">
								    		Minimum Required Skills
								    	</a>
								    </li>
								    <?php
								    if($user->getDirectorAccess()) {
								    	?>
									    <li role="presentation">
									    	<a class="navigation-option" href="#edit<?php echo $fitting['fittingid']; ?>" aria-controls="skills<?php echo $fitting['fittingid']; ?>" role="tab" data-toggle="tab">
									    		Edit Fitting
									    	</a>
									    </li>
									    <?php
									}
								    ?>
								</ul>
								<!-- Modal Tab Panels -->
								<div class="tab-content">
									<div role="tabpanel" class="tab-pane active" id="fitting<?php echo $fitting['fittingid']; ?>">
										<table class="table table-striped">
											<tbody>
												<tr>
													<th><img style="width: 24px; height: 24px;" src="/img/slot_ship.jpg"></th>
													<th></th>
													<th style="text-align: center"><?php echo $eve->getTypeName($fitting['fitting_ship']).', '.$fitting['fitting_name']; ?></th>
													<th></th>
												</tr>
												<?php
													// Creating the IGB Fitting Array
													$igb_fitting_array = array();

													$preRequisites = Fitting::checkItemPrerequisites($fitting['fitting_ship'], $user->getDefaultID());

													if($preRequisites) {
														$prereq_color = 'class="opaque-success"';
													} elseif($preRequisites == 'WARNING') {
														$prereq_color = 'class="opaque-warning"';
														$fitting_prerequsites[$fitting['fittingid']]['warning']++;
													} else {
														$prereq_color = 'class="opaque-danger"';
														$fitting_prerequsites[$fitting['fittingid']]['danger']++;
													}

													// Adding the SHIP Hull ID to the IGB Fitting Array
													$igb_fitting_array[] = $fitting['fitting_ship'];
												?>
												<tr <?php echo $prereq_color; ?>>
													<td style="text-align: left; width; 32px"><img style="width: 24px; height: 24px;" src="https://image.eveonline.com/InventoryType/<?php echo $fitting['fitting_ship']; ?>_32.png"></td>
													<td style="text-align: left; width; 50px"></td>
													<td style="text-align: left; width; 100%"><?php echo $eve->getTypeName($fitting['fitting_ship']); ?></td>
													<td style="width: 100px">
														<a href="#"><span data-toggle="tooltip" data-placement="top" title="View On Market" class="glyphicon glyphicon-euro"></span></a>
														<a href="#"><span data-toggle="tooltip" data-placement="top" title="Add To Purchase Cart" class="glyphicon glyphicon-shopping-cart"></span></a>
														<a href="#"><span data-toggle="tooltip" data-placement="top" title="View on Evelopedia" class="glyphicon glyphicon-file"></span></a>
														<a href="#"><span data-toggle="tooltip" data-placement="top" title="Show Info" class="glyphicon glyphicon-info-sign"></span></a>
													</td>
												</tr>
												<?php
												$stmt = $db->prepare('SELECT * FROM doctrines_fittingmods WHERE fittingid = ? AND module_slot = ?');

												// Getting our Subsystems items first
												$stmt->execute(array($fitting['fittingid'], 'Subsystem'));
												$subsys_slots = $stmt->fetchAll(PDO::FETCH_ASSOC);

												if($stmt->rowCount() >= 1) {
													?>
													<tr>
														<td><img style="width: 24px; height: 24px;" src="/img/slot_subsys.jpg"></td>
														<td></td>
														<td>Subsystems Modules</td>
														<td></td>
													</tr>
													<?php
													foreach($subsys_slots as $subsys) {
														// Adding subsystems to the IGB Fitting Array
														$igb_fitting_array[] = $subsys['type_id'].';'.$subsys['module_quantity'];

														$preRequisites = Fitting::checkItemPrerequisites($subsys['type_id'], $user->getDefaultID());

														if($preRequisites) {
															$prereq_color = 'class="opaque-success"';
														} elseif($preRequisites == 'WARNING') {
															$prereq_color = 'class="opaque-warning"';
														} else {
															$prereq_color = 'class="opaque-danger"';
														}
														?>
														<tr <?php echo $prereq_color; ?>>
															<td style="width: 32px; vertical-align: center"><img style="width: 24px; height: 24px;" src="https://image.eveonline.com/InventoryType/<?php echo $subsys['type_id']; ?>_32.png"></td>
															<td style="text-align: left; width: 50px"><?php echo $subsys['module_quantity']; ?>x</td>
															<td style="text-align: left; width: auto"><?php echo $eve->getTypeName($subsys['type_id']); ?></td>
															<td style="width: 100px">
																<a href="#"><span data-toggle="tooltip" data-placement="top" title="View On Market" class="glyphicon glyphicon-euro"></span></a>
																<a href="#"><span data-toggle="tooltip" data-placement="top" title="Add To Purchase Cart" class="glyphicon glyphicon-shopping-cart"></span></a>
																<a href="#"><span data-toggle="tooltip" data-placement="top" title="View on Evelopedia" class="glyphicon glyphicon-file"></span></a>
																<a href="#"><span data-toggle="tooltip" data-placement="top" title="Show Info" class="glyphicon glyphicon-info-sign"></span></a>
															</td>
														</tr>
														<?php
													}
												}

												// Getting our High Slot items first
												$stmt->execute(array($fitting['fittingid'], 'High'));
												$high_slots = $stmt->fetchAll(PDO::FETCH_ASSOC);

												if($stmt->rowCount() >= 1) {
													?>
													<tr>
														<td><img style="width: 24px; height: 24px;" src="/img/slot_hi.png"></td>
														<td></td>
														<td>High Slot Modules</td>
														<td></td>
													</tr>
													<?php
													foreach($high_slots as $high) {
														// Adding high slot items to the IGB Fitting Array
														$igb_fitting_array[] = $high['type_id'].';'.$high['module_quantity'];

														$preRequisites = Fitting::checkItemPrerequisites($high['type_id'], $user->getDefaultID());

														if($preRequisites) {
															$prereq_color = 'class="opaque-success"';
														} elseif($preRequisites == 'WARNING') {
															$prereq_color = 'class="opaque-warning"';
															$fitting_prerequsites[$fitting['fittingid']]['warning']++;
														} else {
															$prereq_color = 'class="opaque-danger"';
															$fitting_prerequsites[$fitting['fittingid']]['danger']++;
														}
														?>
														<tr <?php echo $prereq_color; ?>>
															<td style="width: 32px; vertical-align: center"><img style="width: 24px; height: 24px;" src="https://image.eveonline.com/InventoryType/<?php echo $high['type_id']; ?>_32.png"></td>
															<td style="text-align: left; width: 50px"><?php echo $high['module_quantity']; ?>x</td>
															<td style="text-align: left; width: auto"><?php echo $eve->getTypeName($high['type_id']); ?></td>
															<td style="width: 100px">
																<a href="#"><span data-toggle="tooltip" data-placement="top" title="View On Market" class="glyphicon glyphicon-euro"></span></a>
																<a href="#"><span data-toggle="tooltip" data-placement="top" title="Add To Purchase Cart" class="glyphicon glyphicon-shopping-cart"></span></a>
																<a href="#"><span data-toggle="tooltip" data-placement="top" title="View on Evelopedia" class="glyphicon glyphicon-file"></span></a>
																<a href="#"><span data-toggle="tooltip" data-placement="top" title="Show Info" class="glyphicon glyphicon-info-sign"></span></a>
															</td>
														</tr>
														<?php
													}
												}

												// Getting our Mid Slot items first
												$stmt->execute(array($fitting['fittingid'], 'Mid'));
												$mid_slots = $stmt->fetchAll(PDO::FETCH_ASSOC);

												if($stmt->rowCount() >= 1) {
													?>
													<tr>
														<td><img style="width: 24px; height: 24px;" src="/img/slot_mid.png"></td>
														<td></td>
														<td>Mid Slot Modules</td>
														<td></td>
													</tr>
													<?php
													foreach($mid_slots as $mid) {
														// Adding mid slots to IGB Fitting Array
														$igb_fitting_array[] = $mid['type_id'].';'.$mid['module_quantity'];

														$preRequisites = Fitting::checkItemPrerequisites($mid['type_id'], $user->getDefaultID());

														if($preRequisites) {
															$prereq_color = 'class="opaque-success"';
														} elseif($preRequisites == 'WARNING') {
															$prereq_color = 'class="opaque-warning"';
															$fitting_prerequsites[$fitting['fittingid']]['warning']++;
														} else {
															$prereq_color = 'class="opaque-danger"';
															$fitting_prerequsites[$fitting['fittingid']]['danger']++;
														}
														?>
														<tr <?php echo $prereq_color; ?>>
															<td style="width: 32px; vertical-align: center"><img style="width: 24px; height: 24px;" src="https://image.eveonline.com/InventoryType/<?php echo $mid['type_id']; ?>_32.png"></td>
															<td style="text-align: left; width: 50px"><?php echo $mid['module_quantity']; ?>x</td>
															<td style="text-align: left; width: auto"><?php echo $eve->getTypeName($mid['type_id']); ?></td>
															<td style="width: 100px">
																<a href="#"><span data-toggle="tooltip" data-placement="top" title="View On Market" class="glyphicon glyphicon-euro"></span></a>
																<a href="#"><span data-toggle="tooltip" data-placement="top" title="Add To Purchase Cart" class="glyphicon glyphicon-shopping-cart"></span></a>
																<a href="#"><span data-toggle="tooltip" data-placement="top" title="View on Evelopedia" class="glyphicon glyphicon-file"></span></a>
																<a href="#"><span data-toggle="tooltip" data-placement="top" title="Show Info" class="glyphicon glyphicon-info-sign"></span></a>
															</td>
														</tr>
														<?php
													}
												}

												// Getting our Low Slot items first
												$stmt->execute(array($fitting['fittingid'], 'Low'));
												$low_slots = $stmt->fetchAll(PDO::FETCH_ASSOC);

												if($stmt->rowCount() >= 1) {
													?>
													<tr>
														<td><img style="width: 24px; height: 24px;" src="/img/slot_low.png"></td>
														<td></td>
														<td>Low Slot Modules</td>
														<td></td>
													</tr>
													<?php
													foreach($low_slots as $low) {
														// Adding low slots to IGB fitting array
														$igb_fitting_array[] = $low['type_id'].';'.$low['module_quantity'];

														$preRequisites = Fitting::checkItemPrerequisites($low['type_id'], $user->getDefaultID());

														if($preRequisites) {
															$prereq_color = 'class="opaque-success"';
														} elseif($preRequisites == 'WARNING') {
															$prereq_color = 'class="opaque-warning"';
															$fitting_prerequsites[$fitting['fittingid']]['warning']++;
														} else {
															$prereq_color = 'class="opaque-danger"';
															$fitting_prerequsites[$fitting['fittingid']]['danger']++;
														}
														?>
														<tr <?php echo $prereq_color; ?>>
															<td style="width: 32px; vertical-align: center"><img style="width: 24px; height: 24px;" src="https://image.eveonline.com/InventoryType/<?php echo $low['type_id']; ?>_32.png"></td>
															<td style="text-align: left; width: 50px"><?php echo $low['module_quantity']; ?>x</td>
															<td style="text-align: left; width: auto"><?php echo $eve->getTypeName($low['type_id']); ?></td>
															<td style="width: 100px">
																<a href="#"><span data-toggle="tooltip" data-placement="top" title="View On Market" class="glyphicon glyphicon-euro"></span></a>
																<a href="#"><span data-toggle="tooltip" data-placement="top" title="Add To Purchase Cart" class="glyphicon glyphicon-shopping-cart"></span></a>
																<a href="#"><span data-toggle="tooltip" data-placement="top" title="View on Evelopedia" class="glyphicon glyphicon-file"></span></a>
																<a href="#"><span data-toggle="tooltip" data-placement="top" title="Show Info" class="glyphicon glyphicon-info-sign"></span></a>
															</td>
														</tr>
														<?php
													}
												}

												// Getting our Rig Slot items first
												$stmt->execute(array($fitting['fittingid'], 'Rig'));
												$rig_slots = $stmt->fetchAll(PDO::FETCH_ASSOC);

												if($stmt->rowCount() >= 1) {
													?>
													<tr>
														<td><img style="width: 24px; height: 24px;" src="/img/slot_rig.png"></td>
														<td></td>
														<td>Ship Rigs</td>
														<td></td>
													</tr>
													<?php
													foreach($rig_slots as $rig) {
														// Adding rigs to IGB fitting array
														$igb_fitting_array[] = $rig['type_id'].';'.$rig['module_quantity'];

														$preRequisites = Fitting::checkItemPrerequisites($low['type_id'], $user->getDefaultID());

														if($preRequisites) {
															$prereq_color = 'class="opaque-success"';
														} elseif($preRequisites == 'WARNING') {
															$prereq_color = 'class="opaque-warning"';
														} else {
															$prereq_color = 'class="opaque-danger"';
														}
														?>
														<tr <?php echo $prereq_color; ?>>
															<td style="width: 32px; vertical-align: center"><img style="width: 24px; height: 24px;" src="https://image.eveonline.com/InventoryType/<?php echo $rig['type_id']; ?>_32.png"></td>
															<td style="text-align: left; width: 50px"><?php echo $rig['module_quantity']; ?>x</td>
															<td style="text-align: left; width: auto"><?php echo $eve->getTypeName($rig['type_id']); ?></td>
															<td style="width: 100px">
																<a href="#"><span data-toggle="tooltip" data-placement="top" title="View On Market" class="glyphicon glyphicon-euro"></span></a>
																<a href="#"><span data-toggle="tooltip" data-placement="top" title="Add To Purchase Cart" class="glyphicon glyphicon-shopping-cart"></span></a>
																<a href="#"><span data-toggle="tooltip" data-placement="top" title="View on Evelopedia" class="glyphicon glyphicon-file"></span></a>
																<a href="#"><span data-toggle="tooltip" data-placement="top" title="Show Info" class="glyphicon glyphicon-info-sign"></span></a>
															</td>
														</tr>
														<?php
													}
												}

												// Getting our Drone / Cargo Slot items first
												$stmt->execute(array($fitting['fittingid'], 'Drone'));
												$drone_slots = $stmt->fetchAll(PDO::FETCH_ASSOC);

												if($stmt->rowCount() >= 1) {
													?>
													<tr>
														<td><img style="width: 24px; height: 24px;" src="/img/slot_cargo.jpg"></td>
														<td></td>
														<td>Drone Bay and Cargo Hold</td>
														<td></td>
													</tr>
													<?php
													foreach($drone_slots as $drone) {
														// Adding drones and cargo to IGB Fitting Array
														$igb_fitting_array[] = $drone['type_id'].';'.$drone['module_quantity'];

														$preRequisites = Fitting::checkItemPrerequisites($drone['type_id'], $user->getDefaultID());

														if($preRequisites) {
															$prereq_color = 'class="opaque-success"';
														} elseif($preRequisites == 'WARNING') {
															$prereq_color = 'class="opaque-warning"';
														} else {
															$prereq_color = 'class="opaque-danger"';
														}
														?>
														<tr <?php echo $prereq_color; ?>>
															<td style="width: 32px; vertical-align: center"><img style="width: 24px; height: 24px;" src="https://image.eveonline.com/InventoryType/<?php echo $drone['type_id']; ?>_32.png"></td>
															<td style="text-align: left; width: 50px"><?php echo $drone['module_quantity']; ?>x</td>
															<td style="text-align: left; width: auto"><?php echo $eve->getTypeName($drone['type_id']); ?></td>
															<td style="width: 100px">
																<a href="#"><span data-toggle="tooltip" data-placement="top" title="View On Market" class="glyphicon glyphicon-euro"></span></a>
																<a href="#"><span data-toggle="tooltip" data-placement="top" title="Add To Purchase Cart" class="glyphicon glyphicon-shopping-cart"></span></a>
																<a href="#"><span data-toggle="tooltip" data-placement="top" title="View on Evelopedia" class="glyphicon glyphicon-file"></span></a>
																<a href="#"><span data-toggle="tooltip" data-placement="top" title="Show Info" class="glyphicon glyphicon-info-sign"></span></a>
															</td>
														</tr>
														<?php
													}
												}
												?>
											</tbody>
										</table>
									</div>
									<div role="tabpanel" class="tab-pane" id="details<?php echo $fitting['fittingid']; ?>" style="text-align: left">
										<p><span style="font-style: italic">Last Updated:</span> Never</p>
										<p><span style="font-style: italic">Created By:</span> Unknown</p>
										<p><span style="font-style: italic">Fitting Notes:</span> <?php echo $fitting['fitting_notes']; ?></p>
									</div>
									<div role="tabpanel" class="tab-pane" id="skills<?php echo $fitting['fittingid']; ?>">
										Minimum Required Skills
									</div>
									<?php
									if($user->getDirectorAccess()) {
										?>
										<div role="tabpanel" class="tab-pane" id="edit<?php echo $fitting['fittingid']; ?>">
											<div class="row">




												<div class="col-md-4 col-sm-6">
													<h3 class="eve-text" style="text-align: center">Update Fitting</h3>
													<form method="post" action="/doctrines/view/<?php echo $request['value']; ?>/updatefit/">
														<textarea  class="form-control" style="width: 100%" rows="8" type="text" name="fitting_raw" placeholder="Add an EFT or in-game Fitting here."></textarea>
														<input class="btn btn-success eve-text" style="margin-top: 10px; font-size: 125%" type="submit" value="Update Fitting">
													</form>
												</div>
												<div class="col-md-4 col-sm-6" style="text-align: center">
													<h3 class="eve-text" style="text-align: center">Edit Fitting Information</h3>
													<form method="post" action="/doctrines/view/<?php echo $doctrine['doctrineid']; ?>/editfit/">
														<input type="hidden" name="fitting_id" value="<?php echo $fitting['fittingid']; ?>">
														<formfield>
															<label for="fitting_name">Fitting Name:</label><br />
															<input class="form-control" type="text" name="fitting_name" value="<?php echo $fitting['fitting_name']; ?>">
														</formfield>
														<formfield>
															<label for="fitting_priority">Priority: </label>
															<select class="form-control" id="fitting_priority" style="margin-bottom: 8px; width: 100%; margin-left: auto; margin-right: auto;" name="fitting_priority">
																<option style="background-color: rgb(23,23,23)" value="3">Normal</option>
																<option style="background-color: rgb(23,23,23)" value="1">Lowest</option>
																<option style="background-color: rgb(23,23,23)" value="2">Low</option>
																<option style="background-color: rgb(23,23,23)" value="4">High</option>
																<option style="background-color: rgb(23,23,23)" value="5">Highest</option>                              
															</select>
														</formfield>
														<formfield>
															<label for="fitting_role">Role: </label>
															<select class="form-control" id="fitting_role" style="margin-bottom: 8px; width: 100%; margin-left: auto; margin-right: auto;" name="fitting_role">
																<option style="background-color: rgb(23,23,23)" value="Mainline">Mainline Ship</option>
																<option style="background-color: rgb(23,23,23)" value="DPS">DPS</option>
																<option style="background-color: rgb(23,23,23)" value="Logistics">Logistics</option>
																<option style="background-color: rgb(23,23,23)" value="Scout">Scout</option>
																<option style="background-color: rgb(23,23,23)" value="Tackle">Tackle</option>
																<option style="background-color: rgb(23,23,23)" value="Specialty">Specialty</option>
															</select>
														</formfield>
														<formfield>
															<label for="fitting_name">Fitting Notes and Information:</label> <br />
															<textarea class="form-control" style="width: 100%" rows="8" type="text" name="fitting_notes" placeholder="Fitting Notes and Details"><?php echo $fitting['fitting_notes']; ?></textarea>
														</formfield>
														<formfield>
															<input style="text-align: center; margin-top: 10px; font-size: 125%" type="submit" value="Edit Fitting Information" class="btn btn-primary eve-text">
														</formfield>
													</form>
												</div>
												<div class="col-md-4 col-sm-6">
													<div class="row">
														<h3 class="eve-text" style="text-align: center">Delete Fitting</h3>
														<form method="post" action="/doctrines/view/<?php echo $doctrine['doctrineid'];?>/deletefit">
															<input type="hidden" name="fitting_id" value="<?php echo $fitting['fittingid']; ?>">
															<input class="btn btn-danger eve-text" style="margin-top: 5px; margin-bottom: 10px; font-size: 125%" type="submit" value="Delete Fitting">
														</form>
													</div>
												</div>





											</div>
										</div>
										<?php
									}
									?>
								</div>
	                        </div>
	                        <!-- Modal Footer -->
	                		<div class="modal-footer">
	                			<?php
	                			$igb_fitting_string = implode(":", $igb_fitting_array).'::';
	                			?>
	                			<button class="btn btn-primary" onclick="CCPEVE.showFitting('<?php echo $igb_fitting_string; ?>')">Open Fitting In EVE</button>
	                  			<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
	                		</div>
	              		</div>
	           	</div>
	        </div> 
			<?php
		}
	}

	// This is the "Add Fitting" Modal
	if($user->getDirectorAccess()) {
		?>
		<div class="modal fade" id="addFittingModal" tabindex="-1" role="dialog" aria-labelledby="addFittingModalLabel" aria-hidden="true" >
			<div class="modal-dialog">
				<form action="/doctrines/view/<?php echo $request['value']; ?>/addfit/" method="post">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
							<h4 class="modal-title" id="addFittingModalLabel">Add a New Fitting to <?php echo $doctrine['doctrine_name']; ?> </h4>
						</div>
						<div class="modal-body" style="text-align: center">
							<p>Select the Fitting Priority and Role from the choices below, and copy/paste a fitting from In-Game or from Eve Fitting Tool.</p>
							<label for="fitting_priority">Priority: </label>
							<select class="form-control" id="fitting_priority" style="margin-bottom: 8px; width: 25%; margin-left: auto; margin-right: auto;" name="fitting_priority">
								<option style="background-color: rgb(23,23,23)" value="3">Normal</option>
								<option style="background-color: rgb(23,23,23)" value="1">Lowest</option>
								<option style="background-color: rgb(23,23,23)" value="2">Low</option>
								<option style="background-color: rgb(23,23,23)" value="4">High</option>
								<option style="background-color: rgb(23,23,23)" value="5">Highest</option>                              
							</select>
							<br />
							<label for="fitting_owner">Fitting Used By:</label>
							<select  class="form-control" name="fitting_owner" style="margin-bottom: 8px; width: 25%; margin-left: auto; margin-right: auto;">
								<option style="background-color: rgb(23,23,23)" value="group"><?php $settings->getGroupTicker(); ?></option>
								<option style="background-color: rgb(23,23,23)" value="LAWN Alliance">LAWN Alliance</option>
								<option style="background-color: rgb(23,23,23)" value="Imperium Coalition">Imperium Coalition</option>
							</select>
							<br />
							<label for="fitting_role">Role: </label>
							<select class="form-control" id="fitting_role" style="margin-bottom: 8px; width: 25%; margin-left: auto; margin-right: auto;" name="fitting_role">
								<option style="background-color: rgb(23,23,23)" value="Mainline">Mainline Ship</option>
								<option style="background-color: rgb(23,23,23)" value="DPS">DPS</option>
								<option style="background-color: rgb(23,23,23)" value="Logistics">Logistics</option>
								<option style="background-color: rgb(23,23,23)" value="Scout">Scout</option>
								<option style="background-color: rgb(23,23,23)" value="Tackle">Tackle</option>
								<option style="background-color: rgb(23,23,23)" value="Specialty">Specialty</option>
							</select>
							<br />
							<textarea class="form-control" style="width: 100%" rows="8" type="text" name="fitting_raw" placeholder="Raw Fitting from In-Game or EFT"></textarea>
							<br />
							<textarea class="form-control" style="width: 100%" rows="8" type="text" name="fitting_notes" placeholder="Fitting Notes and Details"></textarea>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
							<input type="submit" name="newfit" value="Submit" class="btn btn-success" />
						</div>
					</div>
				</form>
			</div>
		</div>
            
		<?php
	}
	// This is the main page section
	?>
	<div class="opaque-container" role="tablist" aria-multiselectable="true">

	    <div class="row" style="width: 100%; margin-top: 20px; margin-bottom: 20px">
			<div class="col-md-12 opaque-section" style="padding: 0px">
				<div class="row box-title-section">
					<div class="row">
						<h1 class="eve-text" style="margin-top: 10px; text-align: center; font-size: 200%; font-weight: 700"><a class="box-title-link" style="text-decoration: none" href="/doctrines/">Fleet Doctrines</a> > <?php echo $doctrine['doctrine_name']; ?></h1>
					</div>
					<?php 
					if($user->getDirectorAccess()) { 
						if($request['value_2'] == 'editmode') {
							?>
							<div class="row">
								<div class="col-md-4 col-sm-12" style="text-align: center; padding-top: 10px; padding-bottom: 20px">
									<button type="button" class="btn btn-success eve-text" data-toggle="modal" data-target="#addFittingModal" style="text-align: center; font-size: 125%">
			                      		Add New Fitting
			                    	</button>
		                    	</div>
								<div class="col-md-4 col-sm-12" style="text-align: center; padding-top: 10px; padding-bottom: 20px">
									<form method="post" action="/doctrines/view/<?php echo $doctrine['doctrineid']; ?>/editdoctrine/">
										<input type="hidden" name="doctrine_id" value="<?php echo $doctrine['doctrineid']; ?>">
										<formfield>
											<label for="doctrine_name">Doctrine Name:</label>
											<input class="form-control" type="text" value="<?php echo $doctrine['doctrine_name']; ?>" name="doctrine_name" id="doctrine_name">
										</formfield>
										<formfield>
											<label for="doctrine_use">Doctrine Type:</label>
											<input class="form-control" type="text" value="<?php echo $doctrine['doctrine_use']; ?>" name="doctrine_use" id="doctrine_use">
										</formfield>
										<formfield>
											<label for="doctrine_requirement">Doctrine Requirement:</label>
											<input class="form-control" type="text" value="<?php echo $doctrine['doctrine_requirement']; ?>" name="doctrine_requirement" id="doctrine_requirement">
										</formfield>
										<formfield>
											<label for="doctrine_owner">Doctrine User:</label>
											<input class="form-control" type="text" value="<?php echo $doctrine['doctrine_owner']; ?>" name="doctrine_owner" id="doctrine_owner">
										</formfield>
										<formfield>
											<label for="doctrine_staging">Doctrine Staging System:</label>
											<input class="form-control" type="text" value="<?php echo $doctrine['doctrine_staging']; ?>" name="doctrine_staging" id="doctrine_staging">
										</formfield>
										<input type="submit" value="Update Doctrine" class="btn btn-primary eve-text" style="font-size: 125%; margin-top: 10px"><br />

										<a href="/doctrines/view/<?php echo $doctrine['doctrineid']; ?>" class="eve-text btn btn-warning" style="font-size: 150%; margin-top: 10px">Disable Edit Mode</a>
									</form>
		                    	</div>

								<div class="col-md-4 col-sm-12" style="text-align: center; padding-top: 10px; padding-bottom: 20px">
									<a href="/doctrines/delete/<?php echo $doctrine['doctrineid']; ?>" class="btn btn-danger eve-text" style="text-align: center; font-size: 125%">Delete Doctrine</a>
		                    	</div>
	                    	</div>
	                    	<?php 
                    	} else {
                    		?>
							<div class="row">
								<div class="col-md-offset-3 col-md-6" style="text-align: center; padding-top: 10px; padding-bottom: 20px">
									<a href="/doctrines/view/<?php echo $doctrine['doctrineid']; ?>/editmode/" class="btn btn-primary eve-text" style="text-align: center; font-size: 125%">
			                      		Enable Doctrine Edit Mode
			                    	</a>
			                    </div>
			                </div>
                    		<?php
                    	}
                    } 
                    ?>
				</div>
				<div>
					<div class="col-md-12">
						<div class="row">
							<div class="col-md-6 col-sm-12">
								<div class="row box-title-section">
									<h1 class="eve-text" style="margin-top: 10px; text-align: center; font-size: 200%; font-weight: 700">Mainline Ship</h1>
								</div>
								<div>
									<table class="table table-striped">
										<tbody>
											<tr>
												<th></th>
												<th style="text-align: center">Ship</th>
												<th style="text-align: center">Priority</th>
												<th style="text-align: center">Estimated Price</th>
												<th style="text-align: center"></th>
											</tr>
											<?php
											if(count($fittings['mainline']) >= 1) {
												foreach($fittings['mainline'] as $fitting) {
													if($fitting_prerequsites[$fitting['fittingid']]['warning'] >= 1) {
														$prereq_color = 'class="opaque-warning"';
													} elseif($fitting_prerequsites[$fitting['fittingid']]['danger'] >= 1) {
														$prereq_color = 'class="opaque-danger"';
													} else {
														$prereq_color = 'class="opaque-success"';
													}
													?>
													<tr <?php echo $prereq_color; ?>>
														<td><img style="margin-right: 5px" src="https://image.eveonline.com/InventoryType/<?php echo $fitting['fitting_ship'];?>_32.png"><?php echo $fitting['fitting_name']; ?></td>
														<td style="text-align: center"><?php echo $eve->getTypeName($fitting['fitting_ship']);?></td>
														<td style="text-align: center"><?php echo $fitting['fitting_priority'];?></td>
														<td style="text-align: center; font-style: italic"><?php echo number_format($fitting['fitting_value']); ?> ISK</td>
														<td style="text-align: center">
															<button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#viewFitting<?php echo $fitting['fittingid']; ?>" style="float:right; margin-left: 5px; margin-right: 5px">View Fitting</button>
														</td>
													</tr>
												<?php
												}
											}
											?>
										</tbody>
									</table>
								</div>
							</div>
							<div class="col-md-6 col-sm-12">
								<div class="row box-title-section">
									<h1 class="eve-text" style="margin-top: 10px; text-align: center; font-size: 200%; font-weight: 700">Logistics</h1>
								</div>
								<div>
									<table class="table table-striped">
										<tbody>
											<tr>
												<th></th>
												<th style="text-align: center">Ship</th>
												<th style="text-align: center">Priority</th>
												<th style="text-align: center">Estimated Price</th>
												<th style="text-align: center"></th>
											</tr>
											<?php
											if(count($fittings['logistics']) >= 1) {
												foreach($fittings['logistics'] as $fitting) {
													if($fitting_prerequsites[$fitting['fittingid']]['warning'] >= 1) {
														$prereq_color = 'class="opaque-warning"';
													} elseif($fitting_prerequsites[$fitting['fittingid']]['danger'] >= 1) {
														$prereq_color = 'class="opaque-danger"';
													} else {
														$prereq_color = 'class="opaque-success"';
													}
													?>
													<tr <?php echo $prereq_color; ?>>
														<td><img style="margin-right: 5px" src="https://image.eveonline.com/InventoryType/<?php echo $fitting['fitting_ship'];?>_32.png"><?php echo $fitting['fitting_name']; ?></td>
														<td style="text-align: center"><?php echo $eve->getTypeName($fitting['fitting_ship']);?></td>
														<td style="text-align: center"><?php echo $fitting['fitting_priority'];?></td>
														<td style="text-align: center; font-style: italic"><?php echo number_format($fitting['fitting_value']); ?> ISK</td>
														<td style="text-align: center">
															<button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#viewFitting<?php echo $fitting['fittingid']; ?>" style="float:right; margin-left: 5px; margin-right: 5px">View Fitting</button>
														</td>
													</tr>
												<?php
												}
											}
											?>
										</tbody>
									</table>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-6 col-sm-12">
								<div class="row box-title-section">
									<h1 class="eve-text" style="margin-top: 10px; text-align: center; font-size: 200%; font-weight: 700">DPS</h1>
								</div>
								<div>
									<table class="table table-striped">
										<tbody>
											<tr>
												<th></th>
												<th style="text-align: center">Ship</th>
												<th style="text-align: center">Priority</th>
												<th style="text-align: center">Estimated Price</th>
												<th style="text-align: center"></th>
											</tr>
											<?php

											if(count($fittings['dps']) >= 1) {
												foreach($fittings['dps'] as $fitting) {
													if($fitting_prerequsites[$fitting['fittingid']]['warning'] >= 1) {
														$prereq_color = 'class="opaque-warning"';
													} elseif($fitting_prerequsites[$fitting['fittingid']]['danger'] >= 1) {
														$prereq_color = 'class="opaque-danger"';
													} else {
														$prereq_color = 'class="opaque-success"';
													}
													?>
													<tr <?php echo $prereq_color; ?>>
														<td><img style="margin-right: 5px" src="https://image.eveonline.com/InventoryType/<?php echo $fitting['fitting_ship'];?>_32.png"><?php echo $fitting['fitting_name']; ?></td>
														<td style="text-align: center"><?php echo $eve->getTypeName($fitting['fitting_ship']);?></td>
														<td style="text-align: center"><?php echo $fitting['fitting_priority'];?></td>
														<td style="text-align: center; font-style: italic"><?php echo number_format($fitting['fitting_value']); ?> ISK</td>
														<td style="text-align: center">
															<button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#viewFitting<?php echo $fitting['fittingid']; ?>" style="float:right; margin-left: 5px; margin-right: 5px">View Fitting</button>
														</td>
													</tr>
												<?php
												}
											}
											?>
										</tbody>
									</table>
								</div>
							</div>
							<div class="col-md-6 col-sm-12">
								<div class="row box-title-section">
									<h1 class="eve-text" style="margin-top: 10px; text-align: center; font-size: 200%; font-weight: 700">Tackle & Specialty</h1>
								</div>
								<div>
									<table class="table table-striped">
										<tbody>
											<tr>
												<th></th>
												<th style="text-align: center">Ship</th>
												<th style="text-align: center">Priority</th>
												<th style="text-align: center">Estimated Price</th>
												<th style="text-align: center"></th>
											</tr>
											<?php


											if(count($fittings['other']) >= 1) {
												foreach($fittings['other'] as $fitting) {
													if($fitting_prerequsites[$fitting['fittingid']]['warning'] >= 1) {
														$prereq_color = 'class="opaque-warning"';
													} elseif($fitting_prerequsites[$fitting['fittingid']]['danger'] >= 1) {
														$prereq_color = 'class="opaque-danger"';
													} else {
														$prereq_color = 'class="opaque-success"';
													}
													?>
													<tr <?php echo $prereq_color; ?>>
														<td><img style="margin-right: 5px" src="https://image.eveonline.com/InventoryType/<?php echo $fitting['fitting_ship'];?>_32.png"><?php echo $fitting['fitting_name']; ?></td>
														<td style="text-align: center"><?php echo $eve->getTypeName($fitting['fitting_ship']);?></td>
														<td style="text-align: center"><?php echo $fitting['fitting_priority'];?></td>
														<td style="text-align: center; font-style: italic"><?php echo number_format($fitting['fitting_value']); ?> ISK</td>
														<td style="text-align: center">
															<button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#viewFitting<?php echo $fitting['fittingid']; ?>" style="float:right; margin-left: 5px; margin-right: 5px">View Fitting</button>
														</td>
													</tr>
												<?php
												}
											}
											?>
										</tbody>
									</table>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
}
require_once('includes/footer.php');
