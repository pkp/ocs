<?php

/**
 * CommentForm.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission.form
 *
 * Comment form.
 *
 * $Id$
 */
 
import("submission.form.comment.CommentForm");

class PeerReviewCommentForm extends CommentForm {

	/** @var int the ID of the review assignment */
	var $reviewId;
	
	/** @var array the IDs of the inserted comments */
	var $insertedComments;
	
	/**
	 * Constructor.
	 * @param $paper object
	 */
	function PeerReviewCommentForm($paper, $reviewId, $roleId) {
		parent::CommentForm($paper, COMMENT_TYPE_PEER_REVIEW, $roleId, $reviewId);
		$this->reviewId = $reviewId;
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($this->reviewId);
		$reviewLetters = &$reviewAssignmentDao->getReviewIndexesForStage($this->paper->getPaperId(), $this->paper->getCurrentStage());

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('commentType', 'peerReview');
		$templateMgr->assign('pageTitle', 'submission.comments.review');
		$templateMgr->assign('commentAction', 'postPeerReviewComment');
		$templateMgr->assign('commentTitle', strip_tags($this->paper->getPaperTitle()));
		$templateMgr->assign('isLocked', isset($reviewAssignment) && $reviewAssignment->getDateCompleted() != null);
		$templateMgr->assign('canEmail', false); // Previously, directors could always email.
		$templateMgr->assign('showReviewLetters', $this->roleId == ROLE_ID_DIRECTOR ? true : false);
		$templateMgr->assign('reviewLetters', $reviewLetters);
		$templateMgr->assign('reviewer', ROLE_ID_REVIEWER);
		$templateMgr->assign('hiddenFormParams', 
			array(
				'paperId' => $this->paper->getPaperId(),
				'reviewId' => $this->reviewId
			)
		);
		
		parent::display();
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(
			array(
				'commentTitle',
				'presenterComments',
				'comments'
			)
		);
	}
	
	/**
	 * Add the comment.
	 */
	function execute() {
		// Personalized execute() method since now there are possibly two comments contained within each form submission.
	
		$commentDao = &DAORegistry::getDAO('PaperCommentDAO');
		$this->insertedComments = array();
	
		// Assign all common information	
		$comment = &new PaperComment();
		$comment->setCommentType($this->commentType);
		$comment->setRoleId($this->roleId);
		$comment->setPaperId($this->paper->getPaperId());
		$comment->setAssocId($this->assocId);
		$comment->setAuthorId($this->user->getUserId());
		$comment->setCommentTitle($this->getData('commentTitle'));
		$comment->setDatePosted(Core::getCurrentDate());
		
		// If comments "For presenters and director" submitted
		if ($this->getData('presenterComments') != null) {
			$comment->setComments($this->getData('presenterComments'));
			$comment->setViewable(1);
			array_push($this->insertedComments, $commentDao->insertPaperComment($comment));
		}		
		
		// If comments "For director" submitted
		if ($this->getData('comments') != null) {
			$comment->setComments($this->getData('comments'));
			$comment->setViewable(null);
			array_push($this->insertedComments, $commentDao->insertPaperComment($comment));
		}
	}
	
	/**
	 * Email the comment.
	 */
	function email() {
		// Create list of recipients:
		
		// Peer Review comments are to be sent to the director or reviewer;
		// the opposite of whomever posted the comment.
		$recipients = array();
		
		if ($this->roleId == ROLE_ID_DIRECTOR) {
			// Then add reviewer
			$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
			$userDao = &DAORegistry::getDAO('UserDAO');
			
			$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($this->reviewId);
			$user = &$userDao->getUser($reviewAssignment->getReviewerId());
			
			if ($user) $recipients = array_merge($recipients, array($user->getEmail() => $user->getFullName()));
		} else {
			/* COMMENTED OUT SINCE THE REVIEWER CAN NO LONGER 'SAVE AND EMAIL' COMMENTS */
		}
		
		parent::email($recipients, $this->insertedComments);
	}
}

?>
