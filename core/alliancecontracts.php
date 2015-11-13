<?php
require_once('includes/header.php');

$doctrine_array = [ "AHAC Fleet" => '#000099',
					'Ferox Fleet' => '#003399',
					'Hurricane Fleet' => '#0055bb',
				    "Caracal Fleet" => '#9900ff', 
				    "Harpy Fleet" => '#9933ff', 
				    "Svipul Fleet" => '#6600ff', 
				    "Moa Fleet" => '#6633ff',  
				    "Legion Fleet" => '#006600',
				    'Dominix Fleet' => '#336600',
				    'Tengu Fleet' => '#009900',
				    'Tempest Fleet' => '#006633',
				    'Special Snowflake' => '#330099'];

// Getting the contracts from our Database
$stmt = $db->prepare('SELECT * FROM alliance_contracts WHERE end_date >= ? ORDER BY doctrine,ship,price,end_date ASC');
$stmt->execute(array(time()));
$contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="opaque-container">

    <div class="row" style="width: 100%; margin-top: 20px; margin-bottom: 20px">
		<div class="col-md-12 opaque-section" style="padding: 0px">
			<div class="row box-title-section">
				<h1 style="text-align: center">LAWN Alliance Contract Tracker</h1>
				<h4 style="text-align: center">Please ensure your contract descriptions include a [Doctrine Name - Ship Name] tag!<h4>
			</div>
			<div class="row" style="padding-bottom: 25px; padding-top: 10px">
				<div class="col-md-4 col-sm-12">
						<div class="row box-title-section"><h3>Strategic Doctrines</h3></div>
						<?php $stmt = $db->prepare('SELECT * FROM alliance_contracts WHERE doctrine = ?'); ?>
						Dominix Fleet: <?php $stmt->execute(array('Dominix Fleet')); echo $stmt->rowCount(); ?> total contracts<br />
						Tengu Fleet: <?php $stmt->execute(array('Tengu Fleet')); echo $stmt->rowCount(); ?> total contracts<br />
						Tempest Fleet: <?php $stmt->execute(array('Tempest Fleet')); echo $stmt->rowCount(); ?> total contracts<br />
						Legion Fleet: <?php $stmt->execute(array('Legion Fleet')); echo $stmt->rowCount(); ?> total contracts<br />
				</div>
				<div class="col-md-4 col-sm-12">
						<div class="row box-title-section"><h3>Mid-Level Doctrines</h3></div>
						AHAC Fleet: <?php $stmt->execute(array('AHAC Fleet')); echo $stmt->rowCount(); ?> total contracts<br />
						Ferox Fleet: <?php $stmt->execute(array('Ferox Fleet')); echo $stmt->rowCount(); ?> total contracts<br />
						Hurricane Fleet: <?php $stmt->execute(array('Hurricane Fleet')); echo $stmt->rowCount(); ?> total contracts<br />
				</div>
				<div class="col-md-4 col-sm-12">
					<div class="row box-title-section"><h3>Skirmish Doctrines</h3></div>
						Svipul Fleet: <?php $stmt->execute(array('Svipul Fleet')); echo $stmt->rowCount(); ?> total contracts<br />
						Caracal Fleet: <?php $stmt->execute(array('Caracal Fleet')); echo $stmt->rowCount(); ?> total contracts<br />
						Moa Fleet: <?php $stmt->execute(array('Moa Fleet')); echo $stmt->rowCount(); ?> total contracts<br />
						Harpy Fleet: <?php $stmt->execute(array('Harpy Fleet')); echo $stmt->rowCount(); ?> total contracts<br />
				</div>			
			</div>
			<div class="row" style="padding-left: 10px; padding-right: 10px">
				<table class="table table-striped">
					<thead>
						<tr>
							<th style="text-align: center">Issuing Player / Corporation</th>
							<th style="text-align: center">Contract Title</th>
							<th style="text-align: center">Contract Price</th>
							<th style="text-align: center">Doctrine</th>
							<th style="text-align: center">Ship</th>
							<th style="text-align: center">Fitting Verification</th>
							<th style="text-align: center">Expiration Date</th>
						</tr>
					</thead>
					<tbody>
						<?php
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
											$doctrine_name = '<span class="label label-success" style="font-size: 85%; background-color: '.$doctrine_array[trim($contract['doctrine'])].'">'.$contract['doctrine'].'</label>';

											switch($ship['fitting_role']):
												case 'Logistics':
													$fitting_color = 'success';
													break;
												case 'Mainline':
													$fitting_color = 'primary';
													break;
												default:
													$fitting_color = 'warning';
													break;
											endswitch;

											$stmt = $db->prepare('SELECT * FROM doctrines_fittingmods WHERE fittingid = ?');
											$stmt->execute(array($ship['fittingid']));
											$fitting_mods = $stmt->fetchAll(PDO::FETCH_ASSOC);

											$fitting_value = $ship['fitting_value'];

											$price_percentage = number_format(($contract['price']/$fitting_value)*100);

											if($contract['price'] > ($fitting_value*1.50)) {
												$price_color = 'red';
												$price_tooltip = 'Current Jita value is '.number_format($fitting_value).'. Markup of '.$price_percentage.'%';
											} else {
												$price_color = '#01b43a';
												$price_tooltip = 'Current Jita value is '.number_format($fitting_value).'. Markup of '.$price_percentage.'%';
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
													if($mod['module_slot'] == 'Drone') {
														$fitting_warnings++;
													} else {
														$fitting_failures++;
													}
													$difference = $mod['module_quantity'] - $contract_quantity;
													$fitting_tooltip[] = $difference.' '.$eve->getTypeName($mod['type_id']);
												}
											}

											if($fitting_failures >= 1) {
												$fitting_parsed_tooltip = implode(', ', $fitting_tooltip);
												$fitting_verification = '<button class="btn btn-danger btn-sm" data-toggle="tooltip" data-placement="top" title="Missing: '.$fitting_parsed_tooltip.'">Fitting Incorrect</button>';
											} elseif($fitting_warnings >= 1) {
												$fitting_parsed_tooltip = implode(', ', $fitting_tooltip);
												$fitting_verification = '<button class="btn btn-warning btn-sm" data-toggle="tooltip" data-placement="top" title="Missing: '.$fitting_parsed_tooltip.'">Missing Some Ammo/Cargo</button>';							
											} else {
												$fitting_verification = '<button class="btn btn-success btn-sm" onclick="CCPEVE.showContract(30000226,'.$contract['contractID'].')">Verfied SRPable Fitting</button>';
											}

											$fitting_name = '<span class="label label-'.$fitting_color.'" style="font-size: 85%">'.$ship['fitting_name'].'</label>';
										}
									}
								}
							}
							?>
							<tr>
								<td style="text-align: center"><?php echo $contract['issuerName']; ?></td>
								<td style="text-align: center"><?php echo $contract['title']; ?></td>
								<td style="text-align: center; color: <?php echo $price_color; ?>"><span data-toggle="tooltip" data-placement="top" title="<?php echo $price_tooltip; ?>"><?php echo number_format($contract['price']); ?> ISK</span></td>
								<td style="text-align: center"><?php echo $doctrine_name; ?></td>
								<td style="text-align: center"><?php echo $fitting_name; ?></td>
								<td style="text-align: center"><?php echo $fitting_verification; ?></td>
								<td style="text-align: center"><?php echo date('Y-m-d H:i', $contract['end_date']); ?></td>
							</tr>
							<?php
						}
						?>
					</tbody>
				</table>
			</div>
		</div>	
    </div>

</div>
<?php
require_once('includes/footer.php');
