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
	 * Changes the secondary track of a paper.
	 * @param $trackEditorSubmission int
	 * @param $trackId int
	 */
	function changeSecondaryTrack($trackEditorSubmission, $trackId) {
		if (!HookRegistry::call('TrackEditorAction::changeSecondaryTrack', array(&$trackEditorSubmission, $trackId))) {
			$trackEditorSubmissionDao = &DAORegistry::getDAO('TrackEditorSubmissionDAO');
			$trackEditorSubmission->setSecondaryTrackId($trackId);
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
			$trackEditorSubmissionDao->updateTrackEditorSubmission($trackEditorSubmission);

			$decisions = TrackEditorSubmission::getEditorDecisionOptions();
			// Add log
			import('paper.log.PaperLog');
			import('paper.log.PaperEventLogEntry');
			PaperLog::logEvent($trackEditorSubmission->getPaperId(), PAPER_LOG_EDITOR_DECISION, LOG_TYPE_EDITOR, $user->getUserId(), 'log.editor.decision', array('editorName' => $user->getFullName(), 'paperId' => $trackEditorSubmission->getPaperId(), 'decision' => Locale::translate($decisions[$decision])));
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
	function completeReview($event, $trackEditorSubmission) {

		$decisions = $trackEditorSubmission->getDecisions(null, null);
		$decision = array_pop(array_pop($decisions));
		
		if ($decision == SUBMISSION_EDITOR_DECISION_DECLINE) {

			$trackEditorSubmission->setStatus(SUBMISSION_STATUS_DECLINED);

		} else {

			if($event->getCollectPapersWithAbstracts() ||
				!$event->getAcceptPapers()) {
			
				// Only one review was necessary; the submission is complete.
				$trackEditorSubmission->setReviewProgress(REVIEW_PROGRESS_COMPLETE);
				$trackEditorSubmission->setStatus(SUBMISSION_STATUS_ACCEPTED);

			} else { // two-stage submission; paper required
		
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
					$trackEditorSubmission->setStatus(SUBMISSION_STATUS_ACCEPTED);

					// TODO: log? notify authors?
					
				}
			}
		
			$trackEditorSubmissionDao =& DAORegistry::getDao('TrackEditorSubmissionDAO');
			$trackEditorSubmissionDao->updateTrackEditorSubmission($trackEditorSubmission);
		}

		// If the review is complete, regardless of the review model, we need
		// to create a galley object with the submission and/or abstract.
		
		if($trackEditorSubmission->getStatus() == SUBMISSION_STATUS_ACCEPTED &&
				$trackEditorSubmission->getReviewProgress() == REVIEW_PROGRESS_COMPLETE) {

			// The paper has been accepted... create a PublishedPaper object for it.

			// This code is from OJS2's EditorHandler.inc.php. The remarks there about
			// bug #2111 apply here as well.
			
			$paperId = $trackEditorSubmission->getPaperId();
			
			$publishedPaperDao = &DAORegistry::getDao('PublishedPaperDAO');
			$publishedPaper =& $publishedPaperDao->getPublishedPaperByPaperId($paperId);
			if (!$publishedPaper) {
				$publishedPaper = &new PublishedPaper();
			}
			$publishedPaper->setPaperId($paperId);
			$publishedPaper->setEventId($event->getEventId());
			$publishedPaper->setDatePublished(Core::getCurrentDate());
			$publishedPaper->setSeq(0);
			$publishedPaper->setViews(0);
			$publishedPaper->setAccessStatus(0);

			// See above note on bug #2111.
			if ($publishedPaper->getPubId()) {
				$publishedPaperDao->updatePublishedPaper($publishedPaper);
			} else {
				$publishedPaperDao->insertPublishedPaper($publishedPaper);
			}
			
			if ($trackEditorSubmission->getEditorFileId()) {

				// If a submission file exists, create a galley for it.
				$paperGalleyDao = &DAORegistry::getDao('PaperGalleyDAO');
				$paperFileDao = &DAORegistry::getDao('PaperFileDAO');

				// Create new galley for the submission
				$fileId = $trackEditorSubmission->getEditorFileId();
				$file = $paperFileDao->getPaperFile($fileId);
			
				$galley = &new PaperGalley();

				$galley->setPaperId($trackEditorSubmission->getPaperId());
				$galley->setFileId($fileId);
				$galley->setLabel($file->getFileType());

				// Insert new galley
				$galleyId = $paperGalleyDao->insertGalley($galley);
				$galley->setGalleyId($galleyId);
			}
		}

		// Commit the paper changes
		$trackEditorSubmission->stampStatusModified();
		$paperDao = &DAORegistry::getDao('PaperDAO');
		$paperDao->updatePaper($trackEditorSubmission);

		// TODO log!

	}
	
	/**
	 * Assigns a reviewer to a submission.
	 * @param $trackEditorSubmission object
	 * @param $reviewerId int
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

			$event = &Request::getConference();
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
						$keyLifetime = ($conference->getSetting('numWeeksPerReview') + 4) * 7;

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
						$reviewDueDate = date('Y-m-d', strtotime('+2 week'));
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
					PaperLog::logEvent($trackEditorSubmission->getPaperId(), PAPER_LOG_REVIEW_CANCEL, LOG_TYPE_REVIEW, $reviewAssignment->getReviewId(), 'log.review.reviewCancelled', array('reviewerName' => $reviewer->getFullName(), 'paperId' => $trackEditorSubmission->getPaperId(), 'round' => $reviewAssignment->getCurrentRound()));
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
				$keyLifetime = ($conference->getSetting('numWeeksPerReview') + 4) * 7;
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
	 * Acknowledges that a review is now underway.
	 * @param $trackEditorSubmission object
	 * @param $reviewId int
	 * @return boolean true iff no error was encountered
	 */
	function acknowledgeReviewerUnderway($trackEditorSubmission, $reviewId, $send = false) {
		$trackEditorSubmissionDao = &DAORegistry::getDAO('TrackEditorSubmissionDAO');
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
			
		$conference = &Request::getConference();
		$user = &Request::getUser();
		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);

		import('mail.PaperMailTemplate');
		$email = &new PaperMailTemplate($trackEditorSubmission, 'REVIEW_CONFIRM_ACK');

		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			HookRegistry::call('TrackEditorAction::acknowledgeReviewerUnderway', array(&$trackEditorSubmission, &$reviewAssignment, &$email));
			$email->setAssoc(PAPER_EMAIL_REVIEW_CONFIRM_ACK, PAPER_EMAIL_TYPE_REVIEW, $reviewId);

			$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());

			if ($email->isEnabled()) $email->send();
			return true;
		} elseif ($reviewAssignment->getPaperId() == $trackEditorSubmission->getPaperId()) {
			$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
		
			if (!Request::getUserVar('continued')) {
				if (!isset($reviewer)) return true;
				$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());

				$submissionUrl = Request::url(null, null, 'reviewer', 'submission', $reviewId);
				
				$paramArray = array(
					'reviewerName' => $reviewer->getFullName(),
					'reviewDueDate' => date('Y-m-d', strtotime($reviewAssignment->getDateDue())),
					'editorialContactSignature' => $user->getContactSignature()
				);
				$email->assignParams($paramArray);
	
			}
			$email->displayEditForm(
				Request::url(null, null, null, 'acknowledgeReviewerUnderway', 'send'),
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
	 * Notifies an author about the editor review.
	 * @param $trackEditorSubmission object
	 * FIXME: Still need to add Reviewer Comments
	 */
	function notifyAuthor($trackEditorSubmission, $send = false) {
		$trackEditorSubmissionDao = &DAORegistry::getDAO('TrackEditorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		
		$conference = &Request::getConference();
		$user = &Request::getUser();
		
		import('mail.PaperMailTemplate');
		$email = &new PaperMailTemplate($trackEditorSubmission, 'EDITOR_REVIEW');

		$author = &$userDao->getUser($trackEditorSubmission->getUserId());
		if (!isset($author)) return true;

		if ($send && !$email->hasErrors()) {
			HookRegistry::call('TrackEditorAction::notifyAuthor', array(&$trackEditorSubmission, &$author, &$email));
			$email->setAssoc(PAPER_EMAIL_EDITOR_NOTIFY_AUTHOR, PAPER_EMAIL_TYPE_EDITOR, $trackEditorSubmission->getPaperId());
			$email->send();
			
		} else {
			if (!Request::getUserVar('continued')) {
				$email->addRecipient($author->getEmail(), $author->getFullName());
				$paramArray = array(
					'authorName' => $author->getFullName(),
					'authorUsername' => $author->getUsername(),
					'authorPassword' => $author->getPassword(),
					'editorialContactSignature' => $user->getContactSignature(),
					'submissionUrl' => Request::url(null, null, 'author', 'submissionEditing', $trackEditorSubmission->getPaperId())
				);
				$email->assignParams($paramArray);
			}
			$email->displayEditForm(Request::url(null, null, null, 'notifyAuthor'), array('paperId' => $trackEditorSubmission->getPaperId()));
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

		$trackEditorSubmission->stampStatusModified();
		
		$trackEditorSubmissionDao->updateTrackEditorSubmission($trackEditorSubmission);
	
		// Add log
		import('paper.log.PaperLog');
		import('paper.log.PaperEventLogEntry');
		PaperLog::logEvent($trackEditorSubmission->getPaperId(), PAPER_LOG_EDITOR_RESTORE, LOG_TYPE_EDITOR, $trackEditorSubmission->getPaperId(), 'log.editor.restored', array('paperId' => $trackEditorSubmission->getPaperId()));
	}
	
	/**
	 * Changes the track.
	 * @param $submission object
	 * @param $trackId int
	 */
	function updateTrack($submission, $trackId) {
		if (HookRegistry::call('TrackEditorAction::updateTrack', array(&$submission, &$trackId))) return;

		$submissionDao = &DAORegistry::getDAO('TrackEditorSubmissionDAO');
		$submission->setTrackId($trackId); // FIXME validate this ID?
		$submissionDao->updateTrackEditorSubmission($submission);
	}
	
	/**
	 * Changes the secondary track.
	 * @param $submission object
	 * @param $trackId int
	 */
	function updateSecondaryTrack($submission, $trackId) {
		if (HookRegistry::call('TrackEditorAction::updateSecondaryTrack', array(&$submission, &$trackId))) return;

		$submissionDao = &DAORegistry::getDAO('TrackEditorSubmissionDAO');
		$submission->setSecondaryTrackId($trackId); // FIXME validate this ID?
		$submissionDao->updateTrackEditorSubmission($submission);
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
			parent::setupTemplate(true);
			$commentForm->display();
		}
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
			parent::setupTemplate(true);
			$commentForm->display();
		}
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

		$user = &Request::getUser();
		import('mail.PaperMailTemplate');
		$email = &new PaperMailTemplate($trackEditorSubmission);
	
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
					$email->setBody($body);
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
	 * Accepts the review assignment on behalf of its reviewer.
	 * @param $reviewId int
	 */
	function acceptReviewForReviewer($reviewId) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
                $user = &Request::getUser();

		$reviewAssignment = &$reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId(), true);
		
		if (HookRegistry::call('TrackEditorAction::acceptReviewForReviewer', array(&$reviewAssignment, &$reviewer))) return;

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
			$entry->setLogMessage('log.review.reviewFileByProxy', array('reviewerName' => $reviewer->getFullName(), 'paperId' => $reviewAssignment->getPaperId(), 'round' => $reviewAssignment->getCurrentRound(), 'userName' => $user->getFullName()));
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
