/*
 * Copyright 2022 - Murena SAS - tous droits réservés
 */
/* global $ */
$(function() {
	$('#beta-form').submit(function(event) {
		event.preventDefault()

		$('#beta-form [type="submit"]').attr('disabled', true)
		const registerType = $('#beta').val()
		const url_ = OC.generateUrl('/apps/ecloud-accounts/beta/update')

		$.ajax({
			url: url_,
			method: (registerType === 'deregister') ? 'DELETE' : 'POST',
			success(result) {
				let message_ = ''
				if (result) {
					if (registerType === 'deregister') {
						message_ = t('ecloud-accounts', 'You\'ve successfully been removed from the beta users.')
					} else {
						message_ = t('ecloud-accounts', 'Congratulations! You\'ve successfully been added to the beta users.')
					}
					$('#message').addClass('alert-success')
				} else {
					message_ = t('ecloud-accounts', 'Something went wrong.')
					$('#message').addClass('alert-fail')
				}
				$('#message').html(message_)
				setTimeout(function() {
					window.location.reload()
				}, 2000)
			},
			error(request, msg, error) {
				$('#message').addClass('alert-fail')
				$('#message').html(t('ecloud-accounts', 'Something went wrong here.'))
			},
		})
	})

	$('#title, #description').on('input', function() {
		$(this).parent().find('.error-msg').remove()
	})

	$('#issue-submit-form').submit(function(event) {
		event.preventDefault()

		const url_ = OC.generateUrl('/apps/ecloud-accounts/issue/submit')
		$('.error-msg').remove()
		if ($('#title').val() === '' || $('#description').val() === '') {
			if ($('#title').val() === '') {
				$('#title').parent().append('<div class="error-msg color-red width300">' + t('ecloud-accounts', 'Title is mandatory.') + '</div>')
			}
			if ($('#description').val() === '') {
				$('#description').parent().append('<div class="error-msg color-red width300">' + t('ecloud-accounts', 'Description is mandatory.') + '</div>')
			}
			return
		}
		$('#issue-submit-form [type="submit"]').attr('disabled', true)

		$.ajax({
			url: url_,
			method: 'POST',
			data: $(this).serializeArray(),
			success(result) {
				let message_ = ''
				if (result) {
					message_ = t('ecloud-accounts', 'Issue submitted successfully.')
					$('#issue_message').addClass('alert-success')
				} else {
					message_ = t('ecloud-accounts', 'Something went wrong.')
					$('#issue_message').addClass('alert-fail')
				}
				$('#issue_message').html(message_)
				setTimeout(function() {
					window.location.reload()
				}, 2000)

			},
			error(request, msg, error) {
				$('#issue_message').addClass('alert-fail')
				$('#issue_message').html(t('ecloud-accounts', 'Something went wrong.'))
			},
		})
	})
})
