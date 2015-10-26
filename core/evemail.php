<?php
require_once('includes/header.php');

if($request['action'] == 'read') {

	if(isset($_POST['unread_inbox']) OR isset($_POST['unread_sent']) OR isset($_POST['unread_corp']) OR isset($_POST['unread_ally'])) {
		if(isset($_POST['unread_inbox'])) {
			$message_array = $_POST['unread_inbox'];
		} elseif(isset($_POST['unread_corp'])) {
			$message_array = $_POST['unread_corp'];
		} elseif(isset($_POST['unread_sent'])) {
			$message_array = $_POST['unread_sent'];
		} elseif(isset($_POST['unread_ally'])) {
			$message_array = $_POST['unread_ally'];
		}

		foreach($message_array as $message) {
			$stmt = $db->prepare('UPDATE user_evemail SET unread = 0 WHERE message_id = ? and uid = ?');
			$stmt->execute(array($message, $user->getUID()));			
		}
	} else {
		$stmt = $db->prepare('UPDATE user_evemail SET unread = 0 WHERE message_id = ? and uid = ?');
		$stmt->execute(array($request['value'], $user->getUID()));
	}
}

// Getting the mails for each group: Inbox, Sent, Corporation, Alliance

$stmt_inbox = $db->prepare('SELECT * FROM user_evemail WHERE evemail_type = "Inbox" AND uid = ? ORDER BY sent_date DESC');
$stmt_inbox->execute(array($user->getUID()));
$inbox_mails = $stmt_inbox->fetchAll(PDO::FETCH_ASSOC);

$stmt_sent = $db->prepare('SELECT * FROM user_evemail WHERE evemail_type = "Sent" AND uid = ? ORDER BY sent_date DESC');
$stmt_sent->execute(array($user->getUID()));
$sent_mails = $stmt_sent->fetchAll(PDO::FETCH_ASSOC);

$stmt_corporation = $db->prepare('SELECT * FROM user_evemail WHERE evemail_type = "Corporation" AND uid = ? GROUP BY message_id ORDER BY sent_date DESC');
$stmt_corporation->execute(array($user->getUID()));
$corporation_mails = $stmt_corporation->fetchAll(PDO::FETCH_ASSOC);

$stmt_alliance = $db->prepare('SELECT * FROM user_evemail WHERE evemail_type = "Alliance" AND uid = ? ORDER BY sent_date DESC');
$stmt_alliance->execute(array($user->getUID()));
$alliance_mails = $stmt_alliance->fetchAll(PDO::FETCH_ASSOC);

// Getting the unread mail count for each group: Inbox, Sent, Corporation, Alliance

$stmt_inbox_unread = $db->prepare('SELECT * FROM user_evemail WHERE evemail_type = "Inbox" AND uid = ? AND unread = 1');
$stmt_inbox_unread->execute(array($user->getUID()));
$inbox_count = $stmt_inbox_unread->rowCount();

$stmt_sent_unread = $db->prepare('SELECT * FROM user_evemail WHERE evemail_type = "Sent" AND uid = ? AND unread = 1');
$stmt_sent_unread->execute(array($user->getUID()));
$sent_count = $stmt_sent_unread->rowCount();

$stmt_corp_unread = $db->prepare('SELECT * FROM user_evemail WHERE evemail_type = "Corporation" AND uid = ? AND unread = 1');
$stmt_corp_unread->execute(array($user->getUID()));
$corp_count = $stmt_corp_unread->rowCount();

$stmt_alliance_unread = $db->prepare('SELECT * FROM user_evemail WHERE evemail_type = "Alliance" AND uid = ? AND unread = 1');
$stmt_alliance_unread->execute(array($user->getUID()));
$alliance_count = $stmt_alliance_unread->rowCount();

// Getting the coloring for each tab based on unread count
if($inbox_count >= 1) {
	$inbox_unread = 'style="color: #e67b0d !important"';;
} else {
	$inbox_unread = '';
}

