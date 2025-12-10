class SpipSelect2 {
	// Helpers
	static helpers = {
		markMatch: function(text, term) {
			const match = text.toUpperCase().indexOf(term.toUpperCase());
			const result = document.createElement('span');

			if (match < 0) {
				result.textContent = text;
				return result;
			}

			const mark = document.createElement('span');
			mark.classList.add('select2-rendered__match');
			mark.textContent = text.substring(match, match + term.length);

			result.insertAdjacentText('afterbegin', text.substring(0, match));
			result.appendChild(mark)
			result.insertAdjacentText('beforeend', text.substring(match + term.length));
			return result;
		},
		transport: function (params, success, failure) {
			// utiliser l’ajax natif de jquery pour pas déclencher onAjaxLoad
			// à chaque appel à l’autocomplete ...
			const request = (typeof jQuery.spip.intercepted.ajax === "function")
				? jQuery.spip.intercepted.ajax(params)
				: jQuery.ajax(params);

			request.then(success);
			request.fail(failure);
			return request;
		}
	};

	/**
	 * Retourne true uniquement au premier chargement de ce node
	 * @param {node} node
	 * @returns bool
	 */
	static started(node) {
		if (node.dataset.select2On === "on") {
			return true;
		}
		node.dataset.select2On = "on";
		return false;
	}

    // Appel de Select2, en prenant en compte quelques options en plus
    // SpipSelect2.on_select(document.querySelector('select'), {...});
    static on_select = function(select, options = {}) {
		if (SpipSelect2.started(select)) {
			return;
		}

		// Éviter des onAjaxLoad() sur les hits autocomplete
		if (options.ajax?.url || select.dataset['ajax-Url'] || select.dataset['ajaxUrl']) {
			options = jQuery.extend(true, {
				ajax: {
					delay: 250, // can be overriden with data-ajax-delay="..."
					transport: SpipSelect2.helpers.transport
				}
			}, options);
		}

		// autoriser le html sur les textes retournés
		if (!options.templateResult) {
			options.templateResult = i => jQuery('<span>' + (i.long_text || i.text) + '</span>');
		}
		if (!options.templateSelection) {
			options.templateSelection = i => jQuery('<span>' + i.text + '</span>');
		}
		// indiquer qu’on crée un tag, pour différencier sur les événements ces actions
		if (!options.createTag) {
			options.createTag = params => {
				return {
					id: params.term,
					text: params.term,
					tag: true,
				};
			}
		};

		// sortAlpha : trier les <option> par ordre alphabétique
		if (options.sortAlpha || select.dataset['sortAlpha']) {
			options = jQuery.extend(true, {
				sorter: data => data.sort((a, b) => a.text.localeCompare(b.text))
			}, options);
		}

		// highlightSearchTerm : souligner les lettres recherchées dans les textes des <option>
		// Note: incompatible avec du HTML dans les textes de réponses
		if (options.highlightSearchTerm || select.dataset['highlightSearchTerm']) {
			options = jQuery.extend(true, options, {
				templateResult: item => {
					if (item.loading) {
						return item.text;
					}
					const term = document.querySelector('.select2-container--open .select2-search__field[aria-controls]').value;
					if (!term || !term.length) {
						return item.text;
					}
					return SpipSelect2.helpers.markMatch(item.text, term);
				}
			});
		}

		// déclarer des événements options.events = {'select2:open': function...; }
		// notamment utile depuis un la création via un input (où on ne connait pas le select)
		if (options.events !== undefined) {
			Object.entries(options.events).forEach(([eventKey, callback]) => {
				jQuery(select).on(eventKey, callback);
			});
		}

		jQuery(select).select2(options);
	};

	// Préparation de Select2, sur un input
	// On crée un <select> spécifique relié à l’input
	// SpipSelect2.on_input(document.querySelector('input.select2'), {...});
	static on_input = function (input, options = {}) {
		if (SpipSelect2.started(input)) {
			return;
		}

		const reword = input.outerHTML.replace('<input', '<select');
		const template = document.createElement('template');
		template.innerHTML = reword.trim();
		const select = template.content.firstChild;
		select.classList.remove('select2'); // ne pas être relancé sur le select sur un ajax
		select.removeAttribute('name'); // ne pas le poster
		delete select.dataset.select2On;
		select.setAttribute('multiple', true);
		if (select.hasAttribute('id')) {
			select.setAttribute('id', select.getAttribute('id').trim() + ':select2');
		}
		if (select.hasAttribute('placeholder')) {
			select.dataset.placeholder = select.getAttribute('placeholder');
			select.removeAttribute('placeholder');
		}
		if (!select.dataset.separator) {
			select.dataset.maximumSelectionLength = 1;
		}

		select.dataset.tags = true;
		select.dataset.onEnterSubmit = true;

		if (input.getAttribute('list')) {
			const datalist = document.querySelector('#' + input.getAttribute('list'));
			if (datalist) {
				select.innerHTML = datalist.innerHTML;
			}
		}

		if (input.value) {
			let values = [input.value];
			if (select.dataset.separator) {
				values = input.value.split(select.dataset.separator);
			}
			values.forEach(value => {
				const exists = select.querySelector("option[value='" + CSS.escape(value) + "']");
				if (exists) {
					exists.setAttribute('selected', true);
				} else {
					const option = document.createElement('option');
					let label = value;
					if (input.dataset.optionLabel) {
						try {
							const labels = JSON.parse(input.dataset.optionLabel);
							label = labels[value] || value;
						} catch (e) {
							console && console.error("Failed to parse data-option-label: " + e);
						}
					}
					option.textContent = label;
					option.setAttribute('value', value);
					option.setAttribute('selected', true);
					select.appendChild(option);
				}
			});
		}

        // Pour mettre à jour un input après une modification du select2 correspondant
        const update_input = function () {
            const data = Array.from(select.selectedOptions).map(option => option.value);
            input.value = data.join(select.dataset.separator || "");
            input.dispatchEvent(new Event('change'));
        };

		// on évite de gérer une délégation d’événement sur un node hors DOM
		options.events = {
			...{'change': update_input},
			...(options.events || {})
		};
		// select.setAttribute('onChange', 'this.spip_select2_update_input();')

		input.setAttribute('type', 'hidden');
		input.after(select);
        SpipSelect2.on_select(select, options);
	};

}

// document.querySelector('select').spip_select2()
HTMLElement.prototype.spip_select2 = function (options) {
    SpipSelect2.on_select(this, options);
}

;(() => {
	// @deprecated 2.0
	jQuery.spip.select2 = SpipSelect2.helpers;

	// Appel de .spip_select2(), via jQuery
	jQuery.fn.spip_select2 = function(options) {
        SpipSelect2.on_select(this.get(), options);
	};
})();
