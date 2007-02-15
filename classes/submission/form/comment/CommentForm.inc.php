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

import('form.Form');

class CommentForm extends Form {

	/** @var int the comment type */
	var $commentType;
	
	/** @var int the role id of the comment poster */
	var $roleId;

	/** @var Paper current paper */
	var $paper;

	/** @var User comment presenter */
	var $user;
	
	/** @var int the ID of the comment after insertion */
	var $commentId;
	
	/**
	 * Constructor.
	 * @param $paper object
	 */
	function CommentForm($paper, $commentType, $roleId, $assocId = null) {
		if ($commentType == COMMENT_TYPE_PEER_REVIEW) {
			parent::Form('submission/comment/peerReviewComment.tpl');
		} else if ($commentType == COMMENT_TYPE_DIRECTOR_DECISION) {
			parent::Form('submission/comment/directorDecisionComment.tpl');
		} else {
			parent::Form('submission/comment/comment.tpl');
		}
		
		$this->paper = $paper;
		$this->commentType = $commentType;
		$this->roleId = $roleId;
		$this->assocId = $assocId == null ? $paper->getPaperId() : $assocId;
		
		$this->user = &Request::getUser();

		if ($commentType != COMMENT_TYPE_PEER_REVIEW) $this->addCheck(new FormValidator($this, 'comments', 'required', 'director.paper.commentsRequired'));
	}

	/**
	 * Set the user this comment form is associated with.
	 * @param $user object
	 */
	function setUser(&$user) {
		$this->user =& $user;
	}

	/**
	 * Display the form.
	 */
	function display() {
		$paper = $this->paper;

		$paperCommentDao = &DAORegistry::getDAO('PaperCommentDAO');
		$paperComments = &$paperCommentDao->getPaperComments($paper->getPaperId(), $this->commentType, $this->assocId);
	
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('paperId', $paper->getPaperId());
		$templateMgr->assign('commentTitle', strip_tags($paper->getPaperTitle()));
		$templateMgr->assign('userId', $this->user->getUserId());
		$templateMgr->assign('paperComments', $paperComments);
		
		parent::display();
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(
			array(
				'commentTitle',
				'comments',
				'viewable'
			)
		);
	}
	
	/**
	 * Add the comment.
	 */
	function execute() {
		$commentDao = &DAORegistry::getDAO('PaperCommentDAO');
		$paper = $this->paper;
	
		// Insert new comment		
		$comment = &new PaperComment();
		$comment->setCommentType($this->commentType);
		$comment->setRoleId($this->roleId);
		$comment->setPaperId($paper->getPaperId());
		$comment->setAssocId($this->assocId);
		$comment->setAuthorId($this->user->getUserId());
		$comment->setCommentTitle($this->getData('commentTitle'));
		$comment->setComments($this->getData('comments'));
		$comment->setDatePosted(Core::getCurrentDate());
		$comment->setViewable($this->getData('viewable'));
		
		$this->commentId = $commentDao->insertPaperComment($comment);
	}
	
	/**
	 * Email the comment.
	 * @param $recipients array of recipients (email address => name)
	 * @param $insertedComments array of comment IDs (currently only used for review-type emails)
	 */
	function email($recipients, $insertedComments = null) {
		$paper = $this->paper;
		$paperCommentDao = &DAORegistry::getDAO('PaperCommentDAO');
		$schedConf = &Request::getSchedConf();
		
		$user = &Request::getUser();
		import('mail.PaperMailTemplate');
		$email = &new PaperMailTemplate($paper, 'SUBMISSION_COMMENT');
		$email->setFrom($schedConf->getSetting('contactEmail', true), $schedConf->getSetting('contactName', true));

		// For Reviews, comments can actually be a compound of two comments.
		// If this is the case, then concatenate them before sending.
		$commentText = "";
		if ($insertedComments != null) {
			foreach ($insertedComments as $commentId) {
				$comment = &$paperCommentDao->getPaperCommentById($commentId);
				$commentText .= $comment->getComments() . "\n\n";
			}
		} else {
			$commentText = $this->getData('comments');
		}

		// Individually send an email to each of the recipients.
		foreach ($recipients as $emailAddress => $name) {
			$email->addRecipient($emailAddress, $name);

			$paramArray = array(
				'name' => $name,
				'commentName' => $this->user->getFullName(),
				'comments' => $commentText	
			);

			$email->sendWithParams($paramArray);
			$email->clearRecipients();
		}
	}
}

?>
