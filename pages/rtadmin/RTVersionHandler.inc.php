<?php

/**
 * @file pages/manager/RTVersionHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RTVersionHandler
 * @ingroup pages_rtadmin
 *
 * @brief Handle Reading Tools administration requests -- setup section.
 */


import('classes.rt.ocs.ConferenceRTAdmin');
import('pages.rtadmin.RTAdminHandler');

class RTVersionHandler extends RTAdminHandler {
	/**
	 * Constructor
	 */
	function RTVersionHandler() {
		parent::RTAdminHandler();
	}
	
	function createVersion($args, &$request) {
		$this->validate();

		$rtDao = DAORegistry::getDAO('RTDAO');

		$conference = $request->getConference();

		import('classes.rt.ocs.form.VersionForm');
		$versionForm = new VersionForm(null, $conference->getId());

		if (isset($args[0]) && $args[0]=='save') {
			$versionForm->readInputData();
			$versionForm->execute();
			$request->redirect(null, null, null, 'versions');
		} else {
			$this->setupTemplate($request, true);
			$versionForm->display();
		}
	}

	function exportVersion($args, &$request) {
		$this->validate();

		$rtDao = DAORegistry::getDAO('RTDAO');

		$conference = $request->getConference();
		$versionId = isset($args[0])?$args[0]:0;
		$version =& $rtDao->getVersion($versionId, $conference->getId());

		if ($version) {
			$templateMgr =& TemplateManager::getManager($request);
			$templateMgr->assign_by_ref('version', $version);

			$templateMgr->display('rtadmin/exportXml.tpl', 'application/xml');
		}
		else $request->redirect(null, null, null, 'versions');
	}

	function importVersion($args, &$request) {
		$this->validate();
		$conference =& $request->getConference();

		$fileField = 'versionFile';
		if (isset($_FILES[$fileField]['tmp_name']) && is_uploaded_file($_FILES[$fileField]['tmp_name'])) {
			$rtAdmin = new ConferenceRTAdmin($conference->getId());
			$rtAdmin->importVersion($_FILES[$fileField]['tmp_name']);
		}
		$request->redirect(null, null, null, 'versions');
	}

	function restoreVersions($args, &$request) {
		$this->validate();

		$conference =& $request->getConference();
		$rtAdmin = new ConferenceRTAdmin($conference->getId());
		$rtAdmin->restoreVersions();

		// If the conference RT was configured, change its state to
		// "disabled" because the RT version it was configured for
		// has now been deleted.
		$rtDao = DAORegistry::getDAO('RTDAO');
		$conferenceRt = $rtDao->getConferenceRTByConference($conference);
		if ($conferenceRt) {
			$conferenceRt->setVersion(null);
			$rtDao->updateConferenceRT($conferenceRt);
		}

		$request->redirect(null, null, null, 'versions');
	}

	function versions($args, &$request) {
		$this->validate();
		$this->setupTemplate($request, true);

		$conference = $request->getConference();

		$rtDao = DAORegistry::getDAO('RTDAO');
		$rangeInfo = $this->getRangeInfo($request, 'versions');

		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign_by_ref('versions', $rtDao->getVersions($conference->getId(), $rangeInfo));
		$templateMgr->assign('helpTopicId', 'conference.generalManagement.readingTools.versions');
		$templateMgr->display('rtadmin/versions.tpl');
	}

	function editVersion($args, &$request) {
		$this->validate();

		$rtDao = DAORegistry::getDAO('RTDAO');

		$conference = $request->getConference();
		$versionId = isset($args[0])?$args[0]:0;
		$version =& $rtDao->getVersion($versionId, $conference->getId());

		if (isset($version)) {
			import('classes.rt.ocs.form.VersionForm');
			$this->setupTemplate($request, true, $version);
			$versionForm = new VersionForm($versionId, $conference->getId());
			$versionForm->initData();
			$versionForm->display();
		}
		else $request->redirect(null, null, null, 'versions');
	}

	function deleteVersion($args, &$request) {
		$this->validate();

		$rtDao = DAORegistry::getDAO('RTDAO');

		$conference = $request->getConference();
		$versionId = isset($args[0])?$args[0]:0;

		$rtDao->deleteVersion($versionId, $conference->getId());

		$request->redirect(null, null, null, 'versions');
	}

	function saveVersion($args, &$request) {
		$this->validate();

		$rtDao = DAORegistry::getDAO('RTDAO');

		$conference = $request->getConference();
		$versionId = isset($args[0])?$args[0]:0;
		$version =& $rtDao->getVersion($versionId, $conference->getId());

		if (isset($version)) {
			import('classes.rt.ocs.form.VersionForm');
			$versionForm = new VersionForm($versionId, $conference->getId());
			$versionForm->readInputData();
			$versionForm->execute();
		}

		$request->redirect(null, null, null, 'versions');
	}
}

?>
