<?php

/**
 * TrackEditorAction.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * TrackEditorAction class.
 *
 * $Id$
 */

import('submission.common.Action');

class TrackEditorAction extends Action {

	/**
	 * Constructor.
	 */
	function TrackEditorAction() {
		parent::Action();
	}
	
	/**
	 * Actions.
	 */
	 
	/**
	 * Changes the track a paper belongs in.
	 * @param $trackEditorSubmission int
	 * @param $trackId int
	 */
	function changeTrack($trackEditorSubmission, $trackId) {
		if (!HookRegistry::call('TrackEditorAction::changeTrack', array(&$trackEditorSubmission, $trackId))) {
			$trackEditorSubmissionDao = &DAORegistry::getDAO('TrackEditorSubmissionDAO');
			$trackEditorSubmission->setTrackId($trackId);
			$trackEditorSubmissionDao->updateTrackEditorSubmission($trackEditorSubmission);
		}
	}
	 
	/**
	 * Records an editor's submission decision.
	 * @param $trackEditorSubmission object
	 * @param $decision int
	 */
	function recordDecision($trackEditorSubmission, $decision) {
		$editAssignments =& $trackEditorSubmission->getEditAssignments();
		if (empty($editAssignments)) return;

		$trackEditorSubmissionDao = &DAORegistry::getDAO('TrackEditorSubmissionDAO');
		$user = &Request::getUser();
		$editorDecision = array(
			'editDecisionId' => null,
			'editorId' => $user->getUserId(),
			'decision' => $decision,
			'dateDecided' => date(Core::getCurrentDate())
		);

		if (!HookRegistry::call('TrackEditorAction::recordDecision', array(&$trackEditorSubmission, $editorDecision))) {
			if ($decision == SUBMISSION_EDITOR_DECISION_DECLINE) {
				$trackEditorSubmission->setStatus(SUBMISSION_STATUS_DECLINED);
				$trackEditorSubmission->stampStatusModified();
			} else {
				$trackEditorSubmission->setStatus(SUBMISSION_STATUS_QUEUED);		
				$trackEditorSubmission->stampStatusModified();
			}
		
			$trackEditorSubmission->addDecision($editorDecision, $trackEditorSubmission->getReviewProgress(), $trackEditorSubmission->getCurrentRound());
			$decisions = TrackEditorSubmission::getEditorDecisionOptions();
			// Add log
			import('paper.log.PaperLog');
			import('paper.log.PaperEventLogEntry');
			PaperLog::logEvent($trackEditorSubmission->getPaperId(), PAPER_LOG_EDITOR_DECISION, LOG_TYPE_EDITOR, $user->getUserId(), 'log.editor.decision', array('editorName' => $user->getFullName(), 'paperId' => $trackEditorSubmission->getPaperId(), 'decision' => Locale::translate($decisions[$decision])));
		}
		
		if($decision == SUBMISSION_EDITOR_DECISION_ACCEPT) {
			// completeReview will take care of updating the
			// submission with the new decision.
			TrackEditorAction::completeReview($trackEditorSubmission);
		} else {
			// Insert the new decision.
			$trackEditorSubmissionDao->updateTrackEditorSubmission($trackEditorSubmission);
		}
	}
	
	/**
	 * After a decision has been recorded, bumps the paper to the next stage.
	 * If the submission requires completion, it's sent back to the author.
	 * If not, review is complete, and the paper can be released.
	 * @param $event object
	 * @param $trackEditorSubmission object
	 * @param $decision int
	 */
	function completeReview($trackEditorSubmission) {
		$event =& Request::getEvent();
		
		if($event->getCollectPapersWithAbstracts() ||
				!$event->getAcceptPapers()) {

			// Only one review was necessary; the submission is complete.
			$trackEditorSubmission->setReviewProgress(REVIEW_PROGRESS_COMPLETE);
			$trackEditorSubmission->setStatus(SUBMISSION_STATUS_PUBLISHED);

		} else {
			// two-stage submission; paper required
			// The submission is incomplete, and needs the author to submit
			// more materials (potentially for another round of reviews)

			if($trackEditorSubmission->getReviewProgress() == REVIEW_PROGRESS_ABSTRACT) {

				$oldRound = $trackEditorSubmission->getCurrentRound();

				// We've just completed reviewing the abstract. If the paper needs
				// a separate review progress, flag it as such and move it back
				// to review round 1.
				if($event->getReviewPapers()) {
					$trackEditorSubmission->setReviewProgress(REVIEW_PROGRESS_PAPER);
					$trackEditorSubmission->setCurrentRound(1);
				} else {
					$trackEditorSubmission->setReviewProgress(REVIEW_PROGRESS_COMPLETE);
				}

				// The paper itself needs to be collected. Flag it so the author
				// may complete it.
				$trackEditorSubmission->setSubmissionProgress(3);

				// TODO: notify the author the submission must be completed.
				// Q: should the editor be given this option explicitly?

				// Now, reassign all reviewers that submitted a review for the last
				// round of reviews.
				foreach ($trackEditorSubmission->getReviewAssignments(REVIEW_PROGRESS_ABSTRACT, $oldRound) as $reviewAssignment) {
					if ($reviewAssignment->getRecommendation() != null) {
						// This reviewer submitted a review; reassign them
						TrackEditorAction::addReviewer($trackEditorSubmission, $reviewAssignment->getReviewerId(), REVIEW_PROGRESS_PAPER, 1);
					}
				}

			} else { // REVIEW_PROGRESS_PAPER

				// Mark the review as complete
				$trackEditorSubmission->setReviewProgress(REVIEW_PROGRESS_COMPLETE);
				$trackEditorSubmission->setStatus(SUBMISSION_STATUS_PUBLISHED);

				// TODO: log? notify authors?

			}
		}

		$trackEditorSubmissionDao =& DAORegistry::getDao('TrackEditorSubmissionDAO');
		$trackEditorSubmissionDao->updateTrackEditorSubmission($trackEditorSubmission);
		$trackEditorSubmission->stampStatusModified();

		// Commit the paper changes
		$paperDao = &DAORegistry::getDao('PaperDAO');
		$paperDao->updatePaper($trackEditorSubmission);
	}
	
