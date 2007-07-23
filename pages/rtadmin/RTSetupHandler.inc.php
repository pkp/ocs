<?php

/**
 * @file RTSetupHandler.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.rtadmin
 * @class RTSetupHandler
 *
 * Handle Reading Tools administration requests -- setup section.
 *
 * $Id$
 */

import('rt.ocs.ConferenceRTAdmin');

class RTSetupHandler extends RTAdminHandler {

	function settings() {
		RTAdminHandler::validate();

		$conference = Request::getConference();

		if ($conference) {
			RTAdminHandler::setupTemplate(true);
			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign_by_ref('conferences', $conferences);

			$rtDao = &DAORegistry::getDAO('RTDAO');
			$rt = $rtDao->getConferenceRTByConference($conference);

			$versionOptions = array();
			$versions = $rtDao->getVersions($conference->getConferenceId());
			foreach ($versions->toArray() as $version) {
				$versionOptions[$version->getVersionId()] = $version->getTitle();
			}

			$templateMgr->assign('versionOptions', $versionOptions);
			$templateMgr->assign_by_ref('version', $rt->getVersion());
			$templateMgr->assign('enabled', $rt->getEnabled());
			$templateMgr->assign('abstract', $rt->getAbstract());
			$templateMgr->assign('captureCite', $rt->getCaptureCite());
			$templateMgr->assign('viewMetadata', $rt->getViewMetadata());
			$templateMgr->assign('supplementaryFiles', $rt->getSupplementaryFiles());
			$templateMgr->assign('printerFriendly', $rt->getPrinterFriendly());
			$templateMgr->assign('presenterBio', $rt->getPresenterBio());
			$templateMgr->assign('defineTerms', $rt->getDefineTerms());
			$templateMgr->assign('addComment', $rt->getAddComment());
			$templateMgr->assign('emailPresenter', $rt->getEmailPresenter());
			$templateMgr->assign('emailOthers', $rt->getEmailOthers());

			$templateMgr->assign('helpTopicId', 'conference.managementPages.readingTools.settings');
			$templateMgr->display('rtadmin/settings.tpl');
		} else {
			Request::redirect(null, null, Request::getRequestedPage());
		}
	}

	function saveSettings() {
		RTAdminHandler::validate();

		$conference = Request::getConference();

		if ($conference) {
			$rtDao = &DAORegistry::getDAO('RTDAO');
			$rt = $rtDao->getConferenceRTByConference($conference);

			if (Request::getUserVar('version')=='') $rt->setVersion(null);
			else $rt->setVersion(Request::getUserVar('version'));
			$rt->setEnabled(Request::getUserVar('enabled')==true);
			$rt->setAbstract(Request::getUserVar('abstract')==true);
			$rt->setCaptureCite(Request::getUserVar('captureCite')==true);
			$rt->setViewMetadata(Request::getUserVar('viewMetadata')==true);
			$rt->setSupplementaryFiles(Request::getUserVar('supplementaryFiles')==true);
			$rt->setPrinterFriendly(Request::getUserVar('printerFriendly')==true);
			$rt->setPresenterBio(Request::getUserVar('presenterBio')==true);
			$rt->setDefineTerms(Request::getUserVar('defineTerms')==true);
			$rt->setAddComment(Request::getUserVar('addComment')==true);
			$rt->setEmailPresenter(Request::getUserVar('emailPresenter')==true);
			$rt->setEmailOthers(Request::getUserVar('emailOthers')==true);

			$rtDao->updateConferenceRT($rt);
		}
		Request::redirect(null, null, Request::getRequestedPage());
	}
}

?>
