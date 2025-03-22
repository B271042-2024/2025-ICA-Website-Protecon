
//console.log('Hi, I am before')

function deleteSelectedRows(){

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
