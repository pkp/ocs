<?php

/**
 * TrackDAO.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
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
	function &getTrackByAbbrev($trackAbbrev, $schedConfId) {
		$result = &$this->retrieve(
			'SELECT * FROM tracks WHERE abbrev = ? AND sched_conf_id = ?',
			array($trackAbbrev, $schedConfId)
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
	function &getTrackByTitle($trackTitle, $schedConfId) {
		$result = &$this->retrieve(
			'SELECT * FROM tracks WHERE (title = ? OR title_alt1 = ? OR title_alt2 = ?) AND sched_conf_id = ?',
			array($trackTitle, $trackTitle, $trackTitle, $schedConfId)
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
	function &getTrackByTitleAndAbbrev($trackTitle, $trackAbbrev, $schedConfId) {
		$result = &$this->retrieve(
			'SELECT * FROM tracks WHERE (title = ? OR title_alt1 = ? OR title_alt2 = ?) AND (abbrev = ? OR abbrev_alt1 = ? OR abbrev_alt2 = ?) AND sched_conf_id = ?',
			array($trackTitle, $trackTitle, $trackTitle, $trackAbbrev, $trackAbbrev, $trackAbbrev, $schedConfId)
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
		$track->setSchedConfId($row['sched_conf_id']);
		$track->setTitle($row['title']);
		$track->setTitleAlt1($row['title_alt1']);
		$track->setTitleAlt2($row['title_alt2']);
		$track->setAbbrev($row['abbrev']);
		$track->setAbbrevAlt1($row['abbrev_alt1']);
		$track->setAbbrevAlt2($row['abbrev_alt2']);
		$track->setSequence($row['seq']);
		$track->setMetaIndexed($row['meta_indexed']);
		$track->setMetaReviewed($row['meta_reviewed']);
		$track->setIdentifyType($row['identify_type']);
		$track->setDirectorRestricted($row['director_restricted']);
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
				(sched_conf_id, title, title_alt1, title_alt2, abbrev, abbrev_alt1, abbrev_alt2, seq, meta_reviewed, meta_indexed, identify_type, policy, director_restricted)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				$track->getSchedConfId(),
				$track->getTitle(),
				$track->getTitleAlt1(),
				$track->getTitleAlt2(),
				$track->getAbbrev(),
				$track->getAbbrevAlt1(),
				$track->getAbbrevAlt2(),
				$track->getSequence() == null ? 0 : $track->getSequence(),
				$track->getMetaReviewed() ? 1 : 0,
				$track->getMetaIndexed() ? 1 : 0,
				$track->getIdentifyType(),
				$track->getPolicy(),
				$track->getDirectorRestricted() ? 1 : 0
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
					meta_reviewed = ?,
					meta_indexed = ?,
					identify_type = ?,
					policy = ?,
					director_restricted = ?
				WHERE track_id = ?',
			array(
				$track->getTitle(),
				$track->getTitleAlt1(),
				$track->getTitleAlt2(),
				$track->getAbbrev(),
				$track->getAbbrevAlt1(),
				$track->getAbbrevAlt2(),
				$track->getSequence(),
				$track->getMetaReviewed(),
				$track->getMetaIndexed(),
				$track->getIdentifyType(),
				$track->getPolicy(),
				$track->getDirectorRestricted(),
				$track->getTrackId()
			)
		);
	}
	
	/**
	 * Delete a track.
	 * @param $track Track
	 */
	function deleteTrack(&$track) {
		return $this->deleteTrackById($track->getTrackId(), $track->getSchedConfId());
	}
	
	/**
	 * Delete a track by ID.
	 * @param $trackId int
	 * @param $schedConfId int optional
	 */
	function deleteTrackById($trackId, $schedConfId = null) {
		$trackDirectorsDao = &DAORegistry::getDAO('TrackDirectorsDAO');
		$trackDirectorsDao->deleteDirectorsByTrackId($trackId, $schedConfId);

		// Remove papers from this track
		$paperDao = &DAORegistry::getDAO('PaperDAO');
		$paperDao->removePapersFromTrack($trackId);

		// Delete published paper entries from this track -- they must
		// be re-published.
		$publishedPaperDao = &DAORegistry::getDAO('PublishedPaperDAO');
		$publishedPaperDao->deletePublishedPapersByTrackId($trackId);

		if (isset($schedConfId)) {
			return $this->update(
				'DELETE FROM tracks WHERE track_id = ? AND sched_conf_id = ?', array($trackId, $schedConfId)
			);
		
		} else {
			return $this->update(
				'DELETE FROM tracks WHERE track_id = ?', $trackId
			);
		}
	}
	
	/**
	 * Delete tracks by sched conf ID
	 * NOTE: This does not delete dependent entries EXCEPT from track_directors. It is intended
	 * to be called only when deleting a scheduled conference.
	 * @param $schedConfId int
	 */
	function deleteTracksBySchedConf($schedConfId) {
		$trackDirectorsDao = &DAORegistry::getDAO('TrackDirectorsDAO');
		$trackDirectorsDao->deleteDirectorsBySchedConfId($schedConfId);

		return $this->update(
			'DELETE FROM tracks WHERE sched_conf_id = ?', $schedConfId
		);
	}

	/**
	 * Retrieve an array associating all track director IDs with 
	 * arrays containing the tracks they edit.
	 * @return array directorId => array(tracks they edit)
	 */
	function &getDirectorTracks($schedConfId) {
		$returner = array();
		
		$result = &$this->retrieve(
			'SELECT s.*, se.user_id AS director_id FROM track_directors se, tracks s WHERE se.track_id = s.track_id AND s.sched_conf_id = se.sched_conf_id AND s.sched_conf_id = ?',
			$schedConfId
		);
		
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$track = &$this->_returnTrackFromRow($row);
			if (!isset($returner[$row['director_id']])) {
				$returner[$row['director_id']] = array($track);
			} else {
				$returner[$row['director_id']][] = $track;
			}
			$result->moveNext();
		}

		$result->Close();
		unset($result);
	
		return $returner;
	}

	/**
	 * Retrieve all tracks in which papers are currently published in
	 * the given scheduled conference.
	 * @return array
	 */
	function &getTracksBySchedConfId($schedConfId) {
		$returner = array();
		
		$result = &$this->retrieve(
			'SELECT DISTINCT s.*,
				COALESCE(o.seq, s.seq) AS track_seq
			FROM tracks s, papers a
			LEFT JOIN sched_confs i ON (a.sched_conf_id = i.sched_conf_id) AND (i.sched_conf_id = ?)
			LEFT JOIN custom_track_orders o ON (a.track_id = o.track_id AND o.sched_conf_id = i.sched_conf_id)
			WHERE s.track_id = a.track_id ORDER BY track_seq',
			array($schedConfId)
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
	 * Retrieve all tracks for a scheduled conference.
	 * @return DAOResultFactory containing Tracks ordered by sequence
	 */
	function &getSchedConfTracks($schedConfId, $rangeInfo = null) {
		$result = &$this->retrieveRange(
			'SELECT * FROM tracks WHERE sched_conf_id = ? ORDER BY seq',
			$schedConfId, $rangeInfo
		);
		
		$returner = &new DAOResultFactory($result, $this, '_returnTrackFromRow');
		return $returner;
	}
	
	/**
	 * Retrieve the IDs and titles of the tracks for a scheduled conference in an associative array.
	 * @return array
	 */
	function &getTrackTitles($schedConfId, $submittableOnly = false) {
		$schedConfDao = DAORegistry::getDAO('SchedConfDAO');
		$schedConf = $schedConfDao->getSchedConf($schedConfId);

		$tracks = array();
		
		$result = &$this->retrieve(
			($submittableOnly?
			'SELECT track_id, title, title_alt1, title_alt2 FROM tracks WHERE sched_conf_id = ? AND director_restricted = 0 ORDER BY seq':
			'SELECT track_id, title, title_alt1, title_alt2 FROM tracks WHERE sched_conf_id = ? ORDER BY seq'),
			$schedConfId
		);

		$localeNumber = Locale::isAlternateConferenceLocale($schedConf->getConferenceId());

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
	 * @param $schedConfId int
	 * @return boolean
	 */
	function trackExists($trackId, $schedConfId) {
		$result = &$this->retrieve(
			'SELECT COUNT(*) FROM tracks WHERE track_id = ? AND sched_conf_id = ?',
			array($trackId, $schedConfId)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Sequentially renumber tracks in their sequence order.
	 * @param $schedConfId int
	 */
	function resequenceTracks($schedConfId) {
		$result = &$this->retrieve(
			'SELECT track_id FROM tracks WHERE sched_conf_id = ? ORDER BY seq',
			$schedConfId
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
	 * Delete the custom ordering of an scheduled conference's tracks.
	 * @param $schedConfId int
	 */
	function deleteCustomTrackOrdering($schedConfId) {
		return $this->update(
			'DELETE FROM custom_track_orders WHERE sched_conf_id = ?', $schedConfId
		);
	}

	/**
	 * Sequentially renumber custom track orderings in their sequence order.
	 * @param $schedConfId int
	 */
	function resequenceCustomTrackOrders($schedConfId) {
		$result = &$this->retrieve(
			'SELECT track_id FROM custom_track_orders WHERE sched_conf_id = ? ORDER BY seq',
			$schedConfId
		);
		
		for ($i=1; !$result->EOF; $i++) {
			list($trackId) = $result->fields;
			$this->update(
				'UPDATE custom_track_orders SET seq = ? WHERE track_id = ? AND sched_conf_id = ?',
				array(
					$i,
					$trackId,
					$schedConfId
				)
			);
			
			$result->moveNext();
		}
		
		$result->close();
		unset($result);
	}
	
	/**
	 * Check if a scheduled conference has custom track ordering.
	 * @param $schedConfId int
	 * @return boolean
	 */
	function customTrackOrderingExists($schedConfId) {
		$result = &$this->retrieve(
			'SELECT COUNT(*) FROM custom_track_orders WHERE sched_conf_id = ?',
			$schedConfId
		);
		$returner = isset($result->fields[0]) && $result->fields[0] == 0 ? false : true;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Get the custom track order of a track.
	 * @param $schedConfId int
	 * @param $trackId int
	 * @return int
	 */
	function getCustomTrackOrder($schedConfId, $trackId) {
		$result = &$this->retrieve(
			'SELECT seq FROM custom_track_orders WHERE sched_conf_id = ? AND track_id = ?',
			array($schedConfId, $trackId)
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
	 * Import the current track orders into the specified scheduled conference as custom
	 * scheduled conference orderings.
	 * @param $schedConfId int
	 */
	function setDefaultCustomTrackOrders($schedConfId) {
		$result = &$this->retrieve(
			'SELECT s.track_id FROM tracks s, sched_confs i WHERE i.sched_conf_id = s.sched_conf_id AND i.sched_conf_id = ? ORDER BY seq',
			$schedConfId
		);
		
		for ($i=1; !$result->EOF; $i++) {
			list($trackId) = $result->fields;
			$this->_insertCustomTrackOrder($schedConfId, $trackId, $i);
			$result->moveNext();
		}
		
		$result->close();
		unset($result);
	}

	/**
	 * INTERNAL USE ONLY: Insert a custom track ordering
	 * @param $schedConfId int
	 * @param $trackId int
	 * @param $seq int
	 */
	function _insertCustomTrackOrder($schedConfId, $trackId, $seq) {
		$this->update(
			'INSERT INTO custom_track_orders (track_id, sched_conf_id, seq) VALUES (?, ?, ?)',
			array(
				$trackId,
				$schedConfId,
				$seq
			)
		);
	}

	/**
	 * Move a custom scheduled conference ordering up or down, resequencing as necessary.
	 * @param $schedConfId int
	 * @param $trackId int
	 * @param $newPos int The new position (0-based) of this track
	 * @param $up boolean Whether we're moving the track up or down
	 */
	function moveCustomTrackOrder($schedConfId, $trackId, $newPos, $up) {
		$this->update(
			'UPDATE custom_track_orders SET seq = ? ' . ($up?'-':'+') . ' 0.5 WHERE sched_conf_id = ? AND track_id = ?',
			array($newPos, $schedConfId, $trackId)
		);
		$this->resequenceCustomTrackOrders($schedConfId);
	}
}

?>
