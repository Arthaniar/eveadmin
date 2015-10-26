<?php
require('includes/config.php');

// Getting the URI of their request so that we can parse it
$request = uriChecker();

// Checking to see if the user is logged in
if($user->getLoginStatus()) {
	// Here we are requiring different levels of access for each page
	switch($request['page']):
		case 'login':
			// The user is trying to reach the login page while logged in, redireting them to the dashboard
			$authentication = TRUE;
			$request['page'] = 'dashboard';
			break;
		case 'logout':
			// The user is attempting to logout, so lets do that.
			$authentication = TRUE;
			$user->doLogout();
			$request['page'] = 'login';
			break;
		case NULL:
		case 'dashboard':
			// Public access
		case 'register':
			// Public access
		case 'contracts':
			// Public access
		case 'alliancecontracts':
			// Public access
		case 'keys':
			// Public access
		case 'evemail':
			// Public access
		case 'skills':
			// Any access grants permission for this public page
			$authentication = TRUE;
			break;
		case 'operations':
			// Member access required
		case 'skillplans':
			// Member access required
		case 'doctrines';
			// Member access required
		case 'store':
			// Member access required
		case 'services':
			// Member access is required for this case and all of the fall-throughs above
			$authentication = $user->getUserAccess();
			break;
		case 'search':
		case 'info':
		case 'spycheck':
			// Fall through for Director Access
		case 'compliance':
			// Fall through for Director Access
		case 'manage':
			// Director access is required for this case and all of the fall-throughs above.
			$authentication = $user->getDirectorAccess();
			break;
	endswitch;

	if(!isset($authentication) OR $authentication === FALSE OR ($user->getGroup() == '0')) {
		$request['page'] == 'denied';
	}

} else {
	// The user is not logged in, so we will only let them see the login page unless they're trying to reach register or recover password
	if($request['page'] != 'recover' AND $request['page'] != 'register' AND $request['page'] != 'alliancecontracts' AND $request['action'] != 'courier') {
		$request['page'] = 'login';
	}
}

require_once(bootstrapper($request));