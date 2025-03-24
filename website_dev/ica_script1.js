
//console.log('Hi, I am before')

function deleteSelectedRows(){
	event.preventDefault();
//	console.log('JS i am');
	var checkboxes = document.querySelectorAll('.delete-checkbox:checked');
        checkboxes.forEach(function(checkbox) {
        	var row = checkbox.closest('tr');  // Get the row that contains the checkbox
        	row.remove();  // Remove the row from the table
        });

        var deletedIds = Array.from(checkboxes).map(function(checkbox) {
        	return checkbox.getAttribute('data-sequence-id');
        });

        if (deletedIds.length > 0) {
        	// Send the deleted IDs to PHP via AJAX for server-side deletion
        	var xhr = new XMLHttpRequest();
		var pg_tools = 'https://bioinfmsc8.bio.ed.ac.uk/~s2704130/S2_IWD/ICA_Website_250318/website_dev/ica_tools.php'
        	xhr.open('POST', pg_tools, true);
        	xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        	xhr.send('delete_ids=' + encodeURIComponent(deletedIds.join(',')));
        }
}


// reload the page, clear up the window and insert new items
document.addEventListener('DOMContentLoaded', function(){
	document.querySelector('button[name="button4"]').addEventListener('click', function(event){
        	event.preventDefault()
        	document.getElementById('content-main').innerHTML = '';
		document.getElementById('output-seqdetails').innerHTML = '';
		document.getElementById('table-fasta').innerHTML = '';
		document.getElementById('button-fasta1').innerHTML = '';
		document.getElementById('button-fasta2').innerHTML = '';

		const newContent = `
			<p style='font: 18px Arial, sans-serif;'><b>Select from the following:</b></p>
			<table class="JS-Tool-table">
                                <tr><th>Tools</th><th>Description</th><th>Select</th><th>Download</th></tr>
                                <tr><td>ClustalO</td><td>For protein alignment</td><td><input type='checkbox' class='select-tools checkie' name='selecttools[]' value='ClustalO'></td><td></td></tr>
                               	<tr><td>EMBOSS: patmatmotifs</td><td>Use PROSITE database to search for motifs</td><td><input type='checkbox' class='select-tools checkie' name='selecttools[]' value='embossPatmatmotifs'></td><td></td></tr>
                                <tr><td>EMBOSS: plotcon</td><td>To generate protein conservation plot</td><td><input type='checkbox' class='select-tools checkie' name='selecttools[]' value='embossPlotcon'></td><td></td></tr>
                                <tr><td>NGL Viewer</td><td>To view 3D protein conservation</td><td><input type='checkbox' class='select-tools checkie' name='selecttools[]' value='nglviewer'></td><td></td></tr>
            		</table>
			<br>
			<button class ='JS-Tool-button' type='button' id='button-run' onclick='runAnalysis()'>Run</button>
		`;
		document.body.innerHTML = newContent;
	})
})


//AJAX send back to ica_tools.php
window.runAnalysis = function(){
        // Get all checked checkboxes
	console.log("JS window.runanalysis good");
        const selectedTools = [];
        document.querySelectorAll('input[type="checkbox"]:checked').forEach(checkbox => {selectedTools.push(checkbox.value);});

        // Send checkboxes to the server via AJAX
        fetch('https://bioinfmsc8.bio.ed.ac.uk/~s2704130/S2_IWD/ICA_Website_250318/website_dev/ica_tools.php', {
        	method: 'POST',
        	headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        	body: 'action=run_analysis&tools=' + encodeURIComponent(JSON.stringify(selectedTools))
        })
        .then(response => response.text())
        .then(data => {
		console.log('Server response:', data)
		const outputclustalo = document.getElementById('clustalo-output');

		if (outputclustalo){
			outputclustalo.innerHTML = data;
		}else{
			console.error("Div id='clustalo-output' was not found.");
        		const newDiv = document.createElement('div');
        		newDiv.id = 'clustalo-output';
        		newDiv.innerHTML = data;
        		document.body.appendChild(newDiv)
		}
	})
        .catch(error => {console.error('Error:', error)});
	console.log("JS till the end")
}
