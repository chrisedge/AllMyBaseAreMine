<?php
define('INCLUDE_CHECK', true);
error_reporting (E_ALL ^ E_NOTICE);

require 'connect.php';
require 'functions.php';
// Those two files can be included only if INCLUDE_CHECK is defined

session_name('seLogin');
session_set_cookie_params(2*7*24*60*60);
session_cache_limiter('public'); // Reset so IE will load Flash over HTTPS properly.
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

if ( !( isset($_POST['submit']) ) ) {
	unset($_SESSION['Msg']);
	$_SESSION['Msg'] = "No values submitted. Please try again.";
	unset($_POST);
	header("Location: view_trend.php");
	exit;
}

if ( isset($_POST['startDate']) && $_POST['startDate'] == ''  ) {
	unset($_SESSION['Msg']);
	$_SESSION['Msg'] = "No start date submitted. Please try again.";
	unset($_POST);
	header("Location: view_trend.php");
	exit;
}

if ( isset($_POST['endDate']) && $_POST['endDate'] == ''  ) {
	unset($_SESSION['Msg']);
	$_SESSION['Msg'] = "No end date submitted. Please try again.";
	unset($_POST);
	header("Location: view_trend.php");
	exit;
}

if ( isset($_POST['activity1']) && $_POST['activity1'] == ''  ) {
	unset($_SESSION['Msg']);
	$_SESSION['Msg'] = "No activity submitted. Please try again.";
	unset($_POST);
	header("Location: view_trend.php");
	exit;
}

$users_id = $_SESSION['users_id'];

// <img src="makeImage.php?total_values= echo $num_rows; "  />

set_time_limit(300);

if ( isset($_POST['submit']) && $_POST['submit'] == "Get Report" || $_POST['submit'] == "Download Image" ) {
//$_POST=from_array($_POST); // Massage the input with from_array().
foreach($_POST as $k1=>$v1) $$k1=$v1; // Get all the $_POST key values and assign them associated variable names.
// print_r($_POST); exit;

// Split apart the startDate and endDate values into startYear, endYear, startMonth, and endMonth.
// The values themselves will be used in the SQL statements to accumulate the data and used for labeling the x axis.
list($startYear, $startMonth) = explode("-", $startDate);
list($endYear, $endMonth) = explode("-", $endDate);
// DEBUG echo '<br />startYear: '.$startYear.' startMonth: '.$startMonth;
$stringMonth = date("M", mktime(0, 0, 0, ($startMonth)));
// DEBUG echo '<br />startMonth as text: '.$stringMonth;


// Since we're doing monthly totals, we need the first day of the month for $startDate. We then convert this into a UNIX timestamp.
$fullStartDate = $startYear.'-'.$startMonth.'-01';
// DEBUG echo '<br />fullStartDate: '.$fullStartDate;
$startDateNum = strtotime($fullStartDate); // Convert to timestamp.
// echo '<br />startDate timestamp: '.$startDateNum;

// Increment $fullStartDate by 1 month.
// $lastWeek = date("Y-m-d",(strtotime('-6 days', $endDateNum))); // Then get -6 days, and convert back to Y-m-d.
// $nextMonthStart = date("Y-m-d",(strtotime('+1 month', $startDateNum)));
// echo '<br />nextMonthStart: '.$nextMonthStart;
// $nextMonthStartNum = strtotime($nextMonthStart);
//$nextMonthDayLast = date("Y-m-t",(strtotime($nextMonthDayOne)));
// $nextMonthDayLast = date("Y-m-d",(strtotime('+30 days', $nextMonthStartNum)));
// echo '<br />nextMonthDayLast: '.$nextMonthDayLast;

// Convert endDate into a timestamp so we can get the last date of the month.
$endDateNum = strtotime($endDate);
$lastEndDateDay = date(t,$endDateNum); // t gives us the number of days in the given month, provided the timestamp.
$fullEndDate = $endYear.'-'.$endMonth.'-'.$lastEndDateDay;
// DEBUG echo '<br />fullEndDate: '.$fullEndDate;
$endDateNum = strtotime($fullEndDate); // Convert to timestamp.
// echo '<br />endDate timestamp: '.$endDateNum;
	
/*
 * For all of directOrg we need a total of all hours for all activities for the month. We then get the total for activity1 and convert
 * it into a % of hours spent. This % will form the dotted line data path on the chart for activity1.
 */
$tmpStartDate = $fullStartDate;
$tmpEndDate = date("Y-m-d",(strtotime('+30 days', $startDateNum)));
$tmpEndDateNum = strtotime($tmpEndDate);
$tmpEndDate = date("Y-m-d",(strtotime('-1 day', $tmpEndDateNum)));
// echo '<br />tmpStartDate: '.$tmpStartDate.'<br />tmpEndDate: '.$tmpEndDate;
// exit;

if ( $_POST['submit'] == "Download Image" ) {
	// header('Content-Disposition: Attachment;filename=image.png');
	$downloadStr = " exportEnabled='1' exportHandler='Charts/FCExporter.php' exportAtClient='0' exportAction='download'";
} else { $downloadStr = ''; }

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
	$subOrgTitle = " For Organization: ".$suborgname."\\r\\n";
	$directsOrg = getDirectsOrg($row_userid['users_id']);
	mysql_free_result($result_userid);
} else { // Just a regular, full organizational report.
	$directsOrg = getDirectsOrg($users_id);
	// print_r($directsOrg);
	// echo 'directsOrg total: '.count($directsOrg).'<br />';
}   // $istype == 'suborg'
// echo $subOrgTitle;





