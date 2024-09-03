document.addEventListener('DOMContentLoaded', function() {
	const googleContent = document.getElementById('google-content')

	const translationKey = "If you see a \"Google hasn't verified this app\" message you can bypass it by clicking \"Advanced\". We're currently working on passing the certification Google demands to get rid of this message."
	const translatedText = t('ecloud-accounts', translationKey)

	const newParagraph = document.createElement('p')
	newParagraph.textContent = '<svg width="21" height="20" viewBox="0 0 21 20" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="warning"><path id="Vector" d="M1.334 17.917h18.333L10.5 2.083 1.334 17.917zM11.334 15.417H9.667v-1.667h1.667v1.667zm0-3.334H9.667V8.75h1.667v3.333z" fill="#FFBB00"/></g></svg>' + translatedText

	googleContent.insertBefore(newParagraph, googleContent.querySelector('h3'))
})
