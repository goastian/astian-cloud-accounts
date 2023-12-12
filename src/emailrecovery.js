document.addEventListener('DOMContentLoaded', function() {
	const targetElement = document.getElementById('header')
	const newDiv = document.createElement('div')
	newDiv.className = 'recovery-email'
	newDiv.textContent = t('ecloud-accounts','Please set your recovery email address now and use your email account without restrictions.')
	const link = document.createElement('a')
	link.textContent =t('ecloud-accounts','SET RECOVERY EMAIL NOW')
	const rootUrl = OC.getRootPath()
	link.href = rootUrl + '/settings/user/security'
	link.style.display = 'block'
	newDiv.appendChild(link)
	targetElement.appendChild(newDiv)
})
