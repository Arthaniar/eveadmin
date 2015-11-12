<?php

/**
 * Class to integrate user account handling and processing to EveAdmin Auth System
 * @author Josh Grancell <josh@joshgrancell.com>
 * @copyright (c) 2015 Josh Grancell
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2.0
 */

class User {
	private $db;
	private $uid;
	private $userName;
	private $group;
	private $defaultCharacter;
	private $defaultID;
	private $defaultKeyID;
	private $defaultVCode;
	private $defaultAccessMask;
	private $loginStatus;
	private $error;
	private $userAccess;
	private $directorAccess;
	private $ceoAccess;
	private $SIGAccess;
	private $adminAccess;

	/**
	 * Class constructor. Called on every page load.
	 * @param $db PDO object
	 */

	public function __construct($db) {
		$this->db = $db;

		if(isset($_SESSION['sid'])) {
			$stmt = $db->prepare('SELECT * FROM sessions WHERE sid = ? ORDER BY expire DESC LIMIT 1');
			$stmt->execute(array($_SESSION['sid']));
			$session_information = $stmt->fetch(PDO::FETCH_ASSOC);

			//Verifying if there is a saved session with this session id
			if($stmt->rowCount() == 1) {
				$stmt= $db->prepare('SELECT * FROM user_accounts WHERE uid = ? LIMIT 1');
				$stmt->execute(array($session_information['uid']));
				$user_info = $stmt->fetch(PDO::FETCH_ASSOC);
				$this->uid = $user_info['uid'];
				$this->userName = $user_info['username'];
				$this->defaultCharacter = $user_info['defaultname'];
				$this->defaultID = $user_info['defaultid'];
				$this->group = $user_info['gid'];
				$this->loginStatus = TRUE;
				$this->error = FALSE;
				
				$this->setAccess($user_info['access']);
				$this->setSIGAccess();

				$stmt = $db->prepare('SELECT * FROM characters WHERE charid = ? LIMIT 1');
				$stmt->execute(array($user_info['defaultid']));
				$characterInfo = $stmt->fetch();

				$this->keyID = $characterInfo['userid'];

				$stmt = $db->prepare('SELECT * FROM user_apikeys WHERE userid = ? LIMIT 1');
				$stmt->execute(array($this->keyID));
				$keyInfo = $stmt->fetch();

				$this->vCode = $keyInfo['vcode'];
				$this->defaultAccessMask = $keyInfo['mask'];
			} else {
				//No session saved - they need to log in
				$this->loginStatus = FALSE;
				$this->error = "No Session Saved";
			}
		} else {
			//There is no valid hash in session - they need to log in
			$this->loginStatus = FALSE;
			$this->error = "No valid hash in session";
		}	
	}

	/**
	 * Class constructor. Called on every page load.
	 * @param $ip string (IP Address)
	 * @param $db PDO object
	 */

	public static function bruteCheck($ip, $db) {
		$time = time() - (BRUTE_DISALLOW * 60);

		// Checking for brute attempts by IP
		$stmt_ip = $db->prepare('SELECT * FROM core_brute WHERE ip = ? AND last_attempt >= ?');
		$stmt_ip->execute(array($ip,
							    $time));
		$ipCount = $stmt_ip->rowCount();

		$stmt_permanence = $db->prepare('SELECT * FROM core_brute WHERE ip = ? AND permanence = 1');
		$stmt_permanence->execute(array($ip));
		$permBlock = $stmt_permanence->rowCount();

		if ($ipCount >= BRUTE_ATTEMPTS OR $permBlock >= 1) {
			return FALSE;
		} else {
			return TRUE;
		}
	}

	public static function bruteInsert($ip, $username, $db) {
		$stmt = $db->prepare('INSERT INTO core_brute (ip,num_attempts,last_username,last_attempt) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE num_attempts = num_attempts + 1,last_username=VALUES(last_username),last_attempt=VALUES(last_attempt)');
		$stmt->execute(array($ip,
							 1,
							 $username,
							 time()));
		setAlert('danger', "Login Invalid", '');
	}

