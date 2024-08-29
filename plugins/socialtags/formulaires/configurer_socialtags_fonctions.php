<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function socialtags_choix() {
	include_spip('socialtags_fonctions');

	$cfg = is_array($cfg = lire_config('socialtags/tags')) ? $cfg : [];

	$retour = [];
	foreach (socialtags_liste() as $service) {
		$t = $service['titre'];
		$u = $service['url'];
		$a = $service['lesauteurs'];
		$d = $service['descriptif'] ?? '';

		$category = (count($service['tags'])?textebrut(reset($service['tags'])):'99');
		$image = '';
		$img = find_in_path('images/' . $a . '.png');
		if ($img) {
			$image = 'data:image/png;base64,'.base64_encode(file_get_contents($img));
		}
		$checked = in_array($a, $cfg) ? ' checked="checked"' : '';

		if (!isset($retour[$category])) {
			$retour[$category] = '';
		}
		$retour[$category] .= "<div class='choix'>
				<input type='checkbox' id='choix_{$a}' name='tags[]' value='{$a}'{$checked} />
				<label for='choix_{$a}'>
					<img src=\"{$image}\" title=\"" . texte_script($t) . '" alt="" style="max-width:16px; height:auto;" />
					' . ($checked ? "<strong>$t</strong>" : $t)
					. ($d ? "&nbsp;<small class='socialtags_descriptif'>$d</small>" : '') . '
				</label>
			</div>';
	}
	ksort($retour);
	return "\n<div class='socialtags_checklist'>\n"
		. implode("</div>\n<div class='socialtags_checklist'>\n", $retour)
		. "\n</div>\n";
}
