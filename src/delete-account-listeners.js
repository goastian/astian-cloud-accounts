document.addEventListener('DOMContentLoaded', function() {
	// Disable initially
	document.getElementById('drop_account_confirm').disabled = true
	document.getElementById('deleteaccount').disabled = true

	const elem = document.getElementById('body-settings')
	elem.addEventListener('disable-delete-account', function() {
	  document.getElementById('deleteaccount').disabled = true
	  document.getElementById('drop_account_confirm').disabled = true
	})

	elem.addEventListener('enable-delete-account', function() {
	  document.getElementById('drop_account_confirm').disabled = false
	  const enableDeleteAccount = document.getElementById('drop_account_confirm').checked
	  document.getElementById('deleteaccount').disabled = !enableDeleteAccount
	})
})
