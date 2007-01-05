<?php

/**
 * InformationHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.information
 *
 * Display conference information.
 *
 * $Id$
 */

class InformationHandler extends Handler {

	/**
	 * Display the information page for the conference..
	 */
	function index($args) {
		parent::validate();
		$conference = Request::getConference();

		if ($conference == null) {
			Request::redirect('index');
			return;
		}

		switch(isset($args[0])?$args[0]:null) {
			case 'readers':
				$content = $conference->getSetting('readerInformation');
				$pageTitle = 'navigation.infoForReaders.long';
				$pageCrumbTitle = 'navigation.infoForReaders';
				break;
			case 'presenters':
				$content = $conference->getSetting('presenterInformation');
				$pageTitle = 'navigation.infoForPresenters.long';
				$pageCrumbTitle = 'navigation.infoForPresenters';
				break;
			default:
				Request::redirect($conference->getPath());
				return;
		}

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageCrumbTitle', $pageCrumbTitle);
		$templateMgr->assign('pageTitle', $pageTitle);
		$templateMgr->assign('content', $content);
		$templateMgr->display('information/information.tpl');
	}

	function readers() {
		InformationHandler::index(array('readers'));
	}

	function presenters() {
		InformationHandler::index(array('presenters'));
	}
}

?>
