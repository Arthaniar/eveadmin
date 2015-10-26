<?php
require('includes/header-no-nav.php');
?>

    <div class="opaque-container" style="height: 100%; padding-bottom: 50px; margin-top: 10%">

	    <div class="row" style="width: 100%;">
			<div class="col-md-offset-4 col-md-4 col-sm-offset-2 col-sm-8 mobile-reconfig" style="padding-right: 0px">
				<?php showAlerts(); ?>
				<div class="col-md-12 opaque-section" style="padding: 0px">
					<div class="row box-title-section">
						<h3 class="eve-text" style="text-align: center; font-size: 250%"><?php echo SITE_NAME; ?></h3>
					</div>
					<div class="row" style="padding-left: 10px; padding-right: 10px">
						<p class="eve-text" style="font-size: 150%; text-align: center">Please enter your user name and password</p>
						<form method="post" action="#" name="loginform" id="loginform" style="margin-bottom: 15px; margin-top: 15px">
							<fieldset>
								<input class="form-control" type="text" placeholder="Username" name="username">
							</fieldset>
							<fieldset>
								<input class="form-control" type="password" placeholder="Password" name="password" style="margin-top: 5px">
							</fieldset>
							<input class="btn btn-primary btn-lg eve-text pull-right" style="margin-top: 5px; margin-bottom: 5px; border-radius: 0px" type="submit" name="login" value="Log In">
							<a href="/register/1/" class="btn btn-info btn-lg eve-text pull-right" style="margin-right: 5px; margin-top: 5px; margin-bottom: 5px; border-radius: 0px;">Create Account</a>
							<a style="color: #65a9cc; margin-top: 15px;" href="/recover/" class="pull-left">Forgot password?</a>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php
require('includes/footer.php');