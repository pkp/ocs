<?php

/**
 * EmailHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.user
 *
 * Handle requests for user emails.
 *
 * $Id$
 */

class EmailHandler extends UserHandler {
	function email($args) {
		list($conference, $event) = parent::validate();

		parent::setupTemplate(true);

		$templateMgr = &TemplateManager::getManager();

		$userDao = &DAORegistry::getDAO('UserDAO');

		$user = &Request::getUser();

		$email = null;
		if ($paperId = Request::getUserVar('paperId')) {
			// This message is in reference to an paper.
			// Determine whether the current user has access
			// to the paper in some form, and if so, use an
			// PaperMailTemplate.
			$paperDao =& DAORegistry::getDAO('PaperDAO');

			$paper =& $paperDao->getPaper($paperId);
			$hasAccess = false;

			// First, conditions where access is OK.
			// 1. User is submitter
			if ($paper && $paper->getUserId() == $user->getUserId()) $hasAccess = true;
			// 2. User is editor
			$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
			$editAssignments =& $editAssignmentDao->getEditAssignmentsByPaperId($paperId);
			while ($editAssignment =& $editAssignments->next()) {
				if ($editAssignment->getEditorId() === $user->getUserId()) $hasAccess = true;
			}
			// 3. User is reviewer
			$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
			foreach ($reviewAssignmentDao->getReviewAssignmentsByPaperId($paperId) as $reviewAssignment) {
				if ($reviewAssignment->getReviewerId() === $user->getUserId()) $hasAccess = true;
			}

			// Last, "deal-breakers" -- access is not allowed.
			if ($paper && $paper->getEventId() !== $event->getEventId()) $hasAccess = false;

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
			Request::redirectUrl(Request::getUserVar('redirectUrl'));
		} else {
			if (!Request::getUserVar('continued')) {
				// Check for special cases.

				// 1. If the parameter authorsPaperId is set, preload
				// the template with all the authors of the specified
				// paper ID as recipients and use the paper title
				// as a subject.
				if (Request::getUserVar('authorsPaperId')) {
					$paperDao = &DAORegistry::getDAO('PaperDAO');
					$paper = $paperDao->getPaper(Request::getUserVar('authorsPaperId'));
					if (isset($paper) && $paper != null) {
						foreach ($paper->getAuthors() as $author) {
							$email->addRecipient($author->getEmail(), $author->getFullName());
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
