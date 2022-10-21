/*
 * Copyright 2019 - ECORP SAS - tous droits réservés
 */

$(function () {
	$('#isbForm').submit(function (event) {
		event.preventDefault();

		let register_type = $('#beta').val();
		let url_ = OC.generateUrl('/apps/ecloud-accounts/api/groups/user');
		if (register_type == 'deregister') {
			if (!confirm(t('ecloud-accounts',"Are you sure you want to opt out of beta features?"))) {
				return;
			}
		} else {
			if (!$("#agree").prop('checked')) {
				alert(t('ecloud-accounts','Please agree terms & conditions.'));
				return;
			}
		}

		$.post(url_,
			{
				beta: register_type
			},
			function (result) {
				console.log(result);
				if(result){
					if (register_type == 'deregister') {
						alert(t('ecloud-accounts','You\'ve successfully opt out from the beta users.'));
					} else {
						alert(t('ecloud-accounts','Congratulations! You\'ve successfully been added to the beta users.'));
					}	
				}else{
					alert(t('ecloud-accounts','Something went wrong.'));
				}
				window.location.reload();
			});
	});
});
