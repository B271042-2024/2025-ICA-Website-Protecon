<?php
session_start();
$sessionid = session_id();
$date = date("d-m-Y");

// setup PDO for mysql
$db_host = getenv('DB_HOST');
$db_name = getenv('DB_NAME');
$db_user = getenv('DB_USER');
$db_password = getenv('DB_PASSWORD');

try{
	$pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_password);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);	// get error
} catch (PDOException $e){
	echo "Connection failed: " . $e->getMessage();
}


?>


<!DOCTYPE html>
<html lang="en">

<!-- html: https://www.w3schools.com/tags/default.asp -->
<!-- css: https://www.w3schools.com/cssref/index.php -->
<!-- js: https://www.w3schools.com/jsref/default.asp -->
<!-- php: https://www.w3schools.com/php/php_ref_overview.asp -->
<!-- hard refresh: Ctrl+Shift+R-->


<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>PROTE-Con</title>
	<link rel="stylesheet" href="https://bioinfmsc8.bio.ed.ac.uk/~s2704130/S2_IWD/ICA_Website_250318/website_dev/ica_style.css">
</head>
<body>
	<script type="text/javascript" src="https://bioinfmsc8.bio.ed.ac.uk/~s2704130/S2_IWD/ICA_Website_250318/website_dev/ica_script.js"></script>
	<iframe src="https://bioinfmsc8.bio.ed.ac.uk/~s2704130/S2_IWD/ICA_Website_250318/website_dev/ica_header.html" width="100%" height="100px" style="border:none;"></iframe>
	<iframe id="contentFrame" src="https://bioinfmsc8.bio.ed.ac.uk/~s2704130/S2_IWD/ICA_Website_250318/website_dev/ica_tools.php" width="100%" height="800px" style="border:none;"></iframe>

	<footer class="footer">
    		<p>2025 ProteCon by ProteCon Team.</p>
	</footer>


</body>


</html>





















/*
<!-- cookies (php)
$_SESSION = array();
if( session_id() != "" || isset($_COOKIE[session_name()]))
  { 
    setcookie(session_name(), '', time() - 2592000, '/');
  session_destroy(); 
}

OR

<?php
session_start();
$current_session_id = session_id();
echo "Your session ID is $current_session_id ..." ;
session_destroy() ;
$nowsession = session_id() ;
echo "
Your session ID was $current_session_id and is now $nowsession ..." ;
?>

 -->
*/
