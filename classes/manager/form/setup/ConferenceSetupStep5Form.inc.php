<?php

/**
 * @file ConferenceSetupStep5Form.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form.setup
 * @class ConferenceSetupStep5Form
 *
 * Form for Step 5 of conference setup.
 *
 * $Id$
 */

import("manager.form.setup.ConferenceSetupForm");

class ConferenceSetupStep5Form extends ConferenceSetupForm {
	/**
	 * Constructor.
	 */
	function ConferenceSetupStep5Form() {
		parent::ConferenceSetupForm(
			5,
			array(
				'paperEventLog' => 'bool',
				'paperEmailLog' => 'bool',
				'conferenceEventLog' => 'bool'
			)
		);
	}
}

?>
