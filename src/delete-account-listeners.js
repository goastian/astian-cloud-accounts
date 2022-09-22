/* global $ */
$(document).ready(function() {
	const elem = document.getElementById('body-settings')
	elem.addEventListener('disable-delete-account', function() {
		$('#deleteaccount').hide()
	})

	elem.addEventListener('enable-delete-account', function() {
		$('#deleteaccount').show()
	})
})
