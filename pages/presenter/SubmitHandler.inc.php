<?php

/**
 * @file SubmitHandler.inc.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmitHandler
 * @ingroup pages_presenter
 *
 * @brief Handle requests for presenter paper submission. 
 */

//$Id$

class SubmitHandler extends PresenterHandler {

	/**
	 * Display conference presenter paper submission.
	 * Displays presenter index page if a valid step is not specified.
	 * @param $args array optional, if set the first parameter is the step to display
	 */
	function submit($args) {
		$user =& Request::getUser();
		$schedConf =& Request::getSchedConf();
		if ($user && $schedConf && !Validation::isPresenter()) {
			// The user is logged in but not a presenter. If
			// possible, enroll them as a presenter automatically.
			Request::redirect(
				null, null,
				'user', 'become',
				array('presenter'),
				array(
					'source' => Request::url(
						null, null, 'presenter', 'submit'
					)
				)
			);
		}

		parent::validate();
		parent::setupTemplate(true);

		$step = isset($args[0]) ? (int) $args[0] : 0;
		$paperId = Request::getUserVar('paperId');
		list($conference, $schedConf, $paper) = SubmitHandler::validate($paperId, $step);

		$formClass = "PresenterSubmitStep{$step}Form";
		import("presenter.form.submit.$formClass");

		$submitForm = &new $formClass($paper);
		if ($submitForm->isLocaleResubmit()) {
			$submitForm->readInputData();
		} else {
			$submitForm->initData();
		}
		$submitForm->display();
	}

	/**
	 * Save a submission step.
	 * @param $args array first parameter is the step being saved
	 */
	function saveSubmit($args) {
		parent::validate();
		parent::setupTemplate(true);

		$step = isset($args[0]) ? (int) $args[0] : 0;
		$paperId = Request::getUserVar('paperId');

		list($conference, $schedConf, $paper) = SubmitHandler::validate($paperId, $step);

		$formClass = "PresenterSubmitStep{$step}Form";
		import("presenter.form.submit.$formClass");

		$submitForm = &new $formClass($paper);
		$submitForm->readInputData();

		// Check for any special cases before trying to save
		switch ($step) {
			case 2:
				if (Request::getUserVar('addPresenter')) {
					// Add a sponsor
					$editData = true;
					$presenters = $submitForm->getData('presenters');
					array_push($presenters, array());
					$submitForm->setData('presenters', $presenters);

				} else if (($delPresenter = Request::getUserVar('delPresenter')) && count($delPresenter) == 1) {
					// Delete an presenter
					$editData = true;
					list($delPresenter) = array_keys($delPresenter);
					$delPresenter = (int) $delPresenter;
					$presenters = $submitForm->getData('presenters');
					if (isset($presenters[$delPresenter]['presenterId']) && !empty($presenters[$delPresenter]['presenterId'])) {
						$deletedPresenters = explode(':', $submitForm->getData('deletedPresenters'));
						array_push($deletedPresenters, $presenters[$delPresenter]['presenterId']);
						$submitForm->setData('deletedPresenters', join(':', $deletedPresenters));
					}
					array_splice($presenters, $delPresenter, 1);
					$submitForm->setData('presenters', $presenters);

					if ($submitForm->getData('primaryContact') == $delPresenter) {
						$submitForm->setData('primaryContact', 0);
					}

				} else if (Request::getUserVar('movePresenter')) {
					// Move an presenter up/down
					$editData = true;
					$movePresenterDir = Request::getUserVar('movePresenterDir');
					$movePresenterDir = $movePresenterDir == 'u' ? 'u' : 'd';
					$movePresenterIndex = (int) Request::getUserVar('movePresenterIndex');
					$presenters = $submitForm->getData('presenters');

					if (!(($movePresenterDir == 'u' && $movePresenterIndex <= 0) || ($movePresenterDir == 'd' && $movePresenterIndex >= count($presenters) - 1))) {
						$tmpPresenter = $presenters[$movePresenterIndex];
						$primaryContact = $submitForm->getData('primaryContact');
						if ($movePresenterDir == 'u') {
							$presenters[$movePresenterIndex] = $presenters[$movePresenterIndex - 1];
							$presenters[$movePresenterIndex - 1] = $tmpPresenter;
							if ($primaryContact == $movePresenterIndex) {
								$submitForm->setData('primaryContact', $movePresenterIndex - 1);
							} else if ($primaryContact == ($movePresenterIndex - 1)) {
								$submitForm->setData('primaryContact', $movePresenterIndex);
							}
						} else {
							$presenters[$movePresenterIndex] = $presenters[$movePresenterIndex + 1];
							$presenters[$movePresenterIndex + 1] = $tmpPresenter;
							if ($primaryContact == $movePresenterIndex) {
								$submitForm->setData('primaryContact', $movePresenterIndex + 1);
							} else if ($primaryContact == ($movePresenterIndex + 1)) {
								$submitForm->setData('primaryContact', $movePresenterIndex);
							}
						}
					}
					$submitForm->setData('presenters', $presenters);
				}
				break;

			case 3:
				if (Request::getUserVar('uploadSubmissionFile')) {
					$submitForm->uploadSubmissionFile('submissionFile');
					$editData = true;
				}
				break;

			case 4:
				if (Request::getUserVar('submitUploadSuppFile')) {
					SubmitHandler::submitUploadSuppFile();
					return;
				}
				break;
		}

		if (!isset($editData) && $submitForm->validate()) {
			$paperId = $submitForm->execute();
			$conference = &Request::getConference();
			$schedConf = &Request::getSchedConf();

			// For the "abstract only" or sequential review models, nothing else needs
			// to be collected beyond page 2.
			$reviewMode = $paper?$paper->getReviewMode():null;
			if (($step == 2 && $reviewMode == REVIEW_MODE_BOTH_SEQUENTIAL) || 
					($step == 2 && $reviewMode == REVIEW_MODE_ABSTRACTS_ALONE && !$schedConf->getSetting('acceptSupplementaryReviewMaterials')) || 
					($step == 5 )) {

				$templateMgr = &TemplateManager::getManager();
				$templateMgr->assign_by_ref('conference', $conference);
				// If this is a director and there is a
				// submission file, paper can be expedited.
				if (Validation::isDirector($conference->getConferenceId()) && $paper->getSubmissionFileId()) {
					$templateMgr->assign('canExpedite', true);
				}
				$templateMgr->assign('paperId', $paperId);
				$templateMgr->assign('helpTopicId','submission.index');
				$templateMgr->display('presenter/submit/complete.tpl');
			} elseif ($step == 3 && !$schedConf->getSetting('acceptSupplementaryReviewMaterials')) {
				Request::redirect(null, null, null, 'submit', 5, array('paperId' => $paperId));
			} elseif ($step == 2 && $reviewMode == REVIEW_MODE_ABSTRACTS_ALONE) {
				Request::redirect(null, null, null, 'submit', 4, array('paperId' => $paperId));
			} else {
				Request::redirect(null, null, null, 'submit', $step+1, array('paperId' => $paperId));
			}
		} else {
			$submitForm->display();
		}
	}

