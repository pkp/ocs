<?php

/**
 * @file SubmissionCommentsHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
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
	/** comment associated with the request **/
	var $comment;
	
	/**
	 * Constructor
	 **/
	function SubmissionCommentsHandler() {
		parent::TrackDirectorHandler();
	}

	/**
	 * View peer review comments.
	 */
	function viewPeerReviewComments($args) {
		parent::validate();
		$this->setupTemplate(true);

		$paperId = $args[0];
		$reviewId = $args[1];

		$submissionEditHandler = new SubmissionEditHandler();
		$submissionEditHandler->validate($paperId);
		$paperDao =& DAORegistry::getDAO('PaperDAO');
		$submission =& $paperDao->getPaper($paperId);
		
		TrackDirectorAction::viewPeerReviewComments($submission, $reviewId);
	}

	/**
	 * Post peer review comments.
	 */
	function postPeerReviewComment() {
		parent::validate();
		$this->setupTemplate(true);

		$paperId = Request::getUserVar('paperId');
		$reviewId = Request::getUserVar('reviewId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;

		$submissionEditHandler = new SubmissionEditHandler();
		$submissionEditHandler->validate($paperId);
		$paperDao =& DAORegistry::getDAO('PaperDAO');
		$submission =& $paperDao->getPaper($paperId);

		if (TrackDirectorAction::postPeerReviewComment($submission, $reviewId, $emailComment)) {
			TrackDirectorAction::viewPeerReviewComments($submission, $reviewId);
		}
	}

	/**
	 * View director decision comments.
	 */
	function viewDirectorDecisionComments($args) {
		parent::validate();
		$this->setupTemplate(true);

		$paperId = $args[0];

		$submissionEditHandler = new SubmissionEditHandler();
		$submissionEditHandler->validate($paperId);
		$paperDao =& DAORegistry::getDAO('PaperDAO');
		$submission =& $paperDao->getPaper($paperId);
		
		TrackDirectorAction::viewDirectorDecisionComments($submission);
	}

	/**
	 * Post peer review comments.
	 */
	function postDirectorDecisionComment() {
		parent::validate();
		$this->setupTemplate(true);

		$paperId = Request::getUserVar('paperId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;

		$submissionEditHandler = new SubmissionEditHandler();
		$submissionEditHandler->validate($paperId);
		$paperDao =& DAORegistry::getDAO('PaperDAO');
		$submission =& $paperDao->getPaper($paperId);
		
		if (TrackDirectorAction::postDirectorDecisionComment($submission, $emailComment)) {
			TrackDirectorAction::viewDirectorDecisionComments($submission);
		}
	}

	/**
	 * Blind CC the reviews to reviewers.
	 */
	function blindCcReviewsToReviewers($args = array()) {
		$paperId = Request::getUserVar('paperId');
		$submissionEditHandler = new SubmissionEditHandler();
		$submissionEditHandler->validate($paperId);
		$paperDao =& DAORegistry::getDAO('PaperDAO');
		$submission =& $paperDao->getPaper($paperId);

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
		$submissionEditHandler = new SubmissionEditHandler();
		$submissionEditHandler->validate($paperId);
		$trackDirectorSubmissionDao =& DAORegistry::getDAO('TrackDirectorSubmissionDAO');
		$submission =& $trackDirectorSubmissionDao->getTrackDirectorSubmission($paperId);

		parent::setupTemplate(true);		
		if (TrackDirectorAction::emailDirectorDecisionComment($submission, Request::getUserVar('send'))) {
			if (Request::getUserVar('blindCcReviewers')) {
				$this->blindCcReviewsToReviewers();
			} else {
				Request::redirect(null, null, null, 'submissionReview', array($paperId));
			}
		}
	}

	/**
	 * Edit comment.
	 */
	function editComment($args) {
		$paperId = $args[0];
		$commentId = $args[1];

		$submissionEditHandler = new SubmissionEditHandler();
		$submissionEditHandler->validate($paperId);
		$paperDao =& DAORegistry::getDAO('PaperDAO');
		$submission =& $paperDao->getPaper($paperId);

		$this->addCheck(new HandlerValidatorSubmissionComment($this, $commentId));
		$this->validate();
		$comment =& $this->comment;
		
		$this->setupTemplate(true);

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
		$paperId = Request::getUserVar('paperId');
		$commentId = Request::getUserVar('commentId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;

		$this->addCheck(new HandlerValidatorSubmissionComment($this, $commentId));
		$this->validate();
		$comment =& $this->comment;

		$this->setupTemplate(true);
		
		$submissionEditHandler = new SubmissionEditHandler();
		$submissionEditHandler->validate($paperId);
		$paperDao =& DAORegistry::getDAO('PaperDAO');
		$submission =& $paperDao->getPaper($paperId);
		
		if ($comment->getCommentType() == COMMENT_TYPE_DIRECTOR_DECISION) {
			// Cannot edit a director decision comment.
			Request::redirect(null, null, Request::getRequestedPage());
		}

		// Save the comment.
		TrackDirectorAction::saveComment($submission, $comment, $emailComment);

		// refresh the comment
		$paperCommentDao =& DAORegistry::getDAO('PaperCommentDAO');
		$comment =& $paperCommentDao->getPaperCommentById($commentId);

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
		$paperId = $args[0];
		$commentId = $args[1];
		
		$this->addCheck(new HandlerValidatorSubmissionComment($this, $commentId));
		$this->validate();
		$comment =& $this->comment;

		$submissionEditHandler = new SubmissionEditHandler();
		$submissionEditHandler->validate($paperId);
		$paperDao =& DAORegistry::getDAO('PaperDAO');
		$submission =& $paperDao->getPaper($paperId);
		
		TrackDirectorAction::deleteComment($commentId);

		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_PEER_REVIEW) {
			Request::redirect(null, null, null, 'viewPeerReviewComments', array($paperId, $comment->getAssocId()));
		} else if ($comment->getCommentType() == COMMENT_TYPE_DIRECTOR_DECISION) {
			Request::redirect(null, null, null, 'viewDirectorDecisionComments', $paperId);
		}

	}
}
?>
