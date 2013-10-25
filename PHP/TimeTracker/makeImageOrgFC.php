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
	if ( $istype == 'suborg' ) { // This is a suborg report, so run directsOrg on the suborg userName.
		// First we need their users_id, cause all we have is their userName.
		$result_userid = mysql_query("SELECT users_id FROM users WHERE userName = '{$suborgname}'");
		if (!$result_userid) {
			$_SESSION['Msg'] = "Unable to get users id from user name for suborg report. Close this window and contact the administrator.";
  			exit;
		}
		$row_userid = mysql_fetch_array($result_userid, MYSQL_ASSOC);
		mysql_free_result($result_userid);
		$directsOrg = getDirectsOrg($row_userid['users_id']);
	} else { // Just a regular, full organizational report.
		$directsOrg = getDirectsOrg($users_id);
		// print_r($directsOrg);
		// echo 'directsOrg total: '.count($directsOrg).'<br />';
	} // $istype == 'suborg'
	
	$count = count($directsOrg);
	if ( $directsOrg == '0' ) { // They have no direct reports.
		unset($_POST);
		$_SESSION['Msg'] = "No direct reports found for you. You may close this window. If you believe this is an error contact the administrator.";
  		exit;
	}
	// Ok, they have some direct reports. Let's get all the data for all of them.
	if ( $category == "total" ) {
		$title = "Organizational Report for ".$_SESSION['firstName']. " ".$_SESSION['lastName'].": Total for ".$count." users";
	  	$result_total = mysql_query("SELECT global_id,description FROM global_activity 
		WHERE isActive = '1' ORDER BY description ASC");
		if (!$result_total) {
			$_SESSION['Msg'] = "Could not get global_id from global table for total report. Close this window and contact the administrator.";
  			exit;
		}
		$num_rows = mysql_num_rows($result_total);
		if ( $num_rows == 0 ) {
			$_SESSION['Msg'] = "No active global_id's found for total report? You may close this window.";
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
		$title = "Organizational Report for ".$_SESSION['firstName']. " ".$_SESSION['lastName']." by Category for ".$count." users";
		// Understand now, that "all" isn't just necessarily category id's 1-5. As of this writing, yes, that is all
		// the categories there are. However, the admin can add and delete categories. So we need to handle this
		// dynamically, like everything else.
		$result_categories = mysql_query("SELECT * FROM categories");
		if (!$result_categories) {
			$_SESSION['Msg'] = "Could not get categories from categories table.  Close this window and contact the administrator.";
  			exit;
		}
		$num_rows = mysql_num_rows($result_categories); // To keep track of the number of categories returned.
		if ( $num_rows == 0 ) {
			$_SESSION['Msg'] = "No categories retrieved from categories table? Close this window and try again.";
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
			$_SESSION['Msg'] = "Could not get category name for category. Close this window and contact the administrator.";
  			exit;
		}
		$row_categoryName = mysql_fetch_array($result_categoryName, MYSQL_ASSOC);
		mysql_free_result($result_categoryName);
		$categoryName = $row_categoryName['category'];
		$title = "Organizational Report for ".$_SESSION['firstName']. " ".$_SESSION['lastName']." for Category: ".$categoryName;
		// Here we just have a single category ID. We need to break out the global IDs that belong to this category 
		// (to be passed to getHoursByCategory), as well as the description fields for the global IDs.
		$result_category = mysql_query("SELECT global_id,description FROM global_activity 
		WHERE categories_id = '{$category}'");
		if (!$result_category) {
			$_SESSION['Msg'] = "Could not get global id's based on category. Close this window and contact the administrator.";
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
?>
		  <!DOCTYPE html 
	   PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
  
		<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
		<head>
		  <title><?php echo $_SESSION['TITLE']; ?></title>
		  <script type="text/javascript" language="javascript" src="js/setime.js"></script>
		  <link type="text/css" href="css/setime.css" rel="stylesheet" />
		
		</head>
		
		<body>
		<strong>No values to display. You may close this window.</strong>
		<?php  // echo $dataTotal.'<br />'; print_r($data); echo '<br />'; print_r($legend); ?>
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
			$_SESSION['Msg'] = "Unable to get users id for report. Close thiw window and contact the administrator.";
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
		$_SESSION['Msg'] = "No direct reports found for you. You may close this window. If you believe this is an error contact the administrator.";
  		exit;
	}
	// Ok, they have some direct reports. Let's get all the data for all of them.
	$output_data_csv = "userName,time_id,date,activity,hours\n";
	if ( $category == "total" || $category == "all" ) {
		foreach ( $directsOrg as $value ) {
			$result_userName = mysql_query("SELECT userName FROM users WHERE users_id = '{$value}'");
			if (!$result_userName) {
				$_SESSION['Msg'] = "Unable to get userName for CSV report. Close this window and contact the administrator.";
  				exit;
			}
			$row_userName = mysql_fetch_array($result_userName, MYSQL_ASSOC);
			mysql_free_result($result_userName);
			$userNameTmp = $row_userName['userName'];
			$result_time = mysql_query("SELECT time_id,date,global_id,hours FROM time WHERE users_id = '{$value}' 
			AND ( date BETWEEN '{$startDate}' AND '{$endDate}' ) ORDER BY date ASC");
			if (!$result_time) {
				$_SESSION['Msg'] = "Unable to get time for CSV report. Close this window and contact the administrator.";
  				exit;
			}
			while ( $row = mysql_fetch_array($result_time, MYSQL_ASSOC) ) {
				$activity = mysql_fetch_assoc(mysql_query("SELECT description FROM global_activity 
				WHERE global_id = '{$row['global_id']}'"));
				$output_data_csv .= $userNameTmp.",".$row['time_id'].",".$row['date'].",".$activity['description'].",".$row['hours']."\n";
			}
			mysql_free_result($result_time);	
		} // $directsOrg as $value
	} // $category == "total"
	  else { // $category == n
		foreach ( $directsOrg as $value ) {
			$result_userName = mysql_query("SELECT userName FROM users WHERE users_id = '{$value}'");
			if (!$result_userName) {
				$_SESSION['Msg'] = "Unable to get userName for CSV report. Close this window and contact the administrator.";
  				exit;
			}
			$row_userName = mysql_fetch_array($result_userName, MYSQL_ASSOC);
			mysql_free_result($result_userName);
			$userNameTmp = $row_userName['userName'];
			$result_global = mysql_query("SELECT global_id FROM global_activity WHERE categories_id = '{$category}'");
			if (!$result_global) {
				$_SESSION['Msg'] = "Could not get global activities. Close this window and contact the administrator.";
				exit;
			}
			while ( $row_global = mysql_fetch_array($result_global, MYSQL_ASSOC) ) {
				$result_time = mysql_query("SELECT time_id,date,global_id,hours FROM time 
				WHERE users_id = '{$value}' AND ( date BETWEEN '{$startDate}' AND '{$endDate}' ) 
				AND global_id = '{$row_global['global_id']}' ORDER BY date ASC");
				if (!$result_time) {
					$_SESSION['Msg'] = "Could not get time. Close this window and contact the administrator.";
  					exit;
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
	
$sizeX = "640"; $sizeY = "480";
if ( $category == "total" ) { // We need a bigger image size.
	$sizeX = "800"; $sizeY = "480";
}
$strXML = "<chart caption='".$title."' numberPrefix='' formatNumberScale='0'".$downloadStr.">";

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

<HTML>
	<HEAD>
		<title><?php echo $_SESSION['TITLE']; ?></title>
		<script type="text/javascript" src="Charts/FusionCharts.js"></script>
	</HEAD>
	
	<BODY>
<?php
if ( $chartType == 'pie' ) {
	echo renderChart("Charts/Pie3D.swf", "", $strXML, "chart", "100%", "100%", false, true);
} else { // $chartType == 'bar'
	echo renderChart("Charts/Column3D.swf", "", $strXML, "chart", "100%", "100%", false, true);
}
?>
	</BODY>
</HTML>
	
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