	/**
	 * Create new supplementary file with a uploaded file.
	 */
	function submitUploadSuppFile() {
		parent::validate();
		parent::setupTemplate(true);

		$paperId = Request::getUserVar('paperId');

		list($conference, $schedConf, $paper) = SubmitHandler::validate($paperId, 4);
		if ($schedConf->getSetting('acceptSupplementaryReviewMaterials')) {
			import("presenter.form.submit.PresenterSubmitSuppFileForm");
			$submitForm = &new PresenterSubmitSuppFileForm($paper);
			$submitForm->setData('title', Locale::translate('common.untitled'));
			$suppFileId = $submitForm->execute();
		}

		Request::redirect(null, null, null, 'submitSuppFile', $suppFileId, array('paperId' => $paperId));
	}

	/**
	 * Display supplementary file submission form.
	 * @param $args array optional, if set the first parameter is the supplementary file to edit
	 */
	function submitSuppFile($args) {
		parent::validate();
		parent::setupTemplate(true);

		$paperId = Request::getUserVar('paperId');
		$suppFileId = isset($args[0]) ? (int) $args[0] : 0;

		list($conference, $schedConf, $paper) = SubmitHandler::validate($paperId, 4);

		if (!$schedConf->getSetting('acceptSupplementaryReviewMaterials')) Request::redirect(null, null, 'index');

		import("presenter.form.submit.PresenterSubmitSuppFileForm");
		$submitForm = &new PresenterSubmitSuppFileForm($paper, $suppFileId);

		if ($submitForm->isLocaleResubmit()) {
			$submitForm->readInputData();
		} else {
			$submitForm->initData();
		}
		$submitForm->display();
	}

