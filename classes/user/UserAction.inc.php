<?php

/**
 * @file classes/user/UserAction.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserAction
 * @ingroup user
 * @see User
 *
 * @brief UserAction class.
 */

// $Id$


class UserAction {

	/**
	 * Constructor.
	 */
	function UserAction() {
	}

	/**
	 * Actions.
	 */

	/**
	 * Merge user accounts, including attributed papers etc.
	 */
	function mergeUsers($oldUserId, $newUserId) {
		// Need both user ids for merge
		if (empty($oldUserId) || empty($newUserId)) {
			return false;
		}

		$paperDao =& DAORegistry::getDAO('PaperDAO');
		foreach ($paperDao->getPapersByUserId($oldUserId) as $paper) {
			$paper->setUserId($newUserId);
			$paperDao->updatePaper($paper);
			unset($paper);
		}

		$commentDao =& DAORegistry::getDAO('CommentDAO');
		foreach ($commentDao->getCommentsByUserId($oldUserId) as $comment) {
			$comment->setUserId($newUserId);
			$commentDao->updateComment($comment);
			unset($comment);
		}

		$paperNoteDao =& DAORegistry::getDAO('PaperNoteDAO');
		$paperNotes =& $paperNoteDao->getPaperNotesByUserId($oldUserId);
		while ($paperNote =& $paperNotes->next()) {
			$paperNote->setUserId($newUserId);
			$paperNoteDao->updatePaperNote($paperNote);
			unset($paperNote);
		}

		$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
		$editAssignments =& $editAssignmentDao->getEditAssignmentsByUserId($oldUserId);
		while ($editAssignment =& $editAssignments->next()) {
			$editAssignment->setDirectorId($newUserId);
			$editAssignmentDao->updateEditAssignment($editAssignment);
			unset($editAssignment);
		}

		$directorSubmissionDao =& DAORegistry::getDAO('DirectorSubmissionDAO');
		$directorSubmissionDao->transferDirectorDecisions($oldUserId, $newUserId);

		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		foreach ($reviewAssignmentDao->getReviewAssignmentsByUserId($oldUserId) as $reviewAssignment) {
			$reviewAssignment->setReviewerId($newUserId);
			$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
			unset($reviewAssignment);
		}

		$paperEmailLogDao =& DAORegistry::getDAO('PaperEmailLogDAO');
		$paperEmailLogDao->transferPaperLogEntries($oldUserId, $newUserId);
		$paperEventLogDao =& DAORegistry::getDAO('PaperEventLogDAO');
		$paperEventLogDao->transferPaperLogEntries($oldUserId, $newUserId);

		$paperCommentDao =& DAORegistry::getDAO('PaperCommentDAO');
		foreach ($paperCommentDao->getPaperCommentsByUserId($oldUserId) as $paperComment) {
			$paperComment->setAuthorId($newUserId);
			$paperCommentDao->updatePaperComment($paperComment);
			unset($paperComment);
		}

		$accessKeyDao =& DAORegistry::getDAO('AccessKeyDAO');
		$accessKeyDao->transferAccessKeys($oldUserId, $newUserId);

		// Delete the old user and associated info.
		$sessionDao =& DAORegistry::getDAO('SessionDAO');
		$sessionDao->deleteSessionsByUserId($oldUserId);
		$registrationDao =& DAORegistry::getDAO('RegistrationDAO');
		$registrationDao->deleteRegistrationsByUserId($oldUserId);
		$temporaryFileDao =& DAORegistry::getDAO('TemporaryFileDAO');
		$temporaryFileDao->deleteTemporaryFilesByUserId($oldUserId);
		$notificationStatusDao =& DAORegistry::getDAO('NotificationStatusDAO');
		$notificationStatusDao->deleteNotificationStatusByUserId($oldUserId);
		$groupMembershipDao =& DAORegistry::getDAO('GroupMembershipDAO');
		$groupMembershipDao->deleteMembershipByUserId($oldUserId);
		$trackDirectorsDao =& DAORegistry::getDAO('TrackDirectorsDAO');
		$trackDirectorsDao->deleteDirectorsByUserId($oldUserId);
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$roleDao->deleteRoleByUserId($oldUserId);
		$userDao->deleteUserById($oldUserId);

		return true;
	}

}

?>