	/**
	 * Assigns a reviewer to a submission.
	 * @param $trackEditorSubmission object
	 * @param $reviewerId int
	 * @param $type int
	 * @param $round int
	 */
	function addReviewer($trackEditorSubmission, $reviewerId, $type, $round) {
		$trackEditorSubmissionDao = &DAORegistry::getDAO('TrackEditorSubmissionDAO');
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &Request::getUser();
		
		$reviewer = &$userDao->getUser($reviewerId);

		// Check to see if the requested reviewer is not already
		// assigned to review this paper.
		if ($round == null) {
			$round = $trackEditorSubmission->getCurrentRound();
		}
		
		$assigned = $trackEditorSubmissionDao->reviewerExists($trackEditorSubmission->getPaperId(), $reviewerId, $type, $round);
				
		// Only add the reviewer if he has not already
		// been assigned to review this paper.
		if (!$assigned && isset($reviewer) && !HookRegistry::call('TrackEditorAction::addReviewer', array(&$trackEditorSubmission, $reviewerId))) {
			$reviewAssignment = &new ReviewAssignment();
			$reviewAssignment->setReviewerId($reviewerId);
			$reviewAssignment->setDateAssigned(Core::getCurrentDate());
			$reviewAssignment->setType($type);
			$reviewAssignment->setRound($round);
			
			$trackEditorSubmission->AddReviewAssignment($reviewAssignment);
			$trackEditorSubmissionDao->updateTrackEditorSubmission($trackEditorSubmission);
		
			$reviewAssignment = $reviewAssignmentDao->getReviewAssignment($trackEditorSubmission->getPaperId(), $reviewerId, $type, $round);

			$event = &Request::getEvent();
			if ($event->getSetting('numWeeksPerReview', true) != null)
				TrackEditorAction::setDueDate($trackEditorSubmission->getPaperId(), $reviewAssignment->getReviewId(), null, $event->getSetting('numWeeksPerReview',true));
			
			// Add log
			import('paper.log.PaperLog');
			import('paper.log.PaperEventLogEntry');
			PaperLog::logEvent($trackEditorSubmission->getPaperId(), PAPER_LOG_REVIEW_ASSIGN, LOG_TYPE_REVIEW, $reviewAssignment->getReviewId(), 'log.review.reviewerAssigned', array('reviewerName' => $reviewer->getFullName(), 'paperId' => $trackEditorSubmission->getPaperId(), 'round' => $round, 'type' => $type));
		}
	}
	
	/**
	 * Clears a review assignment from a submission.
	 * @param $trackEditorSubmission object
	 * @param $reviewId int
	 */
	function clearReview($trackEditorSubmission, $reviewId) {
		$trackEditorSubmissionDao = &DAORegistry::getDAO('TrackEditorSubmissionDAO');
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &Request::getUser();
		
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		
		if (isset($reviewAssignment) && $reviewAssignment->getPaperId() == $trackEditorSubmission->getPaperId() && !HookRegistry::call('TrackEditorAction::clearReview', array(&$trackEditorSubmission, $reviewAssignment))) {
			$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
			if (!isset($reviewer)) return false;
			$trackEditorSubmission->removeReviewAssignment($reviewId);
			$trackEditorSubmissionDao->updateTrackEditorSubmission($trackEditorSubmission);
			
			// Add log
			import('paper.log.PaperLog');
			import('paper.log.PaperEventLogEntry');
			PaperLog::logEvent($trackEditorSubmission->getPaperId(), PAPER_LOG_REVIEW_CLEAR, LOG_TYPE_REVIEW, $reviewAssignment->getReviewId(), 'log.review.reviewCleared', array('reviewerName' => $reviewer->getFullName(), 'paperId' => $trackEditorSubmission->getPaperId(), 'type' => $reviewAssignment->getType(), 'round' => $reviewAssignment->getRound()));
		}		
	}

	/**
	 * Notifies a reviewer about a review assignment.
	 * @param $trackEditorSubmission object
	 * @param $reviewId int
	 * @return boolean true iff ready for redirect
	 */
	function notifyReviewer($trackEditorSubmission, $reviewId, $send = false) {
		$trackEditorSubmissionDao = &DAORegistry::getDAO('TrackEditorSubmissionDAO');
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		
		$conference = &Request::getConference();
		$event = &Request::getEvent();
		$user = &Request::getUser();
		
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);

