<?php

/**
 * @file OAIDAO.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OAIDAO
 * @ingroup oai_ocs
 * @see OAI
 *
 * @brief DAO operations for the OCS OAI interface.
 */

//$Id$

import('oai.OAI');

class OAIDAO extends DAO {

 	/** @var $oai ConferenceOAI parent OAI object */
 	var $oai;

 	/** Helper DAOs */
 	var $conferenceDao;
 	var $trackDao;
	var $publishedPaperDao;
	var $paperGalleyDao;
 	var $authorDao;
 	var $suppFileDao;
 	var $conferenceSettingsDao;

	var $conferenceCache;
	var $schedConfCache;
	var $trackCache;

 	/**
	 * Constructor.
	 */
	function OAIDAO() {
		parent::DAO();
		$this->conferenceDao =& DAORegistry::getDAO('ConferenceDAO');
		$this->schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
		$this->trackDao =& DAORegistry::getDAO('TrackDAO');
		$this->publishedPaperDao =& DAORegistry::getDAO('PublishedPaperDAO');
		$this->paperGalleyDao =& DAORegistry::getDAO('PaperGalleyDAO');
		$this->authorDao =& DAORegistry::getDAO('AuthorDAO');
		$this->suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
		$this->conferenceSettingsDao =& DAORegistry::getDAO('ConferenceSettingsDAO');

		$this->conferenceCache = array();
		$this->schedConfCache = array();
		$this->trackCache = array();
	}

	/**
	 * Set parent OAI object.
	 * @param ConferenceOAI
	 */
	function setOAI(&$oai) {
		$this->oai = $oai;
	}

	//
	// Records
	//

	/**
	 * Return the *nix timestamp of the earliest published paper.
	 * @param $conferenceId int optional
	 * @return int
	 */
	function getEarliestDatestamp($conferenceId = null) {
		$result =& $this->retrieve(
			'SELECT	MIN(p.last_modified)
			FROM	papers p,
				published_papers pp,
				sched_confs sc,
				conferences c
			WHERE	p.paper_id = pp.paper_id AND
				p.sched_conf_id = sc.sched_conf_id AND
				sc.conference_id = c.conference_id AND
				c.enabled = 1'
			. (isset($conferenceId) ? ' AND c.conference_id = ?' : ''),

			isset($conferenceId) ? array((int) $conferenceId) : false
		);

		if (isset($result->fields[0])) {
			$timestamp = strtotime($this->datetimeFromDB($result->fields[0]));
		}
		if (!isset($timestamp) || $timestamp == -1) {
			$timestamp = 0;
		}

		$result->Close();
		unset($result);

		return $timestamp;
	}

