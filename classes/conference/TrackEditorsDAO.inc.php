<?php

/**
 * TrackEditorsDAO.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package conference
 *
 * Class for DAO relating tracks to editors.
 *
 * $Id$
 */

class TrackEditorsDAO extends DAO {

	/**
	 * Constructor.
	 */
	function TrackEditorsDAO() {
		parent::DAO();
	}
	
	/**
	 * Insert a new track editor.
	 * @param $eventId int
	 * @param $trackId int
	 * @param $userId int
	 */
	function insertEditor($eventId, $trackId, $userId) {
		return $this->update(
			'INSERT INTO track_editors
				(event_id, track_id, user_id)
				VALUES
				(?, ?, ?)',
			array(
				$eventId,
				$trackId,
				$userId
			)
		);
	}
	
	/**
	 * Delete a track editor.
	 * @param $eventId int
	 * @param $trackId int
	 * @param $userId int
	 */
	function deleteEditor($eventId, $trackId, $userId) {
		return $this->update(
			'DELETE FROM track_editors WHERE event_id = ? AND track_id = ? AND user_id = ?',
			array(
				$eventId,
				$trackId,
				$userId
			)
		);
	}
	
	/**
	 * Retrieve a list of tracks assigned to the specified user.
	 * @param $eventId int
	 * @param $userId int
	 * @return array matching Tracks
	 */
	function &getTracksByUserId($eventId, $userId) {
		$tracks = array();
		
		$trackDao = &DAORegistry::getDAO('TrackDAO');
				
		$result = &$this->retrieve(
			'SELECT s.* FROM tracks AS s, track_editors AS e WHERE s.track_id = e.track_id AND s.event_id = ? AND e.user_id = ?',
			array($eventId, $userId)
		);
		
		while (!$result->EOF) {
			$tracks[] = &$trackDao->_returnTrackFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}

		$result->Close();
		unset($result);
	
		return $tracks;
	}
	
	/**
	 * Retrieve a list of all track editors assigned to the specified track.
	 * @param $eventId int
	 * @param $trackId int
	 * @return array matching Users
	 */
	function &getEditorsByTrackId($eventId, $trackId) {
		$users = array();
		
		$userDao = &DAORegistry::getDAO('UserDAO');
				
		$result = &$this->retrieve(
			'SELECT u.* FROM users AS u, track_editors AS e WHERE u.user_id = e.user_id AND e.event_id = ? AND e.track_id = ? ORDER BY last_name, first_name',
			array($eventId, $trackId)
		);
		
		while (!$result->EOF) {
			$users[] = &$userDao->_returnUserFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}

		$result->Close();
		unset($result);
	
		return $users;
	}
	
	/**
	 * Retrieve a list of all track editors not assigned to the specified track.
	 * @param $eventId int
	 * @param $trackId int
	 * @return array matching Users
	 */
	function &getEditorsNotInTrack($eventId, $trackId) {
		$users = array();
		
		$userDao = &DAORegistry::getDAO('UserDAO');
				
		$result = &$this->retrieve(
			'SELECT u.* FROM users AS u NATURAL JOIN roles r LEFT JOIN track_editors AS e ON e.user_id = u.user_id AND e.event_id = r.event_id AND e.track_id = ? WHERE r.event_id = ? AND r.role_id = ? AND e.track_id IS NULL ORDER BY last_name, first_name',
			array($trackId, $eventId, ROLE_ID_TRACK_EDITOR)
		);
		
		while (!$result->EOF) {
			$users[] = &$userDao->_returnUserFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}

		$result->Close();
		unset($result);
	
		return $users;
	}
	
	/**
	 * Delete all track editors for a specified track in a event.
	 * @param $trackId int
	 * @param $eventId int
	 */
	function deleteEditorsByTrackId($trackId, $eventId = null) {
		if (isset($eventId)) return $this->update(
			'DELETE FROM track_editors WHERE event_id = ? AND track_id = ?',
			array($eventId, $trackId)
		);
		else return $this->update(
			'DELETE FROM track_editors WHERE track_id = ?',
			$trackId
		);
	}
	
	/**
	 * Delete all track editors for a specified event.
	 * @param $eventId int
	 */
	function deleteEditorsByEventId($eventId) {
		return $this->update(
			'DELETE FROM track_editors WHERE event_id = ?', $eventId
		);
	}
	
	/**
	 * Delete all track assignments for the specified user.
	 * @param $userId int
	 * @param $eventId int optional, include assignments only in this event
	 * @param $trackId int optional, include only this track
	 */
	function deleteEditorsByUserId($userId, $eventId  = null, $trackId = null) {
		return $this->update(
			'DELETE FROM track_editors WHERE user_id = ?' . (isset($eventId) ? ' AND event_id = ?' : '') . (isset($trackId) ? ' AND track_id = ?' : ''),
			isset($eventId) && isset($trackId) ? array($userId, $eventId, $trackId)
			: (isset($eventId) ? array($userId, $eventId)
			: (isset($trackId) ? array($userId, $trackId) : $userId))
		);
	}
	
	/**
	 * Check if a user is assigned to a specified track.
	 * @param $eventId int
	 * @param $trackId int
	 * @param $userId int
	 * @return boolean
	 */
	function editorExists($eventId, $trackId, $userId) {
		$result = &$this->retrieve(
			'SELECT COUNT(*) FROM track_editors WHERE event_id = ? AND track_id = ? AND user_id = ?', array($eventId, $trackId, $userId)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}
	
}

?>
