<?php
session_start();
$sessionid = session_id();
$date = date("d-m-Y");


// setup PDO for mysql
$hostname = '127.0.0.1';
$database = 's2704130_IWD_ICA';
$username = 's2704130';
$password = '@DriUni11111997';

$pdo = new PDO("mysql:host=$hostname; dbname=$database; charset=utf8mb4", $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);	// get error


//$sql = "select $col from $table";			1 to extract col from table
//$stmt = $pdo->prepare($sql);				1 for security
//$stmt->execute();					1 to execute
//$results = $stmt->fetchAll(PDO::FETCH_ASSOC);		to fetch results
//todisplay, chatgpt it

//https://bioinfmsc8.bio.ed.ac.uk/AY24_IWD2_03.html


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
