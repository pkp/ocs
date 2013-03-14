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
	function submit($args, $request) {
		$user =& $request->getUser();
		$schedConf =& $request->getSchedConf();
		if ($user && $schedConf && !Validation::isAuthor()) {
			// The user is logged in but not a author. If
			// possible, enroll them as a author automatically.
			$request->redirect(
				null, null,
				'user', 'become',
				array('author'),
				array(
					'source' => $request->url(
						null, null, 'author', 'submit'
					)
				)
			);
		}

		$step = (int) array_shift($args);
		$paperId = (int) $request->getUserVar('paperId');

		$this->validate($request, $paperId, $step);
		$this->setupTemplate($request, true);

		$paper =& $this->paper;

		$formClass = "AuthorSubmitStep{$step}Form";
		import("classes.author.form.submit.$formClass");

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
	function saveSubmit($args, $request) {
		$step = (int) array_shift($args);
		$paperId = (int) $request->getUserVar('paperId');

		$this->validate($request, $paperId, $step);
		$this->setupTemplate($request, true);

		$paper =& $this->paper;

		$formClass = "AuthorSubmitStep{$step}Form";
		import("classes.author.form.submit.$formClass");

		$submitForm = new $formClass($paper);
		$submitForm->readInputData();

		if (!HookRegistry::call('SubmitHandler::saveSubmit', array($step, &$paper, &$submitForm))) {

			// Check for any special cases before trying to save
			switch ($step) {
				case 2:
					if ($request->getUserVar('uploadSubmissionFile')) {
						if (!$submitForm->uploadSubmissionFile('submissionFile')) {
							$submitForm->addError('uploadSubmissionFile', __('common.uploadFailed'));
						}
						$editData = true;
					}
					break;

				case 3:
					if ($request->getUserVar('addAuthor')) {
						// Add a sponsor
						$editData = true;
						$authors = $submitForm->getData('authors');
						array_push($authors, array());
						$submitForm->setData('authors', $authors);

					} else if (($delAuthor = $request->getUserVar('delAuthor')) && count($delAuthor) == 1) {
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

					} else if ($request->getUserVar('moveAuthor')) {
						// Move an author up/down
						$editData = true;
						$moveAuthorDir = $request->getUserVar('moveAuthorDir');
						$moveAuthorDir = $moveAuthorDir == 'u' ? 'u' : 'd';
						$moveAuthorIndex = (int) $request->getUserVar('moveAuthorIndex');
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
					if ($request->getUserVar('submitUploadSuppFile')) {
						if ($suppFileId = SubmitHandler::submitUploadSuppFile(array(), $request)) {
							$request->redirect(null, null, null, 'submitSuppFile', $suppFileId, array('paperId' => $paperId));
						} else {
							$submitForm->addError('uploadSubmissionFile', __('common.uploadFailed'));
						}
					}
					break;
			}
		}

		if (!isset($editData) && $submitForm->validate()) {
			$paperId = $submitForm->execute();
			$conference =& $request->getConference();
			$schedConf =& $request->getSchedConf();

			// For the "abstract only" or sequential review models, nothing else needs
			// to be collected beyond page 2.
			$reviewMode = $paper?$paper->getReviewMode():$schedConf->getSetting('reviewMode');
			if (($step == 3 && $reviewMode == REVIEW_MODE_BOTH_SEQUENTIAL && !$schedConf->getSetting('acceptSupplementaryReviewMaterials')) ||
					($step == 3 && $reviewMode == REVIEW_MODE_ABSTRACTS_ALONE && !$schedConf->getSetting('acceptSupplementaryReviewMaterials')) ||
					($step == 5 )) {

				// Send a notification to associated users
				import('classes.notification.NotificationManager');
				$notificationManager = new NotificationManager();
				$roleDao = DAORegistry::getDAO('RoleDAO');
				$directors = $roleDao->getUsersByRoleId(ROLE_ID_DIRECTOR, $conference->getId(), $schedConf->getId());
				$notificationUsers = array();
				foreach ($directors->toArray() as $user) {
					$notificationUsers[] = array('id' => $user->getId());
				}

				foreach ($notificationUsers as $userRole) {
					$notificationManager->createNotification(
						$request, $userRole['id'], NOTIFICATION_TYPE_PAPER_SUBMITTED,
						$conference->getId(), ASSOC_TYPE_PAPER, $paperId
					);
				}

				$templateMgr =& TemplateManager::getManager();
				$templateMgr->assign_by_ref('conference', $conference);
				$templateMgr->assign('paperId', $paperId);
				$templateMgr->assign('helpTopicId','submission.index');
				$templateMgr->display('author/submit/complete.tpl');
			} elseif ($step == 3 && !$schedConf->getSetting('acceptSupplementaryReviewMaterials')) {
				$request->redirect(null, null, null, 'submit', 5, array('paperId' => $paperId));
			} elseif ($step == 1 && ($reviewMode == REVIEW_MODE_ABSTRACTS_ALONE || $reviewMode == REVIEW_MODE_BOTH_SEQUENTIAL)) {
				$request->redirect(null, null, null, 'submit', 3, array('paperId' => $paperId));
 			} elseif ($step == 2 && $reviewMode == REVIEW_MODE_BOTH_SEQUENTIAL) {
 				$nextStep = $schedConf->getSetting('acceptSupplementaryReviewMaterials') ? 4:5;
				$request->redirect(null, null, null, 'submit', $nextStep, array('paperId' => $paperId));
 			} else {
				$request->redirect(null, null, null, 'submit', $step+1, array('paperId' => $paperId));
			}

		} else {
			$submitForm->display();
		}
	}

	/**
	 * Create new supplementary file with a uploaded file.
	 */
	function submitUploadSuppFile($args, $request) {
		$paperId = (int) $request->getUserVar('paperId');
		$this->validate($request, $paperId, 4);
		$paper =& $this->paper;
		$this->setupTemplate($request, true);
		$schedConf =& $request->getSchedConf();

		import('lib.pkp.classes.file.FileManager');
		$fileManager = new FileManager();
		if ($fileManager->uploadError('uploadSuppFile')) return false;

		if (!$schedConf->getSetting('acceptSupplementaryReviewMaterials')) return false;

		import('classes.author.form.submit.AuthorSubmitSuppFileForm');
		$submitForm = new AuthorSubmitSuppFileForm($paper);
		$submitForm->setData('title', array($paper->getLocale() => __('common.untitled')));
		return $submitForm->execute();
	}

	/**
	 * Display supplementary file submission form.
	 * @param $args array optional, if set the first parameter is the supplementary file to edit
	 */
	function submitSuppFile($args, $request) {
		$paperId = (int) $request->getUserVar('paperId');
		$suppFileId = (int) array_shift($args);

		$this->validate($request, $paperId, 4);
		$this->setupTemplate($request, true);

		$paper =& $this->paper;
		$schedConf =& $request->getSchedConf();

		if (!$schedConf->getSetting('acceptSupplementaryReviewMaterials')) $request->redirect(null, null, 'index');

		import('classes.author.form.submit.AuthorSubmitSuppFileForm');
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
	function saveSubmitSuppFile($args, $request) {
		$paperId = (int) $request->getUserVar('paperId');
		$suppFileId = (int) array_shift($args);

		$this->validate($request, $paperId, 4);
		$this->setupTemplate($request, true);

		$schedConf =& $request->getSchedConf();
		$paper =& $this->paper;

		if (!$schedConf->getSetting('acceptSupplementaryReviewMaterials')) $request->redirect(null, null, 'index');

		import('classes.author.form.submit.AuthorSubmitSuppFileForm');
		$submitForm = new AuthorSubmitSuppFileForm($paper, $suppFileId);
		$submitForm->readInputData();

		import('lib.pkp.classes.file.FileManager');
		$fileManager = new FileManager();
		if ($fileManager->uploadError('uploadSuppFile') && $suppFileId == 0) {
			$submitForm->addError('uploadSubmissionFile', __('common.uploadFailed'));
		}

		if ($submitForm->validate()) {
			$submitForm->execute();
			$request->redirect(null, null, null, 'submit', '4', array('paperId' => $paperId));
		} else {
			$submitForm->display();
		}
	}

	/**
	 * Delete a supplementary file.
	 * @param $args array, the first parameter is the supplementary file to delete
	 */
	function deleteSubmitSuppFile($args, $request) {
		import('classes.file.PaperFileManager');

		$paperId = (int) $request->getUserVar('paperId');
		$suppFileId = (int) array_shift($args);

		$this->validate($request, $paperId, 4);
		$this->setupTemplate($request, true);

		$schedConf =& $request->getSchedConf();
		$paper =& $this->paper;

		if (!$schedConf->getSetting('acceptSupplementaryReviewMaterials')) $request->redirect(null, null, 'index');

		$suppFileDao = DAORegistry::getDAO('SuppFileDAO');
		$suppFile = $suppFileDao->getSuppFile($suppFileId, $paperId);
		$suppFileDao->deleteSuppFileById($suppFileId, $paperId);

		if ($suppFile->getFileId()) {
			$paperFileManager = new PaperFileManager($paperId);
			$paperFileManager->deleteFile($suppFile->getFileId());
		}

		$request->redirect(null, null, null, 'submit', '4', array('paperId' => $paperId));
	}

	/**
	 * Validation check for submission.
	 * Checks that paper ID is valid, if specified.
	 * @param $request object
	 * @param $paperId int
	 * @param $step int
	 */
	function validate($request, $paperId = null, $step = false) {
		parent::validate();

		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();

		$paperDao = DAORegistry::getDAO('PaperDAO');
		$user =& $request->getUser();

		if ($step !== false && ($step < 1 || $step > 5 || (!$paperId && $step != 1))) {
			$request->redirect(null, null, null, 'submit', array(1));
		}

		$paper = null;

		if ($paperId) {
			// Check that paper exists for this conference and user and that submission is incomplete
			$paper =& $paperDao->getPaper((int) $paperId);
			if (!$paper || $paper->getUserId() !== $user->getId() || $paper->getSchedConfId() !== $schedConf->getId()) {
				$request->redirect(null, null, null, 'submit');
			}

			if($step !== false && $step > $paper->getSubmissionProgress()) {
				$request->redirect(null, null, null, 'submit');
			}
		} else {
			// If the paper does not exist, require that the
			// submission window be open or that this user be a
			// director or track director.
			import('classes.schedConf.SchedConfAction');
			$schedConf =& $request->getSchedConf();
			if (!$schedConf || (!SchedConfAction::submissionsOpen($schedConf) && !Validation::isDirector($schedConf->getConferenceId(), $schedConf->getId()) && !Validation::isTrackDirector($schedConf->getConferenceId()))) {
				$request->redirect(null, null, 'author', 'index');
			}
		}

		$this->paper =& $paper;
		return true;
	}
}

?>
