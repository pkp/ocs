<?php

/**
 * ConferenceSetupStep2Form.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package director.form.setup
 *
 * Form for Step 2 of conference setup.
 *
 * $Id$
 */

import("director.form.setup.ConferenceSetupForm");
import('event.Event');

class ConferenceSetupStep2Form extends ConferenceSetupForm {
	
	function ConferenceSetupStep2Form() {
		parent::ConferenceSetupForm(
			2,
			array(
				'reviewPolicy' => 'string',
				'reviewGuidelines' => 'string',
				'authorGuidelines' => 'string',
				'submissionChecklist' => 'object',
				'copyrightNotice' => 'string',
				'copyrightNoticeAgree' => 'bool',
				'privacyStatement' => 'string',
				'customAboutItems' => 'object'
			)
		);
	}
}

?>
