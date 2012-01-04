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

//$Id$


import('handler.Handler');

class InformationHandler extends Handler {
	/**
	 * Constructor
	 **/
	function InformationHandler() {
		parent::Handler();
	}

	/**
	 * Display the information page for the conference..
	 */
	function index($args) {
		$this->validate();
		$this->setupTemplate();
		
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$schedConfContent = null;

		if ($conference == null) {
			Request::redirect('index');
			return;
		}

		switch(isset($args[0])?$args[0]:null) {
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
				Request::redirect($conference->getPath());
				return;
		}

		$templateMgr =& TemplateManager::getManager();

		if($schedConf) {
			$templateMgr->assign('schedConfTitle', $schedConf->getFullTitle());
		}
		$templateMgr->assign('conferenceTitle', $conference->getConferenceTitle());

		$templateMgr->assign('pageCrumbTitle', $pageCrumbTitle);
		$templateMgr->assign('pageTitle', $pageTitle);
		$templateMgr->assign('conferenceContent', $conferenceContent);
		$templateMgr->assign('schedConfContent', $schedConfContent);
		$templateMgr->display('information/information.tpl');
	}

	function readers() {
		$this->index(array('readers'));
	}

	function authors() {
		$this->index(array('authors'));
	}

	/**
	 * Initialize the template.
	 */
	function setupTemplate() {
		parent::setupTemplate();
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->setCacheability(CACHEABILITY_PUBLIC);
	}
}

?>
