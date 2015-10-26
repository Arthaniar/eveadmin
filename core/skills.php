<?php
require_once('includes/header.php');

// Creating our prepared statements in advance
$stmt_rawskills = $db->prepare('SELECT * FROM rawskilltree WHERE groupName = ? ORDER BY typeName ASC');
$stmt_charskills = $db->prepare('SELECT * FROM user_skills  WHERE skillid = ? AND charid = ? LIMIT 1');
$stmt_skillgroups = $db->prepare('SELECT * FROM invGroups WHERE categoryID = 16 and published = 1 ORDER BY groupName ASC');
$stmt_skillname = $db->prepare('SELECT * FROM invTypes WHERE typeID = ? LIMIT 1');

// Getting the indivudual skill groups
$stmt_skillgroups->execute(array());
$groups = $stmt_skillgroups->fetchAll(PDO::FETCH_ASSOC);
$i = 0;
// Before we loop through all of the skills, we will get some preliminary information
$stmt = $db->prepare('SELECT * FROM user_skills WHERE charid = ? AND level = ?');
$stmt->execute(array($user->getDefaultID(), 5));
$level5Count = $stmt->rowCount();
$stmt->execute(array($user->getDefaultID(), 4));
$level4Count = $stmt->rowCount();
$stmt->execute(array($user->getDefaultID(), 3));
$level3Count = $stmt->rowCount();
$stmt->execute(array($user->getDefaultID(), 2));
$level2Count = $stmt->rowCount();
$stmt->execute(array($user->getDefaultID(), 1));
$level1Count = $stmt->rowCount();
$stmt->execute(array($user->getDefaultID(), 0));
$level0Count = $stmt->rowCount();
$stmt = $db->prepare('SELECT * FROM user_skills WHERE charid = ?');
$stmt->execute(array($user->getDefaultID()));
$allSkillsCount = $stmt->rowCount();

?>
<div class="opaque-container">

    <div class="row" style="width: 100%; margin-top: 20px; margin-bottom: 20px">
		<div class="col-md-12 opaque-section" style="padding: 0px">
			<div class="row box-title-section">
				<h1 style="text-align: center">Viewing All Skills for <?php echo $user->getDefaultCharacter();?></h1>
			</div>
			<?php showAlerts(); ?>
			<div class="row" style="padding-left: 10px; padding-right: 10px">
				<table class="table table-striped" style="margin-top: 15px; margin-bottom: 15px">
					<tr>
						<th style="text-align: center">Injected Skills</th>
						<th style="text-align: center">Level 1 Skills</th>
						<th style="text-align: center">Level 2 Skills</th>
						<th style="text-align: center">Level 3 Skills</th>
						<th style="text-align: center">Level 4 Skills</th>
						<th style="text-align: center">Level 5 Skills</th>
						<th style="text-align: center">Total Skills</th>
					</tr>
					<tr>
						<td style="text-align: center"><?php echo $level0Count;?></td>
						<td style="text-align: center"><?php echo $level1Count;?></td>
						<td style="text-align: center"><?php echo $level2Count;?></td>
						<td style="text-align: center"><?php echo $level3Count;?></td>
						<td style="text-align: center"><?php echo $level4Count;?></td>
						<td style="text-align: center"><?php echo $level5Count; ?></td>
						<td style="text-align: center"><?php echo $allSkillsCount;?></td>
					</tr>
				</table>
			</div>
			<?php
			$i = 0;
			foreach($groups as $group) {
				if($i % 2 == 0) {
					?><div class="row" style="margin-bottom: 15px"><?php
				}
                $stmt_rawskills->execute(array($group['groupName']));
                $skills = $stmt_rawskills->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <div class="col-md-6 col-sm-12">
                	<div class="opaque-section" style="margin-left: 10px; margin-right: 10px; background-image: none">
                    	<div class="row box-title-section">
                    		<h2 style="text-align: center"><?php echo $group['groupName']; ?> Skills</h2>
                    	</div>
                    	<div class="row" style="padding-top: 10px; padding-bottom: 10px">
                    		<table class="table table-striped">
                    			<tr>
                    				<th></th>
                    				<th style="text-align: center">Skill Name</th>
                    				<th style="text-align: center">Level</th>
                    				<th style="text-align: center">Notes</th>
                    			</tr>
			                    <?php

			                    foreach($skills as $skill) {
									// Getting the character's skills to see how they compare to the skill we're looking at
									$stmt_charskills->execute(array($skill['typeID'], $user->getDefaultID()));
									$charskill = $stmt_charskills->fetch(PDO::FETCH_ASSOC);

									if(isset($charskill['level'])) {
										$preRequisitesBadge = '';
										$level = $charskill['level'];

										switch($level):
											case 0:
												$colorClass = 'class="opaque-danger"';
												$iconClass = 'class="skill-not-trained"';
												break;
											case 1:
											case 2:
											case 3:
												$colorClass = 'class="opaque-warning"';
												$iconClass = ' class="skill-below-requirement"';
												break;
											case 4:
												$colorClass = 'class="opaque-success"';
												$iconClass = 'class="skill-meets-requirement"';
												break;
											case 5:
												$colorClass = 'class="opaque-success"';
												$iconClass = 'class="skill-exceeds-requirement"';
												break;
										endswitch;
									} else {
										// If the skill is completely untrained we're going to test the prerequisites to see if we CAN train it, or if we're missing stuff.                          
										$colorClass = 'class="opaque-danger"';
										$iconClass = 'class="skill-not-trained"';
										$level = 'Untrained';
									}

				                        ?>
										<tr <?php echo $colorClass; ?>>
											<td <?php echo $iconClass ?>></td>
											<td style="text-align: center"><?php echo $skill['typeName'];?></td>
											<td style="text-align: center"><?php echo $level ?></td>
											<td style="text-align: center"></td>
										</tr>
										<?php
			                    }
			                    ?>
			                </table>
		                </div>
		            </div>
                </div>
                <?php
				if($i % 2 == 1 OR $i == $stmt_skillgroups->rowCount()) {
					?></div><?php
				}
				$i++;
			}
			?>
		</div>	
    </div>
</div>
<?php
require_once('includes/footer.php');