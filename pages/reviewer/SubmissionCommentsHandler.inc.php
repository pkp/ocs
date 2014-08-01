<?php

/**
 * @file SubmissionCommentsHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionCommentsHandler
 * @ingroup pages_reviewer
 *
 * @brief Handle requests for submission comments. 
 */

//$Id$

import('pages.reviewer.SubmissionReviewHandler');

class SubmissionCommentsHandler extends ReviewerHandler {
	/** comment associated with the request **/
	var $comment;
	
	/**
	 * Constructor
	 **/
	function SubmissionCommentsHandler() {
		parent::ReviewerHandler();
	}

	/**
	 * View peer review comments.
	 */
	function viewPeerReviewComments($args) {
		$paperId = $args[0];
		$reviewId = $args[1];

		$this->validate($reviewId);
		$paperDao =& DAORegistry::getDAO('PaperDAO');
		
		$this->setupTemplate(true);
		ReviewerAction::viewPeerReviewComments($this->user, $this->submission, $reviewId);

	}

	/**
	 * Post peer review comments.
	 */
	function postPeerReviewComment() {
		$paperId = Request::getUserVar('paperId');
		$reviewId = Request::getUserVar('reviewId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;

		$this->validate($reviewId);
		$paperDao =& DAORegistry::getDAO('PaperDAO');

		$this->setupTemplate(true);
		if (ReviewerAction::postPeerReviewComment($this->user, $this->submission, $reviewId, $emailComment)) {
			ReviewerAction::viewPeerReviewComments($this->user, $this->submission, $reviewId);
		}
	}

	/**
	 * Edit comment.
	 */
	function editComment($args) {
		$paperId = $args[0];
		$commentId = $args[1];
		$reviewId = Request::getUserVar('reviewId');

		$paperDao =& DAORegistry::getDAO('PaperDAO');
		$submission = $paperDao->getPaper($paperId);

		$this->validate($reviewId);
		$this->addCheck(new HandlerValidatorSubmissionComment($this, $commentId, $this->user));
		$this->setupTemplate(true);

		ReviewerAction::editComment($this->submission, $this->comment, $reviewId);
	}

	/**
	 * Save comment.
	 */
	function saveComment() {
		$paperId = Request::getUserVar('paperId');
		$commentId = Request::getUserVar('commentId');
		$reviewId = Request::getUserVar('reviewId');

		$paperDao =& DAORegistry::getDAO('PaperDAO');
		$submission = $paperDao->getPaper($paperId);

		$this->validate($reviewId);

		$this->addCheck(new HandlerValidatorSubmissionComment($this, $commentId, $this->user));
		
		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;

		$this->setupTemplate(true);

		ReviewerAction::saveComment($this->submission, $this->comment, $emailComment);

		// Refresh the comment
		$paperCommentDao =& DAORegistry::getDAO('PaperCommentDAO');
		$comment =& $paperCommentDao->getPaperCommentById($commentId);

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
		
		$this->setupTemplate(true);
		
		$this->validate($reviewId);
		$this->addCheck(new HandlerValidatorSubmissionComment($this, $commentId, $this->user));
		ReviewerAction::deleteComment($commentId, $this->user);

		// Redirect back to initial comments page
		if ($this->comment->getCommentType() == COMMENT_TYPE_PEER_REVIEW) {
			Request::redirect(null, null, null, 'viewPeerReviewComments', array($paperId, $this->comment->getAssocId()));
		}
	}
}
?>
