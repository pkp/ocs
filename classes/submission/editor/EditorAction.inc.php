<?php

/**
 * EditorAction.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * EditorAction class.
 *
 * $Id$
 */

import('submission.trackDirector.TrackDirectorAction');

class EditorAction extends TrackDirectorAction {

	/**
	 * Constructor.
	 */
	function EditorAction() {

	}

	/**
	 * Actions.
	 */
	 
	/**
	 * Assigns a track director to a submission.
	 * @param $paperId int
	 * @return boolean true iff ready for redirect
	 */
	function assignEditor($paperId, $trackDirectorId, $send = false) {
		$editorSubmissionDao = &DAORegistry::getDAO('EditorSubmissionDAO');
		$editAssignmentDao = &DAORegistry::getDAO('EditAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');

		$user = &Request::getUser();
		$conference = &Request::getConference();

		$editorSubmission = &$editorSubmissionDao->getEditorSubmission($paperId);
		$trackDirector = &$userDao->getUser($trackDirectorId);
		if (!isset($trackDirector)) return true;

		import('mail.PaperMailTemplate');
		$email = &new PaperMailTemplate($editorSubmission, 'EDITOR_ASSIGN');

		if ($user->getUserId() === $trackDirectorId || !$email->isEnabled() || ($send && !$email->hasErrors())) {
			HookRegistry::call('EditorAction::assignEditor', array(&$editorSubmission, &$trackDirector, &$email));
			if ($email->isEnabled() && $user->getUserId() !== $trackDirectorId) {
				$email->setAssoc(PAPER_EMAIL_EDITOR_ASSIGN, PAPER_EMAIL_TYPE_EDITOR, $trackDirector->getUserId());
				$email->send();
			}

			$editAssignment = &new EditAssignment();
			$editAssignment->setPaperId($paperId);
			$editAssignment->setCanEdit(1);
			$editAssignment->setCanReview(1);
		
			// Make the selected editor the new editor
			$editAssignment->setEditorId($trackDirectorId);
			$editAssignment->setDateNotified(Core::getCurrentDate());
			$editAssignment->setDateUnderway(null);
		
			$editAssignments =& $editorSubmission->getEditAssignments();
			array_push($editAssignments, $editAssignment);
			$editorSubmission->setEditAssignments($editAssignments);
		
			$editorSubmissionDao->updateEditorSubmission($editorSubmission);
		
			// Add log
			import('paper.log.PaperLog');
			import('paper.log.PaperEventLogEntry');
			PaperLog::logEvent($paperId, PAPER_LOG_EDITOR_ASSIGN, LOG_TYPE_EDITOR, $trackDirectorId, 'log.editor.editorAssigned', array('editorName' => $trackDirector->getFullName(), 'paperId' => $paperId));
			return true;
		} else {
			if (!Request::getUserVar('continued')) {
				$email->addRecipient($trackDirector->getEmail(), $trackDirector->getFullName());
				$paramArray = array(
					'editorialContactName' => $trackDirector->getFullName(),
					'editorUsername' => $trackDirector->getUsername(),
					'editorPassword' => $trackDirector->getPassword(),
					'editorialContactSignature' => $user->getContactSignature(),
					'submissionUrl' => Request::url(null, null, 'trackDirector', 'submissionReview', $paperId),
					'submissionEditingUrl' => Request::url(null, null, 'trackDirector', 'submissionReview', $paperId)
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::url(null, null, null, 'assignEditor', 'send'), array('paperId' => $paperId, 'editorId' => $trackDirectorId));
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

		import('submission.editor.EditorAction');
		import('submission.trackDirector.TrackDirectorAction');
		import('submission.proofreader.ProofreaderAction');

		$trackDirectorSubmissionDao =& DAORegistry::getDAO('TrackDirectorSubmissionDAO');
		$trackDirectorSubmission =& $trackDirectorSubmissionDao->getTrackDirectorSubmission($paper->getPaperId());

		$submissionFile = $trackDirectorSubmission->getSubmissionFile();

		// Add a long entry before doing anything.
		import('paper.log.PaperLog');
		import('paper.log.PaperEventLogEntry');
		PaperLog::logEvent($paper->getPaperId(), PAPER_LOG_EDITOR_EXPEDITE, LOG_TYPE_EDITOR, $user->getUserId(), 'log.editor.submissionExpedited', array('editorName' => $user->getFullName(), 'paperId' => $paper->getPaperId()));

		// 1. Ensure that an editor is assigned.
		$editAssignments =& $trackDirectorSubmission->getEditAssignments();
		if (empty($editAssignments)) {
			// No editors are currently assigned; assign self.
			EditorAction::assignEditor($paper->getPaperId(), $user->getUserId());
		}

		// 2. Accept the submission and send to copyediting.
		$trackDirectorSubmission =& $trackDirectorSubmissionDao->getTrackDirectorSubmission($paper->getPaperId());
		if (!$trackDirectorSubmission->getCopyeditFile()) {
			TrackDirectorAction::recordDecision($trackDirectorSubmission, SUBMISSION_EDITOR_DECISION_ACCEPT);
			$editorFile = $trackDirectorSubmission->getEditorFile();
			TrackDirectorAction::setCopyeditFile($trackDirectorSubmission, $editorFile->getFileId(), $editorFile->getRevision());
		}

		// 3. Add a galley.
		$trackDirectorSubmission =& $trackDirectorSubmissionDao->getTrackDirectorSubmission($paper->getPaperId());
		$galleys =& $trackDirectorSubmission->getGalleys();
		if (empty($galleys)) {
			// No galley present -- use copyediting file.
			import('file.PaperFileManager');
			$copyeditFile =& $trackDirectorSubmission->getCopyeditFile();
			$fileType = $copyeditFile->getFileType();
			$paperFileManager =& new PaperFileManager($paper->getPaperId());
			$fileId = $paperFileManager->copyPublicFile($copyeditFile->getFilePath(), $fileType);

			if (strstr($fileType, 'html')) {
				$galley =& new PaperHTMLGalley();
			} else {
				$galley =& new PaperGalley();
			}
			$galley->setPaperId($paper->getPaperId());
			$galley->setFileId($fileId);

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
