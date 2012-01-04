<?php

/**
 * @file PaperFileManager.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PaperFileManager
 * @ingroup file
 *
 * @brief Class defining operations for paper file management.
 *
 * Paper directory structure:
 * [paper id]/note
 * [paper id]/public
 * [paper id]/submission
 * [paper id]/submission/original
 * [paper id]/submission/review
 * [paper id]/submission/director
 * [paper id]/submission/layout
 * [paper id]/supp
 */

//$Id$

import('file.FileManager');

/* File type suffixes */
define('PAPER_FILE_SUBMISSION',	'SM');
define('PAPER_FILE_REVIEW',		'RV');
define('PAPER_FILE_DIRECTOR',		'DR');
define('PAPER_FILE_LAYOUT',		'LE');
define('PAPER_FILE_PUBLIC',		'PB');
define('PAPER_FILE_SUPP',		'SP');
define('PAPER_FILE_NOTE',		'NT');

class PaperFileManager extends FileManager {

	/** @var string the path to location of the files */
	var $filesDir;

	/** @var int the ID of the associated paper */
	var $paperId;

	/** @var Paper the associated paper */
	var $paper;

	/**
	 * Constructor.
	 * Create a manager for handling paper file uploads.
	 * @param $paperId int
	 */
	function PaperFileManager($paperId) {
		$this->paperId = $paperId;
		$paperDao =& DAORegistry::getDAO('PaperDAO');
		$this->paper =& $paperDao->getPaper($paperId);
		$schedConfId = $this->paper->getSchedConfId();
		$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
		$schedConf =& $schedConfDao->getSchedConf($schedConfId);
		$this->filesDir = Config::getVar('files', 'files_dir') . '/conferences/' . $schedConf->getConferenceId() . '/schedConfs/' . $schedConfId .
		'/papers/' . $paperId . '/';
	}

	/**
	 * Upload a submission file.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @return int file ID, is false if failure
	 */
	function uploadSubmissionFile($fileName, $fileId = null, $overwrite = false) {
		return $this->handleUpload($fileName, PAPER_FILE_SUBMISSION, $fileId, $overwrite);
	}

	/**
	 * Write a submission file.
	 * @param $fileName string The original filename
	 * @param $contents string The contents to be written to the file
	 * @param $mimeType string The mime type of the original file
	 * @param $fileId int
	 * @param $overwrite boolean
	 */
	function writeSubmissionFile($fileName, &$contents, $mimeType, $fileId = null, $overwrite = true) {
		return $this->handleWrite($fileName, $contents, $mimeType, PAPER_FILE_SUBMISSION, $fileId, $overwrite);
	}

	/**
	 * Copy a submission file.
	 * @param $url string The source URL/filename
	 * @param $mimeType string The mime type of the original file
	 * @param $fileId int
	 * @param $overwrite boolean
	 */
	function copySubmissionFile($url, $mimeType, $fileId = null, $overwrite = true) {
		return $this->handleCopy($url, $mimeType, PAPER_FILE_SUBMISSION, $fileId, $overwrite);
	}

	/**
	 * Upload a file to the review file folder.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @return int file ID, is false if failure
	 */
	function uploadReviewFile($fileName, $fileId = null) {
		return $this->handleUpload($fileName, PAPER_FILE_REVIEW, $fileId);
	}

	/**
	 * Upload a file to the director decision file folder.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @return int file ID, is false if failure
	 */
	function uploadDirectorDecisionFile($fileName, $fileId = null) {
		return $this->handleUpload($fileName, PAPER_FILE_DIRECTOR, $fileId);
	}

	/**
	 * Upload a track director's layout editing file.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @param $overwrite boolean
	 * @return int file ID, is null if failure
	 */
	function uploadLayoutFile($fileName, $fileId = null, $overwrite = true) {
		return $this->handleUpload($fileName, PAPER_FILE_LAYOUT, $fileId, $overwrite);
	}	

	/**
	 * Upload a supp file.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @param $overwrite boolean
	 * @return int file ID, is false if failure
	 */
	function uploadSuppFile($fileName, $fileId = null, $overwrite = true) {
		return $this->handleUpload($fileName, PAPER_FILE_SUPP, $fileId, $overwrite);
	}	

	/**
	 * Upload a public file.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @param $overwrite boolean
	 * @return int file ID, is false if failure
	 */
	function uploadPublicFile($fileName, $fileId = null, $overwrite = true) {
		return $this->handleUpload($fileName, PAPER_FILE_PUBLIC, $fileId, $overwrite);
	}	

