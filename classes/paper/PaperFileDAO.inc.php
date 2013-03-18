<?php

/**
 * @file PaperFileDAO.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PaperFileDAO
 * @ingroup paper
 * @see PaperFile
 *
 * @brief Operations for retrieving and modifying PaperFile objects.
 */


import('classes.paper.PaperFile');

class PaperFileDAO extends DAO {
	/**
	 * Array of MIME types that can be displayed inline in a browser
	 */
	var $inlineableTypes;

	/**
	 * Retrieve a paper by ID.
	 * @param $fileId int
	 * @param $revision int optional, if omitted latest revision is used
	 * @param $paperId int optional
	 * @return PaperFile
	 */
	function &getPaperFile($fileId, $revision = null, $paperId = null) {
		if ($fileId === null) {
			$returner = null;
			return $returner;
		}
		if ($revision == null) {
			if ($paperId != null) {
				$result =& $this->retrieveLimit(
					'SELECT a.* FROM paper_files a WHERE file_id = ? AND paper_id = ? ORDER BY revision DESC',
					array($fileId, $paperId),
					1
				);
			} else {
				$result =& $this->retrieveLimit(
					'SELECT a.* FROM paper_files a WHERE file_id = ? ORDER BY revision DESC',
					$fileId,
					1
				);
			}

		} else {
			if ($paperId != null) {
				$result =& $this->retrieve(
					'SELECT a.* FROM paper_files a WHERE file_id = ? AND revision = ? AND paper_id = ?',
					array($fileId, $revision, $paperId)
				);
			} else {
				$result =& $this->retrieve(
					'SELECT a.* FROM paper_files a WHERE file_id = ? AND revision = ?',
					array($fileId, $revision)
				);
			}
		}

		$returner = null;
		if (isset($result) && $result->RecordCount() != 0) {
			$returner =& $this->_returnPaperFileFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve all revisions of a paper file.
	 * @param $paperId int
	 * @return PaperFile
	 */
	function &getPaperFileRevisions($fileId, $round = null) {
		if ($fileId === null) {
			$returner = null;
			return $returner;
		}
		$paperFiles = array();

		$params = array($fileId);
		if ($round !== null) $params[] = $round;

		$result =& $this->retrieve(
			'SELECT a.* FROM paper_files a WHERE file_id = ? ' .
			($round!==null?'AND a.round = ? ':'') .
			'ORDER BY revision',
			array($params)
		);

		while (!$result->EOF) {
			$paperFiles[] =& $this->_returnPaperFileFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $paperFiles;
	}

	/**
	 * Retrieve revisions of a paper file in a range.
	 * @param $paperId int
	 * @return PaperFile
	 */
	function &getPaperFileRevisionsInRange($fileId, $start = 1, $end = null) {
		if ($fileId === null) {
			$returner = null;
			return $returner;
		}
		$paperFiles = array();

		if ($end == null) {
			$result =& $this->retrieve(
				'SELECT a.* FROM paper_files a WHERE file_id = ? AND revision >= ?',
				array($fileId, $start)
			);
		} else {
			$result =& $this->retrieve(
				'SELECT a.* FROM paper_files a WHERE file_id = ? AND revision >= ? AND revision <= ?',
				array($fileId, $start, $end)
			);
		}

		while (!$result->EOF) {
			$paperFiles[] =& $this->_returnPaperFileFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $paperFiles;
	}

	/**
	 * Retrieve the current revision number for a file.
	 * @param $fileId int
	 * @return int
	 */
	function &getRevisionNumber($fileId) {
		if ($fileId === null) {
			$returner = null;
			return $returner;
		}
		$result =& $this->retrieve(
			'SELECT MAX(revision) AS max_revision FROM paper_files a WHERE file_id = ?',
			$fileId
		);

		if ($result->RecordCount() == 0) {
			$returner = null;
		} else {
			$row = $result->FetchRow();
			$returner = $row['max_revision'];
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve all paper files for a paper.
	 * @param $paperId int
	 * @return array PaperFiles
	 */
	function &getPaperFilesByPaper($paperId) {
		$paperFiles = array();

		$result =& $this->retrieve(
			'SELECT * FROM paper_files WHERE paper_id = ?',
			$paperId
		);

		while (!$result->EOF) {
			$paperFiles[] =& $this->_returnPaperFileFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $paperFiles;
	}

	/**
	 * Internal function to return an PaperFile object from a row.
	 * @param $row array
	 * @return PaperFile
	 */
	function &_returnPaperFileFromRow(&$row) {
		$paperFile = new PaperFile();
		$paperFile->setFileId($row['file_id']);
		$paperFile->setRevision($row['revision']);
		$paperFile->setPaperId($row['paper_id']);
		$paperFile->setFileName($row['file_name']);
		$paperFile->setFileType($row['file_type']);
		$paperFile->setFileSize($row['file_size']);
		$paperFile->setOriginalFileName($row['original_file_name']);
		$paperFile->setFileStage($row['file_stage']);
		$paperFile->setRound($row['round']);
		$paperFile->setDateUploaded($this->datetimeFromDB($row['date_uploaded']));
		$paperFile->setDateModified($this->datetimeFromDB($row['date_modified']));
		$paperFile->setViewable($row['viewable']);
		HookRegistry::call('PaperFileDAO::_returnPaperFileFromRow', array(&$paperFile, &$row));
		return $paperFile;
	}

	/**
	 * Insert a new PaperFile.
	 * @param $paperFile PaperFile
	 * @return int
	 */
	function insertPaperFile(&$paperFile) {
		$fileId = $paperFile->getFileId();
		$params = array(
			$paperFile->getRevision() === null ? 1 : $paperFile->getRevision(),
			(int) $paperFile->getPaperId(),
			$paperFile->getFileName(),
			$paperFile->getFileType(),
			$paperFile->getFileSize(),
			$paperFile->getOriginalFileName(),
			$paperFile->getFileStage(),
			(int) $paperFile->getRound(),
			$paperFile->getViewable()
		);

		if ($fileId) {
			array_unshift($params, $fileId);
		}

		$this->update(
			sprintf('INSERT INTO paper_files
				(' . ($fileId ? 'file_id, ' : '') . 'revision, paper_id, file_name, file_type, file_size, original_file_name, file_stage, round, date_uploaded, date_modified, viewable)
				VALUES
				(' . ($fileId ? '?, ' : '') . '?, ?, ?, ?, ?, ?, ?, ?, %s, %s, ?)',
				$this->datetimeToDB($paperFile->getDateUploaded()), $this->datetimeToDB($paperFile->getDateModified())),
			$params
		);

		if (!$fileId) {
			$paperFile->setFileId($this->getInsertId());
		}

		return $paperFile->getFileId();
	}

	/**
	 * Update an existing paper file.
	 * @param $paper PaperFile
	 */
	function updatePaperFile(&$paperFile) {
		$this->update(
			sprintf('UPDATE paper_files
				SET
					paper_id = ?,
					file_name = ?,
					file_type = ?,
					file_size = ?,
					original_file_name = ?,
					file_stage = ?,
					round = ?,
					date_uploaded = %s,
					date_modified = %s,
					viewable = ?
				WHERE file_id = ? AND revision = ?',
				$this->datetimeToDB($paperFile->getDateUploaded()), $this->datetimeToDB($paperFile->getDateModified())),
			array(
				(int) $paperFile->getPaperId(),
				$paperFile->getFileName(),
				$paperFile->getFileType(),
				$paperFile->getFileSize(),
				$paperFile->getOriginalFileName(),
				$paperFile->getFileStage(),
				(int) $paperFile->getRound(),
				$paperFile->getViewable(),
				$paperFile->getFileId(),
				$paperFile->getRevision()
			)
		);

		return $paperFile->getFileId();

	}

	/**
	 * Delete a paper file.
	 * @param $paper PaperFile
	 */
	function deletePaperFile(&$paperFile) {
		return $this->deletePaperFileById($paperFile->getFileId(), $paperFile->getRevision());
	}

	/**
	 * Delete a paper file by ID.
	 * @param $paperId int
	 * @param $revision int
	 */
	function deletePaperFileById($fileId, $revision = null) {
		if ($revision == null) {
			return $this->update(
				'DELETE FROM paper_files WHERE file_id = ?', $fileId
			);
		} else {
			return $this->update(
				'DELETE FROM paper_files WHERE file_id = ? AND revision = ?', array($fileId, $revision)
			);
		}
	}

	/**
	 * Delete all paper files for a paper.
	 * @param $paperId int
	 */
	function deletePaperFiles($paperId) {
		return $this->update(
			'DELETE FROM paper_files WHERE paper_id = ?', $paperId
		);
	}

	/**
	 * Get the ID of the last inserted paper file.
	 * @return int
	 */
	function getInsertId() {
		return $this->_getInsertId('paper_files', 'file_id');
	}

	/**
	 * Check whether a file may be displayed inline.
	 * @param $paperFile object
	 * @return boolean
	 */
	function isInlineable(&$paperFile) {
		if (!isset($this->inlineableTypes)) {
			$this->inlineableTypes = array_filter(file(Core::getBaseDir() . '/lib/pkp/registry/inlineTypes.txt'), create_function('&$a', 'return ($a = trim($a)) && !empty($a) && $a[0] != \'#\';'));
		}
		return in_array($paperFile->getFileType(), $this->inlineableTypes);
	}
}

?>
