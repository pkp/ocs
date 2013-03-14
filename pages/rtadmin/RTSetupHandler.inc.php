<?php

/**
 * @file pages/manager/RTSetupHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RTSetupHandler
 * @ingroup pages_rtadmin
 *
 * @brief Handle Reading Tools administration requests -- setup section.
 */


import('classes.rt.ocs.ConferenceRTAdmin');
import('pages.rtadmin.RTAdminHandler');

class RTSetupHandler extends RTAdminHandler {
	/**
	 * Constructor
	 */
	function RTSetupHandler() {
		parent::RTAdminHandler();
	}

	function settings($args, &$request) {
		$this->validate();

		$conference = $request->getConference();

		if ($conference) {
			$this->setupTemplate($request, true);
			$templateMgr =& TemplateManager::getManager($request);

			$rtDao = DAORegistry::getDAO('RTDAO');
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
			$templateMgr->assign('viewReviewPolicy', $rt->getviewReviewPolicy());
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
			$request->redirect(null, null, $request->getRequestedPage());
		}
	}

	function saveSettings($args, &$request) {
		$this->validate();

		$conference = $request->getConference();

		if ($conference) {
			$rtDao = DAORegistry::getDAO('RTDAO');
			$rt = $rtDao->getConferenceRTByConference($conference);

			if ($request->getUserVar('version')=='') $rt->setVersion(null);
			else $rt->setVersion($request->getUserVar('version'));
			$rt->setEnabled($request->getUserVar('enabled')==true);
			$rt->setAbstract($request->getUserVar('abstract')==true);
			$rt->setViewReviewPolicy($request->getUserVar('viewReviewPolicy')==true);
			$rt->setCaptureCite($request->getUserVar('captureCite')==true);
			$rt->setViewMetadata($request->getUserVar('viewMetadata')==true);
			$rt->setSupplementaryFiles($request->getUserVar('supplementaryFiles')==true);
			$rt->setPrinterFriendly($request->getUserVar('printerFriendly')==true);
			$rt->setAuthorBio($request->getUserVar('authorBio')==true);
			$rt->setDefineTerms($request->getUserVar('defineTerms')==true);
			$rt->setAddComment($request->getUserVar('addComment')==true);
			$rt->setEmailAuthor($request->getUserVar('emailAuthor')==true);
			$rt->setEmailOthers($request->getUserVar('emailOthers')==true);
			$rt->setFindingReferences($request->getUserVar('findingReferences')==true);

			$rtDao->updateConferenceRT($rt);
		}
		$request->redirect(null, null, $request->getRequestedPage());
	}
}

?>
