<?php

/**
 * SubmissionCommentsHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.trackDirector
 *
 * Handle requests for submission comments. 
 *
 * $Id$
 */

import('pages.trackDirector.SubmissionEditHandler');

class SubmissionCommentsHandler extends TrackDirectorHandler {
	
	/**
	 * View peer review comments.
	 */
	function viewPeerReviewComments($args) {
		TrackDirectorHandler::validate();
		TrackDirectorHandler::setupTemplate(true);
		
		$paperId = $args[0];
		$reviewId = $args[1];
		
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);
		TrackDirectorAction::viewPeerReviewComments($submission, $reviewId);
	
	}
	
	/**
	 * Post peer review comments.
	 */
	function postPeerReviewComment() {
		TrackDirectorHandler::validate();
		TrackDirectorHandler::setupTemplate(true);
		
		$paperId = Request::getUserVar('paperId');
		$reviewId = Request::getUserVar('reviewId');
		
		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;
		
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);
		if (TrackDirectorAction::postPeerReviewComment($submission, $reviewId, $emailComment)) {
			TrackDirectorAction::viewPeerReviewComments($submission, $reviewId);
		}
	}
	
	/**
	 * View editor decision comments.
	 */
	function viewEditorDecisionComments($args) {
		TrackDirectorHandler::validate();
		TrackDirectorHandler::setupTemplate(true);
		
		$paperId = $args[0];
		
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);
		TrackDirectorAction::viewEditorDecisionComments($submission);
	
	}
	
	/**
	 * Post peer review comments.
	 */
	function postEditorDecisionComment() {
		TrackDirectorHandler::validate();
		TrackDirectorHandler::setupTemplate(true);
		
		$paperId = Request::getUserVar('paperId');
		
		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;
		
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);
		if (TrackDirectorAction::postEditorDecisionComment($submission, $emailComment)) {
			TrackDirectorAction::viewEditorDecisionComments($submission);
		}
	}
	
	/**
	 * Blind CC the reviews to reviewers.
	 */
	function blindCcReviewsToReviewers($args = array()) {
		$paperId = Request::getUserVar('paperId');
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);

		$send = Request::getUserVar('send')?true:false;
		$inhibitExistingEmail = Request::getUserVar('blindCcReviewers')?true:false;

		if (!$send) parent::setupTemplate(true, $paperId, 'editing');
		if (TrackDirectorAction::blindCcReviewsToReviewers($submission, $send, $inhibitExistingEmail)) {
			Request::redirect(null, null, null, 'submissionReview', $paperId);
		}
	}
	
	/**
	 * View layout comments.
	 */
	function viewLayoutComments($args) {
		TrackDirectorHandler::validate();
		TrackDirectorHandler::setupTemplate(true);
		
		$paperId = $args[0];
		
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);
		TrackDirectorAction::viewLayoutComments($submission);

	}
	
	/**
	 * Post layout comment.
	 */
	function postLayoutComment() {
		TrackDirectorHandler::validate();
		TrackDirectorHandler::setupTemplate(true);
		
		$paperId = Request::getUserVar('paperId');
		
		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;
		
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);
		if (TrackDirectorAction::postLayoutComment($submission, $emailComment)) {
			TrackDirectorAction::viewLayoutComments($submission);
		}	
	}
	
	/**
	 * Email an editor decision comment.
	 */
	function emailEditorDecisionComment() {
		$paperId = (int) Request::getUserVar('paperId');
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);

		parent::setupTemplate(true);		
		if (TrackDirectorAction::emailEditorDecisionComment($submission, Request::getUserVar('send'))) {
			if (Request::getUserVar('blindCcReviewers')) {
				SubmissionCommentsHandler::blindCcReviewsToReviewers();
			} else {
				Request::redirect(null, null, null, 'submissionReview', array($paperId));
			}
		}
	}
	
	/**
	 * Edit comment.
	 */
	function editComment($args) {
		TrackDirectorHandler::validate();
		TrackDirectorHandler::setupTemplate(true);
		
		$paperId = $args[0];
		$commentId = $args[1];
		
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);
		list($comment) = SubmissionCommentsHandler::validate($commentId);

		if ($comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
			// Cannot edit an editor decision comment.
			Request::redirect(null, null, Request::getRequestedPage());
		}

		TrackDirectorAction::editComment($submission, $comment);

	}
	
	/**
	 * Save comment.
	 */
	function saveComment() {
		TrackDirectorHandler::validate();
		TrackDirectorHandler::setupTemplate(true);
		
		$paperId = Request::getUserVar('paperId');
		$commentId = Request::getUserVar('commentId');
		
		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;
		
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);
		list($comment) = SubmissionCommentsHandler::validate($commentId);
		
		if ($comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
			// Cannot edit an editor decision comment.
			Request::redirect(null, null, Request::getRequestedPage());
		}

		// Save the comment.
		TrackDirectorAction::saveComment($submission, $comment, $emailComment);

		$paperCommentDao = &DAORegistry::getDAO('PaperCommentDAO');
		$comment = &$paperCommentDao->getPaperCommentById($commentId);
		
		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_PEER_REVIEW) {
			Request::redirect(null, null, null, 'viewPeerReviewComments', array($paperId, $comment->getAssocId()));
		} else if ($comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
			Request::redirect(null, null, null, 'viewEditorDecisionComments', $paperId);
		} else if ($comment->getCommentType() == COMMENT_TYPE_LAYOUT) {
			Request::redirect(null, null, null, 'viewLayoutComments', $paperId);
		}
	}
	
	/**
	 * Delete comment.
	 */
	function deleteComment($args) {
		TrackDirectorHandler::validate();
		TrackDirectorHandler::setupTemplate(true);
		
		$paperId = $args[0];
		$commentId = $args[1];
		
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);
		list($comment) = SubmissionCommentsHandler::validate($commentId);
		TrackDirectorAction::deleteComment($commentId);
		
		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_PEER_REVIEW) {
			Request::redirect(null, null, null, 'viewPeerReviewComments', array($paperId, $comment->getAssocId()));
		} else if ($comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
			Request::redirect(null, null, null, 'viewEditorDecisionComments', $paperId);
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
		
		$paperCommentDao = &DAORegistry::getDAO('PaperCommentDAO');
		$user = &Request::getUser();
		
		$comment = &$paperCommentDao->getPaperCommentById($commentId);

		if (
			$comment == null ||
			$comment->getAuthorId() != $user->getUserId()
		) {
			Request::redirect(null, null, Request::getRequestedPage());
		}

		return array($comment);
	}
}
?>
