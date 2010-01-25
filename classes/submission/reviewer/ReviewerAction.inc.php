<?php

/**
 * @file ReviewerAction.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewerAction
 * @ingroup submission
 *
 * @brief ReviewerAction class.
 *
 */

// $Id$


import('submission.common.Action');

class ReviewerAction extends Action {

	/**
	 * Constructor.
	 */
	function ReviewerAction() {

	}

	/**
	 * Actions.
	 */

	/**
	 * Records whether or not the reviewer accepts the review assignment.
	 * @param $user object
	 * @param $reviewerSubmission object
	 * @param $decline boolean
	 * @param $send boolean
	 */
	function confirmReview($reviewerSubmission, $decline, $send) {
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');

		$reviewId = $reviewerSubmission->getReviewId();

		$reviewAssignment =& $reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$reviewer =& $userDao->getUser($reviewAssignment->getReviewerId());
		if (!isset($reviewer)) return true;

		// Only confirm the review for the reviewer if 
		// he has not previously done so.
		if ($reviewAssignment->getDateConfirmed() == null) {
			import('mail.PaperMailTemplate');
			$email = new PaperMailTemplate($reviewerSubmission, $decline?'REVIEW_DECLINE':'REVIEW_CONFIRM');
			// Must explicitly set sender because we may be here on an access
			// key, in which case the user is not technically logged in
			$email->setFrom($reviewer->getEmail(), $reviewer->getFullName());
			if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
				HookRegistry::call('ReviewerAction::confirmReview', array(&$reviewerSubmission, &$email, $decline));
				if ($email->isEnabled()) {
					$email->setAssoc($decline?PAPER_EMAIL_REVIEW_DECLINE:PAPER_EMAIL_REVIEW_CONFIRM, PAPER_EMAIL_TYPE_REVIEW, $reviewId);
					$email->send();
				}

				$reviewAssignment->setDeclined($decline);
				$reviewAssignment->setDateConfirmed(Core::getCurrentDate());
				$reviewAssignment->stampModified();
				$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);

				// Add log
				import('paper.log.PaperLog');
				import('paper.log.PaperEventLogEntry');

				$entry = new PaperEventLogEntry();
				$entry->setPaperId($reviewAssignment->getPaperId());
				$entry->setUserId($reviewer->getId());
				$entry->setDateLogged(Core::getCurrentDate());
				$entry->setEventType($decline?PAPER_LOG_REVIEW_DECLINE:PAPER_LOG_REVIEW_ACCEPT);
				$entry->setLogMessage($decline?'log.review.reviewDeclined':'log.review.reviewAccepted', array('reviewerName' => $reviewer->getFullName(), 'paperId' => $reviewAssignment->getPaperId(), 'stage' => $reviewAssignment->getStage()));
				$entry->setAssocType(LOG_TYPE_REVIEW);
				$entry->setAssocId($reviewAssignment->getReviewId());

				PaperLog::logEventEntry($reviewAssignment->getPaperId(), $entry);

				return true;
			} else {
				if (!Request::getUserVar('continued')) {
					$reviewingTrackDirectors = $email->toAssignedTrackDirectors($reviewerSubmission->getPaperId());
					if (!empty($reviewingTrackDirectors)) $assignedDirectors = $email->toAssignedDirectors($reviewerSubmission->getPaperId());
					else $assignedDirectors = $email->toAssignedDirectors($reviewerSubmission->getPaperId());
					if (empty($assignedDirectors) && empty($reviewingTrackDirectors)) {
						$schedConf =& Request::getSchedConf();
						$email->addRecipient($schedConf->getSetting('contactEmail'), $schedConf->getSetting('contactName'));
						$editorialContactName = $schedConf->getSetting('contactName');
					} else {
						if (!empty($reviewingTrackDirectors)) $editorialContact = array_shift($reviewingTrackDirectors);
						else $editorialContact = array_shift($assignedDirectors);
						$editorialContactName = $editorialContact->getDirectorFullName();
					}

					$email->assignParams(array(
						'editorialContactName' => $editorialContactName,
						'reviewerName' => $reviewer->getFullName(),
						'reviewDueDate' => ($reviewAssignment->getDateDue() === null ? Locale::translate('common.noDate') : strftime(Config::getVar('general', 'date_format_short'), strtotime($reviewAssignment->getDateDue())))
					));
				}
				$paramArray = array('reviewId' => $reviewId);
				if ($decline) $paramArray['declineReview'] = 1;
				$email->displayEditForm(Request::url(null, null, 'reviewer', 'confirmReview'), $paramArray);
				return false;
			}
		}
		return true;
	}

	/**
	 * Records the reviewer's submission recommendation.
	 * @param $reviewId int
	 * @param $recommendation int
	 * @param $send boolean
	 */
	function recordRecommendation(&$reviewerSubmission, $recommendation, $send) {
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');

		// Check validity of selected recommendation
		$reviewerRecommendationOptions =& ReviewAssignment::getReviewerRecommendationOptions();
		if (!isset($reviewerRecommendationOptions[$recommendation])) return true;

		$reviewAssignment =& $reviewAssignmentDao->getReviewAssignmentById($reviewerSubmission->getReviewId());
		$reviewer =& $userDao->getUser($reviewAssignment->getReviewerId());
		if (!isset($reviewer)) return true;

		// Only record the reviewers recommendation if
		// no recommendation has previously been submitted.
		if ($reviewAssignment->getRecommendation() === null || $reviewAssignment->getRecommendation === '') {
			import('mail.PaperMailTemplate');
			$email = new PaperMailTemplate($reviewerSubmission, 'REVIEW_COMPLETE');
			// Must explicitly set sender because we may be here on an access
			// key, in which case the user is not technically logged in
			$email->setFrom($reviewer->getEmail(), $reviewer->getFullName());

			if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
				HookRegistry::call('ReviewerAction::recordRecommendation', array(&$reviewerSubmission, &$email, $recommendation));
				if ($email->isEnabled()) {
					$email->setAssoc(PAPER_EMAIL_REVIEW_COMPLETE, PAPER_EMAIL_TYPE_REVIEW, $reviewerSubmission->getReviewId());
					$email->send();
				}

				$reviewAssignment->setRecommendation($recommendation);
				$reviewAssignment->setDateCompleted(Core::getCurrentDate());
				$reviewAssignment->stampModified();
				$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);

				// Add log
				import('paper.log.PaperLog');
				import('paper.log.PaperEventLogEntry');

				$entry = new PaperEventLogEntry();
				$entry->setPaperId($reviewAssignment->getPaperId());
				$entry->setUserId($reviewer->getId());
				$entry->setDateLogged(Core::getCurrentDate());
				$entry->setEventType(PAPER_LOG_REVIEW_RECOMMENDATION);
				$entry->setLogMessage('log.review.reviewRecommendationSet', array('reviewerName' => $reviewer->getFullName(), 'paperId' => $reviewAssignment->getPaperId(), 'stage' => $reviewAssignment->getStage()));
				$entry->setAssocType(LOG_TYPE_REVIEW);
				$entry->setAssocId($reviewAssignment->getReviewId());

				PaperLog::logEventEntry($reviewAssignment->getPaperId(), $entry);
			} else {
				if (!Request::getUserVar('continued')) {
					$assignedDirectors = $email->toAssignedDirectors($reviewerSubmission->getPaperId());
					$reviewingTrackDirectors = $email->toAssignedTrackDirectors($reviewerSubmission->getPaperId());
					if (empty($assignedDirectors) && empty($reviewingTrackDirectors)) {
						$schedConf =& Request::getSchedConf();
						$email->addRecipient($schedConf->getSetting('contactEmail'), $schedConf->getSetting('contactName'));
						$editorialContactName = $schedConf->getSetting('contactName');
					} else {
						if (!empty($reviewingTrackDirectors)) $editorialContact = array_shift($reviewingTrackDirectors);
						else $editorialContact = array_shift($assignedDirectors);
						$editorialContactName = $editorialContact->getDirectorFullName();
					}

					$reviewerRecommendationOptions =& ReviewAssignment::getReviewerRecommendationOptions();

					$email->assignParams(array(
						'editorialContactName' => $editorialContactName,
						'reviewerName' => $reviewer->getFullName(),
						'paperTitle' => strip_tags($reviewerSubmission->getLocalizedTitle()),
						'recommendation' => Locale::translate($reviewerRecommendationOptions[$recommendation])
					));
				}

				$email->displayEditForm(Request::url(null, null, 'reviewer', 'recordRecommendation'),
					array('reviewId' => $reviewerSubmission->getReviewId(), 'recommendation' => $recommendation)
				);
				return false;
			}
		}
		return true;
	}

	/**
	 * Upload the annotated version of a paper.
	 * @param $reviewId int
	 */
	function uploadReviewerVersion($reviewId) {
		import('file.PaperFileManager');
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');		
		$reviewAssignment =& $reviewAssignmentDao->getReviewAssignmentById($reviewId);

		$paperFileManager = new PaperFileManager($reviewAssignment->getPaperId());

		// Only upload the file if the reviewer has yet to submit a recommendation
		if (!(($reviewAssignment->getRecommendation() === null || $reviewAssignment->getRecommendation() === '') && !$reviewAssignment->getCancelled())) return false;

		$fileName = 'upload';
		if ($paperFileManager->uploadError($fileName)) return false;
		if (!$paperFileManager->uploadedFileExists($fileName)) return false;
		HookRegistry::call('ReviewerAction::uploadReviewFile', array(&$reviewAssignment));
		if ($reviewAssignment->getReviewerFileId() != null) {
			$fileId = $paperFileManager->uploadReviewFile($fileName, $reviewAssignment->getReviewerFileId());
		} else {
			$fileId = $paperFileManager->uploadReviewFile($fileName);
		}

		if ($fileId == 0) return false;

		$reviewAssignment->setReviewerFileId($fileId);
		$reviewAssignment->stampModified();
		$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);

		// Add log
		import('paper.log.PaperLog');
		import('paper.log.PaperEventLogEntry');

		$userDao =& DAORegistry::getDAO('UserDAO');
		$reviewer =& $userDao->getUser($reviewAssignment->getReviewerId());

		$entry = new PaperEventLogEntry();
		$entry->setPaperId($reviewAssignment->getPaperId());
		$entry->setUserId($reviewer->getId());
		$entry->setDateLogged(Core::getCurrentDate());
		$entry->setEventType(PAPER_LOG_REVIEW_FILE);
		$entry->setLogMessage('log.review.reviewerFile');
		$entry->setAssocType(LOG_TYPE_REVIEW);
		$entry->setAssocId($reviewAssignment->getReviewId());

		PaperLog::logEventEntry($reviewAssignment->getPaperId(), $entry);
		return true;
	}

	/**
	 * Delete an annotated version of a paper.
	 * @param $reviewId int
	 * @param $fileId int
	 * @param $revision int If null, then all revisions are deleted.
	 */
	function deleteReviewerVersion($reviewId, $fileId, $revision = null) {
		import("file.PaperFileManager");

		$paperId = Request::getUserVar('paperId');
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignment =& $reviewAssignmentDao->getReviewAssignmentById($reviewId);

		if (!HookRegistry::call('ReviewerAction::deleteReviewerVersion', array(&$reviewAssignment, &$fileId, &$revision))) {
			$paperFileManager = new PaperFileManager($reviewAssignment->getPaperId());
			$paperFileManager->deleteFile($fileId, $revision);
		}
	}

	/**
	 * View reviewer comments.
	 * @param $user object Current user
	 * @param $paper object
	 * @param $reviewId int
	 */
	function viewPeerReviewComments(&$user, &$paper, $reviewId) {
		if (!HookRegistry::call('ReviewerAction::viewPeerReviewComments', array(&$user, &$paper, &$reviewId))) {
			import("submission.form.comment.PeerReviewCommentForm");

			$commentForm = new PeerReviewCommentForm($paper, $reviewId, ROLE_ID_REVIEWER);

			$commentForm->setUser($user);
			$commentForm->initData();
			$commentForm->setData('reviewId', $reviewId);
			$commentForm->display();
		}
	}

	/**
	 * Post reviewer comments.
	 * @param $user object Current user
	 * @param $paper object
	 * @param $reviewId int
	 * @param $emailComment boolean
	 */
	function postPeerReviewComment(&$user, &$paper, $reviewId, $emailComment) {
		if (!HookRegistry::call('ReviewerAction::postPeerReviewComment', array(&$user, &$paper, &$reviewId, &$emailComment))) {
			import("submission.form.comment.PeerReviewCommentForm");

			$commentForm = new PeerReviewCommentForm($paper, $reviewId, ROLE_ID_REVIEWER);
			$commentForm->setUser($user);
			$commentForm->readInputData();

			if ($commentForm->validate()) {
				$commentForm->execute();
				
				// Send a notification to associated users
				import('notification.Notification');
				$notificationUsers = $paper->getAssociatedUserIds();
				foreach ($notificationUsers as $userRole) {
					$url = Request::url(null, null, $userRole['role'], 'submissionReview', $paper->getPaperId(), null, 'peerReview');
					Notification::createNotification($userRole['id'], "notification.type.reviewerComment",
						$paper->getLocalizedTitle(), $url, 1, NOTIFICATION_TYPE_REVIEWER_COMMENT);
				}


				if ($emailComment) {
					$commentForm->email();
				}

			} else {
				$commentForm->display();
				return false;
			}
			return true;
		}
	}
	
		/**
	 * Edit review form response.
	 * @param $reviewId int
	 * @param $reviewFormId int
	 */
	function editReviewFormResponse($reviewId, $reviewFormId) {
		if (!HookRegistry::call('ReviewerAction::editReviewFormResponse', array($reviewId, $reviewFormId))) {
			import('submission.form.ReviewFormResponseForm');

			$reviewForm = new ReviewFormResponseForm($reviewId, $reviewFormId);
			$reviewForm->initData();
			$reviewForm->display();
		}
	}

	/**
	 * Save review form response.
	 * @param $reviewId int
	 * @param $reviewFormId int
	 */
	function saveReviewFormResponse($reviewId, $reviewFormId) {
		if (!HookRegistry::call('ReviewerAction::saveReviewFormResponse', array($reviewId, $reviewFormId))) {
			import('submission.form.ReviewFormResponseForm');

			$reviewForm = new ReviewFormResponseForm($reviewId, $reviewFormId);
			$reviewForm->readInputData();
			if ($reviewForm->validate()) {
				$reviewForm->execute();
				
				// Send a notification to associated users
				import('notification.Notification');
				$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
				$reviewAssignment = $reviewAssignmentDao->getReviewAssignmentById($reviewId);
				$paperId = $reviewAssignment->getPaperId();
				$paperDao =& DAORegistry::getDAO('PaperDAO'); 
				$paper =& $paperDao->getPaper($paperId);
				$notificationUsers = $paper->getAssociatedUserIds();
				foreach ($notificationUsers as $userRole) {
					$url = Request::url(null, null, $userRole['role'], 'submissionReview', $paper->getPaperId(), null, 'peerReview');
					Notification::createNotification($userRole['id'], "notification.type.reviewerFormComment",
						$paper->getLocalizedTitle(), $url, 1, NOTIFICATION_TYPE_REVIEWER_FORM_COMMENT);
				}

			} else {
				$reviewForm->display();
				return false;
			}
			return true;
		}
	}

	//
	// Misc
	//

	/**
	 * Download a file a reviewer has access to.
	 * @param $reviewId int
	 * @param $paper object
	 * @param $fileId int
	 * @param $revision int
	 */
	function downloadReviewerFile($reviewId, &$paper, $fileId, $revision = null) {
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');		
		$reviewAssignment =& $reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$conference =& Request::getConference();

		$canDownload = false;

		// Reviewers have access to:
		// 1) The current revision of the file to be reviewed.
		// 2) Any file that he uploads.
		// 3) Any supplementary file that is visible to reviewers.
		if ((!$reviewAssignment->getDateConfirmed() || $reviewAssignment->getDeclined()) && $conference->getSetting('restrictReviewerFileAccess')) {
			// Restrict files until review is accepted
		} else if ($reviewAssignment->getReviewFileId() == $fileId) {
			if ($revision != null) {
				if ($reviewAssignment->getReviewRevision() == null) $canDownload = true;
				else $canDownload = ($reviewAssignment->getReviewRevision() == $revision);
			}
		} else if ($reviewAssignment->getReviewerFileId() == $fileId) {
			$canDownload = true;
		} else {
			foreach ($reviewAssignment->getSuppFiles() as $suppFile) {
				if ($suppFile->getFileId() == $fileId && $suppFile->getShowReviewers()) {
					$canDownload = true;
				}
			}
		}

		$result = false;
		if (!HookRegistry::call('ReviewerAction::downloadReviewerFile', array(&$paper, &$fileId, &$revision, &$canDownload, &$result))) {
			if ($canDownload) {
				return Action::downloadFile($paper->getPaperId(), $fileId, $revision);
			} else {
				return false;
			}
		}
		return $result;
	}

	/**
	 * Edit comment.
	 * @param $commentId int
	 */
	function editComment ($paper, $comment, $reviewId) {
		if (!HookRegistry::call('ReviewerAction::editComment', array(&$paper, &$comment, &$reviewId))) {
			import ("submission.form.comment.EditCommentForm");

			$commentForm = new EditCommentForm ($paper, $comment);
			$commentForm->initData();
			$commentForm->setData('reviewId', $reviewId);
			$commentForm->display(array('reviewId' => $reviewId));
		}
	}
}

?>
