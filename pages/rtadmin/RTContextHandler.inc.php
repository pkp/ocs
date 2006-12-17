<?php

/**
 * RTContextHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.rtadmin
 *
 * Handle Reading Tools administration requests -- contexts section.
 *
 * $Id$
 */

import('rt.ocs.ConferenceRTAdmin');

class RTContextHandler extends RTAdminHandler {
	function createContext($args) {
		RTAdminHandler::validate();

		$conference = Request::getConference();

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$versionId = isset($args[0])?$args[0]:0;
		$version = &$rtDao->getVersion($versionId, $conference->getConferenceId());

		import('rt.ocs.form.ContextForm');
		$contextForm = &new ContextForm(null, $versionId);

		if (isset($args[1]) && $args[1]=='save') {
			$contextForm->readInputData();
			$contextForm->execute();
			Request::redirect(null, null, null, 'contexts', $versionId);
		} else {
			RTAdminHandler::setupTemplate(true, $version);
			$contextForm->display();
		}
	}

	function contexts($args) {
		RTAdminHandler::validate();

		$conference = Request::getConference();

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$rangeInfo = Handler::getRangeInfo('contexts');
		
		$versionId = isset($args[0])?$args[0]:0;
		$version = &$rtDao->getVersion($versionId, $conference->getConferenceId());

		if ($version) {
			RTAdminHandler::setupTemplate(true, $version);

			$templateMgr = &TemplateManager::getManager();

			$templateMgr->assign_by_ref('version', $version);

			$templateMgr->assign_by_ref('contexts', new ArrayItemIterator($version->getContexts(), $rangeInfo->getPage(), $rangeInfo->getCount()));

			$templateMgr->assign('helpTopicId', 'conference.managementPages.readingTools.contexts');
			$templateMgr->display('rtadmin/contexts.tpl');
		}
		else Request::redirect(null, null, null, 'versions');
	}

	function editContext($args) {
		RTAdminHandler::validate();

		$rtDao = &DAORegistry::getDAO('RTDAO');

		$conference = Request::getConference();
		$versionId = isset($args[0])?$args[0]:0;
		$version = &$rtDao->getVersion($versionId, $conference->getConferenceId());
		$contextId = isset($args[1])?$args[1]:0;
		$context = &$rtDao->getContext($contextId);

		if (isset($version) && isset($context) && $context->getVersionId() == $version->getVersionId()) {
			import('rt.ocs.form.ContextForm');
			RTAdminHandler::setupTemplate(true, $version, $context);
			$contextForm = &new ContextForm($contextId, $versionId);
			$contextForm->initData();
			$contextForm->display();
		}
		else Request::redirect(null, null, null, 'contexts', $versionId);


	}

	function deleteContext($args) {
		RTAdminHandler::validate();

		$rtDao = &DAORegistry::getDAO('RTDAO');

		$conference = Request::getConference();
		$versionId = isset($args[0])?$args[0]:0;
		$version = &$rtDao->getVersion($versionId, $conference->getConferenceId());
		$contextId = isset($args[1])?$args[1]:0;
		$context = &$rtDao->getContext($contextId);

		if (isset($version) && isset($context) && $context->getVersionId() == $version->getVersionId()) {
			$rtDao->deleteContext($contextId, $versionId);
		}

		Request::redirect(null, null, null, 'contexts', $versionId);
	}

	function saveContext($args) {
		RTAdminHandler::validate();

		$rtDao = &DAORegistry::getDAO('RTDAO');

		$conference = Request::getConference();
		$versionId = isset($args[0])?$args[0]:0;
		$version = &$rtDao->getVersion($versionId, $conference->getConferenceId());
		$contextId = isset($args[1])?$args[1]:0;
		$context = &$rtDao->getContext($contextId);

		if (isset($version) && isset($context) && $context->getVersionId() == $version->getVersionId()) {
			import('rt.ocs.form.ContextForm');
			$contextForm = &new ContextForm($contextId, $versionId);
			$contextForm->readInputData();
			$contextForm->execute();
		}

		Request::redirect(null, null, null, 'contexts', $versionId);
	}

	function moveContext($args) {
		RTAdminHandler::validate();

		$rtDao = &DAORegistry::getDAO('RTDAO');

		$conference = Request::getConference();
		$versionId = isset($args[0])?$args[0]:0;
		$version = &$rtDao->getVersion($versionId, $conference->getConferenceId());
		$contextId = isset($args[1])?$args[1]:0;
		$context = &$rtDao->getContext($contextId);

		if (isset($version) && isset($context) && $context->getVersionId() == $version->getVersionId()) {
			$isDown = Request::getUserVar('dir')=='d';
			$context->setOrder($context->getOrder()+($isDown?1.5:-1.5));
			$rtDao->updateContext($context);
			$rtDao->resequenceContexts($version->getVersionId());
		}

		Request::redirect(null, null, null, 'contexts', $versionId);
	}
}

?>
