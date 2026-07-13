function save_mot(id_objet, type_objet, id_mot) {
	$.post("spip.php?page=ajax&mode=article-sauve-mot", { id_objet: id_objet, type_objet: type_objet, id_mot: id_mot });
}
