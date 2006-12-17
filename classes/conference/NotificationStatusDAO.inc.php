<?php

/**
 * NotificationStatusDAO.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package event
 *
 * Operations for retrieving and modifying users' event notification status.
 *
 * $Id$
 */

class NotificationStatusDAO extends DAO {
	function &getEventNotifications($userId) {
		$returner = array();
		
		$result = &$this->retrieve(
			'SELECT j.event_id AS event_id, n.event_id AS notification FROM events j LEFT JOIN notification_status n ON j.event_id = n.event_id AND n.user_id = ? ORDER BY j.seq',
			$userId
		);
		
		while (!$result->EOF) {
			$row = &$result->GetRowAssoc(false);
			$returner[$row['event_id']] = $row['notification'];
			$result->moveNext();
		}

		$result->Close();
		unset($result);
	
		return $returner;
	}
	
	/**
	 * Changes whether or not a user will receive email notifications about a given event.
	 * @param $eventId int
	 * @param $userId int
	 * @param $notificationStatus bool
	 */
	function setEventNotifications($eventId, $userId, $notificationStatus) {
		return $this->update(
			($notificationStatus?'INSERT INTO notification_status (user_id, event_id) VALUES (?, ?)':
			'DELETE FROM notification_status WHERE user_id = ? AND event_id = ?'),
			array($userId, $eventId)
		);
	}

	/**
	 * Delete notification status entries by event ID
	 * @param $eventId int
	 */
	function deleteNotificationStatusByEvent($eventId) {
		return $this->update(
			'DELETE FROM notification_status WHERE event_id = ?', $eventId
		);
	}

	/**
	 * Delete notification status entries by user ID
	 * @param $userId int
	 */
	function deleteNotificationStatusByUserId($userId) {
		return $this->update(
			'DELETE FROM notification_status WHERE user_id = ?', $userId
		);
	}

	/**
	 * Retrieve a list of users who wish to receive updates about the specified event.
	 * @param $eventId int
	 * @return DAOResultFactory matching Users
	 */
	function &getNotifiableUsersByEventId($eventId) {
		$userDao = &DAORegistry::getDAO('UserDAO');
				
		$result = &$this->retrieve(
			'SELECT u.* FROM users u, notification_status n WHERE u.user_id = n.user_id AND n.event_id = ?',
			$eventId
		);

		$returner = &new DAOResultFactory($result, $userDao, '_returnUserFromRow');
		return $returner;
	}

	/**
	 * Retrieve the number of users who wish to receive updates about the specified event.
	 * @param $eventId int
	 * @return int
	 */
	function getNotifiableUsersCount($eventId) {
		$userDao = &DAORegistry::getDAO('UserDAO');
				
		$result = &$this->retrieve(
			'SELECT count(*) FROM notification_status n WHERE n.event_id = ?',
			$eventId
		);

		$returner = $result->fields[0];

		$result->Close();
		unset($result);

		return $returner;
	}
}

?>