	/**
	 * Upload a note file.
	 * @param $fileName string the name of the file used in the POST form
	 * @param $fileId int
	 * @param $overwrite boolean
	 * @return int file ID, is false if failure
	 */
	function uploadSubmissionNoteFile($fileName, $fileId = null, $overwrite = true) {
		return $this->handleUpload($fileName, PAPER_FILE_NOTE, $fileId, $overwrite);
	}

	/**
	 * Write a public file.
	 * @param $fileName string The original filename
	 * @param $contents string The contents to be written to the file
	 * @param $mimeType string The mime type of the original file
	 * @param $fileId int
	 * @param $overwrite boolean
	 */
	function writePublicFile($fileName, &$contents, $mimeType, $fileId = null, $overwrite = true) {
		return $this->handleWrite($fileName, $contents, $mimeType, PAPER_FILE_PUBLIC, $fileId, $overwrite);
	}

	/**
	 * Copy a public file.
	 * @param $url string The source URL/filename
	 * @param $mimeType string The mime type of the original file
	 * @param $fileId int
	 * @param $overwrite boolean
	 */
	function copyPublicFile($url, $mimeType, $fileId = null, $overwrite = true) {
		return $this->handleCopy($url, $mimeType, PAPER_FILE_PUBLIC, $fileId, $overwrite);
	}

	/**
	 * Write a supplemental file.
	 * @param $fileName string The original filename
	 * @param $contents string The contents to be written to the file
	 * @param $mimeType string The mime type of the original file
	 * @param $fileId int
	 * @param $overwrite boolean
	 */
	function writeSuppFile($fileName, &$contents, $mimeType, $fileId = null, $overwrite = true) {
		return $this->handleWrite($fileName, $contents, $mimeType, PAPER_FILE_SUPP, $fileId, $overwrite);
	}

	/**
	 * Copy a supplemental file.
	 * @param $url string The source URL/filename
	 * @param $mimeType string The mime type of the original file
	 * @param $fileId int
	 * @param $overwrite boolean
	 */
	function copySuppFile($url, $mimeType, $fileId = null, $overwrite = true) {
		return $this->handleCopy($url, $mimeType, PAPER_FILE_SUPP, $fileId, $overwrite);
	}

	/**
	 * Retrieve file information by file ID.
	 * @return PaperFile
	 */
	function &getFile($fileId, $revision = null) {
		$paperFileDao =& DAORegistry::getDAO('PaperFileDAO');
		$paperFile =& $paperFileDao->getPaperFile($fileId, $revision, $this->paperId);
		return $paperFile;
	}

	/**
	 * Read a file's contents.
	 * @param $output boolean output the file's contents instead of returning a string
	 * @return boolean
	 */
	function readFile($fileId, $revision = null, $output = false) {
		$paperFile =& $this->getFile($fileId, $revision);

		if (isset($paperFile)) {
			$fileType = $paperFile->getFileType();
			$filePath = $this->filesDir . $paperFile->getType() . '/' . $paperFile->getFileName();

			return parent::readFile($filePath, $output);

		} else {
			return false;
		}
	}

	/**
	 * Delete a file by ID.
	 * If no revision is specified, all revisions of the file are deleted.
	 * @param $fileId int
	 * @param $revision int (optional)
	 * @return int number of files removed
	 */
	function deleteFile($fileId, $revision = null) {
		$paperFileDao =& DAORegistry::getDAO('PaperFileDAO');

		$files = array();
		if (isset($revision)) {
			$file =& $paperFileDao->getPaperFile($fileId, $revision);
			if (isset($file)) {
				$files[] = $file;
			}

		} else {
			$files =  &$paperFileDao->getPaperFileRevisions($fileId);
		}

		foreach ($files as $f) {
			parent::deleteFile($this->filesDir . $f->getType() . '/' . $f->getFileName());
		}

		$paperFileDao->deletePaperFileById($fileId, $revision);

		return count($files);
	}

	/**
	 * Delete the entire tree of files belonging to a paper.
	 */
	function deletePaperTree() {
		parent::rmtree($this->filesDir);
	}

	/**
	 * Download a file.
	 * @param $fileId int the file id of the file to download
	 * @param $revision int the revision of the file to download
	 * @param $inline print file as inline instead of attachment, optional
	 * @return boolean
	 */
	function downloadFile($fileId, $revision = null, $inline = false) {
		$paperFile =& $this->getFile($fileId, $revision);
		if (isset($paperFile)) {
			$fileType = $paperFile->getFileType();
			$filePath = $this->filesDir . $paperFile->getType() . '/' . $paperFile->getFileName();

			return parent::downloadFile($filePath, $fileType, $inline);

		} else {
			return false;
		}
	}

