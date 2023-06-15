document.addEventListener('DOMContentLoaded', function() {
	const checkboxSelector = '#delete-account-settings .checkbox-radio-switch__input'
	const buttonSelector = '#delete-account-settings .delete-button-wrapper .button-vue'
	const checkboxSpanSelector = '#delete-account-settings span.checkbox-radio-switch'
	const disabledClass = 'checkbox-radio-switch--disabled'
	// Disable initially
	document.querySelector(checkboxSelector).disabled = true
	document.querySelector(buttonSelector).disabled = true

	const elem = document.getElementById('body-settings')
	elem.addEventListener('disable-delete-account', function() {
	  document.querySelector(checkboxSelector).disabled = true
	  document.querySelector(buttonSelector).disabled = true
	  document.querySelector(checkboxSpanSelector).classList.add(disabledClass)
	})

	elem.addEventListener('enable-delete-account', function() {
	  document.querySelector(checkboxSelector).disabled = false
	  const enableDeleteAccount = document.querySelector(checkboxSelector).checked
	  document.querySelector(buttonSelector).disabled = !enableDeleteAccount
	  document.querySelector(checkboxSpanSelector).classList.remove(disabledClass)
	})
})
