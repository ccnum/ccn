/**
 * Génère une réponse.
 *
 * @constructor
 */

function Reponse() {

	/**
	 * Initialise la réponse.
	 *
	 * @param {Object} data - Données à affecter à l'instance
	 */
	this.init = function (data) {
		this.data = data;
		this.id = this.data.id;
		this.classe_id = this.data.classe_id;
		this.titre = decodeHtmlEntities(this.data.titre);
		this.consigne = this.data.consigne;
		this.date = this.data.date;
		this.date_date = this.data.date_date;
		this.nombre_commentaires = this.data.nombre_commentaires;
		this.x = this.data.nombre_jours;
		this.x_absolu = this.data.nombre_jours + this.consigne.x; // Le bloc réponse est relatif à la position x de la consigne
		this.y = this.data.y;
		this.classeIndex = this.data.classes.findIndex(c => this.classe_id == c.id);
		this.nom_classe = this.data.classes[this.classeIndex]?.nom ?? '';

		this.initDOM();
	}
	/**
	 * Crée l'élément DOM et l'intègre dans la timeline.
	 */
	this.initDOM = function () {

		const coul = String(this.classe_id).slice(-1);

		const date_texte = formatDateCourte(this.date);

		this.div_base = $(`
			<div id="reponse_haute${this.id}"
				class="timeline_item reponse_haute reponse_haute_consigne_parent${this.consigne.id} hide"
				data-consigne-id="${this.consigne.id}"
				data-reponse-id="${this.id}"
				style="top:${this.y * 100}%; left:${this.x_absolu / CCN.projet.nombre_jours * 100}%;">
				<div id="reponse${this.id}"
					class="reponse couleur_texte_travail_en_cours bgc_classe_${coul}">
					${this.nombre_commentaires > 0 ? `<div aria-label="${this.nombre_commentaires} interaction${this.nombre_commentaires > 1 ? 's' : ''}" class="picto_nombre_commentaires">${this.nombre_commentaires}</div>` : ''}
					<div class='logo photo'
						 style='display:flex;align-items:center;justify-content:center;container-type:size;'
					>
						<span role="img" aria-label="${escHtml(this.nom_classe)}" style="font-size:min(70cqw,70cqh)" class="bgc_classe_${this.classeIndex}">
							${getClassIcon(this.classeIndex)}
						</span>
					</div>
					<div class="texte">
						<div class="titre">${escHtml(this.titre)}</div>
						<div class="auteur_date">${escHtml(this.nom_classe)} - ${date_texte}</div>
					</div>
					<div class="nettoyeur"></div>
				</div>
			</div>
		`);

		this.div_texte = this.div_base.find(`#reponse${this.id}`);

		this.connecteur = $(`
			<div id="connecteur_consigne_${this.consigne.id}_reponse_${this.id}"
				class="connecteur_timeline bgc_classe_${coul} hide"
				data-consigne-id="${this.consigne.id}"
				data-reponse-id="${this.id}">
			</div>
		`);

		CCN.timelineLayerConsignes.prepend(this.div_base);
		CCN.projet.timeline_fixed.append(this.connecteur);

		const _thisId = this.id;

		this.div_texte.on('click', () => callReponse(_thisId));

		this.largeur = this.div_base.outerWidth();
		this.hauteur = this.div_base.outerHeight() + 7;

		if (CCN.admin == 0) {
			$(this.div_base).draggable(
				{
					axis: "y",
					start: function (event, ui) {
						$(this).addClass('no_event');
					},
					drag: function (event, ui) {
						updateConnecteur(event.target, ui);
					},
					stop: function (event, ui) {
						const yy = (ui.offset.top - CCN.projet.timeline.offset().top) / CCN.projet.timeline.height();

						$.post("spip.php?page=ajax&mode=article-sauve-coordonnees", { id_objet: _thisId, type_objet: "article", X: 0, Y: yy });
						$(this).removeClass('no_event');
						this.y = yy;
						$(this).css({ 'top': (yy * 100) + '%' });
					}
				}
			);
		}
	}

	/**
	 * Affiche la réponse dans la timeline et la met en surbrillance.
	 */
	this.showInTimeline = function () {
		const reponse_DOM = $('.reponse_haute[data-reponse-id="' + this.id + '"]');

		$('body').addClass('highlightReponse');
		$('.reponse_haute, .connecteur_timeline').removeClass('current_select');

		$('#connecteur_consigne_' + this.consigne.id + '_reponse_' + this.id).addClass('current_select');
		reponse_DOM.addClass('current_select');
	}

}
