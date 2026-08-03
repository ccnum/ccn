/**
 * Affiche un tooltip "Fermer la modale" qui suit le curseur au survol de
 * "#sidebarCache" (le voile derrière la sidebar en plein écran), en évitant
 * de déborder de la fenêtre. Ne fait rien si les éléments sont absents du DOM.
 */
function activateSidebarCacheTooltip() {
	const tooltip = document.getElementById("tooltip-sidebarCache");
	const sidebarCache = document.getElementById("sidebarCache");

	if (!tooltip || !sidebarCache) return;

	sidebarCache.addEventListener("mousemove", function(e) {
		tooltip.style.display = "block";
		tooltip.textContent = "Fermer la modale";

		const offset = 12;
		let x = e.clientX + offset;
		let y = e.clientY + offset;

		const width = tooltip.offsetWidth;
		const height = tooltip.offsetHeight;

		if (x + width > window.innerWidth) {
			x = e.clientX - width - offset;
		}

		if (y + height > window.innerHeight) {
			y = e.clientY - height - offset;
		}

		tooltip.style.left = x + "px";
		tooltip.style.top = y + "px";
	});

	sidebarCache.addEventListener("mouseleave", function() {
		tooltip.style.display = "none";
	});
}

if (document.readyState === "loading") {
	document.addEventListener("DOMContentLoaded", activateSidebarCacheTooltip);
} else {
	activateSidebarCacheTooltip();
}
