<?php

/**
 * EventAction.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package event
 *
 * EventAction class.
 *
 * $Id$
 */

class EventAction {

	/**
	 * Constructor.
	 */
	function EventAction() {
	}
	
	/**
	 * Actions.
	 */
	 
	/**
	 * Get whether or not we permit users to register as readers
	 */
	function allowRegReader($event) {
		$allowRegReader = false;
		if($event->getSetting('openRegReader')) {
			$allowRegReader = true;
		}
		return $allowRegReader;
	}

	/**
	 * Get whether or not we permit users to register as reviewers
	 */
	function allowRegReviewer($event) {
		$allowRegReviewer = false;
		if($event->getSetting('regReviewerOpenDate') && time() > $event->getSetting('regReviewerOpenDate')) {
			$allowRegReviewer = true;
		}
		if($event->getSetting('regReviewerCloseDate') && time() > $event->getSetting('regReviewerCloseDate')) {
			$allowRegReviewer = false;
		}
		return $allowRegReviewer;
	}

	/**
	 * Get whether or not we permit users to register as authors
	 */
	function allowRegAuthor($event) {
		$allowRegAuthor = false;
		if($event->getSetting('regAuthorOpenDate') && time() > $event->getSetting('regAuthorOpenDate')) {
			$allowRegAuthor = true;
		}
		if($event->getSetting('regAuthorCloseDate') && time() > $event->getSetting('regAuthorCloseDate')) {
			$allowRegAuthor = false;
		}
		return $allowRegAuthor;
	}
	
	/**
	 * Checks if a user has access to the event
	 * @param $event
	 * @return bool
	 */
	function mayViewEvent(&$event) {
		$conference =& $event->getConference();
		return $event->getEnabled() && $conference->getEnabled();
	}

	/**
	 * Checks if a user has access to the proceedings index (titles and abstracts)
	 * @param $event
	 * @return bool
	 */
	function mayViewProceedings(&$event) {
		if(Validation::isSiteAdmin() || Validation::isConferenceDirector() || Validation::isEditor() || Validation::isTrackEditor()) {
			return true;
		}

		if(!EventAction::mayViewEvent($event)) {
			return false;
		}
		
		if($event->getSetting('delayOpenAccess') && time() > $event->getSetting('delayOpenAccessUntil')) {
			if($event->getSetting('openAccessVisitor')) {
				return true;
			}
			if(Validation::isReader() && $event->getSetting('openAccessReader')) {
				return true;
			}
		}

		if(($event->getSetting('postAbstracts') && time() > $event->getSetting('postAbstractsDate')) ||
				($event->getSetting('postPapers')) && time() > $event->getSetting('postPapersDate')) {

			// Abstracts become publicly available as soon as anything is released.
			// Is this too strong an assumption? Presumably, posting abstracts is an
			// unabashedly good thing (since it drums up interest in the conference.)
			return true;
		}

		return false;
	}

	/**
	 * Checks if a user has access to the proceedings index (titles and abstracts)
	 * @param $event
	 * @return bool
	 */
	function mayViewPapers(&$event) {
		if(Validation::isSiteAdmin() || Validation::isConferenceDirector() || Validation::isEditor() || Validation::isTrackEditor()) {
			return true;
		}

		if(!EventAction::mayViewEvent($event)) {
			return false;
		}
		
		// Allow open access once the "open access" date has passed.
		
		if($event->getSetting('delayOpenAccess') && time() > $event->getSetting('delayOpenAccessUntil')) {
			if($event->getSetting('openAccessVisitor')) {
				return true;
			}
			if(Validation::isReader() && $event->getSetting('openAccessReader')) {
				return true;
			}
		}

		if($event->getSetting('postPapers') && time() > $event->getSetting('postPapersDate')) {

			if($event->getSetting('registrationEnabled') && EventAction::registeredUser($event)) {
				return true;
			} else {
				if($event->getSetting('openAccessVisitor')) {
					return true;
				}
				if(Validation::isReader() && $event->getSetting('openAccessReader')) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Checks if user is entitled by dint of granted roles
	 * @return bool
	 */
	function entitledUser(&$event) {
		$user = &Request::getUser();

		if (isset($user) && isset($event)) {
			// If the user is a event manager, editor, track editor, or layout editor,
			// it is assumed that they are allowed to view the event as a registrant.
			$roleDao = &DAORegistry::getDAO('RoleDAO');
			$registrationAssumedRoles = array(
				ROLE_ID_CONFERENCE_DIRECTOR,
				ROLE_ID_EDITOR,
				ROLE_ID_TRACK_EDITOR,
				ROLE_ID_REGISTRATION_MANAGER
			);

			// First check for event roles
			$roles = &$roleDao->getRolesByUserId($user->getUserId(), $event->getConferenceId(), $event->getEventId());
			foreach ($roles as $role) {
				if (in_array($role->getRoleId(), $registrationAssumedRoles)) return true;
			}

			// Second, conference-level roles
			$roles = &$roleDao->getRolesByUserId($user->getUserId(), $event->getConferenceId(), 0);
			foreach ($roles as $role) {
				if (in_array($role->getRoleId(), $registrationAssumedRoles)) return true;
			}
		}

		$result = false;
		HookRegistry::call('EventAction::entitledUser', array(&$event, &$result));
		return $result;
	}

	/**
	 * Checks if user has registration
	 * @return bool
	 */
	function registeredUser(&$event) {
		$user = &Request::getUser();
		$registrationDao = &DAORegistry::getDAO('RegistrationDAO');

		if (isset($user) && isset($event)) {

			if(EventAction::entitledUser($event)) return true;
			
			$result = $registrationDao->isValidRegistration(null, null, $user->getUserId(), $event->getEventId());
		}
		HookRegistry::call('EventAction::registeredUser', array(&$event, &$result));
		return $result;
	}
	
	/**
	 * Checks if remote client domain or ip is allowed
	 * @return bool
	 */
	function registeredDomain(&$event) {
		$registrationDao = &DAORegistry::getDAO('RegistrationDAO');
		$result = $registrationDao->isValidRegistration(Request::getRemoteDomain(), Request::getRemoteAddr(), null, $event->getEventId());
		HookRegistry::call('EventAction::registeredDomain', array(&$event, &$result));
		return $result;
	}
}

?>
