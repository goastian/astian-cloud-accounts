document.addEventListener('DOMContentLoaded', function() {
	let targetElement = document.getElementById("rl-app")
    let newDiv = document.createElement("div")
    newDiv.textContent = "Please verify your recovery address"
    targetElement.insertBefore(newDiv, targetElement.firstChild)
})
