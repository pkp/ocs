<?php

/**
 * @file AuthorAction.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthorAction
 * @ingroup submission
 *
 * @brief AuthorAction class.
 *
 */



import('classes.submission.common.Action');

class AuthorAction extends Action {

	/**
	 * Constructor.
	 */
	function AuthorAction() {
		parent::Action();
	}

	/**
	 * Actions.
	 */

	/**
	 * Designates the original file the review version.
	 * @param $authorSubmission object
	 */
	function designateReviewVersion($authorSubmission) {
		import('classes.file.PaperFileManager');
		$paperFileManager = new PaperFileManager($authorSubmission->getId());
		$authorSubmissionDao = DAORegistry::getDAO('AuthorSubmissionDAO');

		if (!HookRegistry::call('AuthorAction::designateReviewVersion', array(&$authorSubmission))) {
			$submissionFile =& $authorSubmission->getSubmissionFile();
			if ($submissionFile) {
				$reviewFileId = $paperFileManager->copyToReviewFile($submissionFile->getFileId());

				$authorSubmission->setReviewFileId($reviewFileId);

				$authorSubmissionDao->updateAuthorSubmission($authorSubmission);

				$trackDirectorSubmissionDao = DAORegistry::getDAO('TrackDirectorSubmissionDAO');
				$schedConf =& Request::getSchedConf();
				if (!$schedConf || $schedConf->getId() != $authorSubmission->getSchedConfId()) {
					$schedConfDao = DAORegistry::getDAO('SchedConfDAO');
					unset($schedConf);
					$schedConf = $schedConfDao->getById($authorSubmission->getSchedConfId());
				}
				$trackDirectorSubmissionDao->createReviewRound($authorSubmission->getId(), REVIEW_ROUND_PRESENTATION, 1);
			}
		}
	}

	/**
	 * Delete an author file from a submission.
	 * @param $paper object
	 * @param $fileId int
	 * @param $revisionId int
	 */
	function deletePaperFile($paper, $fileId, $revisionId) {
		import('classes.file.PaperFileManager');

		$paperFileManager = new PaperFileManager($paper->getId());
		$paperFileDao = DAORegistry::getDAO('PaperFileDAO');
		$authorSubmissionDao = DAORegistry::getDAO('AuthorSubmissionDAO');

		$paperFile =& $paperFileDao->getPaperFile($fileId, $revisionId, $paper->getId());
		$authorSubmission = $authorSubmissionDao->getAuthorSubmission($paper->getId());
		$authorRevisions = $authorSubmission->getAuthorFileRevisions();

		// Ensure that this is actually an author file.
		if (isset($paperFile)) {
			HookRegistry::call('AuthorAction::deletePaperFile', array(&$paperFile, &$authorRevisions));
			foreach ($authorRevisions as $round) {
				foreach ($round as $revision) {
					if ($revision->getFileId() == $paperFile->getFileId() &&
						$revision->getRevision() == $paperFile->getRevision()) {
						$paperFileManager->deleteFile($paperFile->getFileId(), $paperFile->getRevision());
					}
				}
			}
		}
	}

	/**
	 * Upload the revised version of a paper.
	 * @param $authorSubmission object
	 */
	function uploadRevisedVersion($authorSubmission) {
		import('classes.file.PaperFileManager');
		$paperFileManager = new PaperFileManager($authorSubmission->getId());
		$authorSubmissionDao = DAORegistry::getDAO('AuthorSubmissionDAO');

		$fileName = 'upload';
		if ($paperFileManager->uploadError($fileName)) return false;
		if (!$paperFileManager->uploadedFileExists($fileName)) return false;

		HookRegistry::call('AuthorAction::uploadRevisedVersion', array(&$authorSubmission));
		if ($authorSubmission->getRevisedFileId() != null) {
			$fileId = $paperFileManager->uploadDirectorDecisionFile($fileName, $authorSubmission->getRevisedFileId());
		} else {
			$fileId = $paperFileManager->uploadDirectorDecisionFile($fileName);
		}
		if (!$fileId) return false;

		$authorSubmission->setRevisedFileId($fileId);
		$authorSubmissionDao->updateAuthorSubmission($authorSubmission);

		// Add log entry
		$user =& Request::getUser();
		import('classes.paper.log.PaperLog');
		import('classes.paper.log.PaperEventLogEntry');
		PaperLog::logEvent($authorSubmission->getId(), PAPER_LOG_AUTHOR_REVISION, LOG_TYPE_AUTHOR, $user->getId(), 'log.author.documentRevised', array('authorName' => $user->getFullName(), 'fileId' => $fileId, 'paperId' => $authorSubmission->getId()));
	}