// $directsOrg = getDirectsOrg($users_id);
// print_r($directsOrg);

if ( $directsOrg == '0' ) { // They have no direct reports.
	unset($_POST);
	unset($_SESSION['Msg']);
	$_SESSION['Msg'] = "No direct reports found for you. If you believe this is an error contact the administrator.";
	header("Location: view_trend.php");
	exit;
}

$regionTitle = $subOrgTitle.' For Theatre: All';

if ( isset($_POST['region']) && $_POST['region'] != "all" ) {
	// Sort directsOrg by region.
	// $tmpDirectsOrg = $directsOrg; // Copy.
	while ( $directsOrg ) {
		$value = array_shift($directsOrg);
		$result_region = mysql_query("SELECT users_id FROM profile WHERE regions_id = '{$region}' AND users_id = '{$value}'");
		if (!$result_region) {
			$_SESSION['Msg'] = "Could not get region information for users. Contact the administrator.";
			header("Location: redirect.php?Url=index.php?logoff");
			exit;
		}
		
		$numRows = mysql_num_rows($result_region);
		if ( $numRows < 1 ) {
			/*
			unset($_SESSION['Msg']);
			$_SESSION['Msg'] = "No users found in the this region to report on. Please try again.";
			header("Location: view_trend.php");
			exit;
			*/ 
			mysql_free_result($result_region);
			continue;
		} else {
			$row_region = mysql_fetch_array($result_region,MYSQL_ASSOC);
			// Update the tmpDirectsOrg array.
			$tmpDirectsOrg[] = $row_region['users_id'];
			mysql_free_result($result_region);
		}
	} // $directsOrg as $value
	
	$directsOrg = $tmpDirectsOrg; // Copy back.
	
	if ( $directsOrg == '0' ) { // They have no direct reports.
		unset($_POST);
		unset($_SESSION['Msg']);
		$_SESSION['Msg'] = "No users found in this region. If you believe this is an error contact the administrator.";
		header("Location: view_trend.php");
		exit;
	}
	
	$result_regions = mysql_query("SELECT region FROM regions WHERE regions_id = '{$region}'");
	if (!$result_regions) {
		$_SESSION['Msg'] = "Could not get regions from regions table. Contact the administrator.";
		header("Location: redirect.php?Url=index.php?logoff");
		exit;
	}
	$row_regions = mysql_fetch_array($result_regions, MYSQL_ASSOC);
	$regionTitle = " For Theatre: ".$row_regions['region'];
	mysql_free_result($result_regions);
} // isset($_POST['region']) && ($_POST['region'] != "all" || $_POST['region'] != '')

// print_r($directsOrg); exit;


