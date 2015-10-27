<?php
if($request['action'] != NULL) {

	// Checking to see what action the user wants to do
	if($request['action'] == 'setprimary') {

		// Setting the users primary character
		$user->setDefault($_POST['characterName'], $_POST['characterID']);

	} elseif($request['action'] == 'addplan' AND $user->getDirectorAccess()) {

		// First we need to see what order was selected
		if($_POST['skillplan_order'] == 'last') {
			// The user wants this skillplan at the end, so we'll first get the highest skillplan_order value
			$stmt = $db->prepare('SELECT * FROM skillplan_main WHERE gid = ? ORDER BY skillplan_order DESC LIMIT 1');
			$stmt->execute(array($user->getGroup()));
			$count = $stmt->fetch(PDO::FETCH_ASSOC);

			// Now we'll add +1 to the highest previous skillplan_order value to get the skillplan_order value for this skillplan
			$selected_order = $count['skillplan_order']+1;
		} else {
			// The user wants this skillplan inserted into the mix, so lets see where it's going to go.
			$selected_order = $_POST['skillplan_order'];

			// Now we will update all other skillplans for this group with a number equal or greater to our selected_order, and add 1 to them to push them down
			$stmt = $db->prepare('UPDATE skillplan_main SET skillplan_order = skillplan_order +1 WHERE skillplan_order >= ? AND gid = ?');
			$stmt->execute(array($selected_order, $user->getGroup()));
		}

		// Adding the requested skillplan to our database
		$stmt = $db->prepare('INSERT INTO skillplan_main (gid,skillplan_name,skillplan_order) VALUES (?,?,?) ON DUPLICATE KEY UPDATE skillplan_order=VALUES(skillplan_order)');
		$stmt->execute(array($user->getGroup(), $_POST['skillplan_name'],$selected_order));
	} elseif($request['action'] == 'deleteplan' AND $user->getDirectorAccess()) {
		if($_POST['skillplan_id'] == 'Do Not Delete') {
			// Skipping this, since they accidentally clicked it. Whoops, bad user
		} else {
			Skillplan::deleteSkillPlan($_POST['skillplan_id'], $user->getGroup());
		}
	} elseif($request['action'] == 'renameplan' AND $user->getDirectorAccess()) {
		if($_POST['skillplan_id'] == 'Do Not Rename') {
			// Skipping this, since they accidentally clicked it. Whoops, bad user
		} else {
			Skillplan::renameSkillPlan($_POST['skillplan_id'], $user->getGroup(), $_POST['new_skill_plan_name']);
		}
	} elseif($request['action'] == 'editmode' AND $user->getDirectorAccess()) {

		// We are currently in edit mode, so we will check to see if there's anything that needs doing
		if(isset($_POST['new_subgroup_name'])) {

			// We are adding a new subgroup to an existing Skill Plan
			// First we need to see what order was selected
			if($_POST['subgroup_order'] == 'last') {
				// The user wants this skillplan at the end, so we'll first get the highest skillplan_order value
				$stmt = $db->prepare('SELECT * FROM skillplan_subgroups WHERE skillplan_id = ? ORDER BY subgroup_order DESC LIMIT 1');
				$stmt->execute(array($_POST['skillplan_id']));
				$count = $stmt->fetch(PDO::FETCH_ASSOC);

				// Now we'll add +1 to the highest previous skillplan_order value to get the skillplan_order value for this skillplan
				$selected_order = $count['subgroup_order']+1;
			} else {
				// The user wants this skillplan inserted into the mix, so lets see where it's going to go.
				$selected_order = $_POST['subgroup_order'];

				// Now we will update all other skillplans for this group with a number equal or greater to our selected_order, and add 1 to them to push them down
				$stmt = $db->prepare('UPDATE skillplan_subgroups SET subgroup_order = subgroup_order +1 WHERE subgroup_order >= ?');
				$stmt->execute(array($selected_order, $user->getGroup()));
			}

			$stmt = $db->prepare('INSERT INTO skillplan_subgroups (gid,skillplan_id,subgroup_name,subgroup_order) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE subgroup_order=VALUES(subgroup_order)');
			$stmt->execute(array($user->getGroup(), $_POST['skillplan_id'], $_POST['new_subgroup_name'], $selected_order));
		} elseif(isset($_POST['delete_subgroup_id'])) {
			$stmt = $db->prepare('DELETE FROM skillplan_skills WHERE subgroup_id = ?');
			$stmt->execute(array($_POST['delete_subgroup_id']));

			$stmt = $db->prepare('DELETE FROM skillplan_subgroups WHERE subgroup_id = ? AND skillplan_id = ?');
			$stmt->execute(array($_POST['delete_subgroup_id'], $_POST['skillplan_id']));
		} elseif(isset($_POST['new_skill_name'])) {
			$newSkillID = $eve->getTypeID($_POST['new_skill_name']);
			if($newSkillID != NULL) {
				$stmt = $db->prepare('INSERT INTO skillplan_skills (subgroup_id,skill_typeid,skill_typename,skill_level) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE skill_level=VALUES(skill_level)');
				$stmt->execute(array($_POST['subgroup_id'], $newSkillID, $_POST['new_skill_name'], $_POST['required_skill_level']));
			}
		} elseif(isset($_POST['delete_skill_id'])) {
			$stmt = $db->prepare('DELETE FROM skillplan_skills WHERE subgroup_id = ? AND skill_typeid = ?');
			$stmt->execute(array($_POST['subgroup_id'], $_POST['delete_skill_id']));
		}
	}
}

