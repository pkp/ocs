<?php

/**
 * @file Action.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 * @class Action
 *
 * Action class.
 *
 * $Id$
 */

/* These constants correspond to editing decision "decision codes". */
define('SUBMISSION_DIRECTOR_DECISION_INVITE', 1);
define('SUBMISSION_DIRECTOR_DECISION_ACCEPT', 2);
define('SUBMISSION_DIRECTOR_DECISION_PENDING_REVISIONS', 3);
define('SUBMISSION_DIRECTOR_DECISION_DECLINE', 4);

class Action {

	/**
	 * Constructor.
	 */
	function Action() {

	}
	
	/**
	 * Actions.
	 */
	 
	/**
	 * View metadata of a paper.
	 * @param $paper object
	 */
	function viewMetadata($paper, $roleId) {
		if (!HookRegistry::call('Action::viewMetadata', array(&$paper, &$roleId))) {
			import("submission.form.MetadataForm");
			$metadataForm = &new MetadataForm($paper, $roleId);
			$metadataForm->initData();
			$metadataForm->display();
		}
	}
	
	/**
	 * Save metadata.
	 * @param $paper object
	 */
	function saveMetadata($paper) {
		if (!HookRegistry::call('Action::saveMetadata', array(&$paper))) {
			import("submission.form.MetadataForm");
			$metadataForm = &new MetadataForm($paper);
			$metadataForm->readInputData();

			if (!$metadataForm->validate()) {
				return $metadataForm->display();
			}

			// Check for any special cases before trying to save
			if (Request::getUserVar('addPresenter')) {
				// Add an presenter
				$editData = true;
				$presenters = $metadataForm->getData('presenters');
				array_push($presenters, array());
				$metadataForm->setData('presenters', $presenters);
			
			} else if (($delPresenter = Request::getUserVar('delPresenter')) && count($delPresenter) == 1) {
				// Delete an presenter
				$editData = true;
				list($delPresenter) = array_keys($delPresenter);
				$delPresenter = (int) $delPresenter;
				$presenters = $metadataForm->getData('presenters');
				if (isset($presenters[$delPresenter]['presenterId']) && !empty($presenters[$delPresenter]['presenterId'])) {
					$deletedPresenters = explode(':', $metadataForm->getData('deletedPresenters'));
					array_push($deletedPresenters, $presenters[$delPresenter]['presenterId']);
					$metadataForm->setData('deletedPresenters', join(':', $deletedPresenters));
				}
				array_splice($presenters, $delPresenter, 1);
				$metadataForm->setData('presenters', $presenters);
					
				if ($metadataForm->getData('primaryContact') == $delPresenter) {
					$metadataForm->setData('primaryContact', 0);
				}
					
			} else if (Request::getUserVar('movePresenter')) {
				// Move an presenter up/down
				$editData = true;
				$movePresenterDir = Request::getUserVar('movePresenterDir');
				$movePresenterDir = $movePresenterDir == 'u' ? 'u' : 'd';
				$movePresenterIndex = (int) Request::getUserVar('movePresenterIndex');
				$presenters = $metadataForm->getData('presenters');
			
				if (!(($movePresenterDir == 'u' && $movePresenterIndex <= 0) || ($movePresenterDir == 'd' && $movePresenterIndex >= count($presenters) - 1))) {
					$tmpPresenter = $presenters[$movePresenterIndex];
					$primaryContact = $metadataForm->getData('primaryContact');
					if ($movePresenterDir == 'u') {
						$presenters[$movePresenterIndex] = $presenters[$movePresenterIndex - 1];
						$presenters[$movePresenterIndex - 1] = $tmpPresenter;
						if ($primaryContact == $movePresenterIndex) {
							$metadataForm->setData('primaryContact', $movePresenterIndex - 1);
						} else if ($primaryContact == ($movePresenterIndex - 1)) {
							$metadataForm->setData('primaryContact', $movePresenterIndex);
						}
					} else {
						$presenters[$movePresenterIndex] = $presenters[$movePresenterIndex + 1];
						$presenters[$movePresenterIndex + 1] = $tmpPresenter;
						if ($primaryContact == $movePresenterIndex) {
							$metadataForm->setData('primaryContact', $movePresenterIndex + 1);
						} else if ($primaryContact == ($movePresenterIndex + 1)) {
							$metadataForm->setData('primaryContact', $movePresenterIndex);
						}
					}
				}
				$metadataForm->setData('presenters', $presenters);
			}
		
			if (isset($editData)) {
				$metadataForm->display();
				return false;
			
			} else {
				$metadataForm->execute();

				// Add log entry
				$user = &Request::getUser();
				import('paper.log.PaperLog');
				import('paper.log.PaperEventLogEntry');
				PaperLog::logEvent($paper->getPaperId(), PAPER_LOG_METADATA_UPDATE, LOG_TYPE_DEFAULT, 0, 'log.director.metadataModified', Array('directorName' => $user->getFullName()));

				return true;
			}
		}
	}
	
	/**
	 * Download file.
	 * @param $paperId int
	 * @param $fileId int
	 * @param $revision int
	 */
	function downloadFile($paperId, $fileId, $revision = null) {
		import('file.PaperFileManager');
		$paperFileManager = &new PaperFileManager($paperId);
		return $paperFileManager->downloadFile($fileId, $revision);
	}
	
	/**
	 * View file.
	 * @param $paperId int
	 * @param $fileId int
	 * @param $revision int
	 */
	function viewFile($paperId, $fileId, $revision = null) {
		import('file.PaperFileManager');
		$paperFileManager = &new PaperFileManager($paperId);
		return $paperFileManager->viewFile($fileId, $revision);
	}
	
	/**
	 * Edit comment.
	 * @param $commentId int
	 */
	function editComment($paper, $comment) {
		if (!HookRegistry::call('Action::editComment', array(&$paper, &$comment))) {
			import("submission.form.comment.EditCommentForm");
		
			$commentForm = &new EditCommentForm($paper, $comment);
			$commentForm->initData();
			$commentForm->display();
		}
	}
	
	/**
	 * Save comment.
	 * @param $commentId int
	 */
	function saveComment($paper, &$comment, $emailComment) {
		if (!HookRegistry::call('Action::saveComment', array(&$paper, &$comment, &$emailComment))) {
			import("submission.form.comment.EditCommentForm");
		
			$commentForm = &new EditCommentForm($paper, $comment);
			$commentForm->readInputData();
		
			if ($commentForm->validate()) {
				$commentForm->execute();
			
				if ($emailComment) {
					$commentForm->email($commentForm->emailHelper());
				}
			
			} else {
				$commentForm->display();
			}
		}
	}
	
	/**
	 * Delete comment.
	 * @param $commentId int
	 * @param $user object The user who owns the comment, or null to default to Request::getUser
	 */
	function deleteComment($commentId, $user = null) {
		if ($user == null) $user = &Request::getUser();
	
		$paperCommentDao = &DAORegistry::getDAO('PaperCommentDAO');
		$comment = &$paperCommentDao->getPaperCommentById($commentId);
		
		if ($comment->getAuthorId() == $user->getUserId()) {
			if (!HookRegistry::call('Action::deleteComment', array(&$comment))) {
				$paperCommentDao->deletePaperComment($comment);
			}
		}
	}
}

?>
