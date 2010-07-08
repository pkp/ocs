<?php

/**
 * @file classes/paper/AuthorDAO.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthorDAO
 * @ingroup paper
 * @see Author
 *
 * @brief Operations for retrieving and modifying Author objects.
 */

// $Id$


import('classes.paper.Author');
import('classes.paper.Paper');
import('lib.pkp.classes.submission.PKPAuthorDAO');

class AuthorDAO extends PKPAuthorDAO {
	/**
	 * Constructor
	 */
	function AuthorDAO() {
		parent::PKPAuthorDAO();
	}

	/**
	 * Retrieve all authors for a paper.
	 * @param $submissionId int
	 * @return array Authors ordered by sequence
	 */
	function &getAuthorsByPaper($submissionId) {
		$authors = array();

		$result =& $this->retrieve(
			'SELECT * FROM authors WHERE submission_id = ? ORDER BY seq',
			(int) $submissionId
		);

		while (!$result->EOF) {
			$authors[] =& $this->_returnAuthorFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $authors;
	}

	/**
	 * Retrieve all published papers associated with authors with
	 * the given first name, middle name, last name, affiliation, and country.
	 * @param $schedConfId int (null if no restriction desired)
	 * @param $firstName string
	 * @param $middleName string
	 * @param $lastName string
	 * @param $affiliation string
	 * @param $country string
	 */
	function &getPublishedPapersForAuthor($schedConfId, $firstName, $middleName, $lastName, $affiliation, $country) {
		$publishedPapers = array();
		$publishedPaperDao =& DAORegistry::getDAO('PublishedPaperDAO');
		$params = array($firstName, $middleName, $lastName, $affiliation, $country);
		if ($schedConfId !== null) $params[] = $schedConfId;

		$result =& $this->retrieve(
			'SELECT DISTINCT
				aa.submission_id
			FROM	authors aa
				LEFT JOIN papers a ON (aa.submission_id = a.paper_id)
			WHERE	aa.first_name = ? AND
				a.status = ' . STATUS_PUBLISHED . ' AND
				(aa.middle_name = ?' . (empty($middleName)?' OR aa.middle_name IS NULL':'') .  ') AND
				aa.last_name = ? AND
				(aa.affiliation = ?' . (empty($affiliation)?' OR aa.affiliation IS NULL':'') . ') AND
				(aa.country = ?' . (empty($country)?' OR aa.country IS NULL':'') . ')' .
				($schedConfId!==null?(' AND a.sched_conf_id = ?'):''),
			$params
		);

		while (!$result->EOF) {
			$row =& $result->getRowAssoc(false);
			$publishedPaper =& $publishedPaperDao->getPublishedPaperByPaperId($row['submission_id']);
			if ($publishedPaper) {
				$publishedPapers[] =& $publishedPaper;
			}
			$result->moveNext();
			unset($publishedPaper);
		}

		$result->Close();
		unset($result);

		return $publishedPapers;
	}

	/**
	 * Retrieve all published authors for a scheduled conference.
	 * Note that if schedConfId is null, alphabetized authors for all
	 * scheduled conferences are returned.
	 * @param $schedConfId int
	 * @param $initial An initial the last names must begin with
	 * @param $rangeInfo Range information
	 * @param $includeEmail Whether or not to include the email in the select distinct
	 * @return object ItemIterator Authors ordered by sequence
	 */
	function &getAuthorsAlphabetizedBySchedConf($schedConfId = null, $initial = null, $rangeInfo = null, $includeEmail = false) {
		$params = array();

		if (isset($schedConfId)) $params[] = $schedConfId;
		if (isset($initial)) {
			$params[] = String::strtolower($initial) . '%';
			$initialSql = ' AND LOWER(aa.last_name) LIKE LOWER(?)';
		} else {
			$initialSql = '';
		}

		$result =& $this->retrieveRange(
			'SELECT	DISTINCT CAST(\'\' AS CHAR) AS url,
				0 AS author_id,
				0 AS submission_id,
				' . ($includeEmail?'aa.email AS email,':'CAST(\'\' AS CHAR) AS email,') . '
				CAST(\'\' AS CHAR) AS biography,
				0 AS primary_contact,
				0 AS seq,
				aa.first_name AS first_name,
				aa.middle_name AS middle_name,
				aa.last_name AS last_name,
				aa.affiliation AS affiliation,
				aa.country
			FROM	authors aa,
				papers a,
				published_papers pa,
				sched_confs e
			WHERE	e.sched_conf_id = pa.sched_conf_id
				AND aa.submission_id = a.paper_id
				' . (isset($schedConfId)?'AND a.sched_conf_id = ? ':'') . '
				AND pa.paper_id = a.paper_id
				AND a.status = ' . STATUS_PUBLISHED . '
				AND (aa.last_name IS NOT NULL
				AND aa.last_name <> \'\')' . $initialSql . ' ORDER BY aa.last_name, aa.first_name',
			empty($params)?false:$params,
			$rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnAuthorFromRow');
		return $returner;
	}

	/**
	 * Get a new data object
	 * @return DataObject
	 */
	function newDataObject() {
		return new Author();
	}

	/**
	 * Insert a new Author.
	 * @param $author Author
	 */	
	function insertAuthor(&$author) {
		$this->update(
			'INSERT INTO authors
				(submission_id, first_name, middle_name, last_name, affiliation, country, email, url, primary_contact, seq)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				$author->getSubmissionId(),
				$author->getFirstName(),
				$author->getMiddleName() . '', // make non-null
				$author->getLastName(),
				$author->getAffiliation() . '', // make non-null
				$author->getCountry(),
				$author->getEmail(),
				$author->getUrl(),
				(int) $author->getPrimaryContact(),
				(float) $author->getSequence()
			)
		);

		$author->setId($this->getInsertAuthorId());
		$this->updateLocaleFields($author);

		return $author->getId();
	}

	/**
	 * Update an existing Author.
	 * @param $author Author
	 */
	function updateAuthor(&$author) {
		$returner = $this->update(
			'UPDATE authors
			SET	first_name = ?,
				middle_name = ?,
				last_name = ?,
				affiliation = ?,
				country = ?,
				email = ?,
				url = ?,
				primary_contact = ?,
				seq = ?
			WHERE	author_id = ?',
			array(
				$author->getFirstName(),
				$author->getMiddleName() . '', // make non-null
				$author->getLastName(),
				$author->getAffiliation() . '', // make non-null
				$author->getCountry(),
				$author->getEmail(),
				$author->getUrl(),
				(int) $author->getPrimaryContact(),
				(float) $author->getSequence(),
				(int) $author->getId()
			)
		);
		$this->updateLocaleFields($author);
		return $returner;
	}

	/**
	 * Delete authors by submission.
	 * @param $submissionId int
	 */
	function deleteAuthorsByPaper($submissionId) {
		$authors =& $this->getAuthorsByPaper($submissionId);
		foreach ($authors as $author) {
			$this->deleteAuthor($author);
		}
	}
}

?>
