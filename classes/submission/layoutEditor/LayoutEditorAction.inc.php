<?php

/**
 * LayoutEditorAction.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission.layoutEditor.LayoutEditorAction
 *
 * LayoutEditorAction class.
 *
 * $Id$
 */

import('submission.common.Action');

class LayoutEditorAction extends Action {
	
	//
	// Actions
	//

	/**
	 * Change the sequence order of a galley.
	 * @param $paper object
	 * @param $galleyId int
	 * @param $direction char u = up, d = down
	 */
	function orderGalley($paper, $galleyId, $direction) {
		$galleyDao = &DAORegistry::getDAO('PaperGalleyDAO');
		$galley = &$galleyDao->getGalley($galleyId, $paper->getPaperId());
		
		if (isset($galley)) {
			$galley->setSequence($galley->getSequence() + ($direction == 'u' ? -1.5 : 1.5));
			$galleyDao->updateGalley($galley);
			$galleyDao->resequenceGalleys($paper->getPaperId());
		}
	}
	
	/**
	 * Delete a galley.
	 * @param $paper object
	 * @param $galleyId int
	 */
	function deleteGalley($paper, $galleyId) {
		import('file.PaperFileManager');
		
		$galleyDao = &DAORegistry::getDAO('PaperGalleyDAO');
		$galley = &$galleyDao->getGalley($galleyId, $paper->getPaperId());
		
		if (isset($galley) && !HookRegistry::call('LayoutEditorAction::deleteGalley', array(&$paper, &$galley))) {
			$paperFileManager = &new PaperFileManager($paper->getPaperId());
			
			if ($galley->getFileId()) {
				$paperFileManager->deleteFile($galley->getFileId());
				import('search.PaperSearchIndex');
				PaperSearchIndex::deleteTextIndex($paper->getPaperId(), PAPER_SEARCH_GALLEY_FILE, $galley->getFileId());
			}
			if ($galley->isHTMLGalley()) {
				if ($galley->getStyleFileId()) {
					$paperFileManager->deleteFile($galley->getStyleFileId());
				}
				foreach ($galley->getImageFiles() as $image) {
					$paperFileManager->deleteFile($image->getFileId());
				}
			}
			$galleyDao->deleteGalley($galley);
		}
	}
	
	/**
	 * Delete an image from an paper galley.
	 * @param $submission object
	 * @param $fileId int
	 * @param $revision int (optional)
	 */
	function deletePaperImage($submission, $fileId, $revision) {
		import('file.PaperFileManager');
		$paperGalleyDao =& DAORegistry::getDAO('PaperGalleyDAO');
		if (HookRegistry::call('LayoutEditorAction::deletePaperImage', array(&$submission, &$fileId, &$revision))) return;
		foreach ($submission->getGalleys() as $galley) {
			$images =& $paperGalleyDao->getGalleyImages($galley->getGalleyId());
			foreach ($images as $imageFile) {
				if ($imageFile->getPaperId() == $submission->getPaperId() && $fileId == $imageFile->getFileId() && $imageFile->getRevision() == $revision) {
					$paperFileManager = &new PaperFileManager($submission->getPaperId());
					$paperFileManager->deleteFile($imageFile->getFileId(), $imageFile->getRevision());
				}
			}
			unset($images);
		}
	}

	/**
	 * Change the sequence order of a supplementary file.
	 * @param $paper object
	 * @param $suppFileId int
	 * @param $direction char u = up, d = down
	 */
	function orderSuppFile($paper, $suppFileId, $direction) {
		$suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		$suppFile = &$suppFileDao->getSuppFile($suppFileId, $paper->getPaperId());
		
		if (isset($suppFile)) {
			$suppFile->setSequence($suppFile->getSequence() + ($direction == 'u' ? -1.5 : 1.5));
			$suppFileDao->updateSuppFile($suppFile);
			$suppFileDao->resequenceSuppFiles($paper->getPaperId());
		}
	}
	
	/**
	 * Delete a supplementary file.
	 * @param $paper object
	 * @param $suppFileId int
	 */
	function deleteSuppFile($paper, $suppFileId) {
		import('file.PaperFileManager');
		
		$suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		
		$suppFile = &$suppFileDao->getSuppFile($suppFileId, $paper->getPaperId());
		if (isset($suppFile) && !HookRegistry::call('LayoutEditorAction::deleteSuppFile', array(&$paper, &$suppFile))) {
			if ($suppFile->getFileId()) {
				$paperFileManager = &new PaperFileManager($paper->getPaperId());
				$paperFileManager->deleteFile($suppFile->getFileId());
				import('search.PaperSearchIndex');
				PaperSearchIndex::deleteTextIndex($paper->getPaperId(), PAPER_SEARCH_SUPPLEMENTARY_FILE, $suppFile->getFileId());
			}
			$suppFileDao->deleteSuppFile($suppFile);
		}
	}
	
