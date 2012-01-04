<?php

/**
 * @file PeerReviewCommentForm.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PeerReviewCommentForm
 * @ingroup submission_form
 *
 * @brief Comment form.
 */

//$Id$

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
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignment =& $reviewAssignmentDao->getReviewAssignmentById($this->reviewId);
		$reviewLetters =& $reviewAssignmentDao->getReviewIndexesForStage($this->paper->getPaperId(), $this->paper->getCurrentStage());

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('commentType', 'peerReview');
		$templateMgr->assign('pageTitle', 'submission.comments.review');
		$templateMgr->assign('commentAction', 'postPeerReviewComment');
		$templateMgr->assign('commentTitle', strip_tags($this->paper->getLocalizedTitle()));
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
				'authorComments',
				'comments'
			)
		);
	}

	/**
	 * Add the comment.
	 */
	function execute() {
		// Personalized execute() method since now there are possibly two comments contained within each form submission.

		$commentDao =& DAORegistry::getDAO('PaperCommentDAO');
		$this->insertedComments = array();

		// Assign all common information	
		$comment = new PaperComment();
		$comment->setCommentType($this->commentType);
		$comment->setRoleId($this->roleId);
		$comment->setPaperId($this->paper->getPaperId());
		$comment->setAssocId($this->assocId);
		$comment->setAuthorId($this->user->getId());
		$comment->setCommentTitle($this->getData('commentTitle'));
		$comment->setDatePosted(Core::getCurrentDate());

		// If comments "For authors and director" submitted
		if ($this->getData('authorComments') != null) {
			$comment->setComments($this->getData('authorComments'));
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
}

?>
