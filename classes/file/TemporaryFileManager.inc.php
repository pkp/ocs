<?php

/**
 * @file classes/file/TemporaryFileManager.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TemporaryFileManager
 * @ingroup file
 * @see TemporaryFileDAO
 *
 * @brief Class defining operations for temporary file management.
 */

// $Id$


import('file.PKPTemporaryFileManager');

class TemporaryFileManager extends PKPTemporaryFileManager {
	/**
	 * Constructor.
	 * Create a manager for handling temporary file uploads.
	 */
	function TemporaryFileManager() {
		parent::PKPTemporaryFileManager();
	}

	/**
	 * Create a new temporary file from a paper file.
	 * @param $paperFile object
	 * @param $userId int
	 * @return object The new TemporaryFile or false on failure
	 */
	function paperToTemporaryFile($paperFile, $userId) {
		// Get the file extension, then rename the file.
		$fileExtension = $this->parseFileExtension($paperFile->getFileName());			

		if (!$this->fileExists($this->filesDir, 'dir')) {
			// Try to create destination directory
			$this->mkdirtree($this->filesDir);
		}

		$newFileName = basename(tempnam($this->filesDir, $fileExtension));
		if (!$newFileName) return false;

		if (copy($paperFile->getFilePath(), $this->filesDir . $newFileName)) {
			$temporaryFileDao =& DAORegistry::getDAO('TemporaryFileDAO');
			$temporaryFile = new TemporaryFile();

			$temporaryFile->setUserId($userId);
			$temporaryFile->setFileName($newFileName);
			$temporaryFile->setFileType($paperFile->getFileType());
			$temporaryFile->setFileSize($paperFile->getFileSize());
			$temporaryFile->setOriginalFileName(TemporaryFileManager::truncateFileName($paperFile->getOriginalFileName(), 127));
			$temporaryFile->setDateUploaded(Core::getCurrentDate());

			$temporaryFileDao->insertTemporaryFile($temporaryFile);

			return $temporaryFile;

		} else {
			return false;
		}
	}
}

?>
