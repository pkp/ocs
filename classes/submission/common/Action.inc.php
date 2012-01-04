<?php

/**
 * @defgroup submission
 */
 
/**
 * @file Action.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Action
 * @ingroup submission
 *
 * @brief Action class.
 */

//$Id$

/* These constants correspond to editing decision "decision codes". */
define('SUBMISSION_DIRECTOR_DECISION_INVITE', 1);
define('SUBMISSION_DIRECTOR_DECISION_ACCEPT', 2);
define('SUBMISSION_DIRECTOR_DECISION_PENDING_REVISIONS', 3);
define('SUBMISSION_DIRECTOR_DECISION_DECLINE', 4);

class Action {
	/**
	 * View metadata of a paper.
	 * @param $paper object
	 */
	function viewMetadata($paper, $roleId) {
		if (!HookRegistry::call('Action::viewMetadata', array(&$paper, &$roleId))) {
			import("submission.form.MetadataForm");
			$metadataForm = new MetadataForm($paper, $roleId);
			if ($metadataForm->getCanEdit() && $metadataForm->isLocaleResubmit()) {
				$metadataForm->readInputData();
			} else {
				$metadataForm->initData();
			}
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
			$metadataForm = new MetadataForm($paper);
			$metadataForm->readInputData();

			// Check for any special cases before trying to save
			if (Request::getUserVar('addAuthor')) {
				// Add an author
				$editData = true;
				$authors = $metadataForm->getData('authors');
				array_push($authors, array());
				$metadataForm->setData('authors', $authors);

			} else if (($delAuthor = Request::getUserVar('delAuthor')) && count($delAuthor) == 1) {
				// Delete an author
				$editData = true;
				list($delAuthor) = array_keys($delAuthor);
				$delAuthor = (int) $delAuthor;
				$authors = $metadataForm->getData('authors');
				if (isset($authors[$delAuthor]['authorId']) && !empty($authors[$delAuthor]['authorId'])) {
					$deletedAuthors = explode(':', $metadataForm->getData('deletedAuthors'));
					array_push($deletedAuthors, $authors[$delAuthor]['authorId']);
					$metadataForm->setData('deletedAuthors', join(':', $deletedAuthors));
				}
				array_splice($authors, $delAuthor, 1);
				$metadataForm->setData('authors', $authors);

				if ($metadataForm->getData('primaryContact') == $delAuthor) {
					$metadataForm->setData('primaryContact', 0);
				}

			} else if (Request::getUserVar('moveAuthor')) {
				// Move an author up/down
				$editData = true;
				$moveAuthorDir = Request::getUserVar('moveAuthorDir');
				$moveAuthorDir = $moveAuthorDir == 'u' ? 'u' : 'd';
				$moveAuthorIndex = (int) Request::getUserVar('moveAuthorIndex');
				$authors = $metadataForm->getData('authors');

				if (!(($moveAuthorDir == 'u' && $moveAuthorIndex <= 0) || ($moveAuthorDir == 'd' && $moveAuthorIndex >= count($authors) - 1))) {
					$tmpAuthor = $authors[$moveAuthorIndex];
					$primaryContact = $metadataForm->getData('primaryContact');
					if ($moveAuthorDir == 'u') {
						$authors[$moveAuthorIndex] = $authors[$moveAuthorIndex - 1];
						$authors[$moveAuthorIndex - 1] = $tmpAuthor;
						if ($primaryContact == $moveAuthorIndex) {
							$metadataForm->setData('primaryContact', $moveAuthorIndex - 1);
						} else if ($primaryContact == ($moveAuthorIndex - 1)) {
							$metadataForm->setData('primaryContact', $moveAuthorIndex);
						}
					} else {
						$authors[$moveAuthorIndex] = $authors[$moveAuthorIndex + 1];
						$authors[$moveAuthorIndex + 1] = $tmpAuthor;
						if ($primaryContact == $moveAuthorIndex) {
							$metadataForm->setData('primaryContact', $moveAuthorIndex + 1);
						} else if ($primaryContact == ($moveAuthorIndex + 1)) {
							$metadataForm->setData('primaryContact', $moveAuthorIndex);
						}
					}
				}
				$metadataForm->setData('authors', $authors);
			}

			if (isset($editData)) {
				$metadataForm->display();
				return false;

			} else {
				if (!$metadataForm->validate()) {
					return $metadataForm->display();
				}
				$metadataForm->execute();
				
				// Send a notification to associated users
				import('notification.NotificationManager');
				$notificationManager = new NotificationManager();
				$notificationUsers = $paper->getAssociatedUserIds();
				foreach ($notificationUsers as $userRole) {
					$url = Request::url(null, null, $userRole['role'], 'submission', $paper->getId(), null, 'metadata');
					$notificationManager->createNotification(
						$userRole['id'], 'notification.type.metadataModified',
						$paper->getLocalizedTitle(), $url, 1, NOTIFICATION_TYPE_METADATA_MODIFIED
					);
				}


				// Add log entry
				$user =& Request::getUser();
				import('paper.log.PaperLog');
				import('paper.log.PaperEventLogEntry');
				PaperLog::logEvent($paper->getId(), PAPER_LOG_METADATA_UPDATE, LOG_TYPE_DEFAULT, 0, 'log.director.metadataModified', array('directorName' => $user->getFullName()));

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
		$paperFileManager = new PaperFileManager($paperId);
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
		$paperFileManager = new PaperFileManager($paperId);
		return $paperFileManager->viewFile($fileId, $revision);
	}

	/**
	 * Edit comment.
	 * @param $commentId int
	 */
	function editComment($paper, $comment) {
		if (!HookRegistry::call('Action::editComment', array(&$paper, &$comment))) {
			import("submission.form.comment.EditCommentForm");

			$commentForm = new EditCommentForm($paper, $comment);
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

			$commentForm = new EditCommentForm($paper, $comment);
			$commentForm->readInputData();

			if ($commentForm->validate()) {
				$commentForm->execute();
				
				// Send a notification to associated users
				import('notification.NotificationManager');
				$notificationManager = new NotificationManager();
				$notificationUsers = $paper->getAssociatedUserIds(true, false);
				foreach ($notificationUsers as $userRole) {
					$url = Request::url(null, null, $userRole['role'], 'submissionReview', $paper->getId(), null, 'editorDecision');
					$notificationManager->createNotification(
						$userRole['id'], 'notification.type.submissionComment',
						$paper->getLocalizedTitle(), $url, 1, NOTIFICATION_TYPE_SUBMISSION_COMMENT
					);
				}


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
		if ($user == null) $user =& Request::getUser();

		$paperCommentDao =& DAORegistry::getDAO('PaperCommentDAO');
		$comment =& $paperCommentDao->getPaperCommentById($commentId);

		if ($comment->getAuthorId() == $user->getId()) {
			if (!HookRegistry::call('Action::deleteComment', array(&$comment))) {
				$paperCommentDao->deletePaperComment($comment);
			}
		}
	}
}

?>