	/**
	 * Marks layout assignment as completed.
	 * @param $submission object
	 * @param $send boolean
	 */
	function completeLayoutEditing($submission, $send = false) {
		$submissionDao = &DAORegistry::getDAO('LayoutEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$schedConf = &Request::getSchedConf();
		
		$layoutAssignment = &$submission->getLayoutAssignment();
		if ($layoutAssignment->getDateCompleted() != null) {
			return true;
		}
		
		import('mail.PaperMailTemplate');
		$email = &new PaperMailTemplate($submission, 'LAYOUT_COMPLETE');

		$editAssignments = &$submission->getEditAssignments();
		if (empty($editAssignments)) return;
		
		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			HookRegistry::call('LayoutEditorAction::completeLayoutEditing', array(&$submission, &$layoutAssignment, &$editAssignments, &$email));
			if ($email->isEnabled()) {
				$email->setAssoc(PAPER_EMAIL_LAYOUT_NOTIFY_COMPLETE, PAPER_EMAIL_TYPE_LAYOUT, $layoutAssignment->getLayoutId());
				$email->send();
			}
				
			$layoutAssignment->setDateCompleted(Core::getCurrentDate());
			$submissionDao->updateSubmission($submission);

			// Add log entry
			$user = &Request::getUser();
			import('paper.log.PaperLog');
			import('paper.log.PaperEventLogEntry');
			PaperLog::logEvent($submission->getPaperId(), PAPER_LOG_LAYOUT_COMPLETE, PAPER_LOG_TYPE_LAYOUT, $user->getUserId(), 'log.layout.layoutEditComplete', Array('editorName' => $user->getFullName(), 'paperId' => $submission->getPaperId()));
			
			return true;
		} else {
			$user = &Request::getUser();
			if (!Request::getUserVar('continued')) {
				$assignedTrackDirectors = $email->toAssignedEditingTrackDirectors($submission->getPaperId());
				$assignedDirectors = $email->ccAssignedDirectors($submission->getPaperId());
				if (empty($assignedTrackDirectors) && empty($assignedDirectors)) {
					$email->addRecipient($schedConf->getSetting('contactEmail'), $schedConf->getSetting('contactName'));
					$editorialContactName = $schedConf->getSetting('contactName');
				} else {
					$editorialContact = array_shift($assignedTrackDirectors);
					if (!$editorialContact) $editorialContact = array_shift($assignedDirectors);
					$editorialContactName = $editorialContact->getDirectorFullName();
				}
				$paramArray = array(
					'editorialContactName' => $editorialContactName,
					'layoutEditorName' => $user->getFullName()
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::url(null, 'layoutEditor', 'completeAssignment', 'send'), array('paperId' => $submission->getPaperId()));

			return false;
		}
	}
	
	/**
	 * Upload the layout version of an paper.
	 * @param $submission object
	 */
	function uploadLayoutVersion($submission) {
		import('file.PaperFileManager');
		$paperFileManager = &new PaperFileManager($submission->getPaperId());
		$layoutEditorSubmissionDao = &DAORegistry::getDAO('LayoutEditorSubmissionDAO');
		
		$layoutDao = &DAORegistry::getDAO('LayoutAssignmentDAO');
		$layoutAssignment = &$layoutDao->getLayoutAssignmentByPaperId($submission->getPaperId());
		
		$fileName = 'layoutFile';
		if ($paperFileManager->uploadedFileExists($fileName) && !HookRegistry::call('LayoutEditorAction::uploadLayoutVersion', array(&$submission, &$layoutAssignment))) {
			$layoutFileId = $paperFileManager->uploadLayoutFile($fileName, $layoutAssignment->getLayoutFileId());
			$layoutAssignment->setLayoutFileId($layoutFileId);
			$layoutDao->updateLayoutAssignment($layoutAssignment);
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
		if (!HookRegistry::call('LayoutEditorAction::viewLayoutComments', array(&$paper))) {
			import("submission.form.comment.LayoutCommentForm");
		
			$commentForm = &new LayoutCommentForm($paper, ROLE_ID_LAYOUT_EDITOR);
			$commentForm->initData();
			$commentForm->display();
		}
	}
	
	/**
	 * Post layout comment.
	 * @param $paper object
	 */
	function postLayoutComment($paper, $emailComment) {
		if (!HookRegistry::call('LayoutEditorAction::postLayoutComment', array(&$paper, &$emailComment))) {
			import("submission.form.comment.LayoutCommentForm");
		
			$commentForm = &new LayoutCommentForm($paper, ROLE_ID_LAYOUT_EDITOR);
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
	
	//
	// Misc
	//
	
	/**
	 * Download a file a layout editor has access to.
	 * This includes: The layout editor submission file, supplementary files, and galley files.
	 * @param $paper object
	 * @parma $fileId int
	 * @param $revision int optional
	 * @return boolean
	 */
	function downloadFile($paper, $fileId, $revision = null) {
		$canDownload = false;
		
		$layoutDao = &DAORegistry::getDAO('LayoutAssignmentDAO');
		$galleyDao = &DAORegistry::getDAO('PaperGalleyDAO');
		$suppDao = &DAORegistry::getDAO('SuppFileDAO');
		
		$layoutAssignment = &$layoutDao->getLayoutAssignmentByPaperId($paper->getPaperId());
		
		if ($layoutAssignment->getLayoutFileId() == $fileId) {
			$canDownload = true;
			
		} else if($galleyDao->galleyExistsByFileId($paper->getPaperId(), $fileId)) {
			$canDownload = true;
			
		} else if($suppDao->suppFileExistsByFileId($paper->getPaperId(), $fileId)) {
			$canDownload = true;
		}

		$result = false;
		if (!HookRegistry::call('LayoutEditorAction::downloadFile', array(&$paper, &$fileId, &$revision, &$canDownload, &$result))) {
			if ($canDownload) {
				return parent::downloadFile($paper->getPaperId(), $fileId, $revision);
			} else {
				return false;
			}
		}
		return $result;
	}
}

?>
