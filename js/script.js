/*
 * Copyright 2019 - ECORP SAS - tous droits réservés
 */

$(function() {
	$('#isbForm').submit(function(event) {
		event.preventDefault();
		if(!$("#agree").prop('checked')){
			alert('please agree terms & condition.');
			return;
		}
	  let register_type = $('#beta').val();
	  let url_ = OC.generateUrl('/apps/ecloud-accounts/api/groups/add');
	  if(register_type == 'deregister'){
		url_ = OC.generateUrl('/apps/ecloud-accounts/api/groups/remove')
	  }
	  
	  $.post(url_,
			{
				beta: register_type
			},
			function() {
				if(register_type == 'deregister'){
					alert('You\'ve successfully opt out from the beta users.');
				}else{
					alert('Congratulations! You\'ve successfully been added to the beta users.');
				}
				window.location.reload();
	  		});
	});
});
    