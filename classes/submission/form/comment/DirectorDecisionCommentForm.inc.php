<?php

/**
 * @file DirectorDecisionCommentForm.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DirectorDecisionCommentForm
 * @ingroup submission_form
 *
 * @brief DirectorDecisionComment form.
 */

//$Id$


import("submission.form.comment.CommentForm");

class DirectorDecisionCommentForm extends CommentForm {

	/**
	 * Constructor.
	 * @param $paper object
	 */
	function DirectorDecisionCommentForm($paper, $roleId) {
		parent::CommentForm($paper, COMMENT_TYPE_DIRECTOR_DECISION, $roleId, $paper->getId());
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageTitle', 'submission.comments.directorAuthorCorrespondence');
		$templateMgr->assign('paperId', $this->paper->getPaperId());
		$templateMgr->assign('commentAction', 'postDirectorDecisionComment');
		$templateMgr->assign('hiddenFormParams', 
			array(
				'paperId' => $this->paper->getPaperId()
			)
		);

		$isDirector = $this->roleId == ROLE_ID_DIRECTOR || $this->roleId == ROLE_ID_TRACK_DIRECTOR ? true : false;
		$templateMgr->assign('isDirector', $isDirector);

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
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$conference =& Request::getConference();

		// Create list of recipients:

		// Director Decision comments are to be sent to the director or author,
		// the opposite of whomever wrote the comment.
		$recipients = array();

		if ($this->roleId == ROLE_ID_DIRECTOR || $this->roleId == ROLE_ID_TRACK_DIRECTOR) {
			// Then add author
			$user =& $userDao->getUser($this->paper->getUserId());

			if ($user) $recipients = array_merge($recipients, array($user->getEmail() => $user->getFullName()));
		} else {
			// Then add director
			$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
			$editAssignments =& $editAssignmentDao->getEditAssignmentsByPaperId($this->paper->getPaperId());
			$directorAddresses = array();
			while (!$editAssignments->eof()) {
				$editAssignment =& $editAssignments->next();
				$directorAddresses[$editAssignment->getDirectorEmail()] = $editAssignment->getDirectorFullName();
			}

			// If no directors are currently assigned to this paper,
			// send the email to all directors for the conference
			if (empty($directorAddresses)) {
				$directors =& $roleDao->getUsersByRoleId(ROLE_ID_DIRECTOR, $conference->getId());
				while (!$directors->eof()) {
					$director =& $directors->next();
					$directorAddresses[$director->getEmail()] = $director->getFullName();
				}
			}
			$recipients = array_merge($recipients, $directorAddresses);
		}

		parent::email($recipients);	
	}
}

?>
