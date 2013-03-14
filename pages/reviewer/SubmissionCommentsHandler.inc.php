<?php

/**
 * @file pages/reviewer/SubmissionCommentsHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionCommentsHandler
 * @ingroup pages_reviewer
 *
 * @brief Handle requests for submission comments.
 */

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
	function viewPeerReviewComments($args, $request) {
		$paperId = (int) array_shift($args);
		$reviewId = (int) array_shift($args);

		$submissionReviewHandler = new SubmissionReviewHandler();
		$submissionReviewHandler->validate($request, $reviewId);
		$paperDao = DAORegistry::getDAO('PaperDAO');
		$submission =& $paperDao->getPaper($paperId);

		$user =& $request->getUser();

		$this->setupTemplate($request, true);
		ReviewerAction::viewPeerReviewComments($user, $submission, $reviewId);

	}

	/**
	 * Post peer review comments.
	 */
	function postPeerReviewComment($args, $request) {
		$paperId = (int) $request->getUserVar('paperId');
		$reviewId = (int) $request->getUserVar('reviewId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		$submissionReviewHandler = new SubmissionReviewHandler();
		$submissionReviewHandler->validate($request, $reviewId);
		$paperDao = DAORegistry::getDAO('PaperDAO');
		$submission =& $paperDao->getPaper($paperId);
		$user =& $submissionReviewHandler->user;

		$this->setupTemplate($request, true);
		if (ReviewerAction::postPeerReviewComment($request, $user, $submission, $reviewId, $emailComment)) {
			ReviewerAction::viewPeerReviewComments($user, $submission, $reviewId);
		}
	}

	/**
	 * Edit comment.
	 */
	function editComment($args, $request) {
		$paperId = (int) array_shift($args);
		$commentId = (int) array_shift($args);
		$reviewId = $request->getUserVar('reviewId');

		$paperDao = DAORegistry::getDAO('PaperDAO');
		$submission = $paperDao->getPaper($paperId);

		$submissionReviewHandler = new SubmissionReviewHandler();
		$submissionReviewHandler->validate($request, $reviewId);
		$user =& $submissionReviewHandler->user;

		$this->addCheck(new HandlerValidatorSubmissionComment($this, $commentId, $user));
		$this->validate($request);
		$comment =& $this->comment;

		$this->setupTemplate($request, true);

		ReviewerAction::editComment($submission, $comment, $reviewId);
	}

	/**
	 * Save comment.
	 */
	function saveComment($args, $request) {
		$paperId = (int) $request->getUserVar('paperId');
		$commentId = (int) $request->getUserVar('commentId');
		$reviewId = (int) $request->getUserVar('reviewId');

		$paperDao = DAORegistry::getDAO('PaperDAO');
		$submission = $paperDao->getPaper($paperId);

		$submissionReviewHandler = new SubmissionReviewHandler();
		$submissionReviewHandler->validate($request, $reviewId);
		$user =& $submissionReviewHandler->user;

		$this->addCheck(new HandlerValidatorSubmissionComment($this, $commentId, $user));
		$this->validate($request);
		$comment =& $this->comment;

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		$this->setupTemplate($request, true);

		ReviewerAction::saveComment($request, $submission, $comment, $emailComment);

		// Refresh the comment
		$paperCommentDao = DAORegistry::getDAO('PaperCommentDAO');
		$comment =& $paperCommentDao->getPaperCommentById($commentId);

		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_PEER_REVIEW) {
			$request->redirect(null, null, null, 'viewPeerReviewComments', array($paperId, $comment->getAssocId()));
		}
	}

	/**
	 * Delete comment.
	 */
	function deleteComment($args, $request) {
		$paperId = (int) array_shift($args);
		$commentId = (int) array_shift($args);
		$reviewId = $request->getUserVar('reviewId');

		$this->setupTemplate($request, true);

		$submissionReviewHandler = new SubmissionReviewHandler();
		$submissionReviewHandler->validate($request, $reviewId);
		$user =& $submissionReviewHandler->user;

		$this->addCheck(new HandlerValidatorSubmissionComment($this, $commentId, $user));
		$this->validate(null, $request);
		$comment =& $this->comment;
		ReviewerAction::deleteComment($commentId, $user);

		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_PEER_REVIEW) {
			$request->redirect(null, null, null, 'viewPeerReviewComments', array($paperId, $comment->getAssocId()));
		}
	}
}

?>
