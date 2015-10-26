<?php
require_once('includes/header.php');
?>
<div class="opaque-container">

    <div class="row" style="width: 100%; margin-top: 20px; margin-bottom: 20px">
		<div class="col-md-12 opaque-section" style="padding: 0px">
			<div class="row box-title-section">
				<h1 style="text-align: center">Courier Contracts</h1>
			</div>
			<?php showAlerts(); ?>
			<div class="row" style="padding-left: 10px; padding-right: 10px">
			<div class="col-md-6 col-sm-12">
				<div class="row box-title-section">
					<h3 style="text-align: center">Contract Calculator</h3>
				</div>
				<div class="row">
					<form method="post" action="/modules/courier/">
						<formfield>
							<label for="freighter_route">Jump Freighter Route:</label>
							<select class="form-control" name="freighter_route" id="freighter_route">
								<option style="background-color: rgb(24,24,24)" disabled>Select A Jump Freighter Route</option>
								<option style="background-color: rgb(24,24,24)" disabled>Vale Of the Silent</option>
								<option style="background-color: rgb(24,24,24)" value="200">FH-TTC <--> Jita</option>
								<option style="background-color: rgb(24,24,24)" value="200">Q-EHMJ <--> Jita</option>
								<option style="background-color: rgb(24,24,24)" value="200">7-K5EL <--> Jita</option>
								<option style="background-color: rgb(24,24,24)" value="200">0-R5TS <--> Jita</option>
								<option style="background-color: rgb(24,24,24)" value="lsjep">LS-JEP <--> Jita</option>
								<option style="background-color: rgb(24,24,24)" value="flatfee">Other Vale <--> Jita</option>
								<option style="background-color: rgb(24,24,24)" value="150">Vale System <--> Vale System</option>
								<option style="background-color: rgb(24,24,24)" disabled>LAWN Deployment</option>
								<option style="background-color: rgb(24,24,24)" value="750">H-ADOC <--> Jita</option>
								<option style="background-color: rgb(24,24,24)" disabled>Imperium Deployment Systems</option>
								<option style="background-color: rgb(24,24,24)" value="dek-3v8">3V8-LJ <--> Jita</option>
								<option style="background-color: rgb(24,24,24)" value="dek-ya0">YA0-XJ <--> Jita</option>
								<option style="background-color: rgb(24,24,24)" value="vale-ya0">YA0-XJ <--> FH-TTC</option>
							</select>
						</formfield>
						<formfield>
							<label for="total_volume">Total m3 of Contract: </label>
							<input class="form-control" type="number" name="total_volume" id="total_volume" placeholder="Total volume of the contract in m3">
						</formfield>
						<formfield>
							<label for="total_collateral">Total Collateral Requested: </label>
							<input class="form-control" type="number" name="total_collateral" id="total_collateral" placeholder="Total collateral requested for contract">
						</formfield>
						<formfield>
							<input type="submit" class="btn btn-primary" value="Calculate Contract" style="margin-top: 10px">
						</formfield>
					</form>
				</div>
				<?php
				if(isset($_POST['freighter_route'])) {
					// Calculating the collateral cost
					if($_POST['total_collateral'] == '' OR $_POST['total_collateral'] == 0) {
						$total_collateral = 0;
						$collateral_fee_modifier = 0;
						$collateral_display = '<span class="laben label-danger>No Collateral</span>';
					} elseif($_POST['total_collateral'] <= 100000000) {
						$total_collateral = $_POST['total_collateral'];
						$collateral_fee_modifier = 0;
						$collateral_display = '<span class="label label-success">100,000,000 ISK Collateral Free!</span>';
					} else {
						$total_collateral = $_POST['total_collateral'];
						$collateral_fee_modifier = (0.05 * $_POST['total_collateral']);
						$collateral_display = '<span class="label label-primary">'.number_format($_POST['total_collateral']).' ISK</span>';
					}

					// Calculating the base cost
					if($_POST['freighter_route'] != 'flatfee') {
						switch($_POST['freighter_route']):
							case 'dek-ya0':
								$base_rate = 250;
								$minimum_rate = 50000000;
								$max_volume = 360000;
								break;
							case 'dek-3v8':
								$base_rate = 250;
								$minimum_rate = 50000000;
								$max_volume = 360000;
								break;
							case 'vale-ya0':
								$base_rate = 300;
								$minimum_rate = 75000000;
								$max_volume = 360000;
								break;
							case 'lsjep':
								$max_volume = 360000;
								if($user->getLoginStatus() AND $user->getUserAccess() AND $user->getGroup() == 1) {
									$base_rate = 150;
									$minimum_rate = 1;
								} else {
									$base_rate = 250;
									$minimum_rate = 5000000;
								}
								break;
							case '750':
								if($_POST['total_volume'] > 120000) {
									$base_rate = 1;
									$minimum_rate = 250000000;
									$max_volume = 120000;
								} else {
									$base_rate = 750;
									$minimum_rate = 25000000;
									$max_volume = 120000;
								}
								break;
							default:
								$base_rate = $_POST['freighter_route'];
								$minimum_rate = 5000000;
								$max_volume = 360000;
								break;
						endswitch;

						$base_fee = $base_rate * $_POST['total_volume'];

						if($base_fee < $minimum_rate) {
							$base_fee = $minimum_rate;
						}

						$base_fee_display = $base_rate.'isk/m3';
					} else {
						$base_fee = 150000000;
						$base_fee_display = '<span class="label label-primary" data-toggle="tooltip" data-placement="top" title="If you plan on regularly contracting from the same station, consider contacting Ashkrall to add this system as a permanent route for a reduced rate!">Flat Fee: 150,000,000 ISK</span>';
					}

					// Confirming the total volume is correct
					if($_POST['total_volume'] > $max_volume) {
						if($_POST['freighter_route'] == 750) {
							$volume_display	= '<span class="label label-success" data-toggle="tooltip" data-placement="top" title="Contracts over 120,000 m3 get flat fee pricing up to 360,000 m3">'.number_format($_POST['total_volume']).'</span>';
							$volume_modifier = 1;
						} else {
							$volume_display = '<span class="label label-danger">Over Volume Limit - '.number_format($_POST['total_volume']).'m3</span>';
							$volume_modifier = 1;
						}
					} elseif($_POST['total_volume'] % 120000 == 0) {
						$volume_display = '<span class="label label-success" data-toggle="tooltip" data-placement="top" title="Alright! You get a 5% discount for packing like a pro!">'.number_format($_POST['total_volume']).'m3</span>';
						$volume_modifier = 0.95;
					} else {
						$volume_display = '<span class="label label-primary" data-toggle="tooltip" data-placement="top" title="Consider packing in multiples of 120,000 m3 for a 5% discount! (not applicable for deployment systems)">'.number_format($_POST['total_volume']).'m3</span>';
						$volume_modifier = 1;
					}

					$total_fee = ($base_fee*$volume_modifier) + $collateral_fee_modifier;

					?>
					<div class="row" style="margin-top: 20px">
						<div class="col-md-6 col-sm-12">					
							<h4 class="eve-text">Contract Price Info</h4>
							<p>ISK/m3: <?php echo $base_fee_display; ?></p>
							<p>Total Volume: <?php echo $volume_display; ?></p>
							<p>Collateral Fee: <?php echo number_format($collateral_fee_modifier).' ISK'; ?></p>
						</div>
						<div class="col-md-6 col-sm-12">
							<h4 class="eve-text">Your Contract</h4>
							<p>Create To: <a onclick="CCPEVE.showInfo(92753614)">Jon Fisk</a></p>
							<p>Expiration/Completion: 7 days/3 days</p>
							<p>Contract Reward: <?php echo number_format($total_fee).' ISK'; ?></p>
							<p>Contract Collateral: <?php echo number_format($total_collateral).' ISK'; ?></p>
						</div>
					</div>
					<?php
				}
				?>
			</div>
			<div class="col-md-6 col-sm-12">
				<div class="row box-title-section">
					<h3 style="text-align: center">Price List and Fees</h3>
				</div>
				<div class="row">
					<p class="eve-text" style="text-align: center; font-size: 140%">Base Fees</p>	
					<table class="table table-striped" style="text-align: center">
						<tr>
							<th style="text-align: center">Location</th>
							<th style="text-align: center">Region</th>
							<th style="text-align: center">Price</th>
							<th style="text-align: center">Minimum Reward</th>
						</tr>
						<tr>
							<td>FH-TTC <--> Jita</td>
							<td>Vale of the Silent</td>
							<td>200isk/m3</td>
							<td>5,000,000 ISK</td>
						</tr>
						<tr>
							<td>Q-EHMJ <--> Jita</td>
							<td>Vale of the Silent</td>
							<td>200isk/m3</td>
							<td>5,000,000 ISK</td>
						</tr>
						<tr>
							<td>7-K5EL <--> Jita</td>
							<td>Vale of the Silent</td>
							<td>200isk/m3</td>
							<td>5,000,000 ISK</td>
						</tr>
						<tr>
							<td>0-R5TS <--> Jita</td>
							<td>Vale of the Silent</td>
							<td>200isk/m3</td>
							<td>5,000,000 ISK</td>
						</tr>
						<tr>
							<td>LS-JEP <--> Jita</td>
							<td>Vale of the Silent</td>
							<td>250isk/m3</td>
							<td>5,000,000 ISK</td>
						</tr>
						<tr>
							<td>YA0-XJ <--> Jita</td>
							<td>Deklein</td>
							<td><!--250isk/m3--><span style="color: red">Suspended</span></td>
							<td><!--50,000,000 ISK--><span style="color: red">Suspended</span></td>
						</tr>
						<tr>
							<td>YA0-XJ <--> FH-TTC</td>
							<td>Deklein</td>
							<td>300isk/m3</td>
							<td>75,000,000 ISK</td>
						</tr>
						<tr>
							<td>3V8-LJ <--> Jita</td>
							<td>Pure Blind</td>
							<td><!--250isk/m3--><span style="color: red">Suspended</span></td>
							<td><!--50,000,000 ISK--><span style="color: red">Suspended</span></td>
						</tr>
						<tr>
							<td>Other Vale System <--> Jita</td>
							<td>Vale of the Silent</td>
							<td>150,000,000 ISK Flat Fee</td>
							<td>150,000,000 ISK</td>
						</tr>
						<tr>
							<td>Intra-Vale Contracts</td>
							<td>Vale of the Silent</td>
							<td>150isk/m3</td>
							<td>5,000,000 ISK</td>
						</tr>
					</table>
					<p class="eve-text" style="text-align: center; font-size: 140%">Collateral Fees</p>
					<table class="table table-striped" style="text-align: center">
						<tr>
							<th style="text-align: center">Collateral Amount</th>
							<th style="text-align: center">Additional Fee</th>
						</tr>
						<tr>
							<td>Up To 100 Mil ISK</td>
							<td style="font-style: italic; color: #01b43a">Included Free!</td>
						</tr>
						<tr>
							<td>100 Mil to 5 Bil</td>
							<td>5% of Collateral</td>
						</tr>
						<tr>
							<td>5 Bil+</td>
							<td>Contact Ashkrall Directly</td>
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