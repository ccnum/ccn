class SpipSelect2Loader {

	static on_select = function() {
		let selector = 'select.select2';
		if (spipConfig?.select2?.selector) {
			selector += ', ' + spipConfig.select2.selector;
		}
		// Select2 avec quelques options en plus...
        document.querySelectorAll(selector).forEach(select => SpipSelect2.on_select(select));
	};

	static on_input = function() {
		document.querySelectorAll('input.select2').forEach(input => SpipSelect2.on_input(input));
	};

	static fix_focus = function () {
        jQuery(document).on('select2:open', () => {
			(list => list[list.length - 1])(document.querySelectorAll('.select2-container--open .select2-search__field')).focus()
		});
	};

	static onReady(fn) {
		if (document.readyState !== 'loading') {
			fn();
		} else {
			document.addEventListener('DOMContentLoaded', fn);
		}
	}

	static load() {
		SpipSelect2Loader.on_select();
		SpipSelect2Loader.on_input();
	}
}

SpipSelect2Loader.onReady(SpipSelect2Loader.load);
onAjaxLoad(SpipSelect2Loader.load);
SpipSelect2Loader.onReady(SpipSelect2Loader.fix_focus);