	public static function doLogin($username, $submitted_password, $allow_login, $ip, $db) {
		if($allow_login) {

			//Checking to see if the Username exists in the database
			$stmt = $db->prepare("SELECT * FROM user_accounts WHERE username=? LIMIT 1");
			$stmt->execute(array($username));
			$loginInfo = $stmt->fetch(PDO::FETCH_ASSOC);

			if(isset($loginInfo['uid']))	{
				if($loginInfo['lockdown'] == 1) {
					setAlert('danger', 'Account Locked', 'This account has been locked by an Administrator, and cannot be used to log in.');
				} elseif(password_verify($submitted_password, $loginInfo['password'])) {
					//Password is valid - Setting a blank session hash string
					$random_string = generateRandom(32);
					$_SESSION['sid'] = $random_string;
					$stmt = $db->prepare('INSERT INTO sessions (sid,uid,expire) VALUES (?,?,?)');
					$stmt->execute(array($random_string,
										 $loginInfo['uid'],
										 time()));
					// Adding the most recent login time to the database information
					$stmt = $db->prepare('UPDATE user_accounts SET last_login = ? WHERE uid = ?');
					$stmt->execute(array(time(), $loginInfo['uid']));
					header('Location: '.SITE_ADDRESS.'/dashboard');
				} else {
					//The password is invalid - adding this request to the brute table
					User::bruteInsert($ip, $username, $db);
	        		$_SESSION['alert-subtext'] = "The username or password that you have entered is invalid.";
				}
			} else {
				//The username doesn't exist
				User::bruteInsert($ip, $username, $db);
	    		$_SESSION['alert-subtext'] = "The username or password that you have entered is invalid.";
			}
		} else {
			//This ip is brute-banned. They cannot log in.
			setAlert('danger', 'IP Address Banned', 'The IP Address you are connecting from has been temporarily banned due to repeated failed login attempts. Please try again later.');
		}
	}

	public function doLogout() {
		$_SESSION = array();
		session_destroy();
		?><meta http-equiv="refresh" content="0;URL='<?php echo SITE_ADDRESS; ?>'" /> <?php
	}	

	public static function doRegistration ($username, $password, $verify_pwd, $characterID, $db) {
		if($password === $verify_pwd) {
			$stmt = $db->prepare("SELECT * FROM user_accounts WHERE username = ?");
			$stmt->execute(array($username));
			$count = $stmt->rowCount();

			if($count >= 1)	{
				//Username is taken
				setAlert('danger', 'Username Unavailable', 'The username you have requested is not available for registration.');	
				return FALSE;	
			} else {
				//Username is available
				$options = [ 'cost' => HASH_COST ];

				// Hasing the password to save it into the DB
				$hash = password_hash($password, PASSWORD_BCRYPT, $options);

				// Defaulting the group code to 0, to prevent unauthorized access
				$gid = 0;
				$memberAccess = 'No Access';

				// Looking up the provided groupcode
				if(isset($groupcode) AND $groupcode != 'No group code provided') {
					$stmt = $db->prepare('SELECT * FROM group_groups WHERE jointoken = ?');
					$stmt->execute(array($groupcode));
					$group = $stmt->fetch(PDO::FETCH_ASSOC);

					if(isset($group['gid'])) {
						$gid = $group['gid'];
						$memberAccess = 'Member';
					}
				}

				// Creating the account.
				$stmt = $db->prepare("INSERT INTO user_accounts (username,password,defaultname,defaultid,access,gid) VALUES (?,?,?,?,?,?)");
				$stmt->execute(array($username,
									 $hash,
									 $username,
									 $characterID,
									 $memberAccess,
									 $gid));
				setAlert('success', 'Registration Successful', '<a href="index.php">Please click here to sign in.</a>');
				return TRUE;
			}

		} else {
			setAlert('danger', 'Password Mismatch', 'The passwords that you have provided do not match. Please try re-typing them.');
			return FALSE;
		}
	}

	public function setNewPassword($uid, $newPassword) {
		$options = [ 'cost' => HASH_COST ];

		$hash = password_hash($newPassword, PASSWORD_BCRYPT, $options);
		if($hash !== FALSE AND $hash !== NULL) {
			$stmt = $this->db->prepare("UPDATE user_accounts SET password = ? WHERE uid = ?");
			$stmt->execute(array($hash,
								 $uid));
			setAlert('success', 'Password Updated', 'Your password has been updated. That is pretty awesome.');
		} else {
			setAlert('danger', 'Internal Server Error - P101', 'Password update failed, please contact the site Administrator');
		}
	}

