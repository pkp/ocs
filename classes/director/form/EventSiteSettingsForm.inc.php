<?php

/**
 * EventSiteSettingsForm.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package director.form
 *
 * Form for conference director to edit basic event settings.
 *
 * $Id$
 */

import('db.DBDataXMLParser');
import('form.Form');

class EventSiteSettingsForm extends Form {

	/** The ID of the event being edited */
	var $eventId;
	var $conferenceId;
	
	/**
	 * Constructor.
	 * @param $eventId omit for a new event
	 */
	function EventSiteSettingsForm($args = array()) {
		parent::Form('director/eventSettings.tpl');

		$this->conferenceId = $args[0];
		$this->eventId = $args[1];
		
		// Validation checks for this form
		$this->addCheck(new FormValidator($this, 'title', 'required', 'director.events.form.titleRequired'));
		$this->addCheck(new FormValidator($this, 'path', 'required', 'director.events.form.pathRequired'));
		$this->addCheck(new FormValidatorAlphaNum($this, 'path', 'required', 'director.events.form.pathAlphaNumeric'));
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('eventId', $this->eventId);
		$templateMgr->assign('conferenceId', $this->conferenceId);
		$templateMgr->assign('helpTopicId', 'director.eventManagement');
		$templateMgr->assign('dateExtentFuture', EVENT_DATE_YEAR_OFFSET_FUTURE);
		parent::display();
	}
	
	/**
	 * Initialize form data from current settings.
	 */
	function initData() {
		if(isset($this->eventId)) {
			$eventDao = &DAORegistry::getDAO('EventDAO');
			$event = &$eventDao->getEvent($this->eventId);
		
			if($event != null) {
				$this->_data = array(
					'enabled' => 1,
					'conferenceId' => $event->getConferenceId(),
					'title' => $event->getTitle(),
					'description' => $event->getSetting('eventIntroduction'),
					'path' => $event->getPath(),
					'enabled' => $event->getEnabled(),
					'startDate' => $event->getStartDate(),
					'endDate' => $event->getEndDate()
				);
			} else {
				$this->eventId = null;
			}
		}

		$conferenceDao = &DAORegistry::getDAO('ConferenceDAO');
		$conference = &$conferenceDao->getConference($this->conferenceId);
		if ($conference == null) {
			// TODO: redirect?
			$this->conferenceId = null;
		}

		if (!isset($this->eventId)) {
			$this->_data = array(
				'enabled' => 1,
				'conferenceId' => $this->conferenceId
			);
		}
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('conferenceId', 'title', 'description', 'path', 'enabled'));
		$this->setData('enabled', (int)$this->getData('enabled'));
		$this->setData('startDate', Request::getUserDateVar('startDate'));
		$this->setData('endDate', Request::getUserDateVar('endDate'));

		if (isset($this->eventId)) {
			$eventDao = &DAORegistry::getDAO('EventDAO');
			$event = &$eventDao->getEvent($this->eventId);
			$this->setData('oldPath', $event->getPath());
		}
	}
	
	/**
	 * Save event settings.
	 */
	function execute() {
		$eventDao = &DAORegistry::getDAO('EventDAO');
		$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');
		
		$conference =& $conferenceDao->getConference($this->getData('conferenceId'));
		
		if (isset($this->eventId)) {
			$event = &$eventDao->getEvent($this->eventId);
		}
		
		if (!isset($event)) {
			$event = &new Event();
		}
		
		$event->setConferenceId($this->getData('conferenceId'));
		$event->setPath($this->getData('path'));
		$event->setTitle($this->getData('title'));
		$event->setEnabled($this->getData('enabled'));

		if ($event->getEventId() != null) {
			$eventDao->updateEvent($event);
			$event->updateSetting('eventIntroduction', $this->getData('description'));
			$event->setStartDate($this->getData('startDate'));
			$event->setEndDate($this->getData('endDate'));
		} else {
			$eventId = $eventDao->insertEvent($event);
			$event->updateSetting('eventIntroduction', $this->getData('description'));
			$event->setStartDate($this->getData('startDate'));
			$event->setEndDate($this->getData('endDate'));
			$eventDao->resequenceEvents();

			// Make this user the event manager
			$sessionManager = &SessionManager::getManager();
			$userSession = &$sessionManager->getUserSession();
			if ($userSession->getUserId() != null && $userSession->getUserId() != 0 && !empty($eventId)) {
				$roleDao = &DAORegistry::getDAO('RoleDAO');

				// Don't create event director role on this event it's already granted on the whole conference
				if (!$roleDao->roleExists($event->getConferenceId(), 0, $userSession->getUserId(), ROLE_ID_EVENT_DIRECTOR)) {
					$role = &new Role();
					$role->setEventId($eventId);
					$role->setConferenceId($event->getConferenceId());
					$role->setUserId($userSession->getUserId());
					$role->setRoleId(ROLE_ID_EVENT_DIRECTOR);
					$roleDao->insertRole($role);
				}
			}
			
			// Make the file directories for the event
			import('file.FileManager');
			$conferenceId = $event->getConferenceId();
			$privateBasePath = Config::getVar('files','files_dir') . '/conferences/' . $conferenceId . '/events/' . $eventId;
			$publicBasePath = Config::getVar('files','public_files_dir') . '/conferences/' . $conferenceId . '/events/' . $eventId;
			FileManager::mkdir($privateBasePath);
			FileManager::mkdir($privateBasePath . '/papers');
			FileManager::mkdir($privateBasePath . '/tracks');
			FileManager::mkdir($publicBasePath);

			// Install default event settings
			$eventSettingsDao = &DAORegistry::getDAO('EventSettingsDAO');
			$eventSettingsDao->installSettings($eventId, 'registry/eventSettings.xml', array(
				'indexUrl' => Request::getIndexUrl(),
				'conferencePath' => $conference->getPath(),
				'eventPath' => $this->getData('path'),
				'eventName' => $this->getData('title')
			));
			
			// Create a default "Papers" track
			$trackDao = &DAORegistry::getDAO('TrackDAO');
			$track = &new Track();
			$track->setEventId($eventId);
			$track->setTitle(Locale::translate('track.default.title'));
			$track->setAbbrev(Locale::translate('track.default.abbrev'));
			$track->setMetaIndexed(true);
			$track->setPolicy(Locale::translate('track.default.policy'));
			$track->setEditorRestricted(false);
			$track->setHideTitle(false);
			$trackDao->insertTrack($track);
		}
		
		// Mark the event as 'current'
		$event->setCurrent(true);
		$eventDao->updateCurrentEvent($event->getConferenceId(), $event);
	}
	
}

?>
