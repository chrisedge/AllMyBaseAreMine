<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title>SE Time Tracker</title>
	<meta name="keywords" content="" />
	<meta name="description" content="" />
  	<!--<script type="text/javascript" language="javascript" src="js/jquery-1.6.2.min.js"></script> -->
    <script type="text/javascript" language="javascript" src="js/jquery-1.7.1.min.js"></script>
	<script type="text/javascript" src="js/jquery.ajaxify.js"></script>
</head>

<body>


                
                
                <div id="ajaxWrapper"></div>
                
                <script type='text/javascript'> <!-- For Ajaxify -->

$('.ajaxify').delay(900).fadeIn(900).ajaxify({
	animateOut:{opacity:'0'},
	animateOutSpeed:600,
	animateIn:{opacity:'1'},
	animateInSpeed:600,
	target: '#ajaxWrapper',
	loading_img:'images/orange_loading.gif',
	method:'POST',
	loading_target:'#ajaxWrapper'
});

</script>
                
		  

		
            
            <div align="center">
            <a class="ajaxify" href="test.php" target="#ajaxWrapper" style="color:#F00000; font-size:16px; width:175px;">Enter Data</a>
            <p>&nbsp;</p>
            </div>
            
            
</body>
</html>