if($sent_count >= 1) {
	$sent_unread = 'style="color: #e67b0d !important"';;
} else {
	$sent_unread = '';
}

if($corp_count >= 1) {
	$corp_unread = 'style="color: #e67b0d !important"';;
} else {
	$corp_unread = '';
}

if($alliance_count >= 1) {
	$alliance_unread = 'style="color: #e67b0d !important"';
} else {
	$alliance_unread = '';
}

?>
<div class="opaque-container">

    <div class="row" style="width: 100%; margin-top: 20px; margin-bottom: 20px">
		<div class="col-md-12 opaque-section" style="padding: 0px">
			<div class="row box-title-section">
				<h1 style="text-align: center">Account-wide Evemail</h1>
				<h3 style="text-align: center">Evemail for all accounts that currently allow Evemail access.</h3>
			</div>
			<?php showAlerts(); ?>
			<div class="row" style="padding-left: 25px; padding-right: 25px; margin-top: 25px; margin-bottom: 25px">
				<!-- Tabs -->
    			<ul class="nav nav-pills" role="tablist" style="color: #e67b0d">
				    <li role="presentation" class="active" style="border-top: 5px">
				    	<a <?php echo $inbox_unread; ?> class="navigation-option" href="#inbox" aria-controls="inbox" role="tab" data-toggle="tab">
				    		Inbox (<?php echo $inbox_count; ?>)
				    	</a>
				    </li>
				    <li role="presentation">
				    	<a <?php echo $sent_unread; ?> class="navigation-option" href="#sent" aria-controls="sent" role="tab" data-toggle="tab">
				    		Sent Mail (<?php echo $sent_count; ?>)
				    	</a>
				    </li>
				    <li role="presentation">
				    	<a <?php echo $corp_unread; ?> class="navigation-option" href="#corporation" aria-controls="corporation" role="tab" data-toggle="tab">
				    		Corporation Mail (<?php echo $corp_count; ?>)
				    	</a>
				    </li>
				    <li role="presentation">
				    	<a <?php echo $alliance_unread; ?> class="navigation-option" href="#alliance" aria-controls="alliance" role="tab" data-toggle="tab">
				    		Alliance Mail (<?php echo $alliance_count; ?>)
				    	</a>
				    </li>
				</ul>
				<!-- Tab Panels -->
				<div class="tab-content">
					<script>
						function toggle(source) {
							checkboxes = document.getElementsByName('unread_inbox[]');
							for(var i=0, n=checkboxes.length;i<n;i++) {
								checkboxes[i].checked = source.checked;
							}
						}

						function toggles(source) {
							checkboxes = document.getElementsByName('unread_sent[]');
							for(var i=0, n=checkboxes.length;i<n;i++) {
								checkboxes[i].checked = source.checked;
							}
						}

						function togglec(source) {
							checkboxes = document.getElementsByName('unread_corp[]');
							for(var i=0, n=checkboxes.length;i<n;i++) {
								checkboxes[i].checked = source.checked;
							}
						}

						function togglea(source) {
							checkboxes = document.getElementsByName('unread_ally[]');
							for(var i=0, n=checkboxes.length;i<n;i++) {
								checkboxes[i].checked = source.checked;
							}
						}
					</script>
					<!-- Inbox Mail -->
					<div role="tabpanel" class="tab-pane active" id="inbox">
						<table class="table table-striped">
							<tr>
								<th><input type="checkbox" onClick="toggle(this)" /></th>
								<th><img src="/img/iconMailWhite.gif"></th>
								<th>Sender</th>
								<th>Subject</th>
								<th>Date</th>
								<th>Receiving Character</th>
							</tr>
							<?php
							foreach($inbox_mails as $inbox) {
								if($inbox['unread'] == 1) {
									$icon = '/img/iconMailUnread.gif';
								} else {
									$icon = '/img/iconMailRead.gif';
								}

								$receiver_array = explode(',', $inbox['evemail_receiver']);
								$receiver_string = '';

								foreach($receiver_array as $receiver) {
									$stmt = $db->prepare('SELECT * FROM characters WHERE charactername = ? AND uid = ? LIMIT 1');
									$stmt->execute(array(trim($receiver), $user->getUID()));
									$character_lookup = $stmt->fetch(PDO::FETCH_ASSOC);

									if(isset($character_lookup['charid'])) {
										$receiver_string .= '<button class="label label-primary">'.$character_lookup['charactername'].'</button>&nbsp;';
									}
								}
								?>
								<div class="modal fade" id="inbox<?php echo $inbox['message_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="inbox<?php echo $inbox['message_id']; ?>Label">
									<div class="modal-dialog" role="document">
										<div class="modal-content">
											<div class="modal-header">
												<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
												<h4 class="modal-title" id="inbox<?php echo $inbox['message_id']; ?>Label">Viewing Evemail: <?php echo $inbox['evemail_title']; ?> </h4>
											</div>
											<div class="modal-body">
												<p>Date: <?php echo $inbox['sent_date']; ?></p>
												<p>Sender: <?php echo $inbox['evemail_sender']; ?></p>
												<p>To: <?php echo $inbox['evemail_receiver']; ?></p>
												<p>Subject: <?php echo $inbox['evemail_title']; ?></p>
												<p><?php echo strip_tags($inbox['evemail_body'], "<br>"); ?></p>
											</div>
											<div class="modal-footer">
												<button type="button" class="btn btn-default" data-dismiss="modal">Keep Unread</button>
												<a type="button" class="btn btn-primary" href="/evemail/read/<?php echo $inbox['message_id']; ?>/">Mark Read</a>
											</div>
										</div>
									</div>
								</div>
								<tr>
									<td><input type="checkbox" name="unread_inbox[]" value="<?php echo $message_id; ?>"></td>
									<td><img src="<?php echo $icon; ?>"></td>
									<td><?php echo $inbox['evemail_sender']; ?></td>
									<td><a style="color: rgb(245,245,245)" data-toggle="modal" data-target="#inbox<?php echo $inbox['message_id']; ?>"><?php echo $inbox['evemail_title']; ?></a></td>
									<td><?php echo $inbox['sent_date']; ?></td>
									<td><?php echo $receiver_string; ?></td>
								</tr>
								<?php
							}
							?>
						</table>
					</div>
					<!-- Sent Mail -->
					<div role="tabpanel" class="tab-pane" id="sent" style="text-align: left">
						<table class="table table-striped">
							<tr>
								<th><input type="checkbox" onClick="toggles(this)" /></th>
								<th><img src="/img/iconMailWhite.gif"></th>
								<th>Sender</th>
								<th>Subject</th>
								<th>Date</th>
								<th>Receiving Character</th>
							</tr>
							<?php
							foreach($sent_mails as $sent) {
								if($sent['unread'] == 1) {
									$icon = '/img/iconMailUnread.gif';
								} else {
									$icon = '/img/iconMailRead.gif';
								}
								?>
								<div class="modal fade" id="sent<?php echo $sent['message_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="sent<?php echo $sent['message_id']; ?>Label">
									<div class="modal-dialog" role="document">
										<div class="modal-content">
											<div class="modal-header">
												<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
												<h4 class="modal-title" id="sent<?php echo $sent['message_id']; ?>Label">Viewing Evemail: <?php echo $sent['evemail_title']; ?> </h4>
											</div>
											<div class="modal-body">
												<p>Date: <?php echo $sent['sent_date']; ?></p>
												<p>Sender: <?php echo $sent['evemail_sender']; ?></p>
												<p>To: <?php echo $sent['evemail_receiver']; ?></p>
												<p>Subject: <?php echo $sent['evemail_title']; ?></p>
												<p><?php echo strip_tags($sent['evemail_body'], "<br>"); ?></p>
											</div>
											<div class="modal-footer">
												<button type="button" class="btn btn-default" data-dismiss="modal">Keep Unread</button>
												<a type="button" class="btn btn-primary" href="/evemail/read/<?php echo $sent['message_id']; ?>/">Mark Read</a>
											</div>
										</div>
									</div>
								</div>
								<tr>
									<td><input type="checkbox" name="unread_sent[]" value="<?php echo $message_id; ?>"></td>
									<td><img src="<?php echo $icon; ?>"></td>
									<td><?php echo $sent['evemail_sender']; ?></td>
									<td><a style="color: rgb(245,245,245)" data-toggle="modal" data-target="#sent<?php echo $sent['message_id']; ?>"><?php echo $sent['evemail_title']; ?></a></td>
									<td><?php echo $sent['sent_date']; ?></td>
									<td><button class="label label-primary"><?php echo $sent['evemail_sender']; ?></button></td>
								</tr>
								<?php
							}
							?>
						</table>
					</div>
					<!-- Corporation Mail -->
					<div role="tabpanel" class="tab-pane" id="corporation">
						<table class="table table-striped">
							<tr>
								<th><input type="checkbox" onClick="togglec(this)" /></th>
								<th><img src="/img/iconMailWhite.gif"></th>
								<th>Sender</th>
								<th>Subject</th>
								<th>Date</th>
								<th>Receiving Character</th>
							</tr>
							<?php
							foreach($corporation_mails as $corp) {
								if($corp['unread'] == 1) {
									$icon = '/img/iconMailUnread.gif';
								} else {
									$icon = '/img/iconMailRead.gif';
								}

								$receiver_string = '';

								$stmt = $db->prepare('SELECT * FROM user_evemail WHERE message_id = ? AND uid = ?');
								$stmt->execute(array($corp['message_id'], $user->getUID()));
								$receivers = $stmt->fetchAll(PDO::FETCH_ASSOC);

								foreach($receivers as $receiver) {
									$stmt = $db->prepare('SELECT * FROM characters WHERE charid = ? AND uid = ? LIMIT 1');
									$stmt->execute(array($receiver['character_id'], $user->getUID()));
									$character_lookup = $stmt->fetch(PDO::FETCH_ASSOC);

									if(isset($character_lookup['charid'])) {
										$receiver_string .= '<button class="label label-primary">'.$character_lookup['charactername'].'</button>&nbsp;';
									}
								}
								?>
								<div class="modal fade" id="corp<?php echo $corp['message_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="corp<?php echo $corp['message_id']; ?>Label">
									<div class="modal-dialog" role="document">
										<div class="modal-content">
											<div class="modal-header">
												<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
												<h4 class="modal-title" id="corp<?php echo $corp['message_id']; ?>Label">Viewing Evemail: <?php echo $corp['evemail_title']; ?> </h4>
											</div>
											<div class="modal-body">
												<p>Date: <?php echo $corp['sent_date']; ?></p>
												<p>Sender: <?php echo $corp['evemail_sender']; ?></p>
												<p>To: <?php echo $corp['evemail_receiver']; ?></p>
												<p>Subject: <?php echo $corp['evemail_title']; ?></p>
												<p><?php echo strip_tags($corp['evemail_body'], '<br>'); ?></p>
											</div>
											<div class="modal-footer">
												<button type="button" class="btn btn-default" data-dismiss="modal">Keep Unread</button>
												<a type="button" class="btn btn-primary" href="/evemail/read/<?php echo $corp['message_id']; ?>/">Mark Read</a>
											</div>
										</div>
									</div>
								</div>
								<tr>
									<td><input type="checkbox" name="unread_corp[]" value="<?php echo $message_id; ?>"></td>
									<td><img src="<?php echo $icon; ?>"></td>
									<td><?php echo $corp['evemail_sender']; ?></td>
									<td><a style="color: rgb(245,245,245)" data-toggle="modal" data-target="#corp<?php echo $corp['message_id']; ?>"><?php echo $corp['evemail_title']; ?></a></td>
									<td><?php echo $corp['sent_date']; ?></td>
									<td><?php echo $receiver_string; ?></td>
								</tr>
								<?php
							}
							?>
						</table>
					</div>
					<!-- Alliance Mail -->
					<div role="tabpanel" class="tab-pane" id="alliance">
						<table class="table table-striped">
							<tr>
								<th><input type="checkbox" onClick="togglea(this)" /></th>
								<th><img src="/img/iconMailWhite.gif"></th>
								<th>Sender</th>
								<th>Subject</th>
								<th>Date</th>
								<th>Receiving Character</th>
							</tr>
							<?php
							foreach($alliance_mails as $alliance) {
								if($alliance['unread'] == 1) {
									$icon = '/img/iconMailUnread.gif';
								} else {
									$icon = '/img/iconMailRead.gif';
								}

								$receiver_string = '';

								$stmt = $db->prepare('SELECT * FROM user_evemail WHERE message_id = ? AND uid = ?');
								$stmt->execute(array($alliance['message_id'], $user->getUID()));
								$receivers = $stmt->fetchAll(PDO::FETCH_ASSOC);

								foreach($receivers as $receiver) {
									$stmt = $db->prepare('SELECT * FROM characters WHERE charid = ? AND uid = ? LIMIT 1');
									$stmt->execute(array($receiver['character_id'], $user->getUID()));
									$character_lookup = $stmt->fetch(PDO::FETCH_ASSOC);

									if(isset($character_lookup['charid'])) {
										$receiver_string .= '<button class="label label-primary">'.$character_lookup['charactername'].'</button>&nbsp;';
									}
								}
								?>
								<div class="modal fade" id="ally<?php echo $alliance['message_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="ally<?php echo $alliance['message_id']; ?>Label">
									<div class="modal-dialog" role="document">
										<div class="modal-content">
											<div class="modal-header">
												<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
												<h4 class="modal-title" id="ally<?php echo $alliance['message_id']; ?>Label">Viewing Evemail: <?php echo $alliance['evemail_title']; ?> </h4>
											</div>
											<div class="modal-body">
												<p>Date: <?php echo $alliance['sent_date']; ?></p>
												<p>Sender: <?php echo $alliance['evemail_sender']; ?></p>
												<p>To: <?php echo $alliance['evemail_receiver']; ?></p>
												<p>Subject: <?php echo $alliance['evemail_title']; ?></p>
												<p><?php echo strip_tags($alliance['evemail_body'], '<br>'); ?></p>
											</div>
											<div class="modal-footer">
												<button type="button" class="btn btn-default" data-dismiss="modal">Keep Unread</button>
												<a type="button" class="btn btn-primary" href="/evemail/read/<?php echo $alliance['message_id']; ?>/">Mark Read</a>
											</div>
										</div>
									</div>
								</div>
								<tr>
									<td><input type="checkbox" name="unread_ally[]" value="<?php echo $message_id; ?>"></td>
									<td><img src="<?php echo $icon; ?>"></td>
									<td><?php echo $alliance['evemail_sender']; ?></td>
									<td><a style="color: rgb(245,245,245); display: block; width: 100%" data-toggle="modal" data-target="#ally<?php echo $alliance['message_id']; ?>"><?php echo $alliance['evemail_title']; ?></a></td>
									<td><?php echo $alliance['sent_date']; ?></td>
									<td><?php echo $receiver_string; ?></td>
								</tr>
								<?php
							}
							?>
						</table>
					</div>
				</div>
			</div>
		</div>	
    </div>

</div>
<?php
require_once('includes/footer.php');