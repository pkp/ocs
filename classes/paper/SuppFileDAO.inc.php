<?php

/**
 * @file SuppFileDAO.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SuppFileDAO
 * @ingroup paper
 * @see SuppFile
 *
 * @brief Operations for retrieving and modifying SuppFile objects.
 */

//$Id$

import('paper.SuppFile');

class SuppFileDAO extends DAO {
	/**
	 * Retrieve a supplementary file by ID.
	 * @param $suppFileId int
	 * @param $paperId int optional
	 * @return SuppFile
	 */
	function &getSuppFile($suppFileId, $paperId = null) {
		$params = array($suppFileId);
		if ($paperId) $params[] = $paperId;

		$result =& $this->retrieve(
			'SELECT s.*, a.file_name, a.original_file_name, a.file_type, a.file_size, a.date_uploaded, a.date_modified FROM paper_supplementary_files s LEFT JOIN paper_files a ON (s.file_id = a.file_id) WHERE s.supp_id = ?' . ($paperId?' AND s.paper_id = ?':''),
			$params
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnSuppFileFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve a supplementary file by public supp file ID.
	 * @param $publicSuppId string
	 * @param $paperId int
	 * @return SuppFile
	 */
	function &getSuppFileByPublicSuppFileId($publicSuppId, $paperId) {
		$result =& $this->retrieve(
			'SELECT s.*, a.file_name, a.original_file_name, a.file_type, a.file_size, a.date_uploaded, a.date_modified FROM paper_supplementary_files s LEFT JOIN paper_files a ON (s.file_id = a.file_id) WHERE s.public_supp_file_id = ? AND s.paper_id = ?',
			array($publicSuppId, $paperId)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnSuppFileFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve all supplementary files for a paper.
	 * @param $paperId int
	 * @return array SuppFiles
	 */
	function &getSuppFilesByPaper($paperId) {
		$suppFiles = array();

		$result =& $this->retrieve(
			'SELECT s.*, a.file_name, a.original_file_name, a.file_type, a.file_size, a.date_uploaded, a.date_modified FROM paper_supplementary_files s LEFT JOIN paper_files a ON (s.file_id = a.file_id) WHERE s.paper_id = ? ORDER BY s.seq',
			$paperId
		);

		while (!$result->EOF) {
			$suppFiles[] =& $this->_returnSuppFileFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $suppFiles;
	}

	/**
	 * Get the list of fields for which data is localized.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('title', 'creator', 'subject', 'typeOther', 'description', 'publisher', 'sponsor', 'source');
	}

	/**
	 * Update the localized fields for this supp file.
	 * @param $suppFile
	 */
	function updateLocaleFields(&$suppFile) {
		$this->updateDataObjectSettings('paper_supp_file_settings', $suppFile, array(
			'supp_id' => $suppFile->getId()
		));
	}

	/**
	 * Internal function to return a SuppFile object from a row.
	 * @param $row array
	 * @return SuppFile
	 */
	function &_returnSuppFileFromRow(&$row) {
		$suppFile = new SuppFile();
		$suppFile->setSuppFileID($row['supp_id']);
		$suppFile->setPublicSuppFileID($row['public_supp_file_id']);
		$suppFile->setFileId($row['file_id']);
		$suppFile->setPaperId($row['paper_id']);
		$suppFile->setType($row['type']);
		$suppFile->setDateCreated($this->dateFromDB($row['date_created']));
		$suppFile->setLanguage($row['language']);
		$suppFile->setShowReviewers($row['show_reviewers']);
		$suppFile->setDateSubmitted($this->datetimeFromDB($row['date_submitted']));
		$suppFile->setSequence($row['seq']);

		//PaperFile set methods
		$suppFile->setFileName($row['file_name']);
		$suppFile->setOriginalFileName($row['original_file_name']);
		$suppFile->setFileType($row['file_type']);
		$suppFile->setFileSize($row['file_size']);
		$suppFile->setDateModified($this->datetimeFromDB($row['date_modified']));
		$suppFile->setDateUploaded($this->datetimeFromDB($row['date_uploaded']));

		$this->getDataObjectSettings('paper_supp_file_settings', 'supp_id', $row['supp_id'], $suppFile);

		HookRegistry::call('SuppFileDAO::_returnSuppFileFromRow', array(&$suppFile, &$row));

		return $suppFile;
	}

	/**
	 * Insert a new SuppFile.
	 * @param $suppFile SuppFile
	 */	
	function insertSuppFile(&$suppFile) {
		if ($suppFile->getDateSubmitted() == null) {
			$suppFile->setDateSubmitted(Core::getCurrentDate());
		}
		if ($suppFile->getSequence() == null) {
			$suppFile->setSequence($this->getNextSuppFileSequence($suppFile->getPaperId()));
		}
		$this->update(
			sprintf('INSERT INTO paper_supplementary_files
				(public_supp_file_id, file_id, paper_id, type, date_created, language, show_reviewers, date_submitted, seq)
				VALUES
				(?, ?, ?, ?, %s, ?, ?, %s, ?)',
				$this->dateToDB($suppFile->getDateCreated()), $this->datetimeToDB($suppFile->getDateSubmitted())),
			array(
				$suppFile->getPublicSuppFileId(),
				$suppFile->getFileId(),
				$suppFile->getPaperId(),
				$suppFile->getType(),
				$suppFile->getLanguage(),
				$suppFile->getShowReviewers(),
				$suppFile->getSequence()
			)
		);
		$suppFile->setId($this->getInsertSuppFileId());
		$this->updateLocaleFields($suppFile);
		return $suppFile->getId();
	}

	/**
	 * Update an existing SuppFile.
	 * @param $suppFile SuppFile
	 */
	function updateSuppFile(&$suppFile) {
		$returner = $this->update(
			sprintf('UPDATE paper_supplementary_files
				SET
					public_supp_file_id = ?,
					file_id = ?,
					type = ?,
					date_created = %s,
					language = ?,
					show_reviewers = ?,
					seq = ?
				WHERE supp_id = ?',
				$this->dateToDB($suppFile->getDateCreated())),
			array(
				$suppFile->getPublicSuppFileId(),
				$suppFile->getFileId(),
				$suppFile->getType(),
				$suppFile->getLanguage(),
				$suppFile->getShowReviewers(),
				$suppFile->getSequence(),
				$suppFile->getId()
			)
		);
		$this->updateLocaleFields($suppFile);
		return $returner;
	}

	/**
	 * Delete a SuppFile.
	 * @param $suppFile SuppFile
	 */
	function deleteSuppFile(&$suppFile) {
		return $this->deleteSuppFileById($suppFile->getId());
	}

	/**
	 * Delete a supplementary file by ID.
	 * @param $suppFileId int
	 * @param $paperId int optional
	 */
	function deleteSuppFileById($suppFileId, $paperId = null) {
		if (isset($paperId)) {
			$returner = $this->update('DELETE FROM paper_supplementary_files WHERE supp_id = ? AND paper_id = ?', array($suppFileId, $paperId));
			if ($returner) $this->update('DELETE FROM paper_supp_file_settings WHERE supp_id = ?', $suppFileId);
			return $returner;

		} else {
			$this->update('DELETE FROM paper_supp_file_settings WHERE supp_id = ?', $suppFileId);
			return $this->update(
				'DELETE FROM paper_supplementary_files WHERE supp_id = ?', $suppFileId
			);
		}
	}

	/**
	 * Delete supplementary files by paper.
	 * @param $paperId int
	 */
	function deleteSuppFilesByPaper($paperId) {
		$suppFiles =& $this->getSuppFilesByPaper($paperId);
		foreach ($suppFiles as $suppFile) {
			$this->deleteSuppFile($suppFile);
		}
	}

	/**
	 * Check if a supplementary file exists with the associated file ID.
	 * @param $paperId int
	 * @param $fileId int
	 * @return boolean
	 */
	function suppFileExistsByFileId($paperId, $fileId) {
		$result =& $this->retrieve(
			'SELECT COUNT(*) FROM paper_supplementary_files
			WHERE paper_id = ? AND file_id = ?',
			array($paperId, $fileId)
		);

		$returner = isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Sequentially renumber supplementary files for a paper in their sequence order.
	 * @param $paperId int
	 */
	function resequenceSuppFiles($paperId) {
		$result =& $this->retrieve(
			'SELECT supp_id FROM paper_supplementary_files WHERE paper_id = ? ORDER BY seq',
			$paperId
		);

		for ($i=1; !$result->EOF; $i++) {
			list($suppId) = $result->fields;
			$this->update(
				'UPDATE paper_supplementary_files SET seq = ? WHERE supp_id = ?',
				array($i, $suppId)
			);
			$result->moveNext();
		}

		$result->close();
		unset($result);
	}

	/**
	 * Get the the next sequence number for a paper's supplementary files (i.e., current max + 1).
	 * @param $paperId int
	 * @return int
	 */
	function getNextSuppFileSequence($paperId) {
		$result =& $this->retrieve(
			'SELECT MAX(seq) + 1 FROM paper_supplementary_files WHERE paper_id = ?',
			$paperId
		);
		$returner = floor($result->fields[0]);

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Get the ID of the last inserted supplementary file.
	 * @return int
	 */
	function getInsertSuppFileId() {
		return $this->getInsertId('paper_supplementary_files', 'supp_id');
	}

	/**
	 * Retrieve supp file by public supp file id or, failing that,
	 * internal supp file ID; public ID takes precedence.
	 * @param $paperId int
	 * @param $suppId string
	 * @return SuppFile object
	 */
	function &getSuppFileByBestSuppFileId($paperId, $suppId) {
		$suppFile =& $this->getSuppFileByPublicSuppFileId($suppId, $paperId);
		if (!isset($suppFile) && ctype_digit("$suppId")) $suppFile =& $this->getSuppFile((int) $suppId, $paperId);
		return $suppFile;
	}

	/**
	 * Checks if public identifier exists
	 * @param $publicSuppFileId string
	 * @param $suppId int A supplemental file ID to exempt from the test
	 * @param $schedConfId int
	 * @return boolean
	 */
	function suppFileExistsByPublicId($publicSuppFileId, $suppId, $schedConfId) {
		$result =& $this->retrieve(
			'SELECT COUNT(*) FROM paper_supplementary_files f, papers a WHERE f.paper_id = a.paper_id AND f.public_supp_file_id = ? AND f.supp_id <> ? AND a.sched_conf_id = ?', array($publicSuppFileId, $suppId, $schedConfId)
		);
		$returner = $result->fields[0] ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}
}

?>
