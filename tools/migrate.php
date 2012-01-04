<?php

/**
 * @file migrate.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class migrate
 * @ingroup tools
 *
 * @brief CLI tool for migrating OCS 1.x data to OCS 2.
 */

//$Id$

require(dirname(__FILE__) . '/bootstrap.inc.php');

import('site.ImportOCS1');

class migrate extends CommandLineTool {

	/** @var $conferencePath string */
	var $conferencePath;

	/** @var $importPath string */
	var $importPath;

	/** @var $options array */
	var $options;

	/**
	 * Constructor.
	 * @param $argv array command-line arguments
	 */
	function migrate($argv = array()) {
		parent::CommandLineTool($argv);

		if (!isset($this->argv[0]) || !isset($this->argv[1])) {
			$this->usage();
			exit(1);
		}

		$this->conferencePath = $this->argv[0];
		$this->importPath = $this->argv[1];
		$this->options = array_slice($this->argv, 2);
	}

	/**
	 * Print command usage information.
	 */
	function usage() {
		echo "OCS 1 -> OCS 2 migration tool (requires OCS >= 1.1.5 and OCS >= 2.0.1)\n"
			. "Use this tool to import data from an OCS 1 system into an OCS 2 system\n\n"
			. "Usage: {$this->scriptName} [conference_path] [ocs1_path] [options]\n"
			. "conference_path   Conference path to create (E.g., \"ocs\")\n"
			. "                  If path already exists, all content except conference settings\n"
			. "                  will be imported into the existing conference\n"
			. "ocs1_path         Complete local filesystem path to the OCS 1 installation\n"
			. "                  (E.g., \"/var/www/ocs\")\n"
			. "options           importRegistrations - import registration type and registrant\n"
			. "                  data\n"
			. "                  verbose - print additional debugging information\n"
			. "                  emailUsers - Email created users with login information\n";
	}

	/**
	 * Execute the import command.
	 */
	function execute() {
		$importer = new ImportOCS1();
		if ($importer->import($this->conferencePath, $this->importPath, $this->options)) {
			printf("Import completed\n"
					. "Users imported:     %u\n"
					. "Papers imported:  %u\n",
				$importer->userCount,
				$importer->paperCount);
		} else {
			printf("Import failed!\nERROR: %s\n", $importer->error());
		}
	}

}

$tool = new migrate(isset($argv) ? $argv : array());
$tool->execute();
?>
