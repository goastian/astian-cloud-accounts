/* global $ */
$(document).ready(function() {
	// Disable initially
	$('#drop_account_confirm').prop('disabled', true)
	$('#deleteaccount').prop('disabled', true)

	const elem = document.getElementById('body-settings')
	elem.addEventListener('disable-delete-account', function() {
		$('#deleteaccount').prop('disabled', true)
		$('#drop_account_confirm').prop('disabled', true)
	})

	elem.addEventListener('enable-delete-account', function() {
		$('#drop_account_confirm').prop('disabled', false)
		const enableDeleteAccount = $('#drop_account_confirm').is(':checked')
		$('#deleteaccount').prop('disabled', !enableDeleteAccount)
	})
})