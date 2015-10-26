<?php

class HTML {
	public static function notLoggedIn() {
		//**********Not Logged In**********//
		setAlert('danger', 'Not Logged In', 'You are not currently logged in, and cannot access this page. Please log in.')
		?>
		    <div class="container">
		    	<div class="page-header" style="margin-top: -25px">
		    		<h1>Access Denied</h1>
		    	</div>
		    	<?php showAlerts(); ?>
		    </div> <!-- /container -->
		<?php
	}

	public static function accessDenied() {
		?>
			<div class="container">
		        <div class="starter-template">
		          <h1>Access Denied</h1>
		          <p class="lead">You have requested access to a page you shouldn't have. Stop that.</p>
		        </div>
		        <?php showAlerts(); ?>
	    	</div> <!-- /container -->
	    <?php
	}

	public static function APINoAccess() {
		?>
			<div class="container">
		        <div class="starter-template">
		          <h1>Access Denied</h1>
		          <p class="lead">The API Key that you have provided does not allow access to this page. <a href="account.php?page=optional">See the API Key guide for the correct masks for this tool.</a></p>
		        </div>
		        <?php showAlerts(); ?>
	    	</div> <!-- /container -->
	    <?php
	}

	public static function pageNotFound() {
		?>
			<div class="container">
		        <div class="starter-template">
		          <h1>Page Not Found</h1>
		          <p class="lead">The page that you have requested was not found. EveAdmin is still in active development and is available as an Alpha, which means any features have not been completed. If you believe you have come to this page in error, please submit a bug.</a></p>
		        </div>
		        <?php showAlerts(); ?>
	    	</div> <!-- /container -->
	    <?php
	}

}