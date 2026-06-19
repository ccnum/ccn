/**
 * Génère un article du blog du projet (blog).
 *
 * @constructor
 */

function ArticleBlog() {

	/**
	 * Initialise l'article de blog.
	 *
	 * @param {Object} data - Données à affecter à l'instance
	 */
	this.init = function (data) {
		this.data = data;
		this.id = this.data.id;
		this.type_objet = this.data.type_objet;
		this.id_objet = this.data.id_objet;
		this.titre = this.data.titre;
		this.date = this.data.date;
		this.nombre_commentaires = this.data.nombre_commentaires;
		this.x = this.data.nombre_jours;
		this.y = this.data.y;
		this.index = this.data.index

		this.div_base = $('<div/>')
			.attr('class', 'timeline_item article_blog_container')
			.css(
				{
					'top': (this.y * 100) + '%',
					'left': (this.x / CCN.projet.nombre_jours * 100) + '%'
				}
			);

		this.img = $('<img src="' + CCN.urlImgBlog + '">');

		this.div_base.append(this.img);

		const _d = parseDate(this.date);
		const date_texte = _d.getDate() + " " + CCN.nomMois[_d.getMonth()];

		const articleBlogClass = 'article_blog' +
			((this.titre.match("gazette") || this.titre.match("novamag") || this.titre.match("magazine")) ? ' article_blog2' : '');
		const divInner = $('<div class="article_blog_inner">')
			.append($('<b>').text(this.titre))
			.append('<br/>')
			.append($('<span class="article_blog_date">').text(date_texte));
		const divArticleBlog = $('<div>').attr('id', 'article_blog' + this.id).attr('class', articleBlogClass).append(divInner);
		if (this.nombre_commentaires > 0) {
			divArticleBlog.append($('<div class="picto_nombre_commentaires">').text(this.nombre_commentaires));
		}
		this.div_texte = $('<div/>').attr('class', '').append(divArticleBlog);

		this.div_base.append(this.div_texte);

		CCN.timelineLayerBlogs.prepend(this.div_base);

		const _thisId = this.id_objet;

		this.div_texte.on(
			'click', function () {
				callArticleBlog(_thisId);
			}
		);

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
						$.post("spip.php?page=ajax&mode=article-sauve-coordonnees", { id_objet: _thisId, type_objet: "article", X: 0, Y: yy });
					}
				}
			);
		}
	}
}