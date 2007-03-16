<?php

/**
 * PaperDAO.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package paper
 *
 * Class for Paper DAO.
 * Operations for retrieving and modifying Paper objects.
 *
 * $Id$
 */

import('paper.Paper');

class PaperDAO extends DAO {

	var $presenterDao;

	/**
	 * Constructor.
	 */
	function PaperDAO() {
		parent::DAO();
		$this->presenterDao = &DAORegistry::getDAO('PresenterDAO');
	}
	
	/**
	 * Retrieve a paper by ID.
	 * @param $paperId int
	 * @return Paper
	 */
	function &getPaper($paperId) {
		$result = &$this->retrieve(
			'SELECT p.*,
				t.title AS track_title,
				t.title_alt1 AS track_title_alt1,
				t.title_alt2 AS track_title_alt2,
				t.abbrev AS track_abbrev,
				t.abbrev_alt1 AS track_abbrev_alt1,
				t.abbrev_alt2 AS track_abbrev_alt2
			FROM papers p
				LEFT JOIN tracks t ON t.track_id = p.track_id
			WHERE paper_id = ?', $paperId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnPaperFromRow($result->GetRowAssoc(false));
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
		$paper = &new Paper();
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
		$schedConfDao = &DAORegistry::getDAO('SchedConfDAO');
		$schedConf = &$schedConfDao->getSchedConf($schedConfId);
		$conferenceId = $schedConf->getConferenceId();
		
		$paper->setPaperId($row['paper_id']);
		$paper->setUserId($row['user_id']);
		$paper->setSchedConfId($row['sched_conf_id']);
		$paper->setTrackId($row['track_id']);

		// Localize track title & abbreviation.
		static $alternateLocaleNum;
		if (!isset($alternateLocaleNum)) {
			$alternateLocaleNum = Locale::isAlternateConferenceLocale($conferenceId);
		}
		$trackTitle = $trackAbbrev = null;
		switch ($alternateLocaleNum) {
			case 1:
				$trackTitle = $row['track_title_alt1'];
				$trackAbbrev = $row['track_abbrev_alt1'];
				break;
			case 2:
				$trackTitle = $row['track_title_alt2'];
				$trackAbbrev = $row['track_abbrev_alt2'];
				break;
		}
		if (empty($trackTitle)) $trackTitle = $row['track_title'];
		if (empty($trackAbbrev)) $trackAbbrev = $row['track_abbrev'];

		$paper->setTrackTitle($trackTitle);
		$paper->setTrackAbbrev($trackAbbrev);

		$paper->setTitle($row['title']);
		$paper->setTitleAlt1($row['title_alt1']);
		$paper->setTitleAlt2($row['title_alt2']);
		$paper->setAbstract($row['abstract']);
		$paper->setAbstractAlt1($row['abstract_alt1']);
		$paper->setAbstractAlt2($row['abstract_alt2']);
		$paper->setPaperType($row['paper_type']);
		$paper->setDiscipline($row['discipline']);
		$paper->setSubjectClass($row['subject_class']);
		$paper->setSubject($row['subject']);
		$paper->setCoverageGeo($row['coverage_geo']);
		$paper->setCoverageChron($row['coverage_chron']);
		$paper->setCoverageSample($row['coverage_sample']);
		$paper->setType($row['type']);
		$paper->setLanguage($row['language']);
		$paper->setSponsor($row['sponsor']);
		$paper->setCommentsToDirector($row['comments_to_dr']);
		$paper->setDateSubmitted($this->datetimeFromDB($row['date_submitted']));
		$paper->setDateStatusModified($this->datetimeFromDB($row['date_status_modified']));
		$paper->setLastModified($this->datetimeFromDB($row['last_modified']));
		$paper->setDateReminded($this->datetimeFromDB($row['date_reminded']));
		$paper->setStatus($row['status']);
		$paper->setSubmissionProgress($row['submission_progress']);
		$paper->setCurrentStage($row['current_stage']);
		$paper->setSubmissionFileId($row['submission_file_id']);
		$paper->setRevisedFileId($row['revised_file_id']);
		$paper->setReviewFileId($row['review_file_id']);
		$paper->setLayoutFileId($row['layout_file_id']);
		$paper->setDirectorFileId($row['director_file_id']);
		$paper->setPages($row['pages']);
		
		$paper->setPresenters($this->presenterDao->getPresentersByPaper($row['paper_id']));
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
				 title,
				 title_alt1,
				 title_alt2,
				 abstract,
				 abstract_alt1,
				 abstract_alt2,
				 paper_type,
				 discipline,
				 subject_class,
				 subject,
				 coverage_geo,
				 coverage_chron,
				 coverage_sample,
				 type,
				 language,
				 sponsor,
				 comments_to_dr,
				 date_submitted,
				 date_status_modified,
				 last_modified,
				 status,
				 submission_progress,
				 current_stage,
				 submission_file_id,
				 revised_file_id,
				 review_file_id,
				 layout_file_id,
				 director_file_id,
				 pages,
				 date_reminded)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, %s, %s, %s, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
				$this->datetimeToDB($paper->getDateSubmitted()), $this->datetimeToDB($paper->getDateStatusModified()), $this->datetimeToDB($paper->getLastModified())),
			array(
				$paper->getUserId(),
				$paper->getSchedConfId(),
				$paper->getTrackId(),
				$paper->getTitle() === null ? '' : $paper->getTitle(),
				$paper->getTitleAlt1(),
				$paper->getTitleAlt2(),
				$paper->getAbstract(),
				$paper->getAbstractAlt1(),
				$paper->getAbstractAlt2(),
				$paper->getPaperType(),
				$paper->getDiscipline(),
				$paper->getSubjectClass(),
				$paper->getSubject(),
				$paper->getCoverageGeo(),
				$paper->getCoverageChron(),
				$paper->getCoverageSample(),
				$paper->getType(),
				$paper->getLanguage(),
				$paper->getSponsor(),
				$paper->getCommentsToDirector(),
				$paper->getStatus() === null ? SUBMISSION_STATUS_QUEUED : $paper->getStatus(),
				$paper->getSubmissionProgress() === null ? 1 : $paper->getSubmissionProgress(),
				$paper->getCurrentStage(),
				$paper->getSubmissionFileId(),
				$paper->getRevisedFileId(),
				$paper->getReviewFileId(),
				$paper->getLayoutFileId(),
				$paper->getDirectorFileId(),
				$paper->getPages(),
				$paper->getDateReminded()
			)
		);
		
		$paper->setPaperId($this->getInsertPaperId());
		
		// Insert presenters for this paper
		$presenters = &$paper->getPresenters();
		for ($i=0, $count=count($presenters); $i < $count; $i++) {
			$presenters[$i]->setPaperId($paper->getPaperId());
			$this->presenterDao->insertPresenter($presenters[$i]);
		}
		
		return $paper->getPaperId();
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
					title = ?,
					title_alt1 = ?,
					title_alt2 = ?,
					abstract = ?,
					abstract_alt1 = ?,
					abstract_alt2 = ?,
					paper_type = ?,
					discipline = ?,
					subject_class = ?,
					subject = ?,
					coverage_geo = ?,
					coverage_chron = ?,
					coverage_sample = ?,
					type = ?,
					language = ?,
					sponsor = ?,
					comments_to_dr = ?,
					date_submitted = %s,
					date_status_modified = %s,
					last_modified = %s,
					status = ?,
					submission_progress = ?,
					current_stage = ?,
					submission_file_id = ?,
					revised_file_id = ?,
					review_file_id = ?,
					layout_file_id = ?,
					director_file_id = ?,
					pages = ?,
					date_reminded = ?
				WHERE paper_id = ?',
				$this->datetimeToDB($paper->getDateSubmitted()), $this->datetimeToDB($paper->getDateStatusModified()), $this->datetimeToDB($paper->getLastModified())),
			array(
				$paper->getUserId(),
				$paper->getTrackId(),
				$paper->getTitle(),
				$paper->getTitleAlt1(),
				$paper->getTitleAlt2(),
				$paper->getAbstract(),
				$paper->getAbstractAlt1(),
				$paper->getAbstractAlt2(),
				$paper->getPaperType(),
				$paper->getDiscipline(),
				$paper->getSubjectClass(),
				$paper->getSubject(),
				$paper->getCoverageGeo(),
				$paper->getCoverageChron(),
				$paper->getCoverageSample(),
				$paper->getType(),
				$paper->getLanguage(),
				$paper->getSponsor(),
				$paper->getCommentsToDirector(),
				$paper->getStatus(),
				$paper->getSubmissionProgress(),
				$paper->getCurrentStage(),
				$paper->getSubmissionFileId(),
				$paper->getRevisedFileId(),
				$paper->getReviewFileId(),
				$paper->getLayoutFileId(),
				$paper->getDirectorFileId(),
				$paper->getPages(),
				$paper->getDateReminded(),
				$paper->getPaperId()
			)
		);
		
		// update presenters for this paper
		$presenters = &$paper->getPresenters();
		for ($i=0, $count=count($presenters); $i < $count; $i++) {
			if ($presenters[$i]->getPresenterId() > 0) {
				$this->presenterDao->updatePresenter($presenters[$i]);
			} else {
				$this->presenterDao->insertPresenter($presenters[$i]);
			}
		}
		
		// Remove deleted presenters
		$removedPresenters = $paper->getRemovedPresenters();
		for ($i=0, $count=count($removedPresenters); $i < $count; $i++) {
			$this->presenterDao->deletePresenterById($removedPresenters[$i], $paper->getPaperId());
		}
		
		// Update presenter sequence numbers
		$this->presenterDao->resequencePresenters($paper->getPaperId());
	}
	
	/**
	 * Delete a paper.
	 * @param $paper Paper
	 */
	function deletePaper(&$paper) {
		return $this->deletePaperById($paper->getPaperId());
	}
	
	/**
	 * Delete a paper by ID.
	 * @param $paperId int
	 */
	function deletePaperById($paperId) {
		$this->presenterDao->deletePresentersByPaper($paperId);

		/*$publishedPaperDao = &DAORegistry::getDAO('PublishedPaperDAO');
		$publishedPaperDao->deletePublishedPaperByPaperId($paperId);*/

		$commentDao = &DAORegistry::getDAO('CommentDAO');
		$commentDao->deleteCommentsByPaper($paperId);

		$paperNoteDao = &DAORegistry::getDAO('PaperNoteDAO');
		$paperNoteDao->clearAllPaperNotes($paperId);

		$trackDirectorSubmissionDao = &DAORegistry::getDAO('TrackDirectorSubmissionDAO');
		$trackDirectorSubmissionDao->deleteDecisionsByPaper($paperId);
		$trackDirectorSubmissionDao->deleteReviewStagesByPaper($paperId);

		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignmentDao->deleteReviewAssignmentsByPaper($paperId);

		$editAssignmentDao = &DAORegistry::getDAO('EditAssignmentDAO');
		$editAssignmentDao->deleteEditAssignmentsByPaper($paperId);

		$paperCommentDao = &DAORegistry::getDAO('PaperCommentDAO');
		$paperCommentDao->deletePaperComments($paperId);

		$paperGalleyDao = &DAORegistry::getDAO('PaperGalleyDAO');
		$paperGalleyDao->deleteGalleysByPaper($paperId);

		$paperSearchDao = &DAORegistry::getDAO('PaperSearchDAO');
		$paperSearchDao->deletePaperKeywords($paperId);

		$paperEventLogDao = &DAORegistry::getDAO('PaperEventLogDAO');
		$paperEventLogDao->deletePaperLogEntries($paperId);

		$paperEmailLogDao = &DAORegistry::getDAO('PaperEmailLogDAO');
		$paperEmailLogDao->deletePaperLogEntries($paperId);

		$paperEventLogDao = &DAORegistry::getDAO('PaperEventLogDAO');
		$paperEventLogDao->deletePaperLogEntries($paperId);

		$suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		$suppFileDao->deleteSuppFilesByPaper($paperId);

		// Delete paper files -- first from the filesystem, then from the database
		import('file.PaperFileManager');
		$paperFileDao = &DAORegistry::getDAO('PaperFileDAO');
		$paperFiles = &$paperFileDao->getPaperFilesByPaper($paperId);
	
		$paperFileManager = &new PaperFileManager($paperId);
		foreach ($paperFiles as $paperFile) {
			$paperFileManager->deleteFile($paperFile->getFileId());
		}

		$paperFileDao->deletePaperFiles($paperId);

		$this->update(
			'DELETE FROM papers WHERE paper_id = ?', $paperId
		);
	}
	
	/**
	 * Get all papers for a scheduled conference.
	 * @param $userId int
	 * @param $schedConfId int
	 * @return DAOResultFactory containing matching Papers
	 */
	function &getPapersBySchedConfId($schedConfId, $trackId = null) {
		$papers = array();
		
		$result = &$this->retrieve(
			'SELECT p.*,
				t.title AS track_title,
				t.title_alt1 AS track_title_alt1,
				t.title_alt2 AS track_title_alt2,
				t.abbrev AS track_abbrev,
				t.abbrev_alt1 AS track_abbrev_alt1,
				t.abbrev_alt2 AS track_abbrev_alt2
			FROM papers p
				LEFT JOIN tracks t ON t.track_id = p.track_id
				WHERE p.sched_conf_id = ?' .
				($trackId ? ' AND p.track_id = ?' : ''),
				($trackId ? array($schedConfId, $trackId) : $schedConfId));
		
		$returner = &new DAOResultFactory($result, $this, '_returnPaperFromRow');
		return $returner;
	}

	/**
	 * Delete all papers by scheduled conference ID.
	 * @param $schedConfId int
	 */
	function deletePapersBySchedConfId($schedConfId) {
		$papers = $this->getPapersBySchedConfId($schedConfId);
		
		while (!$papers->eof()) {
			$paper = &$papers->next();
			$this->deletePaperById($paper->getPaperId());
		}
	}

	/**
	 * Get all papers for a user.
	 * @param $userId int
	 * @param $schedConfId int optional
	 * @return array Papers
	 */
	function &getPapersByUserId($userId, $schedConfId = null) {
		$papers = array();
		
		$result = &$this->retrieve(
			'SELECT p.*,
				t.title AS track_title,
				t.title_alt1 AS track_title_alt1,
				t.title_alt2 AS track_title_alt2,
				t.abbrev AS track_abbrev,
				t.abbrev_alt1 AS track_abbrev_alt1,
				t.abbrev_alt2 AS track_abbrev_alt2
			FROM papers p
				LEFT JOIN tracks t ON t.track_id = p.track_id
			WHERE p.user_id = ?', (isset($schedConfId)?' AND p.sched_conf_id = ?':''),
				isset($schedConfId)?array($userId, $schedConfId):$userId
		);
		
		while (!$result->EOF) {
			$papers[] = &$this->_returnPaperFromRow($result->GetRowAssoc(false));
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
		$result = &$this->retrieve(
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
		$result = &$this->retrieve(
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
