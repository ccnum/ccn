<?php

/**
 * Liste les options globales disponibles dans saisies,
 * utilisée dans le constructeur de formulaire
 * (et potentiellement à terme dans un outil d'autodoc).
 * Permet notamment d'utiliser la syntaxe `@_options_globales[truc]`
 * dans les `afficher_si` des constructeurs de saisies sans provoquer de warning
 * / d'annulation de l'`afficher_si`
 * Retourne à tableau cle (nom de l'option) => [] (à compléter, potentiellement
 * la doc
**/
function saisies_options_globales_lister_disponibles(): array {
	return [
		'previsualisation_mode' => [],
		'texte_submit' => [],
		'afficher_si_submit' => [],
		'squelettes_boutons' => [],
		'etapes_activer' => [],
		'etapes_presentation' => [],
		'etapes_suivant' => [],
		'etapes_precedent' => [],
		'etapes_precedent_suivant_titrer' => [],
		'etapes_ignorer_recapitulatif' => [],
		'ajax' => [],
		'conteneur_class' => [],
		'conteneur_id' => [],
		'inserer_debut' => [],
		'inserer_fin' => [],
		'prefixe_id' => [],
		'verifier_valeurs_acceptables' => [],
		'afficher_si_avec_post' => [],
		'obligatoire_defaut' => [],
		'inserer_a_la_place' => [],
	];
}
