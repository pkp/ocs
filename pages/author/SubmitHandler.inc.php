<?php

/**
 * @file SubmitHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmitHandler
 * @ingroup pages_author
 *
 * @brief Handle requests for author paper submission.
 */

//$Id$

import('pages.author.AuthorHandler');

class SubmitHandler extends AuthorHandler {
	/** the paper associated with the request **/
	var $paper;

	/**
	 * Constructor
	 **/
	function SubmitHandler() {
		parent::AuthorHandler();
	}

	/**
	 * Display conference author paper submission.
	 * Displays author index page if a valid step is not specified.
	 * @param $args array optional, if set the first parameter is the step to display
	 */
	function submit($args) {
		$user =& Request::getUser();
		$schedConf =& Request::getSchedConf();
		if ($user && $schedConf && !Validation::isAuthor()) {
			// The user is logged in but not a author. If
			// possible, enroll them as a author automatically.
			Request::redirect(
				null, null,
				'user', 'become',
				array('author'),
				array(
					'source' => Request::url(
						null, null, 'author', 'submit'
					)
				)
			);
		}

		$step = isset($args[0]) ? (int) $args[0] : 1;
		$paperId = (int) Request::getUserVar('paperId');

		$this->validate($paperId, $step);
		$this->setupTemplate(true);

		$paper =& $this->paper;

		$formClass = "AuthorSubmitStep{$step}Form";
		import("author.form.submit.$formClass");

		$submitForm = new $formClass($paper);
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
		$step = isset($args[0]) ? (int) $args[0] : 0;
		$paperId = (int) Request::getUserVar('paperId');

		$this->validate($paperId, $step);
		$this->setupTemplate(true);

		$paper =& $this->paper;

		$formClass = "AuthorSubmitStep{$step}Form";
		import("author.form.submit.$formClass");

		$submitForm = new $formClass($paper);
		$submitForm->readInputData();

		if (!HookRegistry::call('SubmitHandler::saveSubmit', array($step, &$paper, &$submitForm))) {

			// Check for any special cases before trying to save
			switch ($step) {
				case 2:
					if (Request::getUserVar('uploadSubmissionFile')) {
						if (!$submitForm->uploadSubmissionFile('submissionFile')) {
							$submitForm->addError('uploadSubmissionFile', __('common.uploadFailed'));
						}
						$editData = true;
					}
					break;

				case 3:
					if (Request::getUserVar('addAuthor')) {
						// Add a sponsor
						$editData = true;
						$authors = $submitForm->getData('authors');
						array_push($authors, array());
						$submitForm->setData('authors', $authors);

					} else if (($delAuthor = Request::getUserVar('delAuthor')) && count($delAuthor) == 1) {
						// Delete an author
						$editData = true;
						list($delAuthor) = array_keys($delAuthor);
						$delAuthor = (int) $delAuthor;
						$authors = $submitForm->getData('authors');
						if (isset($authors[$delAuthor]['authorId']) && !empty($authors[$delAuthor]['authorId'])) {
							$deletedAuthors = explode(':', $submitForm->getData('deletedAuthors'));
							array_push($deletedAuthors, $authors[$delAuthor]['authorId']);
							$submitForm->setData('deletedAuthors', join(':', $deletedAuthors));
						}
						array_splice($authors, $delAuthor, 1);
						$submitForm->setData('authors', $authors);

						if ($submitForm->getData('primaryContact') == $delAuthor) {
							$submitForm->setData('primaryContact', 0);
						}

					} else if (Request::getUserVar('moveAuthor')) {
						// Move an author up/down
						$editData = true;
						$moveAuthorDir = Request::getUserVar('moveAuthorDir');
						$moveAuthorDir = $moveAuthorDir == 'u' ? 'u' : 'd';
						$moveAuthorIndex = (int) Request::getUserVar('moveAuthorIndex');
						$authors = $submitForm->getData('authors');

						if (!(($moveAuthorDir == 'u' && $moveAuthorIndex <= 0) || ($moveAuthorDir == 'd' && $moveAuthorIndex >= count($authors) - 1))) {
							$tmpAuthor = $authors[$moveAuthorIndex];
							$primaryContact = $submitForm->getData('primaryContact');
							if ($moveAuthorDir == 'u') {
								$authors[$moveAuthorIndex] = $authors[$moveAuthorIndex - 1];
								$authors[$moveAuthorIndex - 1] = $tmpAuthor;
								if ($primaryContact == $moveAuthorIndex) {
									$submitForm->setData('primaryContact', $moveAuthorIndex - 1);
								} else if ($primaryContact == ($moveAuthorIndex - 1)) {
									$submitForm->setData('primaryContact', $moveAuthorIndex);
								}
							} else {
								$authors[$moveAuthorIndex] = $authors[$moveAuthorIndex + 1];
								$authors[$moveAuthorIndex + 1] = $tmpAuthor;
								if ($primaryContact == $moveAuthorIndex) {
									$submitForm->setData('primaryContact', $moveAuthorIndex + 1);
								} else if ($primaryContact == ($moveAuthorIndex + 1)) {
									$submitForm->setData('primaryContact', $moveAuthorIndex);
								}
							}
						}
						$submitForm->setData('authors', $authors);
					}
					break;

				case 4:
					if (Request::getUserVar('submitUploadSuppFile')) {
						if ($suppFileId = SubmitHandler::submitUploadSuppFile()) {
							Request::redirect(null, null, null, 'submitSuppFile', $suppFileId, array('paperId' => $paperId));
						} else {
							$submitForm->addError('uploadSubmissionFile', __('common.uploadFailed'));
						}
					}
					break;
			}
		}

		if (!isset($editData) && $submitForm->validate()) {
			$paperId = $submitForm->execute();
			$conference =& Request::getConference();
			$schedConf =& Request::getSchedConf();

			// For the "abstract only" or sequential review models, nothing else needs
			// to be collected beyond page 2.
			$reviewMode = $paper?$paper->getReviewMode():$schedConf->getSetting('reviewMode');
			if (($step == 3 && $reviewMode == REVIEW_MODE_BOTH_SEQUENTIAL && !$schedConf->getSetting('acceptSupplementaryReviewMaterials')) || 
					($step == 3 && $reviewMode == REVIEW_MODE_ABSTRACTS_ALONE && !$schedConf->getSetting('acceptSupplementaryReviewMaterials')) ||
					($step == 5 )) {

				// Send a notification to associated users
				import('notification.NotificationManager');
				$notificationManager = new NotificationManager();
				$roleDao =& DAORegistry::getDAO('RoleDAO');
				$notificationUsers = array();
				$conferenceManagers = $roleDao->getUsersByRoleId(ROLE_ID_CONFERENCE_MANAGER, $conference->getId());
				$allUsers = $conferenceManagers->toArray();
				$directors = $roleDao->getUsersByRoleId(ROLE_ID_DIRECTOR, $conference->getId(), $schedConf->getId());
				array_merge($allUsers, $directors->toArray());
				foreach ($allUsers as $user) {
					$notificationUsers[] = array('id' => $user->getId());
				}

				foreach ($notificationUsers as $userRole) {
					$url = Request::url(null, null, 'director', 'submission', $paperId);
					$notificationManager->createNotification(
						$userRole['id'], 'notification.type.paperSubmitted',
						$paper->getLocalizedTitle(), $url, 1, NOTIFICATION_TYPE_PAPER_SUBMITTED
					);
				}

				$templateMgr =& TemplateManager::getManager();
				$templateMgr->assign_by_ref('conference', $conference);
				$templateMgr->assign('paperId', $paperId);
				$templateMgr->assign('helpTopicId','submission.index');
				$templateMgr->display('author/submit/complete.tpl');
			} elseif ($step == 3 && !$schedConf->getSetting('acceptSupplementaryReviewMaterials')) {
				Request::redirect(null, null, null, 'submit', 5, array('paperId' => $paperId));
			} elseif ($step == 1 && ($reviewMode == REVIEW_MODE_ABSTRACTS_ALONE || $reviewMode == REVIEW_MODE_BOTH_SEQUENTIAL)) {
				Request::redirect(null, null, null, 'submit', 3, array('paperId' => $paperId));
 			} elseif ($step == 2 && $reviewMode == REVIEW_MODE_BOTH_SEQUENTIAL) {
 				$nextStep = $schedConf->getSetting('acceptSupplementaryReviewMaterials') ? 4:5;
				Request::redirect(null, null, null, 'submit', $nextStep, array('paperId' => $paperId));
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
		$paperId = (int) Request::getUserVar('paperId');
		$this->validate($paperId, 4);
		$paper =& $this->paper;
		$this->setupTemplate(true);
		$schedConf =& Request::getSchedConf();

		import('file.FileManager');
		$fileManager = new FileManager();
		if ($fileManager->uploadError('uploadSuppFile')) return false;

		if (!$schedConf->getSetting('acceptSupplementaryReviewMaterials')) return false;

		import('author.form.submit.AuthorSubmitSuppFileForm');
		$submitForm = new AuthorSubmitSuppFileForm($paper);
		$submitForm->setData('title', array(AppLocale::getLocale() => __('common.untitled')));
		return $submitForm->execute();
	}

	/**
	 * Display supplementary file submission form.
	 * @param $args array optional, if set the first parameter is the supplementary file to edit
	 */
	function submitSuppFile($args) {
		$paperId = (int) Request::getUserVar('paperId');
		$suppFileId = isset($args[0]) ? (int) $args[0] : 0;

		$this->validate($paperId, 4);
		$this->setupTemplate(true);

		$paper =& $this->paper;
		$schedConf =& Request::getSchedConf();

		if (!$schedConf->getSetting('acceptSupplementaryReviewMaterials')) Request::redirect(null, null, 'index');

		import("author.form.submit.AuthorSubmitSuppFileForm");
		$submitForm = new AuthorSubmitSuppFileForm($paper, $suppFileId);

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
		$paperId = (int) Request::getUserVar('paperId');
		$suppFileId = isset($args[0]) ? (int) $args[0] : 0;

		$this->validate($paperId, 4);
		$this->setupTemplate(true);

		$schedConf =& Request::getSchedConf();
		$paper =& $this->paper;

		if (!$schedConf->getSetting('acceptSupplementaryReviewMaterials')) Request::redirect(null, null, 'index');

		import('author.form.submit.AuthorSubmitSuppFileForm');
		$submitForm = new AuthorSubmitSuppFileForm($paper, $suppFileId);
		$submitForm->readInputData();

		import('file.FileManager');
		$fileManager = new FileManager();
		if ($fileManager->uploadError('uploadSuppFile') && $suppFileId == 0) {
			$submitForm->addError('uploadSubmissionFile', __('common.uploadFailed'));
		}

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

		$paperId = (int) Request::getUserVar('paperId');
		$suppFileId = isset($args[0]) ? (int) $args[0] : 0;

		$this->validate($paperId, 4);
		$this->setupTemplate(true);

		$schedConf =& Request::getSchedConf();
		$paper =& $this->paper;

		if (!$schedConf->getSetting('acceptSupplementaryReviewMaterials')) Request::redirect(null, null, 'index');

		$suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
		$suppFile = $suppFileDao->getSuppFile($suppFileId, $paperId);
		$suppFileDao->deleteSuppFileById($suppFileId, $paperId);

		if ($suppFile->getFileId()) {
			$paperFileManager = new PaperFileManager($paperId);
			$paperFileManager->deleteFile($suppFile->getFileId());
		}

		Request::redirect(null, null, null, 'submit', '4', array('paperId' => $paperId));
	}

	/**
	 * Validation check for submission.
	 * Checks that paper ID is valid, if specified.
	 * @param $paperId int
	 * @param $step int
	 */
	function validate($paperId = null, $step = false) {
		parent::validate();

		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();

		$paperDao =& DAORegistry::getDAO('PaperDAO');
		$user =& Request::getUser();

		if ($step !== false && ($step < 1 || $step > 5 || (!$paperId && $step != 1))) {
			Request::redirect(null, null, null, 'submit', array(1));
		}

		$paper = null;

		if ($paperId) {
			// Check that paper exists for this conference and user and that submission is incomplete
			$paper =& $paperDao->getPaper((int) $paperId);
			if (!$paper || $paper->getUserId() !== $user->getId() || $paper->getSchedConfId() !== $schedConf->getId()) {
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
			if (!$schedConf || (!SchedConfAction::submissionsOpen($schedConf) && !Validation::isDirector($schedConf->getConferenceId(), $schedConf->getId()) && !Validation::isTrackDirector($schedConf->getConferenceId()))) {
				Request::redirect(null, null, 'author', 'index');
			}
		}

		$this->paper =& $paper;
		return true;
	}
}

?>
