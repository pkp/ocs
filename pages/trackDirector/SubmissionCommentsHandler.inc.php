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
	function viewPeerReviewComments($args, $request) {
		$this->validate($request);
		$this->setupTemplate($request, true);

		$paperId = $args[0];
		$reviewId = $args[1];

		$submissionEditHandler = new SubmissionEditHandler();
		$submissionEditHandler->validate($request, $paperId);
		$paperDao = DAORegistry::getDAO('PaperDAO');
		$submission =& $paperDao->getPaper($paperId);

		TrackDirectorAction::viewPeerReviewComments($submission, $reviewId);
	}

	/**
	 * Post peer review comments.
	 */
	function postPeerReviewComment($args, $request) {
		$this->validate($request);
		$this->setupTemplate($request, true);

		$paperId = $request->getUserVar('paperId');
		$reviewId = $request->getUserVar('reviewId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		$submissionEditHandler = new SubmissionEditHandler();
		$submissionEditHandler->validate($request, $paperId);
		$paperDao = DAORegistry::getDAO('PaperDAO');
		$submission =& $paperDao->getPaper($paperId);

		if (TrackDirectorAction::postPeerReviewComment($request, $submission, $reviewId, $emailComment)) {
			TrackDirectorAction::viewPeerReviewComments($submission, $reviewId);
		}
	}

	/**
	 * View director decision comments.
	 */
	function viewDirectorDecisionComments($args, $request) {
		$this->validate($request);
		$this->setupTemplate($request, true);

		$paperId = (int) array_shift($args);

		$submissionEditHandler = new SubmissionEditHandler();
		$submissionEditHandler->validate($request, $paperId);
		$paperDao = DAORegistry::getDAO('PaperDAO');
		$submission =& $paperDao->getPaper($paperId);

		TrackDirectorAction::viewDirectorDecisionComments($submission);
	}

	/**
	 * Post peer review comments.
	 */
	function postDirectorDecisionComment($args, $request) {
		$paperId = (int) $request->getUserVar('paperId');

		$this->validate($request);
		$this->setupTemplate($request, true);

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		$submissionEditHandler = new SubmissionEditHandler();
		$submissionEditHandler->validate($request, $paperId);
		$paperDao = DAORegistry::getDAO('PaperDAO');
		$submission =& $paperDao->getPaper($paperId);

		if (TrackDirectorAction::postDirectorDecisionComment($request, $submission, $emailComment)) {
			TrackDirectorAction::viewDirectorDecisionComments($submission);
		}
	}

	/**
	 * Blind CC the reviews to reviewers.
	 */
	function blindCcReviewsToReviewers($args, $request) {
		$paperId = $request->getUserVar('paperId');
		$submissionEditHandler = new SubmissionEditHandler();
		$submissionEditHandler->validate($request, $paperId);
		$paperDao = DAORegistry::getDAO('PaperDAO');
		$submission =& $paperDao->getPaper($paperId);

		$send = $request->getUserVar('send')?true:false;
		$inhibitExistingEmail = $request->getUserVar('blindCcReviewers')?true:false;

		if (!$send) $this->setupTemplate($request, true, $paperId, 'review');
		if (TrackDirectorAction::blindCcReviewsToReviewers($submission, $send, $inhibitExistingEmail)) {
			$request->redirect(null, null, null, 'submissionReview', $paperId);
		}
	}

	/**
	 * Email a director decision comment.
	 */
	function emailDirectorDecisionComment($args, $request) {
		$paperId = (int) $request->getUserVar('paperId');
		$submissionEditHandler = new SubmissionEditHandler();
		$submissionEditHandler->validate($request, $paperId);
		$trackDirectorSubmissionDao = DAORegistry::getDAO('TrackDirectorSubmissionDAO');
		$submission =& $trackDirectorSubmissionDao->getTrackDirectorSubmission($paperId);

		$this->setupTemplate($request, true);
		if (TrackDirectorAction::emailDirectorDecisionComment($submission, $request->getUserVar('send'))) {
			if ($request->getUserVar('blindCcReviewers')) {
				$this->blindCcReviewsToReviewers(array(), $request);
			} else {
				$request->redirect(null, null, null, 'submissionReview', array($paperId));
			}
		}
	}

	/**
	 * Edit comment.
	 */
	function editComment($args, $request) {
		$paperId = (int) array_shift($args);
		$commentId = (int) array_shift($args);

		$submissionEditHandler = new SubmissionEditHandler();
		$submissionEditHandler->validate($request, $paperId);
		$paperDao = DAORegistry::getDAO('PaperDAO');
		$submission =& $paperDao->getPaper($paperId);

		$this->addCheck(new HandlerValidatorSubmissionComment($this, $commentId));
		$this->validate($request);
		$comment =& $this->comment;

		$this->setupTemplate($request, true);

		if ($comment->getCommentType() == COMMENT_TYPE_DIRECTOR_DECISION) {
			// Cannot edit a director decision comment.
			$request->redirect(null, null, $request->getRequestedPage());
		}

		TrackDirectorAction::editComment($submission, $comment);
	}

	/**
	 * Save comment.
	 */
	function saveComment($args, $request) {
		$paperId = (int) $request->getUserVar('paperId');
		$commentId = (int) $request->getUserVar('commentId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = $request->getUserVar('saveAndEmail') != null ? true : false;

		$this->addCheck(new HandlerValidatorSubmissionComment($this, $commentId));
		$this->validate($request);
		$comment =& $this->comment;

		$this->setupTemplate($request, true);

		$submissionEditHandler = new SubmissionEditHandler();
		$submissionEditHandler->validate($request, $paperId);
		$paperDao = DAORegistry::getDAO('PaperDAO');
		$submission =& $paperDao->getPaper($paperId);

		if ($comment->getCommentType() == COMMENT_TYPE_DIRECTOR_DECISION) {
			// Cannot edit a director decision comment.
			$request->redirect(null, null, $request->getRequestedPage());
		}

		// Save the comment.
		TrackDirectorAction::saveComment($request, $submission, $comment, $emailComment);

		// refresh the comment
		$paperCommentDao = DAORegistry::getDAO('PaperCommentDAO');
		$comment =& $paperCommentDao->getPaperCommentById($commentId);

		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_PEER_REVIEW) {
			$request->redirect(null, null, null, 'viewPeerReviewComments', array($paperId, $comment->getAssocId()));
		} else if ($comment->getCommentType() == COMMENT_TYPE_DIRECTOR_DECISION) {
			$request->redirect(null, null, null, 'viewDirectorDecisionComments', $paperId);
		}
	}

	/**
	 * Delete comment.
	 */
	function deleteComment($args, $request) {
		$paperId = (int) array_shift($args);
		$commentId = (int) array_shift($args);

		$this->addCheck(new HandlerValidatorSubmissionComment($this, $commentId));
		$this->validate($request);
		$comment =& $this->comment;

		$submissionEditHandler = new SubmissionEditHandler();
		$submissionEditHandler->validate($request, $paperId);
		$paperDao = DAORegistry::getDAO('PaperDAO');
		$submission =& $paperDao->getPaper($paperId);

		TrackDirectorAction::deleteComment($commentId);

		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_PEER_REVIEW) {
			$request->redirect(null, null, null, 'viewPeerReviewComments', array($paperId, $comment->getAssocId()));
		} else if ($comment->getCommentType() == COMMENT_TYPE_DIRECTOR_DECISION) {
			$request->redirect(null, null, null, 'viewDirectorDecisionComments', $paperId);
		}
	}
}

?>
