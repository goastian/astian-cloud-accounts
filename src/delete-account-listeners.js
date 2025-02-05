document.addEventListener('DOMContentLoaded', function() {
	const checkboxSelector = '#delete-account-settings .checkbox-radio-switch__input'
	const buttonSelector = '#delete-account-settings .delete-button-wrapper .button-vue'
	const checkboxSpanSelector = '#delete-account-settings span.checkbox-radio-switch'
	const disabledClass = 'checkbox-radio-switch--disabled'
	const deleteAccountSectionId = '#delete-account-settings-section'
	const deleteMyAccountLoader = '#delete-my-account-loader'

	// Disable initially
	document.querySelector(checkboxSelector).disabled = true
	document.querySelector(buttonSelector).disabled = true
	document.querySelector(checkboxSpanSelector).classList.add(disabledClass)

	const elem = document.getElementById('body-settings')
	document.querySelector(deleteMyAccountLoader).style.display = 'block'
	elem.addEventListener('disable-delete-account', function() {
	  document.querySelector(checkboxSelector).disabled = true
	  document.querySelector(buttonSelector).disabled = true
	  document.querySelector(checkboxSpanSelector).classList.add(disabledClass)
	  document.querySelector(deleteMyAccountLoader).style.display = 'block'
	  document.querySelector(deleteAccountSectionId).style.visibility = 'hidden'
	})

	elem.addEventListener('enable-delete-account', function() {
	  document.querySelector(checkboxSelector).disabled = false
	  const enableDeleteAccount = document.querySelector(checkboxSelector).checked
	  document.querySelector(buttonSelector).disabled = !enableDeleteAccount
	  document.querySelector(checkboxSpanSelector).classList.remove(disabledClass)
	  document.querySelector(deleteMyAccountLoader).style.display = 'none'
	  document.querySelector(deleteAccountSectionId).style.visibility = 'visible'
	})
})
