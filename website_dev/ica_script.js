function deleteSelectedRows(){
	event.preventDefault();		// prevent page from reloading
	var checkboxes = document.querySelectorAll('.delete-checkbox:checked');		//select all checkboxes w class delete-checkbox
        checkboxes.forEach(function(checkbox) {
        	var row = checkbox.closest('tr');  // get row w checkbox
        	row.remove();  // Remove
        });

        var deletedIds = Array.from(checkboxes).map(function(checkbox) {
        	return checkbox.getAttribute('data-sequence-id');	// get unique id for the row
        });

        if (deletedIds.length > 0) {	// send the deleted id to php via AJAX
        	var xhr = new XMLHttpRequest();
		var ica_tools = 'https://bioinfmsc8.bio.ed.ac.uk/~s2704130/S2_IWD/ICA_Website_250318/website_dev/ica_tools.php'
        	xhr.open('POST', ica_tools, true);
        	xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        	xhr.send('delete_ids=' + encodeURIComponent(deletedIds.join(',')));
        }
}

function proceedTool(event){
	event.preventDefault();
	document.getElementById('content-main').remove();
	var xhr = new XMLHttpRequest();		// create AJAX request
	var ica_tools = 'https://bioinfmsc8.bio.ed.ac.uk/~s2704130/S2_IWD/ICA_Website_250318/website_dev/ica_tools.php'
	xhr.open('POST', ica_tools, true);	//open POST request to the url, true = makes  request asynchronous (xblock execution)
	xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');	// send data as URL-encoded like form submissino
	xhr.send('button_proceed=1');	// send request (key-value pairs)
	xhr.onload = function() {
		if (xhr.status === 200){
			if (!xhr.responseText.includes('id="content-main"')){
				document.getElementById('button-fasta2').innerHTML = xhr.responseText;	//to display all connected to the button-fasta2
				console.log("Successful function: proceedTool()");
			} else{
				console.warn('Skipping reponse since it containst content-main')
			}
		} else{
			console.error("Error: ", xhr.status);
		}
	}
}

function runAnalysis(event){
        // Get all checked checkboxes
	event.preventDefault();
	console.log("JS window.runanalysis good");
        const selectedTools = [];
        document.querySelectorAll('input[type="checkbox"]:checked').forEach(checkbox => {selectedTools.push(checkbox.value);});
	console.log(selectedTools)

        var xhr = new XMLHttpRequest();         // create AJAX request
        var ica_tools = 'https://bioinfmsc8.bio.ed.ac.uk/~s2704130/S2_IWD/ICA_Website_250318/website_dev/ica_tools.php'
        xhr.open('POST', ica_tools, true);      //open POST request to the url, true = makes  request asynchronous (xblock execution)
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');      // send data as URL-encoded like form submissino
	xhr.send('button_run=1&tools=' + encodeURIComponent(JSON.stringify(selectedTools)));
        xhr.onload = function() {
                if (xhr.status === 200){
                        //if (!xhr.responseText.includes('id="content-main"')){
                	console.log("Running ClustalO...");
			document.getElementById('button-run').innerHTML = xhr.responseText;
                } else{
                        console.error("Error: ", xhr.status);
                }
	}
}


function downloadFile(filename, event) {
	event.preventDefault();
    	fetch('download.php?file=' + encodeURIComponent(filename))
        	.then(response => {
            		if (!response.ok) {
                		throw new Error('File not found');
            		}
            	return response.blob();
        })
        .then(blob => {
            	const a = document.createElement('a');
            	a.href = URL.createObjectURL(blob);
            	a.download = filename;
            	document.body.appendChild(a);
            	a.click();
            	document.body.removeChild(a);
        })
        .catch(error => alert(error.message));
}


