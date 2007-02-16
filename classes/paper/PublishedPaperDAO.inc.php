<?php

/**
 * PublishedPaperDAO.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package paper
 *
 * Class for PublishedPaper DAO.
 * Operations for retrieving and modifying PublishedPaper objects.
 *
 * $Id$
 */

import('paper.PublishedPaper');

class PublishedPaperDAO extends DAO {

	var $paperDao;
	var $presenterDao;
	var $galleyDao;
	var $suppFileDao;

 	/**
	 * Constructor.
	 */
	function PublishedPaperDAO() {
		parent::DAO();
		$this->paperDao = &DAORegistry::getDAO('PaperDAO');
		$this->presenterDao = &DAORegistry::getDAO('PresenterDAO');
		$this->galleyDao = &DAORegistry::getDAO('PaperGalleyDAO');
		$this->suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
	}

	/**
	 * Retrieve Published Papers by scheduled conference id.  Limit provides number of records to retrieve
	 * @param $schedConfId int
	 * @param $limit int, default NULL
	 * @return PublishedPaper objects array
	 */
	function &getPublishedPapers($schedConfId, $limit = NULL) {
		$publishedPapers = array();

		if (isset($limit)) {
			$result = &$this->retrieveLimit(
				'SELECT DISTINCT pa.*,
					p.*,
					s.title AS track_title,
					s.title_alt1 AS track_title_alt1,
					s.title_alt2 AS track_title_alt2,
					s.abbrev AS track_abbrev,
					s.abbrev_alt1 AS track_abbrev_alt1,
					s.abbrev_alt2 AS track_abbrev_alt2,
					COALESCE(o.seq, s.seq) AS track_seq, pa.seq
				FROM published_papers pa,
					papers p
				LEFT JOIN tracks s ON s.track_id = p.track_id
				LEFT JOIN custom_track_orders o ON (p.track_id = o.track_id AND o.sched_conf_id = ?)
				WHERE pa.paper_id = p.paper_id
					AND pa.sched_conf_id = ?
					AND p.status <> ' . SUBMISSION_STATUS_ARCHIVED . '
				ORDER BY track_seq ASC, pa.seq ASC', array($schedConfId, $schedConfId), $limit
			);
		} else {
			$result = &$this->retrieve(
				'SELECT DISTINCT pa.*,
					p.*,
					s.title AS track_title,
					s.title_alt1 AS track_title_alt1,
					s.title_alt2 AS track_title_alt2,
					s.abbrev AS track_abbrev,
					s.abbrev_alt1 AS track_abbrev_alt1,
					s.abbrev_alt2 AS track_abbrev_alt2,
					COALESCE(o.seq, s.seq) AS track_seq,
					pa.seq
				FROM published_papers pa,
					papers p
				LEFT JOIN tracks s ON s.track_id = p.track_id
				LEFT JOIN custom_track_orders o ON (p.track_id = o.track_id AND o.sched_conf_id = ?)
				WHERE pa.paper_id = p.paper_id
					AND pa.sched_conf_id = ?
					AND p.status <> ' . SUBMISSION_STATUS_ARCHIVED . '
				ORDER BY track_seq ASC, pa.seq ASC', array($schedConfId, $schedConfId)
			);
		}

		while (!$result->EOF) {
			$publishedPapers[] = &$this->_returnPublishedPaperFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $publishedPapers;
	}

	/**
	 * Retrieve a count of published papers in a scheduled conference.
	 */
	function getPublishedPaperCountBySchedConfId($schedConfId) {
		$result =& $this->retrieve(
			'SELECT count(*) FROM published_papers pa, papers a WHERE pa.paper_id = a.paper_id AND a.sched_conf_id = ? AND a.status <> ' . SUBMISSION_STATUS_ARCHIVED,
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
		$result =& $this->retrieveRange(
			'SELECT pa.*,
				a.*,
				s.title AS track_title,
				s.title_alt1 AS track_title_alt1,
				s.title_alt2 AS track_title_alt2,
				s.abbrev AS track_abbrev,
				s.abbrev_alt1 AS track_abbrev_alt1,
				s.abbrev_alt2 AS track_abbrev_alt2
			FROM published_papers pa,
				papers a
			LEFT JOIN tracks s ON s.track_id = a.track_id
			WHERE pa.paper_id = a.paper_id
				AND a.sched_conf_id = ?
				AND a.status <> ' . SUBMISSION_STATUS_ARCHIVED,
			$schedConfId,
			$rangeInfo
		);

		$returner =& new DAOResultFactory($result, $this, '_returnPublishedPaperFromRow');
		return $returner;
	}
	
	/**
	 * Retrieve Published Papers by scheduled conference id
	 * @param $schedConfId int
	 * @return PublishedPaper objects array
	 */
	function &getPublishedPapersInTracks($schedConfId) {
		$publishedPapers = array();

		$result = &$this->retrieve(
			'SELECT DISTINCT pa.*,
				a.*,
				s.title AS track_title,
				s.title_alt1 AS track_title_alt1,
				s.title_alt2 AS track_title_alt2,
				s.abbrev AS track_abbrev,
				s.abbrev_alt1 AS track_abbrev_alt1,
				s.abbrev_alt2 AS track_abbrev_alt2,
				COALESCE(o.seq, s.seq) AS track_seq,
				pa.seq
			FROM published_papers pa,
				papers a
			LEFT JOIN tracks s ON s.track_id = a.track_id
			LEFT JOIN custom_track_orders o ON (a.track_id = o.track_id AND o.sched_conf_id = ?)
			WHERE pa.paper_id = a.paper_id
				AND pa.sched_conf_id = ?
				AND a.status <> ' . SUBMISSION_STATUS_ARCHIVED . '
			ORDER BY track_seq ASC, pa.seq ASC', array($schedConfId, $schedConfId)
		);

		$currTrackId = 0;
		while (!$result->EOF) {
			$row = &$result->GetRowAssoc(false);
			$publishedPaper = &$this->_returnPublishedPaperFromRow($row);
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
		$publishedPapers = array();

		$result = &$this->retrieve(
			'SELECT pa.*,
				a.*,
				s.title AS track_title,
				s.title_alt1 AS track_title_alt1,
				s.title_alt2 AS track_title_alt2,
				s.abbrev AS track_abbrev,
				s.abbrev_alt1 AS track_abbrev_alt1,
				s.abbrev_alt2 AS track_abbrev_alt2
			FROM published_papers pa,
				papers a,
				tracks s,
				tracks t2
			WHERE a.track_id = s.track_id
				AND pa.paper_id = a.paper_id
				AND a.track_id = ?
				AND pa.sched_conf_id = ?
				AND a.status <> ' . SUBMISSION_STATUS_ARCHIVED . '
			ORDER BY pa.seq ASC', array($trackId, $schedConfId)
		);

		$currTrackId = 0;
		while (!$result->EOF) {
			$publishedPaper = &$this->_returnPublishedPaperFromRow($result->GetRowAssoc(false));
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
		$result = &$this->retrieve(
			'SELECT * FROM published_papers WHERE pub_id = ?', $pubId
		);
		$row = $result->GetRowAssoc(false);

		$publishedPaper = &new PublishedPaper();
		$publishedPaper->setPubId($row['pub_id']);
		$publishedPaper->setPaperId($row['paper_id']);
		$publishedPaper->setSchedConfId($row['sched_conf_id']);
		$publishedPaper->setDatePublished($this->datetimeFromDB($row['date_published']));
		$publishedPaper->setSeq($row['seq']);
		$publishedPaper->setViews($row['views']);
		$publishedPaper->setAccessStatus($row['access_status']);

		$publishedPaper->setSuppFiles($this->suppFileDao->getSuppFilesByPaper($row['paper_id']));

		$result->Close();
		unset($result);

		return $publishedPaper;
	}

	/**
	 * Retrieve published paper by paper id
	 * @param $paperId int
	 * @param $schedConfId int optional
	 * @return PublishedPaper object
	 */
	function &getPublishedPaperByPaperId($paperId, $schedConfId = null) {
		$result = &$this->retrieve(
			'SELECT pa.*,
				a.*,
				s.title AS track_title,
				s.title_alt1 AS track_title_alt1,
				s.title_alt2 AS track_title_alt2,
				s.abbrev AS track_abbrev,
				s.abbrev_alt1 AS track_abbrev_alt1,
				s.abbrev_alt2 AS track_abbrev_alt2
			FROM published_papers pa,
				papers a
			LEFT JOIN tracks s ON s.track_id = a.track_id
			WHERE pa.paper_id = a.paper_id
				AND a.paper_id = ?' . (isset($schedConfId)?'
				AND a.sched_conf_id = ?':''),
			isset($schedConfId)?
				array($paperId, $schedConfId):
				$paperId
		);

		$publishedPaper = null;
		if ($result->RecordCount() != 0) {
			$publishedPaper = &$this->_returnPublishedPaperFromRow($result->GetRowAssoc(false));
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
		$result = &$this->retrieve(
			'SELECT pa.*,
				a.*,
				s.title AS track_title,
				s.title_alt1 AS track_title_alt1,
				s.title_alt2 AS track_title_alt2,
				s.abbrev AS track_abbrev,
				s.abbrev_alt1 AS track_abbrev_alt1,
				s.abbrev_alt2 AS track_abbrev_alt2
			FROM published_papers pa,
				papers a
			LEFT JOIN tracks s ON s.track_id = a.track_id
			WHERE pa.paper_id = a.paper_id
				AND pa.public_paper_id = ?
				AND a.sched_conf_id = ?',
			array($publicPaperId, $schedConfId)
		);

		$publishedPaper = null;
		if ($result->RecordCount() != 0) {
			$publishedPaper = &$this->_returnPublishedPaperFromRow($result->GetRowAssoc(false));
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
	 * @return PublishedPaper object
	 */
	function &getPublishedPaperByBestPaperId($schedConfId, $paperId) {
		$paper = &$this->getPublishedPaperByPublicPaperId($schedConfId, $paperId);
		if (!isset($paper)) $paper = &$this->getPublishedPaperByPaperId((int) $paperId, $schedConfId);
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
	function &getPublishedPaperIdsAlphabetizedByTitle($conferenceId = -1, $schedConfId = -1, $rangeInfo = null) {
		$paperIds = array();
		
		if($schedConfId !== -1) {
			$result = &$this->retrieveCached(
				'SELECT a.paper_id AS pub_id
				FROM published_papers pa, papers a
				WHERE pa.paper_id = a.paper_id
					AND a.sched_conf_id = ?
				ORDER BY a.title', $schedConfId);
		} elseif ($conferenceId !== -1) {
			$result = &$this->retrieveCached(
				'SELECT a.paper_id AS pub_id
				FROM published_papers pa, papers a
				LEFT JOIN sched_confs e ON e.sched_conf_id = a.sched_conf_id
				WHERE pa.paper_id = a.paper_id
					AND e.conference_id = ?
				ORDER BY a.title', $conferenceId);
		} else {
			$result = &$this->retrieveCached(
				'SELECT a.paper_id AS pub_id
				FROM published_papers pa, papers a
				LEFT JOIN tracks s ON s.track_id = a.track_id
				WHERE pa.paper_id = a.paper_id
				ORDER BY a.title', false);
		}
		
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
	 * Retrieve "paper_id"s for published papers for a scheduled conference,
	 * sorted by scheduled conference.
	 * Note that if schedConfId is null, alphabetized paper IDs for all
	 * scheduled conferences are returned.
	 * @param $schedConfId int
	 * @return Array
	 */
	function &getPublishedPaperIdsAlphabetizedBySchedConf($conferenceId, $schedConfId = -1, $rangeInfo = null) {
		$paperIds = array();
		
		if($schedConfId !== -1) {
			$result = &$this->retrieveCached(
				'SELECT a.paper_id AS pub_id
				FROM published_papers pa, papers a
				WHERE pa.paper_id = a.paper_id
					AND a.sched_conf_id = ?
				ORDER BY a.sched_conf_id, a.title', $schedConfId);
		} elseif ($conferenceId !== -1) {
			$result = &$this->retrieveCached(
				'SELECT a.paper_id AS pub_id
				FROM published_papers pa, papers a
				LEFT JOIN sched_confs e ON e.sched_conf_id = a.sched_conf_id
				WHERE pa.paper_id = a.paper_id
					AND e.conference_id = ?
				ORDER BY a.sched_conf_id, a.title', $conferenceId);
		} else {
			$result = &$this->retrieveCached(
				'SELECT a.paper_id AS pub_id
				FROM published_papers pa, papers a
				LEFT JOIN tracks s ON s.track_id = a.track_id
				WHERE pa.paper_id = a.paper_id
				ORDER BY a.sched_conf_id, a.title', false);
		}
		
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
		$publishedPaper = &new PublishedPaper();
		$publishedPaper->setPubId($row['pub_id']);
		$publishedPaper->setSchedConfId($row['sched_conf_id']);
		$publishedPaper->setDatePublished($this->datetimeFromDB($row['date_published']));
		$publishedPaper->setSeq($row['seq']);
		$publishedPaper->setViews($row['views']);
		$publishedPaper->setAccessStatus($row['access_status']);
		$publishedPaper->setPublicPaperId($row['public_paper_id']);

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
				(paper_id, sched_conf_id, date_published, seq, access_status, public_paper_id)
				VALUES
				(?, ?, %s, ?, ?, ?)',
				$this->datetimeToDB($publishedPaper->getDatePublished())),
			array(
				$publishedPaper->getPaperId(),
				$publishedPaper->getSchedConfId(),
				$publishedPaper->getSeq(),
				$publishedPaper->getAccessStatus(),
				$publishedPaper->getPublicPaperId()
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
		$result = &$this->retrieve(
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
					access_status = ?,
					public_paper_id = ?
				WHERE pub_id = ?',
				$this->datetimeToDB($publishedPaper->getDatePublished())),
			array(
				$publishedPaper->getPaperId(),
				$publishedPaper->getSchedConfId(),
				$publishedPaper->getSeq(),
				$publishedPaper->getAccessStatus(),
				$publishedPaper->getPublicPaperId(),
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
		$result = &$this->retrieve(
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
	 * Retrieve all presenters from published papers
	 * @param $schedConfId int
	 * @return $presenters array Presenter Objects
	 */
	function getPublishedPaperPresenters($schedConfId) {
		$presenters = array();
		$result = &$this->retrieve(
			'SELECT aa.* FROM paper_presenters aa, published_papers pa WHERE aa.paper_id = pa.paper_id AND pa.sched_conf_id = ? ORDER BY pa.sched_conf_id', $schedConfId
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$presenter = &new Presenter();
			$presenter->setPresenterId($row['presenter_id']);
			$presenter->setPaperId($row['paper_id']);
			$presenter->setFirstName($row['first_name']);
			$presenter->setMiddleName($row['middle_name']);
			$presenter->setLastName($row['last_name']);
			$presenter->setAffiliation($row['affiliation']);
			$presenter->setEmail($row['email']);
			$presenter->setBiography($row['biography']);
			$presenter->setPrimaryContact($row['primary_contact']);
			$presenter->setSequence($row['seq']);
			$presenters[] = $presenter;
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $presenters;
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
		$result = &$this->retrieve(
			'SELECT COUNT(*) FROM published_papers pa, papers a WHERE pa.paper_id = a.paper_id AND a.sched_conf_id = ? AND pa.public_paper_id = ? AND pa.paper_id <> ?',
			array($schedConfId, $publicPaperId, $paperId)
		);
		$returner = $result->fields[0] ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}
}

?>
