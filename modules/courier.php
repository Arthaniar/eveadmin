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
					<form method="post" action="/courier/">
						<formfield>
						<h2 style="text-align: center; color: red">Kibbles And Bits JF Service is currently suspended. Contracts will be rejected.</h2>
							<label for="freighter_route">Jump Freighter Route:</label>
							<select class="form-control" name="freighter_route" id="freighter_route">
								<option style="background-color: rgb(24,24,24)" disabled>Select A Jump Freighter Route</option>
								<option style="background-color: rgb(24,24,24)" disabled>Syndicate</option>
								<option style="background-color: rgb(24,24,24)" value="400">VSIG-K <--> Jita</option>
								<option style="background-color: rgb(24,24,24)" value="200">VISG-K <--> Adacyne</option>
								<option style="background-color: rgb(24,24,24)" value="400">DCHR-L <--> Jita</option>
								<option style="background-color: rgb(24,24,24)" value="200">DCHR-L <--> Adacyne</option>
								<option style="background-color: rgb(24,24,24)" value="flatfee-syndicate">Other Syndicate <--> Jita</option>
								<option style="background-color: rgb(24,24,24)" disabled>Imperium Deployment Systems</option>
								<option style="background-color: rgb(24,24,24)" value="flatfee-saranen">Saranen <--> Jita</option>
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
						$collateral_display = '<span class="label label-success">Free!</span>';
					} else {
						$total_collateral = $_POST['total_collateral'];
						$collateral_fee_modifier = (0.05 * $_POST['total_collateral']);
						$collateral_display = '<span class="label label-primary">'.number_format($_POST['total_collateral']).' ISK</span>';
					}

					// Calculating the base cost
					if($_POST['freighter_route'] != 'flatfee') {
						switch($_POST['freighter_route']):
							case 'flatfee-saranen':
								$base_rate = 300;
								$minimum_rate = 50000000;
								$max_volume = 360000;
								break;
							default:
								$base_rate = $_POST['freighter_route'];
								$minimum_rate = 10000000;
								$max_volume = 360000;
								break;
						endswitch;

						$base_fee = $base_rate * $_POST['total_volume'];

						if($base_fee < $minimum_rate) {
							$base_fee = $minimum_rate;
						}

						$base_fee_display = $base_rate.'isk/m3';
					} else {
						$base_fee = 200000000;
						$max_volume = 360000;
						$base_fee_display = '<span class="label label-primary" data-toggle="tooltip" data-placement="top" title="If you plan on regularly contracting from the same station, consider contacting Ashkrall to add this system as a permanent route for a reduced rate.">Flat Fee: 200,000,000 ISK</span>';
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
						$volume_display = '<span class="label label-primary" data-toggle="tooltip" data-placement="top" title="Consider packing in multiples of 120,000 m3 for a 5% discount! (not applicable for deployment systems or flat fee contracts)">'.number_format($_POST['total_volume']).'m3</span>';
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
							<p>Create To: <a onclick="CCPEVE.showInfo(98423199)">Kibbles And Bits</a></p>
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
							<td>VISG-K <--> Jita</td>
							<td>Syndicate</td>
							<td>400isk/m3</td>
							<td>10,000,000 ISK</td>
						</tr>
						<tr>
							<td>VSIG-K <--> Adacyne</td>
							<td>Syndicate</td>
							<td>200isk/m3</td>
							<td>10,000,000 ISK</td>
						</tr>
						<tr>
							<td>DCHR-L <--> Jita</td>
							<td>Syndicate</td>
							<td>400isk/m3</td>
							<td>10,000,000 ISK</td>
						</tr>
						<tr>
							<td>DCHR-L <--> Adacyne</td>
							<td>Syndicate</td>
							<td>200isk/m3</td>
							<td>10,000,000 ISK</td>
						</tr>
						<tr>
							<td>Other Syndicate System <--> Jita</td>
							<td>Syndicate</td>
							<td>200,000,000 ISK Flat Fee</td>
							<td>200,000,000 ISK</td>
						</tr>
					</table>
					<p class="eve-text" style="text-align: center; font-size: 140%">Collateral Fees</p>
					<table class="table table-striped" style="text-align: center">
						<tr>
							<th style="text-align: center">Requested Collateral Amount</th>
							<th style="text-align: center">Additional Fee</th>
						</tr>
						<tr>
							<td>Up To 100 Mil ISK</td>
							<td style="font-style: italic; color: #01b43a">Free</td>
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
