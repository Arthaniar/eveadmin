<?php
require_once('includes/header.php');

$stmt = $db->prepare('SELECT * FROM doctrines WHERE gid = 1 ORDER BY FIELD(doctrine_use, "Strategic Doctrine", "Mid-Tier Doctrine", "Skirmish Doctrine")');
$stmt->execute(array());
$doctrines = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Getting the contracts from our Database
$stmt = $db->prepare('SELECT * FROM alliance_contracts WHERE end_date >= ? ORDER BY doctrine,ship,price,end_date ASC');
$stmt->execute(array(time()));
$contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="opaque-container" role="tablist" aria-multiselectable="true">

    <div class="row" style="width: 100%; margin-top: 20px; margin-bottom: 20px">
		<div class="col-md-12 opaque-section" style="padding: 0px">
			<div class="row box-title-section">
				<h1 class="eve-text" style="text-align: center; font-size: 250%; font-weight: 700">LAWN Alliance Doctrines</h1>
			</div>
			<div class="row">
				<h3 class="eve-text" style="text-align: center;">Get Off My Lawn and The Imperium Doctrines and Contract Listings</h3>
			</div>
		</div>
	</div>
</div>

<?php
foreach($doctrines as $doctrine) {
	$stmt = $db->prepare('SELECT * FROM doctrines_fits WHERE doctrineid = ? ORDER BY FIELD(fitting_role, "Mainline", "Logistics", "DPS", "Tackle", "Specialty"), fitting_priority DESC');
	$stmt->execute(array($doctrine['doctrineid']));
	$fittings = $stmt->fetchAll(PDO::FETCH_ASSOC);

	// Getting the contracts from our Database
	$stmt_contracts = $db->prepare('SELECT * FROM alliance_contracts WHERE end_date >= ? AND doctrine = ? AND ship = ? ORDER BY price,end_date ASC');

	?>
	<div class="opaque-container panel-group" id="<?php echo $doctrine['doctrineid']; ?>" role="tablist" aria-multiselectable="true">
		<div class="row">
			<div class="col-md-12 opaque-section" style="margin-bottom: 15px">
				<div class="row box-title-section" style="margin-bottom: 10px" role="tab" id="doctrine<?php echo $doctrine['doctrineid']; ?>Heading">
					<a class="box-title-link" style="text-decoration: none" role="button" data-toggle="collapse" data-parent="doctrine<?php echo $doctrine['doctrineid']; ?>" href="#collapsedoctrine<?php echo $doctrine['doctrineid']; ?>" aria-expanded="true" aria-controls="collapsedoctrine<?php echo $doctrine['doctrineid']; ?>">
						<h2 class="eve-text" style="margin-top: 0px; text-align: center; font-size: 200%; font-weight: 700"><?php echo $doctrine['doctrine_name']; ?> Doctrine</h2>
					</a>
				</div>
				<div id="collapsedoctrine<?php echo $doctrine['doctrineid']; ?>" class="panel-collapse collapse" role="tabpanel" aria-labelledby="doctrine<?php echo $doctrine['doctrineid']; ?>Heading">
					<?php
					$i = 1;

					$fittingsCount = count($fittings);

					if($fittingsCount >= 1) {

						foreach($fittings as $fitting) {
							if($i % 2 == 1) {
								?><div class="row"><?php
							}
							?>
							<!-- Beginning of Modal Window -->
							<div class="modal fade" id="fitting<?php echo $fitting['fittingid']; ?>Modal" tabindex="-1" role="dialog" aria-labelledby="fittingLabel<?php echo $fitting['fittingid']; ?>Modal" aria-hidden="true" >
								<div class="modal-dialog">
					              		<div class="modal-content">
					              			<!-- Modal Header -->
					                		<div class="modal-header">
					                  			<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					                  				<span aria-hidden="true">&times;</span>
					                  			</button>
					                  			<h4 class="modal-title" id="fittingLabel<?php echo $fitting['fittingid']; ?>Modal"><?php echo $doctrine['doctrine_name'].' - '.$fitting['fitting_name']; ?> EFT Fitting Block</h4>
					                		</div>
					                		<!-- Modal Body -->
					                		<?php
					                		$stmt = $db->prepare('SELECT type_id,module_quantity FROM doctrines_fittingmods WHERE fittingid = ? AND module_slot = ?');

					                		// Getting the low slots
					                		$stmt->execute(array($fitting['fittingid'], 'Low'));
					                		$low_slot = $stmt->fetchAll(PDO::FETCH_ASSOC);

					                		// Getting the mid slots
					                		$stmt->execute(array($fitting['fittingid'], 'Mid'));
					                		$mid_slot = $stmt->fetchAll(PDO::FETCH_ASSOC);

					                		// Getting the high slots
					                		$stmt->execute(array($fitting['fittingid'], 'High'));
					                		$high_slot = $stmt->fetchAll(PDO::FETCH_ASSOC);

					                		// Getting the rigs
					                		$stmt->execute(array($fitting['fittingid'], 'Rig'));
					                		$rig_slot = $stmt->fetchAll(PDO::FETCH_ASSOC);

					                		// Getting the subsystems (if present)
					                		$stmt->execute(array($fitting['fittingid'], 'Subsystem'));
					                		$subsystem_slot = $stmt->fetchAll(PDO::FETCH_ASSOC);

					                		// Getting the drones and cargo
					                		$stmt->execute(array($fitting['fittingid'], 'Drone'));
					                		$drone_slot = $stmt->fetchAll(PDO::FETCH_ASSOC);
					                		?>
					                		<div class="modal-body">
					                		<p>This is a standard EFT formatted out-of-game fitting. This can be used with EFT, Pyfa, EveAdmin, and the in-game Fitting Import function.</p>
					                		<p>
					                			[<?php echo $eve->getTypeName($fitting['fitting_ship']).', '.$fitting['fitting_name']; ?>]<br>
					                			<?php
					                			foreach($low_slot as $module) {
					                				if($module['module_quantity'] > 1) {
					                					$module_i = 1;
					                					while($module_i <= $module['module_quantity']) {
					                						?><span><?php echo $eve->getTypeName($module['type_id']); ?></span><br><?php
					                						$module_i++;
					                					}
					                				} else {
					                					?><span><?php echo $eve->getTypeName($module['type_id']);?></span><br><?php
					                				
					                				}
					                			}
					                			echo "<br>";
			
					                			foreach($mid_slot as $module) {
					                				if($module['module_quantity'] > 1) {
					                					$module_i = 1;
					                					while($module_i <= $module['module_quantity']) {
					                						?><span><?php echo $eve->getTypeName($module['type_id']); ?></span><br><?php
					                						$module_i++;
					                					}
					                				} else {
					                					?><span><?php echo $eve->getTypeName($module['type_id']);?></span><br><?php
					                				
					                				}
					                			}
					                			echo "<br>";
			
					                			foreach($high_slot as $module) {
					                				if($module['module_quantity'] > 1) {
					                					$module_i = 1;
					                					while($module_i <= $module['module_quantity']) {
					                						?><span><?php echo $eve->getTypeName($module['type_id']); ?></span><br><?php
					                						$module_i++;
					                					}
					                				} else {
					                					?><span><?php echo $eve->getTypeName($module['type_id']);?></span><br><?php
					                				
					                				}
					                			}
					                			echo "<br>";
			
					                			foreach($rig_slot as $module) {
					                				if($module['module_quantity'] > 1) {
					                					$module_i = 1;
					                					while($module_i <= $module['module_quantity']) {
					                						?><span><?php echo $eve->getTypeName($module['type_id']); ?></span><br><?php
					                						$module_i++;
					                					}
					                				} else {
					                					?><span><?php echo $eve->getTypeName($module['type_id']);?></span><br><?php
					                				
					                				}
					                			}
					                			echo "<br>";
			
					                			if(count($subsystem_slot) >= 1) {
					                				foreach($subsystem_slot as $module) {
					                					if($module['module_quantity'] > 1) {
					                						$module_i = 1;
					                						while($module_i <= $module['module_quantity']) {
					                							?><span><?php echo $eve->getTypeName($module['type_id']); ?></span><br><?php
					                							$module_i++;
					                						}
					                					} else {
					                						?><span><?php echo $eve->getTypeName($module['type_id']);?></span><br><?php
					                					
					                					}
					                				}
					                				echo "<br>";
					                			}
			
					                			if(count($drone_slot) >= 1) {
					                				foreach($drone_slot as $module) {
					                					?><span><?php echo $eve->getTypeName($module['type_id']).' x'.$module['module_quantity'];?></span><br><?php
					                				}
					                				echo "<br>";
					                			}
					                			?>
						                		</p>
					                        </div>
					                        <!-- Modal Footer -->
					                		<div class="modal-footer">
					                  			<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
					                		</div>
					              		</div>
					           	</div>
					        </div> 
					        <!-- End of Modal Window -->
					        <?php
							$stmt_contracts->execute(array(time(), $doctrine['doctrine_name'], $fitting['fitting_name']));
							$contracts = $stmt_contracts->fetchAll(PDO::FETCH_ASSOC);
							?>
							<div class="col-md-6 col-sm-12" style="margin-bottom: 15px; padding-left: 0px; padding-right: 0px">
								<div class="opaque-section" style="margin-left: 10px; margin-right: 10px; background-image: none">
									<div class="row box-title-section">
										<h2 style="text-align: center"><img style="margin-right: 5px" src="https://image.eveonline.com/InventoryType/<?php echo $fitting['fitting_ship']; ?>_32.png"><?php echo $fitting['fitting_name']; ?><?php if($user->getLoginStatus()) { echo '<button style="margin-left: 5px" class="btn btn-sm btn-success">Usable!</button>'; } ?></h2>
									</div>
									<div class="row">
										<table class="table table-striped">
<!--
											<tr>
												<th style="text-align: center">Role</th>
												<th class="hidden-column" style="text-align: center">Priority</th>
												<th style="text-align: center">Jita Value</th>
												<th style="text-align: center">Info</th>
											</tr>
											<tr style="text-align: center">
												<td><?php echo $fitting['fitting_role']; ?></td>
												<td class="hidden-column"><?php echo $fitting['fitting_priority']; ?></td>
												<td><?php echo number_format($fitting['fitting_value']); ?></td>
												<td><button class="btn btn-sm btn-primary" style="margin-right: 2px" data-toggle="modal" data-target="#fitting<?php echo $fitting['fittingid']; ?>Modal">EFT Block</button><button class="btn btn-sm btn-primary">In Game Fitting</button></td>
											</tr>-->
											<tr>
												<th style="text-align: center">Contractor</th>
												<th class="hidden-column" style="text-align: center">Location</th>
												<th style="text-align: center">Cost</th>
												<th style="text-align: center">Info</th>
											</tr>
											<?php
											if(count($contracts) >= 1) {
												foreach($contracts as $contract) {

													$price_color = "#f5f5f5";
													$fitting_name = '---';
													$fitting_verification = '---';
													$doctrine_name = '<span class="label label-danger" style="font-size: 85%">Unknown Doctrine</label>';

													if($contract['doctrine'] != 'Unknown') {
														$stmt = $db->prepare('SELECT * FROM doctrines WHERE doctrine_name = ? LIMIT 1');
														$stmt->execute(array($contract['doctrine']));
														$doctrine = $stmt->fetch(PDO::FETCH_ASSOC);

														if(isset($doctrine['doctrine_name'])) {
															$ship_id = $eve->getTypeID($contract['ship']);

															if($ship_id != NULL) {
																$doctrine_id = $doctrine['doctrineid'];
																$stmt = $db->prepare('SELECT * FROM doctrines_fits WHERE fitting_ship = ? AND doctrineid = ? LIMIT 1');

																$stmt->execute(array($ship_id, $doctrine_id));
																$ship = $stmt->fetch(PDO::FETCH_ASSOC);

																if(isset($ship['fitting_name'])) {
																	$stmt = $db->prepare('SELECT * FROM doctrines_fittingmods WHERE fittingid = ?');
																	$stmt->execute(array($ship['fittingid']));
																	$fitting_mods = $stmt->fetchAll(PDO::FETCH_ASSOC);

																	$fitting_value = $ship['fitting_value'];

																	$price_percentage = number_format(($contract['price']/$fitting_value)*100);

																	if($contract['price'] > ($fitting_value*1.50)) {
																		$price_color = 'red';
																		$price_tooltip = 'Markup of '.$price_percentage.'%';
																	} else {
																		$price_color = '#01b43a';
																		$price_tooltip = 'Markup of '.$price_percentage.'%';
																	}

																	$fitting_failures = 0;
																	$fitting_warnings = 0;
																	$fitting_tooltip = array();

																	foreach($fitting_mods as $mod) {
																		$stmt = $db->prepare('SELECT * FROM alliance_contract_items WHERE contractID = ? AND itemID = ?');
																		$stmt->execute(array($contract['contractID'], $mod['type_id']));
																		$item_lookup = $stmt->fetchAll(PDO::FETCH_ASSOC);

																		$contract_quantity = 0;
																		if($stmt->rowCount() >= 1) {
																			foreach($item_lookup as $contract_item) {
																				$contract_quantity += $contract_item['quantity'];
																			}
																		}

																		if($contract_quantity < $mod['module_quantity']) {

																			if(strpos($eve->getTypeName($mod['type_id']), 'True Sansha Armor') !== FALSE) {
																				$armor_alternatives = ["Ammatar Navy",
																									   "Dark Blood",
																									   "Federation Navy",
																									   "Imperial Navy",
																									   "Khanid Navy",
																									   "Shadow Serpentis"];
																				foreach($armor_alternatives as $alt) {
																					$stmt->execute(array($contract['contractID'], $eve->getTypeID(str_replace("True Sansha", $alt, $eve->getTypeName($mod['type_id'])))));
																					$alt_item_lookup = $stmt->fetchAll(PDO::FETCH_ASSOC);

																					if($stmt->rowCount() >= 1) {
																						foreach($alt_item_lookup as $contract_item) {
																							$contract_quantity += $contract_item['quantity'];
																						}
																					}
																				}

																				if($contract_quantity < $mod['module_quantity']) {
																					$fitting_failures++;
																					$difference = $mod['module_quantity'] - $contract_quantity;
																					$fitting_tooltip[] = $difference.' '.$eve->getTypeName($mod['type_id']);

																				}
																			} else {
																				if($mod['module_slot'] == 'Drone') {
																					$fitting_warnings++;
																				} else {
																					$fitting_failures++;
																				}
																				$difference = $mod['module_quantity'] - $contract_quantity;
																				$fitting_tooltip[] = $difference.' '.$eve->getTypeName($mod['type_id']);
																			}
																		}
																	}

																	if($fitting_failures >= 1) {
																		$fitting_parsed_tooltip = implode(', ', $fitting_tooltip);
																		$fitting_verification = '<button class="btn btn-danger btn-sm" onclick="CCPEVE.showContract(30000226,'.$contract['contractID'].')" data-toggle="tooltip" data-placement="top" title="Missing: '.$fitting_parsed_tooltip.'">Fitting Incorrect</button>';
																	} elseif($fitting_warnings >= 1) {
																		$fitting_parsed_tooltip = implode(', ', $fitting_tooltip);
																		$fitting_verification = '<button class="btn btn-warning btn-sm" onclick="CCPEVE.showContract(30000226,'.$contract['contractID'].')" data-toggle="tooltip" data-placement="top" title="Missing: '.$fitting_parsed_tooltip.'">Missing Some Ammo/Cargo</button>';							
																	} else {
																		$fitting_verification = '<button class="btn btn-success btn-sm" onclick="CCPEVE.showContract(30000226,'.$contract['contractID'].')">Verfied SRPable Fitting</button>';
																	}

																}
															}
														}
													}
													?>
														<tr style="text-align: center">
															<td><?php echo $contract['issuerName']; ?></td>
															<td class="hidden-column"><?php echo "FH-TTC"; ?></td>
															<td style="color: <?php echo $price_color; ?>">
																<span data-toggle="tooltip" data-placement="top" title="<?php echo $price_tooltip; ?>">
																	<?php echo number_format($contract['price']); ?>
																</span>
															</td>
															<td><?php echo $fitting_verification; ?></td>
														</tr>
													<?php
												}
											} else {
												?>
												<tr style="text-align: center">
													<td>---</td>
													<td class="hidden-column">No Contracts Available</td>
													<td>---</td>
													<td>---</td>
												</tr>
												<?php
											}
											?>
										</table>
									</div>	
								</div>
							</div>
							<?php
							if($i % 2 == 0 OR $i == $fittingsCount) {
								?></div><?php
							}
							$i++;
						}
					} else {
						?>
						<div class="row box-title-section">
							<h2 style="text-align: center">This Doctrine does not currently have any fits.</h2>
						</div>
						<?php
					}
					?>
				</div>
			</div>
	    </div>
	</div>
	<?php
}
require_once('includes/footer.php');
