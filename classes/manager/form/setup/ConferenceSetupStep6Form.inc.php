<?php

/**
 * @file ConferenceSetupStep6Form.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form.setup
 * @class ConferenceSetupStep6Form
 *
 * Form for Step 6 of conference setup.
 *
 * $Id$
 */

import("manager.form.setup.ConferenceSetupForm");

class ConferenceSetupStep6Form extends ConferenceSetupForm {
	
	/**
	 * Constructor.
	 */
	function ConferenceSetupStep6Form() {
		parent::ConferenceSetupForm(
			6,
			array(
				'searchDescription' => 'string',
				'searchKeywords' => 'string',
				'customHeaders' => 'string'
			)
		);
	}
}

?>
