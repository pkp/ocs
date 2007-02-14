<?php

/**
 * TrackDirectorAction.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * TrackDirectorAction class.
 *
 * $Id$
 */

import('submission.common.Action');

class TrackDirectorAction extends Action {

	/**
	 * Constructor.
	 */
	function TrackDirectorAction() {
		parent::Action();
	}
	
	/**
	 * Actions.
	 */
	 
	/**
	 * Changes the track a paper belongs in.
	 * @param $trackDirectorSubmission int
	 * @param $trackId int
	 */
	function changeTrack($trackDirectorSubmission, $trackId) {
		if (!HookRegistry::call('TrackDirectorAction::changeTrack', array(&$trackDirectorSubmission, $trackId))) {
			$trackDirectorSubmissionDao = &DAORegistry::getDAO('TrackDirectorSubmissionDAO');
			$trackDirectorSubmission->setTrackId($trackId);
			$trackDirectorSubmissionDao->updateTrackDirectorSubmission($trackDirectorSubmission);
		}
	}
	 
	/**
	 * Records an editor's submission decision.
	 * @param $trackDirectorSubmission object
	 * @param $decision int
	 */
	function recordDecision($trackDirectorSubmission, $decision) {
		$editAssignments =& $trackDirectorSubmission->getEditAssignments();
		if (empty($editAssignments)) return;

		$trackDirectorSubmissionDao = &DAORegistry::getDAO('TrackDirectorSubmissionDAO');
		$user = &Request::getUser();
		$editorDecision = array(
			'editDecisionId' => null,
			'editorId' => $user->getUserId(),
			'decision' => $decision,
			'dateDecided' => date(Core::getCurrentDate())
		);

		if (!HookRegistry::call('TrackDirectorAction::recordDecision', array(&$trackDirectorSubmission, $editorDecision))) {
			if ($decision == SUBMISSION_EDITOR_DECISION_DECLINE) {
				$trackDirectorSubmission->setStatus(SUBMISSION_STATUS_DECLINED);
				$trackDirectorSubmission->stampStatusModified();
			} else {
				$trackDirectorSubmission->setStatus(SUBMISSION_STATUS_QUEUED);		
				$trackDirectorSubmission->stampStatusModified();
			}
		
			$trackDirectorSubmission->addDecision($editorDecision, $trackDirectorSubmission->getReviewProgress(), $trackDirectorSubmission->getCurrentRound());
			$decisions = TrackDirectorSubmission::getEditorDecisionOptions();
			// Add log
			import('paper.log.PaperLog');
			import('paper.log.PaperEventLogEntry');
			PaperLog::logEvent($trackDirectorSubmission->getPaperId(), PAPER_LOG_EDITOR_DECISION, LOG_TYPE_EDITOR, $user->getUserId(), 'log.editor.decision', array('editorName' => $user->getFullName(), 'paperId' => $trackDirectorSubmission->getPaperId(), 'decision' => Locale::translate($decisions[$decision])));
		}
		
		if($decision == SUBMISSION_EDITOR_DECISION_ACCEPT) {
			// completeReview will take care of updating the
			// submission with the new decision.
			TrackDirectorAction::completeReview($trackDirectorSubmission);
		} else {
			// Insert the new decision.
			$trackDirectorSubmissionDao->updateTrackDirectorSubmission($trackDirectorSubmission);
		}
	}
	
	/**
	 * After a decision has been recorded, bumps the paper to the next stage.
	 * If the submission requires completion, it's sent back to the presenter.
	 * If not, review is complete, and the paper can be released.
	 * @param $schedConf object
	 * @param $trackDirectorSubmission object
	 * @param $decision int
	 */
	function completeReview($trackDirectorSubmission) {
		$schedConf =& Request::getSchedConf();
		
		if($schedConf->getCollectPapersWithAbstracts() ||
				!$schedConf->getAcceptPapers()) {

			// Only one review was necessary; the submission is complete.
			$trackDirectorSubmission->setReviewProgress(REVIEW_PROGRESS_COMPLETE);
			$trackDirectorSubmission->setStatus(SUBMISSION_STATUS_PUBLISHED);

		} else {
			// two-stage submission; paper required
			// The submission is incomplete, and needs the presenter to submit
			// more materials (potentially for another round of reviews)

			if($trackDirectorSubmission->getReviewProgress() == REVIEW_PROGRESS_ABSTRACT) {

				$oldRound = $trackDirectorSubmission->getCurrentRound();

				// We've just completed reviewing the abstract. If the paper needs
				// a separate review progress, flag it as such and move it back
				// to review round 1.
				if($schedConf->getReviewPapers()) {
					$trackDirectorSubmission->setReviewProgress(REVIEW_PROGRESS_PAPER);
					$trackDirectorSubmission->setCurrentRound(1);
				} else {
					$trackDirectorSubmission->setReviewProgress(REVIEW_PROGRESS_COMPLETE);
				}

				// The paper itself needs to be collected. Flag it so the presenter
				// may complete it.
				$trackDirectorSubmission->setSubmissionProgress(3);

				// TODO: notify the presenter the submission must be completed.
				// Q: should the editor be given this option explicitly?

				// Now, reassign all reviewers that submitted a review for the last
				// round of reviews.
				foreach ($trackDirectorSubmission->getReviewAssignments(REVIEW_PROGRESS_ABSTRACT, $oldRound) as $reviewAssignment) {
					if ($reviewAssignment->getRecommendation() != null) {
						// This reviewer submitted a review; reassign them
						TrackDirectorAction::addReviewer($trackDirectorSubmission, $reviewAssignment->getReviewerId(), REVIEW_PROGRESS_PAPER, 1);
					}
				}

			} else { // REVIEW_PROGRESS_PAPER

				// Mark the review as complete
				$trackDirectorSubmission->setReviewProgress(REVIEW_PROGRESS_COMPLETE);
				$trackDirectorSubmission->setStatus(SUBMISSION_STATUS_PUBLISHED);

				// TODO: log? notify presenters?

			}
		}

		$trackDirectorSubmissionDao =& DAORegistry::getDao('TrackDirectorSubmissionDAO');
		$trackDirectorSubmissionDao->updateTrackDirectorSubmission($trackDirectorSubmission);
		$trackDirectorSubmission->stampStatusModified();

		// Commit the paper changes
		$paperDao = &DAORegistry::getDao('PaperDAO');
		$paperDao->updatePaper($trackDirectorSubmission);
	}
	
	/**
	 * Assigns a reviewer to a submission.
	 * @param $trackDirectorSubmission object
	 * @param $reviewerId int
	 * @param $type int
	 * @param $round int
	 */
	function addReviewer($trackDirectorSubmission, $reviewerId, $type, $round) {
		$trackDirectorSubmissionDao = &DAORegistry::getDAO('TrackDirectorSubmissionDAO');
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &Request::getUser();
		
		$reviewer = &$userDao->getUser($reviewerId);

		// Check to see if the requested reviewer is not already
		// assigned to review this paper.
		if ($round == null) {
			$round = $trackDirectorSubmission->getCurrentRound();
		}
		
		$assigned = $trackDirectorSubmissionDao->reviewerExists($trackDirectorSubmission->getPaperId(), $reviewerId, $type, $round);
				
		// Only add the reviewer if he has not already
		// been assigned to review this paper.
		if (!$assigned && isset($reviewer) && !HookRegistry::call('TrackDirectorAction::addReviewer', array(&$trackDirectorSubmission, $reviewerId))) {
			$reviewAssignment = &new ReviewAssignment();
			$reviewAssignment->setReviewerId($reviewerId);
			$reviewAssignment->setDateAssigned(Core::getCurrentDate());
			$reviewAssignment->setType($type);
			$reviewAssignment->setRound($round);
			
			$trackDirectorSubmission->AddReviewAssignment($reviewAssignment);
			$trackDirectorSubmissionDao->updateTrackDirectorSubmission($trackDirectorSubmission);
		
			$reviewAssignment = $reviewAssignmentDao->getReviewAssignment($trackDirectorSubmission->getPaperId(), $reviewerId, $type, $round);

			$schedConf = &Request::getSchedConf();
			if ($schedConf->getSetting('numWeeksPerReview', true) != null)
				TrackDirectorAction::setDueDate($trackDirectorSubmission->getPaperId(), $reviewAssignment->getReviewId(), null, $schedConf->getSetting('numWeeksPerReview',true));
			
			// Add log
			import('paper.log.PaperLog');
			import('paper.log.PaperEventLogEntry');
			PaperLog::logEvent($trackDirectorSubmission->getPaperId(), PAPER_LOG_REVIEW_ASSIGN, LOG_TYPE_REVIEW, $reviewAssignment->getReviewId(), 'log.review.reviewerAssigned', array('reviewerName' => $reviewer->getFullName(), 'paperId' => $trackDirectorSubmission->getPaperId(), 'round' => $round, 'type' => $type));
		}
	}
	
