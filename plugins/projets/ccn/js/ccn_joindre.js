/**
 * Soumet automatiquement le formulaire d'ajout de document
 * (formulaires/ccn_joindre_document.html) une fois l'upload bigup terminé.
 *
 * Ce formulaire n'a pas de bouton "Envoyer" visible (masqué en CSS par
 * chaque plugin) : sans ce déclenchement, rien ne le soumet jamais. Le
 * fichier reste alors "en attente" côté bigup (chunks envoyés,
 * prévisualisation affichée avec son bouton "Enlever") sans jamais être
 * réellement associé à l'objet.
 *
 * "bigup.complete" (cf plugins-dist/bigup/javascript/bigup.js) est déclenché
 * sur le champ <input type=file>, avec le nombre de fichiers envoyés dans
 * cette salve ; on ignore l'évènement à 0 fichier (déclenché aussi par
 * flow.js quand la file d'attente est vide, ex. juste après un "Enlever").
 *
 * Délégué sur document : le formulaire est réinjecté à chaque rechargement
 * ajax (et à chaque ouverture de popup côté thematique). Ce fichier est
 * chargé par le formulaire lui-même, donc potentiellement plusieurs fois
 * sur la même page : la garde évite de binder le handler en double (ce qui
 * soumettrait le formulaire deux fois).
 */
if (!window.ccnJoindreDocument) {
    window.ccnJoindreDocument = true
    jQuery(document).on("bigup.complete", ".formulaire_ccn_joindre_document input.bigup", function (event, data) {
        if (!data || !data.count) return
        const formulaire = this.closest("form")
        if (formulaire) formulaire.requestSubmit()
    })
}
