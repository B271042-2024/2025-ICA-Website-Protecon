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
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);  // get error

	if (!isset($_SESSION['sequence_data'])){
		$_SESSION['sequence_data'] = [];
	}

	$sequence_data = [];
        function displayFastaTable($fasta_sequence){
		echo '<div class="output_fasta">';
               	echo "<table>";
               	echo "<tr><th>Tick</th><th>Accession No.</th><th>Sequence Name</th><th>Length</th></tr>";

		$sequences = explode(">", $fasta_sequence);

		foreach ($sequences as $sequence){
        	       if (trim($sequence) !== ""){
                 	      	$seq = explode("\n", trim($sequence), 2);
                              	$seq_header = htmlspecialchars($seq[0]);
				$seq_acc = explode(" ", $seq_header)[0];
				$seq_name = implode(" ", array_slice(explode(" ", $seq_header), 1));
                              	$seq_sequence = isset($seq[1]) ? preg_replace('/\s+/', '', trim($seq[1])) : '';
                              	$seq_length = strlen($seq_sequence);
				$_SESSION['$sequence_data'][] = ['acc' => $seq_acc, 'name' => $seq_name, 'length' => $seq_length, 'sequence' => ">$seq_header\n$seq_sequence"];

				echo "<tr><td><input type='checkbox' class='delete-checkbox' data-sequence-id='$seq_acc' name='del[]' value='$seq_acc'></td><td>$seq_acc</td><td>$seq_name</td><td>$seq_length</td></tr>";

                        }
		}
                echo "</table>";
		echo "</div>";
		echo "<br>";
		echo "<div style='text-align: left;'>";
			echo "<button id='delete-button' onclick='deleteSelectedRows()'>Remove</button>";
		echo "</div>";

		echo "<form method='POST' action=''>";
		echo "<div style='text-align: right;'>";
			echo "<button type='submit' name='button4'>Proceed</button>";
		echo "</div>";
		echo "</form>";

	} //end fxn

        if (isset($_POST['button4'])) {
                echo "<hr>";
                echo "Button clicked, data is being transferred to SQL...";

		echo "<pre>";
		echo print_r($_SESSION['$sequence_data']);
		echo "</pre>";

                // Loop through the sequences and transfer to SQL
                foreach ($_SESSION['sequence_data'] as $seq) {
                        transfertoSQL($pdo, $sessionid, $seq['acc'], $seq['name'], $seq['length'], $seq['sequence']);
                }
                echo "Data is transferred successfully.";
        }


	function transfertoSQL($pdo, $sessionid, $seq_acc, $seq_name, $seq_length, $fasta_sequence){
		// transfer to database
		//global $sessionid, $pdo;
		echo "start checking";
		$webtosql = "insert into temporary_data (session_id, accession_no, sequence_name, length, fasta_sequence) values (:session_id, :accession_no, :sequence_name, :length, :fasta_sequence)";
		$stmt = $pdo->prepare($webtosql);
    		$stmt->bindParam(':session_id', $sessionid, PDO::PARAM_STR);
    		$stmt->bindParam(':accession_no', $seq_acc, PDO::PARAM_STR);
    		$stmt->bindParam(':sequence_name', $seq_name, PDO::PARAM_STR);
		$stmt->bindParam(':length', $seq_length, PDO::PARAM_INT);
		$stmt->bindParam(':fasta_sequence', $fasta_sequence, PDO::PARAM_STR);
		$stmt-> execute();
		echo "check mysql now";
	}

?>

<?php

echo <<<_HEAD
<html lang="en">
<head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>PROTE-Con</title>
        <link rel="stylesheet" href="https://bioinfmsc8.bio.ed.ac.uk/~s2704130/S2_IWD/ICA_Website_250318/website_dev/ica_style.css">

	<style>

		/* General styling */
                .input_group, legend, label, span {
                        font: 16px Arial, sans-serif;
                }

		input[type="text"], input[type="number"] {
    			padding: 8px;
    			margin: 5px 10px 5px 0; /* Space on the right side */
    			width: 100px; /* Set fixed width for inputs */
    			box-sizing: border-box;
		}

		input[type="checkbox"] {
    			margin-right: 5px;
		}

		label {
    			font-weight: bold;
    			width: auto;
		}

		span {
			width: auto;
		}

		/* Styling the div container */
		.input_group, legend {
			font: 18px Arial, sans-serif;
		}

		.methods {
    			display: flex;
    			flex-direction: column;
    			gap: 20px;
		}

		/* Aligning Method 1 and Method 2 with spacing */
		fieldset {
    			border: 1px solid #D3D3D3;
    			padding: 15px;
    			border-radius: 5px;
    			display: block;
    			width: 95%;
		}

		legend {
    			font-weight: bold;
    			padding: 0 10px;
		}

		.input_checkbox {
    			margin-top: 10px;
		}

		.only_checkbox {
    			display: flex;
    			flex-direction: column;
    			gap: 8px;
		}

		.only_checkbox input[type="checkbox"] {
			transform: scale(1.5);
			margin-right: 15px;
		}

		.input_row {
    			display: flex;
    			align-items: center;
    			margin-bottom: 10px;
		}

		.input_row label {
    			width: 200px;
		}

		.input_row input {
    			width: 500px;
		}

		.input_row span {
			margin-left: 15px;
		}

		button {
			padding: 5px 20px;
			background-color: black;
			font-weight: bold;
			color: white;
			border-radius: 5px;
			border: none;
			cursor: pointer;
		}

		button:hover {
			background-color: gray;
		}

		.output_fasta {
			border: 1px solid white;
			padding: 15px;
			border-radius: 5px;
			display: block;
			width: 95%;
			height: 300px;
			text-align: left;
			overflow: auto;
			font: 16px Arial, sans-serif;
		}

		table {
			width: 100%;
			border-collapse: collapse;
		}

		table th, table td {
			padding: 10px;
			border: 1px solid #D3D3D3;
		}

		table th {
			background-color: black;
			color: white;
		}


	</style>
