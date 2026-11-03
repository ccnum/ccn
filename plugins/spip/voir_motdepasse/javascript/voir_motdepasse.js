function voir_motdepasse_activer() {
	jQuery(function(){
		jQuery('input[type=password]')
			.not('.hideShowPassword-field')
			.hideShowPassword({
			show: false,
			innerToggle: 'focus',
			triggerOnToggle: 'focus',
			toggle: {
				attr: {
					'aria-label': window.spipConfig.voir_motdepasse.lang.voir_title,
					title: window.spipConfig.voir_motdepasse.lang.voir_title,
				}
			},
			states: {
				shown: {
					toggle: {
						content: window.spipConfig.voir_motdepasse.lang.masquer_label,
						attr: {
							title: window.spipConfig.voir_motdepasse.lang.masquer_title,
						}
					}
				},
				hidden: {
					toggle: {
						content: window.spipConfig.voir_motdepasse.lang.voir_label,
						attr: {
							title: window.spipConfig.voir_motdepasse.lang.voir_title,
						}
					}
				},
			}
		});
	})
}

// À l'exécution initiale et aux chargements AJAX de SPIP
voir_motdepasse_activer();
onAjaxLoad(voir_motdepasse_activer);
