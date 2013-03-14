<?php

/**
 * @file SubmissionCommentsHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionCommentsHandler
 * @ingroup pages_author
 *
 * @brief Handle requests for submission comments.
 */


import('pages.author.TrackSubmissionHandler');

class SubmissionCommentsHandler extends AuthorHandler {
	/** comment associated with the request **/
	var $comment;

	/**
	 * Constructor
	 **/
	function SubmissionCommentsHandler() {
		parent::AuthorHandler();
	}

	/**
	 * View director decision comments.
	 */
	function viewDirectorDecisionComments($args, $request) {
		$this->validate($request);
		$this->setupTemplate($request, true);

		$paperId = (int) array_shift($args);

		$trackSubmissionHandler = new TrackSubmissionHandler();
		$trackSubmissionHandler->validate($request, $paperId);
		$authorSubmission =& $trackSubmissionHandler->submission;
		AuthorAction::viewDirectorDecisionComments($authorSubmission);
	}

	/**
	 * Email a director decision comment.
	 */
	function emailDirectorDecisionComment($args, $request) {
		$paperId = (int) $request->getUserVar('paperId');
		$trackSubmissionHandler = new TrackSubmissionHandler();
		$trackSubmissionHandler->validate($request, $paperId);
		$submission =& $trackSubmissionHandler->submission;

		$this->setupTemplate($request, true);
		if (AuthorAction::emailDirectorDecisionComment($submission, $request->getUserVar('send'))) {
			$request->redirect(null, null, null, 'submissionReview', array($paperId));
		}
	}

	/**
	 * Edit comment.
	 */
	function editComment($args, $request) {
		$paperId = (int) array_shift($args);
		$commentId = (int) array_shift($args);

		$this->addCheck(new HandlerValidatorSubmissionComment($this, $commentId));
		$this->validate($request);
		$comment =& $this->comment;

		$this->setupTemplate($request, true);

		$trackSubmissionHandler = new TrackSubmissionHandler();
		$trackSubmissionHandler->validate($request, $paperId);
		$authorSubmission =& $trackSubmissionHandler->submission;

		if ($comment->getCommentType() == COMMENT_TYPE_DIRECTOR_DECISION) {
			// Cannot edit a director decision comment.
			$request->redirect(null, null, $request->getRequestedPage());
		}

		AuthorAction::editComment($authorSubmission, $comment);

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

		$trackSubmissionHandler = new TrackSubmissionHandler();
		$trackSubmissionHandler->validate($request, $paperId);
		$authorSubmission =& $trackSubmissionHandler->submission;

		if ($comment->getCommentType() == COMMENT_TYPE_DIRECTOR_DECISION) {
			// Cannot edit a director decision comment.
			$request->redirect(null, null, $request->getRequestedPage());
		}

		AuthorAction::saveComment($request, $authorSubmission, $comment, $emailComment);

		$paperCommentDao = DAORegistry::getDAO('PaperCommentDAO');
		$comment =& $paperCommentDao->getPaperCommentById($commentId);

		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_DIRECTOR_DECISION) {
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

		$this->setupTemplate($request, true);

		$trackSubmissionHandler = new TrackSubmissionHandler();
		$trackSubmissionHandler->validate($request, $paperId);
		$authorSubmission =& $trackSubmissionHandler->submission;
		AuthorAction::deleteComment($commentId);

		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_DIRECTOR_DECISION) {
			$request->redirect(null, null, null, 'viewDirectorDecisionComments', $paperId);
		}
	}
}

?>
