<?php
define('INCLUDE_CHECK', true);
error_reporting (E_ALL ^ E_NOTICE);

require 'connect.php';
require 'functions.php';
// Those two files can be included only if INCLUDE_CHECK is defined

session_name('seLogin');
session_set_cookie_params(2*7*24*60*60);
session_start();

if ( isset($_GET['logoff']) || !( isset($_SESSION['users_id']) ) ){ // Their credentials are not valid, or they logged off.
    // echo 'SESSION users_id: ' . $_SESSION['users_id']; 
	$_SESSION = array();
    session_destroy();

    header("Location: index.php");
    exit;
}

$_SESSION=from_array($_SESSION); // Massage the input with from_array(), again.
foreach($_SESSION as $k1=>$v1) $$k1=$v1; // Get all the $_SESSION key values and assign them associated variable names.

// Check for the admin user.
if ( $userName == 'admin' ) {
	$_SESSION['managersName'] = 'None';
} else {
	// managers_id comes to us in a session variable from the login page.
	$row_managersName = mysql_fetch_assoc(mysql_query( "SELECT name FROM managers WHERE managers_id = {$managers_id}" ));
	if ($row_managersName['name']) {
  		$_SESSION['managersName'] = $row_managersName['name'];
	} else {
  		$_SESSION['Msg'] = "Could not get name from managers table. Contact the administrator.";
  		header("Location: redirect.php?Url=index.php");
  		exit;
	}
}


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

function showLoadingModal() {
	// $("#modalLoading").dialog("open");
	$('#updates').hide();
	$('.loading').show();
	return true;
}

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
              
              
                
                <div align="center" class="loading">
                <img src="images/orange_loading.gif" width="62" height="62" alt="Loading...." />
                </div>
                
                <div id="updates">
                
                  <div align="right" style="padding-right:5px;">
                  Organization Level: <span class="err" id="orgLevel"><?php echo ' '.$_SESSION['userName'];?></span>
                  </div>
                	
                    <div class="ui-widget-header" style="margin:0px 5px 0px 5px; padding:5px;">
                    Organizational Reports (all direct reports under you)</div>
                    <div class="ui-widget-content" style="margin:0px 5px 0px 5px; padding:5px;">
                    	<form name="reportSubOrg" action="makeImageOrgFCNG.php" method="post" onsubmit="return showLoadingModal();">
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
                              header("Location: redirect.php?Url=index.php?logoff");
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
                              <option value="local">Local Codes</option>
                          </select>
                          <br />
                          <label for="chartType"><strong>Select the desired chart type:</strong></label>
                          <input type="radio" name="chartType" value="pie" checked="checked" /> <strong>Pie</strong>&nbsp;&nbsp;
                          <input type="radio" name="chartType" value="bar" /> <strong>Bar</strong>
                          <br />
                          <br />
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