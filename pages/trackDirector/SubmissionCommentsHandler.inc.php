<?php

/**
 * @file SubmissionCommentsHandler.inc.php
 *
 * Copyright (c) 2000-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionCommentsHandler
 * @ingroup pages_trackDirector
 *
 * @brief Handle requests for submission comments. 
 */

//$Id$

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
	 * View director decision comments.
	 */
	function viewDirectorDecisionComments($args) {
		TrackDirectorHandler::validate();
		TrackDirectorHandler::setupTemplate(true);

		$paperId = $args[0];

		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);
		TrackDirectorAction::viewDirectorDecisionComments($submission);

	}

	/**
	 * Post peer review comments.
	 */
	function postDirectorDecisionComment() {
		TrackDirectorHandler::validate();
		TrackDirectorHandler::setupTemplate(true);

		$paperId = Request::getUserVar('paperId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;

		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);
		if (TrackDirectorAction::postDirectorDecisionComment($submission, $emailComment)) {
			TrackDirectorAction::viewDirectorDecisionComments($submission);
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

		if (!$send) parent::setupTemplate(true, $paperId, 'review');
		if (TrackDirectorAction::blindCcReviewsToReviewers($submission, $send, $inhibitExistingEmail)) {
			Request::redirect(null, null, null, 'submissionReview', $paperId);
		}
	}

	/**
	 * Email a director decision comment.
	 */
	function emailDirectorDecisionComment() {
		$paperId = (int) Request::getUserVar('paperId');
		list($conference, $schedConf, $submission) = SubmissionEditHandler::validate($paperId);

		parent::setupTemplate(true);		
		if (TrackDirectorAction::emailDirectorDecisionComment($submission, Request::getUserVar('send'))) {
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

		if ($comment->getCommentType() == COMMENT_TYPE_DIRECTOR_DECISION) {
			// Cannot edit a director decision comment.
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

		if ($comment->getCommentType() == COMMENT_TYPE_DIRECTOR_DECISION) {
			// Cannot edit a director decision comment.
			Request::redirect(null, null, Request::getRequestedPage());
		}

		// Save the comment.
		TrackDirectorAction::saveComment($submission, $comment, $emailComment);

		$paperCommentDao = &DAORegistry::getDAO('PaperCommentDAO');
		$comment = &$paperCommentDao->getPaperCommentById($commentId);

		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_PEER_REVIEW) {
			Request::redirect(null, null, null, 'viewPeerReviewComments', array($paperId, $comment->getAssocId()));
		} else if ($comment->getCommentType() == COMMENT_TYPE_DIRECTOR_DECISION) {
			Request::redirect(null, null, null, 'viewDirectorDecisionComments', $paperId);
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
		} else if ($comment->getCommentType() == COMMENT_TYPE_DIRECTOR_DECISION) {
			Request::redirect(null, null, null, 'viewDirectorDecisionComments', $paperId);
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
