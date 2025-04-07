$(function(){
	afficher_si_init();
	onAjaxLoad(afficher_si_init);
});
afficher_si_current_data = '';
function afficher_si_init() {
	$('form:not([data-afficher_si-init])').each(function(){
		// Seulement si au moins un afficher_si dedans !
		if (this.hasAttribute('data-resume_etapes_futures')) {
			var resume_etapes_futures = this.getAttribute('data-resume_etapes_futures');
		} else {
			var resume_etapes_futures = '';
		}
		if ($(this).find('[data-afficher_si]').length !== 0 || resume_etapes_futures) {
			form = $(this);
			form.find('.formulaire_spip__etapes').each(function() {
				$(this).css('min-height', $(this).height());
			});
			afficher_si_init_chemin_etapes(form);

			afficher_si_set_current_data(form);
			form.find('[data-afficher_si]').each(function(){
				condition = verifier_afficher_si($(this), true);
				animer_afficher_si($(this), condition, true);
			}
			);
			afficher_si_set_etapes_presentation_courante(form);
			afficher_si_set_etape_suivante(form);

			// Un écouteur sur les champs qui conditionent d'autres champs
			$(this).find('textarea, input, select').each(function () {
				var name = $(this).attr('name');
				if (name) {
					name = afficher_si_normaliser_name(name);
					if (form.find('[data-afficher_si*=\''+name+'\']').length || resume_etapes_futures.includes(name)) {
						$(this).on('input change', function() {
							afficher_si_onchange($(this));
						});
					}
				}
			}
			);
			$(this).attr('data-afficher_si-init', true);
		}
	})
}

/**
 * Normaliser les names
 * @param string name
 * @return return name
**/
function afficher_si_normaliser_name(name) {
	var name = name.replace('[]', '');
	var name = name.replace('_choix_alternatif', '');
	return name;
}
/**
 * Ecouteur sur les champ
 * @param jQuery Object $champ
**/
function afficher_si_onchange($champ) {
	// Seulement si ce champ a un name
	if (name = $champ.attr('name')) {
		var form = $champ.parents('form');
		var name = afficher_si_normaliser_name(name);
		afficher_si_set_current_data(form);

		// Si un autre champ utilise celui-ci dans une condition
		form.find('[data-afficher_si*=\''+name+'\']').each(function(){
			condition = verifier_afficher_si($(this));
			animer_afficher_si($(this), condition);
		})
		afficher_si_set_etapes_presentation_courante(form, name);
		afficher_si_set_etape_suivante(form, name);
	}
}
function afficher_si_set_current_data(form) {
	current_data = form.serializeArray();//Le format de retour n'est pas simple, on transforme en tableau associatif
	afficher_si_current_data = [];
	$(current_data).each(function() {
		if (this.name.includes('[]')) {
			this.name	= this.name.replace('[]', '');
			if (Array.isArray(afficher_si_current_data[this.name])) {
				afficher_si_current_data[this.name].push(this.value)
			} else {
				afficher_si_current_data[this.name] = [this.value];
			}
		} else {
			afficher_si_current_data[this.name] = this.value;
		}
	 if (afficher_si_current_data[this.name] === '@choix_alternatif') {
		 afficher_si_current_data[this.name] = form.find('[name = "' + this.name + '_choix_alternatif' + '"]').val();
		 afficher_si_current_data[this.name + ':choix_alternatif'] = true;// Usage interne, pas de stabilité de l'API
	 } else {
		 afficher_si_current_data[this.name + ':choix_alternatif'] = false;// Usage interne, pas de stabilité de l'API
	 }
	});
}
function verifier_afficher_si(saisie, chargement = false) {
	let condition = saisie.attr('data-afficher_si');
	condition = eval(condition);
	if (
		chargement
		&& !condition
		&& (saisie.hasClass('erreur') || $('.erreur', saisie).length)
		&& !saisie.hasClass('saisies-menu-onglets__item')
		&& !saisie.hasClass('saisies-contenu-onglet')
	) {//Tjr afficher au chargement s'il y  une erreur. Si cela arrive c'est qu'il y a quelque part une incohérence entre l'évaluation JS et l'évaluation PHP des afficher si. Attention ! Ne pas appliquer aux onglets qui ont des classes erreurs, ca c'est volontaire
		console.log('Attention : saisies masquée par afficher_si avec une erreur...' + saisie.attr('data-id'));
		return true;
	}
	return condition
}
function animer_afficher_si(saisie, condition, chargement){
	if (condition) {
		if (!saisie.hasClass('afficher_si_visible')) {
			saisie.trigger('afficher_si_visible_pre');
			saisie.removeClass('afficher_si_masque_chargement').removeClass('afficher_si_masque').addClass('afficher_si_visible').removeAttr('aria-hiden');
			if (!saisie.hasClass('afficher_si_sans_visuel')) {
				afficher_si_show(saisie);
			}
			afficher_si_restaure_validation(saisie);
			saisie.trigger('afficher_si_visible_post');
		}
	} else {
		if (!saisie.hasClass('afficher_si_masque')) {
			saisie.trigger('afficher_si_masque_pre');
			if (!saisie.hasClass('afficher_si_sans_visuel')) {
				afficher_si_hide(saisie);
			}
			if (chargement) {
				saisie.addClass('afficher_si_masque_chargement');
			}
			saisie.addClass('afficher_si_masque').removeClass('afficher_si_visible').attr('aria-hiden', true);
			afficher_si_disable_validation(saisie);
			saisie.trigger('afficher_si_masque_post');
		}
	}
}
/**
 * Prend les attribut de validation au sein d'une saisie et les supprime
 * tout en stockant en mémoire dans des data-truc
 * @param $saisie la saisie (conteneur)
**/
function afficher_si_disable_validation($saisie) {
	for (attribut of afficher_si_validation_attributs) {
		if (attribut in afficher_si_validation_attributs_valeurs) {
			var selecteur = afficher_si_validation_attributs_valeurs[attribut].map((x) => '[' + attribut + '=' + x + ']').join(', ');
		} else {
			var selecteur = '[' + attribut + ']';
		}
		var enfants = $saisie.find(selecteur);
		enfants.each(function() {
			$(this).attr('data-afficher_si-' + attribut, $(this).attr(attribut)).attr(attribut, null);
		});
	}
}

