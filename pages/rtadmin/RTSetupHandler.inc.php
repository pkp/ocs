<?php

/**
 * @file RTSetupHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RTSetupHandler
 * @ingroup pages_rtadmin
 *
 * @brief Handle Reading Tools administration requests -- setup section.
 */

//$Id$

import('rt.ocs.ConferenceRTAdmin');
import('pages.rtadmin.RTAdminHandler');

class RTSetupHandler extends RTAdminHandler {
	/**
	 * Constructor
	 **/
	function RTSetupHandler() {
		parent::RTAdminHandler();
	}

	function settings() {
		$this->validate();

		$conference = Request::getConference();

		if ($conference) {
			$this->setupTemplate(true);
			$templateMgr =& TemplateManager::getManager();

			$rtDao =& DAORegistry::getDAO('RTDAO');
			$rt = $rtDao->getConferenceRTByConference($conference);

			$versionOptions = array();
			$versions = $rtDao->getVersions($conference->getId());
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
			$templateMgr->assign('authorBio', $rt->getAuthorBio());
			$templateMgr->assign('defineTerms', $rt->getDefineTerms());
			$templateMgr->assign('addComment', $rt->getAddComment());
			$templateMgr->assign('emailAuthor', $rt->getEmailAuthor());
			$templateMgr->assign('emailOthers', $rt->getEmailOthers());
			$templateMgr->assign('findingReferences', $rt->getFindingReferences());

			$templateMgr->assign('helpTopicId', 'conference.generalManagement.readingTools.settings');
			$templateMgr->display('rtadmin/settings.tpl');
		} else {
			Request::redirect(null, null, Request::getRequestedPage());
		}
	}

	function saveSettings() {
		$this->validate();

		$conference = Request::getConference();

		if ($conference) {
			$rtDao =& DAORegistry::getDAO('RTDAO');
			$rt = $rtDao->getConferenceRTByConference($conference);

			if (Request::getUserVar('version')=='') $rt->setVersion(null);
			else $rt->setVersion(Request::getUserVar('version'));
			$rt->setEnabled(Request::getUserVar('enabled')==true);
			$rt->setAbstract(Request::getUserVar('abstract')==true);
			$rt->setCaptureCite(Request::getUserVar('captureCite')==true);
			$rt->setViewMetadata(Request::getUserVar('viewMetadata')==true);
			$rt->setSupplementaryFiles(Request::getUserVar('supplementaryFiles')==true);
			$rt->setPrinterFriendly(Request::getUserVar('printerFriendly')==true);
			$rt->setAuthorBio(Request::getUserVar('authorBio')==true);
			$rt->setDefineTerms(Request::getUserVar('defineTerms')==true);
			$rt->setAddComment(Request::getUserVar('addComment')==true);
			$rt->setEmailAuthor(Request::getUserVar('emailAuthor')==true);
			$rt->setEmailOthers(Request::getUserVar('emailOthers')==true);
			$rt->setFindingReferences(Request::getUserVar('findingReferences')==true);

			$rtDao->updateConferenceRT($rt);
		}
		Request::redirect(null, null, Request::getRequestedPage());
	}
}

?>
