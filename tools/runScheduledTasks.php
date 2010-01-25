<?php

/**
 * @file tools/runScheduledTasks.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class runScheduledTasks
 * @ingroup tools
 *
 * @brief CLI tool to execute a set of scheduled tasks.
 */

// $Id$


require(dirname(__FILE__) . '/bootstrap.inc.php');

import('cliTool.ScheduledTaskTool');

class runScheduledTasks extends ScheduledTaskTool {
	/**
	 * Constructor.
	 * @param $argv array command-line arguments
	 * 		If specified, the first parameter should be the path to
	 *		a tasks XML descriptor file (other than the default)
	 */
	function runScheduledTasks($argv = array()) {
		parent::ScheduledTaskTool($argv);
	}

}

$tool = new runScheduledTasks(isset($argv) ? $argv : array());
$tool->execute();

?>
