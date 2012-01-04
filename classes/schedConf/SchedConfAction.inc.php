<?php

/**
 * @file SchedConfAction.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SchedConfAction
 * @ingroup schedConf
 *
 * @brief SchedConfAction class.
 *
 */

// $Id$


class SchedConfAction {

	/**
	 * Constructor.
	 */
	function SchedConfAction() {
	}

	/**
	 * Actions.
	 */

	/**
	 * Get whether or not we permit users to register as readers
	 */
	function allowRegReader($schedConf) {
		return true;
	}

	/**
	 * Get whether or not we permit users to register as reviewers
	 */
	function allowRegReviewer($schedConf) {
		$allowRegReviewer = false;
		if($schedConf->getSetting('regReviewerOpenDate') && time() > $schedConf->getSetting('regReviewerOpenDate')) {
			$allowRegReviewer = true;
		}
		if($schedConf->getSetting('regReviewerCloseDate') && time() > $schedConf->getSetting('regReviewerCloseDate')) {
			$allowRegReviewer = false;
		}
		return $allowRegReviewer;
	}

	/**
	 * Get whether or not we permit users to register as authors
	 */
	function allowRegAuthor($schedConf) {
		$allowRegAuthor = false;
		if($schedConf->getSetting('regAuthorOpenDate') && time() > $schedConf->getSetting('regAuthorOpenDate')) {
			$allowRegAuthor = true;
		}
		if($schedConf->getSetting('regAuthorCloseDate') && time() > $schedConf->getSetting('regAuthorCloseDate')) {
			$allowRegAuthor = false;
		}
		return $allowRegAuthor;
	}

	/**
	 * Checks if a user has access to the scheduled conference
	 * @param $schedConf
	 * @return bool
	 */
	function mayViewSchedConf(&$schedConf) {
		$conference =& $schedConf->getConference();
		return $conference->getEnabled() || Validation::isSiteAdmin() || Validation::isConferenceManager($conference->getId()) || Validation::isDirector($conference->getId(), $schedConf->getId()) || Validation::isTrackDirector($conference->getId(), $schedConf->getId());
	}

	/**
	 * Checks if a user has access to the presentations index (titles and abstracts)
	 * @param $schedConf
	 * @return bool
	 */
	function mayViewProceedings(&$schedConf) {
		if(Validation::isSiteAdmin() || Validation::isConferenceManager() || Validation::isDirector() || Validation::isTrackDirector()) {
			return true;
		}

		if(!SchedConfAction::mayViewSchedConf($schedConf)) {
			return false;
		}

		if(($schedConf->getSetting('postAbstracts') && time() > $schedConf->getSetting('postAbstractsDate')) ||
				($schedConf->getSetting('postPapers')) && time() > $schedConf->getSetting('postPapersDate')) {

			// Abstracts become publicly available as soon as anything is released.
			// Is this too strong an assumption? Presumably, posting abstracts is an
			// unabashedly good thing (since it drums up interest in the conference.)
			return true;
		}

		return false;
	}

	/**
	 * Checks if a user has access to view papers
	 * @param $schedConf object
	 * @param $conference object
	 * @return bool
	 */
	function mayViewPapers(&$schedConf, &$conference) {
		if(Validation::isSiteAdmin() || Validation::isConferenceManager() || Validation::isDirector() || Validation::isTrackDirector()) {
			return true;
		}

		if(!SchedConfAction::mayViewSchedConf($schedConf)) {
			return false;
		}

		// Allow open access once the "open access" date has passed.
		$paperAccess = $conference->getSetting('paperAccess');
		if ($paperAccess == PAPER_ACCESS_OPEN) return true;

		if($schedConf->getSetting('delayOpenAccess') && time() > $schedConf->getSetting('delayOpenAccessDate')) {
			if(Validation::isReader() && $paperAccess == PAPER_ACCESS_ACCOUNT_REQUIRED) {
				return true;
			}
		}

		if($schedConf->getSetting('postPapers') && time() > $schedConf->getSetting('postPapersDate')) {
			if(SchedConfAction::registeredUser($schedConf)) {
				return true;
			}
			if(SchedConfAction::registeredDomain($schedConf)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks if user is entitled by dint of granted roles
	 * @return bool
	 */
	function entitledUser(&$schedConf) {
		$user =& Request::getUser();

		if (isset($user) && isset($schedConf)) {
			// If the user is a conference manager, director, or track director,
			// it is assumed that they are allowed to view the scheduled conference as a registrant.
			$roleDao =& DAORegistry::getDAO('RoleDAO');
			$registrationAssumedRoles = array(
				ROLE_ID_CONFERENCE_MANAGER,
				ROLE_ID_DIRECTOR,
				ROLE_ID_TRACK_DIRECTOR
			);

			// First check for scheduled conference roles
			$roles =& $roleDao->getRolesByUserId($user->getId(), $schedConf->getConferenceId(), $schedConf->getId());
			foreach ($roles as $role) {
				if (in_array($role->getRoleId(), $registrationAssumedRoles)) return true;
			}

			// Second, conference-level roles
			$roles =& $roleDao->getRolesByUserId($user->getId(), $schedConf->getConferenceId(), 0);
			foreach ($roles as $role) {
				if (in_array($role->getRoleId(), $registrationAssumedRoles)) return true;
			}
		}

		$result = false;
		HookRegistry::call('SchedConfAction::entitledUser', array(&$schedConf, &$result));
		return $result;
	}

	/**
	 * Checks if user has registration
	 * @return bool
	 */
	function registeredUser(&$schedConf) {
		$user =& Request::getUser();
		$registrationDao =& DAORegistry::getDAO('RegistrationDAO');
		$result = false;

		if (isset($user) && isset($schedConf)) {

			if(SchedConfAction::entitledUser($schedConf)) return true;

			$result = $registrationDao->isValidRegistration(null, null, $user->getId(), $schedConf->getId());
		}
		HookRegistry::call('SchedConfAction::registeredUser', array(&$schedConf, &$result));
		return $result;
	}

	/**
	 * Checks if remote client domain or ip is allowed
	 * @return bool
	 */
	function registeredDomain(&$schedConf) {
		$schedConf =& Request::getSchedConf();
	
		$registrationDao =& DAORegistry::getDAO('RegistrationDAO');
		$result = $registrationDao->isValidRegistration(Request::getRemoteDomain(), Request::getRemoteAddr(), null, $schedConf->getId());
		HookRegistry::call('SchedConfAction::registeredDomain', array(&$schedConf, &$result));
		return $result;
	}

	/**
	 * Checks whether or not submissions are currently open.
	 * @return bool
	 */
	function submissionsOpen(&$schedConf) {
		$submissionsOpenDate = $schedConf->getSetting('submissionsOpenDate');
		$submissionsCloseDate = $schedConf->getSetting('submissionsCloseDate');

		$currentTime = time();

		return (
			$submissionsOpenDate && $submissionsCloseDate &&
			$currentTime >= $submissionsOpenDate &&
			$currentTime < $submissionsCloseDate
		);
	}
}

?>
