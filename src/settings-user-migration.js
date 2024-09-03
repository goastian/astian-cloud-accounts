import { generateUrl } from '@nextcloud/router'

document.addEventListener('DOMContentLoaded', function() {
	const googleContent = document.getElementById('google-content')

	const translationKey = "If you see a \"Google hasn't verified this app\" message you can bypass it by clicking \"Advanced\". We're currently working on passing the certification Google demands to get rid of this message."
	const translatedText = t('ecloud-accounts', translationKey)
	const img = createImageElement('email-recovery')

	const newParagraph = document.createElement('p')
	// Add the img element to the paragraph
	newParagraph.appendChild(img)

	const textNode = document.createTextNode(' ' + translatedText)
	newParagraph.appendChild(textNode)

	googleContent.insertBefore(newParagraph, googleContent.querySelector('h3'))
})

/**
 *
 * @param appName
 * @return {HTMLImageElement}
 */
function createImageElement(appName) {
	const img = document.createElement('img')
	img.src = generateUrl('/custom_apps/' + appName + '/img/warning.svg')
	img.alt = 'Warning'
	img.style.verticalAlign = 'middle'
	return img
}
