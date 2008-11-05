<?php

/**
 * @file tools/upgrade.php
 *
 * Copyright (c) 2000-2008 John Willinsky
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


define('INDEX_FILE_LOCATION', dirname(dirname(__FILE__)) . '/index.php');
require(dirname(dirname(__FILE__)) . '/lib/pkp/classes/cliTool/CliTool.inc.php');

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
