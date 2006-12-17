<?php

/**
 * EventSetupStep4Form.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package eventDirector.form.setup
 *
 * Form for Step 4 of event setup.
 *
 * $Id$
 */

import("eventDirector.form.setup.EventSetupForm");

class EventSetupStep4Form extends EventSetupForm {
	
	function EventSetupStep4Form() {
		parent::EventSetupForm(
			4,
			array(
				'openRegReader' => 'bool',
				'openRegReaderDate' => 'date',
				'closeRegReader' => 'bool',
				'closeRegReaderDate' => 'date',
				'openAccessPolicy' => 'string',
				'enableRegistration' => 'bool',
				'registrationName' => 'string',
				'registrationEmail' => 'string',
				'registrationPhone' => 'string',
				'registrationFax' => 'string',
				'registrationMailingAddress' => 'string',
			)
		);
	}

	function readInputData() {
		parent::readInputData();

		$this->_data['openRegReaderDate'] = Request::getUserDateVar('openRegReaderDate');
		$this->_data['closeRegReaderDate'] = Request::getUserDateVar('closeRegReaderDate');
	}
}

?>