	//
	// Comments
	//

	/**
	 * View director decision comments.
	 * @param $paper object
	 */
	function viewDirectorDecisionComments($paper) {
		if (!HookRegistry::call('AuthorAction::viewDirectorDecisionComments', array(&$paper))) {
			import('classes.submission.form.comment.DirectorDecisionCommentForm');

			$commentForm = new DirectorDecisionCommentForm($paper, ROLE_ID_AUTHOR);
			$commentForm->initData();
			$commentForm->display();
		}
	}

	/**
	 * Email director decision comment.
	 * @param $authorSubmission object
	 * @param $send boolean
	 */
	function emailDirectorDecisionComment($authorSubmission, $send) {
		$userDao = DAORegistry::getDAO('UserDAO');
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();

		$user =& Request::getUser();
		import('classes.mail.PaperMailTemplate');
		$email = new PaperMailTemplate($authorSubmission);

		$editAssignments = $authorSubmission->getEditAssignments();
		$directors = array();
		foreach ($editAssignments as $editAssignment) {
			array_push($directors, $userDao->getById($editAssignment->getDirectorId()));
		}

		if ($send && !$email->hasErrors()) {
			HookRegistry::call('AuthorAction::emailDirectorDecisionComment', array(&$authorSubmission, &$email));
			$email->send();

			$paperCommentDao = DAORegistry::getDAO('PaperCommentDAO');
			$paperComment = new PaperComment();
			$paperComment->setCommentType(COMMENT_TYPE_DIRECTOR_DECISION);
			$paperComment->setRoleId(ROLE_ID_AUTHOR);
			$paperComment->setPaperId($authorSubmission->getId());
			$paperComment->setAuthorId($authorSubmission->getUserId());
			$paperComment->setCommentTitle($email->getSubject());
			$paperComment->setComments($email->getBody());
			$paperComment->setDatePosted(Core::getCurrentDate());
			$paperComment->setViewable(true);
			$paperComment->setAssocId($authorSubmission->getId());
			$paperCommentDao->insertPaperComment($paperComment);

			return true;
		} else {
			if (!Request::getUserVar('continued')) {
				$email->setSubject($authorSubmission->getLocalizedTitle());
				if (!empty($directors)) {
					foreach ($directors as $director) {
						$email->addRecipient($director->getEmail(), $director->getFullName());
					}
				} else {
					$email->addRecipient($schedConf->getSetting('contactEmail'), $schedConf->getSetting('contactName'));
				}
			}

			$email->displayEditForm(Request::url(null, null, null, 'emailDirectorDecisionComment', 'send'), array('paperId' => $authorSubmission->getId()), 'submission/comment/directorDecisionEmail.tpl');

			return false;
		}
	}

	//
	// Misc
	//

