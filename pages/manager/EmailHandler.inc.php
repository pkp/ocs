<?php

/**
 * @file EmailHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EmailHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for email management functions. 
 */


import('pages.manager.ManagerHandler');

class EmailHandler extends ManagerHandler {
	/**
	 * Constructor
	 */
	function EmailHandler() {
		parent::ManagerHandler();
	}

	/**
	 * Display a list of the emails within the current conference.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function emails($args, &$request) {
		$this->validate($request);
		$this->setupTemplate($request, true);

		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign('pageHierarchy', array(
			array($request->url(null, 'index', 'manager'), 'manager.conferenceSiteManagement')
		));

		$templateMgr->assign('helpTopicId','conference.generalManagement.emails');
		$templateMgr->display('manager/emails/emails.tpl');
	}

	/**
	 * Validate that user has permissions to manage e-mail templates.
	 * Redirects to user index page if not properly authenticated.
	 * @param $request PKPRequest
	 */
	function validate(&$request) {
		parent::validate();
		
		$schedConf =& $request->getSchedConf();

		// If the user is a Conference Manager, but has specified a scheduled conference,
		// redirect so no scheduled conference is present (otherwise they would end up managing
		// scheduled conference e-mails.)
		if ($schedConf && !Validation::isConferenceManager()) {
			$request->redirect(null, 'index', $request->getRequestedPage(), $request->getRequestedOp());
		}

		return true;
	}
}

?>
