/* global $ */
$(document).ready(function() {
	const elem = document.getElementById('delete-shop-account-settings')
	elem.addEventListener('disable-delete-account', function() {
		$('#deleteaccount').hide()
	})

	elem.addEventListener('enable-delete-account', function() {
		$('#deleteaccount').show()
	})
})
