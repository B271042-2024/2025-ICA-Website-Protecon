<?php
session_start();

	// Tool 1: Extract details from NCBI protein
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
			align-items: center;
			margin-bottom: 10px;
			font: 18px Arial, sans-serif;
		}
		.input_group label{
			text-align: left;
		}
		.input_group input{
			padding: 8px;
			font: 18px
		}
		.input_group span{
			font-style: italic;
		}
		.input_checkbox div{		/* among checkboxes */
			display: flex;
			gap: 40px;
		}
		.input_checkbox label{
			display: flex;
			align-items: center;
			gap: 20px;
		}
		button {
			margin-top: 5px;
			padding: 10px 40px;
			border-radius: 10px;
			color: white;
			background-color: black;
			border: none;
			font-size: 18px;
			font-weight: bold;
			cursor: pointer;
			;
		}
		.input_checkbox{
			font: 18px Arial, sans-serif;
		}
		.input_checkbox input[type="checkbox"]{
			transform: scale(1.5);
		}

	</style>


</head>

<body>

<!-- 1 EXTRACT INFORMATION FROM NCBI PROTEIN BEGINS -->
<!-- input1 begins -->

	<form method="POST" action="ica_tools.php">
		<br>
		<div class="input_group">
			<label for="input1"><b>Protein Name:</b></label>
			<input type="text" id="input1" name="input1" placeholder="Enter protein name">
			<span>e.g. Glucose-6-Phosphatase</span>
		</div>
        	<div class="input_group">
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
		<div class="input_group">
			<label for "input3"><b>No. of sequences:</b></label>
			<input type="number" id="input3" name="input3" placeholder="Default: 20" max="300">
			<span>Leave blank to use default</span>
		</div>
		<br>
		<button type="submit" name="button1">Submit</button>
		<hr>
	</form>
<!-- input1 ends -->

<!-- output1 starts -->
	<?php if ($_SESSION['step'] >= 1): ?>
		<br>
		<?php
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
			echo "$ncbi_search";
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
                	echo "<p>Accession ID: $id_string</p>";

			$fasta_sequence = '';
			$batches = array_chunk($o_idlist, 10);
			foreach ($batches as $batch){
				$ids = implode(',', $batch);  // Convert the batch to a comma-separated string of IDs
          			$ncbi_fetch = "https://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?db=protein&id=$ids&retmode=text&rettype=fasta&api_key=$ncbi_token";

            			// Fetch the FASTA sequence for the batch
            			$batch_fasta = file_get_contents($ncbi_fetch);
				$fasta_sequence .= $batch_fasta;
			}

			echo "<pre>$fasta_sequence</pre>";
		}

		?>

		<hr>	<!-- horizontal line-->

		<form method="POST" action="">
			<button type="submit" name="button2">Button 2</button>
		</form>
	<?php endif; ?>
<!-- output1 ends -->

<!-- input2 starts -->
<!-- input2 ends -->

<!-- 1 EXTRACT INFORMATION FROM NCBI PROTEIN ENDS -->

        <?php if ($_SESSION['step'] >= 2): ?>
                <p>Button 2 was clicked.</p>
                <form method="POST" action="">
                        <button type="submit" name="button3">Button 3</button>
                </form>
        <?php endif; ?>

</body>



<!-- user can save and come back to the page, cookies, ensure that can't be hacked (JS) -->


</html>
