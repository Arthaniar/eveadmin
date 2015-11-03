<?php

class Settings {
	private $db;

	private $groupID;

	private $corpUserID;
	private $corpVCode;

	private $voiceIntegration;
	private $voiceCommunications;
	private $voiceAddress;
	private $voicePort;

	private $groupTicker;
	private $groupName;
	private $groupOwner;
	private $groupJoinToken;

	private $forumIntegration;
	private $forumsAddress;

	private $slackIntegration;
	private $slackAddress;
	private $slackWebhook;
	private $slackAuthToken;
	private $slackOperationsNotifications;
	private $slackAPINotifications;
	private $slackMainChannel;
	private $slackAPIChannel;
	private $slackOpsChannel;

	public function __construct($db, $gid) {
		$this->db = $db;
		$this->groupID = $gid;

		// Fetching all Settings from the Settings table
		$stmt = $db->prepare('SELECT * FROM group_settings WHERE gid = ?');
		$stmt->execute(array($gid));
		$settings = $stmt->fetch(PDO::FETCH_ASSOC);

		// Setting the API information
		$this->corpUserID = $settings['corp_keyID'];
		$this->corpVCode = $settings['corp_vCode'];

		// Setting the Voice Communications information
		if($settings['group_voice_integration'] == 1) {
			$this->voiceIntegration = TRUE;
			$this->voiceCommunications = $settings['group_voice_application'];
			$this->voiceAddress = $settings['group_voice_address'];
			$this->voicePort = $settings['group_voice_port'];
		} else {
			$this->voiceIntegration = FALSE;
		}

		// Fetching the Group information
		$stmt = $db->prepare('SELECT * FROM group_groups WHERE gid = ?');
		$stmt->execute(array($gid));
		$groupInfo = $stmt->fetch();

		// Setting the Group information
		$this->groupTicker = $groupInfo['groupticker'];
		$this->groupName = $groupInfo['groupname'];
		$this->groupOwner = $groupInfo['owner'];
		$this->groupJoinToken = $groupInfo['jointoken'];

		if($settings['group_forum_integration'] == 0) {
			$this->forums = NULL;
			$this->forumsAddress = NULL;
		} else {
			$this->forumIntegration = TRUE;
			$this->forumApplication = $settings['group_forum_application'];
			$this->forumsAddress = $settings['group_forum_address'];
		}

		if($settings['group_slack_integration'] == 0) {
			$this->slackIntegration = FALSE;
			$this->slackWebhook = FALSE;
			$this->slackAuthToken = FALSE;
			$this->slackOperationsNotifications = FALSE;
			$this->slackAPINotifications = FALSE;
		} else {
			$this->slackIntegration = TRUE;
			$this->slackAddress = $settings['group_slack_address'];
			$this->slackWebhook = $settings['group_slack_webhook'];
			$this->slackAuthToken = $settings['group_slack_auth_token'];
			$this->slackOperationsNotifications = $settings['group_slack_ops_notifications'];
			$this->slackAPINotifications = $settings['group_slack_api_notifications'];
			$this->slackMainChannel = $settings['group_slack_main_channel'];
			$this->slackAPIChannel = $settings['group_slack_api_channel'];
			$this->slackOpsChannel = $settings['group_slack_ops_channel'];
		}

	}

	// Setting up the correct Voice link address
	public function getVoiceConnectionAddress($username) {
		if($this->voiceCommunications = "TS3") {
			$connection = 'ts3server://'.$this->voiceAddress.'?port='.$this->voicePort.'&nickname='.$username;
		} elseif($this->voiceCommunications = 'Mumble') {
			$connection = 'mumble://'.$username.'@'.$this->voiceAddress.':'.$this->voicePort;
		}

		return $connection;
	}

	// Publically accessible endpoints for the different class variables

	public function getCorpUserID() {
		return $this->corpUserID;
	}

	public function getCorpVCode() {
		return $this->corpVCode;
	}

	public function getGroupID() {
		return $this->groupID;
	}

	public function getVoiceIntegration() {
		return $this->voiceIntegration;
	}

	public function getVoiceCommunications() {
		return $this->voiceCommunications;
	}

	public function getVoiceAddress() {
		return $this->voiceAddress;
	}

	public function getVoicePort() {
		return $this->voicePort;
	}

	public function getGroupTicker() {
		return $this->groupTicker;
	}

	public function getGroupName() {
		return $this->groupName;
	}

	public function getGroupOwner() {
		return $this->groupOwner;
	}

	public function getGroupJoinToken() {
		return $this->groupJoinToken;
	}

	public function getForumIntegration() {
		return $this->forumIntegration;
	}

	public function getForumApplication() {
		return $this->forumApplication;
	}

	public function getForumAddress() {
		return $this->forumsAddress;
	}

	public function getSlackIntegration() {
		return $this->slackIntegration;
	}

	public function getSlackWebhook() {
		return $this->slackWebhook;
	}

	public function getSlackAuthToken() {
		return $this->slackAuthToken;
	}

	public function getSlackAPINotifications() {
		return $this->slackAPINotifications;
	}

	public function getSlackOperationsNotifications() {
		return $this->slackOperationsNotifications;
	}

	public function getSlackAddress() {
		return $this->slackAddress;
	}

	public function getSlackMainChannel() {
		return $this->slackMainChannel;
	}
	public function getSlackAPIChannel() {
		return $this->slackAPIChannel;
	}
	public function getSlackOpsChannel() {
		return $this->slackOpsChannel;
	}
}