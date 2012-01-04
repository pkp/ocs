<?php

/**
 * @file EmailHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EmailHandler
 * @ingroup pages_user
 *
 * @brief Handle requests for user emails.
 */

//$Id$

import('pages.user.UserHandler');

class EmailHandler extends UserHandler {
	/**
	 * Constructor
	 **/
	function EmailHandler() {
		parent::UserHandler();
	}
	function email($args) {
		$this->validate();
		$this->setupTemplate(true);
		
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		
		$templateMgr =& TemplateManager::getManager();

		$userDao =& DAORegistry::getDAO('UserDAO');
		$user =& Request::getUser();

		// See if this is the Director or Manager and an email template has been chosen
		$template = Request::getUserVar('template');
		if (	!$conference || empty($template) || (
			!Validation::isConferenceManager() &&
			!Validation::isDirector() &&
			!Validation::isTrackDirector())) {
			$template = null;
		}

		// Determine whether or not this account is subject to
		// email sending restrictions.
		$canSendUnlimitedEmails = Validation::isSiteAdmin();
		$unlimitedEmailRoles = array(
			ROLE_ID_CONFERENCE_MANAGER,
			ROLE_ID_DIRECTOR,
			ROLE_ID_TRACK_DIRECTOR
		);
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		if ($conference) {
			$roles =& $roleDao->getRolesByUserId($user->getId(), $conference->getId());
			foreach ($roles as $role) {
				if (in_array($role->getRoleId(), $unlimitedEmailRoles)) $canSendUnlimitedEmails = true;
			}
		}

		// Check when this user last sent an email, and if it's too
		// recent, make them wait.
		if (!$canSendUnlimitedEmails) {
			$dateLastEmail = $user->getDateLastEmail();
			if ($dateLastEmail && strtotime($dateLastEmail) + ((int) Config::getVar('email', 'time_between_emails')) > strtotime(Core::getCurrentDate())) {
				$templateMgr->assign('pageTitle', 'email.compose');
				$templateMgr->assign('message', 'email.compose.tooSoon');
				$templateMgr->assign('backLink', 'javascript:history.back()');
				$templateMgr->assign('backLinkLabel', 'email.compose');
				return $templateMgr->display('common/message.tpl');
			}
		}

		$email = null;
		if ($paperId = Request::getUserVar('paperId')) {
			// This message is in reference to a paper.
			// Determine whether the current user has access
			// to the paper in some form, and if so, use an
			// PaperMailTemplate.
			$paperDao =& DAORegistry::getDAO('PaperDAO');

			$paper =& $paperDao->getPaper($paperId);
			$hasAccess = false;

			// First, conditions where access is OK.
			// 1. User is submitter
			if ($paper && $paper->getUserId() == $user->getId()) $hasAccess = true;
			// 2. User is director
			$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
			$editAssignments =& $editAssignmentDao->getEditAssignmentsByPaperId($paperId);
			while ($editAssignment =& $editAssignments->next()) {
				if ($editAssignment->getDirectorId() === $user->getId()) $hasAccess = true;
			}
			if (Validation::isDirector()) $hasAccess = true;
			// 3. User is reviewer
			$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
			foreach ($reviewAssignmentDao->getReviewAssignmentsByPaperId($paperId) as $reviewAssignment) {
				if ($reviewAssignment->getReviewerId() === $user->getId()) $hasAccess = true;
			}

			// Last, "deal-breakers" -- access is not allowed.
			if ($paper && $paper->getSchedConfId() !== $schedConf->getId()) $hasAccess = false;

			if ($hasAccess) {
				import('mail.PaperMailTemplate');
				$email = new PaperMailTemplate($paperDao->getPaper($paperId));
			}
		}

		if ($email === null) {
			import('mail.MailTemplate');
			$email = new MailTemplate();
		}

		if (Request::getUserVar('send') && !$email->hasErrors()) {
			$recipients = $email->getRecipients();
			$ccs = $email->getCcs();
			$bccs = $email->getBccs();

			// Make sure there aren't too many recipients (to
			// prevent use as a spam relay)
			$recipientCount = 0;
			if (is_array($recipients)) $recipientCount += count($recipients);
			if (is_array($ccs)) $recipientCount += count($ccs);
			if (is_array($bccs)) $recipientCount += count($bccs);

			if (!$canSendUnlimitedEmails && $recipientCount > ((int) Config::getVar('email', 'max_recipients'))) {
				$templateMgr->assign('pageTitle', 'email.compose');
				$templateMgr->assign('message', 'email.compose.tooManyRecipients');
				$templateMgr->assign('backLink', 'javascript:history.back()');
				$templateMgr->assign('backLinkLabel', 'email.compose');
				return $templateMgr->display('common/message.tpl');
			}

			$email->send();
			$redirectUrl = Request::getUserVar('redirectUrl');
			if (empty($redirectUrl)) $redirectUrl = Request::url(null, null, 'user');
			$user->setDateLastEmail(Core::getCurrentDate());
			$userDao->updateObject($user);
			Request::redirectUrl($redirectUrl);
		} else {
			$email->displayEditForm(Request::url(null, null, null, 'email'), array('redirectUrl' => Request::getUserVar('redirectUrl'), 'paperId' => $paperId), null, array('disableSkipButton' => true));
		}
	}
}

?>
