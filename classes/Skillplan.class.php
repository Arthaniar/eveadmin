<?php
class Skillplan {

	private $db;
	private $plans = array();
	private $groupID;
	private $userID;
	private $characterID;

	public function __construct($db, $groupID, $characterID){
		$this->db = $db;
		$this->groupID = $groupID;
		$this->characterID = $characterID;

		$stmt = $db->prepare('SELECT * FROM skillplan_main WHERE gid = ? ORDER BY skillplan_order ASC');
		$stmt->execute(array($groupID));
		$skillplans = $stmt->fetchAll(PDO::FETCH_ASSOC);

		foreach($skillplans as $skillplan) {
			$this->plans[$skillplan['skillplan_id']] = $skillplan;
			$this->plans[$skillplan['skillplan_id']]['groups'] = $this->getSkillplanSubGroups($skillplan['skillplan_id']);
		}
	}

	private function getSkillplanSubGroups($skillplan_id) {
		$subGroupArray = array();

		$stmt = $this->db->prepare('SELECT * FROM skillplan_subgroups WHERE skillplan_id = ? ORDER BY subgroup_order ASC');
		$stmt->execute(array($skillplan_id));
		$subgroups = $stmt->fetchAll(PDO::FETCH_ASSOC);

		foreach($subgroups as $subgroup) {
			$subGroupArray[$subgroup['subgroup_id']] = $subgroup;
			$subGroupArray[$subgroup['subgroup_id']]['skills'] = $this->getSubGroupSkills($subgroup['subgroup_id']);
		}

		return $subGroupArray;
	}

	private function getSubGroupSkills($subgroup_id) {
		$stmt = $this->db->prepare('SELECT * FROM skillplan_skills WHERE subgroup_id = ? ORDER BY skill_typename ASC');
		$stmt->execute(array($subgroup_id));
		$skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$stmt = $this->db->prepare('SELECT * FROM user_skills WHERE charid = ? AND skillid = ? LIMIT 1');

		$skillsArray = array();

		foreach($skills as $skill) {
			$stmt->execute(array($this->characterID, $skill['skill_typeid']));
			$training = $stmt->fetch(PDO::FETCH_ASSOC);

			$skillsArray[$skill['skill_typeid']] = $skill;

			if($skill['skill_typeid'] == 21667) {
			}

			if(!isset($training['level']) OR $training['level'] == NULL) {

				$preRequisiteTest = testPreReqs($skill['skill_typeid'], $this->characterID);

				if($skill['skill_typeid'] == 21667) {
				}

				if($preRequisiteTest['status']) {
					$skillsArray[$skill['skill_typeid']]['requirements'] = TRUE;
					$skillsArray[$skill['skill_typeid']]['button'] = '<button style="padding: 1px 10px" type="button" class="eve-text btn btn-primary btn-sm" onclick="CCPEVE.showMarketDetails('.$skill['skill_typeid'].')">Trainable - Buy On Market</button>';
				} else {
					$skillsArray[$skill['skill_typeid']]['requirements'] = FALSE; 
					$skillsArray[$skill['skill_typeid']]['button'] = '<button class="eve-text btn btn-danger btn-sm" style="padding: 1px 10px" type="button" data-toggle="tooltip" data-placement="top" title="Missing Skills: '.$preRequisiteTest['required-skills-string'].'">Pre-Requisites Not Met</button>';
				}

				$skillsArray[$skill['skill_typeid']]['trained_level'] = NULL;
				$skillsArray[$skill['skill_typeid']]['status_icon'] = 'skill-not-trained'; 
			} elseif($training['level'] >= $skill['skill_level']) {
				$skillsArray[$skill['skill_typeid']]['trained_level'] = $training['level'];
				$skillsArray[$skill['skill_typeid']]['requirements'] = TRUE; 
				$skillsArray[$skill['skill_typeid']]['status_icon'] = 'skill-meets-requirement'; 
			} else {
				$skillsArray[$skill['skill_typeid']]['trained_level'] = $training['level'];
				$skillsArray[$skill['skill_typeid']]['requirements'] = FALSE; 
				$skillsArray[$skill['skill_typeid']]['status_icon'] = 'skill-below-requirement'; 
			}
		}

		return $skillsArray;
	}

	public static function deleteSkills($subgroup_id) {
		global $db;
		$stmtDeleteSkills = $db->prepare('DELETE FROM skillplan_skills WHERE subgroup_id = ?');
		$stmtDeleteSkills->execute(array($subgroup_id));

		$stmtDeleteTracking = $db->prepare('DELETE FROM skillplan_tracking WHERE subgroup_id = ?');
		$stmtDeleteTracking->execute(array($subgroup_id));
	}

	public static function deleteSubGroup($subgroup_id) {
		global $db;

		Skillplan::deleteSkills($subgroup_id);

		$stmt = $db->prepare('DELETE FROM skillplan_subgroups WHERE subgroup_id = ?');
		$stmt->execute(array($subgroup_id));

	}

	public static function deleteSkillPlan($skillplan_id, $groupID) {
		global $db;
		$stmt = $db->prepare('SELECT * FROM skillplan_subgroups WHERE skillplan_id = ? AND gid = ?');
		$stmt->execute(array($skillplan_id, $groupID));
		$subgroupList = $stmt->fetchAll(PDO::FETCH_ASSOC);	

		foreach($subgroupList as $subgroup) {	
			Skillplan::deleteSubGroup($subgroup['subgroup_id']);
		}

		$stmt = $db->prepare('DELETE FROM skillplan_main WHERE skillplan_id = ? AND gid = ?');
		$stmt->execute(array($skillplan_id, $groupID));
	}

	public static function renameSkillPlan($skillplan_id, $groupID, $skillplan_name) {
		global $db;

		$stmt = $db->prepare('UPDATE skillplan_main SET skillplan_name = ?  WHERE skillplan_id = ? AND gid = ?');
		$stmt->execute(array($skillplan_name, $skillplan_id, $groupID));
	}

	public function getPlans() {
		return $this->plans;
	}
}