	/**
	 * View a file inline (variant of downloadFile).
	 * @see PaperFileManager::downloadFile
	 */
	function viewFile($fileId, $revision = null) {
		$this->downloadFile($fileId, $revision, true);
	}

	/**
	 * Copies an existing file to create a review file.
	 * @param $originalFileId int the file id of the original file.
	 * @param $originalRevision int the revision of the original file.
	 * @param $destFileId int the file id of the current review file
	 * @return int the file id of the new file.
	 */
	function copyToReviewFile($fileId, $revision = null, $destFileId = null) {
		return $this->copyAndRenameFile($fileId, $revision, PAPER_FILE_REVIEW, $destFileId);
	}

	/**
	 * Copies an existing file to create a director decision file.
	 * @param $fileId int the file id of the review file.
	 * @param $revision int the revision of the review file.
	 * @param $destFileId int file ID to copy to
	 * @return int the file id of the new file.
	 */
	function copyToDirectorFile($fileId, $revision = null, $destFileId = null) {
		return $this->copyAndRenameFile($fileId, $revision, PAPER_FILE_DIRECTOR, $destFileId);
	}

	/**
	 * Copies an existing file to create a layout file.
	 * @param $fileId int the file id of the layout file.
	 * @param $revision int the revision of the layout file.
	 * @return int the file id of the new file.
	 */
	function copyToLayoutFile($fileId, $revision = null) {
		return $this->copyAndRenameFile($fileId, $revision, PAPER_FILE_LAYOUT);
	}

	/**
	 * Return type path associated with a type code.
	 * @param $type string
	 * @return string
	 */
	function typeToPath($type) {
		switch ($type) {
			case PAPER_FILE_PUBLIC: return 'public';
			case PAPER_FILE_SUPP: return 'supp';
			case PAPER_FILE_NOTE: return 'note';
			case PAPER_FILE_REVIEW: return 'submission/review';
			case PAPER_FILE_DIRECTOR: return 'submission/director';
			case PAPER_FILE_LAYOUT: return 'submission/layout';
			case PAPER_FILE_SUBMISSION: default: return 'submission/original';
		}
	}

	/**
	 * Copies an existing PaperFile and renames it.
	 * @param $sourceFileId int
	 * @param $sourceRevision int
	 * @param $destType string
	 * @param $destFileId int (optional)
	 */
	function copyAndRenameFile($sourceFileId, $sourceRevision, $destType, $destFileId = null) {
		$paperFileDao =& DAORegistry::getDAO('PaperFileDAO');
		$paperFile = new PaperFile();

		$destTypePath = $this->typeToPath($destType);
		$destDir = $this->filesDir . $destTypePath . '/';

		if ($destFileId != null) {
			$currentRevision = $paperFileDao->getRevisionNumber($destFileId);
			$revision = $currentRevision + 1;
		} else {
			$revision = 1;
		}	

		$sourcePaperFile = $paperFileDao->getPaperFile($sourceFileId, $sourceRevision, $this->paperId);

		if (!isset($sourcePaperFile)) {
			return false;
		}

		$sourceDir = $this->filesDir . $sourcePaperFile->getType() . '/';

		if ($destFileId != null) {
			$paperFile->setFileId($destFileId);
		}
		$paperFile->setPaperId($this->paperId);
		$paperFile->setFileName($sourcePaperFile->getFileName());
		$paperFile->setFileType($sourcePaperFile->getFileType());
		$paperFile->setFileSize($sourcePaperFile->getFileSize());
		$paperFile->setOriginalFileName(PaperFileManager::truncateFileName($sourcePaperFile->getFileName(), 127));
		$paperFile->setType($destTypePath);
		$paperFile->setDateUploaded(Core::getCurrentDate());
		$paperFile->setDateModified(Core::getCurrentDate());
		$paperFile->setStage($this->paper->getCurrentStage());
		$paperFile->setRevision($revision);

		$fileId = $paperFileDao->insertPaperFile($paperFile);

		// Rename the file.
		$fileExtension = $this->parseFileExtension($sourcePaperFile->getFileName());
		$newFileName = $this->paperId.'-'.$fileId.'-'.$revision.'-'.$destType.'.'.$fileExtension;

		if (!$this->fileExists($destDir, 'dir')) {
			// Try to create destination directory
			$this->mkdirtree($destDir);
		}

		copy($sourceDir.$sourcePaperFile->getFileName(), $destDir.$newFileName);

		$paperFile->setFileName($newFileName);
		$paperFileDao->updatePaperFile($paperFile);

		return $fileId;
	}

