<?php

/**
 * @file RTVersionHandler.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.rtadmin
 * @class RTVersionHandler
 *
 * Handle Reading Tools administration requests -- setup section.
 *
 * $Id$
 */

import('rt.ocs.ConferenceRTAdmin');

class RTVersionHandler extends RTAdminHandler {
	function createVersion($args) {
		RTAdminHandler::validate();

		$rtDao = &DAORegistry::getDAO('RTDAO');

		$conference = Request::getConference();

		import('rt.ocs.form.VersionForm');
		$versionForm = &new VersionForm(null, $conference->getConferenceId());

		if (isset($args[0]) && $args[0]=='save') {
			$versionForm->readInputData();
			$versionForm->execute();
			Request::redirect(null, null, null, 'versions');
		} else {
			RTAdminHandler::setupTemplate(true);
			$versionForm->display();
		}
	}

	function exportVersion($args) {
		RTAdminHandler::validate();

		$rtDao = &DAORegistry::getDAO('RTDAO');

		$conference = Request::getConference();
		$versionId = isset($args[0])?$args[0]:0;
		$version = &$rtDao->getVersion($versionId, $conference->getConferenceId());

		if ($version) {
			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign_by_ref('version', $version);

			$templateMgr->display('rtadmin/exportXml.tpl', 'application/xml');
		}
		else Request::redirect(null, null, null, 'versions');
	}

	function importVersion() {
		RTAdminHandler::validate();
		$conference = &Request::getConference();

		$fileField = 'versionFile';
		if (isset($_FILES[$fileField]['tmp_name']) && is_uploaded_file($_FILES[$fileField]['tmp_name'])) {
			$rtAdmin = &new ConferenceRTAdmin($conference->getConferenceId());
			$rtAdmin->importVersion($_FILES[$fileField]['tmp_name']);
		}
		Request::redirect(null, null, null, 'versions');
	}

	function restoreVersions() {
		RTAdminHandler::validate();

		$conference = &Request::getConference();
		$rtAdmin = &new ConferenceRTAdmin($conference->getConferenceId());
		$rtAdmin->restoreVersions();

		// If the conference RT was configured, change its state to
		// "disabled" because the RT version it was configured for
		// has now been deleted.
		$rtDao = &DAORegistry::getDAO('RTDAO');
		$conferenceRt = $rtDao->getConferenceRTByConference($conference);
		if ($conferenceRt) {
			$conferenceRt->setVersion(null);
			$rtDao->updateConferenceRT($conferenceRt);
		}

		Request::redirect(null, null, null, 'versions');
	}

	function versions() {
		RTAdminHandler::validate();
		RTAdminHandler::setupTemplate(true);

		$conference = Request::getConference();

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$rangeInfo = Handler::getRangeInfo('versions');

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign_by_ref('versions', $rtDao->getVersions($conference->getConferenceId(), $rangeInfo));
		$templateMgr->assign('helpTopicId', 'conference.managementPages.readingTools.versions');
		$templateMgr->display('rtadmin/versions.tpl');
	}

	function editVersion($args) {
		RTAdminHandler::validate();

		$rtDao = &DAORegistry::getDAO('RTDAO');

		$conference = Request::getConference();
		$versionId = isset($args[0])?$args[0]:0;
		$version = &$rtDao->getVersion($versionId, $conference->getConferenceId());

		if (isset($version)) {
			import('rt.ocs.form.VersionForm');
			RTAdminHandler::setupTemplate(true, $version);
			$versionForm = &new VersionForm($versionId, $conference->getConferenceId());
			$versionForm->initData();
			$versionForm->display();
		}
		else Request::redirect(null, null, null, 'versions');
	}

	function deleteVersion($args) {
		RTAdminHandler::validate();

		$rtDao = &DAORegistry::getDAO('RTDAO');

		$conference = Request::getConference();
		$versionId = isset($args[0])?$args[0]:0;

		$rtDao->deleteVersion($versionId, $conference->getConferenceId());

		Request::redirect(null, null, null, 'versions');
	}

	function saveVersion($args) {
		RTAdminHandler::validate();

		$rtDao = &DAORegistry::getDAO('RTDAO');

		$conference = Request::getConference();
		$versionId = isset($args[0])?$args[0]:0;
		$version = &$rtDao->getVersion($versionId, $conference->getConferenceId());

		if (isset($version)) {
			import('rt.ocs.form.VersionForm');
			$versionForm = &new VersionForm($versionId, $conference->getConferenceId());
			$versionForm->readInputData();
			$versionForm->execute();
		}

		Request::redirect(null, null, null, 'versions');
	}
}

?>