// Here comes the heavy lifting.
while ( $startDateNum <= $endDateNum ) {
	// $directsOrg = getDirectsOrg($users_id);
	// $tmpStartDate = '2009-04-01';
	// $tmpEndDate = '2012-04-30';
	$tmpTotal = 0.0;
	$grandTotal = 0.0;
	$tmpCatTotal = 0.0;
	$grandCatTotal = 0.0;
	$categoryID = $activity1; 
	
	foreach ( $directsOrg as $value ) {
		$tmpTotal = getHoursByUser($value,$tmpStartDate,$tmpEndDate);
		$tmpCatTotal = getHoursByGlobal($value,$categoryID,$tmpStartDate,$tmpEndDate);
		// echo '<br />$tmpTotal for '.$value.': '.$tmpTotal;
		// echo '<br />$tmpCatTotal for '.$value.': '.$tmpCatTotal;
		$grandTotal += $tmpTotal;
		$grandCatTotal += $tmpCatTotal;
	}
	
	// DEBUG echo '<br />grandTotal: '.$grandTotal;
	// DEBUG echo '<br />grandCatTotal: '.$grandCatTotal;
	$tmpRatio = round( ( ($grandCatTotal/$grandTotal) * 100 ) );
	// DEBUG echo '<br />ratio for activity_id '.$activity1.' for date range '.$tmpStartDate.'-'.$tmpEndDate.': '.$tmpRatio;
	$ratios[] = $tmpRatio; // Shove the ratio into an array to be spit out later.
	
	// Format the month-year for the xaxis labels.
	list($labelStartYear, $labelStartMonth) = explode("-", $tmpStartDate);
	$labelStringMonth = date("M", mktime(0, 0, 0, ($labelStartMonth))); // Abbrev. month name
	$labelStringYear = date(y,(strtotime($tmpStartDate))); // Two digit year
	$labels[] = $labelStringMonth.'-'.$labelStringYear; // Shove it in the labels array to be spit out later.
	
	// Increment the date.
	$tmpStartDateNum = strtotime($tmpStartDate);
	$nextMonthStart = date("Y-m-d",(strtotime('+1 month', $tmpStartDateNum)));
	$nextMonthStartNum = strtotime($nextMonthStart);
	$nextMonthEnd = date("Y-m-d",(strtotime('+1 month', $nextMonthStartNum)));
	$nextMonthEndNum = strtotime($nextMonthEnd);
	$nextMonthEnd = date("Y-m-d",(strtotime('-1 day', $nextMonthEndNum)));
	// Reset our tmp variables passed into the function that does the SQL...
	$tmpStartDate = $nextMonthStart; // echo '<br />tmpStartDate now: '.$tmpStartDate;
	$tmpEndDate = $nextMonthEnd; // echo '<br />tmpEndDate now: '.$tmpEndDate;
	// Here we increment $startDateNum so at some point it will be >= $endDateNum that controls this loop.
	$startDateNum = strtotime($tmpEndDate); // echo '<br />startDateNum now: '.$startDateNum;
	
} // $startDateNum <= $endDateNum

// print_r($ratios); print_r($labels); exit;

$ratiosTmp = $ratios; // Make a copy to be used for the avg calculation.


$result_category = mysql_query("SELECT description FROM global_activity WHERE global_id = '{$activity1}'");
if (!$result_category) {
	$_SESSION['Msg'] = "Could not get description from global_activity table. Contact the administrator.";
	header("Location: redirect.php?Url=index.php?logoff");
	exit;
}
$row_category = mysql_fetch_array($result_category, MYSQL_ASSOC);
mysql_free_result($result_category);

