<?php
require_once('includes/header.php');

/** Current Purchase Modifiers */
$eveCentralValueType = 'buy';
$buyModifier = 0.9;

/** Eve-Central value fetching */
$eveCentral = new EveCentral($db);

$values = array();

/** Isotope Values */
$values['nitrogen_isotopes'] = $eveCentral->lookupItem('17888', $eveCentralValueType, 'Jita') * $buyModifier;
$values['helium_isotopes'] = $eveCentral->lookupItem('16274', $eveCentralValueType, 'Jita') * $buyModifier;
$values['hydrogen_isotopes'] = $eveCentral->lookupItem('17889', $eveCentralValueType, 'Jita') * $buyModifier;
$values['oxygen_isotopes'] = $eveCentral->lookupItem('17887', $eveCentralValueType, 'Jita') * $buyModifier;

/** Whole Ice */
$values['pristine_white_glaze'] = $eveCentral->lookupItem('17976', $eveCentralValueType, 'Jita') * $buyModifier;
$values['dark_glitter'] = $eveCentral->lookupItem('16267', $eveCentralValueType, 'Jita') * $buyModifier;
$values['glare_crust'] = $eveCentral->lookupItem('16266', $eveCentralValueType, 'Jita') * $buyModifier;
$values['gelidus'] = $eveCentral->lookupItem('16268', $eveCentralValueType, 'Jita') * $buyModifier;
$values['krystallos'] = $eveCentral->lookupItem('16269', $eveCentralValueType, 'Jita') * $buyModifier;

/** Ice Component Values */
$values['heavy_water'] = $eveCentral->lookupItem('16272', $eveCentralValueType, 'Jita') * $buyModifier;
$values['liquid_ozone'] = $eveCentral->lookupItem('16273', $eveCentralValueType, 'Jita') * $buyModifier;
$values['strontium_clathrates'] = $eveCentral->lookupItem('16275', $eveCentralValueType, 'Jita') * $buyModifier;

