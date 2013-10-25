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
	// They're changing a category.
	$_POST=from_array($_POST);
	// print_r($_POST);
	
	$name = $_POST['newname'];
	$category_id = $_POST['id'];
	if ( isset($_POST['isActive']) == 'on' ) {
		$isActive = 1;
	} else {
		$isActive = 0;
	}
	
	if ( $name == '' || $category_id == '' ) {
		unset($_POST);
		$_SESSION['Msg'] = "No category found. Please try again.";
		header("Location: redirect_admin.php?Url=/admin/categories.php");
		exit;
	}
	
	$result_change = mysql_query("UPDATE categories SET category = '{$name}', isActive = '{$isActive}' 
	WHERE categories_id = '{$category_id}'");
	if ( !mysql_affected_rows() ) {
		unset($_POST);
		$_SESSION['Msg'] = "Could not update category. Contact the developer.";
		header("Location: redirect_admin.php?Url=/admin/admin.php");
		exit;
	} else {
		unset($_POST);
		$_SESSION['Msg'] = "Category updated.";
		header("Location: redirect_admin.php?Url=/admin/categories.php");
		exit;
	}
}

if ( isset($_POST['submit']) && $_POST['submit'] == 'Add Category' ) {
	// They're adding a category.
	$_POST=from_array($_POST);
	// print_r($_POST);
	
	$name = $_POST['name'];

	if ( isset($_POST['isActive']) == '1' ) {
		$isActive = 1;
	} else {
		$isActive = 0;
	}

	if ( $name == '' ) {
		unset($_POST);
		$_SESSION['Msg'] = "No new category name found. Please try again.";
		header("Location: redirect_admin.php?Url=/admin/categories.php");
		exit;
	}
	
	$result_change = mysql_query("INSERT INTO categories (category,isActive) VALUES ('{$name}','{$isActive}')");
	if ( !mysql_affected_rows() ) {
		unset($_POST);
		$_SESSION['Msg'] = "Could not insert new category. Contact the developer.";
		header("Location: redirect_admin.php?Url=/admin/admin.php");
		exit;
	} else {
		unset($_POST);
		$_SESSION['Msg'] = "Category added.";
		header("Location: redirect_admin.php?Url=/admin/categories.php");
		exit;
	}
}

if ( isset($_POST['submit']) && $_POST['submit'] == 'Delete' ) {
	// They're deleting a position. They've been warned. This will cascade through DB.profile.positions_id and set any
	// that contained this value to NULL.
	$_POST=from_array($_POST);
	print_r($_POST);
	
	$id = $_POST['category'];
	$delete_id = $_POST['delete_id'];
	if ( $id == '' || $delete_id != 'true' ) {
		unset($_POST);
		$_SESSION['Msg'] = "No category found. Please try again.";
		header("Location: redirect_admin.php?Url=/admin/categories.php");
		exit;
	}
	
	// Delete the category. Foreign key constraint in the global_activity table will also delete all global
	// activities associated with this category, and all time entries associated with those global codes.
	$result_change = mysql_query("DELETE FROM categories WHERE categories_id = '{$id}'");
	if ( !mysql_affected_rows() ) {
		unset($_POST);
		$_SESSION['Msg'] = "Could not delete the category. Contact the developer.";
		header("Location: redirect_admin.php?Url=/admin/admin.php");
		exit;
	} else {
		unset($_POST);
		$_SESSION['Msg'] = "Category, associated global codes (and associated time entries) deleted.";
		header("Location: redirect_admin.php?Url=/admin/categories.php");
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
<legend><strong>Modify an existing category</strong></legend>
<form name="categoryMod" action="" method="post">
    <br />
    <label for="category"><strong>Category:</strong></label>
    <select name="category">
    	<option value="">Select a category...</option>
        <?php
		// Get a list of the categories.
		$result_categories = mysql_query("SELECT categories_id,category FROM categories");
		if (!$result_categories) {
			$_SESSION['Msg'] = "Could not get list of categories for admin change. Contact the developer.";
			header("Location: redirect_admin.php?Url=/admin/admin.php");
			exit;
		}
		while ( $row_categories = mysql_fetch_array($result_categories, MYSQL_ASSOC) ) {
			echo '<option value="'.$row_categories['categories_id'].'">'.$row_categories['category'].'</option>';
		}
		mysql_free_result($result_categories);
		?>
    </select>
  
    <p>
    <input type="submit" name="submit" value="Modify" />
    <button type="submit" name="submit" value="Delete" onclick="return validateDeleteCat('categoryMod')">Delete</button>
    <input type="hidden" name="delete_id" id="delete_id"  />
    </p>
</form>
</fieldset>
<br />

<fieldset style="width:50%;">
<legend><strong>Add a new category</strong></legend>
<br />
<form name="categoryAdd" action="" method="post">
	<table width="60%" border="0" cellpadding="2" cellspacing="5" bgcolor="#999999">
    	<tr>
		<td align="right" width="20%" bgcolor="#CCCCCC">
        <label for="name">Category Name <span class="err">(required)</span></label>
        </td>
        <td align="left" bgcolor="#CCCCCC">
        <input type="text" name="name" size="50" maxlength="255" 
        placeholder="Should only be one or two words, such as 'Human Resources'" />
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
        <input type="submit" name="submit" value="Add Category" /> or 
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
	
	if ( $_POST['category'] == '' ) {
		unset($_POST);
		$_SESSION['Msg'] = "Invalid category selection, or no category selected. Please try again.";
		header("Location: redirect_admin.php?Url=/admin/categories.php");
		exit;
	}
	
	$category = $_POST['category'];
?>
<div style="text-align:left;"> <!-- fieldset div -->
<form name="categoryMod" action="" method="post">
    <br />
    <table width="60%" border="0" cellpadding="2" cellspacing="5" bgcolor="#999999">
     		<tr>
            <th align="center" bgcolor="#333333"><font color="#CCCCCC">Current Name <span class="err">
            (required)</span></font></th>
            <th align="center" bgcolor="#333333"><font color="#CCCCCC">Active?</font></th>
            </tr>
	<?php
	
	$result_category = mysql_query("SELECT * FROM categories WHERE categories_id = '{$category}'");
	if (!$result_category) {
		$_SESSION['Msg'] = "Could not get category id for modify. Contact the developer.";
		header("Location: redirect_admin.php?Url=/admin/admin.php");
		exit;
	}
	$row_category = mysql_fetch_array($result_category, MYSQL_ASSOC);
	mysql_free_result($result_category);
	
	?>
            <tr>
            <td align="center" bgcolor="#CCCCCC">
			<input type="text" maxlength="255" name="newname" value="<?php echo $row_category['category']; ?>"  />
            </td>
            <td align="center" bgcolor="#CCCCCC">
            <input type="checkbox" name="isActive" 
			<?php
            	if ( $row_category['isActive'] == 1 ) {
					echo ' checked="checked" ';
				} 
			?> />
            </tr>
   </table>
   <input type="hidden" name="id" value="<?php echo $category; ?>"  />
   <br /><input type="submit" name="submit" value="Change" />
</form>
<br />
<button name="cancel" onclick="alertAndReturn('Entry Cancelled.','/admin/categories.php')">Cancel</button>

</div> <!-- end fieldset div -->

</div>
</body>
</html>

<?php
} // isset($_POST['submit']) && $_POST['submit'] == 'Modify'
?>
