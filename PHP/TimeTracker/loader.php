<?php

switch($_GET['page'])  {
	case '#page1' : $page = '<img src="images/burst.png" alt="" width="540" height="590" />'; break;

	case '#page2' : $page = '<b>Portfolio</b><br/>You can put whatever you want. For advance programmers, you can create your own simple backend that store web pages, and then display it by using PHP to retrieve it from database. Also, you can integrate a Rich Text Editor for your backend too! I have made a post regarding <a href="http://www.queness.com/post/212/10-jquery-and-non-jquery-javascript-rich-text-editors">Rich Text Editor</a>.<br/><br/> Starting from a simple guideline, you can build your own Content Management System.'; break;

	case '#page3' : $page = '<script type="text/javascript">
	$(function() {
		$( "#datepicker" ).datepicker();
	});
	</script>
	<p>Date: <input type="text" id="datepicker"></p>
	<div id="datepicker"></div>'; break;

	case '#page4' : $page = '<b>Contact</b><br/>This form is just a demostration of what content you can put.<br/><br/><form>Name:<br/><input type="text"/><br/>Email:<br/><input type="text"/><br/>Message:<br/><textarea></textarea><br/><input type="button" value="Send"></form>'; break;

}

echo $page;
?>