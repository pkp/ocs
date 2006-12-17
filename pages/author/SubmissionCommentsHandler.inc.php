<?php

/**
 * SubmissionCommentsHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.author
 *
 * Handle requests for submission comments. 
 *
 * $Id$
 */

import('pages.author.TrackSubmissionHandler');

class SubmissionCommentsHandler extends AuthorHandler {
	
	/**
	 * View editor decision comments.
	 */
	function viewEditorDecisionComments($args) {
		AuthorHandler::validate();
		AuthorHandler::setupTemplate(true);
		
		$paperId = $args[0];
		
		list($conference, $event, $authorSubmission) = TrackSubmissionHandler::validate($paperId);
		AuthorAction::viewEditorDecisionComments($authorSubmission);
	}
	
	/**
	 * View copyedit comments.
	 */
	function viewCopyeditComments($args) {
		AuthorHandler::validate();
		AuthorHandler::setupTemplate(true);
		
		$paperId = $args[0];
		
		list($conference, $event, $authorSubmission) = TrackSubmissionHandler::validate($paperId);
		AuthorAction::viewCopyeditComments($authorSubmission);
	
	}
	
	/**
	 * Post copyedit comment.
	 */
	function postCopyeditComment() {
		AuthorHandler::validate();
		AuthorHandler::setupTemplate(true);
		
		$paperId = Request::getUserVar('paperId');

		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;
		
		list($conference, $event, $authorSubmission) = TrackSubmissionHandler::validate($paperId);
		AuthorAction::postCopyeditComment($authorSubmission, $emailComment);
		
		AuthorAction::viewCopyeditComments($authorSubmission);
	
	}
	
	/**
	 * View proofread comments.
	 */
	function viewProofreadComments($args) {
		AuthorHandler::validate();
		AuthorHandler::setupTemplate(true);
		
		$paperId = $args[0];
		
		list($conference, $event, $authorSubmission) = TrackSubmissionHandler::validate($paperId);
		AuthorAction::viewProofreadComments($authorSubmission);
	
	}
	
	/**
	 * Post proofread comment.
	 */
	function postProofreadComment() {
		AuthorHandler::validate();
		AuthorHandler::setupTemplate(true);
		
		$paperId = Request::getUserVar('paperId');
		
		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;
		
		list($conference, $event, $authorSubmission) = TrackSubmissionHandler::validate($paperId);
		AuthorAction::postProofreadComment($authorSubmission, $emailComment);
		
		AuthorAction::viewProofreadComments($authorSubmission);
	
	}

	/**
	 * View layout comments.
	 */
	function viewLayoutComments($args) {
		AuthorHandler::validate();
		AuthorHandler::setupTemplate(true);

		$paperId = $args[0];

		list($conference, $event, $authorSubmission) = TrackSubmissionHandler::validate($paperId);
		AuthorAction::viewLayoutComments($authorSubmission);

	}

	/**
	 * Post layout comment.
	 */
	function postLayoutComment() {
		AuthorHandler::validate();
		AuthorHandler::setupTemplate(true);
		
		$paperId = Request::getUserVar('paperId');
		
		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;
		
		list($conference, $event, $authorSubmission) = TrackSubmissionHandler::validate($paperId);
		AuthorAction::postLayoutComment($authorSubmission, $emailComment);
		
		AuthorAction::viewLayoutComments($authorSubmission);
	
	}

	/**
	 * Email an editor decision comment.
	 */
	function emailEditorDecisionComment() {
		$paperId = (int) Request::getUserVar('paperId');
		list($conference, $event, $submission) = TrackSubmissionHandler::validate($paperId);

		parent::setupTemplate(true);		
		if (AuthorAction::emailEditorDecisionComment($submission, Request::getUserVar('send'))) {
			Request::redirect(null, null, null, 'submissionReview', array($paperId));
		}
	}
	
	/**
	 * Edit comment.
	 */
	function editComment($args) {
		AuthorHandler::validate();
		AuthorHandler::setupTemplate(true);
		
		$paperId = $args[0];
		$commentId = $args[1];
		
		list($conference, $event, $authorSubmission) = TrackSubmissionHandler::validate($paperId);
		list($comment) = SubmissionCommentsHandler::validate($commentId);

		if ($comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
			// Cannot edit an editor decision comment.
			Request::redirect(null, null, Request::getRequestedPage());
		}

		AuthorAction::editComment($authorSubmission, $comment);

	}
	
	/**
	 * Save comment.
	 */
	function saveComment() {
		AuthorHandler::validate();
		AuthorHandler::setupTemplate(true);
		
		$paperId = Request::getUserVar('paperId');
		$commentId = Request::getUserVar('commentId');
		
		// If the user pressed the "Save and email" button, then email the comment.
		$emailComment = Request::getUserVar('saveAndEmail') != null ? true : false;
		
		list($conference, $event, $authorSubmission) = TrackSubmissionHandler::validate($paperId);
		list($comment) = SubmissionCommentsHandler::validate($commentId);

		if ($comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
			// Cannot edit an editor decision comment.
			Request::redirect(null, null, Request::getRequestedPage());
		}

		AuthorAction::saveComment($authorSubmission, $comment, $emailComment);

		$paperCommentDao = &DAORegistry::getDAO('PaperCommentDAO');
		$comment = &$paperCommentDao->getPaperCommentById($commentId);
		
		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
			Request::redirect(null, null, null, 'viewEditorDecisionComments', $paperId);
		} else if ($comment->getCommentType() == COMMENT_TYPE_COPYEDIT) {
			Request::redirect(null, null, null, 'viewCopyeditComments', $paperId);
		} else if ($comment->getCommentType() == COMMENT_TYPE_LAYOUT) {
			Request::redirect(null, null, null, 'viewLayoutComments', $paperId);
		} else if ($comment->getCommentType() == COMMENT_TYPE_PROOFREAD) {
			Request::redirect(null, null, null, 'viewProofreadComments', $paperId);
		}
	}
	
	/**
	 * Delete comment.
	 */
	function deleteComment($args) {
		AuthorHandler::validate();
		AuthorHandler::setupTemplate(true);
		
		$paperId = $args[0];
		$commentId = $args[1];
		
		$paperCommentDao = &DAORegistry::getDAO('PaperCommentDAO');
		$comment = &$paperCommentDao->getPaperCommentById($commentId);
		
		list($conference, $event, $authorSubmission) = TrackSubmissionHandler::validate($paperId);
		list($comment) = SubmissionCommentsHandler::validate($commentId);
		AuthorAction::deleteComment($commentId);
		
		// Redirect back to initial comments page
		if ($comment->getCommentType() == COMMENT_TYPE_EDITOR_DECISION) {
			Request::redirect(null, null, null, 'viewEditorDecisionComments', $paperId);
		} else if ($comment->getCommentType() == COMMENT_TYPE_COPYEDIT) {
			Request::redirect(null, null, null, 'viewCopyeditComments', $paperId);
		} else if ($comment->getCommentType() == COMMENT_TYPE_LAYOUT) {
			Request::redirect(null, null, null, 'viewLayoutComments', $paperId);
		} else if ($comment->getCommentType() == COMMENT_TYPE_PROOFREAD) {
			Request::redirect(null, null, null, 'viewProofreadComments', $paperId);
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
		
		$isValid = true;
		
		$paperCommentDao = &DAORegistry::getDAO('PaperCommentDAO');
		$user = &Request::getUser();
		
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