/**
 * Rétabli si besoin les attribut de validation au sein d'une saisie
 * @param $saisie la saisie (conteneur)
**/
function afficher_si_restaure_validation($saisie) {
	for (attribut of afficher_si_validation_attributs) {
		var enfants = $saisie.find('[data-afficher_si-' + attribut + ']');
		enfants.each(function() {
			if ($(this).parents('.afficher_si_masque').length == 0) {
				$(this).attr(attribut, $(this).attr('data-afficher_si-' + attribut)).attr('data-afficher_si-' + attribut, null);
			}
		});
	}
}


var afficher_si_validation_attributs = [
	'required',
	'minlength',
	'maxlength',
	'min',
	'max',
	'type',
	'step',
	'pattern'
];
var afficher_si_validation_attributs_valeurs = {'type': ['email', 'url']};
// Ref https://developer.mozilla.org/en-US/docs/Web/HTML/Constraint_validation#validation-related_attributes


function afficher_si(args) {
	if (afficher_si_current_data.hasOwnProperty(args['champ'])) {
		valeur_champ = afficher_si_current_data[args['champ']];
	} else {
		valeur_champ = '';
	}
	valeur = args['valeur'];
	if (!('modificateur' in args)) {
		args['modificateur'] = {'fonction' : ''};
	}

	// Compat historique == > IN pour données tabulaires !
	if (
		Array.isArray(valeur_champ)
		&& (args['modificateur']['fonction'] !== 'total')
	) {
		if (args['operateur'] == '==') {
			args['operateur'] = 'IN';
		} else if(args['operateur'] == '!=') {
			args['operateur'] = '!IN';
		}
	}

	// Si on vérifie un modificateur 'total' est à appliquer sur la valeur du champ de saisie
	if (args['modificateur']['fonction'] === 'total') {
		if (Array.isArray(valeur_champ)) {
			valeur_champ = valeur_champ.length;
		} else {
			valeur_champ = 0;
		}
	}

	// Si on vérifie un modificateur 'substr' est à appliquer sur la valeur du champ de saisie
	if (args['modificateur']['fonction'] === 'substr') {
		// A priori inutile de tester le type car c'est déjà fait dans la fonction PHP
		if (args['modificateur']['arguments'][1]) {
			valeur_champ = valeur_champ.substr(args['modificateur']['arguments'][0], args['modificateur']['arguments'][1]);
		} else {
			valeur_champ = valeur_champ.substr(args['modificateur']['arguments'][0]);
		}
	}

	// Transformation en tableau des valeurs et valeur_champ, si IN/!IN
	if (args['operateur'] == 'IN' || args['operateur'] == '!IN') {
		valeur = valeur.split(',');
		if (!Array.isArray(valeur_champ)) {
			if (valeur_champ) {
				valeur_champ = [valeur_champ];
			} else {
				valeur_champ = [];
			}
		}
	}

	// Transformation en entier des valeurs et valeur_champ, si opérateur de comparaison
	if (['<', '<=', '>=', '>'].includes(args['operateur'])) {
		valeur = Number(valeur);
		valeur_champ = Number(valeur_champ);
	}

	// Et maintenant les test
	switch (args['operateur']) {
		case '==':
			return valeur_champ == valeur;
		case '!=':
			return valeur_champ != valeur;
		case '>':
			return valeur_champ > valeur;
		case '>=':
			return valeur_champ >= valeur;
		case '<':
			return valeur_champ < valeur;
		case '<=':
			return valeur_champ <= valeur;
		case 'MATCH':
			return RegExp(valeur, args.regexp_modif).test(valeur_champ);
		case '!MATCH':
			return !RegExp(valeur, args.regexp_modif).test(valeur_champ);
		case 'IN':
			return $(valeur).filter(valeur_champ).length ? true : false;
		case '!IN':
			return $(valeur).filter(valeur_champ).length ? false : true;
		default:
			return valeur_champ ? true : false;
	}
}