	/**
	 * PRIVATE routine to generate a dummy file. Used in handleUpload.
	 * @param $paper object
	 * @return object paperFile
	 */
	function &generateDummyFile(&$paper) {
		$paperFileDao =& DAORegistry::getDAO('PaperFileDAO');
		$paperFile = new PaperFile();
		$paperFile->setPaperId($paper->getId());
		$paperFile->setFileName('temp');
		$paperFile->setOriginalFileName('temp');
		$paperFile->setFileType('temp');
		$paperFile->setFileSize(0);
		$paperFile->setType('temp');
		$paperFile->setDateUploaded(Core::getCurrentDate());
		$paperFile->setDateModified(Core::getCurrentDate());
		$paperFile->setStage(0);
		$paperFile->setRevision(1);

		$paperFile->setFileId($paperFileDao->insertPaperFile($paperFile));

		return $paperFile;
	}

	/**
	 * PRIVATE routine to remove all prior revisions of a file.
	 */
	function removePriorRevisions($fileId, $revision) {
		$paperFileDao =& DAORegistry::getDAO('PaperFileDAO');
		$revisions = $paperFileDao->getPaperFileRevisions($fileId);
		foreach ($revisions as $revisionFile) {
			if ($revisionFile->getRevision() != $revision) {
				$this->deleteFile($fileId, $revisionFile->getRevision());
			}
		}
	}

	/**
	 * PRIVATE routine to generate a filename for a paper file. Sets the filename
	 * field in the paperFile to the generated value.
	 * @param $paperFile The paper to generate a filename for
	 * @param $type The type of the paper (e.g. as supplied to handleUpload)
	 * @param $originalName The name of the original file
	 */
	function generateFilename(&$paperFile, $type, $originalName) {
		$extension = $this->parseFileExtension($originalName);			
		$newFileName = $paperFile->getPaperId().'-'.$paperFile->getFileId().'-'.$paperFile->getRevision().'-'.$type.'.'.$extension;
		$paperFile->setFileName($newFileName);
		return $newFileName;
	}

	/**
	 * PRIVATE routine to upload the file and add it to the database.
	 * @param $fileName string index into the $_FILES array
	 * @param $type string identifying type
	 * @param $fileId int ID of an existing file to update
	 * @param $overwrite boolean overwrite all previous revisions of the file (revision number is still incremented)
	 * @return int the file ID (false if upload failed)
	 */
	function handleUpload($fileName, $type, $fileId = null, $overwrite = false) {
		if ($this->uploadError($fileName)) return false;

		$paperFileDao =& DAORegistry::getDAO('PaperFileDAO');

		$typePath = $this->typeToPath($type);
		$dir = $this->filesDir . $typePath . '/';

		if (!$fileId) {
			// Insert dummy file to generate file id FIXME?
			$dummyFile = true;
			$paperFile =& $this->generateDummyFile($this->paper);
		} else {
			$dummyFile = false;
			$paperFile = new PaperFile();
			$paperFile->setRevision($paperFileDao->getRevisionNumber($fileId)+1);
			$paperFile->setPaperId($this->paperId);
			$paperFile->setFileId($fileId);
			$paperFile->setDateUploaded(Core::getCurrentDate());
			$paperFile->setDateModified(Core::getCurrentDate());
		}

		$paperFile->setFileType($this->getUploadedFileType($fileName));
		$paperFile->setFileSize($_FILES[$fileName]['size']);
		$paperFile->setOriginalFileName(PaperFileManager::truncateFileName($_FILES[$fileName]['name'], 127));
		$paperFile->setType($typePath);
		$paperFile->setStage($this->paper->getCurrentStage());

		$newFileName = $this->generateFilename($paperFile, $type, $this->getUploadedFileName($fileName));

		if (!$this->uploadFile($fileName, $dir.$newFileName)) {
			// Delete the dummy file we inserted
			$paperFileDao->deletePaperFileById($paperFile->getFileId());

			return false;
		}

		if ($dummyFile) $paperFileDao->updatePaperFile($paperFile);
		else $paperFileDao->insertPaperFile($paperFile);

		if ($overwrite) $this->removePriorRevisions($paperFile->getFileId(), $paperFile->getRevision());

		return $paperFile->getFileId();
	}

