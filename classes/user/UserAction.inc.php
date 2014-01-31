<?php

/**
 * @file classes/user/UserAction.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserAction
 * @ingroup user
 * @see User
 *
 * @brief UserAction class.
 */

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
		$userDao =& DAORegistry::getDAO('UserDAO');
		$newUser =& $userDao->getUser($newUserId);
		foreach ($commentDao->getCommentsByUserId($oldUserId) as $comment) {
			$comment->setUser($newUser);
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

		// Transfer old user's valid registrations if new user does not
		// have similar registrations of if they're invalid
		$registrationDao =& DAORegistry::getDAO('RegistrationDAO');
		$oldUserRegistrations =& $registrationDao->getRegistrationsByUser($oldUserId);

		while ($oldUserRegistration =& $oldUserRegistrations->next()) {
			$schedConfId = $oldUserRegistration->getSchedConfId();
			$oldUserValidRegistration = $registrationDao->isValidRegistrationByUser($oldUserId, $schedConfId);

			if ($oldUserValidRegistration) {
				// Check if new user has a valid registration for sched conf
				$newUserRegistrationId = $registrationDao->getRegistrationIdByUser($newUserId, $schedConfId);

				if (empty($newUserRegistrationId)) {
					// New user does not have this registration, transfer old user's
					$oldUserRegistration->setUserId($newUserId);
					$registrationDao->updateRegistration($oldUserRegistration);
				} elseif (!$registrationDao->isValidRegistrationByUser($newUserId, $schedConfId)) {
					// New user has a registration but it's invalid. Delete it and
					// transfer old user's valid one
					$registrationDao->deleteRegistrationByUserIdSchedConf($newUserId, $schedConfId);
					$oldUserRegistration->setUserId($newUserId);
					$registrationDao->updateRegistration($oldUserRegistration);
				}
			}
		}

		// Delete any remaining oldUser registrations and associated options
		$registrationOptionDao =& DAORegistry::getDAO('RegistrationOptionDAO');
		$oldUserRegistrations =& $registrationDao->getRegistrationsByUser($oldUserId);

		while ($oldUserRegistration =& $oldUserRegistrations->next()) {
			$registrationOptionDao->deleteRegistrationOptionAssocByRegistrationId($oldUserRegistration->getRegistrationId());
		}
		$registrationDao->deleteRegistrationsByUserId($oldUserId);

		// Transfer old user's roles
		$roleDao =& DAORegistry::getDAO('RoleDAO');

		$roles =& $roleDao->getRolesByUserId($oldUserId);
		foreach ($roles as $role) {
			if (!$roleDao->roleExists($role->getConferenceId(), $role->getSchedConfId(), $newUserId, $role->getRoleId())) {
				$role->setUserId($newUserId);
				$roleDao->insertRole($role);
			}
		}
		$roleDao->deleteRoleByUserId($oldUserId);

		// Delete the old user and all remaining associated info.
		$sessionDao =& DAORegistry::getDAO('SessionDAO');
		$sessionDao->deleteSessionsByUserId($oldUserId);
		$temporaryFileDao =& DAORegistry::getDAO('TemporaryFileDAO');
		$temporaryFileDao->deleteTemporaryFilesByUserId($oldUserId);
		$userSettingsDao =& DAORegistry::getDAO('UserSettingsDAO');
		$userSettingsDao->deleteSettings($oldUserId);
		$groupMembershipDao =& DAORegistry::getDAO('GroupMembershipDAO');
		$groupMembershipDao->deleteMembershipByUserId($oldUserId);
		$trackDirectorsDao =& DAORegistry::getDAO('TrackDirectorsDAO');
		$trackDirectorsDao->deleteDirectorsByUserId($oldUserId);
		$userDao->deleteUserById($oldUserId);

		return true;
	}

}

?>