	/**
	 * Save a supplementary file.
	 * @param $args array optional, if set the first parameter is the supplementary file to update
	 */
	function saveSubmitSuppFile($args) {
		parent::validate();
		parent::setupTemplate(true);

		$paperId = Request::getUserVar('paperId');
		$suppFileId = isset($args[0]) ? (int) $args[0] : 0;

		list($conference, $schedConf, $paper) = SubmitHandler::validate($paperId, 4);
		if (!$schedConf->getSetting('acceptSupplementaryReviewMaterials')) Request::redirect(null, null, 'index');

		import("presenter.form.submit.PresenterSubmitSuppFileForm");
		$submitForm = &new PresenterSubmitSuppFileForm($paper, $suppFileId);
		$submitForm->readInputData();

		if ($submitForm->validate()) {
			$submitForm->execute();
			Request::redirect(null, null, null, 'submit', '4', array('paperId' => $paperId));
		} else {
			$submitForm->display();
		}
	}

	/**
	 * Delete a supplementary file.
	 * @param $args array, the first parameter is the supplementary file to delete
	 */
	function deleteSubmitSuppFile($args) {
		import("file.PaperFileManager");

		parent::validate();
		parent::setupTemplate(true);

		$paperId = Request::getUserVar('paperId');
		$suppFileId = isset($args[0]) ? (int) $args[0] : 0;

		list($conference, $schedConf, $paper) = SubmitHandler::validate($paperId, 4);
		if (!$schedConf->getSetting('acceptSupplementaryReviewMaterials')) Request::redirect(null, null, 'index');

		$suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		$suppFile = $suppFileDao->getSuppFile($suppFileId, $paperId);
		$suppFileDao->deleteSuppFileById($suppFileId, $paperId);

		if ($suppFile->getFileId()) {
			$paperFileManager = &new PaperFileManager($paperId);
			$paperFileManager->deleteFile($suppFile->getFileId());
		}

		Request::redirect(null, null, null, 'submit', '4', array('paperId' => $paperId));
	}

	function expediteSubmission() {
		$paperId = (int) Request::getUserVar('paperId');
		list($conference, $schedConf, $paper) = SubmitHandler::validate($paperId);

		// The presenter must also be a director to perform this task.
		if (Validation::isDirector($conference->getConferenceId()) && $paper->getSubmissionFileId()) {
			import('submission.director.DirectorAction');
			DirectorAction::expediteSubmission($paper);
			Request::redirect(null, null, 'director', 'schedulingQueue');
		}

		Request::redirect(null, null, null, 'track');
	}

	/**
	 * Validation check for submission.
	 * Checks that paper ID is valid, if specified.
	 * @param $paperId int
	 * @param $step int
	 */
	function validate($paperId = null, $step = false) {
		list($conference, $schedConf) = parent::validate();

		$paperDao = &DAORegistry::getDAO('PaperDAO');
		$user = &Request::getUser();

		if ($step !== false && ($step < 1 || $step > 5 || (!isset($paperId) && $step != 1))) {
			Request::redirect(null, null, null, 'submit', array(1));
		}

		$paper = null;

		if (isset($paperId)) {
			// Check that paper exists for this conference and user and that submission is incomplete
			$paper =& $paperDao->getPaper((int) $paperId);
			if (!$paper || $paper->getUserId() !== $user->getUserId() || $paper->getSchedConfId() !== $schedConf->getSchedConfId()) {
				Request::redirect(null, null, null, 'submit');
			}

			if($step !== false && $step > $paper->getSubmissionProgress()) {
				Request::redirect(null, null, null, 'submit');
			}

		} else {
			// If the paper does not exist, require that the
			// submission window be open or that this user be a
			// director or track director.
			import('schedConf.SchedConfAction');
			$schedConf =& Request::getSchedConf();
			if (!$schedConf || (!SchedConfAction::submissionsOpen($schedConf) && !Validation::isDirector($schedConf->getConferenceId(), $schedConf->getSchedConfId()) && !Validation::isTrackDirector($schedConf->getConferenceId()))) {
				Request::redirect(null, null, 'presenter', 'index');
			}
		}
		return array(&$conference, &$schedConf, &$paper);
	}
}

?>
