<?php

/**
 * DirectorEventHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.director
 *
 * Handle requests for event management in site directoristration. 
 *
 * $Id$
 */

class DirectorEventHandler extends DirectorHandler {

	/**
	 * Display a list of the events hosted on the site.
	 */
	function events() {
		parent::validate();
		parent::setupTemplate(true);
		
		$conference = &Request::getConference();
		
		$rangeInfo = Handler::getRangeInfo('events');

		// TODO: use $rangeInfo here!
		
		$eventDao = &DAORegistry::getDAO('EventDAO');
		$events = &$eventDao->getEventsByConferenceId($conference->getConferenceId());
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign_by_ref('events', $events);
		$templateMgr->assign_by_ref('conference', $conference);
		$templateMgr->assign('helpTopicId', 'site.siteManagement');
		$templateMgr->display('director/events.tpl');
	}
	
	/**
	 * Display form to create a new event.
	 */
	function createEvent() {
		import('event.Event');
		$event = Request::getEvent();
		$conference = Request::getConference();
		
		if($event) {
			$eventId = $event->getEventId();
		} else {
			$eventId = null;
		}

		if($conference) {
			$conferenceId = $conference->getConferenceId();
		} else {
			$conferenceId = null;
		}
				
		DirectorEventHandler::editEvent(array($conferenceId, $eventId));
	}
	
	/**
	 * Display form to create/edit a event.
	 * @param $args array optional, if set the first parameter is the ID of the event to edit
	 */
	function editEvent($args = array()) {
		parent::validate();
		parent::setupTemplate(true);
		
		import('director.form.EventSiteSettingsForm');
		
		$settingsForm = &new EventSiteSettingsForm($args);
		$settingsForm->initData();
		$settingsForm->display();
	}
	
	/**
	 * Save changes to a event's settings.
	 */
	function updateEvent() {
		parent::validate();
		
		import('director.form.EventSiteSettingsForm');
		
		$settingsForm = &new EventSiteSettingsForm(
			array(Request::getUserVar('conferenceId'), Request::getUserVar('eventId')));
		$settingsForm->readInputData();
		
		if ($settingsForm->validate()) {
			$settingsForm->execute();
			Request::redirect(null, null, null, 'events');
			
		} else {
			parent::setupTemplate(true);
			$settingsForm->display();
		}
	}
	
	/**
	 * Delete a event.
	 * @param $args array first parameter is the ID of the event to delete
	 */
	function deleteEvent($args) {
		parent::validate();
		
		$eventDao = &DAORegistry::getDAO('EventDAO');
		
		if (isset($args) && !empty($args) && !empty($args[0])) {
			$eventId = $args[0];
			$event =& $eventDao->getEvent($eventId);

			// Look up the event path before we delete the event
			import('file.PublicFileManager');
			$publicFileManager = &new PublicFileManager();
			$eventFilesPath = $publicFileManager->getEventFilesPath($eventId);

			if ($eventDao->deleteEventById($eventId)) {
				// Delete event file tree
				// FIXME move this somewhere better.
				import('file.FileManager');
				$fileManager = &new FileManager();
				$eventPath = Config::getVar('files', 'files_dir') . '/conferences/' . $event->getConferenceId() . '/events/' . $eventId;
				$fileManager->rmtree($eventPath);

				$publicFileManager->rmtree($eventFilesPath);
			}
		}
		
		Request::redirect(null, null, null, 'events');
	}
	
	/**
	 * Change the sequence of a event on the site index page.
	 */
	function moveEvent() {
		parent::validate();
		
		$eventDao = &DAORegistry::getDAO('EventDAO');
		$event = &$eventDao->getEvent(Request::getUserVar('eventId'));
		
		if ($event != null) {
			$event->setSequence($event->getSequence() + (Request::getUserVar('d') == 'u' ? -1.5 : 1.5));
			$eventDao->updateEvent($event);
			$eventDao->resequenceEvents();
		}
		
		Request::redirect(null, null, null, 'events');
	}
}

?>
