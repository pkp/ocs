<?php

/**
 * SubmissionCommentsHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.presenter
 *
 * Handle requests for submission comments. 
 *
 * $Id$
 */

import('pages.presenter.TrackSubmissionHandler');

class SubmissionCommentsHandler extends PresenterHandler {
	
	/**
	 * View director decision comments.
	 */
	function viewDirectorDecisionComments($args) {
		PresenterHandler::validate();
		PresenterHandler::setupTemplate(true);
		
		$paperId = $args[0];
		
		list($conference, $schedConf, $presenterSubmission) = TrackSubmissionHandler::validate($paperId);
		PresenterAction::viewDirectorDecisionComments($presenterSubmission);
	}
	
	/**
	 * View layout comments.
	 */
	function viewLayoutComments($args) {
		PresenterHandler::validate();
		PresenterHandler::setupTemplate(true);

		$paperId = $args[0];

		list($conference, $schedConf, $presenterSubmission) = TrackSubmissionHandler::validate($paperId);
		PresenterAction::viewLayoutComments($presenterSubmission);

	}

	/**
	 * Post layout comment.
	 */
	function postLayoutComment() {
		PresenterHandler::validate();
		PresenterHandler::setupTemplate(true);
		
		$paperId = Request::getUserVar('paperId');
		
		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;
		
		list($conference, $schedConf, $presenterSubmission) = TrackSubmissionHandler::validate($paperId);
		if (PresenterAction::postLayoutComment($presenterSubmission, $emailComment)) {
			PresenterAction::viewLayoutComments($presenterSubmission);
		}
	}

	/**
	 * Email a director decision comment.
	 */
	function emailDirectorDecisionComment() {
		$paperId = (int) Request::getUserVar('paperId');
		list($conference, $schedConf, $submission) = TrackSubmissionHandler::validate($paperId);

		parent::setupTemplate(true);		
		if (PresenterAction::emailDirectorDecisionComment($submission, Request::getUserVar('send'))) {
			Request::redirect(null, null, null, 'submissionReview', array($paperId));
		}
	}
	
	/**
	 * Edit comment.
	 */
	function editComment($args) {
		PresenterHandler::validate();
		PresenterHandler::setupTemplate(true);
		
		$paperId = $args[0];
		$commentId = $args[1];
		
		list($conference, $schedConf, $presenterSubmission) = TrackSubmissionHandler::validate($paperId);
		list($comment) = SubmissionCommentsHandler::validate($commentId);

		if ($comment->getCommentType() == COMMENT_TYPE_DIRECTOR_DECISION) {
			// Cannot edit a director decision comment.
			Request::redirect(null, null, Request::getRequestedPage());
		}

		PresenterAction::editComment($presenterSubmission, $comment);

	}
	
	/**
	 * Save comment.
	 */
	function saveComment() {
		PresenterHandler::validate();
		PresenterHandler::setupTemplate(true);
		
		$paperId = Request::getUserVar('paperId');
		$commentId = Request::getUserVar('commentId');
		
		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;
		
		list($conference, $schedConf, $presenterSubmission) = TrackSubmissionHandler::validate($paperId);
		list($comment) = SubmissionCommentsHandler::validate($commentId);

		if ($comment->getCommentType() == COMMENT_TYPE_DIRECTOR_DECISION) {
			// Cannot edit a director decision comment.
			Request::redirect(null, null, Request::getRequestedPage());
		}

		PresenterAction::saveComment($presenterSubmission, $comment, $emailComment);

		$paperCommentDao = &DAORegistry::getDAO('PaperCommentDAO');
		$comment = &$paperCommentDao->getPaperCommentById($commentId);
		
		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_DIRECTOR_DECISION) {
			Request::redirect(null, null, null, 'viewDirectorDecisionComments', $paperId);
		} else if ($comment->getCommentType() == COMMENT_TYPE_LAYOUT) {
			Request::redirect(null, null, null, 'viewLayoutComments', $paperId);
		}
	}
	
	/**
	 * Delete comment.
	 */
	function deleteComment($args) {
		PresenterHandler::validate();
		PresenterHandler::setupTemplate(true);
		
		$paperId = $args[0];
		$commentId = $args[1];
		
		$paperCommentDao = &DAORegistry::getDAO('PaperCommentDAO');
		$comment = &$paperCommentDao->getPaperCommentById($commentId);
		
		list($conference, $schedConf, $presenterSubmission) = TrackSubmissionHandler::validate($paperId);
		list($comment) = SubmissionCommentsHandler::validate($commentId);
		PresenterAction::deleteComment($commentId);
		
		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_DIRECTOR_DECISION) {
			Request::redirect(null, null, null, 'viewDirectorDecisionComments', $paperId);
		} else if ($comment->getCommentType() == COMMENT_TYPE_LAYOUT) {
			Request::redirect(null, null, null, 'viewLayoutComments', $paperId);
		}
	}
	
	
	//
	// Validation
	//
	
	/**
	 * Validate that the user is the presenter of the comment.
	 */
	function validate($commentId) {
		parent::validate();
		
		$isValid = true;
		
		$paperCommentDao = &DAORegistry::getDAO('PaperCommentDAO');
		$user = &Request::getUser();
		
		$comment = &$paperCommentDao->getPaperCommentById($commentId);

		if ($comment == null) {
			$isValid = false;
			
		} else if ($comment->getAuthorId() != $user->getUserId()) {
			$isValid = false;
		}
		
		if (!$isValid) {
			Request::redirect(null, null, Request::getRequestedPage());
		}

		return array($comment);
	}
}
?>
