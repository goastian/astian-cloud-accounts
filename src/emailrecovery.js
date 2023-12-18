import { generateUrl } from '@nextcloud/router'
const APPLICATION_NAME = 'ecloud-accounts'
document.addEventListener('DOMContentLoaded', function() {
	const newDiv = createNewDiv('recovery-email')
	const img = createImageElement(APPLICATION_NAME)
	const textNode = createTextNode(APPLICATION_NAME)
	const link = createLinkElement(APPLICATION_NAME)

	newDiv.appendChild(img)
	newDiv.appendChild(textNode)
	newDiv.appendChild(link)

	insertIntoDOM(newDiv)
})

/**
 *
 * @param className
 */
function createNewDiv(className) {
	const div = document.createElement('div')
	div.className = className
	return div
}

/**
 *
 * @param appName
 */
function createImageElement(appName) {
	const img = document.createElement('img')
	img.src = generateUrl('/custom_apps/' + appName + '/img/warning.svg')
	return img
}

/**
 *
 * @param appName
 */
function createTextNode(appName) {
	const p = document.createElement('p')
	const text = document.createTextNode(t(appName, 'Please set your recovery email address to use your email account without restrictions.'))
	p.appendChild(text)
	return p
}

/**
 *
 * @param appName
 */
function createLinkElement(appName) {
	const link = document.createElement('a')
	link.textContent = t(appName, 'SET RECOVERY EMAIL NOW')
	link.href = OC.getRootPath() + '/settings/user/security#recovery-email-div'
	link.style.display = 'block'
	return link
}

/**
 *
 * @param element
 */
function insertIntoDOM(element) {
	const targetElement = document.getElementById('header')
	const parentElement = targetElement.parentNode
	parentElement.insertBefore(element, targetElement)
}
