<?php

/**
 * @file PaperSearchDAO.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PaperSearchDAO
 * @ingroup search
 * @see PaperSearch
 *
 * @brief DAO class for paper search index.
 */

//$Id$

import('search.PaperSearch');
import('paper.Paper');

class PaperSearchDAO extends DAO {
	/**
	 * Add a word to the keyword list (if it doesn't already exist).
	 * @param $keyword string
	 * @return int the keyword ID
	 */
	function insertKeyword($keyword) {
		static $paperSearchKeywordIds = array();
		if (isset($paperSearchKeywordIds[$keyword])) return $paperSearchKeywordIds[$keyword];
		$result =& $this->retrieve(
			'SELECT keyword_id FROM paper_search_keyword_list WHERE keyword_text = ?',
			$keyword
		);
		if($result->RecordCount() == 0) {
			$result->Close();
			unset($result);
			if ($this->update(
				'INSERT INTO paper_search_keyword_list (keyword_text) VALUES (?)',
				$keyword,
				true,
				false
			)) {
				$keywordId = $this->getInsertId('paper_search_keyword_list', 'keyword_id');
			} else {
				$keywordId = null; // Bug #2324
			}
		} else {
			$keywordId = $result->fields[0];
			$result->Close();
			unset($result);
		}

		$paperSearchKeywordIds[$keyword] = $keywordId;

		return $keywordId;
	}

	/**
	 * Retrieve the top results for a phrases with the given
	 * limit (default 500 results).
	 * @param $keywordId int
	 * @return array of results (associative arrays)
	 */
	function &getPhraseResults(&$conference, $phrase, $publishedFrom = null, $publishedTo = null, $type = null, $limit = 500, $cacheHours = 24) {
		import('db.DBRowIterator');
		if (empty($phrase)) {
			$results = false;
			$returner = new DBRowIterator($results);
			return $returner;
		}

		$sqlFrom = '';
		$sqlWhere = '';

		for ($i = 0, $count = count($phrase); $i < $count; $i++) {
			if (!empty($sqlFrom)) {
				$sqlFrom .= ', ';
				$sqlWhere .= ' AND ';
			}
			$sqlFrom .= 'paper_search_object_keywords o'.$i.' NATURAL JOIN paper_search_keyword_list k'.$i;
			if (strstr($phrase[$i], '%') === false) $sqlWhere .= 'k'.$i.'.keyword_text = ?';
			else $sqlWhere .= 'k'.$i.'.keyword_text LIKE ?';
			if ($i > 0) $sqlWhere .= ' AND o0.object_id = o'.$i.'.object_id AND o0.pos+'.$i.' = o'.$i.'.pos';

			$params[] = $phrase[$i];
		}

		if (!empty($type)) {
			$sqlWhere .= ' AND (o.type & ?) != 0';
			$params[] = $type;
		}

		if (!empty($publishedFrom)) {
			$sqlWhere .= ' AND pa.date_published >= ' . $this->datetimeToDB($publishedFrom);
		}

		if (!empty($publishedTo)) {
			$sqlWhere .= ' AND pa.date_published <= ' . $this->datetimeToDB($publishedTo);
		}

		if (!empty($conference)) {
			$sqlWhere .= ' AND i.conference_id = ?';
			$params[] = $conference->getId();
		}

		$result =& $this->retrieveCached(
			'SELECT	o.paper_id,
				COUNT(*) AS count
			FROM	published_papers pa,
				papers p,
				sched_confs i,
				paper_search_objects o
			NATURAL JOIN ' . $sqlFrom . '
			WHERE	pa.paper_id = o.paper_id AND
				p.paper_id = pa.paper_id AND
				p.status = ' . STATUS_PUBLISHED . ' AND
				i.sched_conf_id = pa.sched_conf_id AND ' .
				$sqlWhere . '
			GROUP BY o.paper_id
			ORDER BY count DESC
			LIMIT ' . $limit,
			$params,
			3600 * $cacheHours // Cache for 24 hours
		);

		$returner = new DBRowIterator($result);
		return $returner;
	}

	/**
	 * Delete all keywords for a paper object.
	 * @param $paperId int
	 * @param $type int optional
	 * @param $assocId int optional
	 */
	function deletePaperKeywords($paperId, $type = null, $assocId = null) {
		$sql = 'SELECT object_id FROM paper_search_objects WHERE paper_id = ?';
		$params = array($paperId);

		if (isset($type)) {
			$sql .= ' AND type = ?';
			$params[] = $type;
		}

		if (isset($assocId)) {
			$sql .= ' AND assoc_id = ?';
			$params[] = $assocId;
		}

		$result =& $this->retrieve($sql, $params);
		while (!$result->EOF) {
			$objectId = $result->fields[0];
			$this->update('DELETE FROM paper_search_object_keywords WHERE object_id = ?', $objectId);
			$this->update('DELETE FROM paper_search_objects WHERE object_id = ?', $objectId);
			$result->MoveNext();
		}
		$result->Close();
		unset($result);
	}

	/**
	 * Add a paper object to the index (if already exists, indexed keywords are cleared).
	 * @param $paperId int
	 * @param $type int
	 * @param $assocId int
	 * @return int the object ID
	 */
	function insertObject($paperId, $type, $assocId) {
		$result =& $this->retrieve(
			'SELECT object_id FROM paper_search_objects WHERE paper_id = ? AND type = ? AND assoc_id = ?',
			array($paperId, $type, $assocId)
		);
		if ($result->RecordCount() == 0) {
			$this->update(
				'INSERT INTO paper_search_objects (paper_id, type, assoc_id) VALUES (?, ?, ?)',
				array($paperId, $type, (int) $assocId)
			);
			$objectId = $this->getInsertId('paper_search_objects', 'object_id');

		} else {
			$objectId = $result->fields[0];
			$this->update(
				'DELETE FROM paper_search_object_keywords WHERE object_id = ?',
				$objectId
			);
		}
		$result->Close();
		unset($result);

		return $objectId;
	}

	/**
	 * Index an occurrence of a keyword in an object.s
	 * @param $objectId int
	 * @param $keyword string
	 * @param $position int
	 * @return $keywordId
	 */
	function insertObjectKeyword($objectId, $keyword, $position) {
		$keywordId = $this->insertKeyword($keyword);
		if ($keywordId === null) return null; // Bug #2324
		$this->update(
			'INSERT INTO paper_search_object_keywords (object_id, keyword_id, pos) VALUES (?, ?, ?)',
			array($objectId, $keywordId, $position)
		);
		return $keywordId;
	}
}

?>
