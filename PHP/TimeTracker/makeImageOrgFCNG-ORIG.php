<?php
define('INCLUDE_CHECK', true);
error_reporting (E_ALL ^ E_NOTICE);

require 'connect.php';
require 'functions.php';
// Those two files can be included only if INCLUDE_CHECK is defined

session_name('seLogin');
session_set_cookie_params(2*7*24*60*60);
session_start();

if ( isset($_GET['logoff']) || !( isset($_SESSION['users_id']) ) ){
    // echo 'SESSION users_id: ' . $_SESSION['users_id']; 
	$_SESSION = array();
    session_destroy();

    die('You are not allowed to execute this file directly.');
    exit;
}

if ( isset($_SESSION['is_manager']) == '0' ) { // They're not a manager. Abort.
	$_SESSION['Msg'] = "Only managers can view this page. If you believe this is an error contact the administrator.";
  	header("Location: redirect.php?Url=index.php?logoff");
  	exit;
}

$users_id = $_SESSION['users_id'];

// <img src="makeImage.php?total_values= echo $num_rows; "  />

set_time_limit(300);

if ( isset($_POST['submit']) && $_POST['submit'] == "Get Report" || $_POST['submit'] == "Download Image" ) {
	$_POST=from_array($_POST); // Massage the input with from_array().
	foreach($_POST as $k1=>$v1) $$k1=$v1; // Get all the $_POST key values and assign them associated variable names.
	// print_r($_POST);
	
	// Get their direct reports.
	$subOrgTitle = '';
	if ( $istype == 'suborg' ) { // This is a suborg report, so run directsOrg on the suborg userName.
		// First we need their users_id, cause all we have is their userName.
		$result_userid = mysql_query("SELECT users_id FROM users WHERE userName = '{$suborgname}'");
		if (!$result_userid) {
			unset($_SESSION['Msg']);
			$_SESSION['Msg'] = "Unable to get users id from user name for suborg report. Close this window and contact the administrator.";
			header("Location: view_directs.php");
  			exit;
		}
		$row_userid = mysql_fetch_array($result_userid, MYSQL_ASSOC);
		mysql_free_result($result_userid);
		$subOrgTitle = "\\r\\nOrganization: ".$suborgname;
		$directsOrg = getDirectsOrg($row_userid['users_id']);
	} else { // Just a regular, full organizational report.
		$directsOrg = getDirectsOrg($users_id);
		// print_r($directsOrg);
		// echo 'directsOrg total: '.count($directsOrg).'<br />';
	} // $istype == 'suborg'
	
	$count = count($directsOrg);
	if ( $directsOrg == '0' ) { // They have no direct reports.
		unset($_POST);
		unset($_SESSION['Msg']);
		$_SESSION['Msg'] = "No direct reports found for you. If you believe this is an error contact the administrator.";
		header("Location: view_directs.php");
  		exit;
	}
	
	// Ok, they have some direct reports. Let's get all the data for all of them.
	if ( $category == "total" ) {
		// $title = "Organizational Report for ".$_SESSION['firstName']. " ".$_SESSION['lastName'].": Total for ".$count." users"; -- REMOVED number of users.
	  	$title = "Organizational Report for ".$_SESSION['firstName']. " ".$_SESSION['lastName']." by Total".$subOrgTitle."\\r\\nTime Period: ".$startDate." to ".$endDate;
		$result_total = mysql_query("SELECT global_id,description FROM global_activity 
		WHERE isActive = '1' ORDER BY description ASC");
		if (!$result_total) {
			unset($_SESSION['Msg']);
			$_SESSION['Msg'] = "Could not get global_id from global table for total report. Close this window and contact the administrator.";
			header("Location: view_directs.php");
  			exit;
		}
		$num_rows = mysql_num_rows($result_total);
		if ( $num_rows == 0 ) {
			unset($_SESSION['Msg']);
			$_SESSION['Msg'] = "No active global_id's found for total report? Please try again.";
			header("Location: view_directs.php");
  			exit;
		}
		$j = 0;
		$data = array();
		$legend = array();
		$tmpTotal = 0.0;
		while ( $row_total = mysql_fetch_array($result_total, MYSQL_ASSOC) ) {
			// We want the cumulative vaule for all users under this manager. So we have to add them all together.
			foreach ( $directsOrg as $value ) {
				$tmpTotal += getHoursByGlobal($value,$row_total['global_id'],$startDate,$endDate);
			}
			if ( $tmpTotal < 1 ) { continue; } // Throw out 0 values. Unlikely in this case.
			$tmpLegend = $row_total['description'];
			$data[$j] = $tmpTotal;
			$legend[$j] = $tmpLegend;
			$j++;
			// Reset $tmpTotal here cause we're about to move on to a new activity.
			$tmpTotal = 0.0;
		}
		// We still need to check for 0'ish values. This depends on the cumulative number of hours collected.
		// For example: 1 hour in a total of 1000 hours is .001 (1 thousandth) of those hours. Expressed as a
		// percentage, this is 0.1%, and will show up on the graph as 0%. So, we have to walk through this array
		// and divide the individual totals by the cumulative total, multiply that by 100 (to get a %), and if it's
		// less than 1, we delte it from the array.
		// First get the cumulative total.
		// NOTE AND BE ADVISED: This does drop hours from the reported total. If you want to see EVERYTHING,
		// comment out this next block.
		$cumTotal = array_sum($data);
		$n = count($data);
		while ( $n > 0 ) {
			$test = ( ($data[$n-1] / $cumTotal) * 100 );
			if ( $test < 1 ) { // Less than 1%
				unset($data[$n-1]); // Remove it from the array.
				unset($legend[$n-1]); // Remove the associated legend as well.
			}
			$n--;
		}
		
		mysql_free_result($result_total);
	} else if ( $category == "all" ) {
		// $title = "Organizational Report for ".$_SESSION['firstName']. " ".$_SESSION['lastName']." by Category for ".$count." users"; -- REMOVED number of users.
		$title = "Organizational Report for ".$_SESSION['firstName']. " ".$_SESSION['lastName']." by Category".$subOrgTitle."\\r\\nTime Period: ".$startDate." to ".$endDate;
		// Understand now, that "all" isn't just necessarily category id's 1-5. As of this writing, yes, that is all
		// the categories there are. However, the admin can add and delete categories. So we need to handle this
		// dynamically, like everything else.
		$result_categories = mysql_query("SELECT * FROM categories");
		if (!$result_categories) {
			unset($_SESSION['Msg']);
			$_SESSION['Msg'] = "Could not get categories from categories table. Please contact the administrator.";
			header("Location: view_directs.php");
  			exit;
		}
		$num_rows = mysql_num_rows($result_categories); // To keep track of the number of categories returned.
		if ( $num_rows == 0 ) {
			unset($_SESSION['Msg']);
			$_SESSION['Msg'] = "No categories retrieved from categories table? Please contact the administrator.";
			header("Location: view_directs.php");
  			exit;
		}
		$j = 0;
		$data = array(); // $data will be the array that gets passed to the AddPoint() pChart method.
		$legend = array(); // $legend is also an array passed to AddPoint().
		$tmpTotal = 0.0;
			while ( $row_categories = mysql_fetch_array($result_categories, MYSQL_ASSOC) ) {
				// We want the cumulative vaule for all users under this manager. So we have to add them all together.
				foreach ( $directsOrg as $value ) {
					$tmpTotal += getHoursByCategory($value,$row_categories['categories_id'],$startDate,$endDate);
				}
				if ( $tmpTotal < 1 ) { continue; } // Throw out 0 values. Very unlikely in this case.
				$tmpLegend = $row_categories['category'];
				$data[$j] = $tmpTotal;
				$legend[$j] = $tmpLegend;
				$j++;
				// Reset $tmpTotal here cause we're about to move on to a new category.
				$tmpTotal = 0.0;
			}
		// We still need to check for 0'ish values. This depends on the cumulative number of hours collected.
		// For example: 1 hour in a total of 1000 hours is .001 (1 thousandth) of those hours. Expressed as a
		// percentage, this is 0.1%, and will show up on the graph as 0%. So, we have to walk through this array
		// and divide the individual totals by the cumulative total, multiply that by 100 (to get a %), and if it's
		// less than 1, we delte it from the array.
		// First get the cumulative total.
		// NOTE AND BE ADVISED: This does drop hours from the reported total. If you want to see EVERYTHING,
		// comment out this next block.
		$cumTotal = array_sum($data);
		$n = count($data);
		while ( $n > 0 ) {
			$test = ( ($data[$n-1] / $cumTotal) * 100 );
			if ( $test < 1 ) { // Less than 1%
				unset($data[$n-1]); // Remove it from the array.
				unset($legend[$n-1]); // Remove the associated legend as well.
			}
			$n--;
		}
		
		mysql_free_result($result_categories);
		
	}  else { // $category == "n"
		// Get the category name of this category for the title.
		$result_categoryName = mysql_query("SELECT category FROM categories WHERE categories_id = '{$category}'");
		if (!$result_categoryName) {
			unset($_SESSION['Msg']);
			$_SESSION['Msg'] = "Could not get category name for category. Please contact the administrator.";
			header("Location: view_directs.php");
  			exit;
		}
		$row_categoryName = mysql_fetch_array($result_categoryName, MYSQL_ASSOC);
		mysql_free_result($result_categoryName);
		$categoryName = $row_categoryName['category'];
		$title = "Organizational Report for ".$_SESSION['firstName']. " ".$_SESSION['lastName']." for Category: ".$categoryName.$subOrgTitle."\\r\\nTime Period: ".$startDate." to ".$endDate;
		// Here we just have a single category ID. We need to break out the global IDs that belong to this category 
		// (to be passed to getHoursByCategory), as well as the description fields for the global IDs.
		$result_category = mysql_query("SELECT global_id,description FROM global_activity 
		WHERE categories_id = '{$category}'");
		if (!$result_category) {
			unset($_SESSION['Msg']);
			$_SESSION['Msg'] = "Could not get global id's based on category. Please contact the administrator.";
			header("Location: view_directs.php");
  			exit;
		}
		$j = 0;
		$data = array(); // $data will be the array that gets passed to the AddPoint() pChart method.
		$legend = array(); // $legend is also an array passed to AddPoint().
		$tmpTotal = 0.0;
		while ( $row_category = mysql_fetch_array($result_category, MYSQL_ASSOC) ) {
			// We want the cumulative vaule for all users under this manager. So we have to add them all together.
				foreach ( $directsOrg as $value ) {
					$tmpTotal += getHoursByGlobal($value,$row_category['global_id'],$startDate,$endDate);
				}
			if ( $tmpTotal < 1 ) { continue; } // Throw out 0 values.
			$tmpLegend = $row_category['description'];
			$data[$j] = $tmpTotal;
			$legend[$j] = $tmpLegend;
			$j++;
			// Reset $tmpTotal here cause we're about to move on to a new activity.
			$tmpTotal = 0.0;
		}
		
		// We still need to check for 0'ish values. This depends on the cumulative number of hours collected.
		// For example: 1 hour in a total of 1000 hours is .001 (1 thousandth) of those hours. Expressed as a
		// percentage, this is 0.1%, and will show up on the graph as 0%. So, we have to walk through this array
		// and divide the individual totals by the cumulative total, multiply that by 100 (to get a %), and if it's
		// less than 1, we delte it from the array.
		// First get the cumulative total.
		// NOTE AND BE ADVISED: This does drop hours from the reported total. If you want to see EVERYTHING,
		// comment out this next block.
		$cumTotal = array_sum($data);
		$n = count($data);
		while ( $n > 0 ) {
			$test = ( ($data[$n-1] / $cumTotal) * 100 );
			if ( $test < 1 ) { // Less than 1%
				unset($data[$n-1]); // Remove it from the array.
				unset($legend[$n-1]); // Remove the associated legend as well.
			}
			$n--;
		}
		
		mysql_free_result($result_category);
		
	} // $category == "all"
	$dataTotal = array_sum($data);


	if ( $dataTotal == 0 ) { // No values at all. Tell them, and allow them to close the window.
		unset($_POST);
		unset($_SESSION['Msg']);
		$_SESSION['Msg'] = 'No values to display. Please try again.';
		header("Location: view_directs.php");
		exit;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title><?php echo $_SESSION['TITLE']; ?></title>
	<meta name="keywords" content="" />
	<meta name="description" content="" />
    <script type="text/javascript" language="javaScript" src="js/calendar.js"></script>
  	<script type="text/javascript" language="javascript" src="js/setime.js"></script>
    <script type="text/javascript" language="javascript" src="js/jquery-1.7.1.min.js"></script>
  	<script type="text/javascript" language="javascript" src="js/jquery-ui-1.8.18.custom.min.js"></script>
    <link type="text/css" href="css/custom-theme/jquery-ui-1.8.18.custom.css" rel="stylesheet" media="screen, projection" />
  	<link type="text/css" href="css/calendar.css" rel="stylesheet" media="screen, projection" />
  	<link type="text/css" href="css/setime.css" rel="stylesheet" media="screen, projection" />
	<link rel="stylesheet" href="css/style.css" type="text/css" media="screen, projection" />

<script type="text/javascript">
	$(function() {
		$( "button, a", "#sideLeft" ).button();
		$( "button, a", "#sideRight" ).button();
		$( "button, a", "#footer" ).button();
		
		$('.disabled').button('disable'); // Initially disabled buttons until functionality is added.
	});
</script>

<script type="text/javascript">
	$(function() {
		$( "#datepicker" ).datepicker();
	});
</script>

<script type="text/javascript">
	
	/* $(document).ready(function () {

	    $.history.init(pageload);	
	    
		$('a[href=' + window.location.hash + ']').addClass('selected');
		
		$('a[rel=ajax]').click(function () {
		
			var hash = this.href;
			hash = hash.replace(/^.*#/, '');
	 		$.history.load(hash);	
	 		
	 		$('a[rel=ajax]').removeClass('selected');
	 		$(this).addClass('selected');
	 		$('#body').hide();
	 		$('.loading').show();
	 		
			// getPage();
	
			return false;
		});
	});
	
	function pageload(hash) {
		if (hash) getPage();    
	} */
	
function getPage(url) {
	/* var data = 'page=' + encodeURIComponent(document.location.hash); */
	/* var url = document.location.hash; */
	// $('#body').hide();
	$('#updates').hide();
	$('.loading').show();
	$.ajax({
		/* url: "loader.php",	
		type: "GET",	
		data: data, */
		url: url,
		type: "POST",
		cache: false,
		success: function (html) {	
			$('.loading').hide();				
			$('#updates').html(html);
			$('#updates').fadeIn('slow');
			// $('#body').fadeIn('slow');		
	
		}		
	});
}

function popupDirects(url) {
	newwindow=window.open(url,"","scrollbars=1,location=0,toolbar=0,status=0,menubar=0,height=400,width=650");
	if (window.focus) {newwindow.focus()}
	return false;
}

function populateSubOrg(userName) {
	document.getElementById('suborgname').value = userName;
	document.getElementById('orgLevel').innerHTML = userName;
	$('#viewDirects').dialog('close');
}

</script>
    
<script type="text/javascript">

$(function() {
	
	$( "#viewDirects" ).dialog({
		height: 300,
		width: 650,
		minWidth: 650,
		modal: true
	});
});

</script>
    
<script type="text/javascript">
	$(function() {
		$( "#startDate" ).datepicker({
			dateFormat: 'yy-mm-dd',
			changeMonth: true,
			changeYear: true,
			maxDate: "+0D"
		});
	});
</script>

<script type="text/javascript">
	$(function() {
		$( "#endDate" ).datepicker({
			dateFormat: 'yy-mm-dd',
			changeMonth: true,
			changeYear: true,
			maxDate: "+0D"
		});
	});
</script>


</head>

<!--<body onload="popupDirects('showDirects.php');"> -->
<body>

<div id="wrapper">

	<div id="middle">

		<div id="container">
			<div id="content" class="ui-widget-content" style="border:none;">
				
              <div style="font-size:24px; font-weight:bold; padding-bottom:5px;">
                <p align="center">&nbsp;<br />
                <?php echo $_SESSION['TITLE']; ?> for <span class="weak"><?php echo ' '.$firstName.' '.$lastName;?></span></p>
              </div>
                            
                <div align="center" class="loading"><img src="images/orange_loading.gif" width="62" height="62" alt="Loading...." /></div>
                
                <div id="updates">
                  <div align="right" style="padding-right:5px;">
                  Organization Level: <span class="err" id="orgLevel"><?php echo ' '.$userName;?></span>
                  </div>
                
                	<div id="viewDirects">
                    
                    	<div id="directReports" class="ui-widget-header" style="text-align:left;float:left;clear:right;">
                        
						  <?php
                          $directs = getDirectsNew($_SESSION['users_id'],'first'); 
                          if ( $directs == '0' ) {
                          ?>
                              <ul><li>No direct reports</li></ul>
                          <?php
                              exit;
                          }
                          ?>
                          
                         </div><!-- end directReports -->
                         
                  <div class="ui-widget-content" style="float:left;clear:right;padding:5px;margin-left:5px;">
                         	<h4 align="left"><strong>Select a user name on the left to view their individual activity reports.</strong></h4>
							<h4 align="left"><strong>If a user has any direct reports, click the <img src="images/icons/arrow_down_blue.png" /> icon to see 
                            those users.</strong></h4>
                            
                            <h4 align="left"><strong>Click on the <img src="images/icons/arrow_right.png" /> icon to view a sub-org manager's reports.</strong></h4>
                            
                    </div>
                        
                    </div><!-- end viewDirects -->
                    
                    <div align="center">No values to display. Please try again.</div>
                		
                    <div class="ui-widget-header" style="margin:0px 5px 0px 5px; padding:5px;">
                    Sub-Organizational Reports (all direct reports under a specified manager)</div>
                    <div class="ui-widget-content" style="margin:0px 5px 0px 5px; padding:5px;">
                    	<form name="reportSubOrg" action="makeImageOrgFC.php" method="post" target="_blank">
                          <p align="left">
                          <label for="suborgname"><strong>Currently selected sub-org manager:</strong></label>
                          <input type="text" name="suborgname" id="suborgname" size="20" maxlength="255" readonly="readonly" placeholder="None" class="err"  />
                          </p>
                          
                          <label for="startDate"><strong>Select start date for the report time period</strong></label>
                          <input type="text" name="startDate" id="startDate" size="10" maxlength="10" />
                          <br />
                          <label for="endDate"><strong>Select end date for the report time period</strong></label>
                          <input type="text" name="endDate" id="endDate" size="10" maxlength="10" />
                                
                          <br />
                          <label for="category"><strong>Select a category for your report</strong></label>
                          <select name="category">
                          <option value="all" selected="selected">All Categories</option>
                          <?php
                          
                          $result_categories = mysql_query("SELECT * FROM categories");
                          if (!$result_categories) {
                              $_SESSION['Msg'] = "Could not get categories from categories table. Contact the administrator.";
                              header("Location: redirect.php?Url=/index.php?logoff");
                              exit;
                          }
                          while ( $row_categories = mysql_fetch_array($result_categories, MYSQL_ASSOC) ) {
                          ?>
                              <option value="<?php echo $row_categories['categories_id'];?>"><?php echo $row_categories['category'];?></option>
                          <?php
                          } // $row_categories = mysql_fetch_array($result_categories, MYSQL_ASSOC)
                          mysql_free_result($result_categories);
                          
                          ?>
                              <option value="total">Total</option>
                          </select>
                          <br />
                          <label for="chartType"><strong>Select the desired chart type:</strong></label>
                          <input type="radio" name="chartType" value="pie" checked="checked" /> <strong>Pie</strong>&nbsp;&nbsp;
                          <input type="radio" name="chartType" value="bar" /> <strong>Bar</strong>
                          <br />
                          <br />
                          <input type="hidden" name="istype" value="suborg" />
                          <input type="submit" name="submit" value="Get Report" /> or 
                          <input type="submit" name="submit" value="Download Image" /> or
                          <input type="submit" name="submit" value="Download CSV" />
                          </form>
                    </div>
                        
                        <!--<img src="images/1x1black.png" alt="" width="540" height="590" /> -->
                    
                </div><!-- end updates -->
                
		  </div><!-- #content-->
		</div><!-- #container-->

		<div class="sidebar ui-widget-content" id="sideLeft" style="border:none;">
			<p align="center">Logged in as: <strong><?php echo $userName; ?></strong></p>
            
            <?php printMenuLeftNG(); ?>
           
           <script type="text/javascript">
		   // Stop the #codes links from firing until time_entry2.php has loaded the #entryTable div.
				$("#codes").click(function(e) {
    				    if( !$('#entryTable').length) { // There is no 'exists' check so we look for length.
					  		e.preventDefault();
				  		} 
				});
			</script>
                      
		</div><!-- .sidebar#sideLeft -->

		<div class="sidebar ui-widget-content" id="sideRight" style="border:none;">
			
            <?php
			
			if ( $_SESSION['is_manager'] != '0' ) {
				printMgmtMenuNG();
			}
			
			?>
            
		</div><!-- .sidebar#sideRight -->

	</div><!-- #middle-->

</div><!-- #wrapper -->

<div id="footer">
	
    <?php printFooterNG(); ?>
     
</div><!-- #footer -->

</body>
</html>
<?php
		exit;
	} // $dataTotal == 0 	
	
} // isset($_POST['submit']) && $_POST['submit'] == "Get Report"
  else if ( $_POST['submit'] == "Download CSV" ) {
	$_POST=from_array($_POST); // Massage the input with from_array().
	foreach($_POST as $k1=>$v1) $$k1=$v1; // Get all the $_POST key values and assign them associated variable names.
	// Get their direct reports.
	if ( $istype == 'suborg' ) { // This is a suborg report, so run directsOrg on the suborg userName.
		// First we need their users_id, cause all we have is their userName.
		$result_userid = mysql_query("SELECT users_id FROM users WHERE userName = '{$suborgname}'");
		if (!$result_userid) {
			unset($_POST);
			unset($_SESSION['Msg']);
			$_SESSION['Msg'] = "Unable to get users id for report. Close thiw window and contact the administrator.";
			header("Location: view_directs.php");
  			exit;
		}
		$row_userid = mysql_fetch_array($result_userid, MYSQL_ASSOC);
		mysql_free_result($result_userid);
		$directsOrg = getDirectsOrg($row_userid['users_id']);
	} else { // Just a regular, full organizational report.
		$directsOrg = getDirectsOrg($users_id);
	} // $istype == 'suborg'
	
	$count = count($directsOrg);
	if ( $directsOrg == '0' ) { // They have no direct reports.
		unset($_POST);
		unset($_SESSION['Msg']);
		$_SESSION['Msg'] = 'No direct reports found. Please try again.';
		header("Location: view_directs.php");
		exit;
	}
	// Ok, they have some direct reports. Let's get all the data for all of them.
	$output_data_csv = "userName,time_id,date,activity,hours\n";
	if ( $category == "total" || $category == "all" ) {
		$isEmptySet = '0';
		$hasTime = '0';
		foreach ( $directsOrg as $value ) {
			$result_userName = mysql_query("SELECT userName FROM users WHERE users_id = '{$value}'");
			if (!$result_userName) {
				unset($_POST);
				unset($_SESSION['Msg']);
				$_SESSION['Msg'] = "Unable to get userName for CSV report. Close this window and contact the administrator.";
				header("Location: view_directs.php");
  				exit;
			}
			$row_userName = mysql_fetch_array($result_userName, MYSQL_ASSOC);
			mysql_free_result($result_userName);
			$userNameTmp = $row_userName['userName'];
			$result_time = mysql_query("SELECT time_id,date,global_id,hours FROM time WHERE users_id = '{$value}' 
			AND ( date BETWEEN '{$startDate}' AND '{$endDate}' ) ORDER BY date ASC");
			if (!$result_time) {
				unset($_POST);
				unset($_SESSION['Msg']);
				$_SESSION['Msg'] = "Unable to get time for CSV report. Close this window and contact the administrator.";
				header("Location: view_directs.php");
  				exit;
			}
			
			if ( mysql_num_rows($result_time) < 1 ) {
				$isEmptySet = '1';
				mysql_free_result($result_time);
				continue;
			} else {
				$hasTime = '1';
			}
			
			while ( $row = mysql_fetch_array($result_time, MYSQL_ASSOC) ) {
				$activity = mysql_fetch_assoc(mysql_query("SELECT description FROM global_activity 
				WHERE global_id = '{$row['global_id']}'"));
				$output_data_csv .= $userNameTmp.",".$row['time_id'].",".$row['date'].",".$activity['description'].",".$row['hours']."\n";
			}
			mysql_free_result($result_time);	
		} // $directsOrg as $value
		
		if ( $isEmptySet == '1' && $hasTime == '0' ) {
			unset($_POST);
			unset($_SESSION['Msg']);
			$_SESSION['Msg'] = "No values to display for any users. Please try again.";
			header("Location: view_directs.php");
			exit;
		}	
		
	} // $category == "total"
	  else { // $category == n
		$isEmptySet = '0';
		$hasTime = '0';
		foreach ( $directsOrg as $value ) {
			$result_userName = mysql_query("SELECT userName FROM users WHERE users_id = '{$value}'");
			if (!$result_userName) {
				unset($_POST);
				unset($_SESSION['Msg']);
				$_SESSION['Msg'] = "Unable to get userName for CSV report. Close this window and contact the administrator.";
				header("Location: view_directs.php");
  				exit;
			}
			$row_userName = mysql_fetch_array($result_userName, MYSQL_ASSOC);
			mysql_free_result($result_userName);
			$userNameTmp = $row_userName['userName'];
			$result_global = mysql_query("SELECT global_id FROM global_activity WHERE categories_id = '{$category}'");
			if (!$result_global) {
				unset($_POST);
				unset($_SESSION['Msg']);
				$_SESSION['Msg'] = "Could not get global activities. Close this window and contact the administrator.";
				header("Location: view_directs.php");
				exit;
			}
			while ( $row_global = mysql_fetch_array($result_global, MYSQL_ASSOC) ) {
				$result_time = mysql_query("SELECT time_id,date,global_id,hours FROM time 
				WHERE users_id = '{$value}' AND ( date BETWEEN '{$startDate}' AND '{$endDate}' ) 
				AND global_id = '{$row_global['global_id']}' ORDER BY date ASC");
				if (!$result_time) {
					unset($_POST);
					unset($_SESSION['Msg']);
					$_SESSION['Msg'] = "Could not get time. Close this window and contact the administrator.";
					header("Location: view_directs.php");
  					exit;
				}
				
				if ( mysql_num_rows($result_time) < 1 ) {
					$isEmptySet = '1';
					mysql_free_result($result_time);
					continue;
				} else {
					$hasTime = '1';
				}
				
				while ( $row = mysql_fetch_array($result_time, MYSQL_ASSOC) ) {
					$activity = mysql_fetch_assoc(mysql_query("SELECT description FROM global_activity 
					WHERE global_id = '{$row['global_id']}'"));
					$output_data_csv .= $userNameTmp.",".$row['time_id'].",".$row['date'].",".$activity['description'].",".$row['hours']."\n";
				}
				mysql_free_result($result_time);
			} // $row_global = mysql_fetch_array($result_global, MYSQL_ASSOC)
			mysql_free_result($result_global);
		} // $directsOrg as $value
		
		if ( $isEmptySet == '1' && $hasTime == '0' ) {
			unset($_POST);
			unset($_SESSION['Msg']);
			$_SESSION['Msg'] = "No values to display for any users. Please try again.";
			header("Location: view_directs.php");
			exit;
		}
		
	} // $category == n
	
	
	
	// send headers and csv file
	header("Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0");
	header("Pragma: no-cache");
	header("Content-Description: File Transfer");
	//header("Content-type: application/vnd.ms-excel");
	header("Content-type: application/csv");
	header("Content-Disposition: attachment; filename=se_time_ORG_" . $_SESSION['userName'] . ".csv");
	// header("Content-Disposition: attachment; filename=se_time.csv");
	print $output_data_csv;
	exit();
} // $_POST['submit'] == "Download CSV"

if ( $_POST['submit'] == "Download Image" ) {
	// header('Content-Disposition: Attachment;filename=image.png');
	$downloadStr = " exportEnabled='1' exportHandler='Charts/FCExporter.php' exportAtClient='0' exportAction='download'";
}

$showLabels = '0'; // Default for pie chart. Set to 1 for bar charts though.

$barOptions = '';
if ( $_POST['chartType'] == "bar" ) {
	$barOptions = " yAxisName='Hours' xAxisName='Activity' useEllipsesWhenOverflow='1' rotateLabels='1' slantLabels='1'";
	$showLabels = '1';
}
	
$sizeX = "640"; $sizeY = "480";
if ( $category == "total" ) { // We need a bigger image size.
	$sizeX = "800"; $sizeY = "480";
}
$strXML = "<chart caption='".$title."' showPercentValues='1' showLegend='1' showLabels='".$showLabels."' showValues='1' use3DLighting='1' legendAllowDrag='1' numberPrefix='' formatNumberScale='0'".$downloadStr.$barOptions.">";

//Convert data to XML and append
while ( $data ) {
	$value = array_pop($data);
	$category = array_pop($legend);
	$strXML .= "<set label='" . $category . "' value='" . $value . "' />";
}

//Close <chart> element
$strXML .= "</chart>";

// Fusion XT
include("Charts/Includes/FusionCharts.php");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title><?php echo $_SESSION['TITLE']; ?></title>
	<meta name="keywords" content="" />
	<meta name="description" content="" />
    <script type="text/javascript" language="javaScript" src="js/calendar.js"></script>
  	<script type="text/javascript" language="javascript" src="js/setime.js"></script>
    <script type="text/javascript" language="javascript" src="js/jquery-1.7.1.min.js"></script>
  	<script type="text/javascript" language="javascript" src="js/jquery-ui-1.8.18.custom.min.js"></script>
    <script type="text/javascript" language="javascript" src="Charts/FusionCharts.js"></script>
    <link type="text/css" href="css/custom-theme/jquery-ui-1.8.18.custom.css" rel="stylesheet" media="screen, projection" />
  	<link type="text/css" href="css/calendar.css" rel="stylesheet" media="screen, projection" />
  	<link type="text/css" href="css/setime.css" rel="stylesheet" media="screen, projection" />
	<link rel="stylesheet" href="css/style.css" type="text/css" media="screen, projection" />
	
<script type="text/javascript">
	$(function() {
		$( "button, a", "#sideLeft" ).button();
		$( "button, a", "#sideRight" ).button();
		$( "button, a", "#footer" ).button();
		
		$('.disabled').button('disable'); // Initially disabled buttons until functionality is added.
	});
</script>

<script type="text/javascript">
	function showLoading() {
		$('.loading').show();
		return true;
	};

</script>


<script type="text/javascript">
	$(function() {
		$( "#datepicker" ).datepicker();
	});
</script>

<script type="text/javascript">
	
	/* $(document).ready(function () {

	    $.history.init(pageload);	
	    
		$('a[href=' + window.location.hash + ']').addClass('selected');
		
		$('a[rel=ajax]').click(function () {
		
			var hash = this.href;
			hash = hash.replace(/^.*#/, '');
	 		$.history.load(hash);	
	 		
	 		$('a[rel=ajax]').removeClass('selected');
	 		$(this).addClass('selected');
	 		$('#body').hide();
	 		$('.loading').show();
	 		
			// getPage();
	
			return false;
		});
	});
	
	function pageload(hash) {
		if (hash) getPage();    
	} */
	
function getPage(url) {
	/* var data = 'page=' + encodeURIComponent(document.location.hash); */
	/* var url = document.location.hash; */
	// $('#body').hide();
	$('#updates').hide();
	$('.loading').show();
	$.ajax({
		/* url: "loader.php",	
		type: "GET",	
		data: data, */
		url: url,
		type: "POST",
		cache: false,
		success: function (html) {	
			$('.loading').hide();				
			$('#updates').html(html);
			$('#updates').fadeIn('slow');
			// $('#body').fadeIn('slow');		
	
		}		
	});
}

</script>

<!--<script type="text/javascript">
$(function() { // NOTE - No longer used, but a good function as an example.
	/* var data = 'page=' + encodeURIComponent(document.location.hash); */
	/* var url = document.location.hash; */
	// $('#body').hide();
	
  // $(".submitButton").click(function() {
  
	
	var startDate = $("input#startDate").val();
	var endDate = $("input#endDate").val();
	var category = $("select#category").val();
	var chartType = $("input#chartType").val();
	var id = $("input#id").val();
	var submitType = $("input#submit").val();

	
	// $('#updates').hide();
	$('.loading').show();
	$.ajax({
		/* url: "loader.php",	
		type: "GET", */	
		// data: $("#reportTime").serialize(),
		data: dataString,
		url: "makeImageFCNG.php",
		type: "POST",
		cache: false,
		success: function (data) {	
			$('.loading').hide();
			$('#chartSelect').html("<div id='chartDisplay'></div>");
			$('#chartDisplay').html(data);
			//$('#updates').html(html);
			//$('#updates').fadeIn('slow');
			// $('#body').fadeIn('slow');		
	
		}		
	});
	return false;
  });
});
</script> -->
        
<script type="text/javascript">
	$(function() {
		$( "#startDate" ).datepicker({
			dateFormat: 'yy-mm-dd',
			changeMonth: true,
			changeYear: true,
			maxDate: "+0D"
		});
	});
</script>

<script type="text/javascript">
	$(function() {
		$( "#endDate" ).datepicker({
			dateFormat: 'yy-mm-dd',
			changeMonth: true,
			changeYear: true,
			maxDate: "+0D"
		});
	});
</script>


</head>

<!--<body onload="popupDirects('showDirects.php');"> -->
<!--<body onload="showLoading();"> -->
<body>

<div id="wrapper">

	<div id="middle">

		<div id="container">
			<div id="content" class="ui-widget-content" style="border:none;">
				
              <div style="font-size:24px; font-weight:bold; padding-bottom:5px;">
                <p align="center">&nbsp;<br />
                <?php echo $_SESSION['TITLE']; ?> for <span class="weak"><?php echo ' '.$_SESSION['firstName'].' '.$_SESSION['lastName'];?></span></p>
              </div>
              
              
                
                <div align="center" class="loading"><img src="images/orange_loading.gif" width="62" height="62" alt="Loading...." /></div>
                
                <div id="updates">
          
				<?php

				if ( $chartType == 'pie' ) {
					echo renderChart("Charts/Pie3D.swf", "", $strXML, "chart", "540", "590", false, true);
				} else { // $chartType == 'bar'
					echo renderChart("Charts/Column3D.swf", "", $strXML, "chart", "540", "590", false, true);
				}

				?>
				</div><!-- end updates -->
                
		  </div><!-- #content-->
		</div><!-- #container-->

		<div class="sidebar ui-widget-content" id="sideLeft" style="border:none;">
			<p align="center">Logged in as: <strong><?php echo $_SESSION['userName']; ?></strong></p>
            
            <?php printMenuLeftNG(); ?>
           
           <script type="text/javascript">
		   // Stop the #codes links from firing until time_entry2.php has loaded the #entryTable div.
				$("#codes").click(function(e) {
    				    if( !$('#entryTable').length ) { // There is no 'exists' check so we look for length.
					  		e.preventDefault();
				  		} 
				});
			</script> 
            
            <!--<script type="text/javascript">
				if( $('#updates').length ) {
					$('.loading').hide();
				}
            </script> -->
            
		</div><!-- .sidebar#sideLeft -->

		<div class="sidebar ui-widget-content" id="sideRight" style="border:none;">
			
            <?php
			
			if ( $_SESSION['is_manager'] != '0' ) {
				printMgmtMenuNG();
			}
			
			?>
            
		</div><!-- .sidebar#sideRight -->

	</div><!-- #middle-->

</div><!-- #wrapper -->

<div id="footer">
	
    <?php printFooterNG(); ?>
     
</div><!-- #footer -->

</body>
</html>
<?php
/*	OLD USING pChart

// Get the sizeof the $data array (which will be the same size as $legend). We have to do this to make sure we parse the
// values out of the array that are actually in there. pChart will vomit if you try to pass an array with only 1 element
// directly to AddPoint().
// So, we use the while loop below to extract the values from the array and pass them directly.
	$i = count($data); 
	$DataSet = new pData;
	
	foreach ( $data as $value ) {
		$DataSet->AddPoint(array($value),"Serie1");
	}
	
	foreach ( $legend as $value ) {
		$DataSet->AddPoint(array($value),"Serie2");
	}

	$DataSet->AddAllSeries();
	$DataSet->SetAbsciseLabelSerie("Serie2");
	// Initialise the graph
	$sizeX = "640"; $sizeY = "480";
	if ( $category == "total" ) { // We need a bigger image size.
		$sizeX = "800"; $sizeY = $sizeX;
	}
	$Test = new pChart($sizeX,$sizeY);
	$Test->setFontProperties("pChart/Fonts/tahoma.ttf",12);
	// $Test->drawFilledRoundedRectangle(2,2,393,193,5,240,240,240);
	// $Test->drawRoundedRectangle(0,0,395,195,5,230,230,230);
	
	if ( $category == "total" ) { // Have to do this again after the $Test object is instantiated.
		$size = count($data); // We need more shades in a bigger pallette.
		$Test->createColorGradientPalette(255,0,0,0,0,255,$size);
	}
	
	// Draw the pie chart
	$Test->AntialiasQuality = 0;
	// $Test->setShadowProperties(2,2,200,200,200);
	// Flat 2D pie chart
	// $Test->drawFlatPieGraphWithShadow($DataSet->GetData(),$DataSet->GetDataDescription(),150,135,85,PIE_PERCENTAGE,8);
	// 3D pie chart
	$Test->drawPieGraph($DataSet->GetData(),$DataSet->GetDataDescription(),180,130,120,PIE_PERCENTAGE,TRUE,50,20,5);
	$Test->clearShadow();
	$Test->drawPieLegend(10,245,$DataSet->GetData(),$DataSet->GetDataDescription(),255,255,255);
	// This will write in black "This is the title" at coordinate (100,15)  
	$Test->drawTitle(20,15,$title,0,0,0);  
	// $Test->Render("C:\\inetpub\\wwwroot\\pChart\\tmp\\example13.png");
	$Test->Stroke();


} else { // $chartType == 'bar'
	// They're looking for a bar chart. No, not that kind of bar.
	
	
    // Based on Example12 : A true bar graph
 	
  
   // Dataset definition 
   $DataSet = new pData;
   // This will be the total for the left most category. For example, in an "All Categories" chart, this would be the
   // Channel category totals. And AddPoint() will only get one value for this: the total number of hours.
   // $DataSet->AddPoint(array(1,4,-3,2,-3,3,2,1,0,7,4),"Serie1");
   // Following the same example above, this would be the Serivce Provider Category.
   // $DataSet->AddPoint(array(3,3,-4,1,-2,2,1,0,-1,6,3),"Serie2");
   // Etc. for the below sample.
   // $DataSet->AddPoint(array(4,1,2,-1,-4,-2,3,2,1,2,2),"Serie3");
   
  
  
   foreach ( $data as $i => $value ) {
   		$serieName = "Serie".$i;
		$DataSet->AddPoint(array($value),$serieName);
		$DataSet->SetSerieName($legend[$i].'  (Total Hours:'.$value.')',$serieName);
		$DataSet->AddSerie($serieName);
   }
   /*  
   while ( $i > 0 ) {
		$dataValue = $data[$i-1];
		$legendValue = $legend[$i-1];
		$serieName = "Serie".$i;
		$DataSet->AddPoint(array($dataValue),$serieName);
		$DataSet->SetSerieName($legendValue.'  (Total Hours:'.$dataValue.')',$serieName);
		$DataSet->AddSerie($serieName);
		// $DataSet->SetAbsciseLabelSerie($serieName);
		$i--;
	}
	*/
/*   
   // $DataSet->AddAllSeries();
   $DataSet->SetAbsciseLabelSerie(''); // This gets rid of the stupid '0' at the bottom of the X axis.
   
   //$DataSet->SetSerieName("January","Serie1");
   //$DataSet->SetSerieName("February","Serie2");
   //$DataSet->SetSerieName("March","Serie3");
   $DataSet->SetXAxisName("Activities");
   $DataSet->SetYAxisName("Hours");
   //$DataSet->SetYAxisUnit("m/s");
  
   // Initialise the graph
   $Test = new pChart(800,640);
   $Test->setFontProperties("pChart/Fonts/tahoma.ttf",12);
   $Test->setGraphArea(100,45,700,200);
   //$Test->drawFilledRoundedRectangle(7,7,693,223,5,240,240,240);
   //$Test->drawRoundedRectangle(5,5,695,225,5,230,230,230);
   $Test->drawGraphArea(255,255,255,TRUE);
   //$Test->drawScale($DataSet->GetData(),$DataSet->GetDataDescription(),SCALE_NORMAL,150,150,150,TRUE,0,2,TRUE);
   $Test->drawScale($DataSet->GetData(),$DataSet->GetDataDescription(),SCALE_START0,150,150,150,TRUE,0,2,TRUE,1);
   //$Test->drawGrid(4,TRUE,230,230,230,50);
  
   // Draw the 0 line
   $Test->setFontProperties("pChart/Fonts/tahoma.ttf",10);
   // $Test->drawTreshold(0,143,55,72,TRUE,TRUE);
   // $Test->drawTreshold(5,255,0,0,TRUE,TRUE,4,"Foo Free Text");
   
   if ( $category == "total" ) { // Have to do this again after the $Test object is instantiated.
		$size = count($data); // We need more shades in a bigger pallette.
		$Test->createColorGradientPalette(255,0,0,0,0,255,$size);
	}
  
   // Draw the bar graph
   $Test->drawBarGraph($DataSet->GetData(),$DataSet->GetDataDescription(),FALSE,80);
   
   // Set some labels. This has to be done after all of the data is initialized.
   /* This doesn't work.
   $i = count($data); // reset $i.
   $Test->setFontProperties("pChart/Fonts/tahoma.ttf",8); // Adjust the font size down for the labels.
   while ( $i > 0 ) {
		$dataValue = $data[$i-1];
		$legendValue = $legend[$i-1];
		$serieName = "Serie".$i;
   		$Test->setLabel($DataSet->GetData(),$DataSet->GetDataDescription(),$serieName,$dataValue,$legendValue,239,233,195);
		$i--;
   }
   */
   
   // $Test->setFontProperties("pChart/Fonts/tahoma.ttf",8);  
   // $Test->setLabel($DataSet->GetData(),$DataSet->GetDataDescription(),"Serie1","2","Daily incomes",221,230,174);
   // $Test->setLabel($DataSet->GetData(),$DataSet->GetDataDescription(),"Serie2","6","Production break",239,233,195);
  
/* 
   // Finish the graph
   $Test->setFontProperties("pChart/Fonts/tahoma.ttf",12);
   $Test->drawLegend(100,250,$DataSet->GetDataDescription(),255,255,255);
   $Test->setFontProperties("pChart/Fonts/tahoma.ttf",14);
   $Test->drawTitle(50,22,$title,50,50,50,585);
   //$Test->Render("example12.png");
   $Test->Stroke();
  //$gTotal = array_sum($data);
  //echo 'grand total: '.$gTotal.'<br />';
  //print_r($legend);
  //print_r($data);
} // $chartType == 'pie' */
?>
