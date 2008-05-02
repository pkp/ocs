<?php

/**
 * @file PresenterDAO.inc.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package paper
 * @class PresenterDAO
 *
 * Class for Presenter DAO.
 * Operations for retrieving and modifying Presenter objects.
 *
 * $Id$
 */

import('paper.Presenter');
import('paper.Paper');

class PresenterDAO extends DAO {
	/**
	 * Retrieve an presenter by ID.
	 * @param $presenterId int
	 * @return Presenter
	 */
	function &getPresenter($presenterId) {
		$result = &$this->retrieve(
			'SELECT * FROM paper_presenters WHERE presenter_id = ?', $presenterId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnPresenterFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve all presenters for a paper.
	 * @param $paperId int
	 * @return array Presenters ordered by sequence
	 */
	function &getPresentersByPaper($paperId) {
		$presenters = array();

		$result = &$this->retrieve(
			'SELECT * FROM paper_presenters WHERE paper_id = ? ORDER BY seq',
			$paperId
		);

		while (!$result->EOF) {
			$presenters[] = &$this->_returnPresenterFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $presenters;
	}

	/**
	 * Retrieve all published papers associated with presenters with
	 * the given first name, middle name, last name, and affiliation.
	 * @param $schedConfId int (null if no restriction desired)
	 * @param firstName string
	 * @param middleName string
	 * @param lastName string
	 * @param affiliation string
	 */
	function &getPublishedPapersForPresenter($schedConfId, $firstName, $middleName, $lastName, $affiliation) {
		$publishedPapers = array();
		$publishedPaperDao = &DAORegistry::getDAO('PublishedPaperDAO');
		$params = array($firstName, $middleName, $lastName, $affiliation);
		if ($schedConfId !== null) $params[] = $schedConfId;

		$result = &$this->retrieve(
			'SELECT DISTINCT
				aa.paper_id
			FROM paper_presenters aa
				LEFT JOIN papers a ON (aa.paper_id = a.paper_id)
			WHERE aa.first_name = ? AND
				a.status = ' . SUBMISSION_STATUS_PUBLISHED . ' AND
				(aa.middle_name = ?' . (empty($middleName)?' OR aa.middle_name IS NULL':'') .  ') AND
				aa.last_name = ? AND
				(aa.affiliation = ?' . (empty($affiliation)?' OR aa.affiliation IS NULL':'') . ')' .
				($schedConfId!==null?(' AND a.sched_conf_id = ?'):''),
			$params
		);

		while (!$result->EOF) {
			$row = &$result->getRowAssoc(false);
			$publishedPaper = &$publishedPaperDao->getPublishedPaperByPaperId($row['paper_id']);
			if ($publishedPaper) {
				$publishedPapers[] = &$publishedPaper;
			}
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $publishedPapers;
	}

	/**
	 * Retrieve all published presenters for a scheduled conference in an associative array by
	 * the first letter of the last name, for example:
	 * $returnedArray['S'] gives array($misterSmithObject, $misterSmytheObject, ...)
	 * Keys will appear in sorted order. Note that if schedConfId is null,
	 * alphabetized presenters for all scheduled conferences are returned.
	 * @param $schedConfId int
	 * @param $initial An initial the last names must begin with
	 * @return array Presenters ordered by sequence
	 */
	function &getPresentersAlphabetizedBySchedConf($schedConfId = null, $initial = null, $rangeInfo = null) {
		$presenters = array();
		$params = array();

		if (isset($schedConfId)) $params[] = $schedConfId;
		if (isset($initial)) {
			$params[] = String::strtolower($initial) . '%';
			$initialSql = ' AND LOWER(aa.last_name) LIKE LOWER(?)';
		} else {
			$initialSql = '';
		}

		$result = &$this->retrieveRange(
			'SELECT	DISTINCT CAST(\'\' AS CHAR(1)) AS url,
				0 AS presenter_id,
				0 AS paper_id,
				CAST(\'\' AS CHAR(1)) AS email,
				CAST(\'\' AS CHAR(1)) AS biography,
				0 AS primary_contact,
				0 AS seq,
				aa.first_name AS first_name,
				aa.middle_name AS middle_name,
				aa.last_name AS last_name,
				aa.affiliation AS affiliation,
				aa.country FROM paper_presenters aa,
				papers a,
				published_papers pa,
				sched_confs e
			WHERE	e.sched_conf_id = pa.sched_conf_id
				AND aa.paper_id = a.paper_id
				' . (isset($schedConfId)?'AND a.sched_conf_id = ? ':'') . '
				AND pa.paper_id = a.paper_id
				AND a.status = ' . SUBMISSION_STATUS_PUBLISHED . '
				AND (aa.last_name IS NOT NULL
				AND aa.last_name <> \'\')' . $initialSql . ' ORDER BY aa.last_name, aa.first_name',
			empty($params)?false:$params,
			$rangeInfo
		);

		$returner = &new DAOResultFactory($result, $this, '_returnPresenterFromRow');
		return $returner;
	}

	/**
	 * Retrieve the IDs of all presenters for a paper.
	 * @param $paperId int
	 * @return array int ordered by sequence
	 */
	function &getPresenterIdsByPaper($paperId) {
		$presenters = array();

		$result = &$this->retrieve(
			'SELECT presenter_id FROM paper_presenters WHERE paper_id = ? ORDER BY seq',
			$paperId
		);

		while (!$result->EOF) {
			$presenters[] = $result->fields[0];
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $presenters;
	}

	/**
	 * Get field names for which data is localized.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('biography');
	}

	/**
	 * Update the localized data for this object
	 * @param $presenter object
	 */
	function updateLocaleFields(&$presenter) {
		$this->updateDataObjectSettings('paper_presenter_settings', $presenter, array(
			'presenter_id' => $presenter->getPresenterId()
		));

	}

	/**
	 * Internal function to return an Presenter object from a row.
	 * @param $row array
	 * @return Presenter
	 */
	function &_returnPresenterFromRow(&$row) {
		$presenter = &new Presenter();
		$presenter->setPresenterId($row['presenter_id']);
		$presenter->setPaperId($row['paper_id']);
		$presenter->setFirstName($row['first_name']);
		$presenter->setMiddleName($row['middle_name']);
		$presenter->setLastName($row['last_name']);
		$presenter->setAffiliation($row['affiliation']);
		$presenter->setCountry($row['country']);
		$presenter->setEmail($row['email']);
		$presenter->setUrl($row['url']);
		$presenter->setPrimaryContact($row['primary_contact']);
		$presenter->setSequence($row['seq']);

		$this->getDataObjectSettings('paper_presenter_settings', 'presenter_id', $row['presenter_id'], $presenter);

		HookRegistry::call('PresenterDAO::_returnPresenterFromRow', array(&$presenter, &$row));

		return $presenter;
	}

	/**
	 * Insert a new Presenter.
	 * @param $presenter Presenter
	 */	
	function insertPresenter(&$presenter) {
		$this->update(
			'INSERT INTO paper_presenters
				(paper_id, first_name, middle_name, last_name, affiliation, country, email, url, primary_contact, seq)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				$presenter->getPaperId(),
				$presenter->getFirstName(),
				$presenter->getMiddleName() . '', // make non-null
				$presenter->getLastName(),
				$presenter->getAffiliation() . '', // make non-null
				$presenter->getCountry(),
				$presenter->getEmail(),
				$presenter->getUrl(),
				$presenter->getPrimaryContact(),
				$presenter->getSequence()
			)
		);

		$presenter->setPresenterId($this->getInsertPresenterId());
		$this->updateLocaleFields($presenter);

		return $presenter->getPresenterId();
	}

	/**
	 * Update an existing Presenter.
	 * @param $presenter Presenter
	 */
	function updatePresenter(&$presenter) {
		$returner = $this->update(
			'UPDATE paper_presenters
				SET
					first_name = ?,
					middle_name = ?,
					last_name = ?,
					affiliation = ?,
					country = ?,
					email = ?,
					url = ?,
					primary_contact = ?,
					seq = ?
				WHERE presenter_id = ?',
			array(
				$presenter->getFirstName(),
				$presenter->getMiddleName() . '', // make non-null
				$presenter->getLastName(),
				$presenter->getAffiliation() . '', // make non-null
				$presenter->getCountry(),
				$presenter->getEmail(),
				$presenter->getUrl(),
				$presenter->getPrimaryContact(),
				$presenter->getSequence(),
				$presenter->getPresenterId()
			)
		);
		$this->updateLocaleFields($presenter);
		return $returner;
	}

	/**
	 * Delete an Presenter.
	 * @param $presenter Presenter
	 */
	function deletePresenter(&$presenter) {
		return $this->deletePresenterById($presenter->getPresenterId());
	}

	/**
	 * Delete an presenter by ID.
	 * @param $presenterId int
	 * @param $paperId int optional
	 */
	function deletePresenterById($presenterId, $paperId = null) {
		$params = array($presenterId);
		if ($paperId) $params[] = $paperId;
		$returner = $this->update(
			'DELETE FROM paper_presenters WHERE presenter_id = ?' .
			($paperId?' AND paper_id = ?':''),
			$params
		);
		if ($returner) $this->update('DELETE FROM paper_presenter_settings WHERE presenter_id = ?', array($presenterId));
	}

	/**
	 * Delete presenters by paper.
	 * @param $paperId int
	 */
	function deletePresentersByPaper($paperId) {
		$presenters =& $this->getPresentersByPaper($paperId);
		foreach ($presenters as $presenter) {
			$this->deletePresenter($presenter);
		}
	}

	/**
	 * Sequentially renumber a paper's presenters in their sequence order.
	 * @param $paperId int
	 */
	function resequencePresenters($paperId) {
		$result = &$this->retrieve(
			'SELECT presenter_id FROM paper_presenters WHERE paper_id = ? ORDER BY seq', $paperId
		);

		for ($i=1; !$result->EOF; $i++) {
			list($presenterId) = $result->fields;
			$this->update(
				'UPDATE paper_presenters SET seq = ? WHERE presenter_id = ?',
				array(
					$i,
					$presenterId
				)
			);

			$result->moveNext();
		}

		$result->close();
		unset($result);
	}

	/**
	 * Get the ID of the last inserted presenter.
	 * @return int
	 */
	function getInsertPresenterId() {
		return $this->getInsertId('paper_presenters', 'presenter_id');
	}
}

?>
