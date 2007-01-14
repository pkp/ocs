<?php

/**
 * AuthorAction.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * AuthorAction class.
 *
 * $Id$
 */

import('submission.common.Action');

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
	 * @param $designate boolean
	 */
	function designateReviewVersion($authorSubmission, $designate = false) {
		import('file.PaperFileManager');
		$paperFileManager = &new PaperFileManager($authorSubmission->getPaperId());
		$authorSubmissionDao = &DAORegistry::getDAO('AuthorSubmissionDAO');
		
		if ($designate && !HookRegistry::call('AuthorAction::designateReviewVersion', array(&$authorSubmission))) {
			$submissionFile =& $authorSubmission->getSubmissionFile();
			if ($submissionFile) {
				$reviewFileId = $paperFileManager->copyToReviewFile($submissionFile->getFileId());

				$authorSubmission->setReviewFileId($reviewFileId);
			
				$authorSubmissionDao->updateAuthorSubmission($authorSubmission);

				$trackEditorSubmissionDao =& DAORegistry::getDAO('TrackEditorSubmissionDAO');
				$trackEditorSubmissionDao->createReviewRound($authorSubmission->getPaperId(), 1, 1, 1);
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
		import('file.PaperFileManager');

		$paperFileManager = &new PaperFileManager($paper->getPaperId());
		$paperFileDao = &DAORegistry::getDAO('PaperFileDAO');
		$authorSubmissionDao = &DAORegistry::getDAO('AuthorSubmissionDAO');

		$paperFile = &$paperFileDao->getPaperFile($fileId, $revisionId, $paper->getPaperId());
		$authorSubmission = $authorSubmissionDao->getAuthorSubmission($paper->getPaperId());
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
	 * Upload the revised version of an paper.
	 * @param $authorSubmission object
	 */
	function uploadRevisedVersion($authorSubmission) {
		import("file.PaperFileManager");
		$paperFileManager = &new PaperFileManager($authorSubmission->getPaperId());
		$authorSubmissionDao = &DAORegistry::getDAO('AuthorSubmissionDAO');
		
		$fileName = 'upload';
		if ($paperFileManager->uploadedFileExists($fileName)) {
			HookRegistry::call('AuthorAction::uploadRevisedVersion', array(&$authorSubmission));
			if ($authorSubmission->getRevisedFileId() != null) {
				$fileId = $paperFileManager->uploadEditorDecisionFile($fileName, $authorSubmission->getRevisedFileId());
			} else {
				$fileId = $paperFileManager->uploadEditorDecisionFile($fileName);
			}
		}
		
		if (isset($fileId) && $fileId != 0) {
			$authorSubmission->setRevisedFileId($fileId);
			
			$authorSubmissionDao->updateAuthorSubmission($authorSubmission);

			// Add log entry
			$user = &Request::getUser();
			import('paper.log.PaperLog');
			import('paper.log.PaperEventLogEntry');
			PaperLog::logEvent($authorSubmission->getPaperId(), PAPER_LOG_AUTHOR_REVISION, LOG_TYPE_AUTHOR, $user->getUserId(), 'log.author.documentRevised', array('authorName' => $user->getFullName(), 'fileId' => $fileId, 'paperId' => $authorSubmission->getPaperId()));
		}
	}
	
	//
	// Comments
	//
	
	/**
	 * View layout comments.
	 * @param $paper object
	 */
	function viewLayoutComments($paper) {
		if (!HookRegistry::call('AuthorAction::viewLayoutComments', array(&$paper))) {
			import("submission.form.comment.LayoutCommentForm");
			$commentForm = &new LayoutCommentForm($paper, ROLE_ID_EDITOR);
			$commentForm->initData();
			$commentForm->display();
		}
	}
	
	/**
	 * Post layout comment.
	 * @param $paper object
	 * @param $emailComment boolean
	 */
	function postLayoutComment($paper, $emailComment) {
		if (!HookRegistry::call('AuthorAction::postLayoutComment', array(&$paper, &$emailComment))) {
			import("submission.form.comment.LayoutCommentForm");

			$commentForm = &new LayoutCommentForm($paper, ROLE_ID_AUTHOR);
			$commentForm->readInputData();
		
			if ($commentForm->validate()) {
				$commentForm->execute();
				
				if ($emailComment) {
					$commentForm->email();
				}
			
			} else {
				$commentForm->display();
				return false;
			}
			return true;
		}
	}
	
	/**
	 * View editor decision comments.
	 * @param $paper object
	 */
	function viewEditorDecisionComments($paper) {
		if (!HookRegistry::call('AuthorAction::viewEditorDecisionComments', array(&$paper))) {
			import("submission.form.comment.EditorDecisionCommentForm");

			$commentForm = &new EditorDecisionCommentForm($paper, ROLE_ID_AUTHOR);
			$commentForm->initData();
			$commentForm->display();
		}
	}
	
	/**
	 * Email editor decision comment.
	 * @param $authorSubmission object
	 * @param $send boolean
	 */
	function emailEditorDecisionComment($authorSubmission, $send) {
		$userDao = &DAORegistry::getDAO('UserDAO');
		$conference = &Request::getConference();

		$user = &Request::getUser();
		import('mail.PaperMailTemplate');
		$email = &new PaperMailTemplate($authorSubmission);
	
		$editAssignments = $authorSubmission->getEditAssignments();
		$editors = array();
		foreach ($editAssignments as $editAssignment) {
			array_push($editors, $userDao->getUser($editAssignment->getEditorId()));
		}

		if ($send && !$email->hasErrors()) {
			HookRegistry::call('AuthorAction::emailEditorDecisionComment', array(&$authorSubmission, &$email));
			$email->send();

			$paperCommentDao =& DAORegistry::getDAO('PaperCommentDAO');
			$paperComment =& new PaperComment();
			$paperComment->setCommentType(COMMENT_TYPE_EDITOR_DECISION);
			$paperComment->setRoleId(ROLE_ID_AUTHOR);
			$paperComment->setPaperId($authorSubmission->getPaperId());
			$paperComment->setAuthorId($authorSubmission->getUserId());
			$paperComment->setCommentTitle($email->getSubject());
			$paperComment->setComments($email->getBody());
			$paperComment->setDatePosted(Core::getCurrentDate());
			$paperComment->setViewable(true);
			$paperComment->setAssocId($authorSubmission->getPaperId());
			$paperCommentDao->insertPaperComment($paperComment);

			return true;
		} else {
			if (!Request::getUserVar('continued')) {
				$email->setSubject($authorSubmission->getPaperTitle());
				if (!empty($editors)) {
					foreach ($editors as $editor) {
						$email->addRecipient($editor->getEmail(), $editor->getFullName());
					}
				} else {
					$email->addRecipient($conference->getSetting('contactEmail'), $conference->getSetting('contactName'));
				}
			}

			$email->displayEditForm(Request::url(null, null, null, 'emailEditorDecisionComment', 'send'), array('paperId' => $authorSubmission->getPaperId()), 'submission/comment/editorDecisionEmail.tpl');

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
		$authorSubmissionDao = &DAORegistry::getDAO('AuthorSubmissionDAO');		

		$submission = &$authorSubmissionDao->getAuthorSubmission($paper->getPaperId());

		$canDownload = false;
		
		// Authors have access to:
		// 1) The original submission file.
		// 2) Any files uploaded by the reviewers that are "viewable",
		//    although only after a decision has been made by the editor.
		// 4) Any of the author-revised files.
		// 5) The layout version of the file.
		// 6) Any supplementary file
		// 7) Any galley file
		// 8) All review versions of the file
		// 9) Current editor versions of the file
		// THIS LIST SHOULD NOW BE COMPLETE.
		if ($submission->getSubmissionFileId() == $fileId) {
			$canDownload = true;
		} else if ($submission->getRevisedFileId() == $fileId) {
			$canDownload = true;
		} else if ($layoutAssignment->getLayoutFileId() == $fileId) {
			$canDownload = true;
		} else {
			// Check reviewer files
			foreach ($submission->getReviewAssignments(null, null) as $typeReviewAssignments) {
				foreach($typeReviewAssignments as $roundReviewAssignments) {
					foreach ($roundReviewAssignments as $reviewAssignment) {
						if ($reviewAssignment->getReviewerFileId() == $fileId) {
							$paperFileDao = &DAORegistry::getDAO('PaperFileDAO');
						
							$paperFile = &$paperFileDao->getPaperFile($fileId, $revision);
						
							if ($paperFile != null && $paperFile->getViewable()) {
								$canDownload = true;
							}
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
			$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
			$reviewFilesByRound =& $reviewAssignmentDao->getReviewFilesByRound($paper->getPaperId());
			$reviewFile = @$reviewFilesByRound[$paper->getCurrentRound()];
			if ($reviewFile && $fileId == $reviewFile->getFileId()) {
				$canDownload = true;
			}

			// Check editor version
			$editorFiles = $submission->getEditorFileRevisions($paper->getCurrentRound());
			foreach ($editorFiles as $editorFile) {
				if ($editorFile->getFileId() == $fileId) {
					$canDownload = true;
				}
			}
		}
		
		$result = false;
		if (!HookRegistry::call('AuthorAction::downloadAuthorFile', array(&$paper, &$fileId, &$revision, &$canDownload, &$result))) {
			if ($canDownload) {
				return Action::downloadFile($paper->getPaperId(), $fileId, $revision);
			} else {
				return false;
			}
		}
		return $result;
	}
}

?>
