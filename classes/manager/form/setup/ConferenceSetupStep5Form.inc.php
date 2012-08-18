<?php

/**
 * @file ConferenceSetupStep5Form.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ConferenceSetupStep5Form
 * @ingroup manager_form_setup
 *
 * @brief Form for Step 5 of conference setup.
 */


import('classes.manager.form.setup.ConferenceSetupForm');

class ConferenceSetupStep5Form extends ConferenceSetupForm {

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
	function ConferenceSetupStep5Form() {
		parent::ConferenceSetupForm(
			5,
			array(
				'searchDescription' => 'string',
				'searchKeywords' => 'string',
				'customHeaders' => 'string'
			)
		);
	}
}

?>
