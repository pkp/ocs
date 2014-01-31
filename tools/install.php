<?php

/**
 * @file tools/install.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class installTool
 * @ingroup tools
 *
 * @brief CLI tool for installing OCS.
 */

//$Id$

require(dirname(__FILE__) . '/bootstrap.inc.php');

import('cliTool.InstallTool');

class OCSInstallTool extends InstallTool {
	/**
	 * Constructor.
	 * @param $argv array command-line arguments
	 */
	function OCSInstallTool($argv = array()) {
		parent::InstallTool($argv);
	}

	/**
	 * Read installation parameters from stdin.
	 * FIXME: May want to implement an abstract "CLIForm" class handling input/validation.
	 * FIXME: Use readline if available?
	 */
	function readParams() {
		AppLocale::requireComponents(array(LOCALE_COMPONENT_PKP_INSTALLER, LOCALE_COMPONENT_APPLICATION_COMMON, LOCALE_COMPONENT_PKP_USER));
		printf("%s\n", __('installer.ocsInstallation'));

		parent::readParams();

		$this->readParamBoolean('install', 'installer.installApplication');

		return $this->params['install'];
	}
}

$tool = new OCSInstallTool(isset($argv) ? $argv : array());
$tool->execute();

?>