	/**
	 * Clears a review assignment from a submission.
	 * @param $trackDirectorSubmission object
	 * @param $reviewId int
	 */
	function clearReview($trackDirectorSubmission, $reviewId) {
		$trackDirectorSubmissionDao = &DAORegistry::getDAO('TrackDirectorSubmissionDAO');
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &Request::getUser();
		
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		
		if (isset($reviewAssignment) && $reviewAssignment->getPaperId() == $trackDirectorSubmission->getPaperId() && !HookRegistry::call('TrackDirectorAction::clearReview', array(&$trackDirectorSubmission, $reviewAssignment))) {
			$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
			if (!isset($reviewer)) return false;
			$trackDirectorSubmission->removeReviewAssignment($reviewId);
			$trackDirectorSubmissionDao->updateTrackDirectorSubmission($trackDirectorSubmission);
			
			// Add log
			import('paper.log.PaperLog');
			import('paper.log.PaperEventLogEntry');
			PaperLog::logEvent($trackDirectorSubmission->getPaperId(), PAPER_LOG_REVIEW_CLEAR, LOG_TYPE_REVIEW, $reviewAssignment->getReviewId(), 'log.review.reviewCleared', array('reviewerName' => $reviewer->getFullName(), 'paperId' => $trackDirectorSubmission->getPaperId(), 'type' => $reviewAssignment->getType(), 'round' => $reviewAssignment->getRound()));
		}		
	}

	/**
	 * Notifies a reviewer about a review assignment.
	 * @param $trackDirectorSubmission object
	 * @param $reviewId int
	 * @return boolean true iff ready for redirect
	 */
	function notifyReviewer($trackDirectorSubmission, $reviewId, $send = false) {
		$trackDirectorSubmissionDao = &DAORegistry::getDAO('TrackDirectorSubmissionDAO');
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		
		$conference = &Request::getConference();
		$schedConf = &Request::getSchedConf();
		$user = &Request::getUser();
		
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);

		$reviewerAccessKeysEnabled = $conference->getSetting('reviewerAccessKeysEnabled');

		// If we're using access keys, disable the address fields
		// for this message. (Prevents security issue: track director
		// could CC or BCC someone else, or change the reviewer address,
		// in order to get the access key.)
		$preventAddressChanges = $reviewerAccessKeysEnabled;

		import('mail.PaperMailTemplate');

		$email = &new PaperMailTemplate($trackDirectorSubmission, $reviewerAccessKeysEnabled?'REVIEW_REQUEST_ONECLICK':'REVIEW_REQUEST');

		if ($preventAddressChanges) {
			$email->setAddressFieldsEnabled(false);
		}

		if ($reviewAssignment->getPaperId() == $trackDirectorSubmission->getPaperId()) {
			$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
			if (!isset($reviewer)) return true;
			
			if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
				HookRegistry::call('TrackDirectorAction::notifyReviewer', array(&$trackDirectorSubmission, &$reviewAssignment, &$email));
				if ($email->isEnabled()) {
					$email->setAssoc(PAPER_EMAIL_REVIEW_NOTIFY_REVIEWER, PAPER_EMAIL_TYPE_REVIEW, $reviewId);
					if ($reviewerAccessKeysEnabled) {
						import('security.AccessKeyManager');
						import('pages.reviewer.ReviewerHandler');
						$accessKeyManager =& new AccessKeyManager();

						// Key lifetime is the typical review period plus four weeks
						$keyLifetime = ($schedConf->getSetting('numWeeksPerReview') + 4) * 7;

						$email->addPrivateParam('ACCESS_KEY', $accessKeyManager->createKey('ReviewerContext', $reviewer->getUserId(), $reviewId, $keyLifetime));
					}

					if ($preventAddressChanges) {
						// Ensure that this messages goes to the reviewer, and the reviewer ONLY.
						$email->clearAllRecipients();
						$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());
					}
					$email->send();
				}
				
