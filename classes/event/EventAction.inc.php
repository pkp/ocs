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
	 * Smarty usage: {print_event_id paperId="$paperId"}
	 *
	 * Custom Smarty function for printing the event id
	 * @return string
	 */
	/*function smartyPrintEventId($params, &$smarty) {
		if (isset($params) && !empty($params)) {
			if (isset($params['paperId'])) {
				$eventDao = &DAORegistry::getDAO('EventDAO');
				$event = &$eventDao->getEventByPaperId($params['paperId']);
				if ($event != null) {
					return $event->getEventIdentification();
				}
			}
		}
	}*/

	/**
	 * Checks if registration is required for viewing the event
	 * @param $event
	 * @return bool
	 */
	function registrationRequired(&$event) {
		$currentEvent =& Request::getEvent();
		if (!$currentEvent || $currentEvent->getEventId() !== $event->getEventId()) {
			$eventDao = &DAORegistry::getDAO('EventDAO');
			$event =& $eventDao->getEvent($event->getEventId());
		} else {
			$event =& $currentEvent;
		}

		$result = $event->getSetting('enableRegistration', true) && ($event->getPublicationState() == PUBLICATION_STATE_PARTICIPANTS);
		HookRegistry::call('EventAction::registrationRequired', array(&$conference, &$event, &$result));
		return $result;
	}

	/**
	 * Checks if user is entitled by dint of granted roles
	 * @return bool
	 */
	function entitledUser(&$event) {
		$user = &Request::getUser();

		if (isset($user) && isset($event)) {
			// If the user is a event manager, editor, section editor,
			// layout editor, copyeditor, or proofreader, it is assumed
			// that they are allowed to view the event as a registrant.
			$roleDao = &DAORegistry::getDAO('RoleDAO');
			$registrationAssumedRoles = array(
				ROLE_ID_CONFERENCE_DIRECTOR,
				ROLE_ID_EVENT_DIRECTOR,
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

	/**
	 * builds the event options pulldown for published and unpublished events
	 * @param $current bool retrieve current or not
	 * @param $published bool retrieve published or non-published events
	 */
	/*function getEventOptions() {
		$eventOptions = array();

		$event = &Request::getEvent();
		$eventId = $event->getEventId();

		$eventDao = &DAORegistry::getDAO('EventDAO');

		$eventOptions['-100'] =  '------    ' . Locale::translate('editor.events.futureEvents') . '    ------';
		$eventIterator = $eventDao->getUnpublishedEvents($eventId);
		while (!$eventIterator->eof()) {
			$event = &$eventIterator->next();
			$eventOptions[$event->getEventId()] = $event->getEventIdentification();
		}
		$eventOptions['-101'] = '------    ' . Locale::translate('editor.events.currentEvent') . '    ------';
		$eventsIterator = $eventDao->getPublishedEvents($eventId, true);
		$events = $eventsIterator->toArray();
		if (isset($events[0]) && $events[0]->getCurrent()) {
			$eventOptions[$events[0]->getEventId()] = $events[0]->getEventIdentification();
			array_shift($events);
		}
		$eventOptions['-102'] = '------    ' . Locale::translate('editor.events.backEvents') . '    ------';
		foreach ($events as $event) {
			$eventOptions[$event->getEventId()] = $event->getEventIdentification();
		}

		return $eventOptions;
	}*/

}

?>
