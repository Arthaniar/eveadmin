<?php
require_once('includes/header.php');


if(isset($_POST['operation_name'])) {
  $operationTimestamp = strtotime($_POST['operation_date']." ".$_POST['operation_time']);
  $fittingArray = array();
  $i = 0;
  if(isset($_POST['operation_fittings'])) {
    foreach($_POST['operation_fittings'] as $fitting) {
      $fittingArray[$i] = $fitting;
      $i++;
    }
    $fittingsList = implode(',', $fittingArray);
  } else {
    $fittingsList = 'No fittings or doctrines requested';
  }
  $stmt = $db->prepare('INSERT INTO group_operations (gid,operation_name,operation_type,operation_fc,operation_rally,operation_comms,operation_timestamp,operation_fittings,operation_details) VALUES (?,?,?,?,?,?,?,?,?)');
  $stmt->execute(array($user->getGroup(),
                       $_POST['operation_name'],
                       $_POST['operation_type'],
                       $_POST['operation_fc'],
                       $_POST['operation_rally'],
                       $_POST['operation_comms'],
                       $operationTimestamp,
                       $fittingsList,
                       $_POST['operation_details']));
  if($settings->getSlack()) {
    sendSlackNotification($settings->getGroupTicker().' Operations Calendar', $settings->getSlackOpsChannel(), 'New Fleet Op Posted: '.$_POST['operation_name'].' - '.$_POST['operation_date'].' @ '.$_POST['operation_time'].' FCed by '.$_POST['operation_fc'].'. Form up in '.$_POST['operation_rally'].' on '.$_POST['operation_comms'], 'aura', $settings->getSlackWebhook());
    setAlert('success', 'New Operation Created and Posted To Slack', '');
  } else {
    setAlert('success', 'New Operation Created', '');
  }
}

$stmt = $db->prepare('SELECT * FROM group_operations WHERE gid = ? AND operation_timestamp >= ? ORDER BY operation_timestamp');
$stmt->execute(array($user->getGroup(), time()));
$operations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="opaque-container">

    <div class="row" style="width: 100%; margin-top: 20px; margin-bottom: 20px">
		<div class="col-md-12 opaque-section" style="padding: 0px">
			<div class="row box-title-section">
				<h1 style="text-align: center">Operations Calendar<h1>
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
				      			<th></th>
				      		</tr>
				      		<?php
				      		foreach($operations as $operation) {
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
					      			<td><button class="btn btn-warning btn-sm" type="button" data-toggle="modal" data-target="#op<?php echo $i; ?>" aria-hidden="true">View Op Details</button></td>
					      		</tr>
				      			<?php
				      		}
				      		?>
				      	</table>
				      	<?php
			      	} else {
						?>
						<h3 class="eve-text" style="text-align: center; margin-bottom: 20px">No Operations Currently Scheduled.<br/>Check Back Later Or Schedule One Below.</h3>
						<?php
					}
		      		?>
		      	</table>
			</div>
		</div>	
    </div>

    <!-- Schedule Operation Section -->
    <?php
    $stmt = $db->prepare('SELECT * FROM doctrines WHERE gid = ? ORDER BY doctrine_name ASC');
    $stmt->execute(array($user->getGroup()));
    $doctrines = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ?>
    <div class="row" style="width: 100%; margin-top: 20px; margin-bottom: 20px">
		<div class="col-md-12 opaque-section" style="padding: 0px">
			<div class="row box-title-section">
				<h1 style="text-align: center">Schedule An Operation<h1>
			</div>
			<div class="row" style="padding-left: 10px; padding-right: 10px">
				<form action="/operations/add/" method="post">
		      		<div class="col-md-6" class="eve-text">
		      			<formfield>
			      			<h4 class="eve-text">Operation Name:</h4>
			      			<input type="text" placeholder="Give your Operation a descriptive name" class="form-control" name="operation_name">
		      			</formfield>
		      			<formfield>
		      				<h4 class="eve-text">Operation Type:</h4>
			      			<select class="form-control" name="operation_type" style="color: #f5f5f5; background-color: transparent; background-image: none">
					            <option style="background-color: rgb(23,23,23)" value="CTA Op">CTA/Strategic Operation</option>
					            <option style="background-color: rgb(23,23,23)" value="PvP Op">PvP Operation</option>
					            <option style="background-color: rgb(23,23,23)" value="PvE Op">ADM Index Operation</option>
					            <option style="background-color: rgb(23,23,23)" value="Meeting">Corp/Alliance Meeting</option>
					            <option style="background-color: rgb(23,23,23)" value="Gank Op">Ganking Op</option>
			      			</select>
		      			</formfield>
						<formfield>
							<h4 class="eve-text">Operation Time:</h4>
							<input class="form-control" name="operation_date" type="date" style="width: 50%; float: left; display: inline-flex"><input style="width: 50%; float: left; clear:right; display: inline-flex;" class="form-control" id="operation_time" name="operation_time" type="time" placeholder="Give your Operation a descriptive name!"><br />
						</formfield>
						<formfield>
							<h4 class="eve-text" style="padding-top: 10px">Fleet Commander:</h4>
							<input class="form-control" name="operation_fc" type="text" placeholder="Who is commanding the Operation?">
						</formfield>
						<formfield>
							<h4 class="eve-text">Form Up Location:</h4>
							<input class="form-control" name="operation_rally" type="text" placeholder="Where is the Operation form up?">
						</formfield>
						<formfield>
							<h4 class="eve-text">Operation Comms:</h4>
							<input style="margin-bottom: 20px" class="form-control" operation="operation_comms" name="operation_comms" type="text" placeholder="What Comms server or channel?">
						</formfield>
		      		</div>
		      		<div class="col-md-6">
		      			<h3 class="eve-text">Required Doctrines:</h3>
		      			<?php
		      			foreach($doctrines as $doctrine) {
		      				?>
		      				<label style="font-weight: normal;"><input type="checkbox" name="operation_fittings[]" value="<?php echo $doctrine['doctrine_name']; ?>"><span class="eve-text" style="font-size: 125%; padding-left: 5px"><?php echo $doctrine['doctrine_name']; ?></span></label><br />
		      				<?php
		      			}
		      			?>
		      			<h3 class="eve-text" style="text-align: center; margin-top: 10px">Any Other Details / Information</h4>
            			<textarea class="form-control" style="width: 100%" rows="5" type="text" name="operation_details" placeholder="Any Operation or Meeting information you wish to provide."></textarea>
            			<br />
            			 <input class="btn btn-primary eve-text" style="text-align: center; font-size: 125%; margin-bottom: 20px" type="submit" value="Create Operation" />
            			 <input class="btn btn-danger eve-text" style="text-align: center; font-size: 125%; margin-bottom: 20px; margin-left: 10px" type="reset" value="Clear All Information" />
		      		</div>
		      	</form>
			</div>
		</div>	
    </div>

</div>
<?php
require_once('includes/footer.php');