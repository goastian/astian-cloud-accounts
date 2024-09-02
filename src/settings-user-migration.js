document.addEventListener('DOMContentLoaded', function() {
	// Select the #google-content element
	const googleContent = document.getElementById('google-content')

	// Create a new p element
	const newParagraph = document.createElement('p')
	newParagraph.textContent = '⚠️ ' + t('If you see a "Google hasn\'t verified this app" message you can bypass it by clicking "Advanced". We\'re currently working on passing the certification Google demands to get rid of this message.')

	// Insert the p element before the h3 element
	googleContent.insertBefore(newParagraph, googleContent.querySelector('h3'))

})
