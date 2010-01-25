<?php

/**
 * @file SuppFileForm.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SuppFileForm
 * @ingroup submission_form
 *
 * @brief Supplementary file form.
 */

//$Id$

import('form.Form');

class SuppFileForm extends Form {
	/** @var int the ID of the supplementary file */
	var $suppFileId;

	/** @var Paper current paper */
	var $paper;

	/** @var SuppFile current file */
	var $suppFile;

	/**
	 * Constructor.
	 * @param $paper object
	 * @param $suppFileId int (optional)
	 */
	function SuppFileForm($paper, $suppFileId = null) {
		parent::Form('submission/suppFile/suppFile.tpl');

		$this->paper = $paper;

		if (isset($suppFileId) && !empty($suppFileId)) {
			$suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
			$this->suppFile =& $suppFileDao->getSuppFile($suppFileId, $paper->getPaperId());
			if (isset($this->suppFile)) {
				$this->suppFileId = $suppFileId;
			}
		}

		// Validation checks for this form
		$this->addCheck(new FormValidatorLocale($this, 'title', 'required', 'author.submit.suppFile.form.titleRequired'));
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Get the names of fields for which data should be localized
	 * @return array
	 */
	function getLocaleFieldNames() {
		$suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
		return $suppFileDao->getLocaleFieldNames();
	}

	/**
	 * Display the form.
	 */
	function display() {
		$conference =& Request::getConference();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('enablePublicSuppFileId', $conference->getSetting('enablePublicSuppFileId'));
		$templateMgr->assign('rolePath', Request::getRequestedPage());
		$templateMgr->assign('paperId', $this->paper->getPaperId());
		$templateMgr->assign('suppFileId', $this->suppFileId);

		$typeOptionsOutput = array(
			'author.submit.suppFile.researchInstrument',
			'author.submit.suppFile.researchMaterials',
			'author.submit.suppFile.researchResults',
			'author.submit.suppFile.transcripts',
			'author.submit.suppFile.dataAnalysis',
			'author.submit.suppFile.dataSet',
			'author.submit.suppFile.sourceText'
		);
		$typeOptionsValues = $typeOptionsOutput;
		array_push($typeOptionsOutput, 'common.other');
		array_push($typeOptionsValues, '');

		$templateMgr->assign('typeOptionsOutput', $typeOptionsOutput);
		$templateMgr->assign('typeOptionsValues', $typeOptionsValues);

		// Sometimes it's necessary to track the page we came from in
		// order to redirect back to the right place
		$templateMgr->assign('from', Request::getUserVar('from'));

		if (isset($this->paper)) {
			$templateMgr->assign('submissionProgress', $this->paper->getSubmissionProgress());
		}

		if (isset($this->suppFile)) {
			$templateMgr->assign_by_ref('suppFile', $this->suppFile);
		}
		$templateMgr->assign('helpTopicId','submission.supplementaryFiles');		
		parent::display();
	}

	/**
	 * Validate the form
	 */
	function validate() {
		$conference =& Request::getConference();
		$suppFileDao =& DAORegistry::getDAO('SuppFileDAO');

		$publicSuppFileId = $this->getData('publicSuppFileId');
		if ($publicSuppFileId && $suppFileDao->suppFileExistsByPublicId($publicSuppFileId, $this->suppFileId, $conference->getId())) {
			$this->addError('publicSchedConfId', Locale::translate('author.suppFile.suppFilePublicIdentificationExists'));
			$this->addErrorField('publicSuppFileId');
		}

		return parent::validate();
	}

	/**
	 * Initialize form data from current supplementary file (if applicable).
	 */
	function initData() {
		if (isset($this->suppFile)) {
			$suppFile =& $this->suppFile;
			$this->_data = array(
				'title' => $suppFile->getTitle(null), // Localized
				'creator' => $suppFile->getCreator(null), // Localized
				'subject' => $suppFile->getSubject(null), // Localized
				'type' => $suppFile->getType(),
				'typeOther' => $suppFile->getTypeOther(null), // Localized
				'description' => $suppFile->getDescription(null), // Localized
				'publisher' => $suppFile->getPublisher(null), // Localized
				'sponsor' => $suppFile->getSponsor(null), // Localized
				'dateCreated' => $suppFile->getDateCreated(),
				'source' => $suppFile->getSource(null), // Localized
				'language' => $suppFile->getLanguage(),
				'showReviewers' => $suppFile->getShowReviewers()==1?1:0,
				'publicSuppFileId' => $suppFile->getPublicSuppFileId()
			);

		} else {
			$this->_data = array(
				'type' => '',
				'showReviewers' => 1
			);
		}

	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(
			array(
				'title',
				'creator',
				'subject',
				'type',
				'typeOther',
				'description',
				'publisher',
				'sponsor',
				'dateCreated',
				'source',
				'language',
				'showReviewers',
				'publicSuppFileId'
			)
		);
	}

	/**
	 * Save changes to the supplementary file.
	 * @return int the supplementary file ID
	 */
	function execute($fileName = null) {
		import("file.PaperFileManager");
		$paperFileManager = new PaperFileManager($this->paper->getPaperId());
		$suppFileDao =& DAORegistry::getDAO('SuppFileDAO');

		$fileName = isset($fileName) ? $fileName : 'uploadSuppFile';

		if (isset($this->suppFile)) {
			$suppFile =& $this->suppFile;

			// Upload file, if file selected.
			if ($paperFileManager->uploadedFileExists($fileName)) {
				$paperFileManager->uploadSuppFile($fileName, $suppFile->getFileId());
				import('search.PaperSearchIndex');
				PaperSearchIndex::updateFileIndex($this->paper->getPaperId(), PAPER_SEARCH_SUPPLEMENTARY_FILE, $suppFile->getFileId());
			}

			// Index metadata
			PaperSearchIndex::indexSuppFileMetadata($suppFile);

			// Update existing supplementary file
			$this->setSuppFileData($suppFile);
			$suppFileDao->updateSuppFile($suppFile);

		} else {
			// Upload file, if file selected.
			if ($paperFileManager->uploadedFileExists($fileName)) {
				$fileId = $paperFileManager->uploadSuppFile($fileName);
				import('search.PaperSearchIndex');
				PaperSearchIndex::updateFileIndex($this->paper->getPaperId(), PAPER_SEARCH_SUPPLEMENTARY_FILE, $fileId);
			} else {
				$fileId = 0;
			}

			// Insert new supplementary file		
			$suppFile = new SuppFile();
			$suppFile->setPaperId($this->paper->getPaperId());
			$suppFile->setFileId($fileId);
			$this->setSuppFileData($suppFile);
			$suppFileDao->insertSuppFile($suppFile);
			$this->suppFileId = $suppFile->getSuppFileId();
		}

		return $this->suppFileId;
	}

	/**
	 * Assign form data to a SuppFile.
	 * @param $suppFile SuppFile
	 */
	function setSuppFileData(&$suppFile) {
		$suppFile->setTitle($this->getData('title'), null); // Localized
		$suppFile->setCreator($this->getData('creator'), null); // Localized
		$suppFile->setSubject($this->getData('subject'), null); // Localized
		$suppFile->setType($this->getData('type'));
		$suppFile->setTypeOther($this->getData('typeOther'), null); // Localized
		$suppFile->setDescription($this->getData('description'), null); // Localized
		$suppFile->setPublisher($this->getData('publisher'), null); // Localized
		$suppFile->setSponsor($this->getData('sponsor'), null); // Localized
		$suppFile->setDateCreated($this->getData('dateCreated') == '' ? Core::getCurrentDate() : $this->getData('dateCreated'));
		$suppFile->setSource($this->getData('source'), null); // Localized
		$suppFile->setLanguage($this->getData('language'));
		$suppFile->setShowReviewers($this->getData('showReviewers')==1?1:0);
		$suppFile->setPublicSuppFileId($this->getData('publicSuppFileId'));
	}
}

?>
