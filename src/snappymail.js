document.addEventListener('DOMContentLoaded', function() {
	var targetElement = document.getElementById("rl-app");
    var newDiv = document.createElement("div");
    newDiv.textContent = "Please verify your recovery address";
    targetElement.insertBefore(newDiv, targetElement.firstChild);
})
