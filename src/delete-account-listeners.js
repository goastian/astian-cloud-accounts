/* global $ */
$(document).ready(function() {
	const elem = document.getElementById('body-settings')
	elem.addEventListener('disable-delete-account', function() {
		$('#deleteaccount').prop('disabled', true)
		$('#drop_account_confirm').prop('disabled', true)
	})

	elem.addEventListener('enable-delete-account', function() {
		$('#deleteaccount').prop('disabled', false)
		$('#drop_account_confirm').prop('disabled', false)
	})
})
