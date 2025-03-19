<?php
session_start();

if (!isset($_SESSION['step'])){$_SESSION['step'] = 0;}
if (isset($_POST['button1'])){$_SESSION['step'] = 1;}
if (isset($_POST['button2'])){$_SESSION['step'] = 2;}	//show button 3 & message
if (isset($_POST['button3'])){$_SESSION['step'] = 0;}	//reset

?>

<!DOCTYPE html>
<html lang="en">

<head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>PROTE-Con</title>
        <link rel="stylesheet" href="https://bioinfmsc8.bio.ed.ac.uk/~s2704130/S2_IWD/ICA_Website_250318/website_dev/ica_style.css">
	<script type="text/javascript" src="https://bioinfmsc8.bio.ed.ac.uk/~s2704130/S2_IWD/ICA_Website_250318/website_dev/ica_script.js"></script>

	<style>
		.input_group{
			width: 100%;
			display: grid;
			grid-template-columns: 1fr 1fr 1fr;
			gap: 10px;
			align-items: start;
			margin-bottom: 10px;
			font: 18px Arial, sans-serif;
		}
		.input_group label{
			text-align: left;
		}
		.input_group input{
			padding: 8px;
		}
		.input_group span{
			font-style: italic;
		}
		button {
			margin-top: 5px;
			padding: 10px 15px;
			font-size: 15px;
			font-weight: bold;
			cursor: pointer;
		}
	</style>


</head>

<body>
<!-- input1 begins -->
	<form method="POST" action="ica_tools.php">
		<br>
		<div class="input_group">
			<label for="input1"><b>Protein Name:</b></label>
			<input type="text" id="input1" placeholder="Enter protein name">
			<span>e.g. Glucose-6-Phosphatase</span>
		</div>
        	<div class="input_group">
                	<label for="input2"><b>Taxonomy Group:</b></label>
                	<input type="text" id="input2" placeholder="Enter taxonomic group">
                	<span>e.g. Aves</span>
        	</div>
		<br>
		<button type="submit" name="button1">Submit</button>
	</form>
<!-- input1 ends -->

<!-- output1 starts -->
	<?php if ($_SESSION['step'] >= 1): ?>
		<p>Button 1 was clicked.</p>
		<br>
		<hr>	<!-- horizontal line-->

		<div class="input_group"><p>
                	<label for="input3"><b>No. of sequences:</b></label>
                	<input type="number" id="qty" placeholder="100">
                	<span>Default: 100</span>
		</p></div>

		<form method="POST" action="">
			<button type="submit" name="button2">Button 2</button>
		</form>
	<?php endif; ?>


        <?php if ($_SESSION['step'] >= 2): ?>
                <p>Button 2 was clicked.</p>
                <form method="POST" action="">
                        <button type="submit" name="button3">Button 3</button>
                </form>
        <?php endif; ?>

</body>



<!-- user can save and come back to the page, cookies, ensure that can't be hacked (JS) -->

</html>
