<?php
session_start();

	//set session id
	if (!isset($_SESSION['session_id'])) {
		$_SESSION['session_id'] = bin2hex(random_bytes(16)); // Initial session ID
	}

	$sessionid = $_SESSION['session_id'];
	$date = date('Y-m-d');


	//connect to .bash_profile that has details to connect to mysql
	function loadBashProfileVars() {
		$bashProfilePath = '/home/s2704130/.bash_profile';

	    	if (!file_exists($bashProfilePath)) {
        		echo '<p>Error: .bash_profile not found at ' . $bashProfilePath . '</p>';
        		return false;
    		}

    		$bashProfile = file_get_contents($bashProfilePath);

    		// Pattern to match variable definitions in .bash_profile
		preg_match_all('/export\s+(DB_[A-Za-z0-9_]+)=([^\n]+)/', $bashProfile, $matches);

		for ($i = 0; $i < count($matches[1]); $i++) {
        		$key = $matches[1][$i];
        		$value = trim($matches[2][$i], '"\'');
        		putenv("$key=$value");
    		}
    		return true;
	}

	// Load the MySQL connection variables from .bash_profile
	loadBashProfileVars();

	$dbHost = getenv('DB_HOST');
	$dbUser = getenv('DB_USER');
	$dbPass = getenv('DB_PASSWORD');
	$dbName = getenv('DB_NAME');

	//print_r(getenv());

	//connect to mysql
	try{
        	$pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
        	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);  // get error
	} catch (PDOException $e){
        	echo "Connection failed: " . $e->getMessage();
	}


	if (!isset($_SESSION['sequence_data'])){
		$_SESSION['sequence_data'] = [];
	}

        function displayFastaTable($fasta_sequence){
		echo '<div class="output_fasta" id="table-fasta">';
               	echo "<table>";
               	echo "<tr><th>Select</th><th>Accession No.</th><th>Sequence Name</th><th>Length</th></tr>";

		$sequences = explode(">", $fasta_sequence);

		foreach ($sequences as $sequence){
        	       if (trim($sequence) !== ""){
                 	      	$seq = explode("\n", trim($sequence), 2);
                              	$seq_header = htmlspecialchars($seq[0]);
				$seq_acc = explode(" ", $seq_header)[0];
				$seq_name = implode(" ", array_slice(explode(" ", $seq_header), 1));
                              	$seq_sequence = isset($seq[1]) ? preg_replace('/\s+/', '', trim($seq[1])) : '';
                              	$seq_length = strlen($seq_sequence);
				$_SESSION['sequence_data'][] = ['acc' => $seq_acc, 'name' => $seq_name, 'length' => $seq_length, 'sequence' => ">$seq_header\n$seq_sequence"];

				echo "<tr><td><input type='checkbox' class='delete-checkbox checkie' data-sequence-id='$seq_acc' name='del[]' value='$seq_acc'></td><td>$seq_acc</td><td>$seq_name</td><td>$seq_length</td></tr>";

                        }
		}
                echo "</table>";
		echo "</div>";
		echo "<br>";
		echo "<form method='POST' action=''>";
		echo '<div id="button-fasta1" style="text-align: left;">';
			echo "<button type='button' id='delete-button' onclick='deleteSelectedRows(event)'>Remove</button>";
		echo "</div>";

		echo '<div id="button-fasta2" style="text-align: right;">';
			echo "<button type='submit' id='button4' onclick=proceedTool(event)>Proceed</button>";
		echo "</div>";
		echo "</form>";
	} //end fxn

	// to match with the visualized data in the table, only transfer data that are not deleted to mysql
	if (isset($_POST['delete_ids'])){		// receive from JS AJAX as delete_ids
		$deleted_ids = explode(',', $_POST['delete_ids']);
		$_SESSION['sequence_data'] = array_filter($_SESSION['sequence_data'], function($seq) use ($deleted_ids) {
			return !in_array($seq['acc'], $deleted_ids);		// remove rows with the accession no.
		});
		$_SESSION['sequence_data'] = array_values($_SESSION['sequence_data']);
		echo "They are deleted.";
	}


	if (isset($_POST['button_proceed'])){
		echo "<br>";
		echo "<br>";
		echo "<p><i>Scroll below to continue. Refresh page to redo this section...</i></p>";
		echo "<br>";
               	echo "<hr>";
		echo "<br>";
		echo "<br>";
		echo "<p><b>Please select tools to run your sequence analysis below:</b></p>";

               	foreach ($_SESSION['sequence_data'] as $seq) {
                       	transfertoSQL($pdo, $sessionid, $seq['acc'], $seq['name'], $seq['length'], $seq['sequence']);
		}

 		echo "<div class='table_tools'>";
			echo "<table>";
				echo "<tr><th>Tools</th><th>Description</th><th>Select</th></tr>";
				echo "<tr><td>ClustalO</td><td>For protein alignment</td><td><input type='checkbox' class='select-tools' name='selecttools[]' value='ClustalO'></td></tr>";
				echo "<tr><td>EMBOSS: patmatmotifs</td><td>Use PROSITE database to search for motifs</td><td><input type='checkbox' class='select-tools' name='selecttools[]' value='patmatmotifs'></td></tr>";
				echo "<tr><td>EMBOSS: plotcon</td><td>To generate protein conservation plot</td><td><input type='checkbox' class='select-tools' name='selecttools[]' value='plotcon'></td></tr>";
				echo "<tr><td>IQTREE</td><td>To generate phylogenetic tree</td><td><input type='checkbox' class='select-tools' name='selecttools[]' value='iqtree'></td></tr>";
				echo "<tr><td>NGL Viewer</td><td>To view 3D protein conservation</td><td><input type='checkbox' class='select-tools' name='selecttools[]' value='ngl'></td></tr>";
			echo "</table>";
		echo "</div>";
		echo "<br>";
		echo "<br>";
		echo "<p><i>Once process is running, it may take awhile to finish depending on the size of your data. Press 'Run' once and wait...</i></p>";
		echo '<img border="0" hspace="0" src="./ica_images/sand_clock.png" width="40" style="float: left;">';
		echo "<br>";

		echo "<form method='POST' action=''>";
			echo '<div id="button-run" style="text-align: right;">';
				echo "<button type='button' id='button5' onclick='runAnalysis(event)'>Run</button>";
			echo "</div>";
		echo "</form>";
		exit;	// stop page from showing content-main
        }

	function transfertoSQL($pdo, $sessionid, $seq_acc, $seq_name, $seq_length, $fasta_sequence){
		try{
			$webtosql = "insert into temporary_data (session_id, accession_no, sequence_name, length, fasta_sequence) values (:session_id, :accession_no, :sequence_name, :length, :fasta_sequence)";
			$stmt = $pdo->prepare($webtosql);
    			$stmt->bindParam(':session_id', $sessionid, PDO::PARAM_STR);
    			$stmt->bindParam(':accession_no', $seq_acc, PDO::PARAM_STR);
	    		$stmt->bindParam(':sequence_name', $seq_name, PDO::PARAM_STR);
			$stmt->bindParam(':length', $seq_length, PDO::PARAM_INT);
			$stmt->bindParam(':fasta_sequence', $fasta_sequence, PDO::PARAM_STR);
			$stmt-> execute();
			error_log("Data successfully transferred to MYSQL");
		} catch (PDOException $e){
			error_log("Error transferring data to MYSQL: " . $e->getMessage());
		}
	}


        function fromysql_todelete($jobid, $pdo, $sessionid){
		try{
                        if (!empty($jobid)){
				$sessionid = $jobid;
			}

				$delete_sql = "DELETE FROM temporary_data WHERE session_id = :sessionid";
				$delete_stmt = $pdo->prepare($delete_sql);
				$delete_stmt->bindParam(':sessionid', $sessionid, PDO::PARAM_STR);
				$delete_stmt->execute();
				error_log("Rows deleted where session_id = " . $sessionid);
			return true;
		} catch (PDOException $e){
			error_log("Error deleting from MYSQL: ". $e->getMessage());
			return false;
		}
	}



	function fromysql($jobid, $pdo, $sessionid){
		try{
			if (!empty($jobid)){
				$sessionid = $jobid;
			}

			$sqltoweb = "select * from temporary_data where session_id = :sessionid";
			$stmt = $pdo->prepare($sqltoweb);
			$stmt->bindParam(':sessionid', $sessionid, PDO::PARAM_STR);
			$stmt->execute();

			$sql_fasta = [];
			while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
				$sql_fasta[] = $row['fasta_sequence'];
			}
			error_log("FASTA sequences: " . json_encode($sql_fasta));
			return $sql_fasta;
		} catch (PDOException $e){
			error_log("Error fetching from MYSQL: " . $e->getMessage());
			return [];
		}
	}


        function transferDataPermanent($sessionid, $pdo, $username, $date){
                //transfer data to the permanent table
                try{
                        //from table temporary_data to session_details
                        $webtosql2 = "insert into session_details (user_id, session_id, time) values (:user_id, :session_id, :time)";
                        $stmtInsert2 = $pdo->prepare($webtosql2);
                        $stmtInsert2->bindValue(':user_id', $username, PDO::PARAM_STR);
                        $stmtInsert2->bindValue(':session_id', $sessionid, PDO::PARAM_STR);
                        $stmtInsert2->bindValue(':time', $date, PDO::PARAM_STR);
                        $stmtInsert2->execute();


			//fetch data from table temporary_data
                        $sqltoweb = "select * from temporary_data where session_id = :sessionid";
                        $stmt = $pdo->prepare($sqltoweb);
                        $stmt->bindParam(':sessionid', $sessionid, PDO::PARAM_STR);
                        $stmt->execute();

			$sql_accessionno = [];
			$sql_sequencename = [];
			$sql_length = [];
			$sql_fasta = [];

                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                                $sql_fasta[] = $row['fasta_sequence'];
                        	$sql_accessionno[] = $row['accession_no'] ;
                        	$sql_sequencename[] = $row['sequence_name'];
                        	$sql_length[] = $row['length'];
                        }

			//from table temporary_data to protein_sequences
			$webtosql1 = "insert into protein_sequences (session_id, accession_no, sequence_name, length, fasta_sequence) values (:sessionid, :accession_no, :sequence_name, :length, :fasta_sequence)";
			$stmtInsert = $pdo->prepare($webtosql1);
        		for ($i = 0; $i < count($sql_fasta); $i++) {
            			$stmtInsert->bindParam(':sessionid', $sessionid, PDO::PARAM_STR);
            			$stmtInsert->bindParam(':accession_no', $sql_accessionno[$i], PDO::PARAM_STR);
            			$stmtInsert->bindParam(':sequence_name', $sql_sequencename[$i], PDO::PARAM_STR);
            			$stmtInsert->bindParam(':length', $sql_length[$i], PDO::PARAM_INT);
            			$stmtInsert->bindParam(':fasta_sequence', $sql_fasta[$i], PDO::PARAM_STR);
            			$stmtInsert->execute();
        		}

			error_log("Data successfully transferred to MYSQL");
                } catch (PDOException $e){
			echo "<p>Error: " . $e->getMessage() . "</p>";
                        error_log("Error from MYSQL: " . $e->getMessage());
                }
        }


        function transferDataPtoTemporary($jobid, $pdo, $date){
                //transfer data to temp table
                try{

                        //fetch data from table temporary_data
                        $sqltoweb = "select * from protein_sequences where session_id = :sessionid";
                        $stmt = $pdo->prepare($sqltoweb);
                        $stmt->bindParam(':sessionid', $jobid, PDO::PARAM_STR);
                        $stmt->execute();

        		// Check if job id exist
        		if ($stmt->rowCount() == 0) {
            			echo "<p><b>Warning: Job ID: $jobid does not exist.</b></p>";
            			error_log("Warning: Job ID: $jobid does not exist.");
            			exit;  // Exit
        		}

                        $sql_accessionno = [];
                        $sql_sequencename = [];
                        $sql_length = [];
                        $sql_fasta = [];

                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                                $sql_fasta[] = $row['fasta_sequence'];
                                $sql_accessionno[] = $row['accession_no'] ;
                                $sql_sequencename[] = $row['sequence_name'];
                                $sql_length[] = $row['length'];
                        }

                        //from table protein_sequences to temporary_data
                        $webtosql1 = "insert into temporary_data (session_id, accession_no, sequence_name, length, fasta_sequence) values (:sessionid, :accession_no, :sequence_name, :length, :fasta_sequence)";
                        $stmtInsert = $pdo->prepare($webtosql1);
                        for ($i = 0; $i < count($sql_fasta); $i++) {
                                $stmtInsert->bindParam(':sessionid', $jobid, PDO::PARAM_STR);
                                $stmtInsert->bindParam(':accession_no', $sql_accessionno[$i], PDO::PARAM_STR);
                                $stmtInsert->bindParam(':sequence_name', $sql_sequencename[$i], PDO::PARAM_STR);
                                $stmtInsert->bindParam(':length', $sql_length[$i], PDO::PARAM_INT);
                                $stmtInsert->bindParam(':fasta_sequence', $sql_fasta[$i], PDO::PARAM_STR);
                                $stmtInsert->execute();
                        }

                        error_log("Data successfully transferred to MYSQL");
                } catch (PDOException $e){
                        echo "<p>Error: " . $e->getMessage() . "</p>";
                        error_log("Error from MYSQL: " . $e->getMessage());
                }
        }


	function clustalo($jobid, $sql_fasta, $sessionid){
		if (!empty($sql_fasta)){
        		echo "<br>";
        		echo "<br>";

        		if(!empty($jobid)){
            			$sessionid = $jobid;
        		}

	        	$input_seq = "./tmp/" . $sessionid . "_seq.fasta";
        		$fasta_content = implode("\n", $sql_fasta);
        		$createfile = fopen($input_seq, 'w');
        		if($createfile){
        			fwrite($createfile, $fasta_content);
            			fclose($createfile);
        		} else{
            			echo "Error opening the file";
            		return;
        		};

        		$output_clustalo = "./tmp/" . $sessionid . "_clustalo.aln";
        		echo "<p><b>ClustalO output:</b></p>";
        		$run_clustalo = shell_exec("clustalo -i $input_seq -o $output_clustalo");

        		// Download button
        		echo "<div class=button_download>";
        		echo '<button onclick="downloadFile(\'' . basename($input_seq) . '\', event)">Download .fasta</button>';
        		echo '<button onclick="downloadFile(\'' . basename($output_clustalo) . '\', event)">Download .aln</button>';
        		echo "</div>";
        		echo "<br>";

        		// Display ClustalO output on website
        		echo "<div class = 'display_clustalo'>";
        		echo "<table class='table_aln' style='border-collapse: collapse;'>";

		        // Fetch the sequences
       			$output_contents = file_get_contents($output_clustalo);
        		$lines = explode("\n", $output_contents);

		        $sequences = [];
        		$seq_header = "";
        		$seq_sequence = "";

		        // Process the output to collect sequences
        		foreach ($lines as $line) {
            			if (strpos($line, '>') === 0) { // Header line
                			if (!empty($seq_sequence)) {
                    				$sequences[] = ['header' => $seq_header, 'sequence' => $seq_sequence];
                			}
                			$seq_header = htmlspecialchars($line);
                			$seq_sequence = "";
            			} else {
                			$seq_sequence .= trim($line);
            			}
        		}
        		if (!empty($seq_sequence)) {
            			$sequences[] = ['header' => $seq_header, 'sequence' => $seq_sequence];
        		}

        		// Find the longest sequence length to align all sequences
        		$max_length = 0;
        		foreach ($sequences as $seq) {
            		$max_length = max($max_length, strlen($seq['sequence']));
        	}

        	// Create rows for each sequence
        	foreach ($sequences as $seq) {
            		echo "<tr>";
            		// sequence name
            		echo "<td class='clustalo_header' style='padding: 5px; text-align: center;'>" . $seq['header'] . "</td>";

            		// base blocks in the sequences
            		$sequence_array = str_split(str_pad($seq['sequence'], $max_length, '-')); // Add gaps if sequence is shorter
            		foreach ($sequence_array as $base) {
                		echo "<td class='clustalo_seq' style='padding: 5px; text-align: center; font-size: 14px'>" . $base . "</td>";
            		}
            		echo "</tr>";
        	}

	        echo "</table>";
        	echo "</div>";
        	return [$input_seq, $output_clustalo];
    		} else {
        		echo "<p>No sequence in the input FASTA file.</p>";
    		}
	}



	function patmatmotifs($jobid, $sql_fasta, $sessionid){
		if (!empty($sql_fasta)){

                        if(!empty($jobid)){
                                $sessionid = $jobid;
                        }

			//if file not exist, then create
			$input_seq = "./tmp/" . $sessionid . "_seq.fasta";
			if(!file_exists($input_seq)){
                        	$fasta_content = implode("\n", $sql_fasta);
                        	$createfile = fopen($input_seq, 'w');
                        	if($createfile){
                                	fwrite($createfile, $fasta_content);
                                	fclose($createfile);
                        	} else{
                                	echo "<p>Error opening the file</p>";
                                	return;
                        	};
			}

			$output_patmatmotifs = "./tmp/" . $sessionid . "_patmatmotifs.txt";
                        echo "<br>";
                        echo "<br>";
			echo "<br>";
                        echo "<br>";
			echo "<p><b>EMBOSS patmatmotifs output:</b></p>";
                        echo "<div class=button_download>";
                                echo '<button onclick="downloadFile(\'' . basename($output_patmatmotifs) . '\', event)">Download motifs</button>';
                        echo "</div>";

			$run_motif = shell_exec("patmatmotifs -full -sequence $input_seq -outfile $output_patmatmotifs");
			echo "<pre>$run_motif</pre>";
			$output_contents = file_get_contents($output_patmatmotifs);
			echo "<pre>" . ($output_contents) . "</pre>";

                } else{
                        echo "<p>FASTA file does not exist possibly due to deletion. Please re-run analysis.</p>";
                }
	}


	function plotcon($jobid, $sql_fasta, $sessionid){
		//check if it exist in the tmp
                if (!empty($sql_fasta)){
                        echo "<br>";
                        echo "<br>";
                        echo "<br>";
                        echo "<p><b>EMBOSS plotcon output:</b></p>";

			//set session id
                	if(!empty($jobid)){
                        	$sessionid = $jobid;
                	}

                        //define input
                        $input_aln = "./tmp/" . $sessionid . "_clustalo.aln";

                        //if aln file is absent, run clustalo
                        if(!file_exists($input_aln)){
                                $input_seq = "./tmp/" . $sessionid . "_seq.fasta";
                                if(!file_exists($input_seq)){
                                        $fasta_content = implode("\n", $sql_fasta);
                                        $createfile = fopen($input_seq, 'w');
                                        if($createfile){
                                                fwrite($createfile, $fasta_content);
                                                fclose($createfile);
                                        } else{
                                                echo "<p>Error opening the file</p>";
                                                return;
                                        };
                                }
                                $run_clustalo = shell_exec("clustalo -i $input_seq -o $input_aln");
                        }

			//winsize=4
			//png
			$output_png_4 = "./tmp/" . $sessionid . "_plotcon_4";
			$realoutput_png_4 = "./tmp/" . $sessionid . "_plotcon_4.1.png";
			$output = shell_exec("./ica_plotcon.sh " . escapeshellarg($input_aln) . " " . escapeshellarg($output_png_4) . " 2>&1");
			echo "<p>1. -winsize = 4 (Fine details: conservation score based on 4 consecutive bases at a time)</p>";
			//data
			$output_data_4 = "./tmp/" . $sessionid . "_plotcondata_4";
                        $realoutput_data_4 = "./tmp/" . $sessionid . "_plotcondata_41.dat";
                        $output = shell_exec("./ica_plotcon.sh " . escapeshellarg($input_aln) . " " . escapeshellarg($output_data_4) . " 2>&1");

                        //set download button (output)
                        echo "<div class=button_download>";
				echo '<button onclick="downloadFile(\'' . basename($realoutput_png_4) . '\', event)">Download .png</button>';
				echo '<button onclick="downloadFile(\'' . basename($realoutput_data_4) . '\', event)">Download .dat</button>';
                        echo "</div>";

			//display
                        echo "<img border='0' hspace='0' src='$realoutput_png_4' width='700' style='display: block; margin-left: 0'>";  //"" outside allows php to parse $var


			echo "<br>";

			//winsize=8
			//png
                        $output_png_8 = "./tmp/" . $sessionid . "_plotcon_8";
                        $realoutput_png_8 = "./tmp/" . $sessionid . "_plotcon_8.1.png";
                        $output = shell_exec("./ica_plotcon.sh " . escapeshellarg($input_aln) . " " . escapeshellarg($output_png_8) . " 2>&1");
                        echo "<p>2. -winsize = 8 (Moderate: conservation score based on 8 consecutive bases at a time)</p>";
                        //data
                        $output_data_8 = "./tmp/" . $sessionid . "_plotcondata_8";
                        $realoutput_data_8 = "./tmp/" . $sessionid . "_plotcondata_81.dat";
                        $output = shell_exec("./ica_plotcon.sh " . escapeshellarg($input_aln) . " " . escapeshellarg($output_data_8) . " 2>&1");

                        //set download button (output)
                        echo "<div class=button_download>";
                                echo '<button onclick="downloadFile(\'' . basename($realoutput_png_8) . '\', event)">Download .png</button>';
                                echo '<button onclick="downloadFile(\'' . basename($realoutput_data_8) . '\', event)">Download .dat</button>';
                        echo "</div>";

			//display
                        echo "<img border='0' hspace='0' src='$realoutput_png_8' width='700' style='display: block; margin-left: 0'>";  //"" outside allows php to parse $var

			echo "<br>";

			//winsize=12
                        //png
                        $output_png_12 = "./tmp/" . $sessionid . "_plotcon_12";
                        $realoutput_png_12 = "./tmp/" . $sessionid . "_plotcon_12.1.png";
                        $output = shell_exec("./ica_plotcon.sh " . escapeshellarg($input_aln) . " " . escapeshellarg($output_png_12) . " 2>&1");
                        echo "<p>3. -winsize = 12 (Smooth: conservation score based on 12 consecutive bases at a time)</p>";
                        //data
                        $output_data_12 = "./tmp/" . $sessionid . "_plotcondata_12";
                        $realoutput_data_12 = "./tmp/" . $sessionid . "_plotcondata_121.dat";
                        $output = shell_exec("./ica_plotcon.sh " . escapeshellarg($input_aln) . " " . escapeshellarg($output_data_12) . " 2>&1");

                        //set download button (output)
                        echo "<div class=button_download>";
                                echo '<button onclick="downloadFile(\'' . basename($realoutput_png_12) . '\', event)">Download .png</button>';
                                echo '<button onclick="downloadFile(\'' . basename($realoutput_data_12) . '\', event)">Download .dat</button>';
                        echo "</div>";

			//display
                        echo "<img border='0' hspace='0' src='$realoutput_png_12' width='700' style='display: block; margin-left: 0'>";  //"" outside allows php to parse $var

			echo "<br>";


//                      echo "PHP is running as: " . shell_exec("whoami") . "<br>";


		} else{
			echo "<p>FASTA file does not exist possibly due to deletion. Please re-run analysis.</p>";
		}
	}


	function iqtree($jobid, $sql_fasta, $sessionid){
		if (!empty($sql_fasta)){
                        echo "<br>";
                        echo "<br>";
                        echo "<br>";
			echo "<p><b>IQTREE output (Bootstrap=1000):</b></p>";

			//set session id
                        if(!empty($jobid)){
                                $sessionid = $jobid;
                        }

			//define input
			$input_aln = "./tmp/" . $sessionid . "_clustalo.aln";

			//if aln file is absent, run clustalo
			if(!file_exists($input_aln)){
                        	$input_seq = "./tmp/" . $sessionid . "_seq.fasta";
                        	if(!file_exists($input_seq)){
                                	$fasta_content = implode("\n", $sql_fasta);
                                	$createfile = fopen($input_seq, 'w');
                                	if($createfile){
                                        	fwrite($createfile, $fasta_content);
                                        	fclose($createfile);
                                	} else{
                                        	echo "<p>Error opening the file</p>";
                                        	return;
                                	};
                        	}
                        	$run_clustalo = shell_exec("clustalo -i $input_seq -o $input_aln");
			}

			//iqtree folder
			$folder_iqtree = "./tmp/" . $sessionid . "_iqtree";
			if(!file_exists($folder_iqtree)){
				mkdir($folder_iqtree, 0777, true);
			}

			//copy aln to iqtree folder
			$copy_input_aln = $folder_iqtree . "/" . basename($input_aln);
			copy($input_aln, $copy_input_aln);

			//run iqtree
			$run_iqtree = shell_exec("cd $folder_iqtree && /localdisk/home/ubuntu-software/iqtree-2.2.0-Linux/bin/iqtree -B 1000 -s " . basename($copy_input_aln) . " 2>&1");

			//zip folder
			$zip_file = $folder_iqtree . ".zip";
			shell_exec("cd ./tmp && zip -r " . basename($zip_file) . " " . basename($folder_iqtree));


                        //set download button (output folder)
                        echo "<div class=button_download>";
                                echo '<button onclick="downloadFile(\'' . basename($zip_file) . '\', event)">Download IQTREE folder</button>';
                        echo "</div>";

			//to view the tree
			echo '<p><b><a href="https://itol.embl.de/upload.cgi" style="text-decoration: none;" target="_blank">Upload and view TREEFILE on iTOL</a></b></p>';
			echo "<p>TREEFILE can be retrieved from the zipped folder.</p>";

			echo "<br>";
			//display the run
			//echo "<pre>$run_iqtree</pre>";

                } else{
                        echo "<p>FASTA file does not exist possibly due to deletion. Please re-run analysis.</p>";
                }
	}

	function nglviewer(){}


	if (isset($_POST['button_run'])){
		echo "<br>";
		echo "<br>";
                echo "<br>";
		echo "<hr>";
                echo "<br>";

		if (!empty($jobid)){
			$sql_fasta = fromysql($jobid, $pdo, $sessionid);
		} else {
			$sql_fasta = fromysql("", $pdo, $sessionid);
		}

		$tools = json_decode($_POST['tools'], true);
		$result = '';

		foreach ($tools as $tool){
			if ($tool === 'ClustalO'){
				if (!empty($jobid)){
					$output = clustalo($jobid, $sql_fasta, $sessionid);
				} else {
					$output = clustalo("", $sql_fasta, $sessionid);
				}

			} elseif ($tool === 'patmatmotifs'){
                               if (!empty($jobid)){
                                        $output = patmatmotifs($jobid, $sql_fasta, $sessionid);
                                } else {
                                        $output = patmatmotifs("", $sql_fasta, $sessionid);
                                }

			} elseif ($tool === 'plotcon'){
                                if (!empty($jobid)){
                                        $output = plotcon($jobid, $sql_fasta, $sessionid);
                                } else {
                                        $output = plotcon("", $sql_fasta, $sessionid);
                                }

			} elseif ($tool === 'iqtree'){
                                if (!empty($jobid)){
                                        $output = iqtree($jobid, $sql_fasta, $sessionid);
                                } else {
                                        $output = iqtree("", $sql_fasta, $sessionid);
                                }

			} else{
				echo "ngl";
			}

			if (is_array($output)){
				$output = implode("\n", $output);
			}

			$result .= $output;
		}


		//send result back to JS
		echo $result;

		echo "<br>";
		//put the details whether to save session
		echo '<form action="" method="post">';
			echo '<div id="session">';
				echo '<br>';
				echo '<hr>';
				echo '<br>';
				echo '<p><b>To save this session, please create a username and save.</b></p>';
				echo '<div class="save_session">';
					echo '<label>Username:</label>';
					echo '<input type="text" name="input_username" placeholder="Any random unique name"/>';
				echo '</div>';
				echo "<p><b>Your Job ID: $sessionid</b></p>";
				echo '<div class="button_session">';
					echo '<button id="submit_id" name="submit_id" type="submit">Save</button>';
					echo '<button id="cancel" name="cancel" type=submit>Cancel</button>';
				echo '</div>';
			echo '</div>';
		echo '</form>';
		exit;
	}


	if (isset($_POST['submit_id'])){
		if (!empty($_POST['input_username'])){
			//get input
			$username = $_POST['input_username'];
			echo "<p><i>Your session has been saved. To retrieve your session, please enter the information below.</i></p>";
			echo "<p><i>Username: $username; Job ID: $sessionid</i></p>";

			//transfer info to the 2 tables from temporary_data
			transferDataPermanent($sessionid, $pdo, $username, $date);

			//delete temporary table
			fromysql_todelete("", $pdo, $sessionid);

		} else{
			echo "<p>To save information, username is required.</p>";
			echo '<form action="" method="post">';
				echo '<button id="cancel" name="cancel" type=submit>Cancel</button>';
			echo '</form>';
		}
	}


	if (isset($_POST['cancel'])){
		fromysql_todelete($pdo, $sessionid);
		echo "<p>Session has been cleared.</p>";
	}


        function clearsessionfilesfromtmp($sessionid){
		shell_exec("rm -rf ./tmp/*"); /**/
        }

