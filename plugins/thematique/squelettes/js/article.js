/**
 * Génère un article de blog ou un événement pédagogique sur la timeline.
 *
 * @constructor
 */

function Article() {

	/**
	 * Échappe les caractères HTML spéciaux (le titre passe par decodeHtmlEntities,
	 * donc on doit ré-échapper avant de l'injecter dans un bloc HTML).
	 *
	 * @param {string} texte
	 * @returns {string}
	 */
	function echapperHtml(texte) {
		return $('<div/>').text(texte).html();
	}

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
		this.titre = decodeHtmlEntities(data.titre);
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

		const titreSur = echapperHtml(this.titre);
		const dateSure = echapperHtml(date_texte);

		const picto_commentaires = this.nombre_commentaires > 0
			? `<div class="picto_nombre_commentaires">${this.nombre_commentaires}</div>`
			: '';

		let html;
		if (isBlog) {
			const est_magazine = /gazette|novamag|magazine/.test(this.titre);
			const classe_article = 'article_blog' + (est_magazine ? ' article_blog2' : '');

			html = `
				<div class="timeline_item ${prefix}_container" style="top:${this.y * 100}%; left:${this.x / CCN.projet.nombre_jours_total * 100}%;">
					<div id="article_blog${this.id}" class="${classe_article} bulle_bd">
						<svg class="bubble_svg" aria-hidden="true"></svg>
						<div class="bulle_contenu">
							<div>${titreSur}</div><br/>
							<span class="${prefix}_date">${dateSure}</span>
						</div>
						${picto_commentaires}
					</div>
				</div>
			`;
		} else {
			html = `
				<div class="timeline_item ${prefix}_container" style="top:${this.y * 100}%; left:${this.x / CCN.projet.nombre_jours_total * 100}%;">
					<div id="article_evenement${this.id}" class="article_evenement">
						<svg class="bubble_svg"></svg>
						<div class="article_evenement_inner">
							<div class="bulle_contenu">
								<div class="bulle-texte">${titreSur}</div><br/>
								<span class="${prefix}_date">${dateSure}</span>
							</div>
						</div>
						${picto_commentaires}
					</div>
				</div>
			`;
		}

		this.div_base = $(html.trim());
		this.div_texte = this.div_base.find('.bulle_contenu');

		layer.prepend(this.div_base);

		const _thisId = this.id_objet;
		const _thisTypeObjet = this.type_objet;

		this.div_texte.on('click', () => isBlog
			? callArticleBlog(_thisId)
			: callArticleEvenement(_thisId, _thisTypeObjet)
		);

		if (CCN.admin == 0) {
			this.div_base.draggable({
				axis: "y",
				start: function (event, ui) {
					$(this).children().children().removeAttr("onClick");
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