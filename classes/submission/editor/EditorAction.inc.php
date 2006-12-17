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

import('submission.trackEditor.TrackEditorAction');

class EditorAction extends TrackEditorAction {

	/**
	 * Constructor.
	 */
	function EditorAction() {
		Parent::TrackEditorAction();
	}

	/**
	 * Actions.
	 */
	 
	/**
	 * Assigns a track editor to a submission.
	 * @param $paperId int
	 * @return boolean true iff ready for redirect
	 */
	function assignEditor($paperId, $trackEditorId, $send = false) {
		$editorSubmissionDao = &DAORegistry::getDAO('EditorSubmissionDAO');
		$editAssignmentDao = &DAORegistry::getDAO('EditAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');

		$user = &Request::getUser();
		$conference = &Request::getConference();

		$editorSubmission = &$editorSubmissionDao->getEditorSubmission($paperId);
		$trackEditor = &$userDao->getUser($trackEditorId);
		if (!isset($trackEditor)) return true;

		import('mail.PaperMailTemplate');
		$email = &new PaperMailTemplate($editorSubmission, 'EDITOR_ASSIGN');

		if ($user->getUserId() === $trackEditorId || !$email->isEnabled() || ($send && !$email->hasErrors())) {
			HookRegistry::call('EditorAction::assignEditor', array(&$editorSubmission, &$trackEditor, &$email));
			if ($email->isEnabled() && $user->getUserId() !== $trackEditorId) {
				$email->setAssoc(PAPER_EMAIL_EDITOR_ASSIGN, PAPER_EMAIL_TYPE_EDITOR, $trackEditor->getUserId());
				$email->send();
			}

			$editAssignment = &new EditAssignment();
			$editAssignment->setPaperId($paperId);
			$editAssignment->setCanEdit(1);
			$editAssignment->setCanReview(1);
		
			// Make the selected editor the new editor
			$editAssignment->setEditorId($trackEditorId);
			$editAssignment->setDateNotified(Core::getCurrentDate());
			$editAssignment->setDateUnderway(null);
		
			$editAssignments =& $editorSubmission->getEditAssignments();
			array_push($editAssignments, $editAssignment);
			$editorSubmission->setEditAssignments($editAssignments);
		
			$editorSubmissionDao->updateEditorSubmission($editorSubmission);
		
			// Add log
			import('paper.log.PaperLog');
			import('paper.log.PaperEventLogEntry');
			PaperLog::logEvent($paperId, PAPER_LOG_EDITOR_ASSIGN, LOG_TYPE_EDITOR, $trackEditorId, 'log.editor.editorAssigned', array('editorName' => $trackEditor->getFullName(), 'paperId' => $paperId));
			return true;
		} else {
			if (!Request::getUserVar('continued')) {
				$email->addRecipient($trackEditor->getEmail(), $trackEditor->getFullName());
				$paramArray = array(
					'editorialContactName' => $trackEditor->getFullName(),
					'editorUsername' => $trackEditor->getUsername(),
					'editorPassword' => $trackEditor->getPassword(),
					'editorialContactSignature' => $user->getContactSignature(),
					'submissionUrl' => Request::url(null, null, 'trackEditor', 'submissionReview', $paperId),
					'submissionEditingUrl' => Request::url(null, null, 'trackEditor', 'submissionReview', $paperId)
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::url(null, null, null, 'assignEditor', 'send'), array('paperId' => $paperId, 'editorId' => $trackEditorId));
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
		import('submission.trackEditor.TrackEditorAction');
		import('submission.proofreader.ProofreaderAction');

		$trackEditorSubmissionDao =& DAORegistry::getDAO('TrackEditorSubmissionDAO');
		$trackEditorSubmission =& $trackEditorSubmissionDao->getTrackEditorSubmission($paper->getPaperId());

		$submissionFile = $trackEditorSubmission->getSubmissionFile();

		// Add a long entry before doing anything.
		import('paper.log.PaperLog');
		import('paper.log.PaperEventLogEntry');
		PaperLog::logEvent($paper->getPaperId(), PAPER_LOG_EDITOR_EXPEDITE, LOG_TYPE_EDITOR, $user->getUserId(), 'log.editor.submissionExpedited', array('editorName' => $user->getFullName(), 'paperId' => $paper->getPaperId()));

		// 1. Ensure that an editor is assigned.
		$editAssignments =& $trackEditorSubmission->getEditAssignments();
		if (empty($editAssignments)) {
			// No editors are currently assigned; assign self.
			EditorAction::assignEditor($paper->getPaperId(), $user->getUserId());
		}

		// 2. Accept the submission and send to copyediting.
		$trackEditorSubmission =& $trackEditorSubmissionDao->getTrackEditorSubmission($paper->getPaperId());
		if (!$trackEditorSubmission->getCopyeditFile()) {
			TrackEditorAction::recordDecision($trackEditorSubmission, SUBMISSION_EDITOR_DECISION_ACCEPT);
			$editorFile = $trackEditorSubmission->getEditorFile();
			TrackEditorAction::setCopyeditFile($trackEditorSubmission, $editorFile->getFileId(), $editorFile->getRevision());
		}

		// 3. Add a galley.
		$trackEditorSubmission =& $trackEditorSubmissionDao->getTrackEditorSubmission($paper->getPaperId());
		$galleys =& $trackEditorSubmission->getGalleys();
		if (empty($galleys)) {
			// No galley present -- use copyediting file.
			import('file.PaperFileManager');
			$copyeditFile =& $trackEditorSubmission->getCopyeditFile();
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
		ProofreaderAction::queueForScheduling($trackEditorSubmission);
	}
	*/
}

?>
