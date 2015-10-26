<?php
Class Doctrine {
	private $db;
	private $doctrineName;
	private $doctrineID;
	private $groupID;

	private $doctrineUse;
	private $doctrineRequirement;

	private $doctrineFits;

	public function __construct($doctrineID, $db, $groupID, $characterID) {
		// Saving the database and group id into the class
		$this->db = $db;
		$this->groupID = $groupID;
		$this->doctrineID = $doctrineID;

		// Pulling the doctrine information
		$stmt = $db->prepare('SELECT * FROM doctrines WHERE doctrineid = ? AND gid = ? LIMIT 1');
		$stmt->execute(array($this->doctrineID, $this->groupID));
		$doctrine = $stmt->fetch();

		// Saving the doctrine information into the class
		$this->doctrineName = $doctrine['doctrine_name'];
		$this->doctrineUse = $doctrine['doctrine_use'];
		$this->doctrineRequirement = $doctrine['doctrine_requirement'];

		// Setting up the iterative for the fittings array
		$i = 0;
		$this->doctrineFits = array();

		// Pulling the list of fittings.
		$stmt = $db->prepare('SELECT * FROM doctrines_fits WHERE doctrineid = ? and gid = ? ORDER BY fitting_priority DESC');
		$stmt->execute(array($this->doctrineID, $this->groupID));
		$fittings = $stmt->fetchAll(PDO::FETCH_ASSOC);

		// Creating the fittings array
		foreach($fittings as $fitting) {
			$this->doctrineFits[$i] = new Fitting($fitting['fittingid'], $this->db, $this->groupID, $characterID);
			$i++;
		}
	}

	public function getDoctrineName() {
		return $this->doctrineName;
	}

	public function getGroupID() {
		return $this->groupID;
	}

	public function getDoctrineID() {
		return $this->doctrineID;
	}

	public function getDoctrineUse() {
		return $this->doctrineUse;
	}

	public function getDoctrineRequirement() {
		return $this->doctrineRequirement;
	}

	public function getDoctrineFits() {
		return $this->doctrineFits;
	}


}