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
	$_SESSION = array();
    session_destroy();
    header("Location: index.php");
    exit;
}

unset($_SESSION['Msg']);
//$_SESSION=from_array($_SESSION); // Massage the input with from_array().
foreach($_SESSION as $k1=>$v1) $$k1=$v1; // Get all the $_SESSION key values and assign them associated variable names.

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
                    <span class="err"><?php echo $_SESSION['Msg']; ?></span>
                    </div>
                    
                    <div class="ui-widget-header" style="margin:0px 5px 0px 5px; padding:5px;">Add/Edit Local Activity Codes</div>
                    
                    <div class="ui-widget-content" style="margin:0px 5px 0px 5px; padding:5px;" id="localMain">
                    <span class="weak">Local activity codes provide a way for managers to track activities specific to their individual needs.
                    <br />
                    Each code <strong>must</strong> have a short description, a longer comments field, and must also roll up under a global category.<br />
                    Up to 64 local activity codes can be defined.
                    </span>
					
                    </div> <!-- end localMain --> 
					
                    <br />
<?php
if ( !isset($_POST['submit']) && !isset($_GET['id']) ) { 
?>

                    
                    
                    
<?php

	/* 
	 * First we'll check to see if they have any local codes already. If so, display them to be edited.
	 */
	 
	$result_locals = mysql_query( "SELECT local_id,description,comments,isActive 
								   FROM local_activity WHERE users_id = '{$users_id}'" );
	if (!$result_locals) {
		$_SESSION['Msg'] = "Could not get local activities. Contact the administrator.";
		header("Location: redirect.php?Url=/index.php");
		exit;
	}
	$num_rows = mysql_num_rows($result_locals);
	if ( $num_rows > 0 ) {
?>
					<h4 align="left">Edit existing local activity code:</h4>
    
                    <table width="100%" border="0" cellpadding="4" cellspacing="1" bgcolor="#FFFFFF">
                        <tr>
                        <th bgcolor="#000000" bordercolor="#000000" style="text-align:center;"></th>
                        <th bgcolor="#000000" bordercolor="#000000" style="text-align:center;">
                        <font color="#FFFFFF" style="font-weight:bold;">Description</font></th>
                        <th bgcolor="#000000" bordercolor="#000000" style="text-align:center;">
                        <font color="#FFFFFF" style="font-weight:bold;">Comments</font></th>
                        <th bgcolor="#000000" bordercolor="#000000" style="text-align:center;">
                        <font color="#FFFFFF" style="font-weight:bold;">Active?</font></th>
                        </tr>
<?php
	$i = 0;
	while ( $row_locals = mysql_fetch_array($result_locals, MYSQL_ASSOC) ) {
		if ( isEven($i) ) {
		$bgcolor= "#CCFFFF";
		} else {
			$bgcolor = "#CCCCFF";
		}
?>
                        <tr bgcolor='<?php echo $bgcolor; ?>'>
                        <td align="center">
                        <a href="local_codesNG.php?id=<?php echo $row_locals['local_id']; ?>">Edit</a>
                        </td>
                        <td>
                        <?php echo $row_locals['description']; ?>
                        </td>
                        <td>
                        <?php echo $row_locals['comments']; ?>
                        </td>
                        <td>
                        <input type="checkbox" name="isActive"
                        <?php
                            if ( $row_locals['isActive'] == 1 ) {
                                echo ' checked="checked" ';
                            } 
                        ?> disabled="disabled" />
                        </td>
                        </tr>
                        
<?php
		$i++;
	} // while $row_locals
	mysql_free_result($result_locals);
?>	
						</table>

<?php
	} // if ( $num_rows > 0 )
?>
                    	<br />
                        <h4 align="left">Add a local activity code:</h4>

                        <form name="addLocalCode" action="" method="post" onsubmit="return validateLocalCode('addLocalCode')">
                            <table width="100%" border="0" cellpadding="4" cellspacing="1" bgcolor="#FFFFFF">
                                <tr>
                                <td align="center" width="20%" bgcolor="#CCCCCC">
                                <label for="description">Description</label>
                                </td>
                                <td align="left" bgcolor="#CCCCCC">
                                <input type="text" name="description" size="50" maxlength="255" 
                                placeholder="A short desription such as 'Cross Activity'" />
                                </td>
                                </tr>
                                <tr>
                                <td align="center" width="20%" bgcolor="#CCCCCC">
                                <label for="comments">Comments</label>
                                </td>
                                <td align="left" bgcolor="#CCCCCC">
                                <input type="text" name="comments" size="50" maxlength="255" placeholder="A longer description"  />
                                </td>
                                </tr>
                                <tr>
                                 <td align="center" width="20%" bgcolor="#CCCCCC">
                                <label for="global_id">Global Code</label>
                                </td>
                                <td align="left" bgcolor="#CCCCCC">
                                <select name="global_id">
                                
<?php
		
		// Get all the global codes and build an option list.
		$result_globals = mysql_query( "SELECT global_id,description FROM global_activity WHERE isActive = 1 ORDER BY description" );
		if (!$result_globals) {
			$_SESSION['Msg'] = "Could not get global activities. Contact the administrator.";
			header("Location: redirect.php?Url=index.php");
			exit;
		}
		while ( $row_globals = mysql_fetch_array($result_globals, MYSQL_ASSOC) ) {
		?>
			<option value="<?php echo $row_globals['global_id'];?>"><?php echo $row_globals['description'];?></option>
		<?php
		}
		mysql_free_result($result_globals);
		?>
        
                                </select>
                                </td>
                                </tr>
                                <tr>
                                 <td align="center" width="20%" bgcolor="#CCCCCC">
                                <label for="global_id">Mark Active?</label>
                                </td>
                                <td align="left" bgcolor="#CCCCCC">
                                <input type="checkbox" name="isActive" value="1" checked="checked" />
                                </td>
                                </tr>
                                <tr>
                                <td align="center" colspan="2">
                                <input type="submit" name="submit" value="Enter Code" /> or 
                                <input type="reset" name="reset" value="Reset" />
                                </td>
                                </tr>
                            </table>
                        </form>
                               

<?php
} // !isset($_POST['submit']) && !isset($_GET['id'])
                    

if ( !isset($_POST['submit']) && isset($_GET['id']) ) {
	$_GET=from_array($_GET); // Massage the input with from_array().
	foreach($_GET as $k=>$v) $$k=$v; // Get all the $_GET key values and assign them associated variable names.
	// Get all the info for the local code their editing.
	$result_locals = mysql_query( "SELECT * FROM local_activity WHERE local_id = '{$id}'" );
	if (!$result_locals) {
		$_SESSION['Msg'] = "Could not get local activity to be edited. Contact the administrator.";
		header("Location: redirect.php?Url=index.php");
		exit;
	}
	$row_locals = mysql_fetch_array($result_locals, MYSQL_ASSOC);
	mysql_free_result($result_locals);
?>

<h4 align="left">Edit local activity code:</h4>

<form name="addLocalCode" action="" method="post" onsubmit="return validateLocalCode('addLocalCode')">
	<table width="10%" border="0" cellpadding="4" cellspacing="1" bgcolor="#FFFFFF">
    	<tr>
		<td align="center" width="20%" bgcolor="#CCCCCC">
        <label for="description">Description</label>
        </td>
        <td align="left" bgcolor="#CCCCCC">
        <input type="text" name="description" size="50" maxlength="255" 
         value="<?php echo $row_locals['description']; ?>" />
        </td>
        </tr>
        <tr>
        <td align="center" width="20%" bgcolor="#CCCCCC">
        <label for="comments">Comments</label>
        </td>
        <td align="left" bgcolor="#CCCCCC">
        <input type="text" name="comments" size="50" maxlength="255" 
         value="<?php echo $row_locals['comments']; ?>" />
        </td>
        </tr>
        <tr>
         <td align="center" width="20%" bgcolor="#CCCCCC">
        <label for="global_id">Global Code</label>
        </td>
        <td align="left" bgcolor="#CCCCCC">
        <select name="global_id">
        
        <?php
		
		// Get all the global codes and build an option list.
		$result_globals = mysql_query( "SELECT global_id,description FROM global_activity WHERE isActive = 1 ORDER BY description" );
		if (!$result_globals) {
			$_SESSION['Msg'] = "Could not get global activities for edit. Contact the administrator.";
			header("Location: redirect.php?Url=index.php");
			exit;
		}
		while ( $row_globals = mysql_fetch_array($result_globals, MYSQL_ASSOC) ) {
		?>
			<option value="<?php echo $row_globals['global_id']; ?>"
            
			<?php
				if ( $row_globals['global_id'] == $row_locals['global_id'] ) {
					echo ' selected="selected"';
				}
			?>
            
            > <?php echo $row_globals['description'];?></option>
		
		<?php
		}
		mysql_free_result($result_globals);
		?>
        
        </select>
        </td>
        </tr>
        <tr>
         <td align="center" width="20%" bgcolor="#CCCCCC">
        <label for="global_id">Mark Active?</label>
        </td>
        <td align="left" bgcolor="#CCCCCC">
        <input type="checkbox" name="isActive" value="1" 
        
		<?php
		if ( $row_locals['isActive'] == 1 ) {
			echo 'checked="checked" ';
		}
		?>
     
        />
        
        </td>
        </tr>
        <tr>
        <td align="center" colspan="2">
        <input type="submit" name="submit" value="Update Code" /> or <a href="javascript:history.go(-1)">Go Back</a>
        </td>
        </tr>
    </table>
<input type="hidden" name="local_id" value="<?php echo $id; ?>"  />
</form>

<form name="deleteCode" method="post" action="" onsubmit="return validateDelete('deleteCode')">
<input type="hidden" name="local_id" value="<?php echo $id; ?>"  />
<p align="left"><input type="submit" name="submit" value="Delete Code"  /></p>
</form>

<?php

} // !isset($_POST['submit']) && isset($_GET['id'])


if ( isset($_POST['submit']) && $_POST['submit'] == 'Enter Code' ) {
	$_POST=from_array($_POST); // Massage the input with from_array().
	foreach($_POST as $k=>$v) $$k=$v; // Get all the $_POST key values and assign them associated variable names.
	// print_r($_POST);
	
	// For the insert we need, global_id, users_id, description, comments, and isActive.
	
	if ( $_POST['isActive'] ) {
		$isActive = 1; 
	} else { $isActive = 0; }
	
	$sql_string = "INSERT INTO local_activity (global_id,users_id,description,comments,isActive)
	VALUES ('{$global_id}','{$users_id}','{$description}','{$comments}','{$isActive}')";
	// echo '<br />'.$sql_string;
	
	if ( !mysql_query($sql_string) ) {
		$_SESSION['Msg'] = "Could not insert values into local_activity table. Contact the administrator.";
		header("Location: redirect.php?Url=/index.php");
		exit;
	} else {
		// echo '</div></body></html>';
		unset($_POST);
		$_SESSION['Msg'] = "Local code successfully added.";
		header("Location: redirect.php?Url=local_codesNG.php");
	}

} // isset($_POST['submit']) && $_POST[submit] == 'Enter Code'

if ( isset($_POST['submit']) && $_POST['submit'] == 'Update Code' ) {
	$_POST=from_array($_POST); // Massage the input with from_array().
	foreach($_POST as $k=>$v) $$k=$v; // Get all the $_POST key values and assign them associated variable names.
	// print_r($_POST);
	
	// For the update we need, global_id, local_id, description, comments, and isActive.
	
	if ( $_POST['isActive'] ) {
		$isActive = 1; 
	} else { $isActive = 0; }
	
	$sql_string = "UPDATE local_activity SET 
	global_id='{$global_id}',description='{$description}',comments='{$comments}',isActive='{$isActive}'
	WHERE local_activity.local_id='{$local_id}'";
	// echo '<br />'.$sql_string;
	
	if ( !mysql_query($sql_string) ) {
		$_SESSION['Msg'] = "Could not update values in local_activity table. Contact the administrator.";
		header("Location: redirect.php?Url=index.php");
		exit;
	} else {
		// echo '</div></body></html>';
		unset($_POST,$_GET);
		$_SESSION['Msg'] = "Local code successfully updated.";
		header("Location: redirect.php?Url=local_codesNG.php");
	}

} // isset($_POST['submit']) && $_POST[submit] == 'Update Code'

if ( isset($_POST['submit']) && $_POST['submit'] == 'Delete Code' ) {
	$_POST=from_array($_POST); // Massage the input with from_array().
	foreach($_POST as $k=>$v) $$k=$v; // Get all the $_POST key values and assign them associated variable names.
	// print_r($_POST);
	
	if ( $_POST['isActive'] ) {
		$isActive = 1; 
	} else { $isActive = 0; }
	
	$sql_string = "DELETE FROM local_activity WHERE local_id='{$local_id}'";
	// echo '<br />'.$sql_string;
	// NOTE: When a local code is deleted, the time table is set with a foreign key constraint on the local_id
	// field to ON DELETE SET NULL. This will prevent extraneous values lingering about if a code is deleted.
	if ( !mysql_query($sql_string) ) {
		$_SESSION['Msg'] = "Could not delete values from local_activity table. Contact the administrator.";
		header("Location: redirect.php?Url=index.php");
		exit;
	} else {
		// echo '</div></body></html>';
		unset($_POST,$_GET);
		$_SESSION['Msg'] = "Local code successfully deleted.";
		header("Location: redirect.php?Url=local_codesNG.php");
	}


} // isset($_POST['submit']) && $_POST[submit] == 'Delete Code'

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