$multi = '0';
// Check for second activity. If so, we get to do all of this again.
if ( isset($_POST['activity2']) && $_POST['activity2'] != '' ) {
	$multi = '1';
	$result_category2 = mysql_query("SELECT description FROM global_activity WHERE global_id = '{$activity2}'");
	if (!$result_category2) {
		$_SESSION['Msg'] = "Could not get description from global_activity table. Contact the administrator.";
		header("Location: redirect.php?Url=index.php?logoff");
		exit;
	}
	$row_category2 = mysql_fetch_array($result_category2, MYSQL_ASSOC);
	mysql_free_result($result_category2);
	
	$startDateNum = strtotime($fullStartDate); // Reset all the time variables that were changed in the first iteration.
	$tmpStartDate = $fullStartDate;
	$tmpEndDate = date("Y-m-d",(strtotime('+30 days', $startDateNum)));
	$tmpEndDateNum = strtotime($tmpEndDate);
	$tmpEndDate = date("Y-m-d",(strtotime('-1 day', $tmpEndDateNum)));
	
	
	while ( $startDateNum <= $endDateNum ) {
		// $directsOrg = getDirectsOrg($users_id);
		// $tmpStartDate = '2009-04-01';
		// $tmpEndDate = '2012-04-30';
		$tmpTotal = 0.0;
		$grandTotal = 0.0;
		$tmpCatTotal = 0.0;
		$grandCatTotal = 0.0;
		$categoryID = $activity2; 
		
		foreach ( $directsOrg as $value ) {
			$tmpTotal = getHoursByUser($value,$tmpStartDate,$tmpEndDate);
			$tmpCatTotal = getHoursByGlobal($value,$categoryID,$tmpStartDate,$tmpEndDate);
			// echo '<br />$tmpTotal for '.$value.': '.$tmpTotal;
			// echo '<br />$tmpCatTotal for '.$value.': '.$tmpCatTotal;
			$grandTotal += $tmpTotal;
			$grandCatTotal += $tmpCatTotal;
		}
		
		// DEBUG echo '<br />grandTotal: '.$grandTotal;
		// DEBUG echo '<br />grandCatTotal: '.$grandCatTotal;
		$tmpRatio = round( ( ($grandCatTotal/$grandTotal) * 100 ) );
		// DEBUG echo '<br />ratio for activity_id '.$activity1.' for date range '.$tmpStartDate.'-'.$tmpEndDate.': '.$tmpRatio;
		$ratios2[] = $tmpRatio; // Shove the ratio into an array to be spit out later.
		
		/* No need to redo the labels the second time around.
		// Format the month-year for the xaxis labels.
		list($labelStartYear, $labelStartMonth) = explode("-", $tmpStartDate);
		$labelStringMonth = date("M", mktime(0, 0, 0, ($labelStartMonth))); // Abbrev. month name
		$labelStringYear = date(y,(strtotime($tmpStartDate))); // Two digit year
		$labels2[] = $labelStringMonth.'-'.$labelStringYear; // Shove it in the labels array to be spit out later.
		*/
		
		// Increment the date.
		$tmpStartDateNum = strtotime($tmpStartDate);
		$nextMonthStart = date("Y-m-d",(strtotime('+1 month', $tmpStartDateNum)));
		$nextMonthStartNum = strtotime($nextMonthStart);
		$nextMonthEnd = date("Y-m-d",(strtotime('+1 month', $nextMonthStartNum)));
		$nextMonthEndNum = strtotime($nextMonthEnd);
		$nextMonthEnd = date("Y-m-d",(strtotime('-1 day', $nextMonthEndNum)));
		// Reset our tmp variables passed into the function that does the SQL...
		$tmpStartDate = $nextMonthStart; // echo '<br />tmpStartDate now: '.$tmpStartDate;
		$tmpEndDate = $nextMonthEnd; // echo '<br />tmpEndDate now: '.$tmpEndDate;
		// Here we increment $startDateNum so at some point it will be >= $endDateNum that controls this loop.
		$startDateNum = strtotime($tmpEndDate); // echo '<br />startDateNum now: '.$startDateNum;
		
	} // $startDateNum <= $endDateNum
	
	$ratiosTmp2 = $ratios2; // Copy.
		
} // isset($_POST['activity2']) && $_POST['activity2'] != ''

