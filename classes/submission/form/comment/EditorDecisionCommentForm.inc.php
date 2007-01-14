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
}

?>
