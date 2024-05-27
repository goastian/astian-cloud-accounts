import { generateUrl } from '@nextcloud/router'
import { loadState } from '@nextcloud/initial-state'

const userLocation = loadState('ecloud-accounts', 'userLocation')
const APPLICATION_NAME = 'ecloud-accounts'
document.addEventListener('DOMContentLoaded', function() {
	if (!localStorage.getItem('bannerClosed')) {
		const newDiv = createNewDiv('business-banner')
		const contentDiv = document.createElement('div')
		contentDiv.id = 'business-banner-container'
		if (userLocation === 'USA') {
			const img = createImageElement(APPLICATION_NAME)
			contentDiv.appendChild(img)
		}
		const textNode = createTextNode(APPLICATION_NAME)
		const link = createLinkElement(APPLICATION_NAME)
		const closeButton = createCloseButton(newDiv)

		contentDiv.appendChild(textNode)
		newDiv.appendChild(contentDiv)
		newDiv.appendChild(link)
		newDiv.appendChild(closeButton)
		insertIntoDOM(newDiv)
		// Measure the height after the element is inserted into the DOM
		const banner = document.getElementById('business-banner')
		if (banner) {
			const bannerHeight = banner.clientHeight + 'px'
			const topHeight = (banner.clientHeight + 50) + 'px'
			setTopStyle('#header', bannerHeight)
			setMarginTopAndHeight('#content', topHeight)
			setMarginTopAndHeight('#content-vue', topHeight)
			setTopStyleWhenElementAvailable('#header-menu-user-menu', topHeight)
			setTopStyleWhenElementAvailable('#header-menu-notifications', topHeight)
			setTopStyle('#header-menu-unified-search', topHeight)
			banner.style.height = bannerHeight
		}
	}
})

/**
 * Sets the 'top' style to an element once it becomes available in the DOM.
 *
 * @param {string} selector - The CSS selector for the element.
 * @param {string} topValue - The value to be set for the 'top' property.
 */

/**
 *
 * @param selector
 * @param topValue
 */
function setTopStyleWhenElementAvailable(selector, topValue) {
	// Function to check each node and apply style if it matches the selector
	/**
	 *
	 * @param node
	 */
	function checkAndApplyStyle(node) {
		if (node.nodeType === Node.ELEMENT_NODE) {
			if (node.matches(selector)) {
				node.style.top = topValue
			}

			// Check all child nodes
			node.querySelectorAll(selector).forEach(childNode => {
				childNode.style.top = topValue
			})
		}
	}

	// Set up a MutationObserver to watch for added nodes
	const observer = new MutationObserver(mutations => {
		mutations.forEach(mutation => {
			mutation.addedNodes.forEach(checkAndApplyStyle)
		})
	})

	// Start observing the document body for added nodes
	observer.observe(document.body, { childList: true, subtree: true })
}

/**
 * Sets the 'top' style property of an element.
 * The element is selected based on the provided CSS selector.
 *
 * @param {string}  selector
 * @param {string}  topValue
 */
function setTopStyle(selector, topValue) {
	const element = document.querySelector(selector)
	if (element) {
		element.style.top = topValue
	}
}

/**
 * Apply a margin-top style with !important and calculate a new height for the element.
 *
 * @param {string} selector - The CSS selector for the element.
 * @param {string} topValue - The value for the margin-top property.
 */

/**
 *
 * @param selector
 * @param topValue
 */
function setMarginTopAndHeight(selector, topValue) {
	const element = document.querySelector(selector)
	if (element) {
		element.style.cssText += `margin-top: ${topValue} !important;`
		const heightValue = `calc(100% - env(safe-area-inset-bottom) - ${topValue} - var(--body-container-margin)) !important`
		element.style.cssText += `height: ${heightValue};`
	}
}
/**
 *
 * @param className
 */
function createNewDiv(className) {
	const div = document.createElement('div')
	div.className = className
	div.id = className
	return div
}

/**
 *
 * @param appName
 */
function createImageElement(appName) {
	const img = document.createElement('img')
	img.src = generateUrl('/custom_apps/' + appName + '/img/crowdcube.png')
	return img
}

/**
 *
 * @param appName
 */
function createTextNode(appName) {
	const p = document.createElement('p')
	const labelText = t(appName, 'Own a Part of Murena!')
	const text = document.createTextNode(labelText)
	p.appendChild(text)
	return p
}

/**
 *
 * @param appName
 */
function createLinkElement(appName) {
	const link = document.createElement('a')
	const labelText = t(appName, 'LEARN MORE')
	link.textContent = labelText
	link.href = 'https://murena.com/investors/'
	link.style.display = 'block'
	return link
}
/**
 *
 * @param appName
 * @param banner
 */
function createCloseButton(banner) {
	const span = document.createElement('span')
	const labelText = 'X'
	span.textContent = labelText
	span.style.display = 'block'
	span.style.cursor = 'pointer'
	span.addEventListener('click', function() {
		banner.style.display = 'none'
		localStorage.setItem('bannerClosed', 'true')
		const bannerHeight = '0'
		const topHeight = 'auto'
		setTopStyle('#header', bannerHeight)
		setMarginTopAndHeight('#content', topHeight)
		setMarginTopAndHeight('#content-vue', topHeight)
		setTopStyleWhenElementAvailable('#header-menu-user-menu', topHeight)
		setTopStyleWhenElementAvailable('#header-menu-notifications', topHeight)
		setTopStyle('#header-menu-unified-search', topHeight)
		banner.style.height = bannerHeight
	})
	return span
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
