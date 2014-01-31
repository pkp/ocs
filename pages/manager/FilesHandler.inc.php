<?php

/**
 * @file FilesHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FilesHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for files browser functions. 
 */

import('pages.manager.ManagerHandler');

class FilesHandler extends ManagerHandler {
	/**
	 * Constructor
	 */
	function FilesHandler() {
		parent::ManagerHandler();
	}

	/**
	 * Display the files associated with a conference.
	 */
	function files($args) {
		$this->validate();
		$this->setupTemplate(true);

		import('file.FileManager');

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(array(Request::url(null, null, 'manager'), 'manager.conferenceSiteManagement')));

		FilesHandler::parseDirArg($args, $currentDir, $parentDir);
		$currentPath = FilesHandler::getRealFilesDir($currentDir);

		if (@is_file($currentPath)) {
			$fileMgr = new FileManager();
			if (Request::getUserVar('download')) {
				$fileMgr->downloadFile($currentPath);
			} else {
				$fileMgr->viewFile($currentPath, FilesHandler::fileMimeType($currentPath));
			}

		} else {
			$files = array();
			if ($dh = @opendir($currentPath)) {
				while (($file = readdir($dh)) !== false) {
					if ($file != '.' && $file != '..') {
						$filePath = $currentPath . '/'. $file;
						$isDir = is_dir($filePath);
						$info = array(
							'name' => $file,
							'isDir' => $isDir,
							'mimetype' => $isDir ? '' : FilesHandler::fileMimeType($filePath),
							'mtime' => filemtime($filePath),
							'size' => $isDir ? '' : FileManager::getNiceFileSize(filesize($filePath)),
						);
						$files[$file] = $info;
					}
				}
				closedir($dh);
			}
			ksort($files);
			$templateMgr->assign_by_ref('files', $files);
			$templateMgr->assign('currentDir', $currentDir);
			$templateMgr->assign('parentDir', $parentDir);
			$templateMgr->assign('helpTopicId','conference.generalManagement.filesBrowser');
			$templateMgr->display('manager/files/index.tpl');
		}
	}

	/**
	 * Upload a new file.
	 */
	function fileUpload($args) {
		$this->validate();

		FilesHandler::parseDirArg($args, $currentDir, $parentDir);
		$currentPath = FilesHandler::getRealFilesDir($currentDir);

		import('file.FileManager');
		$fileMgr = new FileManager();
		if ($success = $fileMgr->uploadedFileExists('file')) {
			$destPath = $currentPath . '/' . FilesHandler::cleanFileName($fileMgr->getUploadedFileName('file'));
			$success = $fileMgr->uploadFile('file', $destPath);
		}

		if (!$success) {
			$templateMgr =& TemplateManager::getManager();
			$this->setupTemplate(true);
			$templateMgr->assign('pageTitle', 'manager.filesBrowser');
			$templateMgr->assign('message', 'common.uploadFailed');
			$templateMgr->assign('backLink', Request::url(null, null, null, 'files', explode('/', $currentDir)));
			$templateMgr->assign('backLinkLabel', 'manager.filesBrowser');
			return $templateMgr->display('common/message.tpl');
		}

		Request::redirect(null, null, null, 'files', explode('/', $currentDir));

	}

	/**
	 * Create a new directory
	 */
	function fileMakeDir($args) {
		$this->validate();

		FilesHandler::parseDirArg($args, $currentDir, $parentDir);

		if ($dirName = Request::getUserVar('dirName')) {
			$currentPath = FilesHandler::getRealFilesDir($currentDir);
			$newDir = $currentPath . '/' . FilesHandler::cleanFileName($dirName);

			import('file.FileManager');
			$fileMgr = new FileManager();
			@$fileMgr->mkdir($newDir);
		}

		Request::redirect(null, null, null, 'files', explode('/', $currentDir));
	}

	function fileDelete($args) {
		$this->validate();

		FilesHandler::parseDirArg($args, $currentDir, $parentDir);
		$currentPath = FilesHandler::getRealFilesDir($currentDir);

		import('file.FileManager');
		$fileMgr = new FileManager();

		if (@is_file($currentPath)) {
			$fileMgr->deleteFile($currentPath);
		} else {
			// TODO Use recursive delete (rmtree) instead?
			@$fileMgr->rmdir($currentPath);
		}

		Request::redirect(null, null, null, 'files', explode('/', $parentDir));
	}


	//
	// Helper functions
	// FIXME Move some of these functions into common class (FileManager?)
	//

	function parseDirArg($args, &$currentDir, &$parentDir) {
		$pathArray = array_filter($args, array('FilesHandler', 'fileNameFilter'));
		$currentDir = join($pathArray, '/');
		array_pop($pathArray);
		$parentDir = join($pathArray, '/');
	}

	function getRealFilesDir($currentDir) {
		$conference =& Request::getConference();
		$base = '/conferences/' . $conference->getId();

		return Config::getVar('files', 'files_dir') . $base .'/' . $currentDir;
	}

	function fileNameFilter($var) {
		return (!empty($var) && $var != '..' && $var != '.' && strpos($var, '/')===false);
	}

	function cleanFileName($var) {
		$var = String::regexp_replace('/[^\w\-\.]/', '', $var);
		if (!FilesHandler::fileNameFilter($var)) {
			$var = time() . '';
		}
		return $var;
	}

	function fileMimeType($filePath) {
		return String::mime_content_type($filePath);
	}

}
?>