/** PI Components*/
$values['electrolytes'] = $eveCentral->lookupItem('2390', $eveCentralValueType, 'Jita') * $buyModifier;
$values['water'] = $eveCentral->lookupItem('3645', $eveCentralValueType, 'Jita') * $buyModifier;
$values['precious_metals'] = $eveCentral->lookupItem('2399', $eveCentralValueType, 'Jita') * $buyModifier;
$values['toxic_metals'] = $eveCentral->lookupItem('2400', $eveCentralValueType, 'Jita') * $buyModifier;
$values['chiral_structures'] = $eveCentral->lookupItem('2401', $eveCentralValueType, 'Jita') * $buyModifier;
$values['reactive_metals'] = $eveCentral->lookupItem('2398', $eveCentralValueType, 'Jita') * $buyModifier;
$values['oxygen'] = $eveCentral->lookupItem('3683', $eveCentralValueType, 'Jita') * $buyModifier;
$values['coolant'] = $eveCentral->lookupItem('9832', $eveCentralValueType, 'Jita') * $buyModifier;
$values['mechanical_parts'] = $eveCentral->lookupItem('3689', $eveCentralValueType, 'Jita') * $buyModifier;
$values['enriched_uranium'] = $eveCentral->lookupItem('44', $eveCentralValueType, 'Jita') * $buyModifier;
$values['robotics'] = $eveCentral->lookupItem('9848', $eveCentralValueType, 'Jita') * $buyModifier;
?>
<div class="opaque-container">

    <div class="row" style="width: 100%; margin-top: 20px; margin-bottom: 20px">
		<div class="col-md-12 opaque-section" style="padding: 0px">
			<div class="row box-title-section">
				<h1 style="text-align: center">DOGFT Buyback Service</h1>
			</div>
			<?php showAlerts(); ?>
			<div class="row" style="padding-left: 10px; padding-right: 10px">
			<div class="col-md-6 col-sm-12">
				<div class="row box-title-section">
					<h3 style="text-align: center">Buyback Calculator</h3>
				</div>
				<div class="row">
					<form method="post" action="/buyback/">
						<div class="col-md-6 col-sm-12">
							<h4 class="eve-text">Ice Products</h4>
							<table class="table table-striped">
								<tr>
									<formfield>
										<td>
											<label for="nitrogen_isotopes">Nitrogen Isotopes</label>
										</td>
										<td>
											<input id="nitrogen_isotopes" type="number" name="nitrogen_isotopes" min="0" max="100000000" value="0" class="form-control">
										</td>
									</formfield>
								</tr>
								<tr>
									<formfield>
										<td>
											<label for="hydrogen">Hydrogen Isotopes</label>
										</td>
										<td>
											<input id="hydrogen" type="number" name="hydrogen" min="0" max="100000000" value="0" class="form-control">
										</td>
									</formfield>
								</tr>
								<tr>
									<formfield>
										<td>
											<label for="helium_isotopes">Hydrogen Isotopes</label>
										</td>
										<td>
											<input id="helium_isotopes" type="number" name="helium_isotopes" min="0" max="100000000" value="0" class="form-control">
										</td>
									</formfield>
								</tr>
								<tr>
									<formfield>
										<td>
											<label for="oxygen_isotopes">Oxygen Isotopes</label>
										</td>
										<td>
											<input id="oxygen_isotopes" type="number" name="oxygen_isotopes" min="0" max="100000000" value="0" class="form-control">
										</td>
									</formfield>
								</tr>																			
								<tr>
									<formfield>
										<td>
											<label for="heavy_water">Heavy Water</label>
										</td>
										<td>
											<input id="heavy_water" type="number" name="heavy_water" min="0" max="100000000" value="0" class="form-control">
										</td>
									</formfield>
								</tr>
								<tr>
									<formfield>
										<td>
											<label for="liquid_ozone">Liquid Ozone</label>
										</td>
										<td>
											<input id="liquid_ozone" type="number" name="liquid_ozone" min="0" max="100000000" value="0" class="form-control">
										</td>
									</formfield>
								</tr>
								<tr>
									<formfield>
										<td>
											<label for="strontium_clathrates">Strontium Clathrates</label>
										</td>
										<td>
											<input id="strontium_clathrates" type="number" name="strontium_clathrates" min="0" max="100000000" value="0" class="form-control">
										</td>
									</formfield>
								</tr>
							</table>
							<h4 class="eve-text">Compressed Ice</h4>
							<table class="table table-striped">
								<tr>
									<formfield>
										<td>
											<label for="pristine_white_glaze">Pristine White Glaze</label>
										</td>
										<td>
											<input id="pristine_white_glaze" type="number" name="pristine_white_glaze" min="0" max="100000000" value="0" class="form-control">
										</td>
									</formfield>
								</tr>
								<tr>
									<formfield>
										<td>
											<label for="dark_glitter">Dark Glitter</label>
										</td>
										<td>
											<input id="dark_glitter" type="number" name="dark_glitter" min="0" max="100000000" value="0" class="form-control">
										</td>
									</formfield>
								</tr>
								<tr>
									<formfield>
										<td>
											<label for="gelidus">Gelidus</label>
										</td>
										<td>
											<input id="gelidus" type="number" name="gelidus" min="0" max="100000000" value="0" class="form-control">
										</td>
									</formfield>
								</tr>
								<tr>
									<formfield>
										<td>
											<label for="glare_crust">Glare Crust</label>
										</td>
										<td>
											<input id="glare_crust" type="number" name="glare_crust" min="0" max="100000000" value="0" class="form-control">
										</td>
									</formfield>
								</tr>
								<tr>
									<formfield>
										<td>
											<label for="krystallos">Krystallos</label>
										</td>
										<td>
											<input id="krystallos" type="number" name="krystallos" min="0" max="100000000" value="0" class="form-control">
										</td>
									</formfield>
								</tr>
							</table>

						</div>
						<div class="col-md-6 col-sm-12">
							<h4 class="eve-text">Planetary Interaction Commodities</h4>
							<table class="table table-striped">
								<tr>
									<formfield>
										<td>
											<label for="electrolytes">Electrolytes</label>
										</td>
										<td>
											<input id="electrolytes" type="number" name="electrolytes" min="0" max="100000000" value="0" class="form-control">
										</td>
									</formfield>
								</tr>
								<tr>
									<formfield>
										<td>
											<label for="water">Water</label>
										</td>
										<td>
											<input id="water" type="number" name="water" min="0" max="100000000" value="0" class="form-control">
										</td>
									</formfield>
								</tr>
								<tr>
									<formfield>
										<td>
											<label for="precious_metals">Precious Metals</label>
										</td>
										<td>
											<input id="precious_metals" type="number" name="precious_metals" min="0" max="100000000" value="0" class="form-control">
										</td>
									</formfield>
								</tr>
								<tr>
									<formfield>
										<td>
											<label for="toxic_metals">Toxic Metals</label>
										</td>
										<td>
											<input id="toxic_metals" type="number" name="toxic_metals" min="0" max="100000000" value="0" class="form-control">
										</td>
									</formfield>
								</tr>
								<tr>
									<formfield>
										<td>
											<label for="chiral_structures">Chiral Structures</label>
										</td>
										<td>
											<input id="chiral_structures" type="number" name="chiral_structures" min="0" max="100000000" value="0" class="form-control">
										</td>
									</formfield>
								</tr>
								<tr>
									<formfield>
										<td>
											<label for="reactive_metals">Reactive Metals</label>
										</td>
										<td>
											<input id="reactive_metals" type="number" name="reactive_metals" min="0" max="100000000" value="0" class="form-control">
										</td>
									</formfield>
								</tr>
								<tr>
									<formfield>
										<td>
											<label for="oxygen">Oxygen</label>
										</td>
										<td>
											<input id="oxygen" type="number" name="oxygen" min="0" max="100000000" value="0" class="form-control">
										</td>
									</formfield>
								</tr>
								<tr>
									<formfield>
										<td>
											<label for="coolant">Coolant</label>
										</td>
										<td>
											<input id="coolant" type="number" name="coolant" min="0" max="100000000" value="0" class="form-control">
										</td>
									</formfield>
								</tr>							
								<tr>
									<formfield>
										<td>
											<label for="enriched_uranium">Enriched Uranium</label>
										</td>
										<td>
											<input id="enriched_uranium" type="number" name="enriched_uranium" min="0" max="100000000" value="0" class="form-control">
										</td>
									</formfield>
								</tr>
								<tr>
									<formfield>
										<td>
											<label for="mechanical_parts">Mechanical Parts</label>
										</td>
										<td>
											<input id="mechanical_parts" type="number" name="mechanical_parts" min="0" max="100000000" value="0" class="form-control">
										</td>
									</formfield>
								</tr>
								<tr>
									<formfield>
										<td>
											<label for="robotics">Robotics</label>
										</td>
										<td>
											<input id="robotics" type="number" name="robotics" min="0" max="100000000" value="0" class="form-control">
										</td>
									</formfield>
								</tr>
								<tr>
									<formfield>
										<td></td>
										<td><input type="submit" value="Calculate" name="buyback" class="btn btn-primary"></td>
									</formfield>
								</tr>
							</table>	

							<?php
							if(isset($_POST['buyback'])) {

								$totalCalculatedValue = 0;

								foreach($_POST as $key => $quantity) {
									if(isset($values[$key])) {
										$totalCalculatedValue += ($values[$key]*$quantity);
									}
								}
								?>
								<div class="row" style="margin-top: 20px">
									<h4 class="eve-text">Your Contract</h4>
									<p>Total Value: <span style="color: green"><?php echo number_format($totalCalculatedValue, 2); ?> </span>ISK</p>
									<p>Create To: <a onclick="CCPEVE.showInfo(1808215244)">ltchy Taint</a></p>
									<p>Expiration: 14 days</p>
								</div>
								<?php
							}
							?>

						</div>
					</form>
				</div>

			</div>
			<div class="col-md-6 col-sm-12">
				<div class="row box-title-section">
					<h3 style="text-align: center">Buyback Prices</h3>
					<p>Please note, we will accept items marked as "Unwanted". Unwanted items will be paid at 66% of Jita value which is reflected in the price.</p>
					<p>All other items are currently being paid out at <?php echo  ($buyModifier*100); ?>% of Jita <?php echo $eveCentralValueType; ?> Value</p>
				</div>
				<div class="row">
					<p class="eve-text" style="text-align: center; font-size: 140%">Base Fees</p>	
					<table class="table table-striped" style="text-align: center">
						<tr>
							<th style="text-align: center">Item</th>
							<th style="text-align: center">Purchase Price</th>
							<th style="text-align: center">Amount Required Per Month</th>
						</tr>
						<tr>
							<td>Nitrogen Isotopes</td>
							<td><?php echo number_format($values['nitrogen_isotopes'], 2); ?> ISK</td>
							<td>611,500</td>
						</tr>
						<tr>
							<td>Helium Isotopes</td>
							<td><?php echo number_format($values['helium_isotopes'], 2); ?> ISK</td>
							<td>388,500</td>
						</tr>
						<tr>
							<td>Hydrogen Isotopes</td>
							<td><?php echo number_format($values['hydrogen_isotopes'], 2); ?> ISK</td>
							<td><span style="color: red">Unwanted</span></td>
						</tr>
						<tr>
							<td>Oxygen Isotopes</td>
							<td><?php echo number_format($values['oxygen_isotopes'], 2); ?> ISK</td>
							<td><span style="color: red">Unwanted</span></td>
						</tr>
						<tr>
							<td>Heavy Water</td>
							<td><?php echo number_format($values['heavy_water'], 2); ?> ISK</td>
							<td>375,750</td>
						</tr>
						<tr>
							<td>Liquid Ozone</td>
							<td><?php echo number_format($values['liquid_ozone'], 2); ?> ISK</td>
							<td>375,750</td>
						</tr>
						<tr>
							<td>Strontium Clathrates</td>
							<td><?php echo number_format($values['strontium_clathrates'], 2); ?> ISK</td>
							<td><span style="color: red">Unwanted</span></td>
						</tr>
						<tr>
							<td>Pristine White Glaze</td>
							<td><?php echo number_format($values['pristine_white_glaze'], 2); ?> ISK</td>
							<td><span style="color: red">Unwanted</span></td>
						</tr>
						<tr>
							<td>Dark Glitter</td>
							<td><?php echo number_format($values['dark_glitter'], 2); ?> ISK</td>
							<td><span style="color: red">Unwanted</span></td>
						</tr>
						<tr>
							<td>Gelidus</td>
							<td><?php echo number_format($values['gelidus'], 2); ?> ISK</td>
							<td><span style="color: red">Unwanted</span></td>
						</tr>
						<tr>
							<td>Glare Crust</td>
							<td><?php echo number_format($values['glare_crust'], 2); ?> ISK</td>
							<td><span style="color: red">Unwanted</span></td>
						</tr>
						<tr>
							<td>Krystallos</td>
							<td><?php echo number_format($values['krystallos'], 2); ?> ISK</td>
							<td><span style="color: red">Unwanted</span></td>
						</tr>
						<tr>
							<td>Electrolytes</td>
							<td><?php echo number_format($values['electrolytes'], 2); ?> ISK</td>
							<td><span style="color: green">Unlimited</span></td>
						</tr>
						<tr>
							<td>Water</td>
							<td><?php echo number_format($values['water'], 2); ?> ISK</td>
							<td><span style="color: green">Unlimited</span></td>
						</tr>
						<tr>
							<td>Precious Metals</td>
							<td><?php echo number_format($values['precious_metals'], 2); ?> ISK</td>
							<td><span style="color: green">Unlimited</span></td>
						</tr>
						<tr>
							<td>Toxic Metals</td>
							<td><?php echo number_format($values['toxic_metals'], 2); ?> ISK</td>
							<td><span style="color: green">Unlimited</span></td>
						</tr>
						<tr>
							<td>Chiral Structures</td>
							<td><?php echo number_format($values['chiral_structures'], 2); ?> ISK</td>
							<td><span style="color: green">Unlimited</span></td>
						</tr>																														
						<tr>
							<td>Reactive Metals</td>
							<td><?php echo number_format($values['reactive_metals'], 2); ?> ISK</td>
							<td><span style="color: green">Unlimited</span></td>
						</tr>
						<tr>
							<td>Oxygen</td>
							<td><?php echo number_format($values['oxygen'], 2); ?> ISK</td>
							<td>>49,500</td>
						</tr>
						<tr>
							<td>Oxygen</td>
							<td><?php echo number_format($values['oxygen'], 2); ?> ISK</td>
							<td>>20,250</td>
						</tr>
						<tr>
							<td>Enriched Uranium</td>
							<td><?php echo number_format($values['enriched_uranium'], 2); ?> ISK</td>
							<td>9,000</td>
						</tr>
						<tr>
							<td>Mechanical Parts</td>
							<td><?php echo number_format($values['mechanical_parts'], 2); ?> ISK</td>
							<td>9,000</td>
						</tr>
						<tr>
							<td>Robotics</td>
							<td><?php echo number_format($values['robotics'], 2); ?> ISK</td>
							<td>2,250</td>
						</tr>

					</table>
					<p class="eve-text" style="text-align: center; font-size: 140%">Serviced Locations</p>
					<table class="table table-striped" style="text-align: center">
						<tr>
							<th style="text-align: center">System</th>
							<th style="text-align: center">Buyback Reduction</th>
						</tr>
						<tr>
							<td>U54-</td>
							<td style="font-style: italic; color: #01b43a">100% of Quote</td>
						</tr>
						<tr>
							<td>0-R5TS</td>
							<td>100% of Quote</td>
						</tr>
						<tr>
							<td>Q-EHMJ</td>
							<td>95% of Quote</td>
						</tr>
					</table>
				</div>
			</div>	
			</div>
		</div>	
    </div>

</div>
<?php
require_once('includes/footer.php');

