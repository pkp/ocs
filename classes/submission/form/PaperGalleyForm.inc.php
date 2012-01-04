<?php

/**
 * @file PaperGalleyForm.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PaperGalleyForm
 * @ingroup submission_form
 *
 * @brief Paper galley editing form.
 */

//$Id$

import('form.Form');

class PaperGalleyForm extends Form {
	/** @var int the ID of the paper */
	var $paperId;

	/** @var $galleyId int the ID of the galley */
	var $galleyId;

	/** @var $galley PaperGalley current galley */
	var $galley;

	/** @var $stage int The review stage the person was coming from (used for redirect) */
	var $stage;

	/**
	 * Constructor.
	 * @param $paperId int
	 * @param $galleyId int (optional)
	 * @param $stage int (optional, used for smart redirect)
	 */
	function PaperGalleyForm($paperId, $galleyId = null, $stage = null) {
		parent::Form('submission/layout/galleyForm.tpl');
		$conference =& Request::getConference();
		$this->paperId = $paperId;
		$this->stage = $stage;

		if (isset($galleyId) && !empty($galleyId)) {
			$galleyDao =& DAORegistry::getDAO('PaperGalleyDAO');
			$this->galley =& $galleyDao->getGalley($galleyId, $paperId);
			if (isset($this->galley)) {
				$this->galleyId = $galleyId;
			}
		}

		// Validation checks for this form
		$this->addCheck(new FormValidator($this, 'label', 'required', 'submission.layout.galleyLabelRequired'));
		$this->addCheck(new FormValidator($this, 'label', 'required', 'submission.layout.galleyLabelRequired'));
		$this->addCheck(new FormValidator($this, 'galleyLocale', 'required', 'submission.layout.galleyLocaleRequired'), create_function('$galleyLocale,$availableLocales', 'return in_array($galleyLocale,$availableLocales);'), array_keys($conference->getSupportedLocaleNames()));
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Display the form.
	 */
	function display() {
		$conference =& Request::getConference();
		$templateMgr =& TemplateManager::getManager();

		$templateMgr->assign('paperId', $this->paperId);
		$templateMgr->assign('galleyId', $this->galleyId);
		$templateMgr->assign('stage', $this->stage);
		$templateMgr->assign('supportedLocales', $conference->getSupportedLocaleNames());

		if (isset($this->galley)) {
			$templateMgr->assign_by_ref('galley', $this->galley);
		}
		$templateMgr->assign('helpTopicId', 'editorial.layoutEditorsRole.layout');
		parent::display();
	}

	/**
	 * Initialize form data from current galley (if applicable).
	 */
	function initData() {
		if (isset($this->galley)) {
			$galley =& $this->galley;
			$this->_data = array(
				'label' => $galley->getLabel(),
				'galleyLocale' => $galley->getLocale()
			);

		} else {
			$this->_data = array();
		}

	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(
			array(
				'label',
				'deleteStyleFile',
				'galleyLocale'
			)
		);
	}

	/**
	 * Save changes to the galley.
	 * @return int the galley ID
	 */
	function execute($fileName = null) {
		import('file.PaperFileManager');
		$paperFileManager = new PaperFileManager($this->paperId);
		$galleyDao =& DAORegistry::getDAO('PaperGalleyDAO');

		$fileName = isset($fileName) ? $fileName : 'galleyFile';

		if (isset($this->galley)) {
			$galley =& $this->galley;

			// Upload galley file
			if ($paperFileManager->uploadedFileExists($fileName)) {
				if($galley->getFileId()) {
					$paperFileManager->uploadPublicFile($fileName, $galley->getFileId());
				} else {
					$fileId = $paperFileManager->uploadPublicFile($fileName);
					$galley->setFileId($fileId);
				}

				// Update file search index
				import('search.PaperSearchIndex');
				PaperSearchIndex::updateFileIndex($this->paperId, PAPER_SEARCH_GALLEY_FILE, $galley->getFileId());
			}

			if ($paperFileManager->uploadedFileExists('styleFile')) {
				// Upload stylesheet file
				$styleFileId = $paperFileManager->uploadPublicFile('styleFile', $galley->getStyleFileId());
				$galley->setStyleFileId($styleFileId);

			} else if($this->getData('deleteStyleFile')) {
				// Delete stylesheet file
				$styleFile =& $galley->getStyleFile();
				if (isset($styleFile)) {
					$paperFileManager->deleteFile($styleFile->getFileId());
				}
			}

			// Update existing galley
			$galley->setLabel($this->getData('label'));
			$galley->setLocale($this->getData('galleyLocale'));
			$galleyDao->updateGalley($galley);

		} else {
			// Upload galley file
			if ($paperFileManager->uploadedFileExists($fileName)) {
				$fileType = $paperFileManager->getUploadedFileType($fileName);
				$fileId = $paperFileManager->uploadPublicFile($fileName);

				// Update file search index
				import('search.PaperSearchIndex');
				PaperSearchIndex::updateFileIndex($this->paperId, PAPER_SEARCH_GALLEY_FILE, $fileId);
			} else {
				$fileId = 0;
			}

			if (isset($fileType) && strstr($fileType, 'html')) {
				// Assume HTML galley
				$galley = new PaperHTMLGalley();
			} else {
				$galley = new PaperGalley();
			}

			$galley->setPaperId($this->paperId);
			$galley->setFileId($fileId);

			if ($this->getData('label') == null) {
				// Generate initial label based on file type
				if ($galley->isHTMLGalley()) {
					$galley->setLabel('HTML');

				} else if (isset($fileType)) {
					if(strstr($fileType, 'pdf')) {
						$galley->setLabel('PDF');

					} else if (strstr($fileType, 'postscript')) {
						$galley->setLabel('PostScript');
					} else if (strstr($fileType, 'xml')) {
						$galley->setLabel('XML');
					} else if (strstr($fileType, 'audio')) {
						$galley->setLabel('Audio');
					} else if (strstr($fileType, 'powerpoint')) {
						$galley->setLabel('Slideshow');
					}
				}

				if ($galley->getLabel() == null) {
					$galley->setLabel(__('common.untitled'));
				}

			} else {
				$galley->setLabel($this->getData('label'));
			}
			$galley->setLocale($this->getData('galleyLocale'));

			// Insert new galley
			$galleyDao->insertGalley($galley);
			$this->galleyId = $galley->getId();
		}

		return $this->galleyId;
	}

	/**
	 * Upload an image to an HTML galley.
	 * @param $imageName string file input key
	 */
	function uploadImage() {
		import('file.PaperFileManager');
		$fileManager = new PaperFileManager($this->paperId);
		$galleyDao =& DAORegistry::getDAO('PaperGalleyDAO');

		$fileName = 'imageFile';

		if (isset($this->galley) && $fileManager->uploadedFileExists($fileName)) {
			$type = $fileManager->getUploadedFileType($fileName);
			$extension = $fileManager->getImageExtension($type);
			if (!$extension) {
				$this->addError('imageFile', __('submission.layout.imageInvalid'));
				return false;
			}

			if ($fileId = $fileManager->uploadPublicFile($fileName)) {
				$galleyDao->insertGalleyImage($this->galleyId, $fileId);

				// Update galley image files
				$this->galley->setImageFiles($galleyDao->getGalleyImages($this->galleyId));
			}

		}
	}

	/**
	 * Delete an image from an HTML galley.
	 * @param $imageId int the file ID of the image
	 */
	function deleteImage($imageId) {
		import('file.PaperFileManager');
		$fileManager = new PaperFileManager($this->paperId);
		$galleyDao =& DAORegistry::getDAO('PaperGalleyDAO');

		if (isset($this->galley)) {
			$images =& $this->galley->getImageFiles();
			if (isset($images)) {
				for ($i=0, $count=count($images); $i < $count; $i++) {
					if ($images[$i]->getFileId() == $imageId) {
						$fileManager->deleteFile($images[$i]->getFileId());
						$galleyDao->deleteGalleyImage($this->galleyId, $imageId);
						unset($images[$i]);
						break;
					}
				}
			}
		}
	}
}

?>