// We need completely different formatting of the XML for the chart for multi vs. single series charts.
if ( $multi == '1' ) {
	$strXML = "<chart caption='6 Month Moving Average".$regionTitle."' useEllipsesWhenOverflow='1' rotateLabels='1' slantLabels='1' showLegend='1'
	showValues='0' drawAnchors='0' legendAllowDrag='1' numberSuffix='%' xAxisName='Month' yAxisName='Activities'".$downloadStr.">";
	
	$strXML .= "<categories> ";
	while ( $labels ) {
		$labelValue = array_shift($labels);
		$strXML .= "<category label='".$labelValue."' />";
	}
	$strXML .= "</categories>";
	
	$strXML .= "<dataset seriesName='".$row_category['description']."' color='EB0C65' plotBorderColor='EB0C65' renderAs='line' dashed='1' dashLen='2' dashGap='2' alpha='40'>";
	
	while ( $ratios ) {
		$ratioValue = array_shift($ratios);
		$strXML .= "<set value='".$ratioValue."' />";
	}
	$strXML .= "</dataset>";
	
	
	
	$strXML .= "<dataset seriesName='".$row_category2['description']."' color='3C0CEB' plotBorderColor='3C0CEB' renderAs='line' dashed='1' dashLen='2' dashGap='2' alpha='40'>";
	
	while ( $ratios2 ) {
		$ratioValue = array_shift($ratios2);
		$strXML .= "<set value='".$ratioValue."' />";
	}
	$strXML .= "</dataset>";
	
	// Calculate a 6 month moving average of this data. Since we're doing a six month moving average, data point 2 of our average line will be the average
	// of $ratios 1-6, and should show up at month 7 along the x axis. Data point 2 is the average of $ratios 2-7 and should show up at month 8, etc.
	// We will use 0 for trendline values 1-6, and also apply alpha='0' to keep them hidden.
	
	// First, $ratiosTmp, which is activity1.
	$strXML .= "<dataset color='EB0C65' plotBorderColor='EB0C65' renderAs='line'>";
	$x = 0;
	while ( $ratiosTmp ) {
		// Pop off the first 6 values, and generate dummy trendline values.
		while ( $x <= 5 ) {
			$emptyValue = array_shift($ratiosTmp);
			$strXML .= "<set value='0' alpha='0' />";
			// Also store the values in tmpAvg along the way.
			$tmpAvg[] = $emptyValue;
			$x++;
		} // $x <= 5
		// We should now be at the 6th element in $ratiosTmp. So, we need to compute the average of the 6 elements in tmpAvg.
		$tmpAvgValue = ( array_sum($tmpAvg) / 6 );
		$strXML .= "<set value='".$tmpAvgValue."' alpha='100' />";
		$emptyValue = array_shift($ratiosTmp);
		$tmpAvg[] = $emptyValue; // Insert element 6 from $ratiosTmp onto the bottom of $tmpAvg.....
		$emptyValue = array_shift($tmpAvg); // ...and shift the top off of $tmpAvg. This shifting of the values allows us to compute the moving average.	
	} // $ratios
	
	$strXML .= "</dataset>";
	// echo $strXML; exit;
	
	// Next, $ratiosTmp2, which is activity2.
	$strXML .= "<dataset color='3C0CEB' plotBorderColor='3C0CEB' renderAs='line'>";
	$x = 0;
	while ( $ratiosTmp2 ) {
		// Pop off the first 6 values, and generate dummy trendline values.
		while ( $x <= 5 ) {
			$emptyValue = array_shift($ratiosTmp2);
			$strXML .= "<set value='0' alpha='0' />";
			// Also store the values in tmpAvg along the way.
			$tmpAvg2[] = $emptyValue;
			$x++;
		} // $x <= 5
		// We should now be at the 6th element in $ratiosTmp. So, we need to compute the average of the 6 elements in tmpAvg.
		$tmpAvgValue = ( array_sum($tmpAvg2) / 6 );
		$strXML .= "<set value='".$tmpAvgValue."' alpha='100' />";
		$emptyValue = array_shift($ratiosTmp2);
		$tmpAvg2[] = $emptyValue; // Insert element 6 from $ratiosTmp onto the bottom of $tmpAvg.....
		$emptyValue = array_shift($tmpAvg2); // ...and shift the top off of $tmpAvg. This shifting of the values allows us to compute the moving average.	
	} // $ratios
	
	$strXML .= "</dataset>";
	// echo $strXML; exit;
	
	
	
	
} else { // $multi == '0'
	$strXML = "<chart caption='6 Month Moving Average".$regionTitle."' useEllipsesWhenOverflow='1' rotateLabels='1' slantLabels='1' showValues='0' numberSuffix='%' xAxisName='Month' yAxisName='".$row_category['description']."'".$downloadStr." drawAnchors='0'>";
	
	$strXML .= "<categories> ";
	while ( $labels ) {
		$labelValue = array_shift($labels);
		$strXML .= "<category label='".$labelValue."' />";
	}
	$strXML .= "</categories>";
	
	$strXML .= "<dataset seriesName='".$row_category['description']."' color='EB0C65' plotBorderColor='EB0C65' renderAs='line' dashed='1' dashLen='2' dashGap='2'>";
	
	while ( $ratios ) {
		$ratioValue = array_shift($ratios);
		$strXML .= "<set value='".$ratioValue."' />";
	}
	$strXML .= "</dataset>";
	
	// First, $ratiosTmp, which is activity1.
	$strXML .= "<dataset color='EB0C65' plotBorderColor='EB0C65' renderAs='line'>";
	$x = 0;
	while ( $ratiosTmp ) {
		// Pop off the first 6 values, and generate dummy trendline values.
		while ( $x <= 5 ) {
			$emptyValue = array_shift($ratiosTmp);
			$strXML .= "<set value='0' alpha='0' />";
			// Also store the values in tmpAvg along the way.
			$tmpAvg[] = $emptyValue;
			$x++;
		} // $x <= 5
		// We should now be at the 6th element in $ratiosTmp. So, we need to compute the average of the 6 elements in tmpAvg.
		$tmpAvgValue = ( array_sum($tmpAvg) / 6 );
		$strXML .= "<set value='".$tmpAvgValue."' alpha='100' />";
		$emptyValue = array_shift($ratiosTmp);
		$tmpAvg[] = $emptyValue; // Insert element 6 from $ratiosTmp onto the bottom of $tmpAvg.....
		$emptyValue = array_shift($tmpAvg); // ...and shift the top off of $tmpAvg. This shifting of the values allows us to compute the moving average.	
	} // $ratios
	
	$strXML .= "</dataset>";
	// echo $strXML; exit;
	
	/*
	while ( $ratios ) {
		$ratioValue = array_shift($ratios);
		$labelValue = array_shift($labels);
		$strXML .= "<set label='".$labelValue."' value='".$ratioValue."' /> ";
	}
	*/

}// $multi == '1'

