<?php

/**
 * Recherches pour autocomplete
 **/

if (!defined("_ECRIRE_INC_VERSION")) return;

/**
 * data-ajax--url="[(#VAL{select2_autocomplete.api/demo}|url_absolue|attribut_html)]"
 */
function action_api_select2_autocomplete_dist() {

	// SPIP Part
	$arg = _request('arg');
	$quoi = str_replace('/', '_', $arg);
	// $quoi = 'demo' : in select2_autocomplete.api/demo
	// $quoi = 'demo_dogs' : in select2_autocomplete.api/demo/dogs

	// Select 2 Part
	$search = _request('q') ?? '';
	$type = _request('_type');
	$page = _request('page') ?: 1;
	// $type = 'query' or 'query_append' (if paginated request)
	// $page = X (if paginated request)

	// Mininum expects
	/*
	$data = [
		'results' => [
			['id' => 1, 'text' => 'Option 1'],
			['id' => 2, 'text' => 'Option 2'],
			...
		],
		'pagination' => [
			'more' => false
		]
	]
	*/

	// Run
	$res = false;
	if (
		strlen($search)
		&& ($autocomplete = charger_fonction('select2_autocomplete_' . $quoi, 'action', true))
	) {
		$res = $autocomplete($search, $page, 20);
	}
	if (!$res) {
		$res = select2_autocomplete_default();
	}

	include_spip('inc/actions');
	ajax_retour(json_encode($res), 'application/json');
}

function select2_autocomplete_default() {
	return [
		'results' => [],
		'pagination' => [
			'more' => false,
		],
	];
}

function action_select2_autocomplete_demo_city($search, $page = 1, $limit = 20) {
	include_spip('inc/autoriser');
	if (!autoriser('configurer', '_select2')) {
		return false;
	}
	$list = [
		"Aberystwyth", "Armo", "Arvier", "Aydın",
		"Baddeck", "Bahraich", "Barranca", "Baulers", "Beerst", "Belfast", "Belford Roxo", "Bhopal", "Birmingham", "Boechout", "Bokaro Steel City", "Bourlers", "Bromyard",
		"Cap-Saint-Ignace", "Cape Breton Island", "Cappelle sul Tavo", "Cardedu", "Castlegar", "Coldstream", "Comblain-au-Pont", "Corby", "Cranbrook",
		"Deline", "Derby", "Deventer", "Dhuy",
		"El Monte",
		"Farrukhabad-cum-Fatehgarh", "Forge-Philippe",
		"Gateshead", "Gonnosfanadiga", "Grand-RosiŽre-Hottomont", "Grimbergen", "Gujranwala",
		"Hannche",
		"Innsbruck", "Inveraray",
		"Juseret",
		"Krems an der Donau", "Kufstein",
		"Laramie", "Le Grand-Quevilly", "Ledeberg", "Lennik", "Linkebeek", "LiŽge", "Lolol", "Lowestoft", "Lutterworth",
		"Madrid", "Maiolati Spontini", "Maisires", "Maizeret", "Mandela", "Markham", "Marneffe", "Monmouth", "Montignies-sur-Sambre", "Morolo", "Morro d'Alba", "Mumbai",
		"Neder-Over-Heembeek", "North Bay",
		"Otricoli",
		"Paradise", "Paris", "Parla", "Parme",  "Parndorf", "Patalillo", "Peñaflor", "Piracicaba", "Port Alice", "Price", "Provo", "Pune",
		"Rycroft",
		"Saguenay", "Saharanpur", "Saint John", "Saint Louis", "Sant'Angelo a Fasanella", "Santander", "Sheikhupura", "Sint-Gillis-Waas", "Sint-Kwintens-Lennik", "Sint-Lambrechts-Woluwe", "Springdale", "Stevoort", "Stintino", "Surrey",
		"Tintigny", "Treglio",
		"Vichte",
		"Waret-l'Evque", "Whitehaven"
	];
	$results = array_filter($list, function($value) use ($search) {
		return stripos($value, $search) !== false;
	});
	$results = array_chunk($results, $limit);
	$more = isset($results[$page]);
	$results = $results[ $page - 1 ] ?? [];
	$res = [
		"results" => array_map(function($value) {
			return [
				'id' => $value,
				'text' => $value,
			];
		}, $results),
		"pagination" => ["more" => $more]
	];
	return $res;
}

function action_select2_autocomplete_demo_dogs($search, $page = 1, $limit = 20) {
	include_spip('inc/autoriser');
	if (!autoriser('configurer', '_select2')) {
		return false;
	}
	$file = find_in_path('data/fci-breeds.csv');
	$csv = charger_fonction('importer_csv', 'inc');
	$list = $csv($file, true);
	$results = array_filter($list, function($value) use ($search) {
		return stripos($value['name'], $search) !== false;
	});
	$results = array_chunk($results, $limit);
	$more = isset($results[$page]);
	$results = $results[ $page - 1 ] ?? [];
	$res = [
		"results" => array_map(function($value) {
			$value['text'] = $value['name'];
			return $value;
		}, $results),
		"pagination" => ["more" => $more]
	];
	return $res;
}