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

//$Id$

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
	function viewDirectorDecisionComments($args) {
		$this->validate();
		$this->setupTemplate(true);

		$paperId = $args[0];

		$trackSubmissionHandler = new TrackSubmissionHandler();
		$trackSubmissionHandler->validate($paperId);
		$authorSubmission =& $trackSubmissionHandler->submission;
		AuthorAction::viewDirectorDecisionComments($authorSubmission);
	}

	/**
	 * Email a director decision comment.
	 */
	function emailDirectorDecisionComment() {
		$paperId = (int) Request::getUserVar('paperId');
		$trackSubmissionHandler = new TrackSubmissionHandler();
		$trackSubmissionHandler->validate($paperId);
		$submission =& $trackSubmissionHandler->submission;

		$this->setupTemplate(true);		
		if (AuthorAction::emailDirectorDecisionComment($submission, Request::getUserVar('send'))) {
			Request::redirect(null, null, null, 'submissionReview', array($paperId));
		}
	}

	/**
	 * Edit comment.
	 */
	function editComment($args) {
		$paperId = $args[0];
		$commentId = $args[1];

		$this->addCheck(new HandlerValidatorSubmissionComment($this, $commentId));
		$this->validate();
		$comment =& $this->comment;
		
		$this->setupTemplate(true);
		
		$trackSubmissionHandler = new TrackSubmissionHandler();
		$trackSubmissionHandler->validate($paperId);
		$authorSubmission =& $trackSubmissionHandler->submission;

		if ($comment->getCommentType() == COMMENT_TYPE_DIRECTOR_DECISION) {
			// Cannot edit a director decision comment.
			Request::redirect(null, null, Request::getRequestedPage());
		}

		AuthorAction::editComment($authorSubmission, $comment);

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
		
		$trackSubmissionHandler = new TrackSubmissionHandler();
		$trackSubmissionHandler->validate($paperId);
		$authorSubmission =& $trackSubmissionHandler->submission;

		if ($comment->getCommentType() == COMMENT_TYPE_DIRECTOR_DECISION) {
			// Cannot edit a director decision comment.
			Request::redirect(null, null, Request::getRequestedPage());
		}

		AuthorAction::saveComment($authorSubmission, $comment, $emailComment);

		$paperCommentDao =& DAORegistry::getDAO('PaperCommentDAO');
		$comment =& $paperCommentDao->getPaperCommentById($commentId);

		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_DIRECTOR_DECISION) {
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
		
		$this->setupTemplate(true);
		
		$trackSubmissionHandler = new TrackSubmissionHandler();
		$trackSubmissionHandler->validate($paperId);
		$authorSubmission =& $trackSubmissionHandler->submission;
		AuthorAction::deleteComment($commentId);

		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_DIRECTOR_DECISION) {
			Request::redirect(null, null, null, 'viewDirectorDecisionComments', $paperId);
		}
	}
}
?>
