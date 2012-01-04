<?php

/**
 * @file tools/upgrade.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class upgradeTool
 * @ingroup tools
 *
 * @brief CLI tool for upgrading OCS.
 *
 * Note: Some functions require fopen wrappers to be enabled.
 */

// $Id$


require(dirname(__FILE__) . '/bootstrap.inc.php');

import('cliTool.UpgradeTool');

class OCSUpgradeTool extends UpgradeTool {
	/**
	 * Constructor.
	 * @param $argv array command-line arguments
	 */
	function OCSUpgradeTool($argv = array()) {
		parent::UpgradeTool($argv);
	}
}

$tool = new OCSUpgradeTool(isset($argv) ? $argv : array());
$tool->execute();

?>
