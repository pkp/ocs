<?php

/**
 * @file pages/manager/ManagerHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ManagerHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for conference management functions.
 */


import('classes.handler.Handler');

class ManagerHandler extends Handler {
	/**
	 * Constructor
	 */
	function ManagerHandler() {
		parent::Handler();

		$this->addCheck(new HandlerValidatorConference($this));
		$this->addCheck(new HandlerValidatorRoles($this, true, null, null, array(ROLE_ID_SITE_ADMIN, ROLE_ID_MANAGER)));
	}

	/**
	 * Display conference management index page.
	 */
	function index($args, &$request) {
		// Manager requests should come to the Conference context, not Sched Conf
		if ($request->getRequestedSchedConfPath() != 'index') $request->redirect(null, 'index', 'manager');
		$this->validate();
		$this->setupTemplate($request);

		$conference =& $request->getConference();
		$templateMgr =& TemplateManager::getManager($request);

		// Display a warning message if there is a new version of OJS available
		$newVersionAvailable = false;
		if (Config::getVar('general', 'show_upgrade_warning')) {
			import('lib.pkp.classes.site.VersionCheck');
			if($latestVersion = VersionCheck::checkIfNewVersionExists()) {
				$newVersionAvailable = true;
				$templateMgr->assign('latestVersion', $latestVersion);
				$currentVersion =& VersionCheck::getCurrentDBVersion();
				$templateMgr->assign('currentVersion', $currentVersion->getVersionString());
				
				// Get contact information for site administrator
				$roleDao = DAORegistry::getDAO('RoleDAO');
				$siteAdmins =& $roleDao->getUsersByRoleId(ROLE_ID_SITE_ADMIN);
				$templateMgr->assign_by_ref('siteAdmin', $siteAdmins->next());
			}
		}
		$templateMgr->assign('newVersionAvailable', $newVersionAvailable);

		$schedConfDao = DAORegistry::getDAO('SchedConfDAO');
		$schedConfs = $schedConfDao->getAll(false, $conference->getId());
		$templateMgr->assign_by_ref('schedConfs', $schedConfs);

		$templateMgr->assign('announcementsEnabled', $conference->getSetting('enableAnnouncements'));

		$templateMgr->assign('helpTopicId','conference.index');
		$templateMgr->display(ROLE_PATH_MANAGER . '/index.tpl');
	}

	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($request, $subclass = false) {
		parent::setupTemplate($request);
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_MANAGER, LOCALE_COMPONENT_APP_MANAGER, LOCALE_COMPONENT_PKP_ADMIN);
		$templateMgr =& TemplateManager::getManager($request);
		$pageHierarchy = array();

		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();

		if ($schedConf) {
			$pageHierarchy[] = array($request->url(null, null, 'index'), $schedConf->getLocalizedName(), true);
		} elseif ($conference) {
			$pageHierarchy[] = array($request->url(null, 'index', 'index'), $conference->getLocalizedName(), true);
		}

		if ($subclass) {
			$pageHierarchy[] = array($request->url(null, null, 'user'), 'navigation.user');
			$pageHierarchy[] = array($request->url(null, 'index', 'manager'), 'manager.conferenceSiteManagement');
		} else {
			$pageHierarchy[] = array($request->url(null, null, 'user'), 'navigation.user');
		}

		$templateMgr->assign('pageHierarchy', $pageHierarchy);
	}
}

?>