// Getting all of our skill plans via Skillplan class
$plans = new Skillplan($db, $user->getGroup(), $user->getDefaultID());

require_once('includes/header.php');
?>
<div class="opaque-container" role="tablist" aria-multiselectable="true">

    <div class="row" style="width: 100%; margin-top: 20px; margin-bottom: 20px">
    	<?php
    	if($user->getDirectorAccess()) {
    		?>
			<div class="col-md-12 opaque-section" style="padding: 0px">
				<div class="row box-title-section" role="tab" id="AddSkillPlanHeading">
					<a class="box-title-link" style="text-decoration: none" role="button" data-toggle="collapse" data-parent="AddSkillPlan" href="#collapseAddSkillPlan" aria-expanded="true" aria-controls="collapseAddSkillPlan">
						<h1 class="eve-text" style="margin-top: 10px; text-align: center; font-size: 200%; font-weight: 700">Skill Plans and Training Information</h1>
					</a>
				</div>
				<div id="collapseAddSkillPlan" class="panel-collapse collapse out" role="tabpanel" aria-labelledby="AddSkillPlanHeading">
					<div class="col-md-12">
						<div class="row">
							<div class="col-md-4 col-sm-6">
								<h3 class="eve-text" style="text-align: center">Add A New Skill Plan</h3>
								<form method="post" action="/skillplans/addplan/">
									<label for="skillplan_name">New Skill Plan Name:</label><input style="background-color: transparent; color: #f5f5f5" class="form-control" type="text" name="skillplan_name" placeholder="Skill Plan Name">
									<label for="skillplan_order">Skill Plan Order</label><select name="skillplan_order" class="form-control" style="margin-top: 5px; background-color: transparent;">
										<option style="background-color: rgb(23,23,23)" value="0">Show First</option>
										<?php
										foreach($plans->getPlans() as $plan) {
											?>
											<option style="background-color: rgb(23,23,23)" value="<?php echo ($plan['skillplan_order']+1); ?>">Show After <?php echo $plan['skillplan_name'];?></option>
											<?php
										}
										?>
										<option style="background-color: rgb(23,23,23)" value="last">Show Last</option>
									</select>
									<input class="btn btn-success eve-text pull-right" style="margin-top: 5px; margin-bottom: 10px; font-size: 125%" type="submit" value="Create Skill Plan">
								</form>
							</div>
							<div class="col-md-4 col-sm-6" style="text-align: center">
								<?php
								if($request['action'] == 'editmode') {
									?>
									<h3 class="eve-text" style="text-align: center">Skill Plan Edit Mode</h3>
									<a href="/skillplans/" style="text-align: center">
										<h3 class="btn btn-primary btn-lg eve-text" style="text-align: center; margin-bottom: 20px">Disable Skill Plan Edit Mode</h3>
									</a>
									<?php
								} else {
									?>
									<h3 class="eve-text" style="text-align: center">Edit Current Plans And Skills</h3>
									<a href="/skillplans/editmode/" style="text-align: center">
										<h3 class="btn btn-primary btn-lg eve-text" style="text-align: center; margin-bottom: 20px">Enable Skill Plan Edit Mode</h3>
									</a>
									<?php
								}
								?>

							</div>
							<div class="col-md-4 col-sm-6">
								<div class="row">
									<h3 class="eve-text" style="text-align: center">Delete A Skill Plan</h3>
									<form method="post" action="/skillplans/deleteplan/">
										<label for="skillplan_id">Select Skill Plan To Delete:</label>
										<select name="skillplan_id" class="form-control" style="background-color: transparent;">
											<option style="background-color: rgb(23,23,23)" value="Do Not Delete">Select A Skill Plan Below</option>
											<?php
											foreach($plans->getPlans() as $plan) {
												?>
												<option style="background-color: rgb(23,23,23)" value="<?php echo $plan['skillplan_id']; ?>"><?php echo $plan['skillplan_name'];?></option>
												<?php
											}
											?>
										</select>
										<input class="btn btn-danger eve-text pull-right" style="margin-top: 5px; margin-bottom: 10px; font-size: 125%" type="submit" value="Delete Skill Plan">
									</form>
								</div>
								<div class="row">
									<h3 class="eve-text" style="text-align: center">Rename A Skill Plan</h3>
									<form method="post" action="/skillplans/renameplan/">
										<label for="skillplan_id">Select Skill Plan To Rename:</label>
										<select name="skillplan_id" class="form-control" style="background-color: transparent;">
											<option style="background-color: rgb(23,23,23)" value="Do Not Rename">Select A Skill Plan Below</option>
											<?php
											foreach($plans->getPlans() as $plan) {
												?>
												<option style="background-color: rgb(23,23,23)" value="<?php echo $plan['skillplan_id']; ?>"><?php echo $plan['skillplan_name'];?></option>
												<?php
											}
											?>
										</select>
										<label>New Skill Plan Name: </label>
										<input class="form-control" type="text" name="new_skill_plan_name" placeholder="New Skill Plan Name">
										<input class="btn btn-primary eve-text pull-right" style="margin-top: 5px; margin-bottom: 10px; font-size: 125%" type="submit" value="Rename Skill Plan">
									</form>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
    		<?php
    	} else {
    		?>
			<div class="col-md-12 opaque-section" style="padding: 0px">
				<div class="row box-title-section">
					<h1 class="eve-text" style="margin-top: 0px; text-align: center; font-size: 200%; font-weight: 700">Skill Plans and Training Information</h1>
				</div>
			</div>
    		<?php
    	}

    	?>
	</div>
