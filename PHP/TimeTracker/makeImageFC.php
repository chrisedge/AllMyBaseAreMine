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

$users_id = $_SESSION['users_id'];
print_r($_POST); exit;

// <img src="makeImage.php?total_values= echo $num_rows; "  />

// Includes for graph generation.
//include("pChart/pData.class");
//include("pChart/pChart.class");

if ( isset($_POST['submit']) && $_POST['submit'] == "Get Report" || $_POST['submit'] == "Download Image" ) {
	$_POST=from_array($_POST); // Massage the input with from_array().
	foreach($_POST as $k1=>$v1) $$k1=$v1; // Get all the $_POST key values and assign them associated variable names.
	// print_r($_POST);
	if ( isset($_POST['id']) && $_POST['id'] != '' ) { 
	// This has been called from view_reports, so this is for another user. Update users_id.
		$users_id = $id;
	}
	
	/*
	if ( $_POST['userSelect'] != '' ) { // They overrode $_POST[id] with the select.
		$users_id = $userSelect;
	}
	*/
	// Handle the "all" category first.
	if ( $category == "all" ) {
		// Understand now, that "all" isn't just necessarily category id's 1-5. As of this writing, yes, that is all
		// the categories there are. However, the admin can add and delete categories. So we need to handle this
		// dynamically, like everything else.
		$result_categories = mysql_query("SELECT * FROM categories");
		if (!$result_categories) {
			$_SESSION['Msg'] = "Could not get categories from categories table. Close this window and contact the administrator.";
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
		while ( $row_categories = mysql_fetch_array($result_categories, MYSQL_ASSOC) ) {
			$tmpTotal = getHoursByCategory($users_id,$row_categories['categories_id'],$startDate,$endDate);
			if ( $tmpTotal == 0.0 ) { continue; } // Throw out 0 values.
			$tmpLegend = $row_categories['category'];
			$data[$j] = $tmpTotal;
			$legend[$j] = $tmpLegend;
			$j++;
		}
		mysql_free_result($result_categories);
	} else if ( $category == "local" ) {
		// First we have to get all of the local_id that this manager owns. But we need the manager's users_id.
		$managers_id = $_SESSION['is_manager'];
		$result_manager = mysql_query("SELECT users_id FROM managers WHERE managers_id = '{$managers_id}'");
		if (!$result_manager) {
			$_SESSION['Msg'] = "Could not determine manager for local id's for report. Close this window and contact the administrator.";
  			exit;
		}
		$row_manager = mysql_fetch_array($result_manager, MYSQL_ASSOC);
		mysql_free_result($result_manager);
		$manager_usersid = $row_manager['users_id'];
		$result_locals = mysql_query("SELECT local_id,description FROM local_activity WHERE users_id ='{$manager_usersid}'");
		if (!$result_locals) {
			$_SESSION['Msg'] = "Could not get local id's for report. Close this window and contact the administrator.";
  			exit;
		}
		$j = 0;
		$data = array();
		$legend = array();
		while ( $row_locals = mysql_fetch_array($result_locals, MYSQL_ASSOC) ) {
			$tmpTotal = getHoursByLocal($users_id,$row_locals['local_id'],$startDate,$endDate);
			if ( $tmpTotal == 0.0 ) { continue; }
			$tmpLegend = $row_locals['description'];
			$data[$j] = $tmpTotal;
			$legend[$j] = $tmpLegend;
			$j++;
		}
		mysql_free_result($result_locals);
	}  else { // $category == "n"
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
		while ( $row_category = mysql_fetch_array($result_category, MYSQL_ASSOC) ) {
			// TODO: Need logic to throw out 0 total AND for 0 total time (if there's no time returned by getHoursBy[blank]
			// pChart throws an error. Maybe use the same error checking that check for any time entries in edit_time.php
			$tmpTotal = getHoursByGlobal($users_id,$row_category['global_id'],$startDate,$endDate);
			if ( $tmpTotal == 0.0 ) { continue; } // Throw out 0 values.
			$tmpLegend = $row_category['description'];
			$data[$j] = $tmpTotal;
			$legend[$j] = $tmpLegend;
			$j++;
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
		  <script type="text/javascript" language="javascript" src="/js/setime.js"></script>
		  <link type="text/css" href="/css/setime.css" rel="stylesheet" />
		
		</head>
		
		<body>
		<strong>No values to display. You may close this window.</strong>
		<?php  // echo $dataTotal.'<br />'; print_r($data); echo '<br />'; print_r($legend); ?>
		</body>
		</html>
<?php
		exit;
	} // array_sum($data) == 0.0 	
	
} // isset($_POST['submit']) && $_POST['submit'] == "Get Report" || $_POST['submit'] == "Download Image"
else if ( $_POST['submit'] == "Download CSV" ) { 
	$_POST=from_array($_POST);
	foreach($_POST as $k1=>$v1) $$k1=$v1; // Get all the $_POST key values and assign them associated variable names.
	if ( isset($_POST['id']) ) { // This has been called from view_reports, so this is for another user. Update users_id.
		$users_id = $id;
	}
	// Handle the "all" category first.
	if ( $category == "all" ) {
		$output_data_csv = "time_id,date,activity,hours\n";
		// CSV generation for "all" categories. Get all of the time for this user for the specified dates.
		$result_time = mysql_query("SELECT time_id,date,global_id,hours FROM time 
		WHERE users_id = '{$users_id}' AND ( date BETWEEN '{$startDate}' AND '{$endDate}' ) ORDER BY date ASC");
		if ( !$result_time ) {
			$_SESSION['Msg'] = "Could not get time for user. Close this window and contact the administrator.";
  			exit;
		}
		while ( $row = mysql_fetch_array($result_time, MYSQL_ASSOC) ) {
			$activity = mysql_fetch_assoc(mysql_query("SELECT description FROM global_activity 
			WHERE global_id = '{$row['global_id']}'"));
			$output_data_csv .= $row['time_id'].",".$row['date'].",".$activity['description'].",".$row['hours']."\n";
		}
		mysql_free_result($result_time);
		
	} // $category == "all"
	  else if ( $category == "local" ) {
		$output_data_csv = "time_id,date,activity,hours\n";
		// First we have to get all of the local_id that this manager owns. But we need the manager's users_id.
		$managers_id = $_SESSION['is_manager'];
		$result_manager = mysql_query("SELECT users_id FROM managers WHERE managers_id = '{$managers_id}'");
		if (!$result_manager) {
			$_SESSION['Msg'] = "Could not determine manager for local id's for report. Close this window and contact the administrator.";
  			exit;
		}
		$row_manager = mysql_fetch_array($result_manager, MYSQL_ASSOC);
		mysql_free_result($result_manager);
		$manager_usersid = $row_manager['users_id'];
		$result_locals = mysql_query("SELECT local_id,description FROM local_activity WHERE users_id ='{$manager_usersid}'");
		if (!$result_locals) {
			$_SESSION['Msg'] = "Could not get local id's for report. Close this window and contact the administrator.";
  			exit;
		}
		while ( $row_locals = mysql_fetch_array($result_locals, MYSQL_ASSOC) ) {
			$result_time = mysql_query("SELECT time_id,date,local_id,hours FROM time 
			WHERE users_id ='{$users_id}' AND ( date BETWEEN '{$startDate}' AND '{$endDate}' ) 
			AND local_id = '{$row_locals['local_id']}' ORDER BY date ASC");
			if (!$result_time) {
				$_SESSION['Msg'] = "Could not get time for local activity. Close this window and contact the administrator.";
  				exit;
			}
			while ( $row = mysql_fetch_array($result_time, MYSQL_ASSOC) ) {
				$output_data_csv .= $row['time_id'].",".$row['date'].",".$row_locals['description'].",".$row['hours']."\n";
			}
			mysql_free_result($result_time);
		} // $row_locals = mysql_fetch_array($result_locals, MYSQL_ASSOC)
		mysql_free_result($result_locals);
	} // $category == "local"
	  else { // $category == n
		$output_data_csv = "time_id,date,activity,hours\n";
		// Get all time for a specific category.
		$result_global = mysql_query("SELECT global_id FROM global_activity WHERE categories_id = '{$category}'");
		if (!$result_global) {
			$_SESSION['Msg'] = "Could not get global activities for category. Close this window and contact the administrator.";
  			exit;
		}
		while ( $row_global = mysql_fetch_array($result_global, MYSQL_ASSOC) ) {
			$result_time = mysql_query("SELECT time_id,date,global_id,hours FROM time 
			WHERE users_id = '{$users_id}' AND ( date BETWEEN '{$startDate}' AND '{$endDate}' ) 
			AND global_id = '{$row_global['global_id']}' ORDER BY date ASC");
			if (!$result_time) {
				$_SESSION['Msg'] = "Could not get time for global activity. Close this window and contact the administrator.";
  				exit;
			}
			while ( $row = mysql_fetch_array($result_time, MYSQL_ASSOC) ) {
				$activity = mysql_fetch_assoc(mysql_query("SELECT description FROM global_activity 
				WHERE global_id = '{$row['global_id']}'"));
				$output_data_csv .= $row['time_id'].",".$row['date'].",".$activity['description'].",".$row['hours']."\n";
			}
			mysql_free_result($result_time);
		} // $row_global = mysql_fetch_array($result_global, MYSQL_ASSOC)
		mysql_free_result($result_global);
	} // $category == n
	// print $output_data_csv;
	
	// send headers and csv file
	header("Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0");
	header("Pragma: no-cache");
	header("Content-Description: File Transfer");
	//header("Content-type: application/vnd.ms-excel");
	header("Content-type: application/csv");
	header("Content-Disposition: attachment; filename=se_time_" . $_SESSION['userName'] . ".csv");
	// header("Content-Disposition: attachment; filename=se_time.csv");
	print $output_data_csv;
	exit();
} // $_POST['submit'] == "Download CSV"

$downloadStr = '';
if ( $_POST['submit'] == "Download Image" ) {
	// header('Content-Disposition: Attachment;filename=image.png');
	$downloadStr = " exportEnabled='1' exportHandler='Charts/FCExporter.php' exportAtClient='0' exportAction='download'";
}
// Get the sizeof the $data array (which will be the same size as $legend). We have to do this to make sure we parse the
// values out of the array that are actually in there. pChart will vomit if you try to pass an array with only 1 element
// directly to AddPoint().
// So, we use the while loop below to extract the values from the array and pass them directly.
$i = count($data);

// This is set by user_reports.php if it's called for another user. We unset when we're done in case they then
// run a report for themselves afterwards.
if ( isset($_SESSION['reportFirstName']) && $_SESSION['reportFirstName'] != '' ) {
	$firstNameTitle = $_SESSION['reportFirstName'];
	$lastNameTitle = $_SESSION['reportLastName'];
} else {
	$firstNameTitle = $_SESSION['firstName'];
	$lastNameTitle = $_SESSION['lastName'];
}
unset($_SESSION['reportFirstName']);
unset($_SESSION['reportLastname']);
			
$strXML = "<chart caption='User report for ".$firstNameTitle." ".$lastNameTitle."' numberPrefix='' formatNumberScale='0'".$downloadStr.">";

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


/* OLD using pChart

if ( $chartType == 'pie' ) {

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
	// $Test->createColorGradientPalette(228,245,116,248,255,136,$size/2);
	
	// Draw the pie chart
	$Test->AntialiasQuality = 0;
	// $Test->setShadowProperties(2,2,200,200,200);
	// $Test->drawFlatPieGraphWithShadow($DataSet->GetData(),$DataSet->GetDataDescription(),150,120,85,PIE_PERCENTAGE,8);
	$Test->drawPieGraph($DataSet->GetData(),$DataSet->GetDataDescription(),180,130,120,PIE_PERCENTAGE,TRUE,50,20,5);
	$Test->clearShadow();
	$Test->drawPieLegend(10,245,$DataSet->GetData(),$DataSet->GetDataDescription(),255,255,255);
	
	// $Test->Render("C:\\inetpub\\wwwroot\\pChart\\tmp\\example13.png");
	$Test->Stroke();
} else { // $chartType == 'bar'
	// They're looking for a bar chart. No, not that kind of bar.
	
	/*
     Based on Example12 : A true bar graph
 	*/
  
   // Dataset definition 
/*   $DataSet = new pData;
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
   
   // $DataSet->AddAllSeries();
/*
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
  
  
   // Finish the graph
/*
   $Test->setFontProperties("pChart/Fonts/tahoma.ttf",12);
   $Test->drawLegend(100,250,$DataSet->GetDataDescription(),255,255,255);
   $Test->setFontProperties("pChart/Fonts/tahoma.ttf",14);
   // $Test->drawTitle(50,22,$title,50,50,50,585);
   //$Test->Render("example12.png");
   $Test->Stroke();
  //$gTotal = array_sum($data);
  //echo 'grand total: '.$gTotal.'<br />';
  //print_r($legend);
  //print_r($data);
} // $chartType == 'pie'
*/
?>
