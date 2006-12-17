<?php

/**
 * EditorDecisionCommentForm.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission.form
 *
 * EditorDecisionComment form.
 *
 * $Id$
 *
 */
 
import("submission.form.comment.CommentForm");

class EditorDecisionCommentForm extends CommentForm {

	/**
	 * Constructor.
	 * @param $paper object
	 */
	function EditorDecisionCommentForm($paper, $roleId) {
		parent::CommentForm($paper, COMMENT_TYPE_EDITOR_DECISION, $roleId, $paper->getPaperId());
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageTitle', 'submission.comments.editorAuthorCorrespondence');
		$templateMgr->assign('paperId', $this->paper->getPaperId());
		$templateMgr->assign('commentAction', 'postEditorDecisionComment');
		$templateMgr->assign('hiddenFormParams', 
			array(
				'paperId' => $this->paper->getPaperId()
			)
		);
		
		$isEditor = $this->roleId == ROLE_ID_EDITOR || $this->roleId == ROLE_ID_TRACK_EDITOR ? true : false;
		$templateMgr->assign('isEditor', $isEditor);
		
		parent::display();
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(
			array(
				'commentTitle',
				'comments'
			)
		);
	}
	
	/**
	 * Add the comment.
	 */
	function execute() {
		parent::execute();
	}
	
	/**
	 * Email the comment.
	 */
	function email() {
		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$conference = &Request::getConference();
		
		// Create list of recipients:
		
		// Editor Decision comments are to be sent to the editor or author,
		// the opposite of whomever wrote the comment.
		$recipients = array();
		
		if ($this->roleId == ROLE_ID_EDITOR) {
			// Then add author
			$user = &$userDao->getUser($this->paper->getUserId());
			
			if ($user) $recipients = array_merge($recipients, array($user->getEmail() => $user->getFullName()));
		} else {
			// Then add editor
			$editAssignmentDao = &DAORegistry::getDAO('EditAssignmentDAO');
			$editAssignments = &$editAssignmentDao->getEditAssignmentsByPaperId($this->paper->getPaperId());
			$editorAddresses = array();
			while (!$editAssignments->eof()) {
				$editAssignment =& $editAssignments->next();
				$editorAddresses[$editAssignment->getEditorEmail()] = $editAssignment->getEditorFullName();
			}

			// If no editors are currently assigned to this paper,
			// send the email to all editors for the conference
			if (empty($editorAddresses)) {
				$editors = &$roleDao->getUsersByRoleId(ROLE_ID_EDITOR, $conference->getConferenceId());
				while (!$editors->eof()) {
					$editor = &$editors->next();
					$editorAddresses[$editor->getEmail()] = $editor->getFullName();
				}
			}
			$recipients = array_merge($recipients, $editorAddresses);
		}
		
		parent::email($recipients);	
	}
	
	/**
	 * Imports Peer Review comments.
	 * FIXME: Need to apply localization to these strings.
	 */
	function importPeerReviews() {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignments = &$reviewAssignmentDao->getReviewAssignmentsByPaperId($this->paper->getPaperId(), $this->paper->getReviewProgress(), $this->paper->getCurrentRound());
		$reviewIndexes = &$reviewAssignmentDao->getReviewIndexesForRound($this->paper->getPaperId(), $this->paper->getReviewProgress(), $this->paper->getCurrentRound());	
		
		$paperCommentDao = &DAORegistry::getDAO('PaperCommentDAO');
				
		$this->importPeerReviews = true;
		$this->peerReviews = "The editor should replace this text with the editorial decision and explanation for this submission.\n\n";
		
		foreach ($reviewAssignments as $reviewAssignment) {
			// If the reviewer has completed the assignment, then import the review.
			if ($reviewAssignment->getDateCompleted() != null && !$reviewAssignment->getCancelled()) {
				// Get the comments associated with this review assignment
				$paperComments = &$paperCommentDao->getPaperComments($this->paper->getPaperId(), COMMENT_TYPE_PEER_REVIEW, $reviewAssignment->getReviewId());
			
				$this->peerReviews .= "------------------------------------------------------\n";
				$this->peerReviews .= "Reviewer " . chr(ord('A') + $reviewIndexes[$reviewAssignment->getReviewId()]) . ":\n";
				
				if (is_array($paperComments)) {
					foreach ($paperComments as $comment) {
						// If the comment is viewable by the author, then add the comment.
						if ($comment->getViewable()) {
							$this->peerReviews .= $comment->getComments() . "\n";
						}
					}
				}
				
				$this->peerReviews .= "------------------------------------------------------\n\n";
			}
		}			
	}
}

?>
