<?php

/**
 * @file EmailHandler.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.user
 * @class EmailHandler
 *
 * Handle requests for user emails.
 *
 * $Id$
 */

class EmailHandler extends UserHandler {
	function email($args) {
		list($conference, $schedConf) = parent::validate();

		parent::setupTemplate(true);

		$templateMgr = &TemplateManager::getManager();

		$userDao = &DAORegistry::getDAO('UserDAO');

		$user = &Request::getUser();

		// See if this is the Director or Manager and an email template has been chosen
		$template = Request::getUserVar('template');
		if (empty($template) || (
			!Validation::isConferenceManager() &&
			!Validation::isDirector() &&
			!Validation::isTrackDirector())) {
			$template = null;
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
			if ($paper && $paper->getUserId() == $user->getUserId()) $hasAccess = true;
			// 2. User is director
			$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
			$editAssignments =& $editAssignmentDao->getEditAssignmentsByPaperId($paperId);
			while ($editAssignment =& $editAssignments->next()) {
				if ($editAssignment->getDirectorId() === $user->getUserId()) $hasAccess = true;
			}
			if (Validation::isDirector()) $hasAccess = true;
			// 3. User is reviewer
			$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
			foreach ($reviewAssignmentDao->getReviewAssignmentsByPaperId($paperId) as $reviewAssignment) {
				if ($reviewAssignment->getReviewerId() === $user->getUserId()) $hasAccess = true;
			}

			// Last, "deal-breakers" -- access is not allowed.
			if ($paper && $paper->getSchedConfId() !== $schedConf->getSchedConfId()) $hasAccess = false;

			if ($hasAccess) {
				import('mail.PaperMailTemplate');
				$email =& new PaperMailTemplate($paperDao->getPaper($paperId));
			}
		}

		if ($email === null) {
			import('mail.MailTemplate');
			$email = &new MailTemplate();
		}
		
		if (Request::getUserVar('send') && !$email->hasErrors()) {
			$email->send();
			$redirectUrl = Request::getUserVar('redirectUrl');
			if (empty($redirectUrl)) $redirectUrl = Request::url(null, null, 'user');
			Request::redirectUrl($redirectUrl);
		} else {
			if (!Request::getUserVar('continued')) {
				// Check for special cases.

				// 1. If the parameter presentersPaperId is set, preload
				// the template with all the presenters of the specified
				// paper ID as recipients and use the paper title
				// as a subject.
				if (Request::getUserVar('presentersPaperId')) {
					$paperDao = &DAORegistry::getDAO('PaperDAO');
					$paper = $paperDao->getPaper(Request::getUserVar('presentersPaperId'));
					if (isset($paper) && $paper != null) {
						foreach ($paper->getPresenters() as $presenter) {
							$email->addRecipient($presenter->getEmail(), $presenter->getFullName());
						}
						$email->setSubject($email->getSubject() . strip_tags($paper->getPaperTitle()));
					}
				}
			}
			$email->displayEditForm(Request::url(null, null, null, 'email'), array('redirectUrl' => Request::getUserVar('redirectUrl'), 'paperId' => $paperId), null, array('disableSkipButton' => true));
		}
	}
}

?>
