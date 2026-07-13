(function () {
	const selectIntervenant = document.querySelector('#admin-intervenant-select');
	if (!selectIntervenant) {
		return;
	}

	window.CCN = window.CCN || {};

	const savedIntervenant = localStorage.getItem('admin_intervenant_id');
	if (savedIntervenant) {
		selectIntervenant.value = savedIntervenant;
		CCN.intervenant_id = savedIntervenant;
	}

	selectIntervenant.addEventListener('change', (e) => {
		CCN.intervenant_id = e.target.value;
		localStorage.setItem('admin_intervenant_id', e.target.value);
	});
})();
