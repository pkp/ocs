<?php

/**
 * @file ConferenceSetupStep6Form.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ConferenceSetupStep6Form
 * @ingroup manager_form_setup
 *
 * @brief Form for Step 6 of conference setup.
 */

//$Id$

import("manager.form.setup.ConferenceSetupForm");

class ConferenceSetupStep6Form extends ConferenceSetupForm {

	/**
	 * Get the list of field names for which localized settings are used.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('searchDescription', 'searchKeywords', 'customHeaders');
	}

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
