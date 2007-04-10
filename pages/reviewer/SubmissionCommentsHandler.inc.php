<?php

/**
 * SubmissionCommentsHandler.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.reviewer
 *
 * Handle requests for submission comments. 
 *
 * $Id$
 */

import('pages.reviewer.SubmissionReviewHandler');

class SubmissionCommentsHandler extends ReviewerHandler {
	
	/**
	 * View peer review comments.
	 */
	function viewPeerReviewComments($args) {
		$paperId = $args[0];
		$reviewId = $args[1];

		list($schedConf, $submission, $user) = SubmissionReviewHandler::validate($reviewId);
		ReviewerHandler::setupTemplate(true);
		ReviewerAction::viewPeerReviewComments($user, $submission, $reviewId);
	
	}
	
	/**
	 * Post peer review comments.
	 */
	function postPeerReviewComment() {
		$paperId = Request::getUserVar('paperId');
		$reviewId = Request::getUserVar('reviewId');
		
		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;
		
		list($schedConf, $submission, $user) = SubmissionReviewHandler::validate($reviewId);

		ReviewerHandler::setupTemplate(true);
		if (ReviewerAction::postPeerReviewComment($user, $submission, $reviewId, $emailComment)) {
			ReviewerAction::viewPeerReviewComments($user, $submission, $reviewId);
		}
	}
	
	/**
	 * Edit comment.
	 */
	function editComment($args) {
		$paperId = $args[0];
		$commentId = $args[1];
		$reviewId = Request::getUserVar('reviewId');

		$paperDao = &DAORegistry::getDAO('PaperDAO');
		$paper = $paperDao->getPaper($paperId);

		list($schedConf, $submission, $user) = SubmissionReviewHandler::validate($reviewId);
		list($comment) = SubmissionCommentsHandler::validate($user, $commentId);

		ReviewerHandler::setupTemplate(true);

		ReviewerAction::editComment($paper, $comment, $reviewId);
	}
	
	/**
	 * Save comment.
	 */
	function saveComment() {
		$paperId = Request::getUserVar('paperId');
		$commentId = Request::getUserVar('commentId');
		$reviewId = Request::getUserVar('reviewId');
		
		$paperDao = &DAORegistry::getDAO('PaperDAO');
		$paper = $paperDao->getPaper($paperId);

		list($schedConf, $submission, $user) = SubmissionReviewHandler::validate($reviewId);
		list($comment) = SubmissionCommentsHandler::validate($user, $commentId);

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;

		ReviewerHandler::setupTemplate(true);

		ReviewerAction::saveComment($paper, $comment, $emailComment);

		// Refresh the comment
		$paperCommentDao = &DAORegistry::getDAO('PaperCommentDAO');
		$comment = &$paperCommentDao->getPaperCommentById($commentId);
		
		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_PEER_REVIEW) {
			Request::redirect(null, null, null, 'viewPeerReviewComments', array($paperId, $comment->getAssocId()));
		}
	}
	
	/**
	 * Delete comment.
	 */
	function deleteComment($args) {
		$paperId = $args[0];
		$commentId = $args[1];
		$reviewId = Request::getUserVar('reviewId');
		
		list($schedConf, $submission, $user) = SubmissionReviewHandler::validate($reviewId);
		list($comment) = SubmissionCommentsHandler::validate($user, $commentId);

		ReviewerHandler::setupTemplate(true);

		ReviewerAction::deleteComment($commentId, $user);
		
		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_PEER_REVIEW) {
			Request::redirect(null, null, null, 'viewPeerReviewComments', array($paperId, $comment->getAssocId()));
		}
	}
	
	//
	// Validation
	//
	
	/**
	 * Validate that the user is the presenter of the comment.
	 */
	function validate($user, $commentId) {
		$isValid = true;
		
		$paperCommentDao = &DAORegistry::getDAO('PaperCommentDAO');
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
