<?php

/**
 * EventSetupStep4Form.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package director.form.eventSetup
 *
 * Form for Step 4 of event setup.
 *
 * $Id$
 */

import("director.form.eventSetup.EventSetupForm");

class EventSetupStep4Form extends EventSetupForm {
	
	function EventSetupStep4Form() {
		parent::EventSetupForm(
			4,
			array(
				'enableRegistration' => 'bool',
				'openAccessPolicy' => 'string',
				'registrationName' => 'string',
				'registrationEmail' => 'string',
				'registrationPhone' => 'string',
				'registrationFax' => 'string',
				'registrationMailingAddress' => 'string',
			)
		);
	}

	function initData() {
		parent::initData();

		$event = &Request::getEvent();

		$this->_data['requireRegReader'] = !$event->getSetting('openAccessVisitor', false);
	}

	function readInputData() {
		parent::readInputData();
		
		if($this->_data['enableRegistration']) {
			$this->_data['openAccessReader'] = false;
			$this->_data['openAccessVisitor'] = false;
		} else {
			$this->_data['openAccessReader'] = true;
			$this->_data['openAccessVisitor'] = !Request::getUserVar('requireRegReader');
		}
	}

	function execute() {
		$event = Request::getEvent();

		$event->updateSetting('openAccessReader', $this->_data['openAccessReader'], 'bool');
		$event->updateSetting('openAccessVisitor', $this->_data['openAccessVisitor'], 'bool');

		parent::execute();
	}
}

?>
