<?php
// PHP error reporting for debug info. Commented out for production
// For more information: https://stackify.com/display-php-errors/
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

    require_once 'file_path.php';
	// session 
	session_start();
    //check once again if the user is logged in
    //if not, redirect back to login page
    if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] == FALSE) {   
        session_destroy();   
        header("Location: " . $FILE_PATH . "login.php");  
    }   
	
	// database connection 
	require './database/dbconfig.php';
	// functions for accessing database
	require './database/dbquery.php';
	
	// connect to database 
	$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname, $dbport);
	if ($mysqli->connect_errno) {
		die("Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error . "\n");
	}
	
	
	// get userID from session 
    //Find userID based off of onid
     $data = lookupUser($mysqli, $_SESSION["onidID"]);
     $user = json_decode($data);
	
	// get past events, invites, and reservations from database 
	$events = eventCreateHist($mysqli, $user->id);
	$invites = inviteHist($mysqli, $user->id);
	$reservations = reservedSlotHist($mysqli, $user->id);
	
	// process events to build an array for fullcalendar.io
	$pastEvents = array();
	for($i = 0; $i < count($events); $i++){
		$tmp = array();
		$tmp['id'] = $events[$i]['id'];
		$tmp['title'] = $events[$i]['title'];
		$tmp['start'] = $events[$i]['dateStart'];
		$tmp['end'] = $events[$i]['dateEnd'];
		$pastEvents[$i] = $tmp;
	}
	
	// process invites to build an array for fullcalendar.io
	$pastInvites = array();
	for($i = 0; $i < count($invites); $i++){
		$tmp = array();
		$tmp['id'] = $invites[$i]['inviteID'];
		$tmp['title'] = $invites[$i]['title'] . " (" . $invites[$i]['status'] . ")";
		$tmp['start'] = $invites[$i]['dateStart'];
		$tmp['end'] = substr($invites[$i]['dateStart'],0,10) . " " . eventEndTime($mysqli, $invites[$i]['eventID']);
		$pastInvites[$i] = $tmp;
	}
	
	// process reservations to build an array for fullcalendar.io
	$pastReservations = array();
	for($i = 0; $i < count($reservations); $i++){
		$tmp = array();
		$tmp['id'] = $reservations[$i]['slotID'];
		$tmp['title'] = $reservations[$i]['title'] . ", Location: " . $reservations[$i]['location'];
		$tmp['start'] = $reservations[$i]['startDateTime'];
		$tmp['end'] = $reservations[$i]['endDateTime'];
		$tmp['url'] = './view_reservation.php?slot=' . $reservations[$i]['slotID'];
		$pastReservations[$i] = $tmp;
	}
	
	
	// send to javascript on client
	echo "<script>\n";
	echo "var pastEvents = " . json_encode($pastEvents) . ";\n";
	echo "var pastInvites = " . json_encode($pastInvites) . ";\n";
	echo "var pastReservations = " . json_encode($pastReservations) . ";\n";
	echo "</script>";


	$mysqli->close();
?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

	<title>Schedule-it</title>

	<!--Bootstrap core CSS-->
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
	<!--Customized css-->
	<link rel="stylesheet" href="./assets/css/main.css" type="text/css">

	<!--NEEDED FOR DIALOG-FORM DISPLAY -->
	<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>

	<!-- javascript files -->
	<script src="./assets/js/main.js"></script>
	<script src="./assets/js/view_history.js"></script>


	<!-- fontawesome for icon usage eg. navbar hamburger icon -->
	<script src="https://kit.fontawesome.com/96abf9bb58.js" crossorigin="anonymous"></script>


	<!--fullcalendar-->
	<!--Use daygrid-views for homepage -->
	<!--Use Selectable for event creation page-->
	<!--Source  fullcalendar.io-->

	<link href='./assets/js/fullcalendar/packages/core/main.css' rel='stylesheet' />
	<link href='./assets/js/fullcalendar/packages/daygrid/main.css' rel='stylesheet' />
	<link href='./assets/js/fullcalendar/packages/list/main.css' rel='stylesheet' />
	<link href='./assets/js/fullcalendar/packages/timegrid/main.css' rel='stylesheet' />

	<script src='./assets/js/fullcalendar/packages/core/main.js'></script>
	<script src='./assets/js/fullcalendar/packages/daygrid/main.js'></script>
	<script src='./assets/js/fullcalendar/packages/list/main.js'></script>
	<!-- <script src='../assets/js/fullcalendar/packages/interaction/main.js'></script> -->
	<script src='./assets/js/fullcalendar/packages/timegrid/main.js'></script>
	
 <!--NEEDED FOR DIALOG-FORM DISPLAY -->
 <style>

label, input { display:block; }
input.text { margin-bottom:12px; width:95%; padding: .4em; }
fieldset { padding:0; border:0; margin-top:25px; }
h1 { font-size: 1.2em; margin: .6em 0; }
div#users-contain { width: 350px; margin: 20px 0; }
div#users-contain table { margin: 1em 0; border-collapse: collapse; width: 100%; }
div#users-contain table td, div#users-contain table th { border: 1px solid #eee; padding: .6em 10px; text-align: left; }
.ui-dialog .ui-state-error { padding: .3em; }
.validateTips { border: 1px solid transparent; padding: 0.3em; }
#live_data .ui-dialog {
 width: 100%;
 padding: 0; }

 .tooltip {
	position: relative;
	display: inline-block;
	border-bottom: 1px dotted black;
  }

  .tooltip .tooltiptext {
	visibility: hidden;
	width: 120px;
	background-color: black;
	color: #fff;
	text-align: center;
	border-radius: 6px;
	padding: 5px 0;

	/* Position the tooltip */
	position: absolute;
	z-index: 1;
  }

  .tooltip:hover .tooltiptext {
	visibility: visible;
  }

  .hidden>div {
	display:none;
  }

  .visible>div {
	display:block;
  }

