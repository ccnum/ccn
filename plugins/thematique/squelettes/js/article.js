/**
 * Génère un article de blog ou un événement pédagogique sur la timeline.
 *
 * @constructor
 */

function Article() {

	/**
	 * Initialise l'article.
	 *
	 * @param {Object} data - Données à affecter à l'instance
	 * @param {string} type - "blogs" ou "evenements"
	 */
	this.init = function (data, type) {
		this.data = data;
		this.type = type;
		this.id = data.id;
		this.type_objet = data.type_objet;
		this.id_objet = data.id_objet;
		this.titre = type === 'evenements' ? decodeHtmlEntities(data.titre) : data.titre;
		this.date = data.date;
		this.nombre_commentaires = data.nombre_commentaires;
		this.x = data.nombre_jours;
		this.y = data.y;

		if (type === 'evenements' && this.titre.length > 25) {
			this.titre = this.titre.substring(0, 25) + "(...)";
		}

		const isBlog = type === 'blogs';
		const prefix = isBlog ? 'article_blog' : 'article_evenement';
		const urlImg = isBlog ? CCN.urlImgBlog : CCN.urlImgEvenement;
		const layer = isBlog ? CCN.timelineLayerBlogs : CCN.timelineLayerEvenements;
		const date_texte = formatDateCourte(this.date);

		this.div_base = $('<div/>')
			.attr('class', 'timeline_item ' + prefix + '_container')
			.css({
				'top': (this.y * 100) + '%',
				'left': (this.x / CCN.projet.nombre_jours * 100) + '%'
			});

		this.div_base.append($('<img src="' + urlImg + '">'));

		const spanDate = $('<span class="' + prefix + '_date">').text(date_texte);
		const bTitre = $('<b>').text(this.titre);

		let contentDiv;
		if (isBlog) {
			const articleClass = 'article_blog' +
				((this.titre.match("gazette") || this.titre.match("novamag") || this.titre.match("magazine")) ? ' article_blog2' : '');
			contentDiv = $('<div class="article_blog_inner">')
				.append(bTitre)
				.append('<br/>')
				.append(spanDate);
			const divArticle = $('<div>').attr('id', 'article_blog' + this.id).attr('class', articleClass).append(contentDiv);
			if (this.nombre_commentaires > 0) {
				divArticle.append($('<div class="picto_nombre_commentaires">').text(this.nombre_commentaires));
			}
			this.div_texte = $('<div/>').attr('class', '').append(divArticle);
		} else {
			const divTexteInner = $('<div class="article_evenement_texte">')
				.append(bTitre)
				.append('<br/>')
				.append(spanDate);
			contentDiv = $('<div class="article_evenement_inner">').append(divTexteInner);
			const divArticle = $('<div>').attr('id', 'article_evenement' + this.id).attr('class', 'article_evenement').append(contentDiv);
			if (this.nombre_commentaires > 0) {
				divArticle.append($('<div class="picto_nombre_commentaires">').text(this.nombre_commentaires));
			}
			this.div_texte = $('<div/>').attr('class', '').append(divArticle);
		}

		this.div_base.append(this.div_texte);
		layer.prepend(this.div_base);

		const _thisId = this.id_objet;
		const _thisTypeObjet = this.type_objet;

		this.div_texte.on('click', () => isBlog
			? callArticleBlog(_thisId)
			: callArticleEvenement(_thisId, _thisTypeObjet)
		);

		if (CCN.admin == 0) {
			$(this.div_base).draggable({
				axis: "y",
				start: function (event, ui) {
					$(this).children('div').children('div').removeAttr("onClick");
				},
				stop: function (event, ui) {
					const y_parent = $(this).parent().height();
					const yy = ui.position.top / y_parent;
					$.post("spip.php?page=ajax&mode=article-sauve-coordonnees", { id_objet: _thisId, type_objet: _thisTypeObjet, X: 0, Y: yy });
				}
			});
		}
	}
}
