/**
 * Génère une consigne.
 *
 * @constructor
 */

function Consigne() {

	/**
	 * Initialise la consigne.
	 *
	 * @param {Object} data - Données à affecter à l'instance
	 */
	this.init = function (data) {
		this.data = data;
		this.id = this.data.id;
		this.intervenant_id = this.data.intervenant_id;
		this.numero = this.data.numero;
		this.titre = decodeHtmlEntities(this.data.titre);

		this.nombre_reponses = this.data.nombre_reponses;
		this.reponses_id = this.data.reponses.map(Number);
		this.nombre_commentaires = this.data.nombre_commentaires;
		this.nombre_jours = this.data.nombre_jours;
		this.x = this.data.nombre_jours;
		this.y = this.data.y; // Entre 0 et 1
		this.image = this.data.image;
		this.select = false;
		this.date_texte = formatDateLongue(this.data.date_texte);
		this.reponses = [];
		this.intervenant_nom = this.data.intervenant_nom;
		this.nombre_jours_max = this.data.nombre_jours_max;

		if (this.nombre_jours_max <= 0) {
			this.nombre_jours_max = data.nombre_jours;
		}
		this.isLivrable = this.data.isLivrable;
		this.isLastConsigne = this.data.isLastConsigne;

		this.initDOM();
	}

	/**
	 * Crée l'élément DOM et l'intègre dans la timeline.
	 */
	this.initDOM = function () {
		const coul = String(this.data.intervenant_id).slice(-1);
		const classes_triees = [...this.data.classes].sort((c1, c2)=>c1.id-c2.id);
		let reponses_puces = '';
		classes_triees.forEach((classe, index) => {
			let disabled = 'disabled';
			let iconSpan = '';
			if (this.reponses_id.includes(classe.id)) {
				disabled = '';
				iconSpan = `<span role="img" aria-label="${escHtml(classe.nom)}" style="font-size:min(70cqw,70cqh)" class="bgc_classe_${index}">
					${getClassIcon(index)}
				</span>`;
			}

			reponses_puces += `
				<div class='reponse_puce ${disabled} tooltip logo'
					data-tip='${classe.nom}'
					style='display:flex;align-items:center;justify-content:center;container-type:size;'>
					${iconSpan}
				</div>`;
		});
		this.div_base = $(`
			<div id="consigne_haute${this.id}"
				 class="timeline_item consigne_haute"
				 style="top:${this.y * 100}%; left:${this.x / CCN.projet.nombre_jours * 100}%;"
			>
				<img class="card-bg"
					 src="${CCN.urlRoot}img/cards_background.svg"
					 alt=""
					 style="display: none"/>
				<div id="consigne${this.id}"
					class="consigne couleur_texte_consignes couleur_consignes${coul}"
					data-id="${this.id}"
					data-index="${this.numero}"
				>
					${this.data.nombre_commentaires > 0 ? `<div aria-label="${this.data.nombre_commentaires} interaction${this.data.nombre_commentaires > 1 ? 's' : ''}" class="picto_nombre_commentaires">${this.data.nombre_commentaires}</div>` : ''}
					<div class="etiquette-etape">
						<img class="logo-etiquette" src="" alt="" />
						<span class="texte-etiquette">Étape N°${this.numero+1}</span>
					</div>
					<div class="texte">
						<div class="first-row">
							<div class="photo"><img src="${this.data.image}" alt="${escHtml(this.intervenant_nom)}" /></div>
							<div class="titre"></div>
						</div>
						<div class="second-row">
							<div class="picto_nombre_reponses">
								${reponses_puces}
							</div>
						</div>
					</div>
					<div class="nettoyeur"></div>
				</div>
				<div class="bouton_reponse_consigne">
					<img src="${CCN.urlRoot}img/reponse_plus.png" alt="" title="R&eacute;pondre &agrave; la consigne">
					<div style="white-space: nowrap;">Répondre&nbsp;&agrave;&nbsp;la mission</div>
				</div>
				<div class="bouton_reponse_consigne">
					<img src="${CCN.urlRoot}img/reponse_plus.png" alt="" title="Accéder à ma réponse">
					<div style="white-space: nowrap;">Ma réponse</div>
				</div>
			</div>
		`);

		this.div_consigne = this.div_base.find(`#consigne${this.id}`);
		this.div_reponse_plus = this.div_base.find('.bouton_reponse_consigne').eq(0);
		this.div_reponse_see = this.div_base.find('.bouton_reponse_consigne').eq(1);

		this.div_base.find(`.titre`).text(this.titre);

		if(this.isLastConsigne) {
			this.div_base.addClass("derniere-etape")
			this.div_base.find(".card-bg").show();
			if(this.isLivrable) {
				this.div_base.find(".texte-etiquette").first().text("PROJETS FINAUX !");
				this.div_base.find(".logo-etiquette").first().attr("src", `${CCN.urlRoot}img/sparks.svg`)
			} else {
				this.div_base.find(".logo-etiquette").first().attr("src", `${CCN.urlRoot}img/location-check.svg`)
			}
		} else {
			this.div_base.find(".logo-etiquette").hide()
		}
		CCN.timelineLayerConsignes.prepend(this.div_base);

		this.largeur = this.div_base.outerWidth();
		this.hauteur = this.div_base.outerHeight();

		const _thisId = this.id;
		const _thisIdRestreint = parseInt(CCN.idRestreint, 10);
		const _thisNumero = parseInt(this.numero, 10);

		this.div_reponse_plus.on('click', () => createReponse(false, _thisId, _thisIdRestreint, _thisNumero));
		this.div_consigne.on('click', () => callConsigne(_thisId));

		if (CCN.admin == 0) {
			const leftPercent = CCN.projet.nombre_jours > 0 ? this.x / CCN.projet.nombre_jours * 100 : 0;

			this.div_base.draggable({
				axis: "y",
				start: function (event, ui) {
					$(this).addClass('no_event');
				},
				drag: function (event, ui) {
					// jQuery UI va écrire un left en px — on le réécrit en % immédiatement
					ui.position.left = CCN.projet.timeline.width() * leftPercent / 100;
					updateConnecteurs();
				},
				stop: function (event, ui) {
					const yy = (ui.offset.top - CCN.projet.timeline.offset().top) / CCN.projet.timeline.height();

					$.post("spip.php?page=ajax&mode=article-sauve-coordonnees", { id_objet: _thisId, type_objet: "article", X: 0, Y: yy });
					$(this).removeClass('no_event');
					this.y = yy;
					// Réécrit les deux coords en %
					$(this).css({
						top:  (yy * 100) + '%',
						left: leftPercent + '%'
					});
				}
			});
		}
	}

	/**
	 * Affiche le bouton <tt>Répondre à la question</tt>.
	 *
	 * @see loadConsignes
	 */
	this.showNewReponseButtonInTimeline = function () {
		if ((CCN.idRestreint > 0)
			&& (CCN.typeRestreint != '')
			&& (CCN.typeRestreint == 'travail_en_cours')
		) {
			this.div_reponse_plus.addClass('show');
		
		}
	}

	/**
	 * Affiche le bouton <tt>Consulter ma réponse</tt>.
	 *
	 * @param {Number} answerId - Id de la réponse de la classe courante
	 *
	 * @see loadConsignes
	 */
	this.showMyReponseButtonInTimeline = function (answerId) {
		if ((CCN.idRestreint > 0)
			&& (CCN.typeRestreint != '')
			&& (CCN.typeRestreint == 'travail_en_cours')
		) {
			this.div_reponse_see.on('click', () => callReponse(answerId)).addClass('show');
		}
		
	}

	/**
	 * Fait apparaître le picto du nombre de commentaires d'une consigne.
	 */
	this.showConsignePastille = function () {
		$("#consigne" + this.id + " .picto_nombre_commentaires").fadeIn('slow');
	}

	/**
	 * Fait disparaître le picto du nombre de commentaires d'une consigne.
	 */
	this.hideConsignePastille = function () {
		$("#consigne" + this.id + " .picto_nombre_commentaires").fadeOut('slow');
	}

	/**
	 * Affiche la consigne et les réponses associées.
	 *
	 * @see showConsigneInTimeline
	 * @see callConsigne
	 *
	 */
	this.showInTimeline = function () {

		let rafId;
		const rafEnd = Date.now() + 2300;

		function rafConnecteurs() {
			updateConnecteurs();
			if (Date.now() < rafEnd) {
				rafId = requestAnimationFrame(rafConnecteurs);
			}
		}
		rafId = requestAnimationFrame(rafConnecteurs);
		CCN.projet.rafConnecteurs = rafId;

		$('.connecteur_timeline').addClass('hide');
		$('.connecteur_timeline[data-consigne-id="' + this.id + '"]').removeClass('hide');

		const y_dest = 0;

		this.hideConsignePastille();

		CCN.projet.showRangeOfTimeline(this.nombre_jours_max, this.x - 3, y_dest);

		$('.consigne_haute').not('#consigne_haute' + this.id).addClass('hide');
		$('.reponse_haute').not('.reponse_haute_consigne_parent' + this.id).addClass('hide');

		$('#consigne_haute' + this.id).removeClass('hide');
		$('.reponse_haute_consigne_parent' + this.id).removeClass('hide');

		for (let i = 0; i < CCN.articlesBlog.length; i++) {
			$(CCN.articlesBlog[i].div_base).hide();
		}

		for (let i = 0; i < CCN.articlesEvenement.length; i++) {
			$(CCN.articlesEvenement[i].div_base).hide();
		}

		this.select = true;
	}
}

