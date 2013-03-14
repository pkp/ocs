<?php

/**
 * @file DirectorAction.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DirectorAction
 * @ingroup submission
 *
 * @brief DirectorAction class.
 */


import('classes.submission.trackDirector.TrackDirectorAction');

class DirectorAction extends TrackDirectorAction {

	/**
	 * Constructor.
	 */
	function DirectorAction() {

	}

	/**
	 * Actions.
	 */

	/**
	 * Assigns a track director to a submission.
	 * @param $paperId int
	 * @return boolean true iff ready for redirect
	 */
	function assignDirector($paperId, $trackDirectorId, $isDirector = false, $send = false) {
		$directorSubmissionDao = DAORegistry::getDAO('DirectorSubmissionDAO');
		$editAssignmentDao = DAORegistry::getDAO('EditAssignmentDAO');
		$userDao = DAORegistry::getDAO('UserDAO');

		$user =& Request::getUser();
		$conference =& Request::getConference();

		$directorSubmission =& $directorSubmissionDao->getDirectorSubmission($paperId);
		$trackDirector =& $userDao->getById($trackDirectorId);
		if (!isset($trackDirector)) return true;

		import('classes.mail.PaperMailTemplate');
		$email = new PaperMailTemplate($directorSubmission, 'DIRECTOR_ASSIGN');

		if ($user->getId() === $trackDirectorId || !$email->isEnabled() || ($send && !$email->hasErrors())) {
			HookRegistry::call('DirectorAction::assignDirector', array(&$directorSubmission, &$trackDirector, &$isDirector, &$email));
			if ($email->isEnabled() && $user->getId() !== $trackDirectorId) {
				$email->setAssoc(PAPER_EMAIL_DIRECTOR_ASSIGN, PAPER_EMAIL_TYPE_DIRECTOR, $trackDirector->getId());
				$email->send();
			}

			$editAssignment = new EditAssignment();
			$editAssignment->setPaperId($paperId);

			// Make the selected director the new director
			$editAssignment->setDirectorId($trackDirectorId);
			$editAssignment->setDateNotified(Core::getCurrentDate());
			$editAssignment->setDateUnderway(null);

			$editAssignments =& $directorSubmission->getEditAssignments();
			array_push($editAssignments, $editAssignment);
			$directorSubmission->setEditAssignments($editAssignments);

			$directorSubmissionDao->updateDirectorSubmission($directorSubmission);

			// Add log
			import('classes.paper.log.PaperLog');
			import('classes.paper.log.PaperEventLogEntry');
			PaperLog::logEvent($paperId, PAPER_LOG_DIRECTOR_ASSIGN, LOG_TYPE_DIRECTOR, $trackDirectorId, 'log.director.directorAssigned', array('directorName' => $trackDirector->getFullName(), 'paperId' => $paperId));
			return true;
		} else {
			if (!Request::getUserVar('continued')) {
				$email->addRecipient($trackDirector->getEmail(), $trackDirector->getFullName());
				$paramArray = array(
					'editorialContactName' => $trackDirector->getFullName(),
					'directorUsername' => $trackDirector->getUsername(),
					'directorPassword' => $trackDirector->getPassword(),
					'editorialContactSignature' => $user->getContactSignature(),
					'submissionUrl' => Request::url(null, null, $isDirector?'director':'trackDirector', 'submissionReview', $paperId)
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::url(null, null, null, 'assignDirector', 'send'), array('paperId' => $paperId, 'directorId' => $trackDirectorId));
			return false;
		}
	}
}

?>
