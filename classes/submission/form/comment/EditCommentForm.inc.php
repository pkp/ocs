<?php

/**
 * @file EditCommentForm.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EditCommentForm
 * @ingroup submission_form
 *
 * @brief Edit comment form.
 */

//$Id$

import('form.Form');

class EditCommentForm extends Form {

	/** @var object the paper */
	var $paper;

	/** @var PaperComment the comment */
	var $comment;

	/** @var int the role of the comment author */
	var $roleId;

	/** @var User the user */
	var $user;

	/**
	 * Constructor.
	 * @param $paper object
	 * @param $comment object
	 */
	function EditCommentForm(&$paper, &$comment) {
		parent::Form('submission/comment/editComment.tpl');

		$this->comment = $comment;
		$this->roleId = $comment->getRoleId();

		$this->paper = $paper;
		$this->user =& Request::getUser();

		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Initialize form data from current comment.
	 */
	function initData() {
		$comment =& $this->comment;
		$this->_data = array(
			'commentId' => $comment->getId(),
			'commentTitle' => $comment->getCommentTitle(),
			'comments' => $comment->getComments(),
			'viewable' => $comment->getViewable(),
		);
	}	

	/**
	 * Display the form.
	 */
	function display($additionalHiddenParams = null) {
		$hiddenFormParams = array(
			'paperId' => $this->paper->getPaperId(),
			'commentId' => $this->comment->getCommentId()
		);
		if (isset($additionalHiddenParams)) {
			$hiddenFormParams = array_merge ($hiddenFormParams, $additionalHiddenParams);
		}

		$templateMgr =& TemplateManager::getManager();

		$isPeerReviewComment = $this->comment->getCommentType() == COMMENT_TYPE_PEER_REVIEW;
		$templateMgr->assign('isPeerReviewComment', $isPeerReviewComment); // FIXME

		$templateMgr->assign_by_ref('comment', $this->comment);
		$templateMgr->assign_by_ref('hiddenFormParams', $hiddenFormParams);

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
	 * Update the comment.
	 */
	function execute() {
		$commentDao =& DAORegistry::getDAO('PaperCommentDAO');

		// Update comment		
		$comment = $this->comment;
		$comment->setCommentTitle($this->getData('commentTitle'));
		$comment->setComments($this->getData('comments'));
		$comment->setViewable($this->getData('viewable') ? 1 : 0);
		$comment->setDateModified(Core::getCurrentDate());

		$commentDao->updatePaperComment($comment);
	}

	/**
	 * UGLEEE function that gets the recipients for a comment.
	 * @return $recipients array of recipients (email address => name)
	 */
	function emailHelper() {
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$conference =& Request::getConference();

		$recipients = array();

		// Get directors for paper
		$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
		$editAssignments =& $editAssignmentDao->getEditAssignmentsByPaperId($this->paper->getPaperId());
		$editAssignments =& $editAssignments->toArray();
		$directorAddresses = array();
		foreach ($editAssignments as $editAssignment) {
			$directorAddresses[$editAssignment->getDirectorEmail()] = $editAssignment->getDirectorFullName();
		}

		// If no directors are currently assigned, send this message to
		// all of the conference's directors.
		if (empty($directorAddresses)) {
			$directors =& $roleDao->getUsersByRoleId(ROLE_ID_DIRECTOR, $conference->getId());
			while (!$directors->eof()) {
				$director =& $directors->next();
				$directorAddresses[$director->getEmail()] = $director->getFullName();
			}
		}

		// Get reviewer
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignment =& $reviewAssignmentDao->getReviewAssignmentById($this->comment->getAssocId());
		if ($reviewAssignment != null && $reviewAssignment->getReviewerId() != null) {
			$reviewer =& $userDao->getUser($reviewAssignment->getReviewerId());
		} else {
			$reviewer = null;
		}

		// Get author
		$author =& $userDao->getUser($this->paper->getUserId());

		switch ($this->comment->getCommentType()) {
		case COMMENT_TYPE_PEER_REVIEW:
			if ($this->roleId == ROLE_ID_DIRECTOR || $this->roleId == ROLE_ID_TRACK_DIRECTOR) {
				// Then add reviewer
				if ($reviewer != null) {
					$recipients = array_merge($recipients, array($reviewer->getEmail() => $reviewer->getFullName()));
				}
			}
			break;

		case COMMENT_TYPE_DIRECTOR_DECISION:
			if ($this->roleId == ROLE_ID_DIRECTOR || $this->roleId == ROLE_ID_TRACK_DIRECTOR) {
				// Then add author
				if (isset($author)) $recipients = array_merge($recipients, array($author->getEmail() => $author->getFullName()));
			} else {
				// Then add directors
				$recipients = array_merge($recipients, $directorAddresses);
			}
			break;
		}

		return $recipients;
	}

	/**
	 * Email the comment.
	 * @param $recipients array of recipients (email address => name)
	 */
	function email($recipients) {
		import('mail.PaperMailTemplate');
		$email = new PaperMailTemplate($this->paper, 'SUBMISSION_COMMENT');

		foreach ($recipients as $emailAddress => $name) {
			$email->addRecipient($emailAddress, $name);
			$email->setSubject(strip_tags($this->paper->getLocalizedTitle()));

			$paramArray = array(
				'name' => $name,
				'commentName' => $this->user->getFullName(),
				'comments' => String::html2text($this->getData('comments'))
			);
			$email->assignParams($paramArray);

			$email->send();
			$email->clearRecipients();
		}
	}
}

?>
