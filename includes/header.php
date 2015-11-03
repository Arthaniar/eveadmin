<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="../../favicon.ico">

    <title><?php echo SITE_NAME; ?></title>

    <!-- Bootstrap core CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css" rel="stylesheet">

    <link href='https://fonts.googleapis.com/css?family=Economica:400,700' rel='stylesheet' type='text/css'>

    <!-- Custom styles for this template -->
    <link href="/css/style.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.countdown/2.0.5/jquery.countdown.min.js"></script>

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
    <nav class="navbar navbar-inverse navbar-fixed-top opaque-navbar" role="navigation">
      <div class="container">
        <div class="navbar-header">
          <a class="navbar-brand" href="/dashboard/"><img style="height: 75px" src="/img/logo.png"></a>
        </div>
        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
          <ul class="nav navbar-nav navbar-right navbar-member">
          <!-- Member Account Dropdown -->
            <li class="dropdown navbar-member">
              <a href="#" class="dropdown-toggle navbar-member navbar-notifications navbar-portrait" data-toggle="dropdown"><span class="eve-text" style="font-size: 180%; font-weight: 700; padding-right: 5px;">Welcome, <?php if($user->getLoginStatus()) { echo ucfirst($user->getUserName()); ?></span> <img src="https://image.eveonline.com/Character/<?php echo $user->getDefaultID(); ?>_128.jpg" style="height: 75px; width: 75px"> <b class="caret"> <?php } else { echo '<span style="line-height: 70px">Guest! Most functions will not work until you register.</span>'; } ?></b></a>
              <ul class="dropdown-menu">
                <li><a href="/keys/">My API Keys</a></li>
                <li><a href="/applications/">My Applications</a></li>
                <li><a href="/account/">My Account</a></li>
                <li><a href="/logout/">Logout</a></li>
              </ul>
            </li>
          </ul>
        </div>
      </div><!-- /.container-fluid -->
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-2" aria-expanded="false">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
        </div>
        <div class="collapse navbar-collapse collapse-buttons" id="bs-example-navbar-collapse-2">
            <ul class="nav navbar-nav sub-nav">
              <li><a href="/skillplans/" class="navigation-option">Skill Plans</a></li>
              <li class="dropdown">
                <a href="#" class="dropdown-toggle navigation-option" data-toggle="dropdown">Character Center <b class="caret"></b></a>
                <ul class="dropdown-menu sub-nav">
                  <li class="dropdown-header">Character Tools</li>
                  <li><a href="/evemail/">Evemail</a></li>
                  <li><a href="/skills/">All Skills</a></li>
                  <li class="divider"></li>
                  <li class="dropdown-header">Industry Tools</li>
                  <li><a href="/orders/">My Orders</a></li>
                  <li><a href="/contracts/">My Contracts</a></li>
                  <li><a href="/industry/">My Industry</a></li>
                  <li class="divider"></li>
                  <li class="dropdown-header">Market Tools</li>
                  <li><a href="/shop/">Corp Shop</a></li>
                  <li><a href="/contracts/alliance/">Alliance Contracts</a></li>
                </ul>
              </li>
              <li class="dropdown">
                <a href="#" class="dropdown-toggle navigation-option" data-toggle="dropdown">PvP Center <b class="caret"></b></a>
                <ul class="dropdown-menu sub-nav">
                  <li class="dropdown-header">PvP Information</li>
                  <li><a href="/doctrines/">Fleet Doctrines</a></li>
                  <li><a href="/operations/">Operations Calendar</a></li>
                  <li class="divider"></li>
                  <li class="dropdown-header">PvP Tools</li>
                  <li><a href="https://kb.dogft.com" target="blank">Corp Killboard</a></li>
                  <li><a href="/participation/">Participation Stats</a></li>
                  <li><a href="/replacement/">Ship Replacement</a></li>
                </ul>
              </li>
              <?php
              if($user->getGroup()  != 0) {
                ?>
                <li class="dropdown">
                  <a href="#" class="dropdown-toggle navigation-option" data-toggle="dropdown">External Services <b class="caret"></b></a>
                  <ul class="dropdown-menu sub-nav">
                    <li class="dropdown-header">External Accounts</li>
                    <li><a href="/services/">Services Registration</a></li>
                    <li class="divider"></li>
                    <li class="dropdown-header">External Links</li>
                    <?php if($settings->getSlackIntegration()) {
                      ?>
                      <li><a href="<?php echo $settings->getSlackAddress(); ?>">Slack Messaging Service</a></li>
                      <?php
                    }
                    ?>
                    <?php if($settings->getForumIntegration()) {
                      ?>
                      <li><a href="<?php echo $settings->getForumAddress(); ?>">Group Forums</a></li>
                      <?php
                    }
                    ?>
                    <?php if($settings->getVoiceIntegration()) {
                      ?>
                      <li><a href="<?php echo $settings->getVoiceConnectionAddress($user->getUsername()); ?>">Voice Chat</a></li>
                      <?php
                    }
                    ?>
                  </ul>
                </li>
                <?php
              }
              if($user->getDirectorAccess()) {
                ?>
                <li class="dropdown">
                  <a href="#" class="dropdown-toggle navigation-option" data-toggle="dropdown">Administration <b class="caret"></b></a>
                  <ul class="dropdown-menu sub-nav">
                    <li class="dropdown-header">Human Resources</li>
                    <li><a href="/applications/">Membership Applications</a></li>
                    <li><a href="/spycheck/">Spycheker</a></li>
                    <li><a href="/permissions/">Permissions Management</a></li>
                    <li class="dropdown-header">Compliance and Information</li>
                    <li><a href="/compliance/skill/">Skill Plan Compliance</a></li>
                    <li><a href="/compliance/doctrine/">Doctrine Compliance</a></li>
                    <li><a href="/compliance/api/">API Compliance</a></li>
                    <li class="dropdown-header">Director Tools</li>
                    <li><a href="/search/skills/">Skills Search</a></li>
                    <li><a href="/search/assets/">Assets Search</a></li>
                    <?php
                    if($user->getCEOAccess()) {
                      ?>
                    <li class="divider"></li>
                    <li class="dropdown-header">CEO Tools</li>
                    <li><a href="/overview/group/">Group Overview</a></li>
                    <li><a href="/settings/group/">Group Settings</a></li>
                      <?php
                    }

                    if($user->getAdminAccess()) {
                      ?>
                      <li class="divider"></li>
                      <li class="dropdown-header">Admin Tools</li>
                      <li><a href="/overview/admin/">Admin Overview</a></li>
                      <li><a href="/security/">Site Security</a></li>
                      <li><a href="/update/">Site Updates</a></li>
                      <?php
                    }
                    ?>
                    <li class="dropdown-header">Groups and Members</li>
                    <li><a href="/manage/groups/">Group Management</a></li>
                    <li><a href="/manage/users/">Account Management</a></li>
                    <li><a href="/info/apis/">API Information</a></li>
                    <li><a href="/info/characters/">Character Information</a></li>
                  </ul>
                </li>
                <?php
              }
              ?>
            </ul>
            <ul class="nav navbar-nav navbar-right sub-nav">
              <?php
              if($user->getLoginStatus()) {
                // Getting evemail counts
                $stmt_inbox_unread = $db->prepare('SELECT * FROM user_evemail WHERE evemail_type = "Inbox" AND uid = ? AND unread = 1');
                $stmt_inbox_unread->execute(array($user->getUID()));
                $inbox_count = $stmt_inbox_unread->rowCount();

                $stmt_corp_unread = $db->prepare('SELECT * FROM user_evemail WHERE evemail_type = "Corporation" AND uid = ? AND unread = 1');
                $stmt_corp_unread->execute(array($user->getUID()));
                $corp_count = $stmt_corp_unread->rowCount();

                $stmt_alliance_unread = $db->prepare('SELECT * FROM user_evemail WHERE evemail_type = "Alliance" AND uid = ? AND unread = 1');
                $stmt_alliance_unread->execute(array($user->getUID()));
                $alliance_count = $stmt_alliance_unread->rowCount();

                if($inbox_count + $corp_count + $alliance_count >= 1) {
                  $envelope = 'unread';
                } else {
                  $envelope = '';
                }

                if($inbox_count >= 1) {
                  $inbox_unread = 'class="unread"';
                } else {
                  $inbox_unread = '';
                }

                if($corp_count >= 1) {
                  $corp_unread = 'class="unread"';
                } else {
                  $corp_unread = '';
                }

                if($alliance_count >= 1) {
                  $alliance_unread = 'class="unread"';
                } else {
                  $alliance_unread = '';
                }
                ?>
                <li class="dropdown">
                  <a href="#" class="dropdown-toggle navigation-option unread" data-toggle="dropdown"><span class="glyphicon glyphicon-envelope <?php echo $envelope; ?>"></span> <b class="caret"></b></a>
                  <ul class="dropdown-menu">
                    <li><a href="/evemail/#inbox" <?php echo $inbox_unread; ?>>Inbox (<?php echo $inbox_count; ?>)</a></li>
                    <li><a href="/evemail/#corporation/" <?php echo $corp_unread; ?>>Corporation (<?php echo $corp_count; ?>)</a></li>
                    <li><a href="/evemail/#alliance/" <?php echo $alliance_unread; ?>>Alliance (<?php echo $alliance_count; ?>)</a></li>
                  </ul>
                </li>
                <li class="dropdown">
                  <a href="#" class="dropdown-toggle navigation-option" data-toggle="dropdown">Notifications <b class="caret"></b></a>
                  <ul class="dropdown-menu sub-nav">
                    <li><a href="#" class="skill-completed">Notifications are not currently enabled.</a></li>
                  </ul>
                </li>
                <?php
              }
              ?>
          </ul>
        </div>
      </div>
    </nav>
  <body role="document">