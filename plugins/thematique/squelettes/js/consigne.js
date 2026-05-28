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
		this.titre = this.data.titre;

		this.nombre_reponses = this.data.nombre_reponses;
		this.reponses_id = this.data.reponses;
		this.nombre_commentaires = this.data.nombre_commentaires;
		this.nombre_jours = this.data.nombre_jours;
		this.x = this.data.nombre_jours;
		this.y = this.data.y; // Entre 0 et 1
		this.image = this.data.image;
		this.select = false;
		this.date_texte = this.data.date_texte.substring(0, 2) + " "
			+ CCN.nomMois[parseFloat(this.data.date_texte.substring(3, 5)) - 1] + " "
			+ this.data.date_texte.substring(6, 10);
		this.reponses = [];
		this.intervenant_nom = '';
		this.nombre_jours_max = this.data.nombre_jours_max;

		for (let k = 0; k < this.data.intervenants.length; k++) {
			if (this.data.intervenant_id == this.data.intervenants[k].id) {
				this.intervenant_nom = this.data.intervenants[k].nom;
			}
		}

		if (this.nombre_jours_max <= 0) {
			this.nombre_jours_max = data.nombre_jours;
		}

		this.initDOM();
	}

	/**
	 * Crée l'élément DOM et l'intègre dans la timeline.
	 */
	this.initDOM = function () {
		const coul = String(this.data.intervenant_id).slice(-1);

		this.div_titre = $('<div/>')
			.attr('id', 'consigne' + this.id)
			.attr('class', 'consigne couleur_texte_consignes couleur_consignes' + coul)
			.attr('data-id', this.id)
			.attr('data-index', this.numero);

		this.div_base = $('<div/>')
			.attr('id', 'consigne_haute' + this.id)
			.attr('class', 'timeline_item consigne_haute')
			.css(
				{
					'top': (this.y * 100) + '%',
					'left': (this.x / CCN.projet.nombre_jours * 100) + '%'
				}
			);

		let reponses_puces = '';

		for (let j = 1; j <= this.data.nombre_reponses; j++) {
			if (j <= this.data.classes.length) {
				const couleur = this.reponses_id[j - 1].slice(-1);
				reponses_puces += '<div class="reponse_puce couleur_travail_en_cours' + couleur + '"></div>';
			}
		}

		for (let j = 1; j <= this.data.classes.length - this.data.nombre_reponses; j++) {
			reponses_puces += '<div class="reponse_puce disabled"></div>';
		}

		const divPictoComm = $('<div class="picto_nombre_commentaires">').text(this.data.nombre_commentaires);
		const divPhoto = $('<div class="photo">').append($('<img>').attr('src', this.data.image));
		const divTitre = $('<div class="titre">').text(this.titre);
		const divAuteurDate = $('<div class="auteur_date">').text(this.intervenant_nom)
			.append($('<div class="picto_nombre_reponses">').html(reponses_puces));
		const divTexte = $('<div class="texte">').append(divTitre).append(divAuteurDate);
		this.div_titre.append(divPictoComm).append(divPhoto).append(divTexte).append($('<div class="nettoyeur">'));
		this.div_base.append(this.div_titre);

		// Calcul des tailles des consignes
		this.largeur = $(this.div_base).outerWidth();
		this.hauteur = $(this.div_base).outerHeight();

		// Préparation bouton réponse plus (crayon)
		this.div_reponse_plus = $("<div class='bouton_reponse_consigne' onclick='createReponse(false," + this.id + "," + CCN.idRestreint + "," + this.numero + ");'><img src='" + CCN.urlRoot + "img/reponse_plus.png' title='Répondre à la consigne'>Répondre à la consigne</div>");
		this.div_reponse_see = $("<div class='bouton_reponse_consigne'><img src='" + CCN.urlRoot + "img/reponse_plus.png' title='Accéder à ma réponse'> Accéder à ma réponse</div>");

		this.div_base.append(this.div_reponse_plus);
		this.div_base.append(this.div_reponse_see);

		CCN.timelineLayerConsignes.prepend(this.div_base);

		const _thisId = this.id;

		this.div_titre.on(
			'click', function () {
				callConsigne(_thisId);
			}
		);

		// Draggable (admin)
		if (CCN.admin == 0) {
			$(this.div_base).draggable({
				axis: "y",
				start: function (event, ui) {
					$(this).addClass('no_event');
				},
				drag: function (event, ui) {
					updateConnecteurs();
				},

				stop: function (event, ui) {
					const yy = (ui.offset.top - CCN.projet.timeline.offset().top) / CCN.projet.timeline.height();

					$.post("spip.php?page=ajax&mode=article-sauve-coordonnees", { id_objet: _thisId, type_objet: "article", X: 0, Y: yy });
					$(this).removeClass('no_event');

					this.y = yy;

					$(this).css({ 'top': (yy * 100) + '%' });
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
			this.div_reponse_see.on(
				'click', function () {
					callReponse(answerId);
				}
			).addClass('show');
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