// variable modifiable afin d'initialiser l'ouverture d'un onglet
if(typeof(onglet_actif)=='undefined') var onglet_actif = '';
// variable modifiable afin de choisir la balise utilisee pour les onglets
if(typeof(onglets_balise)=='undefined') var onglets_balise = 'h2';

// fonction pour retirer toute selection dans le bloc
jQuery.fn.reset_onglets = function(selector) {
		this.children('.selected').removeClass('selected').end()
		    .children('.onglets_liste').children('.selected').removeClass('selected').attr('aria-selected', 'false').attr('tabindex', '-1');
};

// fonction pour montrer un contenu
jQuery.fn.montre_onglet = function(selector) {
	// click sur un titre
	if(this.is('.onglets_titre')) {
		var contenu = '#' + this[0].id.replace(/titre/,'contenu');
		var bloc = this.parent().parent();
		bloc.reset_onglets();
		jQuery(contenu).addClass('selected');
		this.addClass('selected').attr('aria-selected', 'true').attr('tabindex', '0');
	}
	// click sur un titre
	if(this.is('.onglets_contenu')) {
		var titre = this[0].id.replace(/contenu/, 'titre');
		jQuery('#'+titre).montre_onglet();
	}
	return this;
};

// compatibilite Ajax : ajouter "this" a "jQuery" pour mieux localiser les actions
function onglets_init() {
	var cs_bloc = jQuery('div.onglets_bloc_initial', this);
	if(cs_bloc.length) {
		cs_bloc.prepend('<div class="onglets_liste" role="tablist"></div>')
			.children('.onglets_contenu').each(function(i) {
				this.id = 'onglets_contenu_' + i;
				jQuery(this).attr('role', 'tabpanel').attr('tabindex', '0').attr('aria-labelledby', 'onglets_titre_' + i );
				jQuery(this).parent().children('.onglets_liste').append(
					'<'+onglets_balise+' id="'+'onglets_titre_' + i + '" class="onglets_titre" role="tab" tabindex="-1" aria-selected="false" aria-controls="' + this.id + '">'
						+ this.firstChild.innerHTML + '</'+onglets_balise+'>'
				);
			})
			.children(onglets_balise).remove();
		jQuery('div.onglets_liste', this).each(function() {
			jQuery(this.firstChild).montre_onglet();
		});
		jQuery(onglets_balise+'.onglets_titre', this).hover(
			function(){
				jQuery(this).addClass('hover')
			},function(){
				jQuery(this).removeClass('hover')
			}
		);
		jQuery('div.onglets_bloc_initial', this)
			.attr('class','onglets_bloc').each(function(i) {this.id = 'ongl_'+i;});
		// clic du titre...
		jQuery(onglets_balise+'.onglets_titre', this).click(function(e) {
			jQuery(this).montre_onglet();
			return false;
		});
		// clic des <a>, au cas ou...
		jQuery(onglets_balise+'.onglets_titre a', this).click(function(e){
			jQuery(this).parents(onglets_balise).click();
			if (e.stopPropagation) e.stopPropagation();
			e.cancelBubble = true;
			return false;
		});
		// activation d'onglet(s) grace a l'url
		var onglet_get = get_onglet(window.location);
		if(onglet_get && (this==document)) clic_onglet(onglet_get);
		// clic vers une note dans un onglet
		jQuery('.spip_note['+cs_sel_jQuery+'name^=nb], .spip_note['+cs_sel_jQuery+'id^=nb]').each(function(i) {
			jQuery(this).click(function(e){
				var href = this.href.substring(this.href.lastIndexOf("#"));
				jQuery(href).parents('.onglets_contenu').eq(0).montre_onglet();
				return true;
			});
		});
	}
}

function clic_onglet(liste) {
	var onglets = liste.split(',');
	for (var i=0; i<onglets.length; i++)
		jQuery('#onglets_titre_'+onglets[i]).click();
}

function get_onglet(url) {
	tab = url.search.match(/[?&]onglet=([0-9,]*)/) || url.hash.match(/#onglet([0-9,]*)/);
	return tab==null?onglet_actif:tab[1];
}

// fonction non utilisee car en principe, les notes orphelines sont supprimees en php
function decoupe_init() {
	// suppression des notes orphelines
	var that = this;
	jQuery('a[rev="footnote"].spip_note', that).each(function() {
		if(!jQuery(this.getAttribute('href'), that).length) jQuery(this).parents('div[id^=nb]').eq(0).remove();
	});
}