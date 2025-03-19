

//src: ica_tools.html

function fetchProtein(){
	var protein = document.getElementById('input1').value.trim();
	var taxon = document.getElementById('input2').value.trim();
	var div_output1 = document.querySelector('.output1');

	if (protein === "" || taxon === ""){
		div_output1.style.display = "none";
		alert("Failed. Please enter both Protein Name and Taxonomic Group.");
		return;
	}
	else{
		document.getElementById("web_output1").innerHTML = `<b>Protein:</b> ${protein} <br> <b>Taxonomic Group:</b> ${taxon}`;
		div_output1.style.display = "block";
	}
}
