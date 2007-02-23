<?php

/**
 * ConferenceSetupStep3Form.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form.setup
 *
 * Form for Step 3 of conference setup.
 *
 * $Id$
 */

import("manager.form.setup.ConferenceSetupForm");

class ConferenceSetupStep3Form extends ConferenceSetupForm {
	
	function ConferenceSetupStep3Form() {
		parent::ConferenceSetupForm(
			3,
			array(
				'homeHeaderTitleType' => 'int',
				'homeHeaderTitle' => 'string',
				'homeHeaderTitleTypeAlt1' => 'int',
				'homeHeaderTitleAlt1' => 'string',
				'homeHeaderTitleTypeAlt2' => 'int',
				'homeHeaderTitleAlt2' => 'string',
				'pageHeaderTitleType' => 'int',
				'pageHeaderTitle' => 'string',
				'pageHeaderTitleTypeAlt1' => 'int',
				'pageHeaderTitleAlt1' => 'string',
				'pageHeaderTitleTypeAlt2' => 'int',
				'pageHeaderTitleAlt2' => 'string',
				'navItems' => 'object',
				'conferencePageHeader' => 'string',
				'conferencePageFooter' => 'string',
				'itemsPerPage' => 'int',
				'numPageLinks' => 'int'
			)
		);
	}
	
	function readInputData() {
		parent::readInputData();
	}

	/**
	 * Display the form.
	 */
	function display() {
		$conference = &Request::getConference();

		// Ensure upload file settings are reloaded when the form is displayed.
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign(array(
			'homeHeaderTitleImage' => $conference->getSetting('homeHeaderTitleImage'),
			'homeHeaderLogoImage'=> $conference->getSetting('homeHeaderLogoImage'),
			'homeHeaderTitleImageAlt1' => $conference->getSetting('homeHeaderTitleImageAlt1'),
			'homeHeaderLogoImageAlt1'=> $conference->getSetting('homeHeaderLogoImageAlt1'),
			'homeHeaderTitleImageAlt2' => $conference->getSetting('homeHeaderTitleImageAlt2'),
			'homeHeaderLogoImageAlt2'=> $conference->getSetting('homeHeaderLogoImageAlt2'),
			'pageHeaderTitleImage' => $conference->getSetting('pageHeaderTitleImage'),
			'pageHeaderLogoImage' => $conference->getSetting('pageHeaderLogoImage'),
			'pageHeaderTitleImageAlt1' => $conference->getSetting('pageHeaderTitleImageAlt1'),
			'pageHeaderLogoImageAlt1' => $conference->getSetting('pageHeaderLogoImageAlt1'),
			'pageHeaderTitleImageAlt2' => $conference->getSetting('pageHeaderTitleImageAlt2'),
			'pageHeaderLogoImageAlt2' => $conference->getSetting('pageHeaderLogoImageAlt2'),
			'homepageImage' => $conference->getSetting('homepageImage')
		));
		
		parent::display();	   
	}
	
/*	function display() {
		$templateMgr = &TemplateManager::getManager();

		// Bring in the comments constants.
		$commentDao = &DAORegistry::getDao('CommentDAO');

		$templateMgr->assign('commentsOptions', array(
			COMMENTS_DISABLED => 'manager.setup.comments.disable',
			COMMENTS_AUTHENTICATED => 'manager.setup.comments.authenticated',
			COMMENTS_ANONYMOUS => 'manager.setup.comments.anonymous',
			COMMENTS_UNAUTHENTICATED => 'manager.setup.comments.unauthenticated'
		));

	parent::display();
	} */
}

?>
