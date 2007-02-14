<?php

/**
 * SchedConfDeadlineTask.inc.php
 *
 * Copyright (c) 2006-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Class to shift scheduled conference status after a deadline (submission, etc) has passed.
 *
 * $Id$
 */

import('scheduledTask.ScheduledTask');

class SchedConfDeadlineTask extends ScheduledTask {

	/**
	 * Constructor.
	 */
	function SchedConfDeadlineTask() {
		$this->ScheduledTask();
	}

	function execute() {

		$time = time();
		
		// For each enabled scheduled conference, check deadlines.
		$schedConfDao = &DAORegistry::getDAO('SchedConfDAO');
		$enabledSchedConfs =& $schedConfDao->getEnabledSchedConfs();

		$schedConfs =& $enabledSchedConfs->toArray();
		foreach($schedConfs as $schedConf) {
		}
	}
}

?>
