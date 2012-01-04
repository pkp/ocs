<?php

/**
 * @file ManagerHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ManagerHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for conference management functions. 
 */

//$Id$

import('handler.Handler');

class ManagerHandler extends Handler {	
	/**
	 * Constructor
	 **/
	function ManagerHandler() {
		parent::Handler();

		$this->addCheck(new HandlerValidatorConference($this));		
		$this->addCheck(new HandlerValidatorRoles($this, true, null, null, array(ROLE_ID_SITE_ADMIN, ROLE_ID_CONFERENCE_MANAGER)));
	}
	
	/**
	 * Display conference management index page.
	 */
	function index() {
		// Manager requests should come to the Conference context, not Sched Conf
		if (Request::getRequestedSchedConfPath() != 'index') Request::redirect(null, 'index', 'manager');
		$this->validate();
		$this->setupTemplate();
		
		$conference =& Request::getConference();
		$templateMgr =& TemplateManager::getManager();

		$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
		$schedConfs =& $schedConfDao->getSchedConfsByConferenceId($conference->getId());
		$templateMgr->assign_by_ref('schedConfs', $schedConfs);

		$templateMgr->assign('announcementsEnabled', $conference->getSetting('enableAnnouncements'));
		$templateMgr->assign('loggingEnabled', $conference->getSetting('conferenceEventLog'));

		$templateMgr->assign('helpTopicId','conference.index');
		$templateMgr->display(ROLE_PATH_CONFERENCE_MANAGER . '/index.tpl');
	}


	/**
	 * Send an email to a user or group of users.
	 */
	function email($args) {
		$this->validate();
		$this->setupTemplate(true);
		
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('helpTopicId', 'conference.users.emailUsers');

		$userDao =& DAORegistry::getDAO('UserDAO');

		$site =& Request::getSite();
		$user =& Request::getUser();

		import('mail.MailTemplate');
		$email = new MailTemplate(Request::getUserVar('template'), Request::getUserVar('locale'));

		if (Request::getUserVar('send') && !$email->hasErrors()) {
			$email->send();
			Request::redirect(null, null, Request::getRequestedPage());
		} else {
			$email->assignParams(); // FIXME Forces default parameters to be assigned (should do this automatically in MailTemplate?)
			if (!Request::getUserVar('continued')) {
				if (($groupId = Request::getUserVar('toGroup')) != '') {
					// Special case for emailing entire groups:
					// Check for a group ID and add recipients.
					$groupDao =& DAORegistry::getDAO('GroupDAO');
					$group =& $groupDao->getGroup($groupId, ASSOC_TYPE_SCHED_CONF, $schedConf->getId());
					if ($group) {
						$groupMembershipDao =& DAORegistry::getDAO('GroupMembershipDAO');
						$memberships =& $groupMembershipDao->getMemberships($group->getId());
						$memberships =& $memberships->toArray();
						foreach ($memberships as $membership) {
							$user =& $membership->getUser();
							$email->addRecipient($user->getEmail(), $user->getFullName());
						}
					}
				}
				if (count($email->getRecipients())==0) $email->addRecipient($user->getEmail(), $user->getFullName());
			}
			$email->displayEditForm(Request::url(null, null, null, 'email'), array(), 'manager/people/email.tpl');
		}
	}		

	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false) {
		parent::setupTemplate();
		AppLocale::requireComponents(array(LOCALE_COMPONENT_PKP_MANAGER, LOCALE_COMPONENT_OCS_MANAGER, LOCALE_COMPONENT_PKP_ADMIN));
		$templateMgr =& TemplateManager::getManager();
		$pageHierarchy = array();

		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();

		if ($schedConf) {
			$pageHierarchy[] = array(Request::url(null, null, 'index'), $schedConf->getFullTitle(), true);
		} elseif ($conference) {
			$pageHierarchy[] = array(Request::url(null, 'index', 'index'), $conference->getConferenceTitle(), true);
		}

		if ($subclass) {
			$pageHierarchy[] = array(Request::url(null, null, 'user'), 'navigation.user');
			$pageHierarchy[] = array(Request::url(null, 'index', 'manager'), 'manager.conferenceSiteManagement');
		} else {
			$pageHierarchy[] = array(Request::url(null, null, 'user'), 'navigation.user');
		}

		$templateMgr->assign('pageHierarchy', $pageHierarchy);
	}
}

?>
