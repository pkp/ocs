<?php

/**
 * @file pages/rtadmin/RTContextHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RTContextHandler
 * @ingroup pages_rtadmin
 *
 * @brief Handle Reading Tools administration requests -- contexts section.
 */


import('classes.rt.ocs.ConferenceRTAdmin');
import('pages.rtadmin.RTAdminHandler');

class RTContextHandler extends RTAdminHandler {
	/**
	 * Constructor
	 */
	function RTContextHandler() {
		parent::RTAdminHandler();
	}

	function createContext($args, &$request) {
		$this->validate();

		$conference = $request->getConference();

		$rtDao = DAORegistry::getDAO('RTDAO');
		$versionId = isset($args[0])?$args[0]:0;
		$version =& $rtDao->getVersion($versionId, $conference->getId());

		import('classes.rt.ocs.form.ContextForm');
		$contextForm = new ContextForm(null, $versionId);

		if (isset($args[1]) && $args[1]=='save') {
			$contextForm->readInputData();
			$contextForm->execute();
			$request->redirect(null, null, null, 'contexts', $versionId);
		} else {
			$this->setupTemplate($request, true, $version);
			$contextForm->display();
		}
	}

	function contexts($args, &$request) {
		$this->validate();

		$conference = $request->getConference();

		$rtDao = DAORegistry::getDAO('RTDAO');
		$rangeInfo = $this->getRangeInfo($request, 'contexts');

		$versionId = isset($args[0])?$args[0]:0;
		$version =& $rtDao->getVersion($versionId, $conference->getId());

		if ($version) {
			$this->setupTemplate($request, true, $version);

			$templateMgr =& TemplateManager::getManager($request);
			$templateMgr->addJavaScript('lib/pkp/js/lib/jquery/plugins/jquery.tablednd.js');
			$templateMgr->addJavaScript('lib/pkp/js/functions/tablednd.js');

			$templateMgr->assign_by_ref('version', $version);

			import('lib.pkp.classes.core.ArrayItemIterator');
			$templateMgr->assign('contexts', new ArrayItemIterator($version->getContexts(), $rangeInfo->getPage(), $rangeInfo->getCount()));

			$templateMgr->assign('helpTopicId', 'conference.generalManagement.readingTools.contexts');
			$templateMgr->display('rtadmin/contexts.tpl');
		}
		else $request->redirect(null, null, null, 'versions');
	}

	function editContext($args, &$request) {
		$this->validate();

		$rtDao = DAORegistry::getDAO('RTDAO');

		$conference = $request->getConference();
		$versionId = isset($args[0])?$args[0]:0;
		$version =& $rtDao->getVersion($versionId, $conference->getId());
		$contextId = isset($args[1])?$args[1]:0;
		$context =& $rtDao->getContext($contextId);

		if (isset($version) && isset($context) && $context->getVersionId() == $version->getVersionId()) {
			import('classes.rt.ocs.form.ContextForm');
			$this->setupTemplate($request, true, $version, $context);
			$contextForm = new ContextForm($contextId, $versionId);
			$contextForm->initData();
			$contextForm->display();
		}
		else $request->redirect(null, null, null, 'contexts', $versionId);


	}

	function deleteContext($args, &$request) {
		$this->validate();

		$rtDao = DAORegistry::getDAO('RTDAO');

		$conference = $request->getConference();
		$versionId = isset($args[0])?$args[0]:0;
		$version =& $rtDao->getVersion($versionId, $conference->getId());
		$contextId = isset($args[1])?$args[1]:0;
		$context =& $rtDao->getContext($contextId);

		if (isset($version) && isset($context) && $context->getVersionId() == $version->getVersionId()) {
			$rtDao->deleteContext($contextId, $versionId);
		}

		$request->redirect(null, null, null, 'contexts', $versionId);
	}

	function saveContext($args, &$request) {
		$this->validate();

		$rtDao = DAORegistry::getDAO('RTDAO');

		$conference = $request->getConference();
		$versionId = isset($args[0])?$args[0]:0;
		$version =& $rtDao->getVersion($versionId, $conference->getId());
		$contextId = isset($args[1])?$args[1]:0;
		$context =& $rtDao->getContext($contextId);

		if (isset($version) && isset($context) && $context->getVersionId() == $version->getVersionId()) {
			import('classes.rt.ocs.form.ContextForm');
			$contextForm = new ContextForm($contextId, $versionId);
			$contextForm->readInputData();
			$contextForm->execute();
		}

		$request->redirect(null, null, null, 'contexts', $versionId);
	}

	function moveContext($args, &$request) {
		$this->validate();

		$rtDao = DAORegistry::getDAO('RTDAO');

		$conference = $request->getConference();
		$versionId = isset($args[0])?$args[0]:0;
		$version =& $rtDao->getVersion($versionId, $conference->getId());
		$contextId = isset($args[1])?$args[1]:0;
		$context =& $rtDao->getContext($contextId);

		if (isset($version) && isset($context) && $context->getVersionId() == $version->getVersionId()) {
			$isDown = $request->getUserVar('dir')=='d';
			$context->setOrder($context->getOrder()+($isDown?1.5:-1.5));
			$rtDao->updateContext($context);
			$rtDao->resequenceContexts($version->getVersionId());
		}

		$request->redirect(null, null, null, 'contexts', $versionId);
	}
}

?>