?>

<?php

echo <<<_HEAD
<!DOCTYPE html>
<html lang="en">
<head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>PROTE-Con</title>
        <link rel="stylesheet" href="https://bioinfmsc8.bio.ed.ac.uk/~s2704130/S2_IWD/ICA_Website_250318/website_dev/ica_style.css">

	<style>

		/* General styling */
                .input_group, legend, label, span{
                        font: 18px Arial, sans-serif;
                }

		input[type="text"], input[type="number"]{
    			padding: 8px;
    			margin: 5px 10px 5px 0; /* Space on the right side */
    			width: 100px; /* Set fixed width for inputs */
    			box-sizing: border-box;
		}

		input[type="checkbox"]{
    			margin-right: 5px;
		}

		label{
    			font-weight: bold;
    			width: auto;
		}

		span{
			width: auto;
		}

		/* Styling the div container */
		.input_group, legend{
			font: 20px Arial, sans-serif;
		}

		.listtools{
			display: flex;
			flex-direction: column;
			gap: 10px;
			background: rgba(0, 0, 0, 0.5);;
		}

		.methods{
    			display: flex;
    			flex-direction: column;
    			gap: 20px;
		}

		/* Aligning Method 1 and Method 2 with spacing */
		fieldset{
    			border: 1px solid #D3D3D3;
    			padding: 15px;
    			border-radius: 5px;
    			display: block;
    			width: 95%;
		}

		legend{
/*    			font-weight: bold;	*/
    			padding: 0 10px;
/*			color: #808080;		*/
		}

		.input_checkbox{
    			margin-top: 10px;
		}

		.only_checkbox{
    			display: flex;
    			flex-direction: column;
    			gap: 8px;
		}

		.only_checkbox input[type="checkbox"]{
			transform: scale(1.5);
			margin-right: 15px;
		}

		.input_row{
    			display: flex;
    			align-items: center;
    			margin-bottom: 10px;
		}

		.input_row label{
    			width: 200px;
		}

		.input_row input{
    			width: 500px;
		}

		.input_row span{
			margin-left: 15px;
		}

		button{
			padding: 8px 20px;
			background-color: #DCDCDC;
/*			font-weight: bold;	*/
			color: black;
			border-radius: 5px;
			border: 1px solid black;
			cursor: pointer;
			font-size: 16px;
		}

		button:hover{
			background-color: gray;
		}

		button:focus{
    			background-color: gray;
		}

		.FASTA_file_preview{
			border: 1px solid gray;
			padding: 5px;
                 	border-radius: 5px;
			display: block;
			width: 95%;
			height: 300px;
			text-align: left;
			overflow: auto;
			font: 18px Arial, sans-serif;
		}

		.output_fasta{
			border: 1px solid white;
			padding: 15px;
			border-radius: 5px;
			display: block;
			width: 95%;
			height: 300px;
			text-align: left;
			overflow: auto;
			font: 18px Arial, sans-serif;
		}

		table{
			width: 1100px;
			border-collapse: collapse;
		}

		table th, table td{
			padding: 10px;
			border: 1px solid #D3D3D3;
		}

		table th{
			background-color: black;
			color: white;
		}

		.table_tools{
                        border: 1px solid white;
                        padding: 15px;
                        border-radius: 5px;
                        display: block;
                        width: 95%;
                        text-align: left;
                        overflow: auto;
                        font: 18px Arial, sans-serif;
		}

		.display_clustalo{
                        border: 1px solid #D3D3D3;
                        padding: 5px;
                        border-radius: 5px;
                        display: block;
                        width: 95%;
                        text-align: left;
                        overflow: auto;
			font: 18px Arial, sans-serif;
		}

		.table_aln{
			border: none;
		}

		.table_aln th:first-child, .table_aln td:first-child{
    			width: 400px;
    			white-space: nowrap;
			font-size: 12px;
		}

		.table_aln th:nth-child(2), .table_aln td:nth-child(2){
			white-space: nowrap;
		}

		.display_plotcon{
                        border: 1px solid #D3D3D3;
                        padding: 5px;
                        border-radius: 5px;
                        display: block;
                        width: 800px;
                        overflow: auto;
		}

		.button_download, .button_session, .save_session{
 		       	display: flex;
        		gap: 20px;
        		align-items: center;
		}

		input[name="input_username"]{
			padding: 5px;
			width: 200px;
		}

		button.delete-button:active{
    			background-color: gray;
		}

		.span_click{
			cursor: default;
		}

		.span_click:hover{
			font-weight: bold;
			color: blue;
			cursor: pointer;
		}

	</style>