	/**
	 * PRIVATE routine to write a paper file and add it to the database.
	 * @param $fileName original filename of the file
	 * @param $contents string contents of the file to write
	 * @param $mimeType string the mime type of the file
	 * @param $type string identifying type
	 * @param $fileId int ID of an existing file to update
	 * @param $overwrite boolean overwrite all previous revisions of the file (revision number is still incremented)
	 * @return int the file ID (false if upload failed)
	 */
	function handleWrite($fileName, &$contents, $mimeType, $type, $fileId = null, $overwrite = false) {
		$paperFileDao =& DAORegistry::getDAO('PaperFileDAO');

		$typePath = $this->typeToPath($type);
		$dir = $this->filesDir . $typePath . '/';

		if (!$fileId) {
			// Insert dummy file to generate file id FIXME?
			$dummyFile = true;
			$paperFile =& $this->generateDummyFile($this->paper);
		} else {
			$dummyFile = false;
			$paperFile = new PaperFile();
			$paperFile->setRevision($paperFileDao->getRevisionNumber($fileId)+1);
			$paperFile->setPaperId($this->paperId);
			$paperFile->setFileId($fileId);
			$paperFile->setDateUploaded(Core::getCurrentDate());
			$paperFile->setDateModified(Core::getCurrentDate());
		}

		$paperFile->setFileType($mimeType);
		$paperFile->setFileSize(strlen($contents));
		$paperFile->setOriginalFileName(PaperFileManager::truncateFileName($fileName, 127));
		$paperFile->setType($typePath);
		$paperFile->setStage($this->paper->getCurrentStage());

		$newFileName = $this->generateFilename($paperFile, $type, $fileName);

		if (!$this->writeFile($dir.$newFileName, $contents)) {
			// Delete the dummy file we inserted
			$paperFileDao->deletePaperFileById($paperFile->getFileId());

			return false;
		}

		if ($dummyFile) $paperFileDao->updatePaperFile($paperFile);
		else $paperFileDao->insertPaperFile($paperFile);

		if ($overwrite) $this->removePriorRevisions($paperFile->getFileId(), $paperFile->getRevision());

		return $paperFile->getFileId();
	}

	/**
	 * PRIVATE routine to copy a paper file and add it to the database.
	 * @param $url original filename/url of the file
	 * @param $mimeType string the mime type of the file
	 * @param $type string identifying type
	 * @param $fileId int ID of an existing file to update
	 * @param $overwrite boolean overwrite all previous revisions of the file (revision number is still incremented)
	 * @return int the file ID (false if upload failed)
	 */
	function handleCopy($url, $mimeType, $type, $fileId = null, $overwrite = false) {
		$paperFileDao =& DAORegistry::getDAO('PaperFileDAO');

		$typePath = $this->typeToPath($type);
		$dir = $this->filesDir . $typePath . '/';

		if (!$fileId) {
			// Insert dummy file to generate file id FIXME?
			$dummyFile = true;
			$paperFile =& $this->generateDummyFile($this->paper);
		} else {
			$dummyFile = false;
			$paperFile = new PaperFile();
			$paperFile->setRevision($paperFileDao->getRevisionNumber($fileId)+1);
			$paperFile->setPaperId($this->paperId);
			$paperFile->setFileId($fileId);
			$paperFile->setDateUploaded(Core::getCurrentDate());
			$paperFile->setDateModified(Core::getCurrentDate());
		}

		$paperFile->setFileType($mimeType);
		$paperFile->setOriginalFileName(PaperFileManager::truncateFileName(basename($url), 127));
		$paperFile->setType($typePath);
		$paperFile->setStage($this->paper->getCurrentStage());

		$newFileName = $this->generateFilename($paperFile, $type, $paperFile->getOriginalFileName());

		if (!$this->copyFile($url, $dir.$newFileName)) {
			// Delete the dummy file we inserted
			$paperFileDao->deletePaperFileById($paperFile->getFileId());

			return false;
		}

		$paperFile->setFileSize(filesize($dir.$newFileName));

		if ($dummyFile) $paperFileDao->updatePaperFile($paperFile);
		else $paperFileDao->insertPaperFile($paperFile);

		if ($overwrite) $this->removePriorRevisions($paperFile->getFileId(), $paperFile->getRevision());

		return $paperFile->getFileId();
	}
}

?>
