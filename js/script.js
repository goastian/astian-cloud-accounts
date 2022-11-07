/*
 * Copyright 2022 - Murena SAS - tous droits réservés
 */

$(function () {
	$('#beta-form').submit(function (event) {
		event.preventDefault();

		$('#beta-form [type="submit"]').attr('disabled', true);
		let register_type = $('#beta').val();
		let url_ = OC.generateUrl('/apps/ecloud-accounts/beta/update');

		$.ajax({
			url: url_,
			method: (register_type == 'deregister') ? 'DELETE' : 'POST',
			success: function (result) {
				var message_ = '';
				if (result) {
					if (register_type == 'deregister') {
						message_ = t('ecloud-accounts', 'You\'ve successfully been removed from the beta users.');
					} else {
						message_ = t('ecloud-accounts', 'Congratulations! You\'ve successfully been added to the beta users.');
					}
					$('#message').addClass('alert-success');
				} else {
					message_ = t('ecloud-accounts', 'Something went wrong.');
					$('#message').addClass('alert-fail');
				}
				$('#message').html(message_);
				setTimeout(function () {
					window.location.reload();
				}, 2000);
			},
			error: function (request, msg, error) {
				$('#message').addClass('alert-fail');
				$('#message').html(t('ecloud-accounts', 'Something went wrong here.'));
				setTimeout(function () {
					window.location.reload();
				}, 2000);
			}
		});
	});


	$('#issue-submit-form').submit(function (event) {
		event.preventDefault();

		let url_ = OC.generateUrl('/apps/ecloud-accounts/issue/submit');

		if ($('#title').val() == '' || $('#description').val() == '') {
			if ($('#title').val() == '') {
				$('#title').parent().append('<div class="color-red width300">'+t('ecloud-accounts', 'Title is mandatory.')+'</div>')
			}
			if ($('#description').val() == '') {
				$('#description').parent().append('<div class="color-red width300">'+t('ecloud-accounts', 'Description is mandatory.')+'</div>')
			}
			return;
		}
		$('#issue-submit-form [type="submit"]').attr('disabled', true);

		$.ajax({
			url: url_,
			method: 'POST',
			data: $(this).serializeArray(),
			success: function (result) {
				var message_ = '';
				if (result) {
					message_ = t('ecloud-accounts', 'Issue submitted successfully.');
					$('#issue_message').addClass('alert-success');
				} else {
					message_ = t('ecloud-accounts', 'Something went wrong.');
					$('#issue_message').addClass('alert-fail');
				}
				$('#issue_message').html(message_);
				setTimeout(function () {
					window.location.reload();
				}, 2000);

			},
			error: function (request, msg, error) {
				$('#issue_message').addClass('alert-fail');
				$('#issue_message').html(t('ecloud-accounts', 'Something went wrong.'));
				setTimeout(function () {
					window.location.reload();
				}, 2000);
			}
		});
	});
});
