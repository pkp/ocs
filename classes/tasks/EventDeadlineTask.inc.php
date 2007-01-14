<?php

/**
 * EventDeadlineTask.inc.php
 *
 * Copyright (c) 2006-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Class to shift event status after a deadline (submission, etc) has passed.
 *
 * $Id$
 */

import('scheduledTask.ScheduledTask');

class EventDeadlineTask extends ScheduledTask {

	/**
	 * Constructor.
	 */
	function EventDeadlineTask() {
		$this->ScheduledTask();
	}

	function execute() {

		$time = time();
		
		// For each enabled event, check deadlines.
		$eventDao = &DAORegistry::getDAO('EventDAO');
		$enabledEvents =& $eventDao->getEnabledEvents();

		$events =& $enabledEvents->toArray();
		foreach($events as $event) {
		}
	}
}

?>
