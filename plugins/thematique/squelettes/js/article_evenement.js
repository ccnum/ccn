/**
 * Génère un article du blog pédagogique (événement).
 *
 * @constructor
 */

function ArticleEvenement() {

	/**
	 * Initialise l'événement.
	 *
	 * @param {Object} data - Données à affecter à l'instance
	 */
	this.init = function (data) {
		this.data = data;
		this.id = this.data.id;
		this.type_objet = this.data.type_objet;
		this.id_objet = this.data.id_objet;
		this.titre = decodeHtmlEntities(this.data.titre);
		this.date = this.data.date;
		this.nombre_commentaires = this.data.nombre_commentaires;
		this.x = this.data.nombre_jours;
		this.y = this.data.y;
		this.left = -1;
		this.top = -1;
		this.show = false;

		this.div_base = $('<div/>')
			.attr('class', 'timeline_item article_evenement_container')
			.css(
				{
					'top': (this.y * 100) + '%',
					'left': (this.x / CCN.projet.nombre_jours * 100) + '%'
				}
			);

		this.img = $('<img src="' + CCN.urlImgEvenement + '">');

		this.div_base.append(this.img);

		const date_texte = formatDateCourte(this.date);

		// Trim text if too long
		if (this.titre.length > 25) {
			this.titre = this.titre.substring(0, 25) + "(...)";
		}

		const divTexteInner = $('<div class="article_evenement_texte">')
			.append($('<b>').text(this.titre))
			.append('<br/>')
			.append($('<span class="article_evenement_date">').text(date_texte));
		const divInner = $('<div class="article_evenement_inner">').append(divTexteInner);
		const divArticleEvenement = $('<div>').attr('id', 'article_evenement' + this.id).attr('class', 'article_evenement').append(divInner);
		if (this.nombre_commentaires > 0) {
			divArticleEvenement.append($('<div class="picto_nombre_commentaires">').text(this.nombre_commentaires));
		}
		this.div_texte = $('<div/>').attr('class', '').append(divArticleEvenement);

		this.div_base.append(this.div_texte);

		CCN.timelineLayerEvenements.prepend(this.div_base);

		const _thisId = this.id_objet;
		const _thisTypeObjet = this.type_objet;

		this.div_texte.on('click', () => callArticleEvenement(_thisId, _thisTypeObjet));

		if (CCN.admin == 0) {
			$(this.div_base).draggable(
				{
					axis: "y",
					start: function (event, ui) {
						$(this).children('div').children('div').removeAttr("onClick");
					},
					stop: function (event, ui) {
						const y_parent = $(this).parent().height();
						const yy = ui.position.top / y_parent;
						$.post("spip.php?page=ajax&mode=article-sauve-coordonnees", { id_objet: _thisId, type_objet: _thisTypeObjet, X: 0, Y: yy });
					}
				}
			);
		}
	}
}

