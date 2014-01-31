<?php

/**
 * @file PublishedPaperDAO.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PublishedPaperDAO
 * @ingroup paper
 * @see PublishedPaper
 *
 * @brief Operations for retrieving and modifying PublishedPaper objects.
 */

//$Id$

import('paper.PublishedPaper');

define('PAPER_SORT_ORDER_NATURAL', 0);
define('PAPER_SORT_ORDER_TIME', 1);

class PublishedPaperDAO extends DAO {
	var $paperDao;
	var $authorDao;
	var $galleyDao;
	var $suppFileDao;

 	/**
	 * Constructor.
	 */
	function PublishedPaperDAO() {
		parent::DAO();
		$this->paperDao =& DAORegistry::getDAO('PaperDAO');
		$this->authorDao =& DAORegistry::getDAO('AuthorDAO');
		$this->galleyDao =& DAORegistry::getDAO('PaperGalleyDAO');
		$this->suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
	}

	/**
	 * Retrieve Published Papers by scheduled conference id.  Limit provides number of records to retrieve
	 * @param $schedConfId int
	 * @param $sortOrder int PAPER_SORT_ORDER_...
	 * @return object Iterator of PublishedPaper objects
	 */
	function &getPublishedPapers($schedConfId, $sortOrder = PAPER_SORT_ORDER_NATURAL) {
		$primaryLocale = AppLocale::getPrimaryLocale();
		$locale = AppLocale::getLocale();

		$params = array(
			'title',
			$primaryLocale,
			'title',
			$locale,
			'abbrev',
			$primaryLocale,
			'abbrev',
			$locale,
			$schedConfId
		);

		$publishedPapers = array();

		$sql = 'SELECT	pp.*,
				p.*,
				COALESCE(ttl.setting_value, ttpl.setting_value) AS track_title,
				COALESCE(tal.setting_value, tapl.setting_value) AS track_abbrev,
				t.seq AS track_seq, pp.seq
			FROM	published_papers pp,
				papers p
				LEFT JOIN tracks t ON t.track_id = p.track_id
				LEFT JOIN track_settings ttpl ON (t.track_id = ttpl.track_id AND ttpl.setting_name = ? AND ttpl.locale = ?)
				LEFT JOIN track_settings ttl ON (t.track_id = ttl.track_id AND ttl.setting_name = ? AND ttl.locale = ?)
				LEFT JOIN track_settings tapl ON (t.track_id = tapl.track_id AND tapl.setting_name = ? AND tapl.locale = ?)
				LEFT JOIN track_settings tal ON (t.track_id = tal.track_id AND tal.setting_name = ? AND tal.locale = ?)
			WHERE	pp.paper_id = p.paper_id
				AND pp.sched_conf_id = ?
				AND p.status = ' . STATUS_PUBLISHED;

		switch ($sortOrder) {
			case PAPER_SORT_ORDER_TIME:
				$sql .= ' ORDER BY p.start_time ASC, p.end_time ASC';
				break;
			case PAPER_SORT_ORDER_NATURAL:
			default:
				$sql .= ' ORDER BY track_seq ASC, pp.seq ASC';
				break;
		}

		$result =& $this->retrieve($sql, $params);
		$returner = new DAOResultFactory($result, $this, '_returnPublishedPaperFromRow');
		return $returner;
	}

	/**
	 * Retrieve a count of published papers in a scheduled conference.
	 */
	function getPublishedPaperCountBySchedConfId($schedConfId) {
		$result =& $this->retrieve(
			'SELECT count(*) FROM published_papers pa, papers a WHERE pa.paper_id = a.paper_id AND a.sched_conf_id = ? AND a.status = ' . STATUS_PUBLISHED,
			$schedConfId
		);
		list($count) = $result->fields;
		$result->Close();
		return $count;
	}