</style>


</head>
<body>
   
    <!-- Miobile responsive navbar -->
    <nav class="navbar navbar-expand-md schedule-it-top-hat">
        <div class="container-fluid">
            <a class="navbar-brand logo" href="https://oregonstate.edu"><img src="https://oregonstate.edu/themes/osu/drupal8-osuhomepage/logo.svg"></a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
                <span><i class="fas fa-bars fa-1x"></i></span>
            </button>

            <!-- Collapsible content -->
            <div class="collapse navbar-collapse" id="navbarResponsive">
                <ul class="navbar-nav schedule-it-main-menu mr-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="homepage.php">Schedule-It Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="calendar.php">Calendar</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="eventmanagement.php">Manage Events</a>
                    </li>
                </ul>
                <ul class="navbar-nav schedule-it-main-menu ml-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <p>
	
	<!-- buttons to switch between past events created by user and slots reserved by user -->
	<div class="container-fluid">
	    <div class="row">
			<div class="col-sm"></div>
			<div class="col-sm"><button type="button" class="btn btn-block" onclick="showResHist(event)" id="resHistButton" disabled>Reservations</div>
			<div class="col-sm"><button type="button" class="btn btn-block" onclick="showInviteHist(event)" id="inviteHistButton" >Invites</div>
			<div class="col-sm"><button type="button" class="btn btn-block" onclick="showEventHist(event)" id="eventHistButton">Created Events</div>
			<div class="col-sm"></div>
		</div>
	</div>
	<div class="container-fluid">
		<div class="row">
			<div class="col-sm-2"></div>
			<div class="col-sm-8"><h5 class="text-center" id="viewTitle"></h5></div>
		</div>
	</div>
	<!-- div for calendar-->
    <div class="container-fluid" id="content">
        
    </div>

	<!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>
</body>
</html>