</head>
<body>
_HEAD;

echo <<<_TOOL1_FASTA
<!-- 1 EXTRACT INFORMATION FROM NCBI PROTEIN BEGINS -->
<!-- input1 begins -->

<div class='main_layout'>
	<form method="POST" action="ica_tools.php" enctype="multipart/form-data">
		<br>
		<div class="methods" id="content-main">
		<h1><b>Get your Sequences (Refresh page to redo this section)</b></h1>
			<fieldset>
				<legend>Method 1: Use your saved Job ID</legend>
                        	<div class="input_row">
					<label for="input0"><b>Job ID:</b></label>
                        		<input type="text" id="input0" name="input0" placeholder="Enter Job ID">
                        		<span>e.g. 1234567890abcdef</span>
				</div>
                                <div class="input_row">
                                        <label for="input0_1"><b>Username:</b></label>
                                        <input type="text" id="input0_1" name="input0_1" placeholder="Enter Username">
                                        <span>e.g. panda123</span>
                                </div>
                                <br>
                                        <button type="submit" name="button0">Submit</button>
			</fieldset>

			<p><b>OR</b></p>

			<fieldset>
				<legend>Method 2: Retrieve sequences from NCBI Protein</legend>
				<p><i>To use example, click on Glucose-6-Phosphatase and Aves. Press Submit. The output will be at the bottom of the page.</i></p>
				<div class="input_row">
					<label for="input1"><b>Protein Name:</b></label>
					<input type="text" id="input1" name="input1" placeholder="Enter protein name">
					<span onclick="document.getElementById('input1').value='Glucose-6-Phosphatase'" class="span_click">e.g. Glucose-6-Phosphatase</span>
				</div>
				<div class="input_row">
                			<label for="input2"><b>Taxonomy Group:</b></label>
                			<input type="text" id="input2" name="input2" placeholder="Enter taxonomic group">
                			<span onclick="document.getElementById('input2').value='Aves'" class="span_click">e.g. Aves</span>
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

			<p><b>OR</b></p>

                        <fieldset>
                                <legend>Method 3: Upload FASTA file</legend>
                                <div class="input_row">
                                        <label for="upload_fastafile"><b>Upload FASTA file:</b></label>
                                        <input type="file" id="upload_fasta" name="upload_fasta" accept=".fasta,.fa,.txt">
                                        <span>Only .fasta, .fa, .txt files</span>
                                </div>
                                <br>
                                        <button type="submit" name="button2">Upload</button>
                        </fieldset>
		</div>
		<br>
	</form>
