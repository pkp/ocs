<?php

/**
 * @file PaperDAO.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PaperDAO
 * @ingroup paper
 *
 * @brief Operations for retrieving and modifying Paper objects.
 *
 */

// $Id$


import('paper.Paper');

class PaperDAO extends DAO {
	var $authorDao;

	/**
	 * Constructor.
	 */
	function PaperDAO() {
		parent::DAO();
		$this->authorDao =& DAORegistry::getDAO('AuthorDAO');
	}

	/**
	 * Get a list of non-localized additional fields to maintain.
	 * @return array
	 */
	function getAdditionalFieldNames() {
		return array('sessionType');
	}

	/**
	 * Get a list of field names for which data is localized.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('title', 'cleanTitle', 'abstract', 'discipline', 'subjectClass', 'subject', 'coverageGeo', 'coverageChron', 'coverageSample', 'type', 'sponsor');
	}

	/**
	 * Update the settings for this object
	 * @param $paper object
	 */
	function updateLocaleFields(&$paper) {
		$this->updateDataObjectSettings('paper_settings', $paper, array(
			'paper_id' => $paper->getId()
		));
	}

	/**
	 * Retrieve a paper by ID.
	 * @param $paperId int
	 * @return Paper
	 */
	function &getPaper($paperId) {
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
		$result =& $this->retrieve(
			'SELECT	p.*,
				COALESCE(ttl.setting_value, ttpl.setting_value) AS track_title,
				COALESCE(tal.setting_value, tapl.setting_value) AS track_abbrev
			FROM	papers p
				LEFT JOIN tracks t ON t.track_id = p.track_id
				LEFT JOIN track_settings ttpl ON (t.track_id = ttpl.track_id AND ttpl.setting_name = ? AND ttpl.locale = ?)
				LEFT JOIN track_settings ttl ON (t.track_id = ttl.track_id AND ttl.setting_name = ? AND ttl.locale = ?)
				LEFT JOIN track_settings tapl ON (t.track_id = tapl.track_id AND tapl.setting_name = ? AND tapl.locale = ?)
				LEFT JOIN track_settings tal ON (t.track_id = tal.track_id AND tal.setting_name = ? AND tal.locale = ?)
			WHERE	paper_id = ?',
			$params
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnPaperFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Internal function to return an Paper object from a row.
	 * @param $row array
	 * @return Paper
	 */
	function &_returnPaperFromRow(&$row) {
		$paper = new Paper();
		$this->_paperFromRow($paper, $row);
		return $paper;
	}

	/**
	 * Internal function to fill in the passed paper object from the row.
	 * @param $paper Paper output paper
	 * @param $row array input row
	 */
	function _paperFromRow(&$paper, &$row) {
		$schedConfId = $row['sched_conf_id'];
		$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
		$schedConf =& $schedConfDao->getSchedConf($schedConfId);
		$conferenceId = $schedConf->getConferenceId();

		$paper->setId($row['paper_id']);
		$paper->setUserId($row['user_id']);
		$paper->setSchedConfId($row['sched_conf_id']);
		$paper->setTrackId($row['track_id']);
		$paper->setDateToPresentations($this->datetimeFromDB($row['date_to_presentations']));
		$paper->setDateToArchive($this->datetimeFromDB($row['date_to_archive']));

		$paper->setTrackTitle($row['track_title']);
		$paper->setTrackAbbrev($row['track_abbrev']);
		$paper->setLanguage($row['language']);
		$paper->setCommentsToDirector($row['comments_to_dr']);
		$paper->setCitations($row['citations']);
		$paper->setDateSubmitted($this->datetimeFromDB($row['date_submitted']));
		$paper->setDateStatusModified($this->datetimeFromDB($row['date_status_modified']));
		$paper->setLastModified($this->datetimeFromDB($row['last_modified']));
		$paper->setDateReminded($this->datetimeFromDB($row['date_reminded']));
		$paper->setStartTime($this->datetimeFromDB($row['start_time']));
		$paper->setEndTime($this->datetimeFromDB($row['end_time']));
		$paper->setStatus($row['status']);
		$paper->setSubmissionProgress($row['submission_progress']);
		$paper->setReviewMode($row['review_mode']);
		$paper->setCurrentStage($row['current_stage']);
		$paper->setSubmissionFileId($row['submission_file_id']);
		$paper->setRevisedFileId($row['revised_file_id']);
		$paper->setReviewFileId($row['review_file_id']);
		$paper->setLayoutFileId($row['layout_file_id']);
		$paper->setDirectorFileId($row['director_file_id']);
		$paper->setPages($row['pages']);
		$paper->setCommentsStatus($row['comments_status']);

		$paper->setAuthors($this->authorDao->getAuthorsByPaper($row['paper_id']));

		$this->getDataObjectSettings('paper_settings', 'paper_id', $row['paper_id'], $paper);

		HookRegistry::call('PaperDAO::_returnPaperFromRow', array(&$paper, &$row));

	}

	/**
	 * Insert a new Paper.
	 * @param $paper Paper
	 */
	function insertPaper(&$paper) {
		$paper->stampModified();
		$this->update(
			sprintf('INSERT INTO papers
				(user_id,
				 sched_conf_id,
				 track_id,
				 language,
				 comments_to_dr,
				 citations,
				 date_submitted,
				 date_status_modified,
				 last_modified,
				 date_reminded,
				 start_time,
				 end_time,
				 date_to_presentations,
				 date_to_archive,
				 status,
				 submission_progress,
				 review_mode,
				 current_stage,
				 submission_file_id,
				 revised_file_id,
				 review_file_id,
				 layout_file_id,
				 director_file_id,
				 pages,
				 comments_status)
				VALUES
				(?, ?, ?, ?, ?, ?, %s, %s, %s, %s, %s, %s, %s, %s, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
				$this->datetimeToDB($paper->getDateSubmitted()), $this->datetimeToDB($paper->getDateStatusModified()), $this->datetimeToDB($paper->getLastModified()), $this->datetimeToDB($paper->getDateReminded()), $this->datetimeToDB($paper->getStartTime()), $this->datetimeToDB($paper->getEndTime()), $this->datetimeToDB($paper->getDateToPresentations()), $this->datetimeToDB($paper->getDateToArchive())),
			array(
				$paper->getUserId(),
				$paper->getSchedConfId(),
				$paper->getTrackId(),
				$paper->getLanguage(),
				$paper->getCommentsToDirector(),
				$paper->getCitations(),
				$paper->getStatus() === null ? STATUS_QUEUED : $paper->getStatus(),
				$paper->getSubmissionProgress() === null ? 1 : $paper->getSubmissionProgress(),
				$paper->getReviewMode(),
				$paper->getCurrentStage(),
				$paper->getSubmissionFileId(),
				$paper->getRevisedFileId(),
				$paper->getReviewFileId(),
				$paper->getLayoutFileId(),
				$paper->getDirectorFileId(),
				$paper->getPages(),
				$paper->getCommentsStatus() === null ? 0 : $paper->getCommentsStatus()
			)
		);

		$paper->setId($this->getInsertPaperId());
		$this->updateLocaleFields($paper);

		// Insert authors for this paper
		$authors =& $paper->getAuthors();
		for ($i=0, $count=count($authors); $i < $count; $i++) {
			$authors[$i]->setPaperId($paper->getId());
			$this->authorDao->insertAuthor($authors[$i]);
		}

		return $paper->getId();
	}

	/**
	 * Update an existing paper.
	 * @param $paper Paper
	 */
	function updatePaper(&$paper) {
		$paper->stampModified();
		$this->update(
			sprintf('UPDATE papers
				SET
					user_id = ?,
					track_id = ?,
					language = ?,
					comments_to_dr = ?,
					citations = ?,
					date_submitted = %s,
					date_status_modified = %s,
					last_modified = %s,
					date_reminded = %s,
					start_time = %s,
					end_time = %s,
					date_to_presentations = %s,
					date_to_archive = %s,
					status = ?,
					submission_progress = ?,
					review_mode = ?,
					current_stage = ?,
					submission_file_id = ?,
					revised_file_id = ?,
					review_file_id = ?,
					layout_file_id = ?,
					director_file_id = ?,
					pages = ?,
					comments_status = ?
				WHERE paper_id = ?',
				$this->datetimeToDB($paper->getDateSubmitted()), $this->datetimeToDB($paper->getDateStatusModified()), $this->datetimeToDB($paper->getLastModified()), $this->datetimeToDB($paper->getDateReminded()), $this->datetimeToDB($paper->getStartTime()), $this->datetimeToDB($paper->getEndTime()), $this->datetimeToDB($paper->getDateToPresentations()), $this->datetimeToDB($paper->getDateToArchive())),
			array(
				$paper->getUserId(),
				$paper->getTrackId(),
				$paper->getLanguage(),
				$paper->getCommentsToDirector(),
				$paper->getCitations(),
				$paper->getStatus(),
				$paper->getSubmissionProgress(),
				$paper->getReviewMode(),
				$paper->getCurrentStage(),
				$paper->getSubmissionFileId(),
				$paper->getRevisedFileId(),
				$paper->getReviewFileId(),
				$paper->getLayoutFileId(),
				$paper->getDirectorFileId(),
				$paper->getPages(),
				$paper->getCommentsStatus(),
				$paper->getId()
			)
		);

		// update authors for this paper
		$authors =& $paper->getAuthors();
		for ($i=0, $count=count($authors); $i < $count; $i++) {
			if ($authors[$i]->getId() > 0) {
				$this->authorDao->updateAuthor($authors[$i]);
			} else {
				$this->authorDao->insertAuthor($authors[$i]);
			}
		}

		// Remove deleted authors
		$removedAuthors = $paper->getRemovedAuthors();
		for ($i=0, $count=count($removedAuthors); $i < $count; $i++) {
			$this->authorDao->deleteAuthorById($removedAuthors[$i], $paper->getId());
		}

		$this->updateLocaleFields($paper);

		// Update author sequence numbers
		$this->authorDao->resequenceAuthors($paper->getId());
	}

	/**
	 * Delete a paper.
	 * @param $paper Paper
	 */
	function deletePaper(&$paper) {
		return $this->deletePaperById($paper->getId());
	}

	/**
	 * Delete a paper by ID.
	 * @param $paperId int
	 */
	function deletePaperById($paperId) {
		$this->authorDao->deleteAuthorsByPaper($paperId);

		$publishedPaperDao =& DAORegistry::getDAO('PublishedPaperDAO');
		$publishedPaperDao->deletePublishedPaperByPaperId($paperId);

		$commentDao =& DAORegistry::getDAO('CommentDAO');
		$commentDao->deleteCommentsByPaper($paperId);

		$paperNoteDao =& DAORegistry::getDAO('PaperNoteDAO');
		$paperNoteDao->clearAllPaperNotes($paperId);

		$trackDirectorSubmissionDao =& DAORegistry::getDAO('TrackDirectorSubmissionDAO');
		$trackDirectorSubmissionDao->deleteDecisionsByPaper($paperId);
		$trackDirectorSubmissionDao->deleteReviewStagesByPaper($paperId);

		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignmentDao->deleteReviewAssignmentsByPaper($paperId);

		$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');
		$editAssignmentDao->deleteEditAssignmentsByPaper($paperId);

		$paperCommentDao =& DAORegistry::getDAO('PaperCommentDAO');
		$paperCommentDao->deletePaperComments($paperId);

		$paperGalleyDao =& DAORegistry::getDAO('PaperGalleyDAO');
		$paperGalleyDao->deleteGalleysByPaper($paperId);

		$paperSearchDao =& DAORegistry::getDAO('PaperSearchDAO');
		$paperSearchDao->deletePaperKeywords($paperId);

		$paperEventLogDao =& DAORegistry::getDAO('PaperEventLogDAO');
		$paperEventLogDao->deletePaperLogEntries($paperId);

		$paperEmailLogDao =& DAORegistry::getDAO('PaperEmailLogDAO');
		$paperEmailLogDao->deletePaperLogEntries($paperId);

		$paperEventLogDao =& DAORegistry::getDAO('PaperEventLogDAO');
		$paperEventLogDao->deletePaperLogEntries($paperId);

		$suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
		$suppFileDao->deleteSuppFilesByPaper($paperId);

		// Delete paper files -- first from the filesystem, then from the database
		import('file.PaperFileManager');
		$paperFileDao =& DAORegistry::getDAO('PaperFileDAO');
		$paperFiles =& $paperFileDao->getPaperFilesByPaper($paperId);

		$paperFileManager = new PaperFileManager($paperId);
		foreach ($paperFiles as $paperFile) {
			$paperFileManager->deleteFile($paperFile->getFileId());
		}

		$paperFileDao->deletePaperFiles($paperId);

		$this->update('DELETE FROM paper_settings WHERE paper_id = ?', $paperId);
		$this->update('DELETE FROM papers WHERE paper_id = ?', $paperId);
	}

	/**
	 * Get all papers for a scheduled conference.
	 * @param $userId int
	 * @param $schedConfId int
	 * @return DAOResultFactory containing matching Papers
	 */
	function &getPapersBySchedConfId($schedConfId, $trackId = null) {
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

		if ($trackId) $params[] = $trackId;

		$papers = array();
		$result =& $this->retrieve(
			'SELECT	p.*,
				COALESCE(ttl.setting_value, ttpl.setting_value) AS track_title,
				COALESCE(tal.setting_value, tapl.setting_value) AS track_abbrev
			FROM	papers p
				LEFT JOIN tracks t ON t.track_id = p.track_id
				LEFT JOIN track_settings ttpl ON (t.track_id = ttpl.track_id AND ttpl.setting_name = ? AND ttpl.locale = ?)
				LEFT JOIN track_settings ttl ON (t.track_id = ttl.track_id AND ttl.setting_name = ? AND ttl.locale = ?)
				LEFT JOIN track_settings tapl ON (t.track_id = tapl.track_id AND tapl.setting_name = ? AND tapl.locale = ?)
				LEFT JOIN track_settings tal ON (t.track_id = tal.track_id AND tal.setting_name = ? AND tal.locale = ?)
				WHERE p.sched_conf_id = ?' .
				($trackId ? ' AND p.track_id = ?' : ''),
			$params
		);

		$returner = new DAOResultFactory($result, $this, '_returnPaperFromRow');
		return $returner;
	}

	/**
	 * Delete all papers by scheduled conference ID.
	 * @param $schedConfId int
	 */
	function deletePapersBySchedConfId($schedConfId) {
		$papers = $this->getPapersBySchedConfId($schedConfId);

		while (!$papers->eof()) {
			$paper =& $papers->next();
			$this->deletePaperById($paper->getId());
		}
	}

	/**
	 * Get all papers for a user.
	 * @param $userId int
	 * @param $schedConfId int optional
	 * @return array Papers
	 */
	function &getPapersByUserId($userId, $schedConfId = null) {
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
			$userId
		);

		if ($schedConfId) $params[] = $schedConfId;

		$papers = array();

		$result =& $this->retrieve(
			'SELECT p.*,
				COALESCE(ttl.setting_value, ttpl.setting_value) AS track_title,
				COALESCE(tal.setting_value, tapl.setting_value) AS track_abbrev
			FROM papers p
				LEFT JOIN tracks t ON t.track_id = p.track_id
				LEFT JOIN track_settings ttpl ON (t.track_id = ttpl.track_id AND ttpl.setting_name = ? AND ttpl.locale = ?)
				LEFT JOIN track_settings ttl ON (t.track_id = ttl.track_id AND ttl.setting_name = ? AND ttl.locale = ?)
				LEFT JOIN track_settings tapl ON (t.track_id = tapl.track_id AND tapl.setting_name = ? AND tapl.locale = ?)
				LEFT JOIN track_settings tal ON (t.track_id = tal.track_id AND tal.setting_name = ? AND tal.locale = ?)
			WHERE p.user_id = ?' . (isset($schedConfId)?' AND p.sched_conf_id = ?':''),
			$params
		);

		while (!$result->EOF) {
			$papers[] =& $this->_returnPaperFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $papers;
	}

	/**
	 * Get the ID of the scheduled conference a paper is in.
	 * @param $paperId int
	 * @return int
	 */
	function getPaperSchedConfId($paperId) {
		$result =& $this->retrieve(
			'SELECT sched_conf_id FROM papers WHERE paper_id = ?', $paperId
		);
		$returner = isset($result->fields[0]) ? $result->fields[0] : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Check if the specified incomplete submission exists.
	 * @param $paperId int
	 * @param $userId int
	 * @param $schedConfId int
	 * @return int the submission progress
	 */
	function incompleteSubmissionExists($paperId, $userId, $schedConfId) {
		$result =& $this->retrieve(
			'SELECT submission_progress FROM papers WHERE paper_id = ? AND user_id = ? AND sched_conf_id = ? AND date_submitted IS NULL',
			array($paperId, $userId, $schedConfId)
		);
		$returner = isset($result->fields[0]) ? $result->fields[0] : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Change the status of the paper
	 * @param $paperId int
	 * @param $status int
	 */
	function changePaperStatus($paperId, $status) {
		$this->update(
			'UPDATE papers SET status = ? WHERE paper_id = ?', array($status, $paperId)
		);
	}

	/**
	 * Removes papers from a track by track ID
	 * @param $trackId int
	 */
	function removePapersFromTrack($trackId) {
		$this->update(
			'UPDATE papers SET track_id = null WHERE track_id = ?', $trackId
		);
	}

	/**
	 * Get the ID of the last inserted paper.
	 * @return int
	 */
	function getInsertPaperId() {
		return $this->getInsertId('papers', 'paper_id');
	}
}

?>
