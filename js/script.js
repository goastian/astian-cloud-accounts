/*
 * Copyright 2019 - ECORP SAS - tous droits réservés
 */

$(function () {
	$('#isbForm').submit(function (event) {
		event.preventDefault();

		$('[type="submit"]').attr('disabled',true);
		let register_type = $('#beta').val();
		let url_ = OC.generateUrl('/apps/ecloud-accounts/beta/update');
		
		$.post(url_,
			{
				beta: register_type
			},
			function (result) {
				var message_ = '';
				if(result){
					if (register_type == 'deregister') {
						message_ = t('ecloud-accounts','You\'ve successfully been removed from the beta users.');
					} else {
						message_ = t('ecloud-accounts','Congratulations! You\'ve successfully been added to the beta users.');
					}
					$('#message').addClass('alert-success');
				}else{
					message_ = t('ecloud-accounts','Something went wrong.');
					$('#message').addClass('alert-fail');
				}
				$('#message').html(message_);
				setTimeout(function(){
					window.location.reload();
				}, 2000);
			});
	});
});