	/**
	 * Retrieve all published papers in a scheduled conference.
	 * @param $schedConfId int
	 * @param $rangeInfo object
	 */
	function &getPublishedPapersBySchedConfId($schedConfId, $rangeInfo = null) {
		$primaryLocale = AppLocale::getPrimaryLocale();
		$locale = AppLocale::getLocale();

		$result =& $this->retrieveRange(
			'SELECT pp.*,
				p.*,
				COALESCE(ttl.setting_value, ttpl.setting_value) AS track_title,
				COALESCE(tal.setting_value, tapl.setting_value) AS track_abbrev
			FROM published_papers pp,
				papers p
				LEFT JOIN tracks t ON t.track_id = p.track_id
				LEFT JOIN track_settings ttpl ON (t.track_id = ttpl.track_id AND ttpl.setting_name = ? AND ttpl.locale = ?)
				LEFT JOIN track_settings ttl ON (t.track_id = ttl.track_id AND ttl.setting_name = ? AND ttl.locale = ?)
				LEFT JOIN track_settings tapl ON (t.track_id = tapl.track_id AND tapl.setting_name = ? AND tapl.locale = ?)
				LEFT JOIN track_settings tal ON (t.track_id = tal.track_id AND tal.setting_name = ? AND tal.locale = ?)
			WHERE pp.paper_id = p.paper_id
				AND p.sched_conf_id = ?
				AND p.status = ' . STATUS_PUBLISHED,
			array(
				'title',
				$primaryLocale,
				'title',
				$locale,
				'abbrev',
				$primaryLocale,
				'abbrev',
				$locale,
				$schedConfId
			),
			$rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnPublishedPaperFromRow');
		return $returner;
	}

	/**
	 * Retrieve Published Papers by scheduled conference id
	 * @param $schedConfId int
	 * @param $trackId int ID of track to view, or null for all
	 * @param $searchField int SUBMISSION_FIELD_...
	 * @param $searchMatch string 'is' or 'contains'
	 * @param $search string Search value
	 * @param $previewAbstracts boolean Whether to include unpublished abstracts that have been reviewed
	 * @return PublishedPaper objects array
	 */
	function &getPublishedPapersInTracks($schedConfId, $trackId = null, $searchField = null, $searchMatch = null, $search = null, $previewAbstracts = false) {
		$primaryLocale = AppLocale::getPrimaryLocale();
		$locale = AppLocale::getLocale();

		$publishedPapers = array();

		$params = array(
			'title', // Paper title
			$primaryLocale,
			'title', // Paper title
			$locale,
			'title', // Track title
			$primaryLocale,
			'title', // Track title
			$locale,
			'abbrev',
			$primaryLocale,
			'abbrev',
			$locale,
			$schedConfId
		);

		if ($trackId) $params[] = $trackId;
		$searchSql = '';
		if (!empty($search)) switch ($searchField) {
			case SUBMISSION_FIELD_TITLE:
				if ($searchMatch === 'is') {
					$searchSql = ' AND LOWER(COALESCE(ptl.setting_value, ptpl.setting_value)) = LOWER(?)';
				} else {
					$searchSql = ' AND LOWER(COALESCE(ptl.setting_value, ptpl.setting_value)) LIKE LOWER(?)';
					$search = '%' . $search . '%';
				}
				$params[] = $search;
				break;
			case SUBMISSION_FIELD_AUTHOR:
				$directorSubmissionDao =& DAORegistry::getDAO('DirectorSubmissionDAO');
				$searchSql = $directorSubmissionDao->_generateUserNameSearchSQL($search, $searchMatch, 'pp.', $params);
				break;
		}

		$result =& $this->retrieve(
			'SELECT DISTINCT
				pa.*,
				p.*,
				COALESCE(ttl.setting_value, ttpl.setting_value) AS track_title,
				COALESCE(tal.setting_value, tapl.setting_value) AS track_abbrev,
				t.seq AS track_seq,
				pa.seq
			FROM	paper_authors pp,
				papers p
				LEFT JOIN published_papers pa ON (p.paper_id = pa.paper_id)
				LEFT JOIN paper_settings ptl ON (p.paper_id = ptl.paper_id AND ptl.setting_name = ? AND ptl.locale = ?)
				LEFT JOIN paper_settings ptpl ON (p.paper_id = ptpl.paper_id AND ptpl.setting_name = ? AND ptpl.locale = ?)
				LEFT JOIN tracks t ON t.track_id = p.track_id
				LEFT JOIN track_settings ttpl ON (t.track_id = ttpl.track_id AND ttpl.setting_name = ? AND ttpl.locale = ?)
				LEFT JOIN track_settings ttl ON (t.track_id = ttl.track_id AND ttl.setting_name = ? AND ttl.locale = ?)
				LEFT JOIN track_settings tapl ON (t.track_id = tapl.track_id AND tapl.setting_name = ? AND tapl.locale = ?)
				LEFT JOIN track_settings tal ON (t.track_id = tal.track_id AND tal.setting_name = ? AND tal.locale = ?)
			WHERE	p.sched_conf_id = ?
				AND (
					(p.status = ' . STATUS_PUBLISHED . ' AND pa.paper_id IS NOT NULL)' .
					($previewAbstracts ? 'OR (p.review_mode <> ' . REVIEW_MODE_BOTH_SIMULTANEOUS . ' AND p.status = ' . STATUS_QUEUED . ' AND p.current_stage = ' . REVIEW_STAGE_PRESENTATION . ')':'') . '
				)
				AND pp.paper_id = p.paper_id
				' . ($trackId?'AND p.track_id = ?' : ''). '
				' . $searchSql . '
			ORDER BY track_seq ASC, pa.seq ASC', $params
		);

		$currTrackId = 0;
		while (!$result->EOF) {
			$row =& $result->GetRowAssoc(false);
			$publishedPaper =& $this->_returnPublishedPaperFromRow($row);
			if ($publishedPaper->getTrackId() != $currTrackId) {
				$currTrackId = $publishedPaper->getTrackId();
				$publishedPapers[$currTrackId] = array(
					'papers'=> array(),
					'title' => ''
				);
				$publishedPapers[$currTrackId]['title'] = $publishedPaper->getTrackTitle();
			}
			$publishedPapers[$currTrackId]['papers'][] = $publishedPaper;
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $publishedPapers;
	}

	/**
	 * Retrieve Published Papers by track id
	 * @param $trackId int
	 * @return PublishedPaper objects array
	 */
	function &getPublishedPapersByTrackId($trackId, $schedConfId) {
		$primaryLocale = AppLocale::getPrimaryLocale();
		$locale = AppLocale::getLocale();

		$publishedPapers = array();

		$result =& $this->retrieve(
			'SELECT pa.*,
				a.*,
				COALESCE(ttl.setting_value, ttpl.setting_value) AS track_title,
				COALESCE(tal.setting_value, tapl.setting_value) AS track_abbrev
			FROM published_papers pa,
				papers a,
				tracks t
				LEFT JOIN track_settings ttpl ON (t.track_id = ttpl.track_id AND ttpl.setting_name = ? AND ttpl.locale = ?)
				LEFT JOIN track_settings ttl ON (t.track_id = ttl.track_id AND ttl.setting_name = ? AND ttl.locale = ?)
				LEFT JOIN track_settings tapl ON (t.track_id = tapl.track_id AND tapl.setting_name = ? AND tapl.locale = ?)
				LEFT JOIN track_settings tal ON (t.track_id = tal.track_id AND tal.setting_name = ? AND tal.locale = ?)
			WHERE a.track_id = t.track_id
				AND pa.paper_id = a.paper_id
				AND a.track_id = ?
				AND pa.sched_conf_id = ?
				AND a.status = ' . STATUS_PUBLISHED . '
			ORDER BY pa.seq ASC', array(
				'title',
				$primaryLocale,
				'title',
				$locale,
				'abbrev',
				$primaryLocale,
				'abbrev',
				$locale,
				$trackId,
				$schedConfId
			)
		);

		$currTrackId = 0;
		while (!$result->EOF) {
			$publishedPaper =& $this->_returnPublishedPaperFromRow($result->GetRowAssoc(false));
			$publishedPapers[] = $publishedPaper;
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $publishedPapers;
	}

	/**
	 * Retrieve Published Paper by pub id
	 * @param $pubId int
	 * @return PublishedPaper object
	 */
	function &getPublishedPaperById($pubId) {
		$result =& $this->retrieve(
			'SELECT * FROM published_papers WHERE pub_id = ?', $pubId
		);
		$row = $result->GetRowAssoc(false);

		$publishedPaper = new PublishedPaper();
		$publishedPaper->setPubId($row['pub_id']);
		$publishedPaper->setId($row['paper_id']);
		$publishedPaper->setSchedConfId($row['sched_conf_id']);
		$publishedPaper->setDatePublished($this->datetimeFromDB($row['date_published']));
		$publishedPaper->setSeq($row['seq']);
		$publishedPaper->setViews($row['views']);
		$publishedPaper->setRoomId($row['room_id']);

		$publishedPaper->setSuppFiles($this->suppFileDao->getSuppFilesByPaper($row['paper_id']));

		$result->Close();
		unset($result);

		return $publishedPaper;
	}

	/**
	 * Retrieve published paper by paper id
	 * @param $paperId int
	 * @param $schedConfId int optional
	 * @param $previewAbstracts whether or not to allow access to unpublished papers
	 * @return PublishedPaper object
	 */
	function &getPublishedPaperByPaperId($paperId, $schedConfId = null, $previewAbstracts = null) {
		$primaryLocale = AppLocale::getPrimaryLocale();
		$locale = AppLocale::getLocale();
		$params = array(
			'title',
			$primaryLocale,
			'title',
			$locale,
			'abbrev',
			$primaryLocale,
			'abbrev',
			$locale,
			$paperId
		);
		if ($schedConfId) $params[] = $schedConfId;

		$result =& $this->retrieve(
			'SELECT pa.*,
				a.*,
				COALESCE(ttl.setting_value, ttpl.setting_value) AS track_title,
				COALESCE(tal.setting_value, tapl.setting_value) AS track_abbrev
			FROM	papers a
				LEFT JOIN published_papers pa ON (pa.paper_id = a.paper_id)
				LEFT JOIN tracks t ON t.track_id = a.track_id
				LEFT JOIN track_settings ttpl ON (t.track_id = ttpl.track_id AND ttpl.setting_name = ? AND ttpl.locale = ?)
				LEFT JOIN track_settings ttl ON (t.track_id = ttl.track_id AND ttl.setting_name = ? AND ttl.locale = ?)
				LEFT JOIN track_settings tapl ON (t.track_id = tapl.track_id AND tapl.setting_name = ? AND tapl.locale = ?)
				LEFT JOIN track_settings tal ON (t.track_id = tal.track_id AND tal.setting_name = ? AND tal.locale = ?)
			WHERE	a.paper_id = ?' .
				(isset($schedConfId)?' AND a.sched_conf_id = ?':'') .
				($previewAbstracts!==true?' AND pa.paper_id IS NOT NULL':'') .
				($previewAbstracts===true?' AND (a.status = ' . STATUS_PUBLISHED . ' OR (a.review_mode <> ' . REVIEW_MODE_BOTH_SIMULTANEOUS . ' AND a.status = ' . STATUS_QUEUED . ' AND a.current_stage = ' . REVIEW_STAGE_PRESENTATION . '))':'') .
				($previewAbstracts===false?' AND a.status = ' . STATUS_PUBLISHED:''),
			$params
		);

		$publishedPaper = null;
		if ($result->RecordCount() != 0) {
			$publishedPaper =& $this->_returnPublishedPaperFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $publishedPaper;
	}

	/**
	 * Retrieve published paper by public paper id
	 * @param $schedConfId int
	 * @param $publicPaperId string
	 * @return PublishedPaper object
	 */
	function &getPublishedPaperByPublicPaperId($schedConfId, $publicPaperId) {
		$primaryLocale = AppLocale::getPrimaryLocale();
		$locale = AppLocale::getLocale();

		$result =& $this->retrieve(
			'SELECT	pa.*,
				a.*,
				COALESCE(ttl.setting_value, ttpl.setting_value) AS track_title,
				COALESCE(tal.setting_value, tapl.setting_value) AS track_abbrev
			FROM	published_papers pa,
				papers a
				LEFT JOIN tracks t ON t.track_id = a.track_id
				LEFT JOIN track_settings ttpl ON (t.track_id = ttpl.track_id AND ttpl.setting_name = ? AND ttpl.locale = ?)
				LEFT JOIN track_settings ttl ON (t.track_id = ttl.track_id AND ttl.setting_name = ? AND ttl.locale = ?)
				LEFT JOIN track_settings tapl ON (t.track_id = tapl.track_id AND tapl.setting_name = ? AND tapl.locale = ?)
				LEFT JOIN track_settings tal ON (t.track_id = tal.track_id AND tal.setting_name = ? AND tal.locale = ?)
			WHERE	pa.paper_id = a.paper_id
				AND pa.public_paper_id = ?
				AND a.sched_conf_id = ?',
			array(
				'title',
				$primaryLocale,
				'title',
				$locale,
				'abbrev',
				$primaryLocale,
				'abbrev',
				$locale,
				$publicPaperId,
				$schedConfId
			)
		);

		$publishedPaper = null;
		if ($result->RecordCount() != 0) {
			$publishedPaper =& $this->_returnPublishedPaperFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $publishedPaper;
	}

	/**
	 * Retrieve published paper by public paper id or, failing that,
	 * internal paper ID; public paper ID takes precedence.
	 * @param $schedConfId int
	 * @param $paperId string
	 * @param $previewAbstracts boolean Whether to include unpublished abstracts that have been reviewed
	 * @return PublishedPaper object
	 */
	function &getPublishedPaperByBestPaperId($schedConfId, $paperId, $previewAbstracts = null) {
		$paper =& $this->getPublishedPaperByPublicPaperId($schedConfId, $paperId);
		if (!isset($paper) && ctype_digit("$paperId")) $paper =& $this->getPublishedPaperByPaperId((int) $paperId, $schedConfId, $previewAbstracts);
		return $paper;
	}

	/**
	 * Retrieve "paper_id"s for published papers for a scheduled conference,
	 * sorted alphabetically.
	 * Note that if schedConfId is null, alphabetized paper IDs for all
	 * scheduled conferences are returned.
	 * @param $schedConfId int
	 * @return Array
	 */
	function &getPublishedPaperIdsAlphabetizedByTitle($conferenceId = null, $schedConfId = null, $rangeInfo = null) {
		$params = array(
			'cleanTitle',
			AppLocale::getLocale(),
			'cleanTitle',
			AppLocale::getPrimaryLocale()
		);
		if ($conferenceId) $params[] = $conferenceId;
		if ($schedConfId) $params[] = $schedConfId;

		$paperIds = array();

		$result =& $this->retrieveCached(
			'SELECT	p.paper_id,
				COALESCE(ptl.setting_value, ptpl.setting_value) AS paper_title
			FROM	published_papers pp,
				papers p
				' . ($conferenceId?'LEFT JOIN sched_confs sc ON sc.sched_conf_id = p.sched_conf_id':'') . '
				LEFT JOIN paper_settings ptl ON (ptl.setting_name = ? AND ptl.paper_id = p.paper_id AND ptl.locale = ?)
				LEFT JOIN paper_settings ptpl ON (ptpl.setting_name = ? AND ptpl.paper_id = p.paper_id AND ptpl.locale = ?)
			WHERE	pp.paper_id = p.paper_id AND
				p.status = ' . STATUS_PUBLISHED . '
				' . ($conferenceId?'AND sc.conference_id = ?':'') . '
				' . ($schedConfId?'AND p.sched_conf_id = ?':'') . '
			ORDER BY paper_title',
			$params
		);

		while (!$result->EOF) {
			$row = $result->getRowAssoc(false);
			$paperIds[] = $row['paper_id'];
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $paperIds;
	}

	/**
	 * Retrieve "paper_id"s for published papers for a scheduled conference,
	 * sorted by scheduled conference.
	 * Note that if schedConfId is null, alphabetized paper IDs for all
	 * scheduled conferences are returned.
	 * @param $schedConfId int
	 * @return Array
	 */
	function &getPublishedPaperIdsAlphabetizedBySchedConf($conferenceId = null, $schedConfId = null, $rangeInfo = null) {
		$params = array();
		if ($conferenceId) $params[] = $conferenceId;
		if ($schedConfId) $params[] = $schedConfId;

		$paperIds = array();

		$result =& $this->retrieveCached(
			'SELECT	p.paper_id AS pub_id
			FROM	published_papers pa,
				papers p
				' . ($conferenceId?'LEFT JOIN sched_confs e ON e.sched_conf_id = p.sched_conf_id':'') . '
			WHERE	pa.paper_id = p.paper_id
				AND p.status = ' . STATUS_PUBLISHED .
				($conferenceId?' AND e.conference_id = ?':'') .
				($schedConfId?' AND p.sched_conf_id = ?':'') .
			' ORDER BY p.sched_conf_id',
			$params
		);

		while (!$result->EOF) {
			$row = $result->getRowAssoc(false);
			$paperIds[] = $row['pub_id'];
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $paperIds;
	}

	/**
	 * creates and returns a published paper object from a row
	 * @param $row array
	 * @return PublishedPaper object
	 */
	function &_returnPublishedPaperFromRow($row) {
		$publishedPaper = new PublishedPaper();
		$publishedPaper->setPubId($row['pub_id']);
		$publishedPaper->setSchedConfId($row['sched_conf_id']);
		$publishedPaper->setDatePublished($this->datetimeFromDB($row['date_published']));
		$publishedPaper->setSeq($row['seq']);
		$publishedPaper->setViews($row['views']);
		$publishedPaper->setPublicPaperId($row['public_paper_id']);
		$publishedPaper->setRoomId($row['room_id']);

		// Paper attributes
		$this->paperDao->_paperFromRow($publishedPaper, $row);

		$publishedPaper->setGalleys($this->galleyDao->getGalleysByPaper($row['paper_id']));

		$publishedPaper->setSuppFiles($this->suppFileDao->getSuppFilesByPaper($row['paper_id']));

		HookRegistry::call('PublishedPaperDAO::_returnPublishedPaperFromRow', array(&$publishedPaper, &$row));

		return $publishedPaper;
	}

	/**
	 * inserts a new published paper into published_papers table
	 * @param PublishedPaper object
	 * @return pubId int
	 */

	function insertPublishedPaper(&$publishedPaper) {
		$this->update(
			sprintf('INSERT INTO published_papers
				(paper_id, sched_conf_id, date_published, seq, public_paper_id, room_id)
				VALUES
				(?, ?, %s, ?, ?, ?)',
				$this->datetimeToDB($publishedPaper->getDatePublished())),
			array(
				$publishedPaper->getId(),
				$publishedPaper->getSchedConfId(),
				$publishedPaper->getSeq(),
				$publishedPaper->getPublicPaperId(),
				$publishedPaper->getRoomId()
			)
		);

		$publishedPaper->setPubId($this->getInsertPublishedPaperId());
		return $publishedPaper->getPubId();
	}

	/**
	 * Get the ID of the last inserted published paper.
	 * @return int
	 */
	function getInsertPublishedPaperId() {
		return $this->getInsertId('published_papers', 'pub_id');
	}

	/**
	 * removes an published Paper by id
	 * @param pubId int
	 */
	function deletePublishedPaperById($pubId) {
		$this->update(
			'DELETE FROM published_papers WHERE pub_id = ?', $pubId
		);
	}

	/**
	 * Delete published paper by paper ID
	 * NOTE: This does not delete the related Paper or any dependent entities
	 * @param $paperId int
	 */
	function deletePublishedPaperByPaperId($paperId) {
		return $this->update(
			'DELETE FROM published_papers WHERE paper_id = ?', $paperId
		);
	}

	/**
	 * Delete published papers by track ID
	 * @param $trackId int
	 */
	function deletePublishedPapersByTrackId($trackId) {
		$result =& $this->retrieve(
			'SELECT pa.paper_id AS paper_id FROM published_papers pa, papers a WHERE pa.paper_id = a.paper_id AND a.track_id = ?', $trackId
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$this->update(
				'DELETE FROM published_papers WHERE paper_id = ?', $row['paper_id']
			);
		}

		$result->Close();
		unset($result);
	}

	/**
	 * Delete published papers by scheduled conference ID
	 * @param $schedConfId int
	 */
	function deletePublishedPapersBySchedConfId($schedConfId) {
		return $this->update(
			'DELETE FROM published_papers WHERE sched_conf_id = ?', $schedConfId
		);
	}

	/**
	 * updates a published paper
	 * @param PublishedPaper object
	 */
	function updatePublishedPaper($publishedPaper) {
		$this->update(
			sprintf('UPDATE published_papers
				SET
					paper_id = ?,
					sched_conf_id = ?,
					date_published = %s,
					seq = ?,
					public_paper_id = ?,
					room_id = ?
				WHERE pub_id = ?',
				$this->datetimeToDB($publishedPaper->getDatePublished())),
			array(
				$publishedPaper->getId(),
				$publishedPaper->getSchedConfId(),
				$publishedPaper->getSeq(),
				$publishedPaper->getPublicPaperId(),
				$publishedPaper->getRoomId(),
				$publishedPaper->getPubId()
			)
		);
	}

	/**
	 * updates a published paper field
	 * @param $pubId int
	 * @param $field string
	 * @param $value mixed
	 */
	function updatePublishedPaperField($pubId, $field, $value) {
		$this->update(
			"UPDATE published_papers SET $field = ? WHERE pub_id = ?", array($value, $pubId)
		);
	}

	/**
	 * Sequentially renumber published papers in their sequence order.
	 */
	function resequencePublishedPapers($trackId, $schedConfId) {
		$result =& $this->retrieve(
			'SELECT pa.pub_id FROM published_papers pa, papers a WHERE a.track_id = ? AND a.paper_id = pa.paper_id AND pa.sched_conf_id = ? ORDER BY pa.seq',
			array($trackId, $schedConfId)
		);

		for ($i=1; !$result->EOF; $i++) {
			list($pubId) = $result->fields;
			$this->update(
				'UPDATE published_papers SET seq = ? WHERE pub_id = ?',
				array($i, $pubId)
			);

			$result->moveNext();
		}

		$result->close();
		unset($result);
	}

	/**
	 * Retrieve all authors from published papers
	 * @param $schedConfId int
	 * @return $authors array Author Objects
	 */
	function getPublishedPaperAuthors($schedConfId) {
		$authors = array();
		$result =& $this->retrieve(
			'SELECT aa.* FROM paper_authors aa, published_papers pa WHERE aa.paper_id = pa.paper_id AND pa.sched_conf_id = ? ORDER BY pa.sched_conf_id', $schedConfId
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$author = new Author();
			$author->setId($row['author_id']);
			$author->setPaperId($row['paper_id']);
			$author->setFirstName($row['first_name']);
			$author->setMiddleName($row['middle_name']);
			$author->setLastName($row['last_name']);
			$author->setAffiliation($row['affiliation']);
			$author->setEmail($row['email']);
			$author->setBiography($row['biography']);
			$author->setPrimaryContact($row['primary_contact']);
			$author->setSequence($row['seq']);
			$authors[] = $author;
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $authors;
	}

	/**
	 * Increment the views count for a galley.
	 * @param $paperId int
	 */
	function incrementViewsByPaperId($paperId) {
		return $this->update(
			'UPDATE published_papers SET views = views + 1 WHERE paper_id = ?',
			$paperId
		);
	}

	/**
	 * Checks if public identifier exists
	 * @param $publicPaperId string
	 * @param $paperId int
	 * @param $schedConfId int
	 * @return boolean
	 */
	function publicPaperIdExists($publicPaperId, $paperId, $schedConfId) {
		$result =& $this->retrieve(
			'SELECT COUNT(*) FROM published_papers pa, papers a WHERE pa.paper_id = a.paper_id AND a.sched_conf_id = ? AND pa.public_paper_id = ? AND pa.paper_id <> ?',
			array($schedConfId, $publicPaperId, $paperId)
		);
		$returner = $result->fields[0] ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}
	
	/**
	 * Return years of oldest/youngest published paper within the site or a specific scheduled conference
	 * @param $conferenceId int
	 * @return array
	 */
	function getPaperYearRange($conferenceId = null) {
		$result =& $this->retrieve(
			'SELECT MAX(pp.date_published), MIN(pp.date_published) FROM published_papers pp, papers p, sched_confs sc WHERE pp.paper_id = p.paper_id AND pp.sched_conf_id = sc.sched_conf_id ' . (isset($conferenceId)?' AND sc.conference_id = ?':''),
			isset($conferenceId)?$conferenceId:false
		);
		$returner = array($result->fields[0], $result->fields[1]);

		$result->Close();
		unset($result);

		return $returner;
	}
}

?>
