<?php
require_once('includes/header.php');
use Pheal\Pheal;
use Pheal\Core\Config;
Config::getInstance()->cache = new \Pheal\Cache\MemcacheStorage();
Config::getInstance()->access = new \Pheal\Access\StaticCheck();

// Getting the compliance type
if($request['action'] == 'api') {

	if($request['value'] == 'refresh') {
		$key = new ApiKey($_POST['keyID'], $_POST['vCode'], $_POST['uid'], $db);
		if($key->getKeyStatus() == 1 AND $key->getAccessMask() & MINIMUM_API) {
			$update = $key->updateApiKey();
			if($update) {
				foreach($key->getCharacters() as $character) {
					$char = new Character($character['characterID'], $key->getKeyID(), $key->getVCode(), $key->getAccessMask(), $db, $user);
					if($char->getExistance() OR $char->getExistance() == FALSE) {
						$char->updateCharacterInfo();
					}
				}
				$refresh = $key->refreshAPIKey();
				setAlert('success', 'API Key Refreshed', 'The API key has been successfully refreshed.');
								
			}
		} elseif(!($key->getAccessMask() & MINIMUM_API) AND $key->getKeyStatus() == 1) {
			setAlert('danger', 'The API Key Does Not Meet Minimum Requirements', 'The required minimum Access Mask for API keys is '.MINIMUM_API.'. Please create a new key using the Create Key link.');
		}
	} 
	// We're doing API compliance
	$compliance_type = "API";

	// Getting a full API-pulled member list
    $pheal = new Pheal($settings->getCorpUserID(), $settings->getCorpVCode(), 'corp');
    $response = $pheal->MemberTracking(array("extended" => 1));
    $memberList = array();
    foreach($response->members as $member) {
      $memberList[$member->name]['name'] = $member->name;
      $memberList[$member->name]['id'] = $member->characterID;
    }
    ksort($memberList, SORT_NATURAL | SORT_FLAG_CASE);

	// Working through the member list
	$stmt = $db->prepare('SELECT * FROM characters WHERE charid = ?');
	$stmt_api = $db->prepare('SELECT * FROM user_apikeys WHERE userid = ?');

} elseif($request['action'] == 'doctrine') {
	$compliance_type = 'Doctrine';

	// Getting all of the doctrines for the group
	$stmt = $db->prepare('SELECT * FROM doctrines WHERE gid = ? ORDER BY doctrine_name ASC');
	$stmt->execute(array($user->getGroup()));
	$doctrineList = $stmt->fetchAll(PDO::FETCH_ASSOC);
	// Set the iterative that we'll use for tab functionality to 0
	$doctrineIterative = 0;

} elseif($request['action'] == 'skill') {
	$compliance_type = 'Skill';

	// Getting all of our skill plans
	$stmt = $db->prepare('SELECT * FROM skillplan_main WHERE gid = ? ORDER BY skillplan_order ASC');
	$stmt->execute(array($user->getGroup()));
	$skillPlans = $stmt->fetchAll(PDO::FETCH_ASSOC);

	// Setting the iterative that we will use for collapse groups to 0
	$skillPlanIterative = 0;
}
?>
<div class="opaque-container">
    <div class="row" style="width: 100%; margin-top: 20px; margin-bottom: 20px">
		<div class="col-md-12 opaque-section" style="padding: 0px">
			<div class="row box-title-section">
				<h1 style="text-align: center"><?php echo $compliance_type; ?> Compliance<h1>
			</div>
			<?php showAlerts(); ?>
			<div class="row" style="padding-left: 10px; padding-right: 10px">
					<?php
					if($compliance_type == "API") {
						?>
						<table class="table table-striped">
						<thead>
							<tr>
								<th>Character</th>
								<th style="text-align: center">User Account</th>
								<th style="text-align: center">API Key ID</th>
								<th style="text-align: center">API Access Mask</th>
								<th style="text-align: center">Key Type</th>
								<th style="text-align: center">Key Status</th>
								<th style="text-align: center">Key Actions</th>
							</tr>
						</thead>
						<tbody>
						<?php
						foreach($memberList as $member) {

							$failure = 0;

							$stmt->execute(array($member['id']));
							$character = $stmt->fetch(PDO::FETCH_ASSOC);

							$stmt_api->execute(array($character['userid']));
							$apikey = $stmt_api->fetch(PDO::FETCH_ASSOC);

							if(isset($character['uid'])) {
			                    $stmt_acct = $db->prepare('SELECT * from user_accounts WHERE uid = ? LIMIT 1');
			                    $stmt_acct->execute(array($character['uid']));
			                    $account_name = $stmt_acct->fetch(PDO::FETCH_ASSOC);

			                    // Checking the Mask to confirm it's what we're looking for
			                    if(isset($apikey['mask'])) {
			                    	if($apikey['mask'] == MINIMUM_API) {
			                    		$mask_display = '<span style="color: #01b43a">Full API Key</span>';
			                    	} else {
			                    		$mask_display = '<span style="color: #ac2925">'.$apikey['mask'].'</span>';
			                    		$failure = TRUE;
			                    	}
			                    } else {
			                    	$mask_display = '<span style="color: #ac2925">No API Submitted</span>';
			                    	$failure = TRUE;
			                    }

			                    // Checking the Key Type to confirm it's account-wide
			                    if(isset($apikey['keyType'])) {
			                    	if($apikey['keyType'] == 'Account') {
			                    		$type_display = '<span style="color: #01b43a">Account</span>';
			                    	} else {
			                    		$type_display = '<span style="color: #ac2925">Character</span>';
			                    		$failure = TRUE;
			                    	}
			                    } else {
			                    	$type_display = '---';
			                    	$failure = TRUE;
			                    }

			                    if(isset($apikey['keystatus'])) {
			                    	if($apikey['keystatus'] == 1) {
			                    		$status_display = '<span style="color: #01b43a">Valid Key</span>';
			                    	} else {
			                    		$status_display = '<span style="color: #ac2925">Invalid Key</span>';
			                    		$failure = TRUE;
			                    	}
			                    } else {
			                    	$status_display = '---';
			                    	$failure = TRUE;
			                    }

			                    if($failure) {
			                    	$background = 'class="opaque-danger"';
			                    } else {
			                    	$background = '';
			                    }

			                    // Checking the validity of the key
			                    ?>
			                    	<tr <?php echo $background; ?>>
			                    		<td><?php echo $member['name']; ?></td>
			                    		<td style="text-align: center"><a href="/spycheck/<?php echo $character['uid']; ?>/"><?php echo $account_name['username']; ?></a></td>
			                    		<td style="text-align: center"><?php echo $apikey['userid']; ?></td>
			                    		<td style="text-align: center"><?php echo $mask_display;?></td>
			                    		<td style="text-align: center"><?php echo $type_display;?></td>
			                    		<td style="text-align: center"><?php echo $status_display;?></td>
			                    		<td style="text-align: center">
			                    			<form method="post" action="/compliance/api/refresh/">
			                    				<input type="hidden" name="keyID" value="<?php echo $apikey['userid']; ?>">
			                    				<input type="hidden" name="vCode" value="<?php echo $apikey['vcode']; ?>">
			                    				<input type="hidden" name="uid" value="<?php echo $apikey['uid']; ?>">
			                    				<button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-refresh"></span></button>
			                    			</form>
			                    		</td>
			                    	</tr>
			                    <?php
							} else {
								?>
								<tr class="opaque-danger">
									<td><?php echo $member['name']; ?></td>
									<td style="text-align: center">No Account</td>
									<td style="text-align: center">No API Key</td>
									<td style="text-align: center">---</td>
									<td style="text-align: center">---</td>
									<td style="text-align: center">---</td>
									<td style="text-align: center">---</td>
								</tr>
								<?php
							}
						}
						?>
						</tbody>
						</table>
						<?php
					} elseif($compliance_type == "Doctrine") {

						$stmt = $db->prepare('SELECT doctrineid,doctrine_name FROM doctrines WHERE gid = ? ORDER BY doctrine_name ASC');
						$stmt->execute(array($user->getGroup()));
						$doctrines = $stmt->fetchAll(PDO::FETCH_ASSOC);

						$stmt_fittings = $db->prepare('SELECT fittingid,fitting_name,fitting_ship FROM doctrines_fits WHERE doctrineid = ? ORDER BY fitting_priority DESC');
						$stmt_accounts = $db->prepare('SELECT uid,username FROM user_accounts WHERE gid = ? ORDER BY username ASC');
						$stmt_tracking = $db->prepare('SELECT characters.charid,characters.charactername,usable_items,total_items,color_status FROM characters JOIN doctrines_tracking ON characters.charid = doctrines_tracking.charid WHERE characters.uid = ? AND doctrines_tracking.fittingid = ? AND characters.skillpoints > 1000000 ORDER BY characters.skillpoints DESC');

						$stmt_accounts->execute(array($user->getGroup()));
						$accounts = $stmt_accounts->fetchAll(PDO::FETCH_ASSOC);

						$i = 0;
						foreach($doctrines as $doctrine) {
							$i_doctrines = 1;
							$stmt_fittings->execute(array($doctrine['doctrineid']));
							$fittings = $stmt_fittings->fetchAll(PDO::FETCH_ASSOC);

							$fittingsCount = count($fittings);
							?>
							<div class="opaque-container panel-group" style="width: 100%; margin-top: 20px; margin-bottom: 20px" id="<?php echo $doctrine['doctrineid']; ?>" role="tablist" aria-multiselectable="true">
								<div class="row">
									<div class="col-md-12 opaque-section">
										<div class="row bog-title-section" style="margin-bottom: 10px" role="tab" id="<?php echo $doctrine['doctrineid']; ?>planHeading>">
											<a class="box-title-link" style="text-decoration: none" role="button" data-toggle="collapse" data-parent="<?php echo $doctrine['doctrineid']; ?>plan" href="#collapse<?php echo $doctrine['doctrineid']; ?>plan" aria-expanded="true" aria-controls="collapse<?php echo $doctrine['doctrineid']; ?>">
												<h2 class="eve-text" style="margin-top: 0px; text-align: center; font-size: 200%; font-weight: 700"><?php echo $doctrine['doctrine_name']; ?> Doctrine</h2>
											</a>
										</div>
										<div id="collapse<?php echo $doctrine['doctrineid']; ?>plan" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="<?php echo $plan['skillplan_id']; ?>planHeading">
											<?php
											foreach($fittings as $fitting) {
												if($i_doctrines % 2 == 1) {
													?><div class="row"><?php
												}
												?>
										        <div class="col-md-6 col-sm-12" style="background-image: none" id="<?php echo $fitting['fittingid']; ?>" role="tablist" aria-multiselectable="true">
										          	<div style="vertical-align: middle">
										            	<div class="row box-title-section" role="tab" id="heading<?php echo $i; ?>" style="text-align: center; background-image: none; background-color: transparent; font-size: 150%; padding-top: 5px; padding-bottom: 5px">
										              		<a style="text-decoration: none; color: #f5f5f5; font-weight: 700" role="button" data-toggle="collapse" data-parent="<?php echo $fitting['fittingid']; ?>" href="#collapse<?php echo $i; ?>" aria-expanded="true" aria-controls="collapse<?php echo $i; ?>">
										              			<h2 class="eve-text" style="font-size: 120%"><?php echo $fitting['fitting_name']; ?></h2>
										              		</a>
										            	</div>
										            	<div id="collapse<?php echo $i; ?>" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="heading<?php echo $i; ?>">
										              		<table class="table table-striped">
										              			<tr>
										              				<th>Account</th>
										              				<th>Can Use</th>
										              				<th>Missing Some Skills</th>
										              				<!--<th>Cannot Use</th>-->
										              			</tr>
										              			<?php
										              			foreach($accounts as $account) {
										              				$stmt_tracking->execute(array($account['uid'], $fitting['fittingid']));
										              				$tracking = $stmt_tracking->fetchAll(PDO::FETCH_ASSOC);

										              				$tracking_success = 0;
										              				$tracking_warning = 0;

										              				foreach($tracking as $track) {
										              					if($track['color_status'] == 'success') {
										              						$tracking_success = 1;
										              						break;
										              					} elseif($track['color_status'] = 'warning') {
										              						$tracking_warning += 1;
										              					}
										              				}


										              				if($tracking_success == 1) {
										              					$tr_class = 'opaque-success';
										              				} elseif($tracking_warning >= 1) {
										              					$tr_class = 'opaque-warning';
										              				} else {
										              					$tr_class = 'opaque-danger';
										              				}
										              				?>
										              				<tr <?php echo 'class="'.$tr_class.'"'; ?>>
										              					<td><?php echo $account['username']; ?></td>
										              					<td><?php foreach($tracking as $track) { if($track['color_status'] == 'success') { echo '<img style="margin-left: 2px; margin-right: 2px" data-toggle="tooltip" data-placement="top" title="'.$track['charactername'].'" src="'.Character::getCharacterImage($track['charid'], 32).'">'; } } ?></td>
										              					<td><?php foreach($tracking as $track) { if($track['color_status'] == 'warning') { echo '<img style="margin-left: 2px; margin-right: 2px" data-toggle="tooltip" data-placement="top" title="'.$track['charactername'].'" src="'.Character::getCharacterImage($track['charid'], 32).'">'; } } ?></td>
										              					<!--<td><?php foreach($tracking as $track) { if($track['color_status'] == 'failure') { echo '<img style="margin-left: 2px; margin-right: 2px" data-toggle="tooltip" data-placement="top" title="'.$track['charactername'].'" src="'.Character::getCharacterImage($track['charid'], 32).'">'; } } ?></td>-->
										              				</tr>
										              				<?php
										              			}
										              			?>
										              		</table>
										            	</div>
										          	</div>
										        </div>
												<?php
												$i++;
												if($i_doctrines % 2 == 0 OR $i_doctrines == $fittingsCount) {
													?></div><?php
												}
												$i_doctrines++;
											}
											?>
										</div>
									</div>
								</div>
							</div>
							<?php
						}

					} elseif($compliance_type == 'Skill') {
						?>
						 <div class="tab-content col-md-9">
						    <?php
						    // Looping through the skill plans to create the subgroup panes
						    $skillPlanIterative = 0;
						    $stmt = $db->prepare('SELECT * FROM skillplan_subgroups WHERE skillplan_id = ? ORDER BY subgroup_order ASC');
						    foreach($skillPlans as $plan) {
						      $stmt->execute(array($plan['skillplan_id']));
						      $subGroups = $stmt->fetchAll(PDO::FETCH_ASSOC);
						      if($skillPlanIterative == 0) {
						        $planClass = 'active';
						      } else {
						        $planClass = '';
						      }
						      ?>
						      <div role="tabpanel" class="tab-pane <?php echo $planClass; ?>" id="tab<?php echo $skillPlanIterative; ?>">
							      <?php
							      $stmtSkills = $db->prepare('SELECT * FROM skillplan_tracking WHERE subgroup_id = ? ORDER BY character_name ASC');
							      foreach($subGroups as $subgroup) {
							        ?>
							        <h3 style="text-align: center; margin-top: 0px"><?php echo $subgroup['subgroup_name']; ?></h3>
							        <div class="panel panel-default">
							          <table class="table">
							            <tr>
							              <th style="background-color: #010102"></th>
							              <th style="background-color: #010102; text-align: center">Character Name</th>
							              <th style="background-color: #010102; text-align: center">Skills Met</th>
							              <th style="background-color: #010102; text-align: center">Total Skills</th>
							            </tr>
							            <?php
							            $stmtSkills->execute(array($subgroup['subgroup_id']));
							            $skills = $stmtSkills->fetchAll(PDO::FETCH_ASSOC);
							            foreach($skills as $skill) {
							           
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
							            ?>
							          </table>
							        </div>
							        <?php
							      }
							      ?>
						      </div>
						      <?php
						      $skillPlanIterative++;
						    }
						    ?>
						  </div>
						  <?php
					}
					?>
			</div>
		</div>	
    </div>

</div>
<?php
require_once('includes/footer.php');
