document.addEventListener('DOMContentLoaded', function() {
	const APPLICATION_NAME = 'ecloud-accounts'
	const targetElement = document.getElementById('header')
	const newDiv = document.createElement('div')
	newDiv.className = 'recovery-email'
	const img = document.createElement('img')
	img.src = OC.generateUrl('/custom_apps/' + APPLICATION_NAME + '/img/warning.svg')
	newDiv.appendChild(img)
	const textNode = document.createTextNode(t(APPLICATION_NAME, 'Please set your recovery email address to use your email account without restrictions.'))
    newDiv.appendChild(textNode)
	const link = document.createElement('a')
	link.textContent = t(APPLICATION_NAME, 'SET RECOVERY EMAIL NOW')
	const rootUrl = OC.getRootPath()
	link.href = rootUrl + '/settings/user/security'
	link.style.display = 'block'
	newDiv.appendChild(link)
	const parentElement = targetElement.parentNode
	parentElement.insertBefore(newDiv, targetElement.nextSibling)
})
