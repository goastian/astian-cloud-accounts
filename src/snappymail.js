document.addEventListener('DOMContentLoaded', function() {
	const targetElement = document.getElementById('rl-app')
	const newDiv = document.createElement('div')
	newDiv.className = 'recovery-email'
	newDiv.textContent = 'Please set your recovery email address now and use your email account without restrictions.'
	const link = document.createElement('a')
	link.textContent = 'SET RECOVERY EMAIL NOW'
	var rootUrl = OC.getRootPath()
	link.href = rootURL+'settings/user/security'
	link.style.display = 'block'
	newDiv.appendChild(link)
	targetElement.insertBefore(newDiv, targetElement.firstChild)
})