//Pour l'affichage des étapes selon la présentation "étape courante" seulement
//@param form, le formulaire
//@param name le nom de la saisie dont la valeur vient juste de changer
function afficher_si_set_etapes_presentation_courante(form, name='') {
	var etapes = afficher_si_parse_data_etapes_futures(form);
	if (!etapes) {
		return;
	}
	form.find('[data-etapes_max]').each(function() {
		var etape_total = $(this).attr('data-etapes_max');
		for (etape in etapes) {
			var condition = etapes[etape]['afficher_si'] ?? 'true';
			if (!name || condition.includes(name)) {
				$(this).attr('data-' + etape, eval(condition));
			}
			if (condition && !eval($(this).attr('data-' + etape))) {
				etape_total--;
			}
		}
		$(this).find('.formulaire_spip_etape__total').text(etape_total);
	});
}

// Pour le libellé de l'étape suivante
//@param form, le formulaire
//@param name le nom de la saisie dont la valeur vient juste de changer
function afficher_si_set_etape_suivante(form, name) {
	var etapes = afficher_si_parse_data_etapes_futures(form);
	if (!etapes) {
		return;
	}

	var label_enregistrer = form.find('button.submit_suivant').attr('data-label_enregistrer');
	var titre_retenu = label_enregistrer;
	// Chercher la première future étape
	for (etape in etapes) {
		var afficher_si_etape = etapes[etape]['afficher_si'] ?? 'true';
		if (eval(afficher_si_etape)) {
			titre_retenu = etapes[etape]['label'];
			break;
		}
	}
	form.find('button.submit_suivant').each(function() {
		var $span = $(this).find('.btn__label');
		// Stocker le modèle pour suivant, si pas deja fait
		if (!$(this).attr('data-modele')) {
			$(this).attr('data-modele', $span.html());
		}
		// Puis ajuster le titre, le modèle variant selon que nous passons directement à la validation ou pas
		if (titre_retenu == label_enregistrer) {
			$span.html(titre_retenu);
		} else {
			$span.html($(this).attr('data-modele'));
			$span.find('.titre-etape').html(titre_retenu);
		}
	});
}
// Recopier les info d'afficher_si présente dans [data-resume_etapes_futures] au sein de chaque etapes futures
// Le but est de simplifier ainsi le code principal, en se contentant du code de animer_afficher_si()
// @param jquery obcet form
function afficher_si_init_chemin_etapes(form) {
	var etapes = afficher_si_parse_data_etapes_futures(form);
	if (!etapes) {
		return;
	}
	for (etape in etapes) {
		var afficher_si_etape = etapes[etape]['afficher_si'] ?? '';
		if (afficher_si_etape) {
			form.find('.etapes__item.' + etape).attr('data-afficher_si', afficher_si_etape).addClass('afficher_si_sans_visuel');
		}
	}
}
function afficher_si_parse_data_etapes_futures(form) {
	var data = form.attr('data-resume_etapes_futures');
	if (!data) {
		return;
	}
	var etapes = JSON.parse(data);
	return etapes;
}
