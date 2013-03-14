<?php

/**
 * @file classes/paper/AuthorDAO.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthorDAO
 * @ingroup paper
 * @see Author
 *
 * @brief Operations for retrieving and modifying Author objects.
 */

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
	 * Retrieve all published submissions associated with authors with
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
		$publishedPaperDao = DAORegistry::getDAO('PublishedPaperDAO');
		$params = array(
			'affiliation',
			$firstName, $middleName, $lastName,
			$affiliation, $country
		);
		if ($schedConfId !== null) $params[] = (int) $schedConfId;

		$result =& $this->retrieve(
			'SELECT DISTINCT
				aa.submission_id
			FROM	authors aa
				LEFT JOIN papers a ON (aa.submission_id = a.paper_id)
				LEFT JOIN author_settings asl ON (asl.author_id = aa.author_id AND asl.setting_name = ?)
			WHERE	aa.first_name = ?
				AND a.status = ' . STATUS_PUBLISHED . '
				AND (aa.middle_name = ?' . (empty($middleName)?' OR aa.middle_name IS NULL':'') . ')
				AND aa.last_name = ?
				AND (asl.setting_value = ?' . (empty($affiliation)?' OR asl.setting_value IS NULL':'') . ')
				AND (aa.country = ?' . (empty($country)?' OR aa.country IS NULL':'') . ') ' .
				($schedConfId!==null?(' AND a.sched_conf_id = ?'):''),
			$params
		);

		while (!$result->EOF) {
			$row = $result->getRowAssoc(false);
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
	 * Retrieve all published authors for a scheduled conference in an associative array by
	 * the first letter of the last name, for example:
	 * $returnedArray['S'] gives array($misterSmithObject, $misterSmytheObject, ...)
	 * Keys will appear in sorted order. Note that if schedConfId is null,
	 * alphabetized authors for all scheduled conferences are returned.
	 * @param $schedConfId int
	 * @param $initial An initial the last names must begin with
	 * @param $rangeInfo Range information
	 * @param $includeEmail Whether or not to include the email in the select distinct
	 * @return array Authors ordered by sequence
	 */
	function &getAuthorsAlphabetizedBySchedConf($schedConfId = null, $initial = null, $rangeInfo = null, $includeEmail = false) {
		$authors = array();
		$params = array(
			'affiliation', AppLocale::getPrimaryLocale(),
			'affiliation', AppLocale::getLocale()
		);

		if (isset($schedConfId)) $params[] = $schedConfId;
		if (isset($initial)) {
			$params[] = String::strtolower($initial) . '%';
			$initialSql = ' AND LOWER(aa.last_name) LIKE LOWER(?)';
		} else {
			$initialSql = '';
		}

		$result =& $this->retrieveRange(
			'SELECT DISTINCT
				CAST(\'\' AS CHAR) AS url,
				0 AS author_id,
				0 AS submission_id,
				' . ($includeEmail?'aa.email AS email,':'CAST(\'\' AS CHAR) AS email,') . '
				0 AS primary_contact,
				0 AS seq,
				aa.first_name,
				aa.middle_name,
				aa.last_name,
				asl.setting_value AS affiliation_l,
				asl.locale,
				aspl.setting_value AS affiliation_pl,
				aspl.locale AS primary_locale,
				aa.country
			FROM	authors aa
				LEFT JOIN author_settings aspl ON (aa.author_id = aspl.author_id AND aspl.setting_name = ? AND aspl.locale = ?)
				LEFT JOIN author_settings asl ON (aa.author_id = asl.author_id AND asl.setting_name = ? AND asl.locale = ?)
				JOIN papers a ON (a.paper_id = aa.submission_id)
				JOIN published_papers pa ON (pa.paper_id = a.paper_id)
				JOIN sched_confs e ON (a.sched_conf_id = e.sched_conf_id)
			WHERE	a.status = ' . STATUS_PUBLISHED . '
				' . (isset($schedConfId)?'AND a.sched_conf_id = ? ':'') . '
				AND (aa.last_name IS NOT NULL
				AND aa.last_name <> \'\')' . $initialSql . ' ORDER BY aa.last_name, aa.first_name',
			$params,
			$rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnSimpleAuthorFromRow');
		return $returner;
	}

	/**
	 * Get a new data object
	 * @return DataObject
	 */
	function newDataObject() {
		return new Author();
	}
}

?>
