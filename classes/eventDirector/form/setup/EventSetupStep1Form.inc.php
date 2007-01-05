<?php

/**
 * EventSetupStep1Form.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package eventDirector.form.setup
 *
 * Form for Step 1 of event setup.
 *
 * $Id$
 */

import("eventDirector.form.setup.EventSetupForm");

class EventSetupStep1Form extends EventSetupForm {
	
	function EventSetupStep1Form() {
		parent::EventSetupForm(
			1,
			array(
				'eventTitle' => 'string',
				'eventOverview' => 'string',
				'eventIntroduction' => 'string',
				'location' => 'string',
				'sponsorNote' => 'string',
				'sponsors' => 'object',
				'contributorNote' => 'string',
				'contributors' => 'object'
			)
		);
		
		// Validation checks for this form
		$this->addCheck(new FormValidator($this, 'eventTitle', 'required', 'eventDirector.setup.form.eventTitleRequired'));

		// TODO: Validation checks for event start and end date
	}

	function initData() {
		parent::initData();

		$event = &Request::getEvent();
		$this->_data['eventTitle'] = $event->getTitle();
	}

	function readInputData() {
		parent::readInputData();
		$this->_data['eventTitle'] = Request::getUserVar('eventTitle');
	}

	function execute() {
		$eventDao = &DAORegistry::getDAO('EventDAO');
		$event = Request::getEvent();

		$event->setTitle($this->_data['eventTitle']);
		$eventDao->updateEvent($event);

		parent::execute();
	}
}

?>
