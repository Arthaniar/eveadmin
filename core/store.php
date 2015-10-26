<?php
require_once('includes/header.php');
if($request['action'] = 'delivery') {
	// We want to travel from Amarr
	$origin = 30002187;

	// To Jita
	$target = 30000142;

	// This will hold the result of our calculation
	$jumpResult = array(
	    'origin' => $origin,
	    'destination' => $target,
	    'jumps' => 'N/A',
	    'distance' => -1
	);

	// Load the jumps, by fetching the SolarSystemIDs from the Static Data Dump
	// Results in an array like
	// $jumps = array(
	//     'SystemID' => array('ID of neighbour system 1', 'ID of neighbour system 2', '...'),
	//     '...'
	// );

	$jumps = array();

	// Assuming a mysql conversion of the Static Data Dump
	// in the database evesdd
	$stmt = $db->prepare('SELECT `fromSolarSystemID`, `toSolarSystemID` FROM `mapSolarSystemJumps`');
	$stmt->execute(array());
	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

	foreach ($result as $row) {
	    $from = (int) $row['fromSolarSystemID'];
	    $to   = (int) $row['toSolarSystemID'];

	    if (!isset($jumps[$from])) {
	        $jumps[$from] = array();
	    }
	    $jumps[$from][] = $to;
	}


	// Start the fun
	if (isset($jumps[$origin]) && isset($jumps[$target])) {

	    // Target and origin the same, no distance
	    if ($target == $origin) {
	        $jumpResult['jumps'] = $origin;
	        $jumpResult['distance'] = 0;
	    }

	    // Target is a neigbour system of origin
	    elseif (in_array($target, $jumps[$origin])) {
	        $jumpResult['jumps'] = $origin . ',' . $target;
	        $jumpResult['distance'] = 1;
	    }

	    // Lets start the fun
	    else {
	        // Will contain the system IDs
	        $resultPath = array();
	        // Already visited system
	        $visitedSystems = array();
	        // Limit the number of iterations
	        $remainingJumps = 9000;
	        // Systems we can reach from here
	        $withinReach = array($origin);

	        while (count($withinReach) > 0 && $remainingJumps > 0 && count($resultPath) < 1) {
	            $remainingJumps--;

	            // Jump to the first system within reach
	            $currentSystem = array_shift($withinReach);

	            // Get the IDs of the systems, connected to the current
	            $links = $jumps[$currentSystem];
	            $linksCount = count($links);

	            // Test all connected systems
	            for($i = 0; $i < $linksCount; $i++) {
	                $neighborSystem = $links[$i];

	                // If neighbour system is the target,
	                // Build an array of ordered system IDs we need to
	                // visit to get from thhe origin system to the
	                // target system
	                if ($neighborSystem == $target) {
	                    $resultPath[] = $neighborSystem;
	                    $resultPath[] = $currentSystem;
	                    while ($visitedSystems[$currentSystem] != $origin) {
	                        $currentSystem = $visitedSystems[$currentSystem];
	                        $resultPath[] = $currentSystem;
	                    }
	                    $resultPath[] = $origin;
	                    $resultPath = array_reverse($resultPath);
	                    break;
	                }

	                // Otherwise, store the current - neighbour
	                // Connection in the visited systems and add the
	                // neighbour to the systems within reach
	                else if (!isset($visitedSystems[$neighborSystem])) {
	                    $visitedSystems[$neighborSystem] = $currentSystem;
	                    array_push($withinReach, $neighborSystem);
	                }
	            }
	        }

	        // If the result path is filled, we have a connection
	        if (count($resultPath) > 1) {
	            $jumpResult['distance'] = count($resultPath) - 1;
	            $jumpResult['jumps'] = $resultPath;
	        }
	    }
	}
}

?>
<div class="opaque-container">

    <div class="row" style="width: 100%; margin-top: 20px; margin-bottom: 20px">
		<div class="col-md-12 opaque-section" style="padding: 0px">
			<div class="row box-title-section">
				<h1 style="text-align: center">DOGFT Delivery Service</h1>
			</div>
			<?php showAlerts(); ?>
			<div class="row" style="padding-left: 10px; padding-right: 10px">
				<div class="col-md-4">
					<div class="opaque-section" style="background-image: none">
						
					</div>
				</div>
				<div class="col-md-4 col-sm-6">
					<div class="opaque-section" style="margin-top: 10px; margin-bottom: 10px; background-image: none">
						<div class="row box-title-section">
							<h3 style="text-align: center">Trip Calculator</h3>
						</div>	
						<div class="row">	
							<form action="/store/delivery/calculate" method="post" style="margin-top: 10px; margin-bottom: 20px">
								<label for="starting_system">Starting Location:</label>
								<input class="form-control" type="text" name="starting_system" placeholder="Starting Location">
								<label for="ending_system">Ending Location:</label>
								<input class="form-control" type="text" name="ending_system" placeholder="Ending Location">
								<input type="submit" value="Calculate Trip" class="btn btn-primary btn-lg eve-text" style="margin-top: 10px; margin-bottom: 10px">
							</form>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<table class="table table-striped">
					<tr>
						<th>Jump #</th>
						<th>System</th>
						<th>Security Status</th>
					</tr>
					<?php
					if(isset($jumpResult['distance'])) {
						$i = 0;
						?>
						<?php
						$stmt = $db->prepare('SELECT * FROM mapSolarSystems WHERE solarSystemID = ? LIMIT 1');
						foreach($jumpResult['jumps'] as $jump) {
							$stmt->execute(array($jump));
							$solarSystemInfo = $stmt->fetch(PDO::FETCH_ASSOC);
							?>
							<tr>
								<td><?php echo $i; ?></td>
								<td><?php echo $solarSystemInfo['solarSystemName']; ?></td>
								<td><?php echo number_format($solarSystemInfo['security'], 1);  ?></td>
							</tr>
							<?php
							$i++;
						}
					}
					?>
				</table>
			</div>
		</div>	
    </div>

</div>
<?php
require_once('includes/footer.php');