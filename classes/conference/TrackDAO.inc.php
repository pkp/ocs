<?php

/**
 * TrackDAO.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package conference
 *
 * Class for track DAO.
 * Operations for retrieving and modifying Track objects.
 *
 * $Id$
 */

import ('conference.Track');

class TrackDAO extends DAO {

	/**
	 * Constructor.
	 */
	function TrackDAO() {
		parent::DAO();
	}
	
	/**
	 * Retrieve a track by ID.
	 * @param $trackId int
	 * @return Track
	 */
	function &getTrack($trackId) {
		$result = &$this->retrieve(
			'SELECT * FROM tracks WHERE track_id = ?', $trackId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnTrackFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}
	
	/**
	 * Retrieve a track by abbreviation.
	 * @param $trackAbbrev string
	 * @return Track
	 */
	function &getTrackByAbbrev($trackAbbrev, $eventId) {
		$result = &$this->retrieve(
			'SELECT * FROM tracks WHERE abbrev = ? AND event_id = ?',
			array($trackAbbrev, $eventId)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnTrackFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}
	
	/**
	 * Retrieve a track by title.
	 * @param $trackTitle string
	 * @return Track
	 */
	function &getTrackByTitle($trackTitle, $eventId) {
		$result = &$this->retrieve(
			'SELECT * FROM tracks WHERE (title = ? OR title_alt1 = ? OR title_alt2 = ?) AND event_id = ?',
			array($trackTitle, $trackTitle, $trackTitle, $eventId)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnTrackFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}
	
	/**
	 * Retrieve a track by title and abbrev.
	 * @param $trackTitle string
	 * @param $trackAbbrev string
	 * @return Track
	 */
	function &getTrackByTitleAndAbbrev($trackTitle, $trackAbbrev, $eventId) {
		$result = &$this->retrieve(
			'SELECT * FROM tracks WHERE (title = ? OR title_alt1 = ? OR title_alt2 = ?) AND (abbrev = ? OR abbrev_alt1 = ? OR abbrev_alt2 = ?) AND event_id = ?',
			array($trackTitle, $trackTitle, $trackTitle, $trackAbbrev, $trackAbbrev, $trackAbbrev, $eventId)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnTrackFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}
	
	/**
	 * Internal function to return a Track object from a row.
	 * @param $row array
	 * @return Track
	 */
	function &_returnTrackFromRow(&$row) {
		$track = &new Track();
		$track->setTrackId($row['track_id']);
		$track->setEventId($row['event_id']);
		$track->setTitle($row['title']);
		$track->setTitleAlt1($row['title_alt1']);
		$track->setTitleAlt2($row['title_alt2']);
		$track->setAbbrev($row['abbrev']);
		$track->setAbbrevAlt1($row['abbrev_alt1']);
		$track->setAbbrevAlt2($row['abbrev_alt2']);
		$track->setSequence($row['seq']);
		$track->setMetaIndexed($row['meta_indexed']);
		$track->setIdentifyType($row['identify_type']);
		$track->setEditorRestricted($row['editor_restricted']);
		$track->setPolicy($row['policy']);
		
		HookRegistry::call('TrackDAO::_returnTrackFromRow', array(&$track, &$row));

		return $track;
	}

	/**
	 * Insert a new track.
	 * @param $track Track
	 */	
	function insertTrack(&$track) {
		$this->update(
			'INSERT INTO tracks
				(event_id, title, title_alt1, title_alt2, abbrev, abbrev_alt1, abbrev_alt2, seq, meta_indexed, identify_type, policy, editor_restricted)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				$track->getEventId(),
				$track->getTitle(),
				$track->getTitleAlt1(),
				$track->getTitleAlt2(),
				$track->getAbbrev(),
				$track->getAbbrevAlt1(),
				$track->getAbbrevAlt2(),
				$track->getSequence() == null ? 0 : $track->getSequence(),
				$track->getMetaIndexed() ? 1 : 0,
				$track->getIdentifyType(),
				$track->getPolicy(),
				$track->getEditorRestricted() ? 1 : 0
			)
		);
		
		$track->setTrackId($this->getInsertTrackId());
		return $track->getTrackId();
	}
	
	/**
	 * Update an existing track.
	 * @param $track Track
	 */
	function updateTrack(&$track) {
		return $this->update(
			'UPDATE tracks
				SET
					title = ?,
					title_alt1 = ?,
					title_alt2 = ?,
					abbrev = ?,
					abbrev_alt1 = ?,
					abbrev_alt2 = ?,
					seq = ?,
					meta_indexed = ?,
					identify_type = ?,
					policy = ?,
					editor_restricted = ?
				WHERE track_id = ?',
			array(
				$track->getTitle(),
				$track->getTitleAlt1(),
				$track->getTitleAlt2(),
				$track->getAbbrev(),
				$track->getAbbrevAlt1(),
				$track->getAbbrevAlt2(),
				$track->getSequence(),
				$track->getMetaIndexed(),
				$track->getIdentifyType(),
				$track->getPolicy(),
				$track->getEditorRestricted(),
				$track->getTrackId()
			)
		);
	}
	
	/**
	 * Delete a track.
	 * @param $track Track
	 */
	function deleteTrack(&$track) {
		return $this->deleteTrackById($track->getTrackId(), $track->getEventId());
	}
	
	/**
	 * Delete a track by ID.
	 * @param $trackId int
	 * @param $eventId int optional
	 */
	function deleteTrackById($trackId, $eventId = null) {
		$trackEditorsDao = &DAORegistry::getDAO('TrackEditorsDAO');
		$trackEditorsDao->deleteEditorsByTrackId($trackId, $eventId);

		// Remove papers from this track
		$paperDao = &DAORegistry::getDAO('PaperDAO');
		$paperDao->removePapersFromTrack($trackId);

		// Delete published paper entries from this track -- they must
		// be re-published.
		$publishedPaperDao = &DAORegistry::getDAO('PublishedPaperDAO');
		$publishedPaperDao->deletePublishedPapersByTrackId($trackId);

		if (isset($eventId)) {
			return $this->update(
				'DELETE FROM tracks WHERE track_id = ? AND event_id = ?', array($trackId, $eventId)
			);
		
		} else {
			return $this->update(
				'DELETE FROM tracks WHERE track_id = ?', $trackId
			);
		}
	}
	
	/**
	 * Delete tracks by event ID
	 * NOTE: This does not delete dependent entries EXCEPT from track_editors. It is intended
	 * to be called only when deleting a event.
	 * @param $eventId int
	 */
	function deleteTracksByEvent($eventId) {
		$trackEditorsDao = &DAORegistry::getDAO('TrackEditorsDAO');
		$trackEditorsDao->deleteEditorsByEventId($eventId);

		return $this->update(
			'DELETE FROM tracks WHERE event_id = ?', $eventId
		);
	}

	/**
	 * Retrieve an array associating all track editor IDs with 
	 * arrays containing the tracks they edit.
	 * @return array editorId => array(tracks they edit)
	 */
	function &getEditorTracks($eventId) {
		$returner = array();
		
		$result = &$this->retrieve(
			'SELECT s.*, se.user_id AS editor_id FROM track_editors se, tracks s WHERE se.track_id = s.track_id AND s.event_id = se.event_id AND s.event_id = ?',
			$eventId
		);
		
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$track = &$this->_returnTrackFromRow($row);
			if (!isset($returner[$row['editor_id']])) {
				$returner[$row['editor_id']] = array($track);
			} else {
				$returner[$row['editor_id']][] = $track;
			}
			$result->moveNext();
		}

		$result->Close();
		unset($result);
	
		return $returner;
	}

	/**
	 * Retrieve all tracks in which papers are currently published in
	 * the given event.
	 * @return array
	 */
	function &getTracksByEventId($eventId) {
		$returner = array();
		
		$result = &$this->retrieve(
			'SELECT DISTINCT s.*,
				COALESCE(o.seq, s.seq) AS track_seq
			FROM tracks s, papers a
			LEFT JOIN events i ON (a.event_id = i.event_id) AND (i.event_id = ?)
			LEFT JOIN custom_track_orders o ON (a.track_id = o.track_id AND o.event_id = i.event_id)
			WHERE s.track_id = a.track_id ORDER BY track_seq',
			array($eventId)
		);
		
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$returner[] = &$this->_returnTrackFromRow($row);
			$result->moveNext();
		}

		$result->Close();
		unset($result);
	
		return $returner;
	}
	
	/**
	 * Retrieve all tracks for a event.
	 * @return DAOResultFactory containing Tracks ordered by sequence
	 */
	function &getEventTracks($eventId, $rangeInfo = null) {
		$result = &$this->retrieveRange(
			'SELECT * FROM tracks WHERE event_id = ? ORDER BY seq',
			$eventId, $rangeInfo
		);
		
		$returner = &new DAOResultFactory($result, $this, '_returnTrackFromRow');
		return $returner;
	}
	
	/**
	 * Retrieve the IDs and titles of the tracks for a event in an associative array.
	 * @return array
	 */
	function &getTrackTitles($eventId, $submittableOnly = false) {
		$eventDao = DAORegistry::getDAO('EventDAO');
		$event = $eventDao->getEvent($eventId);

		$tracks = array();
		
		$result = &$this->retrieve(
			($submittableOnly?
			'SELECT track_id, title, title_alt1, title_alt2 FROM tracks WHERE event_id = ? AND editor_restricted = 0 ORDER BY seq':
			'SELECT track_id, title, title_alt1, title_alt2 FROM tracks WHERE event_id = ? ORDER BY seq'),
			$eventId
		);

		$localeNumber = Locale::isAlternateConferenceLocale($event->getConferenceId());

		while (!$result->EOF) {
			$trackTitle = $result->fields[$localeNumber + 1];
			if (!isset($trackTitle)) $trackTitle = $result->fields[1];
			$tracks[$result->fields[0]] = $trackTitle;
			$result->moveNext();
		}

		$result->Close();
		unset($result);
	
		return $tracks;
	}
	
	/**
	 * Check if a track exists with the specified ID.
	 * @param $trackId int
	 * @param $eventId int
	 * @return boolean
	 */
	function trackExists($trackId, $eventId) {
		$result = &$this->retrieve(
			'SELECT COUNT(*) FROM tracks WHERE track_id = ? AND event_id = ?',
			array($trackId, $eventId)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Sequentially renumber tracks in their sequence order.
	 * @param $eventId int
	 */
	function resequenceTracks($eventId) {
		$result = &$this->retrieve(
			'SELECT track_id FROM tracks WHERE event_id = ? ORDER BY seq',
			$eventId
		);
		
		for ($i=1; !$result->EOF; $i++) {
			list($trackId) = $result->fields;
			$this->update(
				'UPDATE tracks SET seq = ? WHERE track_id = ?',
				array(
					$i,
					$trackId
				)
			);
			
			$result->moveNext();
		}
		
		$result->close();
		unset($result);
	}
	
	/**
	 * Get the ID of the last inserted track.
	 * @return int
	 */
	function getInsertTrackId() {
		return $this->getInsertId('tracks', 'track_id');
	}

	/**
	 * Delete the custom ordering of an event's tracks.
	 * @param $eventId int
	 */
	function deleteCustomTrackOrdering($eventId) {
		return $this->update(
			'DELETE FROM custom_track_orders WHERE event_id = ?', $eventId
		);
	}

	/**
	 * Sequentially renumber custom track orderings in their sequence order.
	 * @param $eventId int
	 */
	function resequenceCustomTrackOrders($eventId) {
		$result = &$this->retrieve(
			'SELECT track_id FROM custom_track_orders WHERE event_id = ? ORDER BY seq',
			$eventId
		);
		
		for ($i=1; !$result->EOF; $i++) {
			list($trackId) = $result->fields;
			$this->update(
				'UPDATE custom_track_orders SET seq = ? WHERE track_id = ? AND event_id = ?',
				array(
					$i,
					$trackId,
					$eventId
				)
			);
			
			$result->moveNext();
		}
		
		$result->close();
		unset($result);
	}
	
	/**
	 * Check if an event has custom track ordering.
	 * @param $eventId int
	 * @return boolean
	 */
	function customTrackOrderingExists($eventId) {
		$result = &$this->retrieve(
			'SELECT COUNT(*) FROM custom_track_orders WHERE event_id = ?',
			$eventId
		);
		$returner = isset($result->fields[0]) && $result->fields[0] == 0 ? false : true;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Get the custom track order of a track.
	 * @param $eventId int
	 * @param $trackId int
	 * @return int
	 */
	function getCustomTrackOrder($eventId, $trackId) {
		$result = &$this->retrieve(
			'SELECT seq FROM custom_track_orders WHERE event_id = ? AND track_id = ?',
			array($eventId, $trackId)
		);
		
		$returner = null;
		if (!$result->EOF) {
			list($returner) = $result->fields;
		}
		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Import the current track orders into the specified event as custom
	 * event orderings.
	 * @param $eventId int
	 */
	function setDefaultCustomTrackOrders($eventId) {
		$result = &$this->retrieve(
			'SELECT s.track_id FROM tracks s, events i WHERE i.event_id = s.event_id AND i.event_id = ? ORDER BY seq',
			$eventId
		);
		
		for ($i=1; !$result->EOF; $i++) {
			list($trackId) = $result->fields;
			$this->_insertCustomTrackOrder($eventId, $trackId, $i);
			$result->moveNext();
		}
		
		$result->close();
		unset($result);
	}

	/**
	 * INTERNAL USE ONLY: Insert a custom track ordering
	 * @param $eventId int
	 * @param $trackId int
	 * @param $seq int
	 */
	function _insertCustomTrackOrder($eventId, $trackId, $seq) {
		$this->update(
			'INSERT INTO custom_track_orders (track_id, event_id, seq) VALUES (?, ?, ?)',
			array(
				$trackId,
				$eventId,
				$seq
			)
		);
	}

	/**
	 * Move a custom event ordering up or down, resequencing as necessary.
	 * @param $eventId int
	 * @param $trackId int
	 * @param $newPos int The new position (0-based) of this track
	 * @param $up boolean Whether we're moving the track up or down
	 */
	function moveCustomTrackOrder($eventId, $trackId, $newPos, $up) {
		$this->update(
			'UPDATE custom_track_orders SET seq = ? ' . ($up?'-':'+') . ' 0.5 WHERE event_id = ? AND track_id = ?',
			array($newPos, $eventId, $trackId)
		);
		$this->resequenceCustomTrackOrders($eventId);
	}
}

?>
