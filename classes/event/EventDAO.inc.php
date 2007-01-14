<?php

/**
 * EventDAO.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package event
 *
 * Class for Event DAO.
 * Operations for retrieving and modifying Event objects.
 *
 * $Id$
 */

import ('event.Event');

class EventDAO extends DAO {

	/**
	 * Constructor.
	 */
	function EventDAO() {
		parent::DAO();
	}
	
	/**
	 * Retrieve a event by ID.
	 * @param $eventId int
	 * @return Event
	 */
	function &getEvent($eventId) {
		$result = &$this->retrieve(
			'SELECT * FROM events WHERE event_id = ?', $eventId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnEventFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		unset($result);
		return $returner;
	}
	
	/**
	 * Retrieve a event by path.
	 * @param $path string
	 * @return Event
	 */
	function &getEventByPath($path, $conferenceId = null) {
		if($conferenceId == null) {
			$conference = &Request::getConference();

			if(!$conference)
				$conferenceId = -1;
			else
				$conferenceId = $conference->getConferenceId();
		}
		
		$returner = null;
		$result = &$this->retrieve(
			'SELECT * FROM events WHERE path = ? and conference_id = ?',
			array($path, $conferenceId));
		
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnEventFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		unset($result);
		return $returner;
	}
	
	/**
	 * Internal function to return a Event object from a row.
	 * @param $row array
	 * @return Event
	 */
	function &_returnEventFromRow(&$row) {
		$event = &new Event();
		$event->setEventId($row['event_id']);
		$event->setTitle($row['title']);
		$event->setPath($row['path']);
		$event->setSequence($row['seq']);
		$event->setEnabled($row['enabled']);
		$event->setConferenceId($row['conference_id']);
		$event->setStartDate($this->datetimeFromDB($row['start_date']));
		$event->setEndDate($this->datetimeFromDB($row['end_date']));
		
		HookRegistry::call('EventDAO::_returnEventFromRow', array(&$event, &$row));

		return $event;
	}

	/**
	 * Insert a new event.
	 * @param $event Event
	 */	
	function insertEvent(&$event) {
		$this->update(
			sprintf('INSERT INTO events
				(conference_id, title, path, seq, enabled, start_date, end_date)
				VALUES
				(?, ?, ?, ?, ?, %s, %s)',
				$this->datetimeToDB($event->getStartDate()),
				$this->datetimeToDB($event->getEndDate())),
			array(
				$event->getConferenceId(),
				$event->getTitle(),
				$event->getPath(),
				$event->getSequence() == null ? 0 : $event->getSequence(),
				$event->getEnabled() ? 1 : 0
			)
		);
		
		$event->setEventId($this->getInsertEventId());
		return $event->getEventId();
	}
	
	/**
	 * Update an existing event.
	 * @param $event Event
	 */
	function updateEvent(&$event) {
		return $this->update(
			sprintf('UPDATE events
				SET
					conference_id = ?,
					title = ?,
					path = ?,
					seq = ?,
					enabled = ?,
					start_date = %s,
					end_date = %s
				WHERE event_id = ?',
				$this->datetimeToDB($event->getStartDate()),
				$this->datetimeToDB($event->getEndDate())),
			array(
				$event->getConferenceId(),
				$event->getTitle(),
				$event->getPath(),
				$event->getSequence(),
				$event->getEnabled() ? 1 : 0,
				$event->getEventId()
			)
		);
	}
	
	/**
	 * Delete a event, INCLUDING ALL DEPENDENT ITEMS.
	 * @param $event Event
	 */
	function deleteEvent(&$event) {
		return $this->deleteEventById($event->getEventId());
	}
	
	/**
	 * Retrieves all events for a conference
	 * @param $conferenceId
	 */
	function &getEventsByConferenceId($conferenceId) {
		$result = &$this->retrieve(
			'SELECT i.*
			FROM events i
				WHERE i.conference_id = ?',
			$conferenceId
		);
		
		$returner = &new DAOResultFactory($result, $this, '_returnEventFromRow');
		return $returner;
	}

	/**
	 * Delete all events by conference ID.
	 * @param $eventId int
	 */
	function deleteEventsByConferenceId($conferenceId) {
		$events = $this->getEventsByConferenceId($conferenceId);
		
		while (!$events->eof()) {
			$event = &$events->next();
			$this->deleteEventById($event->getEventId());
		}
	}
	
	/**
	 * Delete a event by ID, INCLUDING ALL DEPENDENT ITEMS.
	 * @param $eventId int
	 */
	function deleteEventById($eventId) {
		$eventSettingsDao = &DAORegistry::getDAO('EventSettingsDAO');
		$eventSettingsDao->deleteSettingsByEvent($eventId);

		$trackDao = &DAORegistry::getDAO('TrackDAO');
		$trackDao->deleteTracksByEvent($eventId);

		$notificationStatusDao = &DAORegistry::getDAO('NotificationStatusDAO');
		$notificationStatusDao->deleteNotificationStatusByEvent($eventId);

		$emailTemplateDao = &DAORegistry::getDAO('EmailTemplateDAO');
		$emailTemplateDao->deleteEmailTemplatesByEvent($eventId);

		$registrationDao = &DAORegistry::getDAO('RegistrationDAO');
		$registrationDao->deleteRegistrationsByEvent($eventId);

		$paperDao = &DAORegistry::getDAO('PaperDAO');
		$paperDao->deletePapersByEventId($eventId);

		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$roleDao->deleteRoleByEventId($eventId);

		$groupDao = &DAORegistry::getDAO('GroupDAO');
		$groupDao->deleteGroupsByEventId($eventId);

		$announcementDao = &DAORegistry::getDAO('AnnouncementDAO');
		$announcementDao->deleteAnnouncementsByEvent($eventId);

		return $this->update(
			'DELETE FROM events WHERE event_id = ?', $eventId
		);
	}
	
	/**
	 * Retrieve all events.
	 * @return DAOResultFactory containing matching events
	 */
	function &getEvents($rangeInfo = null) {
		$result = &$this->retrieveRange(
			'SELECT * FROM events ORDER BY seq',
			false, $rangeInfo
		);

		$returner = &new DAOResultFactory($result, $this, '_returnEventFromRow');
		return $returner;
	}
	
	/**
	 * Retrieve all enabled events
	 * @param conferenceId optional conference ID
	 * @return array Events ordered by sequence
	 */
	 function &getEnabledEvents($conferenceId = null) 
	 {
		$result = &$this->retrieve('
			SELECT i.* FROM events i
				LEFT JOIN conferences c ON (i.conference_id = c.conference_id)
			WHERE i.enabled=1
				AND c.enabled = 1'
				. ($conferenceId?' AND i.conference_id = ?':'')
			. ' ORDER BY c.seq, i.seq',
			$conferenceId===null?-1:$conferenceId);
		
		$resultFactory = &new DAOResultFactory($result, $this, '_returnEventFromRow');
		return $resultFactory;
	}
	
	/**
	 * Retrieve the IDs and titles of all events in an associative array.
	 * @return array
	 */
	function &getEventTitles() {
		$events = array();
		
		$result = &$this->retrieve(
			'SELECT event_id, title FROM events ORDER BY seq'
		);
		
		while (!$result->EOF) {
			$eventId = $result->fields[0];
			$events[$eventId] = $result->fields[1];
			$result->moveNext();
		}
		$result->Close();
		unset($result);
	
		return $events;
	}
	
	/**
	* Retrieve enabled event IDs and titles in an associative array
	* @return array
	*/
	function &getEnabledEventTitles() {
		$events = array();
		
		$result = &$this->retrieve('
			SELECT i.event_id, i.title FROM events i
				LEFT JOIN conferences c ON (i.conference_id = c.conference_id)
			WHERE i.enabled=1
				AND c.enabled = 1
			ORDER BY seq'
		);
		
		while (!$result->EOF) {
			$eventId = $result->fields[0];
			$events[$eventId] = $result->fields[1];
			$result->moveNext();
		}
		$result->Close();
		unset($result);
	
		return $events;
	}
	
	/**
	 * Check if a event exists with a specified path.
	 * @param $path the path of the event
	 * @return boolean
	 */
	function eventExistsByPath($path) {
		$result = &$this->retrieve(
			'SELECT COUNT(*) FROM events WHERE path = ?', $path
		);
		$returner = isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}
	
	/**
	 * Sequentially renumber events in their sequence order.
	 */
	function resequenceEvents() {
		$result = &$this->retrieve(
			'SELECT event_id FROM events ORDER BY seq'
		);
		
		for ($i=1; !$result->EOF; $i++) {
			list($eventId) = $result->fields;
			$this->update(
				'UPDATE events SET seq = ? WHERE event_id = ?',
				array(
					$i,
					$eventId
				)
			);
			
			$result->moveNext();
		}

		$result->close();
		unset($result);
	}
	
	/**
	 * Get the ID of the last inserted event.
	 * @return int
	 */
	function getInsertEventId() {
		return $this->getInsertId('events', 'event_id');
	}
	
	/**
	 * Retrieve most recent enabled event of a given conference
	 * @return array Events ordered by sequence
	 */
	 function &getCurrentEvents($conferenceId) 
	 {
		$result = &$this->retrieve('
			SELECT i.* FROM events i
				LEFT JOIN conferences c ON (i.conference_id = c.conference_id)
			WHERE i.enabled=1
				AND c.enabled = 1
				AND i.conference_id = ?
				AND i.start_date < NOW()
				AND i.end_date > NOW()
			ORDER BY c.seq, i.seq',
			$conferenceId);
		
		$resultFactory = &new DAOResultFactory($result, $this, '_returnEventFromRow');
		return $resultFactory;
	}

	/**
	 * Check if one or more archived events exist for a conference.
	 * @param $conferenceId the conference owning the event
	 * @return boolean
	 */
	function archivedEventsExist($conferenceId) {
		$result = &$this->retrieve(
			'SELECT COUNT(*) FROM events WHERE conference_id = ? AND end_date < now()', $conferenceId
		);
		$returner = isset($result->fields[0]) && $result->fields[0] >= 1 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Check if one or more archived events exist for a conference.
	 * @param $conferenceId the conference owning the event
	 * @return boolean
	 */
	function currentEventsExist($conferenceId) {
		$result = &$this->retrieve(
			'SELECT COUNT(*) FROM events WHERE conference_id = ? AND start_date < now() AND end_date > now()', $conferenceId
		);
		$returner = isset($result->fields[0]) && $result->fields[0] >= 1 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}
}

?>
