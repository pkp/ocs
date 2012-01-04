<?php

/**
 * @file rebuildSearchIndex.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class rebuildSearchIndex
 * @ingroup tools
 *
 * @brief CLI tool to rebuild the paper keyword search database.
 */

//$Id$

require(dirname(__FILE__) . '/bootstrap.inc.php');

import('search.PaperSearchIndex');

class rebuildSearchIndex extends CommandLineTool {

	/**
	 * Print command usage information.
	 */
	function usage() {
		echo "Script to rebuild paper search index\n"
			. "Usage: {$this->scriptName}\n";
	}

	/**
	 * Rebuild the search index for all papers in all conferences.
	 */
	function execute() {
		PaperSearchIndex::rebuildIndex(true);
	}

}

$tool = new rebuildSearchIndex(isset($argv) ? $argv : array());
$tool->execute();
?>
