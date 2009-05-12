<?php

/**
 * @file DirectorAction.inc.php
 *
 * Copyright (c) 2000-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DirectorAction
 * @ingroup submission
 *
 * @brief DirectorAction class.
 */

//$Id$

import('submission.trackDirector.TrackDirectorAction');

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
		$directorSubmissionDao =& DAORegistry::getDAO('DirectorSubmissionDAO');
		$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');

		$user =& Request::getUser();
		$conference =& Request::getConference();

		$directorSubmission =& $directorSubmissionDao->getDirectorSubmission($paperId);
		$trackDirector =& $userDao->getUser($trackDirectorId);
		if (!isset($trackDirector)) return true;

		import('mail.PaperMailTemplate');
		$email = new PaperMailTemplate($directorSubmission, 'DIRECTOR_ASSIGN');

		if ($user->getUserId() === $trackDirectorId || !$email->isEnabled() || ($send && !$email->hasErrors())) {
			HookRegistry::call('DirectorAction::assignDirector', array(&$directorSubmission, &$trackDirector, &$isDirector, &$email));
			if ($email->isEnabled() && $user->getUserId() !== $trackDirectorId) {
				$email->setAssoc(PAPER_EMAIL_DIRECTOR_ASSIGN, PAPER_EMAIL_TYPE_DIRECTOR, $trackDirector->getUserId());
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
			import('paper.log.PaperLog');
			import('paper.log.PaperEventLogEntry');
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

	/**
	 * Rush a new submission into the Scheduling queue.
	 * @param $paper object
	 */
	/*FIXME
	function expediteSubmission($paper) {
		$user =& Request::getUser();

		import('submission.director.DirectorAction');
		import('submission.trackDirector.TrackDirectorAction');
		import('submission.proofreader.ProofreaderAction');

		$trackDirectorSubmissionDao =& DAORegistry::getDAO('TrackDirectorSubmissionDAO');
		$trackDirectorSubmission =& $trackDirectorSubmissionDao->getTrackDirectorSubmission($paper->getPaperId());

		$submissionFile = $trackDirectorSubmission->getSubmissionFile();

		// Add a long entry before doing anything.
		import('paper.log.PaperLog');
		import('paper.log.PaperEventLogEntry');
		PaperLog::logEvent($paper->getPaperId(), PAPER_LOG_DIRECTOR_EXPEDITE, LOG_TYPE_DIRECTOR, $user->getUserId(), 'log.director.submissionExpedited', array('directorName' => $user->getFullName(), 'paperId' => $paper->getPaperId()));

		// 1. Ensure that a director is assigned.
		$editAssignments =& $trackDirectorSubmission->getEditAssignments();
		if (empty($editAssignments)) {
			// No directors are currently assigned; assign self.
			DirectorAction::assignDirector($paper->getPaperId(), $user->getUserId(), true);
		}

		// 2. Accept the submission and send to copyediting.
		$trackDirectorSubmission =& $trackDirectorSubmissionDao->getTrackDirectorSubmission($paper->getPaperId());
		if (!$trackDirectorSubmission->getCopyeditFile()) {
			TrackDirectorAction::recordDecision($trackDirectorSubmission, SUBMISSION_DIRECTOR_DECISION_ACCEPT);
			$directorFile = $trackDirectorSubmission->getDirectorFile();
			TrackDirectorAction::setCopyeditFile($trackDirectorSubmission, $directorFile->getFileId(), $directorFile->getRevision());
		}

		// 3. Add a galley.
		$trackDirectorSubmission =& $trackDirectorSubmissionDao->getTrackDirectorSubmission($paper->getPaperId());
		$galleys =& $trackDirectorSubmission->getGalleys();
		if (empty($galleys)) {
			// No galley present -- use copyediting file.
			import('file.PaperFileManager');
			$copyeditFile =& $trackDirectorSubmission->getCopyeditFile();
			$fileType = $copyeditFile->getFileType();
			$paperFileManager = new PaperFileManager($paper->getPaperId());
			$fileId = $paperFileManager->copyPublicFile($copyeditFile->getFilePath(), $fileType);

			if (strstr($fileType, 'html')) {
				$galley = new PaperHTMLGalley();
			} else {
				$galley = new PaperGalley();
			}
			$galley->setPaperId($paper->getPaperId());
			$galley->setFileId($fileId);
			$galley->setLocale(Locale::getLocale());

			if ($galley->isHTMLGalley()) {
				$galley->setLabel('HTML');
			} else {
				if (strstr($fileType, 'pdf')) {
					$galley->setLabel('PDF');
				} else if (strstr($fileType, 'postscript')) {
					$galley->setLabel('Postscript');
				} else if (strstr($fileType, 'xml')) {
					$galley->setLabel('XML');
				} else {
					$galley->setLabel(Locale::translate('common.untitled'));
				}
			}

			$galleyDao =& DAORegistry::getDAO('PaperGalleyDAO');
			$galleyDao->insertGalley($galley);
		}

		// 4. Send to scheduling
		ProofreaderAction::queueForScheduling($trackDirectorSubmission);
	}
	*/
}

?>
