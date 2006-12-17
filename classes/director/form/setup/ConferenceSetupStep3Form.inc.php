<?php

/**
 * ConferenceSetupStep3Form.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package director.form.setup
 *
 * Form for Step 3 of conference setup.
 *
 * $Id$
 */

import("director.form.setup.ConferenceSetupForm");

class ConferenceSetupStep3Form extends ConferenceSetupForm {
	
	function ConferenceSetupStep3Form() {
		parent::ConferenceSetupForm(
			3,
			array(
				'enableLockss' => 'bool',
				'lockssLicense' => 'string',
				'restrictSiteAccess' => 'bool',
				'restrictPaperAccess' => 'bool',
				'enableComments' => 'int',
				'paperEventLog' => 'bool',
				'paperEmailLog' => 'bool',
				'conferenceEventLog' => 'bool'
			)
		);
	}
	
	function readInputData() {
		parent::readInputData();
	}

	function display() {
		$templateMgr = &TemplateManager::getManager();

		// Bring in the comments constants.
		$commentDao = &DAORegistry::getDao('CommentDAO');

		$templateMgr->assign('commentsOptions', array(
			COMMENTS_DISABLED => 'director.setup.comments.disable',
			COMMENTS_AUTHENTICATED => 'director.setup.comments.authenticated',
			COMMENTS_ANONYMOUS => 'director.setup.comments.anonymous',
			COMMENTS_UNAUTHENTICATED => 'director.setup.comments.unauthenticated'
		));

	parent::display();
	}
}

?>
