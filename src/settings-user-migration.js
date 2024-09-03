document.addEventListener('DOMContentLoaded', function() {
	const googleContent = document.getElementById('google-content')

	const translationKey = "If you see a \"Google hasn't verified this app\" message you can bypass it by clicking \"Advanced\". We're currently working on passing the certification Google demands to get rid of this message."
	const translatedText = t('ecloud-accounts', translationKey)

	// eslint-disable-next-line no-console
	console.log('Translated text:', translatedText)

	const newParagraph = document.createElement('p')
	newParagraph.textContent = '⚠️ ' + translatedText

	googleContent.insertBefore(newParagraph, googleContent.querySelector('h3'))
})
