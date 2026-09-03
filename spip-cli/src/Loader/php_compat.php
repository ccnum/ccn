<?php

/**
 * Fix demarrer un vieux SPIP en cli dans un PHP 7.x
 */
if (!function_exists('set_magic_quotes_runtime')) {
    function set_magic_quotes_runtime() {}
}

if (!function_exists('get_magic_quotes_gpc')) {
    function get_magic_quotes_gpc() {}
}