</div>

<!-- input1 ends -->
_TOOL1_FASTA;



// <!-- output1 starts -->

	//Tool2: Upload FASTAfile onto website
	if (isset($_POST['button2'])){
		//check if the file exist or error associated
		if (!isset($_FILES['upload_fasta']) || $_FILES['upload_fasta']['error'] !== UPLOAD_ERR_OK){
			die("Error: No file uploaded or uploading failed.");
		}

		//clear up previous session
		fromysql_todelete("", $pdo, $sessionid);

		//get file
		$file_tmp = $_FILES['upload_fasta']['tmp_name'];
		$file_name = $_FILES['upload_fasta']['name'];

		//get file contents
		$file_content = file_get_contents($file_tmp);

		//preview
		echo '<div class="FASTA_file_preview">';
			echo '<pre>' . htmlspecialchars($file_content) . '</pre>';
		echo '</div>';

		//transfer to mysql tmp table
		$incremented_no = 1;
		$header = '';
		$seq = '';
		$header_exist = false;	//check if header exist (formatting)
		foreach (explode("\n", $file_content) as $row){
			//if starts with >, it is a header
			if (strpos($row, '>')=== 0){
				$header_exist = true;
				if (!empty($header) && !empty($seq)){
					$seq_acc = $sessionid . "_" . $incremented_no;
					$seq_name = $header;
					$seq_length = strlen($seq);
					$fasta_sequence = ">" .  $header . "\n" . $seq;

					transfertoSQL($pdo, $sessionid, $seq_acc, $seq_name, $seq_length, $fasta_sequence);

					$incremented_no++;
				}

				$header = substr($row, 1);
				$seq = '';
			} else{
				$seq .= $row;
			}
		}

		if (!$header_exist){
			die("Error: File format is not FASTA. Action is halted.");
		}

		if (!empty($header) && !empty($seq)){
			$seq_acc = $sessionid . "_" . $incremented_no;
			$seq_name = $header;
			$seq_length = strlen($seq);
			$fasta_sequence = ">" . $header . "\n" . $seq;

			transfertoSQL($pdo, $sessionid, $seq_acc, $seq_name, $seq_length, $fasta_sequence);
		}




                //select analyses
                echo "<p><b>Please select tools to run your sequence analysis below:</b></p>";

                echo "<div class='table_tools'>";
                        echo "<table>";
                                echo "<tr><th>Tools</th><th>Description</th><th>Select</th></tr>";
                                echo "<tr><td>ClustalO</td><td>For protein alignment</td><td><input type='checkbox' class='select-tools' name='selecttools[]' value='ClustalO'></td></tr>";
                                echo "<tr><td>EMBOSS: patmatmotifs</td><td>Use PROSITE database to search for motifs</td><td><input type='checkbox' class='select-tools' name='selecttools[]' value='patmatmotifs'></td></tr>";
                                echo "<tr><td>EMBOSS: plotcon</td><td>To generate protein conservation plot</td><td><input type='checkbox' class='select-tools' name='selecttools[]' value='plotcon'></td></tr>";
                                echo "<tr><td>IQTREE</td><td>To generate phylogenetic tree</td><td><input type='checkbox' class='select-tools' name='selecttools[]' value='iqtree'></td></tr>";
                                echo "<tr><td>NGL Viewer</td><td>To view 3D protein conservation</td><td><input type='checkbox' class='select-tools' name='selecttools[]' value='ngl'></td></tr>";
                        echo "</table>";
                echo "</div>";
                echo "<br>";
                echo "<br>";
                echo "<p><i>Once process is running, it may take awhile to finish depending on the size of your data. Press 'Run' once and wait...</i></p>";
                echo '<img border="0" hspace="0" src="./ica_images/sand_clock.png" width="40" style="float: left;">';
                echo "<br>";

                echo "<form method='POST' action=''>";
                        echo '<div id="button-run" style="text-align: right;">';
                                echo "<button type='button' id='button5' onclick='runAnalysis(event)'>Run</button>";
                        echo "</div>";
                echo "</form>";
                echo '<script type="text/javascript" src="https://bioinfmsc8.bio.ed.ac.uk/~s2704130/S2_IWD/ICA_Website_250318/website_dev/ica_script.js"></script>';
                exit;   // stop page from showing content-main
	}


	//Tool 0: Extract Job ID from sql
	if (isset($_POST['button0'])){
                fromysql_todelete("", $pdo, $sessionid);

                //extract input0 - 0_1
                $jobid = trim($_POST['input0']);
                $ji_username = trim($_POST['input0_1']);

		if (empty($jobid) || empty($ji_username)){
			echo "<p>Error. Please key in Job Id and Username.</p>";
			return;
		}

		//extract sessionid and seq
		$_SESSION['session_id'] = $jobid;

		transferDataPtoTemporary($jobid, $pdo, $date);

		//select analyses
                echo "<p><b>Please select tools to run your sequence analysis below:</b></p>";

                echo "<div class='table_tools'>";
                        echo "<table>";
                                echo "<tr><th>Tools</th><th>Description</th><th>Select</th></tr>";
                                echo "<tr><td>ClustalO</td><td>For protein alignment</td><td><input type='checkbox' class='select-tools' name='selecttools[]' value='ClustalO'></td></tr>";
                                echo "<tr><td>EMBOSS: patmatmotifs</td><td>Use PROSITE database to search for motifs</td><td><input type='checkbox' class='select-tools' name='selecttools[]' value='patmatmotifs'></td></tr>";
                                echo "<tr><td>EMBOSS: plotcon</td><td>To generate protein conservation plot</td><td><input type='checkbox' class='select-tools' name='selecttools[]' value='plotcon'></td></tr>";
                                echo "<tr><td>IQTREE</td><td>To generate phylogenetic tree</td><td><input type='checkbox' class='select-tools' name='selecttools[]' value='iqtree'></td></tr>";
                                echo "<tr><td>NGL Viewer</td><td>To view 3D protein conservation</td><td><input type='checkbox' class='select-tools' name='selecttools[]' value='ngl'></td></tr>";
			echo "</table>";
                echo "</div>";
                echo "<br>";
                echo "<br>";
                echo "<p><i>Once process is running, it may take awhile to finish depending on the size of your data. Press 'Run' once and wait...</i></p>";
                echo '<img border="0" hspace="0" src="./ica_images/sand_clock.png" width="40" style="float: left;">';
                echo "<br>";

                echo "<form method='POST' action=''>";
                        echo '<div id="button-run" style="text-align: right;">';
                                echo "<button type='button' id='button5' onclick='runAnalysis(event)'>Run</button>";
                        echo "</div>";
                echo "</form>";
		echo '<script type="text/javascript" src="https://bioinfmsc8.bio.ed.ac.uk/~s2704130/S2_IWD/ICA_Website_250318/website_dev/ica_script.js"></script>';
                exit;   // stop page from showing content-main
	}


	// Tool 1: Extract details from NCBI protein
	if (isset($_POST['button1'])){

		//delete data from mysql each time user key in new entry at each session. It will only be saved once the user click save.
		fromysql_todelete("", $pdo, $sessionid);
		$_SESSION['session_id'] = bin2hex(random_bytes(16));

		echo "<div id=output-seqdetails>";
		// extract input1-3
               	$protein_name = trim($_POST['input1']);
               	$taxon_group = trim($_POST['input2']);
		$num_sequence = isset($_POST['input3']) && !empty($_POST['input3']) ? (int)$_POST['input3'] : 20;	// default: 20
               	$ncbi_token = "abb4f7cff84a4af777891b6f35184e703808";
		echo "<p><b>Protein Name:</b> $protein_name</p>";
		echo "<p><b>Taxonomy Group:</b> $taxon_group</p>";

		$ncbi_search = "https://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=protein&term=" . urlencode($protein_name) . "+AND+" . urlencode($taxon_group);
		if (!empty($_POST['options'])){
			$encode_options = array_map('urlencode', $_POST['options']);
			$filter = implode("+NOT+", $encode_options);
			$ncbi_search .= "+NOT+" . $filter;
		}
		$ncbi_search .= "&retmax=$num_sequence&retmode=json&api_key=$ncbi_token";


		// NCBI SEARCH
		$o_search = file_get_contents($ncbi_search);
               	if($o_search === false){
                       	echo "<p>Error. Unable to connect to NCBI API.</p>";
                       	return;
               	}

               	$o_data = json_decode($o_search, true);
               	$o_idlist = $o_data['esearchresult']['idlist'] ?? [];   // if null, assign an empty array

               	if(empty($o_idlist)){
                       	echo "<p>ERROR. No matching protein found.</p>";
                       	return;
               	}

		$id_string = implode(", ", $o_idlist);
		$id_count = count($o_idlist);
		echo "<p>Total sequences found: <b>$id_count</b>";

		$fasta_sequence = '';
		$batches = array_chunk($o_idlist, 10);
		foreach ($batches as $batch){
			$ids = implode(',', $batch);  // Convert the batch to a comma-separated string of IDs
          		$ncbi_fetch = "https://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?db=protein&id=$ids&retmode=text&rettype=fasta&api_key=$ncbi_token";

            		// Fetch the FASTA sequence for the batch
          		$batch_fasta = file_get_contents($ncbi_fetch);
			$fasta_sequence .= $batch_fasta;

		}
		echo "</div>";
		displayFastaTable($fasta_sequence);

	}

	





//$formSubmitted = isset($_POST['button_proceed']);  // Check if the form was submitted

//to clear tmp folder before session ends
//echo "<p>session_id()</p>";
//register_shutdown_function('clearsessionfilesfromtmp', session_id());

echo <<<_TAIL

	<script type="text/javascript" src="https://bioinfmsc8.bio.ed.ac.uk/~s2704130/S2_IWD/ICA_Website_250318/website_dev/ica_script.js"></script>
</body>
<!-- user can save and come back to the page, cookies, ensure that can't be hacked (JS) -->
</html>
_TAIL;

?>
