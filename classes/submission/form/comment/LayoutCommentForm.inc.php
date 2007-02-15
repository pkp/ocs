<?php

/**
 * LayoutCommentForm.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission.form
 *
 * LayoutComment form.
 *
 * $Id$
 */
 
import("submission.form.comment.CommentForm");

class LayoutCommentForm extends CommentForm {

	/**
	 * Constructor.
	 * @param $paper object
	 */
	function LayoutCommentForm($paper, $roleId) {
		parent::CommentForm($paper, COMMENT_TYPE_LAYOUT, $roleId, $paper->getPaperId());
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageTitle', 'submission.comments.comments');
		$templateMgr->assign('commentAction', 'postLayoutComment');
		$templateMgr->assign('commentType', 'layout');
		$templateMgr->assign('hiddenFormParams', 
			array(
				'paperId' => $this->paper->getPaperId()
			)
		);
		
		parent::display();
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		parent::readInputData();
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
		$schedConf = &Request::getSchedConf();
	
		// Create list of recipients:
		
		// Layout comments are to be sent to the director or layout editor;
		// the opposite of whomever posted the comment.
		$recipients = array();
		
		if ($this->roleId == ROLE_ID_DIRECTOR) {
			// Then add layout editor
			$layoutAssignmentDao = &DAORegistry::getDAO('LayoutAssignmentDAO');
			$layoutAssignment = &$layoutAssignmentDao->getLayoutAssignmentByPaperId($this->paper->getPaperId());
			
			// Check to ensure that there is a layout editor assigned to this paper.
			if ($layoutAssignment != null && $layoutAssignment->getEditorId() > 0) {
				$user = &$userDao->getUser($layoutAssignment->getEditorId());
			
				if ($user) $recipients = array_merge($recipients, array($user->getEmail() => $user->getFullName()));
			}
		} else {
			// Then add director
			$editAssignmentDao = &DAORegistry::getDAO('EditAssignmentDAO');
			$editAssignments = &$editAssignmentDao->getEditAssignmentsByPaperId($this->paper->getPaperId());
			$directorAddresses = array();
			while (!$editAssignments->eof()) {
				$editAssignment =& $editAssignments->next();
				if ($editAssignment->getCanEdit()) $directorAddresses[$editAssignment->getDirectorEmail()] = $editAssignment->getDirectorFullName();
				unset($editAssignment);
			}

			// If no directors are currently assigned to this paper,
			// send the email to all directors for the conference
			if (empty($directorAddresses)) {
				$directors = &$roleDao->getUsersByRoleId(ROLE_ID_DIRECTOR, $conference->getConferenceId(), $schedConf->getSchedConfId());
				while (!$directors->eof()) {
					$director = &$directors->next();
					$directorAddresses[$director->getEmail()] = $director->getFullName();
				}
			}
			$recipients = array_merge($recipients, $directorAddresses);
		}
		
		parent::email($recipients);
	}
}

?>
