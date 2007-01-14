<?php

/**
 * SubmissionCommentsHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.trackEditor
 *
 * Handle requests for submission comments. 
 *
 * $Id$
 */

import('pages.trackEditor.SubmissionEditHandler');

class SubmissionCommentsHandler extends TrackEditorHandler {
	
	/**
	 * View peer review comments.
	 */
	function viewPeerReviewComments($args) {
		TrackEditorHandler::validate();
		TrackEditorHandler::setupTemplate(true);
		
		$paperId = $args[0];
		$reviewId = $args[1];
		
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId);
		TrackEditorAction::viewPeerReviewComments($submission, $reviewId);
	
	}
	
	/**
	 * Post peer review comments.
	 */
	function postPeerReviewComment() {
		TrackEditorHandler::validate();
		TrackEditorHandler::setupTemplate(true);
		
		$paperId = Request::getUserVar('paperId');
		$reviewId = Request::getUserVar('reviewId');
		
		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;
		
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId);
		if (SectionEditorAction::postPeerReviewComment($submission, $reviewId, $emailComment)) {
			SectionEditorAction::viewPeerReviewComments($submission, $reviewId);
		}
	}
	
	/**
	 * View editor decision comments.
	 */
	function viewEditorDecisionComments($args) {
		TrackEditorHandler::validate();
		TrackEditorHandler::setupTemplate(true);
		
		$paperId = $args[0];
		
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId);
		TrackEditorAction::viewEditorDecisionComments($submission);
	
	}
	
	/**
	 * Post peer review comments.
	 */
	function postEditorDecisionComment() {
		TrackEditorHandler::validate();
		TrackEditorHandler::setupTemplate(true);
		
		$paperId = Request::getUserVar('paperId');
		
		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;
		
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId);
		if (SectionEditorAction::postEditorDecisionComment($submission, $emailComment)) {
			SectionEditorAction::viewEditorDecisionComments($submission);
		}
	}
	
	/**
	 * Blind CC the reviews to reviewers.
	 */
	function blindCcReviewsToReviewers($args = array()) {
		$paperId = Request::getUserVar('paperId');
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId);

		$send = Request::getUserVar('send')?true:false;
		$inhibitExistingEmail = Request::getUserVar('blindCcReviewers')?true:false;

		if (!$send) parent::setupTemplate(true, $paperId, 'editing');
		if (TrackEditorAction::blindCcReviewsToReviewers($submission, $send, $inhibitExistingEmail)) {
			Request::redirect(null, null, null, 'submissionReview', $paperId);
		}
	}
	
	/**
	 * View layout comments.
	 */
	function viewLayoutComments($args) {
		TrackEditorHandler::validate();
		TrackEditorHandler::setupTemplate(true);
		
		$paperId = $args[0];
		
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId);
		TrackEditorAction::viewLayoutComments($submission);

	}
	
	/**
	 * Post layout comment.
	 */
	function postLayoutComment() {
		TrackEditorHandler::validate();
		TrackEditorHandler::setupTemplate(true);
		
		$paperId = Request::getUserVar('paperId');
		
		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;
		
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId);
		if (TrackEditorAction::postLayoutComment($submission, $emailComment)) {
			TrackEditorAction::viewLayoutComments($submission);
		}	
	}
	
	/**
	 * Email an editor decision comment.
	 */
	function emailEditorDecisionComment() {
		$paperId = (int) Request::getUserVar('paperId');
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId);

		parent::setupTemplate(true);		
		if (TrackEditorAction::emailEditorDecisionComment($submission, Request::getUserVar('send'))) {
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
		TrackEditorHandler::validate();
		TrackEditorHandler::setupTemplate(true);
		
		$paperId = $args[0];
		$commentId = $args[1];
		
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId);
		list($comment) = SubmissionCommentsHandler::validate($commentId);

		if ($comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
			// Cannot edit an editor decision comment.
			Request::redirect(null, null, Request::getRequestedPage());
		}

		TrackEditorAction::editComment($submission, $comment);

	}
	
	/**
	 * Save comment.
	 */
	function saveComment() {
		TrackEditorHandler::validate();
		TrackEditorHandler::setupTemplate(true);
		
		$paperId = Request::getUserVar('paperId');
		$commentId = Request::getUserVar('commentId');
		
		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;
		
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId);
		list($comment) = SubmissionCommentsHandler::validate($commentId);
		
		if ($comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
			// Cannot edit an editor decision comment.
			Request::redirect(null, null, Request::getRequestedPage());
		}

		// Save the comment.
		TrackEditorAction::saveComment($submission, $comment, $emailComment);

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
		TrackEditorHandler::validate();
		TrackEditorHandler::setupTemplate(true);
		
		$paperId = $args[0];
		$commentId = $args[1];
		
		list($conference, $event, $submission) = SubmissionEditHandler::validate($paperId);
		list($comment) = SubmissionCommentsHandler::validate($commentId);
		TrackEditorAction::deleteComment($commentId);
		
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
	 * Validate that the user is the author of the comment.
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
