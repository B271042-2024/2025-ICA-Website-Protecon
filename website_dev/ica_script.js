

//src: ica_tools.html

function fetchProtein(){
	var proteinName = document.getElementById('input1').value;
	var taxonGroup = document.getElementById('input2').value;

	if (proteinName == "" || taxonGroup == ""){
		alert("Failed. Please enter both Protein Name and Taxonomic Group.");
		return;
	}

}
