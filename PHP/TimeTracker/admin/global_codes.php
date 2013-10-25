<?php
define('INCLUDE_CHECK', true);
error_reporting (E_ALL ^ E_NOTICE);

require '../connect.php';
require '../functions.php';
// Those two files can be included only if INCLUDE_CHECK is defined

session_name('seAdmin');
session_set_cookie_params(2*7*24*60*60);
session_start();

if ( isset($_GET['logoff']) || !( isset($_SESSION['users_id']) ) || !( isset($_SESSION['admin_id']) ) ){ // Their credentials are not valid, or they logged off.
	$_SESSION = array();
    session_destroy();

    header("Location: /admin/index.php");
    exit;
}

$_SESSION=from_array($_SESSION); // Massage the input with from_array().
foreach($_SESSION as $k1=>$v1) $$k1=$v1; // Get all the $_SESSION key values and assign them associated variable names.

if ( isset($_POST['submit']) && $_POST['submit'] == 'Change' ) {
	// They're changing a global code.
	$_POST=from_array($_POST);
	//print_r($_POST);
	
	$description = $_POST['newdescription'];
	$comments = $_POST['newcomments'];
	$category_id = $_POST['category_id'];
	if ( isset($_POST['isActive']) == 'on' ) {
		$isActive = 1;
	} else {
		$isActive = 0;
	}
	$id = $_POST['id'];
	
	if ( $description == '' || $id == '' ) {
		unset($_POST);
		$_SESSION['Msg'] = "No description found. Please try again.";
		header("Location: redirect_admin.php?Url=/admin/global_codes.php");
		exit;
	}
	
	$result_change = mysql_query("UPDATE global_activity SET description = '{$description}',
	comments = '{$comments}', categories_id = '{$category_id}', isActive = '{$isActive}' 
	WHERE global_id = '{$id}'");
	if ( !mysql_affected_rows() ) {
		unset($_POST);
		$_SESSION['Msg'] = "Could not update global code. Contact the developer.";
		header("Location: redirect_admin.php?Url=/admin/admin.php");
		exit;
	} else {
		unset($_POST);
		$_SESSION['Msg'] = "Global code updated.";
		header("Location: redirect_admin.php?Url=/admin/global_codes.php");
		exit;
	}
}

if ( isset($_POST['submit']) && $_POST['submit'] == 'Add Code' ) {
	// They're adding a global code.
	$_POST=from_array($_POST);
	// print_r($_POST);
	
	$description = $_POST['description'];
	$comments = $_POST['comments'];
	$category_id = $_POST['category_id'];
	if ( isset($_POST['isActive']) == '1' ) {
		$isActive = 1;
	} else {
		$isActive = 0;
	}

	if ( $description == '' ) {
		unset($_POST);
		$_SESSION['Msg'] = "No new description found. Please try again.";
		header("Location: redirect_admin.php?Url=/admin/global_codes.php");
		exit;
	}
	
	$result_change = mysql_query("INSERT INTO global_activity (categories_id,description,comments,isActive) 
	VALUES ('{$category_id}','{$description}','{$comments}','{$isActive}')");
	if ( !mysql_affected_rows() ) {
		unset($_POST);
		$_SESSION['Msg'] = "Could not insert new global code. Contact the developer.";
		header("Location: redirect_admin.php?Url=/admin/admin.php");
		exit;
	} else {
		unset($_POST);
		$_SESSION['Msg'] = "Global code added.";
		header("Location: redirect_admin.php?Url=/admin/global_codes.php");
		exit;
	}
}

if ( isset($_POST['submit']) && $_POST['submit'] == 'Delete' ) {
	// They're deleting a position. They've been warned. This will cascade through DB.profile.positions_id and set any
	// that contained this value to NULL.
	$_POST=from_array($_POST);
	// print_r($_POST);
	
	$id = $_POST['code'];
	$delete_id = $_POST['delete_id'];
	if ( $id == '' || $delete_id != 'true' ) {
		unset($_POST);
		$_SESSION['Msg'] = "No global code found. Please try again.";
		header("Location: redirect_admin.php?Url=/admin/global_codes.php");
		exit;
	}
	
	// Delete the code. Foreign key constraint in the time table will also delete all time entries associated with this code.
	$result_change = mysql_query("DELETE FROM global_activity WHERE global_id = '{$id}'");
	if ( !mysql_affected_rows() ) {
		unset($_POST);
		$_SESSION['Msg'] = "Could not delete global code. Contact the developer.";
		header("Location: redirect_admin.php?Url=/admin/admin.php");
		exit;
	} else {
		unset($_POST);
		$_SESSION['Msg'] = "Global code (and associated time entries) deleted.";
		header("Location: redirect_admin.php?Url=/admin/global_codes.php");
		exit;
	}
}

?>

<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <title><?php echo $_SESSION['TITLE']; ?> Administration</title>
	<script type="text/javascript" language="javascript" src="/js/setime.js"></script>
    <link type="text/css" href="/css/setime.css" rel="stylesheet" />
</head>

<body>
<div style="float:left;font-family:sans-serif;width=25%">
<img src="/logo.gif" alt="" /> <br /><br /><br />
Login: <?php echo $_SESSION['userName']; ?>
<br /><br />
<?php printAdminMenu(); // functions.php ?>

</div> <!-- end left div -->

<div id="main" style="float:right;text-align:center; background-color:white; border-left:solid #808080;width:75%">
<h3><?php echo $_SESSION['TITLE']; ?> Administrator</h3>



<?php if ( !isset($_POST['submit']) && $_POST['submit'] != 'Modify' && $_POST['submit'] != 'Add Code' ) { ?>

<div style="text-align:left;"> <!-- fieldset div -->
<fieldset style="width:50%;">
<legend><strong>Modify an existing global code</strong></legend>
<form name="globalMod" action="" method="post">
    <br />
    <label for="code"><strong>Global Code:</strong></label>
    <select name="code">
    	<option value="">Select a global code...</option>
        <?php
		// Get a list of the global codes.
		$result_global = mysql_query("SELECT global_id,description FROM global_activity ORDER BY categories_id ASC");
		if (!$result_global) {
			$_SESSION['Msg'] = "Could not get list of global codes for admin change. Contact the developer.";
			header("Location: redirect_admin.php?Url=/admin/admin.php");
			exit;
		}
		while ( $row_global = mysql_fetch_array($result_global, MYSQL_ASSOC) ) {
			echo '<option value="'.$row_global['global_id'].'">'.$row_global['description'].'</option>';
		}
		mysql_free_result($result_global);
		?>
    </select>
  
    <p>
    <input type="submit" name="submit" value="Modify" />
    <button type="submit" name="submit" value="Delete" onclick="return validateDeleteGlobal('globalMod')">Delete</button>
    <input type="hidden" name="delete_id" id="delete_id"  />
    </p>
</form>
</fieldset>
<br />

<fieldset style="width:50%;">
<legend><strong>Add a new global code</strong></legend>
<strong><span class="err">REMINDER: Any codes added must roll up to an existing category.<br />If you need to, 
<a href="/admin/categories.php">add a new category</a> first.</span></strong>
<br /><br />
<form name="globalAdd" action="" method="post">
	<table width="60%" border="0" cellpadding="2" cellspacing="5" bgcolor="#999999">
    	<tr>
		<td align="right" width="20%" bgcolor="#CCCCCC">
        <label for="description">Description <span class="err">(required)</span></label>
        </td>
        <td align="left" bgcolor="#CCCCCC">
        <input type="text" name="description" size="50" maxlength="255" 
        placeholder="A short desription such as 'Cross Activity'" />
        </td>
        </tr>
        <tr>
        <td align="right" width="20%" bgcolor="#CCCCCC">
        <label for="comments">Comments (optional)</label>
        </td>
        <td align="left" bgcolor="#CCCCCC">
        <input type="text" name="comments" size="50" maxlength="255" placeholder="A longer description"  />
        </td>
        </tr>
        <tr>
         <td align="right" width="20%" bgcolor="#CCCCCC">
        <label for="category_id">Category</label>
        </td>
        <td align="left" bgcolor="#CCCCCC">
        <select name="category_id">
        
        <?php
		
		// Get all the categories and build an option list.
		$result_categories = mysql_query( "SELECT categories_id,category FROM categories WHERE isActive = 1" );
		if (!$result_categories) {
			$_SESSION['Msg'] = "Could not get categories. Contact the developer.";
			header("Location: redirect_admin.php?Url=/admin/admin.php");
			exit;
		}
		while ( $row_categories = mysql_fetch_array($result_categories, MYSQL_ASSOC) ) {
		?>
			<option value="<?php echo $row_categories['categories_id'];?>"><?php echo $row_categories['category'];?></option>
		<?php
		}
		mysql_free_result($result_categories);
		?>
        
        </select>
        </td>
        </tr>
        <tr>
         <td align="right" width="20%" bgcolor="#CCCCCC">
        <label for="global_id">Mark Active?</label>
        </td>
        <td align="left" bgcolor="#CCCCCC">
        <input type="checkbox" name="isActive" value="1" checked="checked" />
        </td>
        </tr>
        <tr>
        <td align="center" colspan="2">
        <input type="submit" name="submit" value="Add Code" /> or 
        <input type="reset" name="reset" value="Reset" />
        </td>
        </tr>
    </table>
</form>
</fieldset>
</div> <!-- end fieldset div -->

</div>
</body>
</html>

<?php
} // !isset($_POST['submit']) && $_POST['submit'] != 'Modiy' && $_POST['submit'] != 'Add'

if ( isset($_POST['submit']) && $_POST['submit'] == 'Modify' ) {
	$_POST=from_array($_POST);
	/*
	print_r($_POST);
	echo '<br />';
	foreach ($_POST as $key => $value) {
		echo 'key: '.$key.' = value: '.$value.'<br />';
	}
	*/
	
	if ( $_POST['code'] == '' ) {
		unset($_POST);
		$_SESSION['Msg'] = "Invalid code selection, or no code selected. Please try again.";
		header("Location: redirect_admin.php?Url=/admin/global_codes.php");
		exit;
	}
	
	$code = $_POST['code'];
?>
<div style="text-align:left;"> <!-- fieldset div -->
<form name="globalMod" action="" method="post">
    <br />
    <table width="60%" border="0" cellpadding="2" cellspacing="5" bgcolor="#999999">
     		<tr>
            <th align="center" bgcolor="#333333"><font color="#CCCCCC">Current Description <span class="err">
            (required)</span></font></th>
            <th align="center" bgcolor="#333333"><font color="#CCCCCC">Current Comments</font></th>
            <th align="center" bgcolor="#333333"><font color="#CCCCCC">Current Category</font></th>
            <th align="center" bgcolor="#333333"><font color="#CCCCCC">Active?</font></th>
            </tr>
	<?php
	
	$result_global = mysql_query("SELECT * FROM global_activity WHERE global_id = '{$code}'");
	if (!$result_global) {
		$_SESSION['Msg'] = "Could not get global id for modify. Contact the developer.";
		header("Location: redirect_admin.php?Url=/admin/admin.php");
		exit;
	}
	$row_global = mysql_fetch_array($result_global, MYSQL_ASSOC);
	mysql_free_result($result_global);
	
	?>
            <tr>
            <td align="left" bgcolor="#CCCCCC">
			<input type="text" maxlength="255" name="newdescription" value="<?php echo $row_global['description']; ?>"  />
            </td>
            <td align="left" bgcolor="#CCCCCC">
            <input type="text" maxlength="255" name="newcomments" value="<?php echo $row_global['comments']; ?>"  />
            </td>
            <td align="left" bgcolor="#CCCCCC">
            <select name="category_id">
        
			<?php
            
            // Get all the categories and build an option list.
            $result_categories = mysql_query( "SELECT categories_id,category FROM categories WHERE isActive = 1" );
            if (!$result_categories) {
                $_SESSION['Msg'] = "Could not get categories. Contact the developer.";
                header("Location: redirect_admin.php?Url=/admin/admin.php");
                exit;
            }
			
            while ( $row_categories = mysql_fetch_array($result_categories, MYSQL_ASSOC) ) {
            	if ( $row_categories['categories_id'] == $row_global['categories_id'] ) {
					$selected = ' selected="selected"';
				} else {
					$selected = '';
				}
			?>
                <option value="<?php echo $row_categories['categories_id'];?>"<?php echo $selected; ?>>
				<?php echo $row_categories['category'];?></option>
            <?php
            }
            mysql_free_result($result_categories);
            ?>
            
            </select>
            </td>
            <td align="center" bgcolor="#CCCCCC">
            <input type="checkbox" name="isActive" 
			<?php
            	if ( $row_global['isActive'] == 1 ) {
					echo ' checked="checked" ';
				} 
			?> />
            </tr>
   </table>
   <input type="hidden" name="id" value="<?php echo $code; ?>"  />
   <br /><input type="submit" name="submit" value="Change" />
</form>
<br />
<button name="cancel" onclick="alertAndReturn('Entry Cancelled','/admin/global_codes.php')">Cancel</button>

</div> <!-- end fieldset div -->

</div>
</body>
</html>

<?php
} // isset($_POST['submit']) && $_POST['submit'] == 'Modify'
?>
