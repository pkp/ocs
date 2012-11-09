<?php

/**
 * @file InformationHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class InformationHandler
 * @ingroup pages_information
 *
 * @brief Display conference information.
 */



import('classes.handler.Handler');

class InformationHandler extends Handler {
	/**
	 * Constructor
	 */
	function InformationHandler() {
		parent::Handler();
	}

	/**
	 * Display the information page for the conference.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function index($args, &$request) {
		$this->validate();
		$this->setupTemplate($request);
		
		$conference =& $request->getConference();
		if (!$conference) $request->redirect('index');

		switch(array_shift($args)) {
			case 'readers':
				$conferenceContent = $conference->getLocalizedSetting('readerInformation');
				$pageTitle = 'navigation.infoForReaders.long';
				$pageCrumbTitle = 'navigation.infoForReaders';
				break;
			case 'authors':
				$conferenceContent = $conference->getLocalizedSetting('authorInformation');
				$pageTitle = 'navigation.infoForAuthors.long';
				$pageCrumbTitle = 'navigation.infoForAuthors';
				break;
			default:
				$request->redirect($conference->getPath());
		}

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageCrumbTitle', $pageCrumbTitle);
		$templateMgr->assign('pageTitle', $pageTitle);
		$templateMgr->assign('content', $conferenceContent);
		$templateMgr->display('information/information.tpl');
	}

	function readers($args, &$request) {
		$this->index(array('readers'), $request);
	}

	function authors($args, &$request) {
		$this->index(array('authors'), $request);
	}

	/**
	 * Initialize the template.
	 */
	function setupTemplate($request) {
		parent::setupTemplate($request);
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->setCacheability(CACHEABILITY_PUBLIC);
	}
}

?>
