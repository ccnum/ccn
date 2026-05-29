////////////////////////////////////////////////////////////////
// objet bouton
////////////////////////////////////////////////////////////////
function bouton() {

	// membres
	let url_base, opacite;
	let div_base;

	// méthode init
	this.init = function (projet, canvas, type) {
		switch (type) {
			// bouton plus pour ajout d'article
			case 0:
				this.div_base = document.createElement("div");
				if ((CCN.idRestreint > 0) && (CCN.typeRestreint != '') && (CCN.typeRestreint != 'travail_en_cours')) {
					const wrapper0 = document.createElement("div");
					wrapper0.id = "reponse_plus2";
					wrapper0.className = "bouton_article_plus";
					const img0 = document.createElement("img");
					img0.src = CCN.urlRoot + "img/reponse_plus.png";
					img0.title = "publier un nouvel article";
					img0.addEventListener("click", () => createReponse(false, 0, CCN.idRestreint));
					wrapper0.appendChild(img0);
					this.div_base.appendChild(wrapper0);
					this.div_base.style.position = "absolute";
					this.div_base.style.left = "195px";
					this.div_base.style.bottom = "-105px";
					this.div_base.style.visibility = "visible";
				}
				break;
			// bouton plus pour ajout dans le canvas
			case 1:
				this.div_base = document.createElement("div");
				if ((CCN.idRestreint > 0) && (CCN.typeRestreint != '') && (CCN.typeRestreint != 'travail_en_cours')) {
					const wrapper1 = document.createElement("div");
					wrapper1.id = "reponse_plus2";
					wrapper1.className = "bouton_article_plus";
					const img1 = document.createElement("img");
					img1.src = CCN.urlRoot + "img/reponse_plus.png";
					img1.title = "publier un nouvel article";
					img1.addEventListener("click", () => createReponse(false, 0, CCN.idRestreint));
					wrapper1.appendChild(img1);
					this.div_base.appendChild(wrapper1);
					this.div_base.style.position = "absolute";
					this.div_base.style.left = "20px";
					this.div_base.style.bottom = "60px";
					this.div_base.style.visibility = "visible";
				}
				break;
		}
		canvas.appendChild(this.div_base);
	}

	// méthode update
	this.update = function () {
		if (this.opacite < 1) {
			this.opacite += 0.1;
			this.div_base.style.opacity = this.opacite;
		}
	}
}
