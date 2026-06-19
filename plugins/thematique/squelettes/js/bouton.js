////////////////////////////////////////////////////////////////
// objet bouton
////////////////////////////////////////////////////////////////
function bouton() {

	this.init = function (projet, canvas, type) {
		this.div_base = document.createElement("div");

		if ((CCN.idRestreint > 0) && (CCN.typeRestreint != '') && (CCN.typeRestreint != 'travail_en_cours')) {
			const positions = {
				0: { left: "195px", bottom: "-105px" },
				1: { left: "20px",  bottom: "60px"   },
			};
			const pos = positions[type];
			if (pos) {
				const wrapper = document.createElement("div");
				wrapper.id = "reponse_plus2";
				wrapper.className = "bouton_article_plus";
				const img = document.createElement("img");
				img.src = CCN.urlRoot + "img/reponse_plus.png";
				img.title = "publier un nouvel article";
				img.addEventListener("click", () => createReponse(false, 0, CCN.idRestreint));
				wrapper.appendChild(img);
				this.div_base.appendChild(wrapper);
				this.div_base.style.position = "absolute";
				this.div_base.style.left = pos.left;
				this.div_base.style.bottom = pos.bottom;
				this.div_base.style.visibility = "visible";
			}
		}

		canvas.appendChild(this.div_base);
	}
}
