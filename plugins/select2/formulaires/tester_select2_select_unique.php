<?php

function formulaires_tester_select2_select_unique_charger_dist() {
    if (!autoriser('configurer', '_select2')) {
        return false;
    }
    return [
        'test_basique' => '',
        'test_placeholder_clear' => '',
        'test_sort_alpha' => '',
        'test_ajax_search' => '',
        'test_ajax_search_more' => '',
        'test_ajax_search_clear' => '',
        'test_tags' => '',
        'test_highlight' => '',
        'test_on_enter_submit' => '',
        'test_data_list' => '',
    ];
}

function formulaires_tester_select2_select_unique_verifier_dist() {
    $keys = array_keys(formulaires_tester_select2_select_unique_charger_dist());
    $values = [];
    foreach($keys as $key) {
        $values[$key] = _request($key);
    }

    return [
        'message_erreur' => '',
        'message_ok' => "Le formulaire a été verifié.\n" 
            . propre("```php\n" . var_export($values, true) . "\n```"),
    ];
}