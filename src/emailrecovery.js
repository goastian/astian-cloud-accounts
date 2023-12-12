document.addEventListener('DOMContentLoaded', function() {
	const targetElement = document.getElementById('header')
	const newDiv = document.createElement('div')
	newDiv.className = 'recovery-email'
	newDiv.appendChild('<svg xmlns="http://www.w3.org/2000/svg" width="21" height="20" viewBox="0 0 21 20" fill="none"><path d="M1.3335 17.9167H19.6668L10.5002 2.08334L1.3335 17.9167ZM11.3335 15.4167H9.66683V13.75H11.3335V15.4167ZM11.3335 12.0833H9.66683V8.75001H11.3335V12.0833Z" fill="#FFBB00"/></svg>')
	newDiv.textContent = t('ecloud-accounts', 'Please set your recovery email address now and use your email account without restrictions.')
	const link = document.createElement('a')
	link.textContent = t('ecloud-accounts', 'SET RECOVERY EMAIL NOW')
	const rootUrl = OC.getRootPath()
	link.href = rootUrl + '/settings/user/security'
	link.style.display = 'block'
	newDiv.appendChild(link)
	const parentElement = targetElement.parentNode
	parentElement.insertBefore(newDiv, targetElement.nextSibling)
})
