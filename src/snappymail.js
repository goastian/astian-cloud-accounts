document.addEventListener('DOMContentLoaded', function() {
	const targetElement = document.getElementById('rl-app')
	const newDiv = document.createElement('div')
	newDiv.textContent = 'Please verify your recovery address'
	targetElement.insertBefore(newDiv, targetElement.firstChild)
})