		$isEmailBasedReview = $conference->getSetting('mailSubmissionsToReviewers')==1?true:false;
		$reviewerAccessKeysEnabled = $conference->getSetting('reviewerAccessKeysEnabled');

		// If we're using access keys, disable the address fields
		// for this message. (Prevents security issue: track editor
		// could CC or BCC someone else, or change the reviewer address,
		// in order to get the access key.)
		$preventAddressChanges = $reviewerAccessKeysEnabled;

		import('mail.PaperMailTemplate');

		$email = &new PaperMailTemplate($trackEditorSubmission, $isEmailBasedReview?'REVIEW_REQUEST_ATTACHED':($reviewerAccessKeysEnabled?'REVIEW_REQUEST_ONECLICK':'REVIEW_REQUEST'), null, $isEmailBasedReview?true:null);

		if ($preventAddressChanges) {
			$email->setAddressFieldsEnabled(false);
		}

		if ($reviewAssignment->getPaperId() == $trackEditorSubmission->getPaperId()) {
			$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
			if (!isset($reviewer)) return true;
			
			if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
				HookRegistry::call('TrackEditorAction::notifyReviewer', array(&$trackEditorSubmission, &$reviewAssignment, &$email));
				if ($email->isEnabled()) {
					$email->setAssoc(PAPER_EMAIL_REVIEW_NOTIFY_REVIEWER, PAPER_EMAIL_TYPE_REVIEW, $reviewId);
					if ($reviewerAccessKeysEnabled) {
						import('security.AccessKeyManager');
						import('pages.reviewer.ReviewerHandler');
						$accessKeyManager =& new AccessKeyManager();

						// Key lifetime is the typical review period plus four weeks
						$keyLifetime = ($event->getSetting('numWeeksPerReview') + 4) * 7;

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
						$numWeeks = max((int) $event->getSetting('numWeeksPerReview'), 2);
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
					if ($isEmailBasedReview) {
						// An email-based review process was selected. Attach
						// the current review version.
						import('file.TemporaryFileManager');
						$temporaryFileManager = &new TemporaryFileManager();
						$reviewVersion =& $trackEditorSubmission->getReviewFile();
						if ($reviewVersion) {
							$temporaryFile = $temporaryFileManager->paperToTemporaryFile($reviewVersion, $user->getUserId());
							$email->addPersistAttachment($temporaryFile);
						}
					}
				}
				$email->displayEditForm(Request::url(null, null, null, 'notifyReviewer'), array('reviewId' => $reviewId, 'paperId' => $trackEditorSubmission->getPaperId()));
				return false;
			}
		}
		return true;
	}

	/**
	 * Cancels a review.
	 * @param $trackEditorSubmission object
	 * @param $reviewId int
	 * @return boolean true iff ready for redirect
	 */
	function cancelReview($trackEditorSubmission, $reviewId, $send = false) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$trackEditorSubmissionDao = &DAORegistry::getDAO('TrackEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');

		$conference = &Request::getConference();
		$user = &Request::getUser();

		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
		if (!isset($reviewer)) return true;

		if ($reviewAssignment->getPaperId() == $trackEditorSubmission->getPaperId()) {
			// Only cancel the review if it is currently not cancelled but has previously
			// been initiated, and has not been completed.
			if ($reviewAssignment->getDateNotified() != null && !$reviewAssignment->getCancelled() && ($reviewAssignment->getDateCompleted() == null || $reviewAssignment->getDeclined())) {
				import('mail.PaperMailTemplate');
				$email = &new PaperMailTemplate($trackEditorSubmission, 'REVIEW_CANCEL');

				if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
					HookRegistry::call('TrackEditorAction::cancelReview', array(&$trackEditorSubmission, &$reviewAssignment, &$email));
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
					PaperLog::logEvent($trackEditorSubmission->getPaperId(), PAPER_LOG_REVIEW_CANCEL, LOG_TYPE_REVIEW, $reviewAssignment->getReviewId(), 'log.review.reviewCancelled', array('reviewerName' => $reviewer->getFullName(), 'paperId' => $trackEditorSubmission->getPaperId(), 'round' => $reviewAssignment->getRound()));
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
					$email->displayEditForm(Request::url(null, null, null, 'cancelReview', 'send'), array('reviewId' => $reviewId, 'paperId' => $trackEditorSubmission->getPaperId()));
					return false;
				}
			}				
		}
		return true;
	}
	
	/**
	 * Reminds a reviewer about a review assignment.
	 * @param $trackEditorSubmission object
	 * @param $reviewId int
	 * @return boolean true iff no error was encountered
	 */
	function remindReviewer($trackEditorSubmission, $reviewId, $send = false) {
		$trackEditorSubmissionDao = &DAORegistry::getDAO('TrackEditorSubmissionDAO');
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
			
		$conference = &Request::getConference();
		$event = &Request::getEvent();
		$user = &Request::getUser();
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$reviewerAccessKeysEnabled = $conference->getSetting('reviewerAccessKeysEnabled');

		// If we're using access keys, disable the address fields
		// for this message. (Prevents security issue: track editor
		// could CC or BCC someone else, or change the reviewer address,
		// in order to get the access key.)
		$preventAddressChanges = $reviewerAccessKeysEnabled;

		import('mail.PaperMailTemplate');
		$email = &new PaperMailTemplate($trackEditorSubmission, $reviewerAccessKeysEnabled?'REVIEW_REMIND_ONECLICK':'REVIEW_REMIND');

		if ($preventAddressChanges) {
			$email->setAddressFieldsEnabled(false);
		}

		if ($send && !$email->hasErrors()) {
			HookRegistry::call('TrackEditorAction::remindReviewer', array(&$trackEditorSubmission, &$reviewAssignment, &$email));
			$email->setAssoc(PAPER_EMAIL_REVIEW_REMIND, PAPER_EMAIL_TYPE_REVIEW, $reviewId);

			$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());

			if ($reviewerAccessKeysEnabled) {
				import('security.AccessKeyManager');
				import('pages.reviewer.ReviewerHandler');
				$accessKeyManager =& new AccessKeyManager();

				// Key lifetime is the typical review period plus four weeks
				$keyLifetime = ($event->getSetting('numWeeksPerReview') + 4) * 7;
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
		} elseif ($reviewAssignment->getPaperId() == $trackEditorSubmission->getPaperId()) {
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
					'paperId' => $trackEditorSubmission->getPaperId(),
					'reviewId' => $reviewId
				)
			);
			return false;
		}
		return true;
	}
	
	/**
	 * Thanks a reviewer for completing a review assignment.
	 * @param $trackEditorSubmission object
	 * @param $reviewId int
	 * @return boolean true iff ready for redirect
	 */
	function thankReviewer($trackEditorSubmission, $reviewId, $send = false) {
		$trackEditorSubmissionDao = &DAORegistry::getDAO('TrackEditorSubmissionDAO');
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		
		$conference = &Request::getConference();
		$user = &Request::getUser();
		
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		
		import('mail.PaperMailTemplate');
		$email = &new PaperMailTemplate($trackEditorSubmission, 'REVIEW_ACK');

		if ($reviewAssignment->getPaperId() == $trackEditorSubmission->getPaperId()) {
			$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
			if (!isset($reviewer)) return true;
			
			if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
				HookRegistry::call('TrackEditorAction::thankReviewer', array(&$trackEditorSubmission, &$reviewAssignment, &$email));
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
				$email->displayEditForm(Request::url(null, null, null, 'thankReviewer', 'send'), array('reviewId' => $reviewId, 'paperId' => $trackEditorSubmission->getPaperId()));
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
		
		if ($reviewAssignment->getPaperId() == $paperId && !HookRegistry::call('TrackEditorAction::rateReviewer', array(&$reviewAssignment, &$reviewer, &$quality))) {
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
	 * Makes a reviewer's annotated version of an paper available to the author.
	 * @param $paperId int
	 * @param $reviewId int
	 * @param $viewable boolean
	 */
	function makeReviewerFileViewable($paperId, $reviewId, $fileId, $revision, $viewable = false) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$paperFileDao = &DAORegistry::getDAO('PaperFileDAO');
		
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$paperFile = &$paperFileDao->getPaperFile($fileId, $revision);
		
		if ($reviewAssignment->getPaperId() == $paperId && $reviewAssignment->getReviewerFileId() == $fileId && !HookRegistry::call('TrackEditorAction::makeReviewerFileViewable', array(&$reviewAssignment, &$paperFile, &$viewable))) {
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
		
		if ($reviewAssignment->getPaperId() == $paperId && !HookRegistry::call('TrackEditorAction::setDueDate', array(&$reviewAssignment, &$reviewer, &$dueDate, &$numWeeks))) {
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
	 * Notifies an author that a submission was unsuitable.
	 * @param $trackEditorSubmission object
	 * @return boolean true iff ready for redirect
	 */
	function unsuitableSubmission($trackEditorSubmission, $send = false) {
		$trackEditorSubmissionDao = &DAORegistry::getDAO('TrackEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		
		$conference = &Request::getConference();
		$user = &Request::getUser();

		$author = &$userDao->getUser($trackEditorSubmission->getUserId());
		if (!isset($author)) return true;
		
		import('mail.PaperMailTemplate');
		$email = &new PaperMailTemplate($trackEditorSubmission, 'SUBMISSION_UNSUITABLE');

		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			HookRegistry::call('TrackEditorAction::unsuitableSubmission', array(&$trackEditorSubmission, &$author, &$email));
			if ($email->isEnabled()) {
				$email->setAssoc(PAPER_EMAIL_EDITOR_NOTIFY_AUTHOR_UNSUITABLE, PAPER_EMAIL_TYPE_EDITOR, $user->getUserId());
				$email->send();
			}
			TrackEditorAction::archiveSubmission($trackEditorSubmission);
			return true;
		} else {
			if (!Request::getUserVar('continued')) {
				$paramArray = array(
					'editorialContactSignature' => $user->getContactSignature(),
					'authorName' => $author->getFullName()
				);
				$email->assignParams($paramArray);
				$email->addRecipient($author->getEmail(), $author->getFullName());
			}
			$email->displayEditForm(Request::url(null, null, null, 'unsuitableSubmission'), array('paperId' => $trackEditorSubmission->getPaperId()));
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
		
		if ($reviewAssignment->getPaperId() == $paperId && !HookRegistry::call('TrackEditorAction::setReviewerRecommendation', array(&$reviewAssignment, &$reviewer, &$recommendation, &$acceptOption))) {
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
	 * @param $trackEditorSubmission object
	 * @param $fileId int
	 * @param $revision int
	 * TODO: SECURITY!
	 */
	function resubmitFile($trackEditorSubmission, $fileId, $revision) {
		import('file.PaperFileManager');
		$paperFileManager = &new PaperFileManager($trackEditorSubmission->getPaperId());
		$trackEditorSubmissionDao = &DAORegistry::getDAO('TrackEditorSubmissionDAO');
		$paperFileDao = &DAORegistry::getDAO('PaperFileDAO');
		$user = &Request::getUser();
		
		if (!HookRegistry::call('TrackEditorAction::resubmitFile', array(&$trackEditorSubmission, &$fileId, &$revision))) {
			// Increment the round
			$currentRound = $trackEditorSubmission->getCurrentRound();
			$trackEditorSubmission->setCurrentRound($currentRound + 1);
			$trackEditorSubmission->stampStatusModified();
		
			// Copy the file from the editor decision file folder to the review file folder
			$newFileId = $paperFileManager->copyToReviewFile($fileId, $revision, $trackEditorSubmission->getReviewFileId());
			$newReviewFile = $paperFileDao->getPaperFile($newFileId);
			$newReviewFile->setRound($trackEditorSubmission->getCurrentRound());
			$paperFileDao->updatePaperFile($newReviewFile);

			// Copy the file from the editor decision file folder to the next-round editor file
			// $editorFileId may or may not be null after assignment
			$editorFileId = $trackEditorSubmission->getEditorFileId() != null ? $trackEditorSubmission->getEditorFileId() : null;

			// $editorFileId definitely will not be null after assignment
			$editorFileId = $paperFileManager->copyToEditorFile($newFileId, null, $editorFileId);
			$newEditorFile = $paperFileDao->getPaperFile($editorFileId);
			$newEditorFile->setRound($trackEditorSubmission->getCurrentRound());
			$paperFileDao->updatePaperFile($newEditorFile);

			// The review revision is the highest revision for the review file.
			$reviewRevision = $paperFileDao->getRevisionNumber($newFileId);
			$trackEditorSubmission->setReviewRevision($reviewRevision);
		
			$trackEditorSubmissionDao->updateTrackEditorSubmission($trackEditorSubmission);

			// Now, reassign all reviewers that submitted a review for this new round of reviews.
			$previousRound = $trackEditorSubmission->getCurrentRound() - 1;
			foreach ($trackEditorSubmission->getReviewAssignments($previousRound) as $reviewAssignment) {
				if ($reviewAssignment->getRecommendation() != null) {
					// Then this reviewer submitted a review.
					TrackEditorAction::addReviewer($trackEditorSubmission, $reviewAssignment->getReviewerId(), $trackEditorSubmission->getCurrentRound());
				}
			}
		
		
			// Add log
			import('paper.log.PaperLog');
			import('paper.log.PaperEventLogEntry');
			PaperLog::logEvent($trackEditorSubmission->getPaperId(), ARTICLE_LOG_REVIEW_RESUBMIT, ARTICLE_LOG_TYPE_EDITOR, $user->getUserId(), 'log.review.resubmit', array('paperId' => $trackEditorSubmission->getPaperId()));
		}
	}
	
	/**
	 * Upload the review version of an paper.
	 * @param $trackEditorSubmission object
	 */
	function uploadReviewVersion($trackEditorSubmission) {
		import('file.PaperFileManager');
		$paperFileManager = &new PaperFileManager($trackEditorSubmission->getPaperId());
		$trackEditorSubmissionDao = &DAORegistry::getDAO('TrackEditorSubmissionDAO');
		
		$fileName = 'upload';
		if ($paperFileManager->uploadedFileExists($fileName) && !HookRegistry::call('TrackEditorAction::uploadReviewVersion', array(&$trackEditorSubmission))) {
			if ($trackEditorSubmission->getReviewFileId() != null) {
				$reviewFileId = $paperFileManager->uploadReviewFile($fileName, $trackEditorSubmission->getReviewFileId());
				// Increment the review revision.
				$trackEditorSubmission->setReviewRevision($trackEditorSubmission->getReviewRevision()+1);
			} else {
				$reviewFileId = $paperFileManager->uploadReviewFile($fileName);
				$trackEditorSubmission->setReviewRevision(1);
			}
			$editorFileId = $paperFileManager->copyToEditorFile($reviewFileId, $trackEditorSubmission->getReviewRevision(), $trackEditorSubmission->getEditorFileId());
		}
		
		if (isset($reviewFileId) && $reviewFileId != 0 && isset($editorFileId) && $editorFileId != 0) {
			$trackEditorSubmission->setReviewFileId($reviewFileId);
			$trackEditorSubmission->setEditorFileId($editorFileId);
	
			$trackEditorSubmissionDao->updateTrackEditorSubmission($trackEditorSubmission);
		}
	}

	/**
	 * Upload the post-review version of an paper.
	 * @param $trackEditorSubmission object
	 */
	function uploadEditorVersion($trackEditorSubmission) {
		import('file.PaperFileManager');
		$paperFileManager = &new PaperFileManager($trackEditorSubmission->getPaperId());
		$trackEditorSubmissionDao = &DAORegistry::getDAO('TrackEditorSubmissionDAO');
		$user = &Request::getUser();
		
		$fileName = 'upload';
		if ($paperFileManager->uploadedFileExists($fileName) && !HookRegistry::call('TrackEditorAction::uploadEditorVersion', array(&$trackEditorSubmission))) {
			if ($trackEditorSubmission->getEditorFileId() != null) {
				$fileId = $paperFileManager->uploadEditorDecisionFile($fileName, $trackEditorSubmission->getEditorFileId());
			} else {
				$fileId = $paperFileManager->uploadEditorDecisionFile($fileName);
			}
		}
		
		if (isset($fileId) && $fileId != 0) {
			$trackEditorSubmission->setEditorFileId($fileId);

			$trackEditorSubmissionDao->updateTrackEditorSubmission($trackEditorSubmission);
			
			// Set initial layout version to final copyedit version
			$layoutDao = &DAORegistry::getDAO('LayoutAssignmentDAO');
			$layoutAssignment = &$layoutDao->getLayoutAssignmentByPaperId($trackEditorSubmission->getPaperId());

			if (isset($layoutAssignment) && !$layoutAssignment->getLayoutFileId()) {
				import('file.PaperFileManager');
				$paperFileManager = &new PaperFileManager($trackEditorSubmission->getPaperId());
				if ($layoutFileId = $paperFileManager->copyToLayoutFile($fileId)) {
					$layoutAssignment->setLayoutFileId($layoutFileId);
					$layoutDao->updateLayoutAssignment($layoutAssignment);
				}
			}
		
			// Add a publishedpaper object
			$publishedPaperDao =& DAORegistry::getDAO('PublishedPaperDAO');
			if(($publishedPaper = $publishedPaperDao->getPublishedPaperByPaperId($trackEditorSubmission->getPaperId())) == null) {
				$eventId = $trackEditorSubmission->getEventId();

				$publishedPaper =& new PublishedPaper();
				$publishedPaper->setPaperId($trackEditorSubmission->getPaperId());
				$publishedPaper->setEventId($eventId);
				$publishedPaper->setDatePublished(Core::getCurrentDate());
				$publishedPaper->setSeq(100000); // KLUDGE: End of list
				$publishedPaper->setViews(0);
				$publishedPaper->setAccessStatus(0);

				$publishedPaperDao->insertPublishedPaper($publishedPaper);

				// Resequence the papers.
				$publishedPaperDao->resequencePublishedPapers($trackEditorSubmission->getTrackId(), $eventId);

				// If we're using custom track ordering, and if this is the first
				// paper published in a track, make sure we enter a custom ordering
				// for it. (Default at the end of the list.)
				$trackDao =& DAORegistry::getDAO('TrackDAO');
				if ($trackDao->customTrackOrderingExists($eventId)) {
					if ($trackDao->getCustomTrackOrder($eventId, $submission->getTrackId()) === null) {
						$trackDao->insertCustomTrackOrder($eventId, $submission->getTrackId(), 10000); // KLUDGE: End of list
						$trackDao->resequenceCustomTrackOrders($eventId);
					}
				}
			}		
			// Add log
			import('paper.log.PaperLog');
			import('paper.log.PaperEventLogEntry');
			PaperLog::logEvent($trackEditorSubmission->getPaperId(), PAPER_LOG_EDITOR_FILE, LOG_TYPE_EDITOR, $trackEditorSubmission->getEditorFileId(), 'log.editor.editorFile');
		}
	}
	
	/**
	 * Archive a submission.
	 * @param $trackEditorSubmission object
	 */
	function archiveSubmission($trackEditorSubmission) {
		$trackEditorSubmissionDao = &DAORegistry::getDAO('TrackEditorSubmissionDAO');
		$user = &Request::getUser();
		
		if (HookRegistry::call('TrackEditorAction::archiveSubmission', array(&$trackEditorSubmission))) return;

		$trackEditorSubmission->setStatus(SUBMISSION_STATUS_ARCHIVED);
		$trackEditorSubmission->stampStatusModified();
		
		$trackEditorSubmissionDao->updateTrackEditorSubmission($trackEditorSubmission);
		
		// Add log
		import('paper.log.PaperLog');
		import('paper.log.PaperEventLogEntry');
		PaperLog::logEvent($trackEditorSubmission->getPaperId(), PAPER_LOG_EDITOR_ARCHIVE, LOG_TYPE_EDITOR, $trackEditorSubmission->getPaperId(), 'log.editor.archived', array('paperId' => $trackEditorSubmission->getPaperId()));
	}
	
	/**
	 * Restores a submission to the queue.
	 * @param $trackEditorSubmission object
	 */
	function restoreToQueue($trackEditorSubmission) {
		if (HookRegistry::call('TrackEditorAction::restoreToQueue', array(&$trackEditorSubmission))) return;

		$trackEditorSubmissionDao = &DAORegistry::getDAO('TrackEditorSubmissionDAO');

		// Determine which queue to return the paper to: the
		// scheduling queue or the editing queue.
		$publishedPaperDao =& DAORegistry::getDAO('PublishedPaperDAO');
		$publishedPaper =& $publishedPaperDao->getPublishedPaperByPaperId($trackEditorSubmission->getPaperId());
		if ($publishedPaper) {
			$trackEditorSubmission->setStatus(SUBMISSION_STATUS_PUBLISHED);
		} else {
			$trackEditorSubmission->setStatus(SUBMISSION_STATUS_QUEUED);
		}
		unset($publishedPaper);

		$trackEditorSubmission->stampStatusModified();
		
		$trackEditorSubmissionDao->updateTrackEditorSubmission($trackEditorSubmission);
	
		// Add log
		import('paper.log.PaperLog');
		import('paper.log.PaperEventLogEntry');
		PaperLog::logEvent($trackEditorSubmission->getPaperId(), PAPER_LOG_EDITOR_RESTORE, LOG_TYPE_EDITOR, $trackEditorSubmission->getPaperId(), 'log.editor.restored', array('paperId' => $trackEditorSubmission->getPaperId()));
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
		$submissionDao = &DAORegistry::getDAO('TrackEditorSubmissionDAO');
		
		$layoutAssignment = &$submission->getLayoutAssignment();
		
		$fileName = 'layoutFile';
		if ($paperFileManager->uploadedFileExists($fileName) && !HookRegistry::call('TrackEditorAction::uploadLayoutVersion', array(&$submission, &$layoutAssignment))) {
			$layoutFileId = $paperFileManager->uploadLayoutFile($fileName, $layoutAssignment->getLayoutFileId());
		
			$layoutAssignment->setLayoutFileId($layoutFileId);
			$submissionDao->updateTrackEditorSubmission($submission);
		}
	}
	
	/**
	 * Assign a layout editor to a submission.
	 * @param $submission object
	 * @param $editorId int user ID of the new layout editor
	 */
	function assignLayoutEditor($submission, $editorId) {
		if (HookRegistry::call('TrackEditorAction::assignLayoutEditor', array(&$submission, &$editorId))) return;

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
		$submissionDao = &DAORegistry::getDAO('TrackEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &Request::getUser();
		
		import('mail.PaperMailTemplate');
		$email = &new PaperMailTemplate($submission, 'LAYOUT_REQUEST');
		$layoutAssignment = &$submission->getLayoutAssignment();
		$layoutEditor = &$userDao->getUser($layoutAssignment->getEditorId());
		if (!isset($layoutEditor)) return true;
		
		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			HookRegistry::call('TrackEditorAction::notifyLayoutEditor', array(&$submission, &$layoutEditor, &$layoutAssignment, &$email));
			if ($email->isEnabled()) {
				$email->setAssoc(ARTICLE_EMAIL_LAYOUT_NOTIFY_EDITOR, ARTICLE_EMAIL_TYPE_LAYOUT, $layoutAssignment->getLayoutId());
				$email->send();
			}
			
			$layoutAssignment->setDateNotified(Core::getCurrentDate());
			$layoutAssignment->setDateUnderway(null);
			$layoutAssignment->setDateCompleted(null);
			$layoutAssignment->setDateAcknowledged(null);
			$submissionDao->updateTrackEditorSubmission($submission);
			
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
		$submissionDao = &DAORegistry::getDAO('TrackEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$user = &Request::getUser();
		
		import('mail.PaperMailTemplate');
		$email = &new PaperMailTemplate($submission, 'LAYOUT_ACK');

		$layoutAssignment = &$submission->getLayoutAssignment();
		$layoutEditor = &$userDao->getUser($layoutAssignment->getEditorId());
		if (!isset($layoutEditor)) return true;
		
		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			HookRegistry::call('TrackEditorAction::thankLayoutEditor', array(&$submission, &$layoutEditor, &$layoutAssignment, &$email));
			if ($email->isEnabled()) {
				$email->setAssoc(ARTICLE_EMAIL_LAYOUT_THANK_EDITOR, ARTICLE_EMAIL_TYPE_LAYOUT, $layoutAssignment->getLayoutId());
				$email->send();
			}
			
			$layoutAssignment->setDateAcknowledged(Core::getCurrentDate());
			$submissionDao->updateTrackEditorSubmission($submission);
			
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
		
		if (isset($file) && $file->getFileId() == $fileId && !HookRegistry::call('TrackEditorAction::deletePaperFile', array(&$submission, &$fileId, &$revision))) {
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
		if (HookRegistry::call('TrackEditorAction::deletePaperImage', array(&$submission, &$fileId, &$revision))) return;
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

		if (!HookRegistry::call('TrackEditorAction::addSubmissionNote', array(&$paperId, &$paperNote))) {
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

		if (HookRegistry::call('TrackEditorAction::removeSubmissionNote', array(&$paperId, &$noteId, &$fileId))) return;

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
		
		if (HookRegistry::call('TrackEditorAction::updateSubmissionNote', array(&$paperId, &$paperNote))) return;

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
		if (HookRegistry::call('TrackEditorAction::clearAllSubmissionNotes', array(&$paperId))) return;

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
		if (HookRegistry::call('TrackEditorAction::viewPeerReviewComments', array(&$paper, &$reviewId))) return;

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
		if (HookRegistry::call('TrackEditorAction::postPeerReviewComment', array(&$paper, &$reviewId, &$emailComment))) return;

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
		if (HookRegistry::call('TrackEditorAction::viewEditorDecisionComments', array(&$paper))) return;

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
		if (HookRegistry::call('TrackEditorAction::postEditorDecisionComment', array(&$paper, &$emailComment))) return;

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
	 * @param $trackEditorSubmission object
	 * @param $send boolean
	 */
	function emailEditorDecisionComment($trackEditorSubmission, $send) {
		$userDao = &DAORegistry::getDAO('UserDAO');
		$paperCommentDao =& DAORegistry::getDAO('PaperCommentDAO');
		$conference = &Request::getConference();

		$templateName = null;
		$types = $trackEditorSubmission->getDecisions();
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
		$email = &new PaperMailTemplate($trackEditorSubmission, $templateName);
	
		if ($send && !$email->hasErrors()) {
			HookRegistry::call('TrackEditorAction::emailEditorDecisionComment', array(&$trackEditorSubmission, &$send));
			$email->send();

			$paperComment =& new PaperComment();
			$paperComment->setCommentType(COMMENT_TYPE_EDITOR_DECISION);
			$paperComment->setRoleId(Validation::isEditor()?ROLE_ID_EDITOR:ROLE_ID_TRACK_EDITOR);
			$paperComment->setPaperId($trackEditorSubmission->getPaperId());
			$paperComment->setAuthorId($trackEditorSubmission->getUserId());
			$paperComment->setCommentTitle($email->getSubject());
			$paperComment->setComments($email->getBody());
			$paperComment->setDatePosted(Core::getCurrentDate());
			$paperComment->setViewable(true);
			$paperComment->setAssocId($trackEditorSubmission->getPaperId());
			$paperCommentDao->insertPaperComment($paperComment);

			return true;
		} else {
			if (!Request::getUserVar('continued')) {
				$authorUser =& $userDao->getUser($trackEditorSubmission->getUserId());
				$email->setSubject($trackEditorSubmission->getPaperTitle());
				$email->addRecipient($authorUser->getEmail(), $authorUser->getFullName());
			} else {
				if (Request::getUserVar('importPeerReviews')) {
					$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
					$reviewAssignments = &$reviewAssignmentDao->getReviewAssignmentsByPaperId($trackEditorSubmission->getPaperId(), $trackEditorSubmission->getReviewProgress(), $trackEditorSubmission->getCurrentRound());
					$reviewIndexes = &$reviewAssignmentDao->getReviewIndexesForRound($trackEditorSubmission->getPaperId(), $trackEditorSubmission->getReviewProgress(), $trackEditorSubmission->getCurrentRound());

					$body = '';
					foreach ($reviewAssignments as $reviewAssignment) {
						// If the reviewer has completed the assignment, then import the review.
						if ($reviewAssignment->getDateCompleted() != null && !$reviewAssignment->getCancelled()) {
							// Get the comments associated with this review assignment
							$paperComments = &$paperCommentDao->getPaperComments($trackEditorSubmission->getPaperId(), COMMENT_TYPE_PEER_REVIEW, $reviewAssignment->getReviewId());
							$body .= "------------------------------------------------------\n";
							$body .= Locale::translate('submission.comments.importPeerReviews.reviewerLetter', array('reviewerLetter' => chr(ord('A') + $reviewIndexes[$reviewAssignment->getReviewId()]))) . "\n";
							if (is_array($paperComments)) {
								foreach ($paperComments as $comment) {
									// If the comment is viewable by the author, then add the comment.
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

			$email->displayEditForm(Request::url(null, null, null, 'emailEditorDecisionComment', 'send'), array('paperId' => $trackEditorSubmission->getPaperId()), 'submission/comment/editorDecisionEmail.tpl', array('isAnEditor' => true));

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
			HookRegistry::call('TrackEditorAction::blindCcReviewsToReviewers', array(&$paper, &$reviewAssignments, &$email));
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
		if (HookRegistry::call('TrackEditorAction::viewLayoutComments', array(&$paper))) return;

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
		if (HookRegistry::call('TrackEditorAction::postLayoutComment', array(&$paper, &$emailComment))) return;

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
		
		if (HookRegistry::call('TrackEditorAction::confirmReviewForReviewer', array(&$reviewAssignment, &$reviewer))) return;

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
		
		if (HookRegistry::call('TrackEditorAction::uploadReviewForReviewer', array(&$reviewAssignment, &$reviewer))) return;

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
			if ($track != 'editor' && $track != 'trackEditor') {
				$parent[0] = Request::url(null, null, $track, 'submission', $paperId);
			}
			$breadcrumb[] = $parent;
		}
		return $breadcrumb;
	}
	
}

?>