</head>
<body>
_HEAD;

echo <<<_TOOL1_FASTA
<!-- 1 EXTRACT INFORMATION FROM NCBI PROTEIN BEGINS -->
<!-- input1 begins -->

	<form method="POST" action="ica_tools.php">
		<br>
		<h1>Get your Sequences</h1>
		<div class="methods">
			<fieldset>
				<legend>Method 1: Use your saved Job ID</legend>
                        	<div class="input_row">
					<label for="input0"><b>Job ID:</b></label>
                        		<input type="text" id="input0" name="input0" placeholder="Enter job ID">
                        		<span>e.g. 1234567890abcdef</span>
				</div>
                                <br>
                                        <button type="submit" name="button0">Submit</button>
			</fieldset>

			<p>OR</p>

			<fieldset>
				<legend>Method 2: Retrieve sequences from NCBI Protein</legend>
				<div class="input_row">
					<label for="input1"><b>Protein Name:</b></label>
					<input type="text" id="input1" name="input1" placeholder="Enter protein name">
					<span>e.g. Glucose-6-Phosphatase</span>
				</div>
				<div class="input_row">
                			<label for="input2"><b>Taxonomy Group:</b></label>
                			<input type="text" id="input2" name="input2" placeholder="Enter taxonomic group">
                			<span>e.g. Aves</span>
				</div>
				<br>
				<div class="input_checkbox">
					<p><b>Tick below to exclude:</b></p>
					<div class="only_checkbox">
    						<label><input type="checkbox" name="options[]" value="isoform">isoform</label>
    						<label><input type="checkbox" name="options[]" value="partial">partial</label>
					</div>
				</div>
				<br>
				<div class="input_row">
					<label for="input3"><b>No. of sequences:</b></label>
					<input type="number" id="input3" name="input3" placeholder="Default: 20, Max: 200" max="200">
					<span>Leave blank to use default</span>
				</div>
				<br>
					<button type="submit" name="button1">Submit</button>
			</fieldset>
		</div>
		<br>
	</form>
<!-- input1 ends -->
_TOOL1_FASTA;



// <!-- output1 starts -->

	//Tool 0: Extract Job ID from sql



	// Tool 1: Extract details from NCBI protein
	//if button
	if (isset($_POST['button1'])){
		// extract input1-3
               	$protein_name = trim($_POST['input1']);
               	$taxon_group = trim($_POST['input2']);
		$num_sequence = isset($_POST['input3']) && !empty($_POST['input3']) ? (int)$_POST['input3'] : 20;	// default: 20
               	$ncbi_token = "abb4f7cff84a4af777891b6f35184e703808";
		echo "<p><b>Protein Name:</b> $protein_name</p>";
		echo "<p><b>Taxonomy Group:</b> $taxon_group</p>";

		$ncbi_search = "https://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=protein&term=" . urlencode($protein_name) . "+AND+" . urlencode($taxon_group);
		//if >1 tick
		if (!empty($_POST['options'])){
			$encode_options = array_map('urlencode', $_POST['options']);
			$filter = implode("+NOT+", $encode_options);
			$ncbi_search .= "+NOT+" . $filter;
		}
		$ncbi_search .= "&retmax=$num_sequence&retmode=json&api_key=$ncbi_token";


		// NCBI SEARCH
		$o_search = file_get_contents($ncbi_search);
//		echo "$ncbi_search";
               	if($o_search === false){
                       	echo "<p>Error. Unable to connect to NCBI API.</p>";
                       	return;
               	}

               	$o_data = json_decode($o_search, true);
               	$o_idlist = $o_data['esearchresult']['idlist'] ?? [];   // if null, assign an empty array

               	if(empty($o_idlist)){
                       	echo "<p>ERROR. No matching protein found.";
                       	return;
               	}

		$id_string = implode(", ", $o_idlist);
		$id_count = count($o_idlist);
		echo "<p>Total sequences found: <b>$id_count</b>";
//               	echo "<p>Accession ID: $id_string</p>";

		$fasta_sequence = '';
		$batches = array_chunk($o_idlist, 10);
		foreach ($batches as $batch){
			$ids = implode(',', $batch);  // Convert the batch to a comma-separated string of IDs
          		$ncbi_fetch = "https://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?db=protein&id=$ids&retmode=text&rettype=fasta&api_key=$ncbi_token";

            		// Fetch the FASTA sequence for the batch
          		$batch_fasta = file_get_contents($ncbi_fetch);
			$fasta_sequence .= $batch_fasta;

		}

		displayFastaTable($fasta_sequence);

	}

echo <<<_TAIL

	<script type="text/javascript" src="https://bioinfmsc8.bio.ed.ac.uk/~s2704130/S2_IWD/ICA_Website_250318/website_dev/ica_script.js"></script>
</body>
<!-- user can save and come back to the page, cookies, ensure that can't be hacked (JS) -->
</html>
_TAIL;

?>
