<?php

# A variant of the plugin DatabaseHide

/** Hide some databases from the interface - just to improve design, not a security plugin
* @link https://www.adminer.org/plugins/#use
* @author Jakub Vrana, https://www.vrana.cz/
* @license https://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
* @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2 (one or other)
*/
class AdminerAllowedDatabases extends Adminer\Plugin  {
	protected $visible;

	/**
	* @param list<string> $disabled case insensitive database names in values
	*/
	function __construct($visible) {
		$this->visible = array_map('strtolower', $visible);
	}

	function databases($flush = true) {
		$return = [];
		foreach (Adminer\get_databases($flush) as $db) {
			if (in_array(strtolower($db), $this->visible)) {
				$return[] = $db;
			}
		}
		return $return;
	}

}