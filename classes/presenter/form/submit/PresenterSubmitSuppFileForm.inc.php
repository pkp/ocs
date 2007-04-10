<?php

/**
 * PresenterSubmitSuppFileForm.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package presenter.form.submit
 *
 * Supplementary file presenter submission form.
 *
 * $Id$
 */

import('form.Form');

class PresenterSubmitSuppFileForm extends Form {

	/** @var int the ID of the paper */
	var $paperId;

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
	function PresenterSubmitSuppFileForm($paper, $suppFileId = null) {
		parent::Form('presenter/submit/suppFile.tpl');
		$this->paperId = $paper->getPaperId();
		
		if (isset($suppFileId) && !empty($suppFileId)) {
			$suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
			$this->suppFile = &$suppFileDao->getSuppFile($suppFileId, $paper->getPaperId());
			if (isset($this->suppFile)) {
				$this->suppFileId = $suppFileId;
			}
		}
		
		// Validation checks for this form
		$this->addCheck(new FormValidator($this, 'title', 'required', 'presenter.submit.suppFile.form.titleRequired'));
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('sidebarTemplate', 'presenter/submit/submitSidebar.tpl');
		$templateMgr->assign('paperId', $this->paperId);
		$templateMgr->assign('suppFileId', $this->suppFileId);
		$templateMgr->assign('submitStep', 4);
		
		$typeOptionsOutput = array(
			'presenter.submit.suppFile.researchInstrument',
			'presenter.submit.suppFile.researchMaterials',
			'presenter.submit.suppFile.researchResults',
			'presenter.submit.suppFile.transcripts',
			'presenter.submit.suppFile.dataAnalysis',
			'presenter.submit.suppFile.dataSet',
			'presenter.submit.suppFile.sourceText'
		);
		$typeOptionsValues = $typeOptionsOutput;
		array_push($typeOptionsOutput, 'common.other');
		array_push($typeOptionsValues, '');
		
		$templateMgr->assign('typeOptionsOutput', $typeOptionsOutput);
		$templateMgr->assign('typeOptionsValues', $typeOptionsValues);
		
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
	 * Initialize form data from current supplementary file (if applicable).
	 */
	function initData() {
		if (isset($this->suppFile)) {
			$suppFile = &$this->suppFile;
			$this->_data = array(
				'title' => $suppFile->getTitle(),
				'creator' => $suppFile->getCreator(),
				'subject' => $suppFile->getSubject(),
				'type' => $suppFile->getType(),
				'typeOther' => $suppFile->getTypeOther(),
				'description' => $suppFile->getDescription(),
				'publisher' => $suppFile->getPublisher(),
				'sponsor' => $suppFile->getSponsor(),
				'dateCreated' => $suppFile->getDateCreated(),
				'source' => $suppFile->getSource(),
				'language' => $suppFile->getLanguage(),
				'showReviewers' => $suppFile->getShowReviewers()
			);
			
		} else {
			$this->_data = array(
				'type' => ''
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
				'showReviewers'
			)
		);
	}
	
	/**
	 * Save changes to the supplementary file.
	 * @return int the supplementary file ID
	 */
	function execute() {
		import("file.PaperFileManager");
		$paperFileManager = &new PaperFileManager($this->paperId);
		$suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		
		$fileName = 'uploadSuppFile';
		
		// edit an existing supp file, otherwise create new supp file entry	
		if (isset($this->suppFile)) {
			$suppFile = &$this->suppFile;

			// Remove old file and upload new, if file is selected.
			if ($paperFileManager->uploadedFileExists($fileName)) {
				$paperFileDao = &DAORegistry::getDAO('PaperFileDAO');
				$suppFileId = $paperFileManager->uploadSuppFile($fileName, $suppFile->getFileId(), true);
				$suppFile->setFileId($suppFileId);
			}

			// Update existing supplementary file
			$this->setSuppFileData($suppFile);
			$suppFileDao->updateSuppFile($suppFile);
			
		} else {
			// Upload file, if file selected.
			if ($paperFileManager->uploadedFileExists($fileName)) {
				$fileId = $paperFileManager->uploadSuppFile($fileName);
			} else {
				$fileId = 0;
			}
			
			// Insert new supplementary file		
			$suppFile = &new SuppFile();
			$suppFile->setPaperId($this->paperId);
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
		$suppFile->setTitle($this->getData('title'));
		$suppFile->setCreator($this->getData('creator'));
		$suppFile->setSubject($this->getData('subject'));
		$suppFile->setType($this->getData('type'));
		$suppFile->setTypeOther($this->getData('typeOther'));
		$suppFile->setDescription($this->getData('description'));
		$suppFile->setPublisher($this->getData('publisher'));
		$suppFile->setSponsor($this->getData('sponsor'));
		$suppFile->setDateCreated($this->getData('dateCreated') == '' ? Core::getCurrentDate() : $this->getData('dateCreated'));
		$suppFile->setSource($this->getData('source'));
		$suppFile->setLanguage($this->getData('language'));
		$suppFile->setShowReviewers($this->getData('showReviewers'));
	}
}

?>
