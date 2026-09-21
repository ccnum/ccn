<?php

function formulaires_tester_select2_input_multiple_charger_dist() {
    if (!autoriser('configurer', '_select2')) {
        return false;
    }
    return [
        'test_list' => '',
        'test_list_labeled' => '',
        'test_ajax' => '',
        'test_datalist' => '',
    ];
}

function formulaires_tester_select2_input_multiple_verifier_dist() {
    $keys = array_keys(formulaires_tester_select2_input_multiple_charger_dist());
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