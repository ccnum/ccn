<?php

namespace Spip\Cli\Plugins;

/**
 * Méthodes communes entre différentes commandes.
 */
trait ErrorsTrait
{
    /**
     * Retourne un tableau ['message d’erreur' => [liste détaillée]]
     *
     * @return array
     */
    protected function getPluginsErrors()
    {
        $alertes = [];
        if (
            isset($GLOBALS['meta']['message_crash_plugins'])
            && $GLOBALS['meta']['message_crash_plugins']
            && is_array($msg = unserialize($GLOBALS['meta']['message_crash_plugins']))
        ) {
            $msg = implode(', ', array_map('joli_repertoire', array_keys($msg)));
            $msg = _T('plugins_erreur', ['plugins' => $msg]);
            $msg = $this->html_entity_to_utf8($msg);
            $alertes[$msg] = [];
        }
        if (isset($GLOBALS['meta']['plugin_erreur_activation'])) {
            include_spip('inc/plugin');
            $erreurs = plugin_donne_erreurs(true);
            foreach ($erreurs as $plugin => $liste) {
                $msg = _T('plugin_impossible_activer', ['plugin' => $plugin]);
                $msg = $this->html_entity_to_utf8($msg);
                $alertes[$msg] = $this->html_entity_to_utf8($liste);
            }
        }
        return $alertes;
    }

    protected function showPluginsErrors()
    {
        if ($erreurs = $this->getPluginsErrors()) {
            $this->io->error('Des erreurs sont présentes');
            foreach ($erreurs as $msg => $details) {
                $this->io->fail($msg);
                $this->io->listing($details, 2);
            }
        }
    }

    /**
     * Transforme les &gt; en >
     */
    protected function html_entity_to_utf8($msg)
    {
        if (is_array($msg)) {
            return array_map([$this, 'html_entity_to_utf8'], $msg);
        }
        return html_entity_decode($msg, ENT_COMPAT | ENT_HTML401, 'UTF-8');
    }
}