</div>

<?php
foreach($plans->getPlans() as $skillplan) {
	?>
	<div class="opaque-container panel-group" id="<?php echo $skillplan['skillplan_id']; ?>" role="tablist" aria-multiselectable="true">
		<div class="row">
			<div class="col-md-12 opaque-section" style="margin-bottom: 15px">
				<div class="row box-title-section" style="margin-bottom: 10px" role="tab" id="<?php echo $skillplan['skillplan_id']; ?>Heading">
					<a class="box-title-link" style="text-decoration: none" role="button" data-toggle="collapse" data-parent="<?php echo $skillplan['skillplan_id']; ?>" href="#collapse<?php echo $skillplan['skillplan_id']; ?>" aria-expanded="true" aria-controls="collapse<?php echo $skillplan['skillplan_id']; ?>">
						<h2 class="eve-text" style="margin-top: 0px; text-align: center; font-size: 200%; font-weight: 700"><?php echo $skillplan['skillplan_name']; ?> Plan</h2>
					</a>
				</div>
				<div id="collapse<?php echo $skillplan['skillplan_id']; ?>" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="<?php echo $skillplan['skillplan_id']; ?>Heading">
					<?php
					$i = 1;

					if($request['action'] == 'editmode' AND $user->getDirectorAccess()) {
						?>
						<div class="row">
							<div class="col-md-6 col-sm-6">
								<h3 class="eve-text" style="text-align: center">Add A New Skill Group</h3>
								<form method="post" action="/skillplans/editmode/">
									<formfield>
										<input type="hidden" name="skillplan_id" value="<?php echo $skillplan['skillplan_id']; ?>">
									</formfield>
									<formfield>
										<label for="new_subgroup_name">New Skill Group Name:</label>
										<input style="background-color: transparent; color: #f5f5f5" class="form-control" type="text" name="new_subgroup_name" placeholder="Skill Group Name">
									</formfield>
									<formfield>
										<label for="subgroup_order">Skill Group Order</label>
										<select name="subgroup_order" class="form-control" style="margin-top: 5px; background-color: transparent;">
											<option style="background-color: rgb(23,23,23)" value="0">Show First</option>
											<?php
											foreach($skillplan['groups'] as $group) {
												?>
												<option style="background-color: rgb(23,23,23)" value="<?php echo ($group['subgroup_order']+1); ?>">Show After <?php echo $group['subgroup_name'];?></option>
												<?php
											}
											?>
											<option style="background-color: rgb(23,23,23)" value="last">Show Last</option>
										</select>
									</formfield>
									<input class="btn btn-success eve-text pull-right" style="margin-top: 5px; margin-bottom: 10px; font-size: 125%" type="submit" value="Create Skill Group">
								</form>
							</div>
							<div class="col-md-6 col-sm-6">
								<h3 class="eve-text" style="text-align: center">Delete A Skill Group</h3>
								<form method="post" action="/skillplans/editmode/">
									<input type="hidden" name="skillplan_id" value="<?php echo $skillplan['skillplan_id']; ?>">
									<label for="delete_subgroup_id">Select Skill Group To Delete:</label>
									<select name="delete_subgroup_id" class="form-control" style="background-color: transparent;">
										<option style="background-color: rgb(23,23,23)" value="Do Not Delete">Select A Skill Group Below</option>
										<?php
										foreach($skillplan['groups'] as $group) {
											?>
											<option style="background-color: rgb(23,23,23)" value="<?php echo $group['subgroup_id']; ?>"><?php echo $group['subgroup_name'];?></option>
											<?php
										}
										?>
									</select>
									<input class="btn btn-danger eve-text pull-right" style="margin-top: 5px; margin-bottom: 10px; font-size: 125%" type="submit" value="Delete Skill Group">
								</form>
							</div>
						</div>
						<?php
					}

					$groupsCount = count($skillplan['groups']);

					if($groupsCount >= 1) {

						foreach($skillplan['groups'] as $group) {
							if($i % 2 == 1) {
								?><div class="row"><?php
							}
							?>
							<div class="col-md-6 col-sm-12" style="margin-bottom: 15px; padding-left: 0px; padding-right: 0px">
								<div class="opaque-section" style="margin-left: 10px; margin-right: 10px; background-image: none">
									<div class="row box-title-section">
										<h2 style="text-align: center"><?php echo $group['subgroup_name']; ?></h2>
									</div>
									<div class="row">
									<?php
									$skillGroupFailures = 0;
									// Looping through each skill to see if the prerequisites are met
									
									if(count($group['skills']) >= 1){
										foreach($group['skills'] as $skill) {
											if($skill['status_icon'] != 'skill-meets-requirement') {
												// PreRequisites are NOT met
												$skillGroupFailures++;
											}
										}

										// Checking to see if we've met all the skills in this group
										if($skillGroupFailures == 0) {
											// We have, so we'll show the pretty big indicator
											?>
												<div class="row box-title-section" role="tab" id="<?php echo $group['subgroup_id']; ?>SubGroupHeading">
													<a style="text-decoration: none;" role="button" data-toggle="collapse" data-parent="AddSkillPlan" href="#collapseSubgroup<?php echo $group['subgroup_id']; ?>" aria-expanded="true" aria-controls="collapseSubgroup<?php echo $group['subgroup_id']; ?>">
														<h2 class="eve-text" style="text-align: center; color: #01b43a; margin-top: 10px">All Skills Are Trained. Click To Show Skills.</h2>
													</a>
												</div>
											<?php
										}
											
										
										?>
										<div id="collapseSubgroup<?php echo $group['subgroup_id']; ?>" class="panel-collapse collapse <?php if($skillGroupFailures == 0) { echo 'out'; } else { echo 'in'; } ?>" role="tabpanel" aria-labelledby="<?php echo $group['subgroup_id']; ?>SubGroupHeading">
											<table class="table table-striped">
												<tr>
													<th></th>
													<th style="text-align: center">Skill Name</th>
													<th style="text-align: center">Required Level</th>
													<th style="text-align: center">Trained Level</th>
													<th  class="hidden-xs-sm" style="text-align: center">Notes</th>
												</tr>
												<?php
												foreach($group['skills'] as $skill) {
													?>
													<tr style="text-align: center">
														<td class="<?php echo $skill['status_icon']; ?>"></td>
														<td><?php echo $skill['skill_typename']; ?></td>
														<td><?php echo $skill['skill_level']; ?></td>
														<td><?php if($skill['trained_level'] == NULL) { echo 'Untrained'; } else { echo $skill['trained_level']; } ?></td>
														<td class="hidden-xs-sm"><?php if($request['action'] == 'editmode' AND $user->getDirectorAccess()) { ?><form method="post" action="/skillplans/editmode/"><input type="hidden" name="subgroup_id" value="<?php echo $group['subgroup_id']; ?>"><input type="hidden" name="delete_skill_id" value="<?php echo $skill['skill_typeid']; ?>"><input type="submit" value="Delete Skill" class="btn btn-sm btn-danger eve-text"></form><?php } elseif ($skill['trained_level'] == NULL) { echo $skill['button']; } ?></td>
													</tr>
													<?php
												}
												?>
											</table>
										</div>
										<?php

									} else {
										?>
										<div class="row box-title-section">
											<h2 style="text-align: center">There Are Currently No Skills In This Group</h2>
										</div>
										<?php
									}

									if($request['action'] == 'editmode' AND $user->getDirectorAccess()) {
										?>
										<form method="post" action="/skillplans/editmode/" style="margin-top: 5px">
											<input type="hidden" name="subgroup_id" value="<?php echo $group['subgroup_id']; ?>">
											<label for="new_skill_name">Add A New Skill:</label><br />
											<input class="form-control" style="width: 45%; float: left" type="text" placeholder="Add New Skill" name="new_skill_name">
											<input class="form-control" style="width: 45%; float: left; margin-left: 10px; clear: right;" type="text" placeholder="Required Level" name="required_skill_level">
											<input class="btn btn-primary" style="margin-top: 10px" type="submit" value="Add Skill to Plan">
										</form>
										<?php
									}
									?>
									</div>	
								</div>
							</div>
							<?php
							if($i % 2 == 0 OR $i == $groupsCount) {
								?></div><?php
							}
							$i++;
						}
					} else {
						?>
						<div class="row box-title-section">
							<h2 style="text-align: center">This Skill Plan Is Currently Empty</h2>
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