	/**
	 * Check if an paper ID specifies a published paper.
	 * @param $paperId int
	 * @param $conferenceId int optional
	 * @return boolean
	 */
	function recordExists($paperId, $conferenceId = null) {
		$params = array((int) $paperId);
		if (isset($conferenceId)) $params[] = (int) $conferenceId;
		$result =& $this->retrieve(
			'SELECT COUNT(*)
			FROM	published_papers pp,
				papers p,
				sched_confs sc,
				conferences c
			WHERE	pp.paper_id = ? AND
				pp.paper_id = p.paper_id AND
				sc.sched_conf_id = p.sched_conf_id AND
				c.conference_id = sc.conference_id'
			. (isset($conferenceId) ? ' AND c.conference_id = ?' : ''),
			$params
		);

		$returner = $result->fields[0] == 1;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Return OAI record for specified paper.
	 * @param $paperId int
	 * @param $conferenceId int optional
	 * @return OAIRecord
	 */
	function &getRecord($paperId, $conferenceId = null) {
		$result =& $this->retrieve(
			'SELECT	pp.*, p.*,
				c.path AS conference_path,
				c.conference_id AS conference_id,
				s.path AS sched_conf_path
			FROM	published_papers pp, conferences c, sched_confs s, papers p
				LEFT JOIN tracks t ON t.track_id = p.track_id
			WHERE	pp.paper_id = p.paper_id AND
				c.conference_id = s.conference_id AND
				s.sched_conf_id = p.sched_conf_id AND
				c.enabled = 1 AND
				pp.paper_id = ?'
			. (isset($conferenceId) ? ' AND c.conference_id = ?' : ''),
			isset($conferenceId) ? array((int) $paperId, (int) $conferenceId) : array((int) $paperId)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$row =& $result->GetRowAssoc(false);
			$returner =& $this->_returnRecordFromRow($row);
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Return set of OAI records matching specified parameters.
	 * @param $conferenceId int
	 * @param $schedConfId int
	 * @param $trackId int
	 * @parma $from int timestamp
	 * @parma $until int timestamp
	 * @param $offset int
	 * @param $limit int
	 * @param $total int
	 * @return array OAIRecord
	 */
	function &getRecords($conferenceId, $schedConfId, $trackId, $from, $until, $offset, $limit, &$total) {
		$records = array();

		$params = array();
		if (isset($conferenceId)) {
			array_push($params, (int) $conferenceId);
		}
		if (isset($schedConfId)) {
			array_push($params, (int) $schedConfId);
		}
		if (isset($trackId)) {
			array_push($params, (int) $trackId);
		}
		$result =& $this->retrieve(
			'SELECT	pp.*, p.*,
				c.path AS conference_path,
				c.conference_id AS conference_id,
				s.path AS sched_conf_path
			FROM	published_papers pp,
				conferences c,
				sched_confs s,
				papers p
				LEFT JOIN tracks t ON t.track_id = p.track_id
			WHERE	pp.paper_id = p.paper_id AND
				p.sched_conf_id = s.sched_conf_id AND
				c.enabled = 1 AND
				s.conference_id = c.conference_id'
			. (isset($conferenceId) ? ' AND c.conference_id = ?' : '')
			. (isset($schedConfId) ? ' AND s.sched_conf_id = ?' : '')
			. (isset($trackId) ? ' AND p.track_id = ?' : '')
			. (isset($from) ? ' AND p.last_modified >= ' . $this->datetimeToDB($from) : '')
			. (isset($until) ? ' AND p.last_modified <= ' . $this->datetimeToDB($until) : ''),
			$params
		);

		$total = $result->RecordCount();

		$result->Move($offset);
		for ($count = 0; $count < $limit && !$result->EOF; $count++) {
			$row =& $result->GetRowAssoc(false);
			$records[] =& $this->_returnRecordFromRow($row);
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $records;
	}

	/**
	 * Return set of OAI identifiers matching specified parameters.
	 * @param $conferenceId int
	 * @param $schedConfId int
	 * @param $trackId int
	 * @parma $from int timestamp
	 * @parma $until int timestamp
	 * @param $offset int
	 * @param $limit int
	 * @param $total int
	 * @return array OAIIdentifier
	 */
	function &getIdentifiers($conferenceId, $schedConfId, $trackId, $from, $until, $offset, $limit, &$total) {
		$records = array();

		$params = array();
		if (isset($conferenceId)) {
			array_push($params, (int) $conferenceId);
		}
		if (isset($schedConfId)) {
			array_push($params, (int) $schedConfId);
		}
		if (isset($trackId)) {
			array_push($params, (int) $trackId);
		}
		$result =& $this->retrieve(
			'SELECT	pp.paper_id,
				p.last_modified,
				c.path AS conference_path,
				c.conference_id,
				s.path AS sched_conf_path,
				s.sched_conf_id,
				p.track_id
			FROM	published_papers pp,
				conferences c,
				sched_confs s,
				papers p
				LEFT JOIN tracks t ON t.track_id = p.track_id
			WHERE	pp.paper_id = p.paper_id AND
				p.sched_conf_id = s.sched_conf_id AND
				c.enabled = 1 AND
				s.conference_id = c.conference_id'
			. (isset($conferenceId) ? ' AND c.conference_id = ?' : '')
			. (isset($schedConfId) ? ' AND s.sched_conf_id = ?' : '')
			. (isset($trackId) ? ' AND p.track_id = ?' : '')
			. (isset($from) ? ' AND p.last_modified >= ' . $this->datetimeToDB($from) : '')
			. (isset($until) ? ' AND p.last_modified <= ' . $this->datetimeToDB($until) : ''),
			$params
		);

		$total = $result->RecordCount();

		$result->Move($offset);
		for ($count = 0; $count < $limit && !$result->EOF; $count++) {
			$row =& $result->GetRowAssoc(false);
			$records[] =& $this->_returnIdentifierFromRow($row);
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $records;
	}

	function stripAssocArray($values) {
		foreach (array_keys($values) as $key) {
			$values[$key] = strip_tags($values[$key]);
		}
		return $values;
	}
 
	/**
	 * Cached function to get a conference
	 * @param $conferenceId int
	 * @return object
	 */
	function &getConference($conferenceId) {
		if (!isset($this->conferenceCache[$conferenceId])) {
			$this->conferenceCache[$conferenceId] =& $this->conferenceDao->getConference($conferenceId);
		}
		return $this->conferenceCache[$conferenceId];
	}

	/**
	 * Cached function to get a schedConf
	 * @param $schedConfId int
	 * @return object
	 */
	function &getSchedConf($schedConfId) {
		if (!isset($this->schedConfCache[$schedConfId])) {
			$this->schedConfCache[$schedConfId] =& $this->schedConfDao->getSchedConf($schedConfId);
		}
		return $this->schedConfCache[$schedConfId];
	}

	/**
	 * Cached function to get a track
	 * @param $trackId int
	 * @return object
	 */
	function &getTrack($trackId) {
		if (!isset($this->trackCache[$trackId])) {
			$this->trackCache[$trackId] =& $this->trackDao->getTrack($trackId);
		}
		return $this->trackCache[$trackId];
	}

	/**
	 * Return OAIRecord object from database row.
	 * @param $row array
	 * @return OAIRecord
	 */
	function &_returnRecordFromRow(&$row) {
		$record = new OAIRecord();

		$paperId = $row['paper_id'];
/*		if ($this->conferenceSettingsDao->getSetting($row['conference_id'], 'enablePublicPaperId')) {
			if (!empty($row['public_paper_id'])) {
				$paperId = $row['public_paper_id'];
			}
		} */

		$paper =& $this->publishedPaperDao->getPublishedPaperByPaperId($paperId);
		$conference =& $this->getConference($row['conference_id']);
		$schedConf =& $this->getSchedConf($row['sched_conf_id']);
		$track =& $this->getTrack($row['track_id']);
		$galleys =& $this->paperGalleyDao->getGalleysByPaper($paperId);

		$record->setData('paper', $paper);
		$record->setData('conference', $conference);
		$record->setData('schedConf', $schedConf);
		$record->setData('track', $track);
		$record->setData('galleys', $galleys);
		
		// FIXME Use public ID in OAI identifier?
		$record->identifier = $this->oai->paperIdToIdentifier($row['paper_id']);
		$record->datestamp = OAIUtils::UTCDate(strtotime($this->datetimeFromDB($row['last_modified'])));
		$record->sets = array($conference->getPath() . ':' . $schedConf->getPath() . ':' . $track->getLocalizedAbbrev());

		return $record;
	}

	/**
	 * Return OAIIdentifier object from database row.
	 * @param $row array
	 * @return OAIIdentifier
	 */
	function &_returnIdentifierFromRow(&$row) {
		$record = new OAIRecord();
		$conference =& $this->getConference($row['conference_id']);
		$schedConf =& $this->getSchedConf($row['sched_conf_id']);
		$track =& $this->getTrack($row['track_id']);

		$record->identifier = $this->oai->paperIdToIdentifier($row['paper_id']);
		$record->datestamp = OAIUtils::UTCDate(strtotime($this->datetimeFromDB($row['last_modified'])));
		$record->sets = array($conference->getPath() . ':' . $schedConf->getPath() . ':' . $track->getLocalizedAbbrev());

		return $record;
	}

	//
	// Resumption tokens
	//

	/**
	 * Clear stale resumption tokens.
	 */
	function clearTokens() {
		$this->update(
			'DELETE FROM oai_resumption_tokens WHERE expire < ?', time()
		);
	}

	/**
	 * Retrieve a resumption token.
	 * @return OAIResumptionToken
	 */
	function &getToken($tokenId) {
		$result =& $this->retrieve(
			'SELECT * FROM oai_resumption_tokens WHERE token = ?',
			array($tokenId)
		);

		if ($result->RecordCount() == 0) {
			$token = null;

		} else {
			$row =& $result->getRowAssoc(false);
			$token = new OAIResumptionToken($row['token'], $row['record_offset'], unserialize($row['params']), $row['expire']);
		}

		$result->Close();
		unset($result);

		return $token;
	}

	/**
	 * Insert an OAI resumption token, generating a new ID.
	 * @param $token OAIResumptionToken
	 * @return OAIResumptionToken
	 */
	function &insertToken(&$token) {
		do {
			// Generate unique token ID
			$token->id = md5(uniqid(mt_rand(), true));
			$result =& $this->retrieve(
				'SELECT COUNT(*) FROM oai_resumption_tokens WHERE token = ?',
				array($token->id)
			);
			$val = $result->fields[0];

			$result->Close();
			unset($result);
		} while($val != 0);

		$this->update(
			'INSERT INTO oai_resumption_tokens (token, record_offset, params, expire)
			VALUES
			(?, ?, ?, ?)',
			array($token->id, $token->offset, serialize($token->params), $token->expire)
		);

		return $token;
	}

	//
	// Sets
	//

	/**
	 * Return hierarchy of OAI sets (conferences plus conference tracks).
	 * @param $conferenceId int
	 * @param $offset int
	 * @param $total int
	 * @return array OAISet
	 */
	function &getConferenceSets($conferenceId, $offset, &$total) {
		if (isset($conferenceId)) {
			$conferences = array($this->conferenceDao->getConference($conferenceId));
		} else {
			$conferences =& $this->conferenceDao->getConferences();
			$conferences =& $conferences->toArray();
		}

		// FIXME Set descriptions
		$sets = array();
		foreach ($conferences as $conference) {
			$title = $conference->getConferenceTitle();
			$abbrev = $conference->getPath();
			array_push($sets, new OAISet($abbrev, $title, ''));

			$tracks =& $this->trackDao->getConferenceTracks($conference->getId());
			foreach ($tracks->toArray() as $track) {
				$schedConf =& $this->getSchedConf($track->getSchedConfId());
				array_push($sets, new OAISet($abbrev . ':' . $schedConf->getPath() . ':' . $track->getLocalizedAbbrev(), $track->getTrackTitle(), ''));
				unset($schedConf);
			}
		}

		if ($offset != 0) {
			$sets = array_slice($sets, $offset);
		}

		return $sets;
	}

	/**
	 * Return the conference ID and track ID corresponding to a conference/sched conf/track group.
	 * @param $conferenceSpec string
	 * @param $schedConfSpec string
	 * @param $trackSpec string
	 * @param $restrictConferenceId int
	 * @return array (int, int)
	 */
	function getSetConferenceTrackId($conferenceSpec, $schedConfSpec, $trackSpec, $restrictConferenceId = null) {
		$conferenceId = null;
		$schedConfId = null;

		$conference =& $this->conferenceDao->getConferenceByPath($conferenceSpec);
		if (!isset($conference) || (isset($restrictConferenceId) && $conference->getId() != $restrictConferenceId)) {
			return array(0, 0, 0);
		}

		$conferenceId = $conference->getId();
		$trackId = null;

		if (isset($schedConfSpec)) {
			$schedConf =& $this->schedConfDao->getSchedConfByPath($schedConfSpec, $conferenceId);
			if (!$schedConf) return array(0, 0, 0);
		}

		if (isset($trackSpec)) {
			if (!$schedConf) return array(0, 0, 0);
			$track =& $this->trackDao->getTrackByAbbrev($trackSpec, $schedConf->getId());
			if (isset($track)) {
				$trackId = $track->getId();
			} else {
				$trackId = 0;
			}
		}

		return array($conferenceId, $schedConfId, $trackId);
	}
}

?>