//Close <chart> element
$strXML .= "</chart>";

//echo '<br />';
//echo $strXML;
//exit;

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
	function hideLoading() {
		//$("#modalLoading").dialog("close");
		$('.loading').fadeOut('slow');
		$('.loading').hide();
		$('#updates').fadeIn('slow');
		// return true;
	};

</script>


<script type="text/javascript">
	$(function() {
		$( "#datepicker" ).datepicker();
	});
</script>

<script type="text/javascript">
	
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
<body onload="hideLoading();">

<div id="wrapper">

	<div id="middle">

		<div id="container">
			<div id="content" class="ui-widget-content" style="border:none;">
				
              <div style="font-size:24px; font-weight:bold; padding-bottom:5px;">
                <p align="center">&nbsp;<br />
                <?php echo $_SESSION['TITLE']; ?> for <span class="weak"><?php echo ' '.$_SESSION['firstName'].' '.$_SESSION['lastName'];?></span></p>
              </div>
              
              
                
                <div align="center" class="loading">
                <img src="images/orange_loading.gif" width="62" height="62" alt="Loading...." />
                </div>
                
                <div id="updates">
          
				<?php

				if ( $chartType == 'sixSMA' && $multi == '0' ) {
					echo renderChart("Charts/MSLine.swf", "", $strXML, "chart", "540", "590", false, true);
				} else { // multi series
					echo renderChart("Charts/MSLine.swf", "", $strXML, "chart", "540", "590", false, true);
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
unset($_SESSION['Msg']);
exit;

// THIS IS OLD CODE BELOW, WE SHOULD NOT GET HERE NOW.

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
	
	// Ok, they have some direct reports. Let's get all the data for all of them based on whatever category(s) they're looking for.
	if ( $category == "local" ) {
		$title = "Organizational Report for ".$_SESSION['firstName']. " ".$_SESSION['lastName']." by Local Codes".$subOrgTitle."\\r\\nTime Period: ".$startDate." to ".$endDate;
		// the $directsOrg array is an array of users_id's. Pass them to getHoursByLocal.
		$j = 0;
		$data = array();
		$legend = array();
		$tmpTotal = 0.0;
		
		$result_local = mysql_query("SELECT local_id,description FROM local_activity");
		if (!$result_local) {
			unset($_SESSION['Msg']);
			$_SESSION['Msg'] = "Could not get local_id's from local_activity table for local report. Please contact the administrator.";
			header("Location: view_directs.php");
			exit;
		}
		$numRows = mysql_num_rows($result_local);
		if ( $numRows < 1 ) {
			unset($_SESSION['Msg']);
			$_SESSION['Msg'] = "No local_id's found in local_activity table for local report. Please try again or contact the administrator.";
			header("Location: view_directs.php");
			exit;
		}
		
		while ( $row_local = mysql_fetch_array($result_local, MYSQL_ASSOC) ) {
			$data[$j] = 0.0;
			foreach ( $directsOrg as $value ) {
				// Here we are adding together all the hours for each local_id, for every user contained within directsOrg. If any of the users
				// don't have hours input against this local_id, getHoursByLocal will return 0.0 anyway.
				$tmpTotal = getHoursByLocal($value,$row_local['local_id'],$startDate,$endDate);
				if ( $tmpTotal == 0.0 ) { continue; }
				$data[$j] += $tmpTotal; // Accumulate the total.
				
			} // foreach ( $directsOrg as $value )
			$legend[$j] = $row_local['description'];
			$j++;
			$tmpTotal = 0.0; // Reset tmpTotal because we're about to move on to a different local_id.
		} // while ( $row_local = mysql_fetch_array($result_local, MYSQL_ASSOC) )
		mysql_free_result($result_local);
		
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
		
		
	} else if ( $category == "total" ) {
		// $title = "Organizational Report for ".$_SESSION['firstName']. " ".$_SESSION['lastName'].": Total for ".$count." users"; -- REMOVED number of users.
	  	$title = "Organizational Report for ".$_SESSION['firstName']. " ".$_SESSION['lastName']." by Total".$subOrgTitle."\\r\\nTime Period: ".$startDate." to ".$endDate;
		$result_total = mysql_query("SELECT global_id,description FROM global_activity 
		WHERE isActive = '1' ORDER BY description ASC");
		if (!$result_total) {
			unset($_SESSION['Msg']);
			$_SESSION['Msg'] = "Could not get global_id from global table for total report. Please contact the administrator.";
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
		
	} // $category == "local"
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
                <?php echo $_SESSION['TITLE']; ?> for <span class="weak"><?php echo ' '.$_SESSION['firstName'].' '.$_SESSION['lastName'];?></span></p>
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
			<p align="center">Logged in as: <strong><?php echo $_SESSION['userName']; ?></strong></p>
            
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
		
	} else if ( $category == "local" ) {// $category == "total"
		$output_data_csv = "userName,local_id,description,date,hours\n";

		$result_local = mysql_query("SELECT local_id,description FROM local_activity");
		if (!$result_local) {
			unset($_SESSION['Msg']);
			$_SESSION['Msg'] = "Could not get local_id's from local_activity table for local report. Please contact the administrator.";
			header("Location: view_directs.php");
			exit;
		}
		$numRows = mysql_num_rows($result_local);
		if ( $numRows < 1 ) {
			unset($_SESSION['Msg']);
			$_SESSION['Msg'] = "No local_id's found in local_activity table for local report. Please try again or contact the administrator.";
			header("Location: view_directs.php");
			exit;
		}
		
		$isEmptySet = '0';
		$hasTime = '0';
		while ( $row_local = mysql_fetch_array($result_local, MYSQL_ASSOC) ) {
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
				// Here we are just outputting the hours to the csv file.
				$result_tmpTime = mysql_query("SELECT date,hours FROM time 
											  WHERE users_id = '{$value}' AND local_id = '{$row_local[local_id]}' 
											  AND ( date BETWEEN '{$startDate}' AND '{$endDate}' ) ORDER BY date ASC");
				if (!$result_tmpTime) {
					unset($_POST);
					unset($_SESSION['Msg']);
					$_SESSION['Msg'] = "Unable to get date and hours from time table for CSV report. Close this window and contact the administrator.";
					header("Location: view_directs.php");
					exit;
				}
				$numRows = mysql_num_rows($result_tmpTime);
				if ( mysql_num_rows($result_tmpTime) < 1 ) {
					$isEmptySet = '1';
					mysql_free_result($result_tmpTime);
					continue;
				} else {
					$hasTime = '1';
				}
					
				while ( $row_tmpTime = mysql_fetch_array($result_tmpTime, MYSQL_ASSOC) ) {
					// userName,local_id,description,date,hours
					$output_data_csv .= $userNameTmp.",".$row_local['local_id'].",".$row_local['description'].",".$row_tmpTime['date'].",".$row_tmpTime['hours']."\n";
				} // while ( $row_tmpTime = mysql_fetch_array($result_tmpTime, MYSQL_ASSOC) )
				mysql_free_result($result_tmpTime);
				
			} // foreach ( $directsOrg as $value )
			
		} // while ( $row_local = mysql_fetch_array($result_local, MYSQL_ASSOC) )
		mysql_free_result($result_local);
		
		if ( $isEmptySet == '1' && $hasTime == '0' ) {
			unset($_POST);
			unset($_SESSION['Msg']);
			$_SESSION['Msg'] = "No values to display for any users. Please try again.";
			header("Location: view_directs.php");
			exit;
		}
		
	
	} else { // $category == n
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

?>