	/**
	 * Download a file an author has access to.
	 * @param $paper object
	 * @param $fileId int
	 * @param $revision int
	 * @return boolean
	 * TODO: Complete list of files author has access to
	 */
	function downloadAuthorFile($paper, $fileId, $revision = null) {
		$authorSubmissionDao = DAORegistry::getDAO('AuthorSubmissionDAO');		

		$submission =& $authorSubmissionDao->getAuthorSubmission($paper->getId());

		$canDownload = false;

		// Authors have access to:
		// 1) The original submission file.
		// 2) Any files uploaded by the reviewers that are "viewable",
		//    although only after a decision has been made by the director.
		// 4) Any of the author-revised files.
		// 5) The layout version of the file.
		// 6) Any supplementary file
		// 7) Any galley file
		// 8) All review versions of the file
		// 9) Current director versions of the file
		// THIS LIST SHOULD NOW BE COMPLETE.
		if ($submission->getSubmissionFileId() == $fileId) {
			$canDownload = true;
		} else if ($submission->getRevisedFileId() == $fileId) {
			$canDownload = true;
		} else if ($submission->getLayoutFileId() == $fileId) {
			$canDownload = true;
		} else {
			// Check reviewer files
			foreach ($submission->getReviewAssignments(null) as $roundReviewAssignments) {
				foreach ($roundReviewAssignments as $reviewAssignment) {
					if ($reviewAssignment->getReviewerFileId() == $fileId) {
						$paperFileDao = DAORegistry::getDAO('PaperFileDAO');

						$paperFile =& $paperFileDao->getPaperFile($fileId, $revision);

						if ($paperFile != null && $paperFile->getViewable()) {
							$canDownload = true;
						}
					}
				}
			}

			// Check supplementary files
			foreach ($submission->getSuppFiles() as $suppFile) {
				if ($suppFile->getFileId() == $fileId) {
					$canDownload = true;
				}
			}

			// Check galley files
			foreach ($submission->getGalleys() as $galleyFile) {
				if ($galleyFile->getFileId() == $fileId) {
					$canDownload = true;
				}
			}

			// Check current review version
			$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
			$reviewFilesByRound =& $reviewAssignmentDao->getReviewFilesByRound($paper->getId());
			$reviewFile = @$reviewFilesByRound[$paper->getCurrentRound()];
			if ($reviewFile && $fileId == $reviewFile->getFileId()) {
				$canDownload = true;
			}

			// Check director version
			$directorFiles = $submission->getDirectorFileRevisions($paper->getCurrentRound());
			if (is_array($directorFiles)) foreach ($directorFiles as $directorFile) {
				if ($directorFile->getFileId() == $fileId) {
					$canDownload = true;
				}
			}
		}

		$result = false;
		if (!HookRegistry::call('AuthorAction::downloadAuthorFile', array(&$paper, &$fileId, &$revision, &$canDownload, &$result))) {
			if ($canDownload) {
				return Action::downloadFile($paper->getId(), $fileId, $revision);
			} else {
				return false;
			}
		}
		return $result;
	}

	function mayEditPaper(&$authorSubmission) {
		$schedConf =& Request::getSchedConf();
		if (!$schedConf || $schedConf->getId() != $authorSubmission->getSchedConfId()) {
			unset($schedConf);
			$schedConfDao = DAORegistry::getDAO('SchedConfDAO');
			$schedConf = $schedConfDao->getById($paper->getSchedConfId());
		}

		// Directors acting as Authors can always edit.
		if (Validation::isDirector($schedConf->getConferenceId(), $schedConf->getId()) || Validation::isTrackDirector($schedConf->getConferenceId(), $schedConf->getId())) return true;

		// Incomplete submissions can always be edited.
		if ($authorSubmission->getSubmissionProgress() != 0) return true;

		// Archived or declined submissions can never be edited.
		if ($authorSubmission->getStatus() == STATUS_ARCHIVED || $authorSubmission->getStatus() == STATUS_DECLINED) return false;

		// If the last recorded editorial decision on the current round
		// was "Revisions Required", the author may edit the submission.
		$decisions = $authorSubmission->getDecisions($authorSubmission->getCurrentRound());
		$decision = array_shift($decisions);
		if ($decision == SUBMISSION_DIRECTOR_DECISION_PENDING_REVISIONS) return true;

		// If there are open reviews for the submission, it may not be edited.
		$assignments = $authorSubmission->getReviewAssignments(null);
		if (is_array($assignments)) foreach ($assignments as $round => $roundAssignments) {
			if (is_array($roundAssignments)) foreach($roundAssignments as $assignment) {
				if (	!$assignment->getCancelled() &&
					!$assignment->getReplaced() &&
					!$assignment->getDeclined() &&
					$assignment->getDateCompleted() == null &&
					$assignment->getDateNotified() != null
				) {
					return false;
				}
			}
		}

		// If the conference isn't closed, the author may edit the submission.
		if (strtotime($schedConf->getEndDate()) > time()) return true;

		// Otherwise, edits are not allowed.
		return false;
	}
}

?>