	public function setDefault($characterName, $characterID) {
	    $sql = "UPDATE user_accounts SET defaultname = ?, defaultid = ?  WHERE uid = ?"; 
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array($characterName,
							 $characterID,
							 $this->uid));
		$this->defaultCharacter = $characterName;
		$this->defaultID = $characterID;
	}

	public static function deleteUser($uid) {
		global $db;

		$stmt =  $db->prepare('SELECT * FROM user_apikeys WHERE uid = ?');
		$stmt->execute(array($uid));
		$keys = $stmt->fetchAll(PDO::FETCH_ASSOC);

		foreach($keys as $key) {
			ApiKey::deleteKey($key['userid'], $uid);
		}

		$stmt = $db->prepare('SELECT * FROM user_applications WHERE uid = ?');
		$stmt->execute(array($uid));
		$apps = $stmt->fetchAll(PDO::FETCH_ASSOC);

		foreach($apps as $app) {
			$stmt = $db->prepare('DELETE FROM group_application_comments WHERE application_id = ?');
			$stmt->execute(array($app['application_id']));
		}

		$stmt = $db->prepare('DELETE FROM user_applications WHERE uid = ?');
		$stmt->execute(array($uid));

		$stmt = $db->prepare('DELETE FROM user_accounts WHERE uid = ?');
		$stmt->execute(array($uid));
	}

	public function setAccess($access) {
		// Setting the access level to false for all, and the switching on as needed.
		$this->userAccess = FALSE;
		$this->directorAccess = FALSE;
		$this->ceoAccess = FALSE;
		$this->adminAccess = FALSE;

		if($access == 'Member') {
			$this->userAccess = TRUE;
		}

		if($access == "Director") {
			$this->directorAccess = TRUE;			
		}
		if($access == "CEO") {
			$this->ceoAccess = TRUE;
			$this->directorAccess = TRUE;
		}
		if($access == "Admin"){
			$this->adminAccess = TRUE;
			$this->ceoAccess = TRUE;
			$this->directorAccess = TRUE;
		}
	}

	public function setSIGAccess() {
		$stmt = $this->db->prepare('SELECT * FROM user_sig_access WHERE uid = ?');
		$stmt->execute(array($this->uid));

		$this->SIGAccess = array();

		foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $access) {
			$this->SIGAccess[$access['sig_name']] = $access['sig_access'];
		}
	}

	public static function fetchUserName($uid) {
		global $db;
		$stmt = $db->prepare('SELECT username FROM user_accounts WHERE uid = ?');
		$stmt->execute(array($uid));
		$account = $stmt->fetch(PDO::FETCH_ASSOC);

		return $account['username'];
	}

	// Checking the API Mask to see if we have access to a section
	public function checkAPIAccess($requiredMask) {
		$stmt = $this->db->prepare('SELECT userid FROM characters WHERE charid = ?');
		$stmt->execute(array($this->defaultID));
		$keyID = $stmt->fetch(PDO::FETCH_ASSOC);

		$stmt = $this->db->prepare('SELECT mask FROM user_apikeys WHERE userid = ?');
		$stmt->execute(array($keyID['userid']));

		$mask = $stmt->fetch(PDO::FETCH_ASSOC);

		if($mask['mask'] & $requiredMask) {
			$success = TRUE;
		} else {
			$success = FALSE;
		}

		return $success;
	}

	// OO-based functionality

	public function getUID() {
		return $this->uid;
	}

	public function getUserName() {
		return $this->userName;
	}

	public function getGroup() {
		return $this->group;
	}

	public function getDefaultCharacter() {
		return $this->defaultCharacter;
	}

	public function getDefaultID() {
		return $this->defaultID;
	}

	public function getDefaultKeyID() {
		return $this->defaultKeyID;
	}

	public function getDefaultVCode() {
		return $this->defaultVCode;
	}

	public function getDefaultAccessMask() {
		return $this->defaultAccessMask;
	}

	public function getLoginStatus() {
		return $this->loginStatus;
	}

	public function getError() {
		return $this->error;
	}

	public function getAdminAccess() {
		return $this->adminAccess;
	}

	public function getUserAccess() {
		if($this->adminAccess OR $this->ceoAccess OR $this->directorAccess OR $this->userAccess) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	public function getFleetCommanderAccess() {
		if(isset($this->SIGAccess['Fleet Command']) AND $this->SIGAccess['Fleet Command'] == TRUE) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	public function getHumanResourcesAccess() {
		return $this->humanResourcesAccess;
	}

	public function getDirectorAccess() {
		if($this->adminAccess OR $this->ceoAccess OR $this->directorAccess) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	public function getCEOAccess() {
		if($this->adminAccess OR $this->ceoAccess) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
}
?>