				$reviewAssignment->setDateNotified(Core::getCurrentDate());
				$reviewAssignment->setCancelled(0);
				$reviewAssignment->stampModified();
				$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
				return true;
			} else {
				if (!Request::getUserVar('continued')) {
					$weekLaterDate = date('Y-m-d', strtotime('+1 week'));
				
					if ($reviewAssignment->getDateDue() != null) {
						$reviewDueDate = date('Y-m-d', strtotime($reviewAssignment->getDateDue()));
					} else {
						$numWeeks = max((int) $schedConf->getSetting('numWeeksPerReview'), 2);
						$reviewDueDate = date('Y-m-d', strtotime('+' . $numWeeks . ' week'));
					}

					$submissionUrl = Request::url(null, null, 'reviewer', 'submission', $reviewId, $reviewerAccessKeysEnabled?array('key' => 'ACCESS_KEY'):array());

					$paramArray = array(
						'reviewerName' => $reviewer->getFullName(),
						'weekLaterDate' => $weekLaterDate,
						'reviewDueDate' => $reviewDueDate,
						'reviewerUsername' => $reviewer->getUsername(),
						'reviewerPassword' => $reviewer->getPassword(),
						'editorialContactSignature' => $user->getContactSignature(),
						'reviewGuidelines' => $conference->getSetting('reviewGuidelines'),
						'submissionReviewUrl' => $submissionUrl,
						'passwordResetUrl' => Request::url(null, null, 'login', 'resetPassword', $reviewer->getUsername(), array('confirm' => Validation::generatePasswordResetHash($reviewer->getUserId())))
					);
					$email->assignParams($paramArray);
					$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());
				}
				$email->displayEditForm(Request::url(null, null, null, 'notifyReviewer'), array('reviewId' => $reviewId, 'paperId' => $trackDirectorSubmission->getPaperId()));
				return false;
			}
		}
		return true;
	}

	/**
	 * Cancels a review.
	 * @param $trackDirectorSubmission object
	 * @param $reviewId int
	 * @return boolean true iff ready for redirect
	 */
	function cancelReview($trackDirectorSubmission, $reviewId, $send = false) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$trackDirectorSubmissionDao = &DAORegistry::getDAO('TrackDirectorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');

		$conference = &Request::getConference();
		$user = &Request::getUser();

		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
		if (!isset($reviewer)) return true;

		if ($reviewAssignment->getPaperId() == $trackDirectorSubmission->getPaperId()) {
			// Only cancel the review if it is currently not cancelled but has previously
			// been initiated, and has not been completed.
			if ($reviewAssignment->getDateNotified() != null && !$reviewAssignment->getCancelled() && ($reviewAssignment->getDateCompleted() == null || $reviewAssignment->getDeclined())) {
				import('mail.PaperMailTemplate');
				$email = &new PaperMailTemplate($trackDirectorSubmission, 'REVIEW_CANCEL');

				if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
					HookRegistry::call('TrackDirectorAction::cancelReview', array(&$trackDirectorSubmission, &$reviewAssignment, &$email));
					if ($email->isEnabled()) {
						$email->setAssoc(PAPER_EMAIL_REVIEW_CANCEL, PAPER_EMAIL_TYPE_REVIEW, $reviewId);
						$email->send();
					}

					$reviewAssignment->setCancelled(1);
					$reviewAssignment->setDateCompleted(Core::getCurrentDate());
					$reviewAssignment->stampModified();
				
					$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);

					// Add log
					import('paper.log.PaperLog');
					import('paper.log.PaperEventLogEntry');
					PaperLog::logEvent($trackDirectorSubmission->getPaperId(), PAPER_LOG_REVIEW_CANCEL, LOG_TYPE_REVIEW, $reviewAssignment->getReviewId(), 'log.review.reviewCancelled', array('reviewerName' => $reviewer->getFullName(), 'paperId' => $trackDirectorSubmission->getPaperId(), 'round' => $reviewAssignment->getRound()));
				} else {
					if (!Request::getUserVar('continued')) {
						$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());

						$paramArray = array(
							'reviewerName' => $reviewer->getFullName(),
							'reviewerUsername' => $reviewer->getUsername(),
							'reviewerPassword' => $reviewer->getPassword(),
							'editorialContactSignature' => $user->getContactSignature()
						);
						$email->assignParams($paramArray);
					}
					$email->displayEditForm(Request::url(null, null, null, 'cancelReview', 'send'), array('reviewId' => $reviewId, 'paperId' => $trackDirectorSubmission->getPaperId()));
					return false;
				}
			}				
		}
		return true;
	}
	
	/**
	 * Reminds a reviewer about a review assignment.
	 * @param $trackDirectorSubmission object
	 * @param $reviewId int
	 * @return boolean true iff no error was encountered
	 */
	function remindReviewer($trackDirectorSubmission, $reviewId, $send = false) {
		$trackDirectorSubmissionDao = &DAORegistry::getDAO('TrackDirectorSubmissionDAO');
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
			
		$conference = &Request::getConference();
		$schedConf = &Request::getSchedConf();
		$user = &Request::getUser();
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$reviewerAccessKeysEnabled = $conference->getSetting('reviewerAccessKeysEnabled');

		// If we're using access keys, disable the address fields
		// for this message. (Prevents security issue: track director
		// could CC or BCC someone else, or change the reviewer address,
		// in order to get the access key.)
		$preventAddressChanges = $reviewerAccessKeysEnabled;

		import('mail.PaperMailTemplate');
		$email = &new PaperMailTemplate($trackDirectorSubmission, $reviewerAccessKeysEnabled?'REVIEW_REMIND_ONECLICK':'REVIEW_REMIND');

		if ($preventAddressChanges) {
			$email->setAddressFieldsEnabled(false);
		}

		if ($send && !$email->hasErrors()) {
			HookRegistry::call('TrackDirectorAction::remindReviewer', array(&$trackDirectorSubmission, &$reviewAssignment, &$email));
			$email->setAssoc(PAPER_EMAIL_REVIEW_REMIND, PAPER_EMAIL_TYPE_REVIEW, $reviewId);

			$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());

			if ($reviewerAccessKeysEnabled) {
				import('security.AccessKeyManager');
				import('pages.reviewer.ReviewerHandler');
				$accessKeyManager =& new AccessKeyManager();

				// Key lifetime is the typical review period plus four weeks
				$keyLifetime = ($schedConf->getSetting('numWeeksPerReview') + 4) * 7;
				$email->addPrivateParam('ACCESS_KEY', $accessKeyManager->createKey('ReviewerContext', $reviewer->getUserId(), $reviewId, $keyLifetime));
			}

			if ($preventAddressChanges) {
				// Ensure that this messages goes to the reviewer, and the reviewer ONLY.
				$email->clearAllRecipients();
				$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());
			}

			$email->send();

			$reviewAssignment->setDateReminded(Core::getCurrentDate());
			$reviewAssignment->setReminderWasAutomatic(0);
			$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
			return true;
		} elseif ($reviewAssignment->getPaperId() == $trackDirectorSubmission->getPaperId()) {
			$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
		
			if (!Request::getUserVar('continued')) {
				if (!isset($reviewer)) return true;
				$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());

				$submissionUrl = Request::url(null, null, 'reviewer', 'submission', $reviewId, $reviewerAccessKeysEnabled?array('key' => 'ACCESS_KEY'):array());
				
				//
				// FIXME: Assign correct values!
				//
				$paramArray = array(
					'reviewerName' => $reviewer->getFullName(),
					'reviewerUsername' => $reviewer->getUsername(),
					'reviewerPassword' => $reviewer->getPassword(),
					'reviewDueDate' => date('Y-m-d', strtotime($reviewAssignment->getDateDue())),
					'editorialContactSignature' => $user->getContactSignature(),
					'passwordResetUrl' => Request::url(null, null, 'login', 'resetPassword', $reviewer->getUsername(), array('confirm' => Validation::generatePasswordResetHash($reviewer->getUserId()))),
					'submissionReviewUrl' => $submissionUrl
				);
				$email->assignParams($paramArray);
	
			}
			$email->displayEditForm(
				Request::url(null, null, null, 'remindReviewer', 'send'),
				array(
					'reviewerId' => $reviewer->getUserId(),
					'paperId' => $trackDirectorSubmission->getPaperId(),
					'reviewId' => $reviewId
				)
			);
			return false;
		}
		return true;
	}
	
	/**
	 * Thanks a reviewer for completing a review assignment.
	 * @param $trackDirectorSubmission object
	 * @param $reviewId int
	 * @return boolean true iff ready for redirect
	 */
	function thankReviewer($trackDirectorSubmission, $reviewId, $send = false) {
		$trackDirectorSubmissionDao = &DAORegistry::getDAO('TrackDirectorSubmissionDAO');
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		
		$conference = &Request::getConference();
		$user = &Request::getUser();
		
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		
		import('mail.PaperMailTemplate');
		$email = &new PaperMailTemplate($trackDirectorSubmission, 'REVIEW_ACK');

		if ($reviewAssignment->getPaperId() == $trackDirectorSubmission->getPaperId()) {
			$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
			if (!isset($reviewer)) return true;
			
			if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
				HookRegistry::call('TrackDirectorAction::thankReviewer', array(&$trackDirectorSubmission, &$reviewAssignment, &$email));
				if ($email->isEnabled()) {
					$email->setAssoc(PAPER_EMAIL_REVIEW_THANK_REVIEWER, PAPER_EMAIL_TYPE_REVIEW, $reviewId);
					$email->send();
				}
				
				$reviewAssignment->setDateAcknowledged(Core::getCurrentDate());
				$reviewAssignment->stampModified();
				$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
			} else {
				if (!Request::getUserVar('continued')) {
					$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());

					$paramArray = array(
						'reviewerName' => $reviewer->getFullName(),
						'editorialContactSignature' => $user->getContactSignature()
					);
					$email->assignParams($paramArray);
				}
				$email->displayEditForm(Request::url(null, null, null, 'thankReviewer', 'send'), array('reviewId' => $reviewId, 'paperId' => $trackDirectorSubmission->getPaperId()));
				return false;
			}
		}
		return true;
	}
	
	/**
	 * Rates a reviewer for quality of a review.
	 * @param $paperId int
	 * @param $reviewId int
	 * @param $quality int
	 */
	function rateReviewer($paperId, $reviewId, $quality = null) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &Request::getUser();

		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
		if (!isset($reviewer)) return false;
		
		if ($reviewAssignment->getPaperId() == $paperId && !HookRegistry::call('TrackDirectorAction::rateReviewer', array(&$reviewAssignment, &$reviewer, &$quality))) {
			// Ensure that the value for quality
			// is between 1 and 5.
			if ($quality != null && ($quality >= 1 && $quality <= 5)) {
				$reviewAssignment->setQuality($quality);
			}

			$reviewAssignment->setDateRated(Core::getCurrentDate());
			$reviewAssignment->stampModified();

			$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
			
			// Add log
			import('paper.log.PaperLog');
			import('paper.log.PaperEventLogEntry');
			PaperLog::logEvent($paperId, PAPER_LOG_REVIEW_RATE, LOG_TYPE_REVIEW, $reviewAssignment->getReviewId(), 'log.review.reviewerRated', array('reviewerName' => $reviewer->getFullName(), 'paperId' => $paperId, 'round' => $reviewAssignment->getRound()));
		}
	}
	
	/**
	 * Makes a reviewer's annotated version of an paper available to the presenter.
	 * @param $paperId int
	 * @param $reviewId int
	 * @param $viewable boolean
	 */
	function makeReviewerFileViewable($paperId, $reviewId, $fileId, $revision, $viewable = false) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$paperFileDao = &DAORegistry::getDAO('PaperFileDAO');
		
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$paperFile = &$paperFileDao->getPaperFile($fileId, $revision);
		
		if ($reviewAssignment->getPaperId() == $paperId && $reviewAssignment->getReviewerFileId() == $fileId && !HookRegistry::call('TrackDirectorAction::makeReviewerFileViewable', array(&$reviewAssignment, &$paperFile, &$viewable))) {
			$paperFile->setViewable($viewable);
			$paperFileDao->updatePaperFile($paperFile);				
		}
	}
	
	/**
	 * Sets the due date for a review assignment.
	 * @param $paperId int
	 * @param $reviewId int
	 * @param $dueDate string
	 * @param $numWeeks int
	 */
	 function setDueDate($paperId, $reviewId, $dueDate = null, $numWeeks = null) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &Request::getUser();
		
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
		if (!isset($reviewer)) return false;
		
		if ($reviewAssignment->getPaperId() == $paperId && !HookRegistry::call('TrackDirectorAction::setDueDate', array(&$reviewAssignment, &$reviewer, &$dueDate, &$numWeeks))) {
			if ($dueDate != null) {
				$dueDateParts = explode('-', $dueDate);
				$today = getDate();
				
				// Ensure that the specified due date is today or after today's date.
				if ($dueDateParts[0] >= $today['year'] && ($dueDateParts[1] > $today['mon'] || ($dueDateParts[1] == $today['mon'] && $dueDateParts[2] >= $today['mday']))) {
					$reviewAssignment->setDateDue(date('Y-m-d H:i:s', mktime(0, 0, 0, $dueDateParts[1], $dueDateParts[2], $dueDateParts[0])));
				}
				else {
					$today = getDate();
					$todayTimestamp = mktime(0, 0, 0, $today['mon'], $today['mday'], $today['year']);
					$reviewAssignment->setDateDue(date('Y-m-d H:i:s', $todayTimestamp));
				}
			} else {
				$today = getDate();
				$todayTimestamp = mktime(0, 0, 0, $today['mon'], $today['mday'], $today['year']);
				
				// Add the equivilant of $numWeeks weeks, measured in seconds, to $todaysTimestamp.
				$newDueDateTimestamp = $todayTimestamp + ($numWeeks * 7 * 24 * 60 * 60);

				$reviewAssignment->setDateDue(date('Y-m-d H:i:s', $newDueDateTimestamp));
			}
		
			$reviewAssignment->stampModified();
			$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
			
			// Add log
			import('paper.log.PaperLog');
			import('paper.log.PaperEventLogEntry');
			PaperLog::logEvent($paperId, PAPER_LOG_REVIEW_SET_DUE_DATE, LOG_TYPE_REVIEW, $reviewAssignment->getReviewId(), 'log.review.reviewDueDateSet', array('reviewerName' => $reviewer->getFullName(), 'dueDate' => date('Y-m-d', strtotime($reviewAssignment->getDateDue())), 'paperId' => $paperId, 'round' => $reviewAssignment->getRound(), 'type' => $reviewAssignment->getType()));
		}
	 }
	 
	/**
	 * Notifies an presenter that a submission was unsuitable.
	 * @param $trackDirectorSubmission object
	 * @return boolean true iff ready for redirect
	 */
	function unsuitableSubmission($trackDirectorSubmission, $send = false) {
		$trackDirectorSubmissionDao = &DAORegistry::getDAO('TrackDirectorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		
		$conference = &Request::getConference();
		$user = &Request::getUser();

		$presenter = &$userDao->getUser($trackDirectorSubmission->getUserId());
		if (!isset($presenter)) return true;
		
		import('mail.PaperMailTemplate');
		$email = &new PaperMailTemplate($trackDirectorSubmission, 'SUBMISSION_UNSUITABLE');

		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			HookRegistry::call('TrackDirectorAction::unsuitableSubmission', array(&$trackDirectorSubmission, &$presenter, &$email));
			if ($email->isEnabled()) {
				$email->setAssoc(PAPER_EMAIL_EDITOR_NOTIFY_PRESENTER_UNSUITABLE, PAPER_EMAIL_TYPE_EDITOR, $user->getUserId());
				$email->send();
			}
			TrackDirectorAction::archiveSubmission($trackDirectorSubmission);
			return true;
		} else {
			if (!Request::getUserVar('continued')) {
				$paramArray = array(
					'editorialContactSignature' => $user->getContactSignature(),
					'presenterName' => $presenter->getFullName()
				);
				$email->assignParams($paramArray);
				$email->addRecipient($presenter->getEmail(), $presenter->getFullName());
			}
			$email->displayEditForm(Request::url(null, null, null, 'unsuitableSubmission'), array('paperId' => $trackDirectorSubmission->getPaperId()));
			return false;
		}
	}

	/**
	 * Sets the reviewer recommendation for a review assignment.
	 * Also concatenates the reviewer and editor comments from Peer Review and adds them to Editor Review.
	 * @param $paperId int
	 * @param $reviewId int
	 * @param $recommendation int
	 */
	 function setReviewerRecommendation($paperId, $reviewId, $recommendation, $acceptOption) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &Request::getUser();
		
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId(), true);
		
		if ($reviewAssignment->getPaperId() == $paperId && !HookRegistry::call('TrackDirectorAction::setReviewerRecommendation', array(&$reviewAssignment, &$reviewer, &$recommendation, &$acceptOption))) {
			$reviewAssignment->setRecommendation($recommendation);

			$nowDate = Core::getCurrentDate();
			if (!$reviewAssignment->getDateConfirmed()) {
				$reviewAssignment->setDateConfirmed($nowDate);
			}
			$reviewAssignment->setDateCompleted($nowDate);
			$reviewAssignment->stampModified();
		
			$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
			
			// Add log
			import('paper.log.PaperLog');
			import('paper.log.PaperEventLogEntry');
			PaperLog::logEvent($paperId, PAPER_LOG_REVIEW_RECOMMENDATION_BY_PROXY, LOG_TYPE_REVIEW, $reviewAssignment->getReviewId(), 'log.review.reviewRecommendationSetByProxy', array('editorName' => $user->getFullName(), 'reviewerName' => $reviewer->getFullName(), 'paperId' => $paperId, 'round' => $reviewAssignment->getRound(), 'type' => $reviewAssignment->getType()));
		}
	}	 
	 
	/**
	 * Resubmit the file for review.
	 * @param $trackDirectorSubmission object
	 * @param $fileId int
	 * @param $revision int
	 * TODO: SECURITY!
	 */
	function resubmitFile($trackDirectorSubmission, $fileId, $revision) {
		import('file.PaperFileManager');
		$paperFileManager = &new PaperFileManager($trackDirectorSubmission->getPaperId());
		$trackDirectorSubmissionDao = &DAORegistry::getDAO('TrackDirectorSubmissionDAO');
		$paperFileDao = &DAORegistry::getDAO('PaperFileDAO');
		$user = &Request::getUser();
		
		if (!HookRegistry::call('TrackDirectorAction::resubmitFile', array(&$trackDirectorSubmission, &$fileId, &$revision))) {
			// Increment the round
			$currentRound = $trackDirectorSubmission->getCurrentRound();
			$trackDirectorSubmission->setCurrentRound($currentRound + 1);
			$trackDirectorSubmission->stampStatusModified();
		
			// Copy the file from the editor decision file folder to the review file folder
			$newFileId = $paperFileManager->copyToReviewFile($fileId, $revision, $trackDirectorSubmission->getReviewFileId());
			$newReviewFile = $paperFileDao->getPaperFile($newFileId);
			$newReviewFile->setRound($trackDirectorSubmission->getCurrentRound());
			$paperFileDao->updatePaperFile($newReviewFile);

			// Copy the file from the editor decision file folder to the next-round editor file
			// $editorFileId may or may not be null after assignment
			$editorFileId = $trackDirectorSubmission->getEditorFileId() != null ? $trackDirectorSubmission->getEditorFileId() : null;

			// $editorFileId definitely will not be null after assignment
			$editorFileId = $paperFileManager->copyToEditorFile($newFileId, null, $editorFileId);
			$newEditorFile = $paperFileDao->getPaperFile($editorFileId);
			$newEditorFile->setRound($trackDirectorSubmission->getCurrentRound());
			$paperFileDao->updatePaperFile($newEditorFile);

			// The review revision is the highest revision for the review file.
			$reviewRevision = $paperFileDao->getRevisionNumber($newFileId);
			$trackDirectorSubmission->setReviewRevision($reviewRevision);
		
			$trackDirectorSubmissionDao->updateTrackDirectorSubmission($trackDirectorSubmission);

			// Now, reassign all reviewers that submitted a review for this new round of reviews.
			$previousRound = $trackDirectorSubmission->getCurrentRound() - 1;
			foreach ($trackDirectorSubmission->getReviewAssignments($previousRound) as $reviewAssignment) {
				if ($reviewAssignment->getRecommendation() != null) {
					// Then this reviewer submitted a review.
					TrackDirectorAction::addReviewer($trackDirectorSubmission, $reviewAssignment->getReviewerId(), $trackDirectorSubmission->getCurrentRound());
				}
			}
		
		
			// Add log
			import('paper.log.PaperLog');
			import('paper.log.PaperEventLogEntry');
			PaperLog::logEvent($trackDirectorSubmission->getPaperId(), ARTICLE_LOG_REVIEW_RESUBMIT, ARTICLE_LOG_TYPE_EDITOR, $user->getUserId(), 'log.review.resubmit', array('paperId' => $trackDirectorSubmission->getPaperId()));
		}
	}
	
	/**
	 * Upload the review version of an paper.
	 * @param $trackDirectorSubmission object
	 */
	function uploadReviewVersion($trackDirectorSubmission) {
		import('file.PaperFileManager');
		$paperFileManager = &new PaperFileManager($trackDirectorSubmission->getPaperId());
		$trackDirectorSubmissionDao = &DAORegistry::getDAO('TrackDirectorSubmissionDAO');
		
		$fileName = 'upload';
		if ($paperFileManager->uploadedFileExists($fileName) && !HookRegistry::call('TrackDirectorAction::uploadReviewVersion', array(&$trackDirectorSubmission))) {
			if ($trackDirectorSubmission->getReviewFileId() != null) {
				$reviewFileId = $paperFileManager->uploadReviewFile($fileName, $trackDirectorSubmission->getReviewFileId());
				// Increment the review revision.
				$trackDirectorSubmission->setReviewRevision($trackDirectorSubmission->getReviewRevision()+1);
			} else {
				$reviewFileId = $paperFileManager->uploadReviewFile($fileName);
				$trackDirectorSubmission->setReviewRevision(1);
			}
			$editorFileId = $paperFileManager->copyToEditorFile($reviewFileId, $trackDirectorSubmission->getReviewRevision(), $trackDirectorSubmission->getEditorFileId());
		}
		
		if (isset($reviewFileId) && $reviewFileId != 0 && isset($editorFileId) && $editorFileId != 0) {
			$trackDirectorSubmission->setReviewFileId($reviewFileId);
			$trackDirectorSubmission->setEditorFileId($editorFileId);
	
			$trackDirectorSubmissionDao->updateTrackDirectorSubmission($trackDirectorSubmission);
		}
	}

	/**
	 * Upload the post-review version of an paper.
	 * @param $trackDirectorSubmission object
	 */
	function uploadEditorVersion($trackDirectorSubmission) {
		import('file.PaperFileManager');
		$paperFileManager = &new PaperFileManager($trackDirectorSubmission->getPaperId());
		$trackDirectorSubmissionDao = &DAORegistry::getDAO('TrackDirectorSubmissionDAO');
		$user = &Request::getUser();
		
		$fileName = 'upload';
		if ($paperFileManager->uploadedFileExists($fileName) && !HookRegistry::call('TrackDirectorAction::uploadEditorVersion', array(&$trackDirectorSubmission))) {
			if ($trackDirectorSubmission->getEditorFileId() != null) {
				$fileId = $paperFileManager->uploadEditorDecisionFile($fileName, $trackDirectorSubmission->getEditorFileId());
			} else {
				$fileId = $paperFileManager->uploadEditorDecisionFile($fileName);
			}
		}
		
		if (isset($fileId) && $fileId != 0) {
			$trackDirectorSubmission->setEditorFileId($fileId);

			$trackDirectorSubmissionDao->updateTrackDirectorSubmission($trackDirectorSubmission);
			
			// Set initial layout version to final copyedit version
			$layoutDao = &DAORegistry::getDAO('LayoutAssignmentDAO');
			$layoutAssignment = &$layoutDao->getLayoutAssignmentByPaperId($trackDirectorSubmission->getPaperId());

			if (isset($layoutAssignment) && !$layoutAssignment->getLayoutFileId()) {
				import('file.PaperFileManager');
				$paperFileManager = &new PaperFileManager($trackDirectorSubmission->getPaperId());
				if ($layoutFileId = $paperFileManager->copyToLayoutFile($fileId)) {
					$layoutAssignment->setLayoutFileId($layoutFileId);
					$layoutDao->updateLayoutAssignment($layoutAssignment);
				}
			}
		
			// Add a publishedpaper object
			$publishedPaperDao =& DAORegistry::getDAO('PublishedPaperDAO');
			if(($publishedPaper = $publishedPaperDao->getPublishedPaperByPaperId($trackDirectorSubmission->getPaperId())) == null) {
				$schedConfId = $trackDirectorSubmission->getSchedConfId();

				$publishedPaper =& new PublishedPaper();
				$publishedPaper->setPaperId($trackDirectorSubmission->getPaperId());
				$publishedPaper->setSchedConfId($schedConfId);
				$publishedPaper->setDatePublished(Core::getCurrentDate());
				$publishedPaper->setSeq(100000); // KLUDGE: End of list
				$publishedPaper->setViews(0);
				$publishedPaper->setAccessStatus(0);

				$publishedPaperDao->insertPublishedPaper($publishedPaper);

				// Resequence the papers.
				$publishedPaperDao->resequencePublishedPapers($trackDirectorSubmission->getTrackId(), $schedConfId);

				// If we're using custom track ordering, and if this is the first
				// paper published in a track, make sure we enter a custom ordering
				// for it. (Default at the end of the list.)
				$trackDao =& DAORegistry::getDAO('TrackDAO');
				if ($trackDao->customTrackOrderingExists($schedConfId)) {
					if ($trackDao->getCustomTrackOrder($schedConfId, $submission->getTrackId()) === null) {
						$trackDao->insertCustomTrackOrder($schedConfId, $submission->getTrackId(), 10000); // KLUDGE: End of list
						$trackDao->resequenceCustomTrackOrders($schedConfId);
					}
				}
			}		
			// Add log
			import('paper.log.PaperLog');
			import('paper.log.PaperEventLogEntry');
			PaperLog::logEvent($trackDirectorSubmission->getPaperId(), PAPER_LOG_EDITOR_FILE, LOG_TYPE_EDITOR, $trackDirectorSubmission->getEditorFileId(), 'log.editor.editorFile');
		}
	}
	
	/**
	 * Archive a submission.
	 * @param $trackDirectorSubmission object
	 */
	function archiveSubmission($trackDirectorSubmission) {
		$trackDirectorSubmissionDao = &DAORegistry::getDAO('TrackDirectorSubmissionDAO');
		$user = &Request::getUser();
		
		if (HookRegistry::call('TrackDirectorAction::archiveSubmission', array(&$trackDirectorSubmission))) return;

		$trackDirectorSubmission->setStatus(SUBMISSION_STATUS_ARCHIVED);
		$trackDirectorSubmission->stampStatusModified();
		
		$trackDirectorSubmissionDao->updateTrackDirectorSubmission($trackDirectorSubmission);
		
		// Add log
		import('paper.log.PaperLog');
		import('paper.log.PaperEventLogEntry');
		PaperLog::logEvent($trackDirectorSubmission->getPaperId(), PAPER_LOG_EDITOR_ARCHIVE, LOG_TYPE_EDITOR, $trackDirectorSubmission->getPaperId(), 'log.editor.archived', array('paperId' => $trackDirectorSubmission->getPaperId()));
	}
	
	/**
	 * Restores a submission to the queue.
	 * @param $trackDirectorSubmission object
	 */
	function restoreToQueue($trackDirectorSubmission) {
		if (HookRegistry::call('TrackDirectorAction::restoreToQueue', array(&$trackDirectorSubmission))) return;

		$trackDirectorSubmissionDao = &DAORegistry::getDAO('TrackDirectorSubmissionDAO');

		// Determine which queue to return the paper to: the
		// scheduling queue or the editing queue.
		$publishedPaperDao =& DAORegistry::getDAO('PublishedPaperDAO');
		$publishedPaper =& $publishedPaperDao->getPublishedPaperByPaperId($trackDirectorSubmission->getPaperId());
		if ($publishedPaper) {
			$trackDirectorSubmission->setStatus(SUBMISSION_STATUS_PUBLISHED);
		} else {
			$trackDirectorSubmission->setStatus(SUBMISSION_STATUS_QUEUED);
		}
		unset($publishedPaper);

		$trackDirectorSubmission->stampStatusModified();
		
		$trackDirectorSubmissionDao->updateTrackDirectorSubmission($trackDirectorSubmission);
	
		// Add log
		import('paper.log.PaperLog');
		import('paper.log.PaperEventLogEntry');
		PaperLog::logEvent($trackDirectorSubmission->getPaperId(), PAPER_LOG_EDITOR_RESTORE, LOG_TYPE_EDITOR, $trackDirectorSubmission->getPaperId(), 'log.editor.restored', array('paperId' => $trackDirectorSubmission->getPaperId()));
	}
	
	//
	// Layout Editing
	//
	
	/**
	 * Upload the layout version of an paper.
	 * @param $submission object
	 */
	function uploadLayoutVersion($submission) {
		import('file.PaperFileManager');
		$paperFileManager = &new PaperFileManager($submission->getPaperId());
		$submissionDao = &DAORegistry::getDAO('TrackDirectorSubmissionDAO');
		
		$layoutAssignment = &$submission->getLayoutAssignment();
		
		$fileName = 'layoutFile';
		if ($paperFileManager->uploadedFileExists($fileName) && !HookRegistry::call('TrackDirectorAction::uploadLayoutVersion', array(&$submission, &$layoutAssignment))) {
			$layoutFileId = $paperFileManager->uploadLayoutFile($fileName, $layoutAssignment->getLayoutFileId());
		
			$layoutAssignment->setLayoutFileId($layoutFileId);
			$submissionDao->updateTrackDirectorSubmission($submission);
		}
	}
	
	/**
	 * Assign a layout editor to a submission.
	 * @param $submission object
	 * @param $editorId int user ID of the new layout editor
	 */
	function assignLayoutEditor($submission, $editorId) {
		if (HookRegistry::call('TrackDirectorAction::assignLayoutEditor', array(&$submission, &$editorId))) return;

		$layoutAssignment = &$submission->getLayoutAssignment();
		
		import('paper.log.PaperLog');
		import('paper.log.PaperEventLogEntry');

		if ($layoutAssignment->getEditorId()) {
			PaperLog::logEvent($submission->getPaperId(), ARTICLE_LOG_LAYOUT_UNASSIGN, ARTICLE_LOG_TYPE_LAYOUT, $layoutAssignment->getLayoutId(), 'log.layout.layoutEditorUnassigned', array('editorName' => $layoutAssignment->getEditorFullName(), 'paperId' => $submission->getPaperId()));
		}
		
		$layoutAssignment->setEditorId($editorId);
		$layoutAssignment->setDateNotified(null);
		$layoutAssignment->setDateUnderway(null);
		$layoutAssignment->setDateCompleted(null);
		$layoutAssignment->setDateAcknowledged(null);
		
		$layoutDao = &DAORegistry::getDAO('LayoutAssignmentDAO');
		$layoutDao->updateLayoutAssignment($layoutAssignment);
		$layoutAssignment =& $layoutDao->getLayoutAssignmentById($layoutAssignment->getLayoutId());
		
		PaperLog::logEvent($submission->getPaperId(), ARTICLE_LOG_LAYOUT_ASSIGN, ARTICLE_LOG_TYPE_LAYOUT, $layoutAssignment->getLayoutId(), 'log.layout.layoutEditorAssigned', array('editorName' => $layoutAssignment->getEditorFullName(), 'paperId' => $submission->getPaperId()));
	}
	
	/**
	 * Notifies the current layout editor about an assignment.
	 * @param $submission object
	 * @param $send boolean
	 * @return boolean true iff ready for redirect
	 */
	function notifyLayoutEditor($submission, $send = false) {
		$submissionDao = &DAORegistry::getDAO('TrackDirectorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &Request::getUser();
		
		import('mail.PaperMailTemplate');
		$email = &new PaperMailTemplate($submission, 'LAYOUT_REQUEST');
		$layoutAssignment = &$submission->getLayoutAssignment();
		$layoutEditor = &$userDao->getUser($layoutAssignment->getEditorId());
		if (!isset($layoutEditor)) return true;
		
		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			HookRegistry::call('TrackDirectorAction::notifyLayoutEditor', array(&$submission, &$layoutEditor, &$layoutAssignment, &$email));
			if ($email->isEnabled()) {
				$email->setAssoc(ARTICLE_EMAIL_LAYOUT_NOTIFY_EDITOR, ARTICLE_EMAIL_TYPE_LAYOUT, $layoutAssignment->getLayoutId());
				$email->send();
			}
			
			$layoutAssignment->setDateNotified(Core::getCurrentDate());
			$layoutAssignment->setDateUnderway(null);
			$layoutAssignment->setDateCompleted(null);
			$layoutAssignment->setDateAcknowledged(null);
			$submissionDao->updateTrackDirectorSubmission($submission);
			
		} else {
			if (!Request::getUserVar('continued')) {
				$email->addRecipient($layoutEditor->getEmail(), $layoutEditor->getFullName());
				$paramArray = array(
					'layoutEditorName' => $layoutEditor->getFullName(),
					'layoutEditorUsername' => $layoutEditor->getUsername(),
					'editorialContactSignature' => $user->getContactSignature(),
					'submissionLayoutUrl' => Request::url(null, 'layoutEditor', 'submission', $submission->getPaperId())
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::url(null, null, 'notifyLayoutEditor', 'send'), array('paperId' => $submission->getPaperId()));
			return false;
		}
		return true;
	}
	
	/**
	 * Sends acknowledgement email to the current layout editor.
	 * @param $submission object
	 * @param $send boolean
	 * @return boolean true iff ready for redirect
	 */
	function thankLayoutEditor($submission, $send = false) {
		$submissionDao = &DAORegistry::getDAO('TrackDirectorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &Request::getUser();
		
		import('mail.PaperMailTemplate');
		$email = &new PaperMailTemplate($submission, 'LAYOUT_ACK');

		$layoutAssignment = &$submission->getLayoutAssignment();
		$layoutEditor = &$userDao->getUser($layoutAssignment->getEditorId());
		if (!isset($layoutEditor)) return true;
		
		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			HookRegistry::call('TrackDirectorAction::thankLayoutEditor', array(&$submission, &$layoutEditor, &$layoutAssignment, &$email));
			if ($email->isEnabled()) {
				$email->setAssoc(ARTICLE_EMAIL_LAYOUT_THANK_EDITOR, ARTICLE_EMAIL_TYPE_LAYOUT, $layoutAssignment->getLayoutId());
				$email->send();
			}
			
			$layoutAssignment->setDateAcknowledged(Core::getCurrentDate());
			$submissionDao->updateTrackDirectorSubmission($submission);
			
		} else {
			if (!Request::getUserVar('continued')) {
				$email->addRecipient($layoutEditor->getEmail(), $layoutEditor->getFullName());
				$paramArray = array(
					'layoutEditorName' => $layoutEditor->getFullName(),
					'editorialContactSignature' => $user->getContactSignature()
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::url(null, null, 'thankLayoutEditor', 'send'), array('paperId' => $submission->getPaperId()));
			return false;
		}
		return true;
	}

	/**
	 * Change the sequence order of a galley.
	 * @param $paper object
	 * @param $galleyId int
	 * @param $direction char u = up, d = down
	 */
	function orderGalley($paper, $galleyId, $direction) {
		import('submission.layoutEditor.LayoutEditorAction');
		LayoutEditorAction::orderGalley($paper, $galleyId, $direction);
	}
	
	/**
	 * Delete a galley.
	 * @param $paper object
	 * @param $galleyId int
	 */
	function deleteGalley($paper, $galleyId) {
		import('submission.layoutEditor.LayoutEditorAction');
		LayoutEditorAction::deleteGalley($paper, $galleyId);
	}
	
	/**
	 * Change the sequence order of a supplementary file.
	 * @param $paper object
	 * @param $suppFileId int
	 * @param $direction char u = up, d = down
	 */
	function orderSuppFile($paper, $suppFileId, $direction) {
		import('submission.layoutEditor.LayoutEditorAction');
		LayoutEditorAction::orderSuppFile($paper, $suppFileId, $direction);
	}
	
	/**
	 * Delete a supplementary file.
	 * @param $paper object
	 * @param $suppFileId int
	 */
	function deleteSuppFile($paper, $suppFileId) {
		import('submission.layoutEditor.LayoutEditorAction');
		LayoutEditorAction::deleteSuppFile($paper, $suppFileId);
	}
	
	/**
	 * Delete a file from an paper.
	 * @param $submission object
	 * @param $fileId int
	 * @param $revision int (optional)
	 */
	function deletePaperFile($submission, $fileId, $revision) {
		import('file.PaperFileManager');
		$file =& $submission->getEditorFile();
		
		if (isset($file) && $file->getFileId() == $fileId && !HookRegistry::call('TrackDirectorAction::deletePaperFile', array(&$submission, &$fileId, &$revision))) {
			$paperFileManager = &new PaperFileManager($submission->getPaperId());
			$paperFileManager->deleteFile($fileId, $revision);
		}
	}

	/**
	 * Delete an image from an paper galley.
	 * @param $submission object
	 * @param $fileId int
	 * @param $revision int (optional)
	 */
	function deletePaperImage($submission, $fileId, $revision) {
		import('file.PaperFileManager');
		$paperGalleyDao =& DAORegistry::getDAO('PaperGalleyDAO');
		if (HookRegistry::call('TrackDirectorAction::deletePaperImage', array(&$submission, &$fileId, &$revision))) return;
		foreach ($submission->getGalleys() as $galley) {
			$images =& $paperGalleyDao->getGalleyImages($galley->getGalleyId());
			foreach ($images as $imageFile) {
				if ($imageFile->getPaperId() == $submission->getPaperId() && $fileId == $imageFile->getFileId() && $imageFile->getRevision() == $revision) {
					$paperFileManager = &new PaperFileManager($submission->getPaperId());
					$paperFileManager->deleteFile($imageFile->getFileId(), $imageFile->getRevision());
				}
			}
			unset($images);
		}
	}
	
	/**
	 * Add Submission Note
	 * @param $paperId int
	 */
	function addSubmissionNote($paperId) {
		import('file.PaperFileManager');

		$paperNoteDao = &DAORegistry::getDAO('PaperNoteDAO');
		$user = &Request::getUser();
		
		$paperNote = &new PaperNote();
		$paperNote->setPaperId($paperId);
		$paperNote->setUserId($user->getUserId());
		$paperNote->setDateCreated(Core::getCurrentDate());
		$paperNote->setDateModified(Core::getCurrentDate());
		$paperNote->setTitle(Request::getUserVar('title'));
		$paperNote->setNote(Request::getUserVar('note'));

		if (!HookRegistry::call('TrackDirectorAction::addSubmissionNote', array(&$paperId, &$paperNote))) {
			$paperFileManager = &new PaperFileManager($paperId);
			if ($paperFileManager->uploadedFileExists('upload')) {
				$fileId = $paperFileManager->uploadSubmissionNoteFile('upload');
			} else {
				$fileId = 0;
			}

			$paperNote->setFileId($fileId);
	
			$paperNoteDao->insertPaperNote($paperNote);
		}
	}

	/**
	 * Remove Submission Note
	 * @param $paperId int
	 */
	function removeSubmissionNote($paperId) {
		$noteId = Request::getUserVar('noteId');
		$fileId = Request::getUserVar('fileId');

		if (HookRegistry::call('TrackDirectorAction::removeSubmissionNote', array(&$paperId, &$noteId, &$fileId))) return;

		// if there is an attached file, remove it as well
		if ($fileId) {
			import('file.PaperFileManager');
			$paperFileManager = &new PaperFileManager($paperId);
			$paperFileManager->deleteFile($fileId);
		}

		$paperNoteDao = &DAORegistry::getDAO('PaperNoteDAO');
		$paperNoteDao->deletePaperNoteById($noteId);
	}
	
	/**
	 * Updates Submission Note
	 * @param $paperId int
	 */
	function updateSubmissionNote($paperId) {
		import('file.PaperFileManager');

		$paperNoteDao = &DAORegistry::getDAO('PaperNoteDAO');
		$user = &Request::getUser();
		
		$paperNote = &new PaperNote();
		$paperNote->setNoteId(Request::getUserVar('noteId'));
		$paperNote->setPaperId($paperId);
		$paperNote->setUserId($user->getUserId());
		$paperNote->setDateModified(Core::getCurrentDate());
		$paperNote->setTitle(Request::getUserVar('title'));
		$paperNote->setNote(Request::getUserVar('note'));
		$paperNote->setFileId(Request::getUserVar('fileId'));
		
		if (HookRegistry::call('TrackDirectorAction::updateSubmissionNote', array(&$paperId, &$paperNote))) return;

		$paperFileManager = &new PaperFileManager($paperId);
		
		// if there is a new file being uploaded
		if ($paperFileManager->uploadedFileExists('upload')) {
			// Attach the new file to the note, overwriting existing file if necessary
			$fileId = $paperFileManager->uploadSubmissionNoteFile('upload', $paperNote->getFileId(), true);
			$paperNote->setFileId($fileId);
			
		} else {
			if (Request::getUserVar('removeUploadedFile')) {
				$paperFileManager = &new PaperFileManager($paperId);
				$paperFileManager->deleteFile($paperNote->getFileId());
				$paperNote->setFileId(0);
			}
		}
	
		$paperNoteDao->updatePaperNote($paperNote);
	}
	
	/**
	 * Clear All Submission Notes
	 * @param $paperId int
	 */
	function clearAllSubmissionNotes($paperId) {
		if (HookRegistry::call('TrackDirectorAction::clearAllSubmissionNotes', array(&$paperId))) return;

		import('file.PaperFileManager');

		$paperNoteDao = &DAORegistry::getDAO('PaperNoteDAO');

		$fileIds = $paperNoteDao->getAllPaperNoteFileIds($paperId);

		if (!empty($fileIds)) {
			$paperFileDao = &DAORegistry::getDAO('PaperFileDAO');
			$paperFileManager = &new PaperFileManager($paperId);
			
			foreach ($fileIds as $fileId) {
				$paperFileManager->deleteFile($fileId);
			}			
		}
		
		$paperNoteDao->clearAllPaperNotes($paperId);
		
	}
	
	//
	// Comments
	//
	
	/**
	 * View reviewer comments.
	 * @param $paper object
	 * @param $reviewId int
	 */
	function viewPeerReviewComments(&$paper, $reviewId) {
		if (HookRegistry::call('TrackDirectorAction::viewPeerReviewComments', array(&$paper, &$reviewId))) return;

		import('submission.form.comment.PeerReviewCommentForm');
		
		$commentForm = &new PeerReviewCommentForm($paper, $reviewId, ROLE_ID_EDITOR);
		$commentForm->initData();
		$commentForm->display();
	}
	
	/**
	 * Post reviewer comments.
	 * @param $paper object
	 * @param $reviewId int
	 * @param $emailComment boolean
	 */
	function postPeerReviewComment(&$paper, $reviewId, $emailComment) {
		if (HookRegistry::call('TrackDirectorAction::postPeerReviewComment', array(&$paper, &$reviewId, &$emailComment))) return;

		import('submission.form.comment.PeerReviewCommentForm');
		
		$commentForm = &new PeerReviewCommentForm($paper, $reviewId, ROLE_ID_EDITOR);
		$commentForm->readInputData();
		
		if ($commentForm->validate()) {
			$commentForm->execute();
			
			if ($emailComment) {
				$commentForm->email();
			}
			
		} else {
			$commentForm->display();
			return false;
		}
		return true;
	}
	
	/**
	 * View editor decision comments.
	 * @param $paper object
	 */
	function viewEditorDecisionComments($paper) {
		if (HookRegistry::call('TrackDirectorAction::viewEditorDecisionComments', array(&$paper))) return;

		import('submission.form.comment.EditorDecisionCommentForm');
		
		$commentForm = &new EditorDecisionCommentForm($paper, ROLE_ID_EDITOR);
		$commentForm->initData();
		$commentForm->display();
	}
	
	/**
	 * Post editor decision comment.
	 * @param $paper int
	 * @param $emailComment boolean
	 */
	function postEditorDecisionComment($paper, $emailComment) {
		if (HookRegistry::call('TrackDirectorAction::postEditorDecisionComment', array(&$paper, &$emailComment))) return;

		import('submission.form.comment.EditorDecisionCommentForm');
		
		$commentForm = &new EditorDecisionCommentForm($paper, ROLE_ID_EDITOR);
		$commentForm->readInputData();
		
		if ($commentForm->validate()) {
			$commentForm->execute();
			
			if ($emailComment) {
				$commentForm->email();
			}
		} else {
			$commentForm->display();
			return false;
		}
		return true;
	}
	
	/**
	 * Email editor decision comment.
	 * @param $trackDirectorSubmission object
	 * @param $send boolean
	 */
	function emailEditorDecisionComment($trackDirectorSubmission, $send) {
		$userDao = &DAORegistry::getDAO('UserDAO');
		$paperCommentDao =& DAORegistry::getDAO('PaperCommentDAO');
		$conference = &Request::getConference();

		$templateName = null;
		$types = $trackDirectorSubmission->getDecisions();
		if (is_array($types)) {
			$isAbstract = array_pop(array_keys($types)) == REVIEW_PROGRESS_ABSTRACT;
			$rounds = array_pop($types);
		}
		if (isset($rounds) && is_array($rounds)) $decisions = array_pop($rounds);
		if (isset($decisions) && is_array($decisions)) $lastDecision = array_pop($decisions);
		if (isset($lastDecision) && is_array($lastDecision)) switch ($lastDecision['decision']) {
			case SUBMISSION_EDITOR_DECISION_ACCEPT:
				$templateName = $isAbstract?'SUBMISSION_ABSTRACT_ACCEPT':'SUBMISSION_PAPER_ACCEPT';
				break;
			case SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS:
				$templateName = $isAbstract?'SUBMISSION_ABSTRACT_REVISE':'SUBMISSION_PAPER_REVISE';
				break;
			case SUBMISSION_EDITOR_DECISION_ACCEPT_RESUBMIT:
				$templateName = $isAbstract?'SUBMISSION_ABSTRACT_RESUBMIT':'SUBMISSION_PAPER_RESUBMIT';
				break;
			case SUBMISSION_EDITOR_DECISION_ACCEPT_DECLINE:
				$templateName = $isAbstract?'SUBMISSION_ABSTRACT_DECLINE':'SUBMISSION_PAPER_DECLINE';
				break;
		}

		$user = &Request::getUser();
		import('mail.PaperMailTemplate');
		$email = &new PaperMailTemplate($trackDirectorSubmission, $templateName);
	
		if ($send && !$email->hasErrors()) {
			HookRegistry::call('TrackDirectorAction::emailEditorDecisionComment', array(&$trackDirectorSubmission, &$send));
			$email->send();

			$paperComment =& new PaperComment();
			$paperComment->setCommentType(COMMENT_TYPE_EDITOR_DECISION);
			$paperComment->setRoleId(Validation::isEditor()?ROLE_ID_EDITOR:ROLE_ID_TRACK_EDITOR);
			$paperComment->setPaperId($trackDirectorSubmission->getPaperId());
			$paperComment->setAuthorId($trackDirectorSubmission->getUserId());
			$paperComment->setCommentTitle($email->getSubject());
			$paperComment->setComments($email->getBody());
			$paperComment->setDatePosted(Core::getCurrentDate());
			$paperComment->setViewable(true);
			$paperComment->setAssocId($trackDirectorSubmission->getPaperId());
			$paperCommentDao->insertPaperComment($paperComment);

			return true;
		} else {
			if (!Request::getUserVar('continued')) {
				$presenterUser =& $userDao->getUser($trackDirectorSubmission->getUserId());
				$email->setSubject($trackDirectorSubmission->getPaperTitle());
				$email->addRecipient($presenterUser->getEmail(), $presenterUser->getFullName());
			} else {
				if (Request::getUserVar('importPeerReviews')) {
					$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
					$reviewAssignments = &$reviewAssignmentDao->getReviewAssignmentsByPaperId($trackDirectorSubmission->getPaperId(), $trackDirectorSubmission->getReviewProgress(), $trackDirectorSubmission->getCurrentRound());
					$reviewIndexes = &$reviewAssignmentDao->getReviewIndexesForRound($trackDirectorSubmission->getPaperId(), $trackDirectorSubmission->getReviewProgress(), $trackDirectorSubmission->getCurrentRound());

					$body = '';
					foreach ($reviewAssignments as $reviewAssignment) {
						// If the reviewer has completed the assignment, then import the review.
						if ($reviewAssignment->getDateCompleted() != null && !$reviewAssignment->getCancelled()) {
							// Get the comments associated with this review assignment
							$paperComments = &$paperCommentDao->getPaperComments($trackDirectorSubmission->getPaperId(), COMMENT_TYPE_PEER_REVIEW, $reviewAssignment->getReviewId());
							$body .= "------------------------------------------------------\n";
							$body .= Locale::translate('submission.comments.importPeerReviews.reviewerLetter', array('reviewerLetter' => chr(ord('A') + $reviewIndexes[$reviewAssignment->getReviewId()]))) . "\n";
							if (is_array($paperComments)) {
								foreach ($paperComments as $comment) {
									// If the comment is viewable by the presenter, then add the comment.
									if ($comment->getViewable()) {
										$body .= $comment->getComments() . "\n\n";
									}
								}
							}
							$body .= "------------------------------------------------------\n\n";
						}
					$oldBody = $email->getBody();
					if (!empty($oldBody)) $oldBody .= "\n";
					$email->setBody($oldBody . $body);
					}
				}
			}

			$email->displayEditForm(Request::url(null, null, null, 'emailEditorDecisionComment', 'send'), array('paperId' => $trackDirectorSubmission->getPaperId()), 'submission/comment/editorDecisionEmail.tpl', array('isAnEditor' => true));

			return false;
		}
	}
	
	/**
	 * Blind CC the reviews to reviewers.
	 * @param $paper object
	 * @param $send boolean
	 * @param $inhibitExistingEmail boolean
	 * @return boolean true iff ready for redirect
	 */
	function blindCcReviewsToReviewers($paper, $send = false, $inhibitExistingEmail = false) {
		$commentDao = &DAORegistry::getDAO('PaperCommentDAO');
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$conference = &Request::getConference();
		
		$comments = &$commentDao->getPaperComments($paper->getPaperId(), COMMENT_TYPE_EDITOR_DECISION);
		$reviewAssignments = &$reviewAssignmentDao->getReviewAssignmentsByPaperId($paper->getPaperId(), $paper->getReviewProgress(), $paper->getCurrentRound());
		
		$commentsText = "";
		foreach ($comments as $comment) {
			$commentsText .= $comment->getComments() . "\n\n";
		}
		
		$user = &Request::getUser();
		import('mail.PaperMailTemplate');
		$email = &new PaperMailTemplate($paper, 'SUBMISSION_DECISION_REVIEWERS');

		if ($send && !$email->hasErrors() && !$inhibitExistingEmail) {
			HookRegistry::call('TrackDirectorAction::blindCcReviewsToReviewers', array(&$paper, &$reviewAssignments, &$email));
			$email->send();
			return true;
		} else {
			if ($inhibitExistingEmail || !Request::getUserVar('continued')) {
				$email->clearRecipients();
				foreach ($reviewAssignments as $reviewAssignment) {
					if ($reviewAssignment->getDateCompleted() != null && !$reviewAssignment->getCancelled()) {
						$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
						
						if (isset($reviewer)) $email->addBcc($reviewer->getEmail(), $reviewer->getFullName());
					}
				}

				$paramArray = array(
					'comments' => $commentsText,
					'editorialContactSignature' => $user->getContactSignature()
				);
				$email->assignParams($paramArray);
			}
			
			$email->displayEditForm(Request::url(null, null, null, 'blindCcReviewsToReviewers'), array('paperId' => $paper->getPaperId()));
			return false;
		}
	}
	
	/**
	 * View layout comments.
	 * @param $paper object
	 */
	function viewLayoutComments($paper) {
		if (HookRegistry::call('TrackDirectorAction::viewLayoutComments', array(&$paper))) return;

		import('submission.form.comment.LayoutCommentForm');
		
		$commentForm = &new LayoutCommentForm($paper, ROLE_ID_EDITOR);
		$commentForm->initData();
		$commentForm->display();
	}
	
	/**
	 * Post layout comment.
	 * @param $paper object
	 * @param $emailComment boolean
	 */
	function postLayoutComment($paper, $emailComment) {
		if (HookRegistry::call('TrackDirectorAction::postLayoutComment', array(&$paper, &$emailComment))) return;

		import('submission.form.comment.LayoutCommentForm');
		
		$commentForm = &new LayoutCommentForm($paper, ROLE_ID_EDITOR);
		$commentForm->readInputData();
		
		if ($commentForm->validate()) {
			$commentForm->execute();
			
			if ($emailComment) {
				$commentForm->email();
			}
			
		} else {
			$commentForm->display();
			return false;
		}
		return true;
	}	/**
	 * Accepts the review assignment on behalf of its reviewer.
	 * @param $reviewId int
	 */
	function confirmReviewForReviewer($reviewId) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &Request::getUser();

		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId(), true);
		
		if (HookRegistry::call('TrackDirectorAction::confirmReviewForReviewer', array(&$reviewAssignment, &$reviewer))) return;

		// Only confirm the review for the reviewer if 
		// he has not previously done so.
		if ($reviewAssignment->getDateConfirmed() == null) {
			$reviewAssignment->setDeclined(0);
			$reviewAssignment->setDateConfirmed(Core::getCurrentDate());
			$reviewAssignment->stampModified();
			$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);

			// Add log
			import('paper.log.PaperLog');
			import('paper.log.PaperEventLogEntry');

			$entry = &new PaperEventLogEntry();
			$entry->setPaperId($reviewAssignment->getPaperId());
			$entry->setUserId($user->getUserId());
			$entry->setDateLogged(Core::getCurrentDate());
			$entry->setEventType(PAPER_LOG_REVIEW_ACCEPT_BY_PROXY);
			$entry->setLogMessage('log.review.reviewAcceptedByProxy', array('reviewerName' => $reviewer->getFullName(), 'paperId' => $reviewAssignment->getPaperId(), 'round' => $reviewAssignment->getRound(), 'userName' => $user->getFullName()));
			$entry->setAssocType(LOG_TYPE_REVIEW);
			$entry->setAssocId($reviewAssignment->getReviewId());

			PaperLog::logEventEntry($reviewAssignment->getPaperId(), $entry);
		}
	}

	/**
	 * Upload a review on behalf of its reviewer.
	 * @param $reviewId int
	 */
	function uploadReviewForReviewer($reviewId) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &Request::getUser();

		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId(), true);
		
		if (HookRegistry::call('TrackDirectorAction::uploadReviewForReviewer', array(&$reviewAssignment, &$reviewer))) return;

		// Upload the review file.
		import('file.PaperFileManager');
		$paperFileManager = &new PaperFileManager($reviewAssignment->getPaperId());
		// Only upload the file if the reviewer has yet to submit a recommendation
		if ($reviewAssignment->getRecommendation() == null && !$reviewAssignment->getCancelled()) {
			$fileName = 'upload';
			if ($paperFileManager->uploadedFileExists($fileName)) {
				if ($reviewAssignment->getReviewerFileId() != null) {
					$fileId = $paperFileManager->uploadReviewFile($fileName, $reviewAssignment->getReviewerFileId());
				} else {
					$fileId = $paperFileManager->uploadReviewFile($fileName);
				}
			}
		}

		if (isset($fileId) && $fileId != 0) {
			// Only confirm the review for the reviewer if 
			// he has not previously done so.
			if ($reviewAssignment->getDateConfirmed() == null) {
				$reviewAssignment->setDeclined(0);
				$reviewAssignment->setDateConfirmed(Core::getCurrentDate());
			}

			$reviewAssignment->setReviewerFileId($fileId);
			$reviewAssignment->stampModified();
			$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);

			// Add log
			import('paper.log.PaperLog');
			import('paper.log.PaperEventLogEntry');

			$entry = &new PaperEventLogEntry();
			$entry->setPaperId($reviewAssignment->getPaperId());
			$entry->setUserId($user->getUserId());
			$entry->setDateLogged(Core::getCurrentDate());
			$entry->setEventType(PAPER_LOG_REVIEW_FILE_BY_PROXY);
			$entry->setLogMessage('log.review.reviewFileByProxy', array('reviewerName' => $reviewer->getFullName(), 'paperId' => $reviewAssignment->getPaperId(), 'round' => $reviewAssignment->getRound(), 'userName' => $user->getFullName()));
			$entry->setAssocType(LOG_TYPE_REVIEW);
			$entry->setAssocId($reviewAssignment->getReviewId());

			PaperLog::logEventEntry($reviewAssignment->getPaperId(), $entry);
		}
	}

	/**
	 * Helper method for building submission breadcrumb
	 * @param $paperId
	 * @param $parentPage name of submission component
	 * @return array
	 */
	function submissionBreadcrumb($paperId, $parentPage, $track) {
		$breadcrumb = array();
		if ($paperId) {
			$breadcrumb[] = array(Request::url(null, null, $track, 'submission', $paperId), "#$paperId", true);
		}

		if ($parentPage) {
			switch($parentPage) {
				case 'summary':
					$parent = array(Request::url(null, null, $track, 'submission', $paperId), 'submission.summary');
					break;
				case 'review':
					$parent = array(Request::url(null, null, $track, 'submissionReview', $paperId), 'submission.review');
					break;
				case 'editing':
					$parent = array(Request::url(null, null, $track, 'submissionEditing', $paperId), 'submission.editing');
					break;
				case 'history':
					$parent = array(Request::url(null, null, $track, 'submissionHistory', $paperId), 'submission.history');
					break;
			}
			if ($track != 'editor' && $track != 'trackDirector') {
				$parent[0] = Request::url(null, null, $track, 'submission', $paperId);
			}
			$breadcrumb[] = $parent;
		}
		return $breadcrumb;
	}
	
}

?>
