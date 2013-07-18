<?php

/**
 * @file TrackDirectorAction.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TrackDirectorAction
 * @ingroup submission
 *
 * @brief TrackDirectorAction class.
 *
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
	 * @param $trackDirectorSubmission object
	 * @param $trackId int
	 */
	function changeTrack(&$trackDirectorSubmission, $trackId) {
		if (!HookRegistry::call('TrackDirectorAction::changeTrack', array(&$trackDirectorSubmission, $trackId))) {
			$trackDirectorSubmissionDao =& DAORegistry::getDAO('TrackDirectorSubmissionDAO');
			$trackDirectorSubmission->setTrackId((int) $trackId);
			$trackDirectorSubmissionDao->updateTrackDirectorSubmission($trackDirectorSubmission);
		}
	}

	/**
	 * Changes the session type.
	 * @param $trackDirectorSubmission object
	 * @param $sessionType int
	 */
	function changeSessionType(&$trackDirectorSubmission, $sessionType) {
		if (!HookRegistry::call('TrackDirectorAction::changeSessionType', array(&$trackDirectorSubmission, $sessionType))) {
			$trackDirectorSubmissionDao =& DAORegistry::getDAO('TrackDirectorSubmissionDAO');
			$trackDirectorSubmission->setData('sessionType', (int) $sessionType);
			$trackDirectorSubmissionDao->updateTrackDirectorSubmission($trackDirectorSubmission);
		}
	}

	/**
	 * Records a director's submission decision.
	 * @param $trackDirectorSubmission object
	 * @param $decision int
	 * @param $stage int
	 */
	function recordDecision($trackDirectorSubmission, $decision, $stage) {
		$editAssignments =& $trackDirectorSubmission->getEditAssignments();
		if (empty($editAssignments)) return;

		$trackDirectorSubmissionDao =& DAORegistry::getDAO('TrackDirectorSubmissionDAO');
		$user =& Request::getUser();
		$directorDecision = array(
			'editDecisionId' => null,
			'directorId' => $user->getId(),
			'decision' => $decision,
			'dateDecided' => date(Core::getCurrentDate())
		);

		if (!HookRegistry::call('TrackDirectorAction::recordDecision', array(&$trackDirectorSubmission, $directorDecision))) {
			if ($decision == SUBMISSION_DIRECTOR_DECISION_DECLINE) {
				$trackDirectorSubmission->setStatus(STATUS_DECLINED);
				$trackDirectorSubmission->stampStatusModified();
			} else {
				$trackDirectorSubmission->setStatus(STATUS_QUEUED);		
				$trackDirectorSubmission->stampStatusModified();
			}

			$trackDirectorSubmission->addDecision($directorDecision, $stage);
			$decisions = TrackDirectorSubmission::getDirectorDecisionOptions();
			// Add log
			import('paper.log.PaperLog');
			import('paper.log.PaperEventLogEntry');
			AppLocale::requireComponents(array(LOCALE_COMPONENT_APPLICATION_COMMON, LOCALE_COMPONENT_OCS_DIRECTOR));
			PaperLog::logEvent(
				$trackDirectorSubmission->getPaperId(),
				PAPER_LOG_DIRECTOR_DECISION,
				LOG_TYPE_DIRECTOR,
				$user->getId(),
				'log.director.decision',
				array(
					'directorName' => $user->getFullName(),
					'paperId' => $trackDirectorSubmission->getPaperId(),
					'decision' => __($decisions[$decision]),
					'round' => ($stage == REVIEW_STAGE_ABSTRACT?'submission.abstractReview':'submission.paperReview')
				)
			);
		}

		if($decision == SUBMISSION_DIRECTOR_DECISION_ACCEPT || $decision == SUBMISSION_DIRECTOR_DECISION_INVITE) {
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
	 * If the submission requires completion, it's sent back to the author.
	 * If not, review is complete, and the paper can be released.
	 * @param $schedConf object
	 * @param $trackDirectorSubmission object
	 * @param $decision int
	 */
	function completeReview($trackDirectorSubmission) {
		$schedConf =& Request::getSchedConf();

		if($trackDirectorSubmission->getReviewMode() == REVIEW_MODE_BOTH_SEQUENTIAL) {
			// two-stage submission; paper required
			// The submission is incomplete, and needs the author to submit
			// more materials (potentially for another stage of reviews)

			if($trackDirectorSubmission->getCurrentStage() == REVIEW_STAGE_ABSTRACT) {

				// We've just completed reviewing the abstract. Prepare for presentation
				// review process.
				$trackDirectorSubmission->setCurrentStage(REVIEW_STAGE_PRESENTATION);

				// The paper itself needs to be collected. Flag it so the author
				// may complete it.
				$trackDirectorSubmission->setSubmissionProgress(2);

				// TODO: notify the author the submission must be completed.
				// Q: should the director be given this option explicitly?

				// Now, reassign all reviewers that submitted a review for the last
				// stage of reviews.
				foreach ($trackDirectorSubmission->getReviewAssignments(REVIEW_STAGE_ABSTRACT) as $reviewAssignment) {
					if ($reviewAssignment->getRecommendation() !== null && $reviewAssignment->getRecommendation() !== '') {
						// This reviewer submitted a review; reassign them
						TrackDirectorAction::addReviewer($trackDirectorSubmission, $reviewAssignment->getReviewerId(), REVIEW_STAGE_PRESENTATION);
					}
				}
			}
		}

		$trackDirectorSubmissionDao =& DAORegistry::getDao('TrackDirectorSubmissionDAO');
		$trackDirectorSubmissionDao->updateTrackDirectorSubmission($trackDirectorSubmission);
		$trackDirectorSubmission->stampStatusModified();

		// Commit the paper changes
		$paperDao =& DAORegistry::getDao('PaperDAO');
		$paperDao->updatePaper($trackDirectorSubmission);
	}

	/**
	 * Assigns a reviewer to a submission.
	 * @param $trackDirectorSubmission object
	 * @param $reviewerId int
	 * @param $stage int
	 */
	function addReviewer($trackDirectorSubmission, $reviewerId, $stage) {
		$trackDirectorSubmissionDao =& DAORegistry::getDAO('TrackDirectorSubmissionDAO');
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$user =& Request::getUser();

		$reviewer =& $userDao->getUser($reviewerId);

		// Check to see if the requested reviewer is not already
		// assigned to review this paper.
		if ($stage == null) {
			$stage = $trackDirectorSubmission->getCurrentStage();
		}

		$assigned = $trackDirectorSubmissionDao->reviewerExists($trackDirectorSubmission->getPaperId(), $reviewerId, $stage);

		// Only add the reviewer if he has not already
		// been assigned to review this paper.
		if (!$assigned && isset($reviewer) && !HookRegistry::call('TrackDirectorAction::addReviewer', array(&$trackDirectorSubmission, $reviewerId))) {
			$reviewAssignment = new ReviewAssignment();
			$reviewAssignment->setReviewerId($reviewerId);
			$reviewAssignment->setDateAssigned(Core::getCurrentDate());
			$reviewAssignment->setStage($stage);

			// Assign review form automatically if needed
			$schedConfId = $trackDirectorSubmission->getSchedConfId();
			$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
			$schedConf =& $schedConfDao->getSchedConf($schedConfId);
			$conferenceId = $schedConf->getConferenceId();
			$trackDao =& DAORegistry::getDAO('TrackDAO');
			$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');

			$trackId = $trackDirectorSubmission->getTrackId();
			$track =& $trackDao->getTrack($trackId, $conferenceId);
			if ($track && ($reviewFormId = (int) $track->getReviewFormId())) {
				if ($reviewFormDao->reviewFormExists($reviewFormId, ASSOC_TYPE_CONFERENCE, $conferenceId)) {
					$reviewAssignment->setReviewFormId($reviewFormId);
				}
			}

			$trackDirectorSubmission->addReviewAssignment($reviewAssignment);
			$trackDirectorSubmissionDao->updateTrackDirectorSubmission($trackDirectorSubmission);

			$reviewAssignment = $reviewAssignmentDao->getReviewAssignment($trackDirectorSubmission->getPaperId(), $reviewerId, $stage);

			$schedConf =& Request::getSchedConf();
			if ($schedConf->getSetting('reviewDeadlineType') != null) {
				$dueDateSet = true;
				if ($schedConf->getSetting('reviewDeadlineType') == REVIEW_DEADLINE_TYPE_ABSOLUTE) {
					$reviewDeadlineDate = $schedConf->getSetting('numWeeksPerReviewAbsolute');
					$reviewDueDate = strftime(Config::getVar('general', 'date_format_short'), $reviewDeadlineDate);
					TrackDirectorAction::setDueDate($trackDirectorSubmission->getPaperId(), $reviewAssignment->getId(), $reviewDueDate, null, false);
				} elseif ($schedConf->getSetting('reviewDeadlineType') == REVIEW_DEADLINE_TYPE_RELATIVE) {
					TrackDirectorAction::setDueDate($trackDirectorSubmission->getPaperId(), $reviewAssignment->getId(), null, $schedConf->getSetting('numWeeksPerReviewRelative'), false);
				} else {
					$dueDateSet = false;
				}
				if ($dueDateSet) {
					$reviewAssignment = $reviewAssignmentDao->getReviewAssignment($trackDirectorSubmission->getPaperId(), $reviewerId, $stage);
					$trackDirectorSubmission->updateReviewAssignment($reviewAssignment);
					$trackDirectorSubmissionDao->updateTrackDirectorSubmission($trackDirectorSubmission);
				}
			}

			// Add log
			import('paper.log.PaperLog');
			import('paper.log.PaperEventLogEntry');
			PaperLog::logEvent($trackDirectorSubmission->getPaperId(), PAPER_LOG_REVIEW_ASSIGN, LOG_TYPE_REVIEW, $reviewAssignment->getId(), 'log.review.reviewerAssigned', array('reviewerName' => $reviewer->getFullName(), 'paperId' => $trackDirectorSubmission->getPaperId(), 'stage' => $stage));
		}
	}

	/**
	 * Clears a review assignment from a submission.
	 * @param $trackDirectorSubmission object
	 * @param $reviewId int
	 */
	function clearReview($trackDirectorSubmission, $reviewId) {
		$trackDirectorSubmissionDao =& DAORegistry::getDAO('TrackDirectorSubmissionDAO');
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$user =& Request::getUser();

		$reviewAssignment =& $reviewAssignmentDao->getReviewAssignmentById($reviewId);

		if (isset($reviewAssignment) && $reviewAssignment->getPaperId() == $trackDirectorSubmission->getPaperId() && !HookRegistry::call('TrackDirectorAction::clearReview', array(&$trackDirectorSubmission, $reviewAssignment))) {
			$reviewer =& $userDao->getUser($reviewAssignment->getReviewerId());
			if (!isset($reviewer)) return false;
			$trackDirectorSubmission->removeReviewAssignment($reviewId);
			$trackDirectorSubmissionDao->updateTrackDirectorSubmission($trackDirectorSubmission);

			// Add log
			import('paper.log.PaperLog');
			import('paper.log.PaperEventLogEntry');
			PaperLog::logEvent($trackDirectorSubmission->getPaperId(), PAPER_LOG_REVIEW_CLEAR, LOG_TYPE_REVIEW, $reviewAssignment->getId(), 'log.review.reviewCleared', array('reviewerName' => $reviewer->getFullName(), 'paperId' => $trackDirectorSubmission->getPaperId(), 'stage' => $reviewAssignment->getStage()));
		}		
	}

	/**
	 * Notifies a reviewer about a review assignment.
	 * @param $trackDirectorSubmission object
	 * @param $reviewId int
	 * @return boolean true iff ready for redirect
	 */
	function notifyReviewer($trackDirectorSubmission, $reviewId, $send = false) {
		$trackDirectorSubmissionDao =& DAORegistry::getDAO('TrackDirectorSubmissionDAO');
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');

		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$user =& Request::getUser();

		$reviewAssignment =& $reviewAssignmentDao->getReviewAssignmentById($reviewId);

		$reviewerAccessKeysEnabled = $schedConf->getSetting('reviewerAccessKeysEnabled');

		// If we're using access keys, disable the address fields
		// for this message. (Prevents security issue: track director
		// could CC or BCC someone else, or change the reviewer address,
		// in order to get the access key.)
		$preventAddressChanges = $reviewerAccessKeysEnabled;

		import('mail.PaperMailTemplate');

		$email = new PaperMailTemplate($trackDirectorSubmission, $reviewerAccessKeysEnabled?'REVIEW_REQUEST_ONECLICK':'REVIEW_REQUEST');

		if ($preventAddressChanges) {
			$email->setAddressFieldsEnabled(false);
		}

		if ($reviewAssignment->getPaperId() == $trackDirectorSubmission->getPaperId()) {
			$reviewer =& $userDao->getUser($reviewAssignment->getReviewerId());
			if (!isset($reviewer)) return true;

			if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
				HookRegistry::call('TrackDirectorAction::notifyReviewer', array(&$trackDirectorSubmission, &$reviewAssignment, &$email));
				if ($email->isEnabled()) {
					$email->setAssoc(PAPER_EMAIL_REVIEW_NOTIFY_REVIEWER, PAPER_EMAIL_TYPE_REVIEW, $reviewId);
					if ($reviewerAccessKeysEnabled) {
						import('security.AccessKeyManager');
						import('pages.reviewer.ReviewerHandler');
						$accessKeyManager = new AccessKeyManager();

						// Key lifetime is the typical review period plus four weeks
						if ($schedConf->getSetting('reviewDeadlineType') == REVIEW_DEADLINE_TYPE_ABSOLUTE) {
							// Get number of days from now until review deadline date
							$reviewDeadlineDate = $schedConf->getSetting('numWeeksPerReviewAbsolute');
							$daysDiff = ($reviewDeadlineDate - strtotime(date("Y-m-d"))) / (60 * 60 * 24);
							$keyLifetime = (round($daysDiff / 7) + 4) * 7;
						} elseif ($schedConf->getSetting('reviewDeadlineType') == REVIEW_DEADLINE_TYPE_RELATIVE) {
							$keyLifetime = ((int) $schedConf->getSetting('numWeeksPerReviewRelative') + 4) * 7;
						}

						$email->addPrivateParam('ACCESS_KEY', $accessKeyManager->createKey('ReviewerContext', $reviewer->getId(), $reviewId, $keyLifetime));
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
					$weekLaterDate = strftime(Config::getVar('general', 'date_format_short'), strtotime('+1 week'));

					if ($reviewAssignment->getDateDue() != null) {
						$reviewDueDate = strftime(Config::getVar('general', 'date_format_short'), strtotime($reviewAssignment->getDateDue()));
					} else {
						if ($schedConf->getSetting('reviewDeadlineType') == REVIEW_DEADLINE_TYPE_ABSOLUTE) {
							$reviewDeadlineDate = $schedConf->getSetting('numWeeksPerReviewAbsolute');
							$reviewDueDate = strftime(Config::getVar('general', 'date_format_short'), $reviewDeadlineDate);
						} elseif ($schedConf->getSetting('reviewDeadlineType') == REVIEW_DEADLINE_TYPE_RELATIVE) {
							$numWeeks = max((int) $schedConf->getSetting('numWeeksPerReviewRelative'), 2);
							$reviewDueDate = strftime(Config::getVar('general', 'date_format_short'), strtotime('+' . $numWeeks . ' week'));
						}
						
					}

					$submissionUrl = Request::url(null, null, 'reviewer', 'submission', $reviewId, $reviewerAccessKeysEnabled?array('key' => 'ACCESS_KEY'):array());

					$paramArray = array(
						'reviewerName' => $reviewer->getFullName(),
						'weekLaterDate' => $weekLaterDate,
						'reviewDueDate' => $reviewDueDate,
						'reviewerUsername' => $reviewer->getUsername(),
						'reviewerPassword' => $reviewer->getPassword(),
						'editorialContactSignature' => $user->getContactSignature(),
						'reviewGuidelines' => $schedConf->getLocalizedSetting('reviewGuidelines'),
						'submissionReviewUrl' => $submissionUrl,
						'passwordResetUrl' => Request::url(null, null, 'login', 'resetPassword', $reviewer->getUsername(), array('confirm' => Validation::generatePasswordResetHash($reviewer->getId())))
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
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$trackDirectorSubmissionDao =& DAORegistry::getDAO('TrackDirectorSubmissionDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');

		$conference =& Request::getConference();
		$user =& Request::getUser();

		$reviewAssignment =& $reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$reviewer =& $userDao->getUser($reviewAssignment->getReviewerId());
		if (!isset($reviewer)) return true;

		if ($reviewAssignment->getPaperId() == $trackDirectorSubmission->getPaperId()) {
			// Only cancel the review if it is currently not cancelled but has previously
			// been initiated, and has not been completed.
			if ($reviewAssignment->getDateNotified() != null && !$reviewAssignment->getCancelled() && ($reviewAssignment->getDateCompleted() == null || $reviewAssignment->getDeclined())) {
				import('mail.PaperMailTemplate');
				$email = new PaperMailTemplate($trackDirectorSubmission, 'REVIEW_CANCEL');

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
					PaperLog::logEvent($trackDirectorSubmission->getPaperId(), PAPER_LOG_REVIEW_CANCEL, LOG_TYPE_REVIEW, $reviewAssignment->getId(), 'log.review.reviewCancelled', array('reviewerName' => $reviewer->getFullName(), 'paperId' => $trackDirectorSubmission->getPaperId(), 'stage' => $reviewAssignment->getStage()));
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
		$trackDirectorSubmissionDao =& DAORegistry::getDAO('TrackDirectorSubmissionDAO');
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');

		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$user =& Request::getUser();
		$reviewAssignment =& $reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$reviewerAccessKeysEnabled = $schedConf->getSetting('reviewerAccessKeysEnabled');

		// If we're using access keys, disable the address fields
		// for this message. (Prevents security issue: track director
		// could CC or BCC someone else, or change the reviewer address,
		// in order to get the access key.)
		$preventAddressChanges = $reviewerAccessKeysEnabled;

		import('mail.PaperMailTemplate');
		$email = new PaperMailTemplate($trackDirectorSubmission, $reviewerAccessKeysEnabled?'REVIEW_REMIND_ONECLICK':'REVIEW_REMIND');

		if ($preventAddressChanges) {
			$email->setAddressFieldsEnabled(false);
		}

		if ($send && !$email->hasErrors()) {
			HookRegistry::call('TrackDirectorAction::remindReviewer', array(&$trackDirectorSubmission, &$reviewAssignment, &$email));
			$email->setAssoc(PAPER_EMAIL_REVIEW_REMIND, PAPER_EMAIL_TYPE_REVIEW, $reviewId);

			$reviewer =& $userDao->getUser($reviewAssignment->getReviewerId());

			if ($reviewerAccessKeysEnabled) {
				import('security.AccessKeyManager');
				import('pages.reviewer.ReviewerHandler');
				$accessKeyManager = new AccessKeyManager();

				// Key lifetime is the typical review period plus four weeks
				if ($schedConf->getSetting('reviewDeadlineType') == REVIEW_DEADLINE_TYPE_ABSOLUTE) {
					// Get number of days from now until review deadline date
					$reviewDeadlineDate = $schedConf->getSetting('numWeeksPerReviewAbsolute');
					$daysDiff = ($reviewDeadlineDate - strtotime(date("Y-m-d"))) / (60 * 60 * 24);
					$keyLifetime = (round($daysDiff / 7) + 4) * 7;
				} elseif ($schedConf->getSetting('reviewDeadlineType') == REVIEW_DEADLINE_TYPE_RELATIVE) {
					$keyLifetime = ((int) $schedConf->getSetting('numWeeksPerReviewRelative') + 4) * 7;
				}
				$email->addPrivateParam('ACCESS_KEY', $accessKeyManager->createKey('ReviewerContext', $reviewer->getId(), $reviewId, $keyLifetime));
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
			$reviewer =& $userDao->getUser($reviewAssignment->getReviewerId());

			if (!Request::getUserVar('continued')) {
				if (!isset($reviewer)) return true;
				$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());

				$submissionUrl = Request::url(null, null, 'reviewer', 'submission', $reviewId, $reviewerAccessKeysEnabled?array('key' => 'ACCESS_KEY'):array());

				// Format the review due date
				$reviewDueDate = strtotime($reviewAssignment->getDateDue());
				$dateFormatShort = Config::getVar('general', 'date_format_short');
				if ($reviewDueDate === -1 || $reviewDueDate === false) {
					// Use something human-readable if unspecified.
					$reviewDueDate = '_____';
				} else {
					$reviewDueDate = strftime($dateFormatShort, $reviewDueDate);
				}

				$paramArray = array(
					'reviewerName' => $reviewer->getFullName(),
					'reviewerUsername' => $reviewer->getUsername(),
					'reviewerPassword' => $reviewer->getPassword(),
					'reviewDueDate' => $reviewDueDate,
					'editorialContactSignature' => $user->getContactSignature(),
					'passwordResetUrl' => Request::url(null, null, 'login', 'resetPassword', $reviewer->getUsername(), array('confirm' => Validation::generatePasswordResetHash($reviewer->getId()))),
					'submissionReviewUrl' => $submissionUrl
				);
				$email->assignParams($paramArray);

			}
			$email->displayEditForm(
				Request::url(null, null, null, 'remindReviewer', 'send'),
				array(
					'reviewerId' => $reviewer->getId(),
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
		$trackDirectorSubmissionDao =& DAORegistry::getDAO('TrackDirectorSubmissionDAO');
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');

		$conference =& Request::getConference();
		$user =& Request::getUser();

		$reviewAssignment =& $reviewAssignmentDao->getReviewAssignmentById($reviewId);

		import('mail.PaperMailTemplate');
		$email = new PaperMailTemplate($trackDirectorSubmission, 'REVIEW_ACK');

		if ($reviewAssignment->getPaperId() == $trackDirectorSubmission->getPaperId()) {
			$reviewer =& $userDao->getUser($reviewAssignment->getReviewerId());
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
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$user =& Request::getUser();

		$reviewAssignment =& $reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$reviewer =& $userDao->getUser($reviewAssignment->getReviewerId());
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
			PaperLog::logEvent($paperId, PAPER_LOG_REVIEW_RATE, LOG_TYPE_REVIEW, $reviewAssignment->getId(), 'log.review.reviewerRated', array('reviewerName' => $reviewer->getFullName(), 'paperId' => $paperId, 'stage' => $reviewAssignment->getStage()));
		}
	}

	/**
	 * Makes a reviewer's annotated version of a paper available to the author.
	 * @param $paperId int
	 * @param $reviewId int
	 * @param $viewable boolean
	 */
	function makeReviewerFileViewable($paperId, $reviewId, $fileId, $revision, $viewable = false) {
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$paperFileDao =& DAORegistry::getDAO('PaperFileDAO');

		$reviewAssignment =& $reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$paperFile =& $paperFileDao->getPaperFile($fileId, $revision);

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
	 * @param $logChange boolean
	 */
	function setDueDate($paperId, $reviewId, $dueDate = null, $numWeeks = null, $logChange = true) {
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$user =& Request::getUser();

		$reviewAssignment =& $reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$reviewer =& $userDao->getUser($reviewAssignment->getReviewerId());
		if (!isset($reviewer)) return false;

		if ($reviewAssignment->getPaperId() == $paperId && !HookRegistry::call('TrackDirectorAction::setDueDate', array(&$reviewAssignment, &$reviewer, &$dueDate, &$numWeeks))) {
			$today = getDate();
			$todayTimestamp = mktime(0, 0, 0, $today['mon'], $today['mday'], $today['year']);
			if ($dueDate != null) {
				$dueDateParts = explode('-', $dueDate);

				// Ensure that the specified due date is today or after today's date.
				if ($todayTimestamp <= strtotime($dueDate)) {
					$reviewAssignment->setDateDue(date('Y-m-d H:i:s', mktime(0, 0, 0, $dueDateParts[1], $dueDateParts[2], $dueDateParts[0])));
				} else {
					$reviewAssignment->setDateDue(date('Y-m-d H:i:s', $todayTimestamp));
				}
			} else {
				// Add the equivilant of $numWeeks weeks, measured in seconds, to $todaysTimestamp.
				$newDueDateTimestamp = $todayTimestamp + ($numWeeks * 7 * 24 * 60 * 60);

				$reviewAssignment->setDateDue(date('Y-m-d H:i:s', $newDueDateTimestamp));
			}

			$reviewAssignment->stampModified();
			$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);

			if ($logChange) {
				// Add log
				import('paper.log.PaperLog');
				import('paper.log.PaperEventLogEntry');
				PaperLog::logEvent(
					$paperId,
					PAPER_LOG_REVIEW_SET_DUE_DATE,
					LOG_TYPE_REVIEW,
					$reviewAssignment->getId(),
					'log.review.reviewDueDateSet',
					array(
						'reviewerName' => $reviewer->getFullName(),
						'dueDate' => strftime(Config::getVar('general', 'date_format_short'), strtotime($reviewAssignment->getDateDue())),
						'paperId' => $paperId,
						'stage' => $reviewAssignment->getStage()
					)
				);
			} // $logChange
		}
	}

	/**
	 * Notifies an author that a submission was unsuitable.
	 * @param $trackDirectorSubmission object
	 * @return boolean true iff ready for redirect
	 */
	function unsuitableSubmission($trackDirectorSubmission, $send = false) {
		$trackDirectorSubmissionDao =& DAORegistry::getDAO('TrackDirectorSubmissionDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');

		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$user =& Request::getUser();

		$author =& $userDao->getUser($trackDirectorSubmission->getUserId());
		if (!isset($author)) return true;

		import('mail.PaperMailTemplate');
		$email = new PaperMailTemplate($trackDirectorSubmission, 'SUBMISSION_UNSUITABLE');

		if (!$email->isEnabled() || ($send && !$email->hasErrors())) {
			HookRegistry::call('TrackDirectorAction::unsuitableSubmission', array(&$trackDirectorSubmission, &$author, &$email));
			if ($email->isEnabled()) {
				$email->setAssoc(PAPER_EMAIL_DIRECTOR_NOTIFY_AUTHOR_UNSUITABLE, PAPER_EMAIL_TYPE_DIRECTOR, $user->getId());
				$email->send();
			}
			TrackDirectorAction::archiveSubmission($trackDirectorSubmission);
			return true;
		} else {
			if (!Request::getUserVar('continued')) {
				$paramArray = array(
					'editorialContactSignature' => $user->getContactSignature(),
					'authorName' => $author->getFullName(),
					'locationCity' => $schedConf->getSetting('locationCity')
				);
				$email->assignParams($paramArray);
				$email->addRecipient($author->getEmail(), $author->getFullName());
			}
			$email->displayEditForm(Request::url(null, null, null, 'unsuitableSubmission'), array('paperId' => $trackDirectorSubmission->getPaperId()));
			return false;
		}
	}

	/**
	 * Sets the reviewer recommendation for a review assignment.
	 * Also concatenates the reviewer and director comments from Peer Review and adds them to Director Review.
	 * @param $paperId int
	 * @param $reviewId int
	 * @param $recommendation int
	 */
	function setReviewerRecommendation($paperId, $reviewId, $recommendation, $acceptOption) {
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$user =& Request::getUser();

		$reviewAssignment =& $reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$reviewer =& $userDao->getUser($reviewAssignment->getReviewerId(), true);

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
			PaperLog::logEvent($paperId, PAPER_LOG_REVIEW_RECOMMENDATION_BY_PROXY, LOG_TYPE_REVIEW, $reviewAssignment->getId(), 'log.review.reviewRecommendationSetByProxy', array('directorName' => $user->getFullName(), 'reviewerName' => $reviewer->getFullName(), 'paperId' => $paperId, 'stage' => $reviewAssignment->getStage()));
		}
	}	 

	/**
	 * Clear a review form
	 * @param $trackDirectorSubmission object
	 * @param $reviewId int
	 */
	function clearReviewForm($trackDirectorSubmission, $reviewId) {
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignment =& $reviewAssignmentDao->getReviewAssignmentById($reviewId);

		if (HookRegistry::call('TrackDirectorAction::clearReviewForm', array(&$trackDirectorSubmission, &$reviewAssignment, &$reviewId))) return $reviewId;

		if (isset($reviewAssignment) && $reviewAssignment->getPaperId() == $trackDirectorSubmission->getPaperId()) {
			$reviewFormResponseDao =& DAORegistry::getDAO('ReviewFormResponseDAO');
			$responses = $reviewFormResponseDao->getReviewReviewFormResponseValues($reviewId);
			if (!empty($responses)) {
				$reviewFormResponseDao->deleteByReviewId($reviewId);
			}
			$reviewAssignment->setReviewFormId(null);
			$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
		}
	}

	/**
	 * Assigns a review form to a review.
	 * @param $trackDirectorSubmission object
	 * @param $reviewId int
	 * @param $reviewFormId int
	 */
	function addReviewForm($trackDirectorSubmission, $reviewId, $reviewFormId) {
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignment =& $reviewAssignmentDao->getReviewAssignmentById($reviewId);

		if (HookRegistry::call('TrackDirectorAction::addReviewForm', array(&$trackDirectorSubmission, &$reviewAssignment, &$reviewId, &$reviewFormId))) return $reviewFormId;

		if (isset($reviewAssignment) && $reviewAssignment->getPaperId() == $trackDirectorSubmission->getPaperId()) {
			// Only add the review form if it has not already
			// been assigned to the review.
			if ($reviewAssignment->getReviewFormId() != $reviewFormId) {
				$reviewFormResponseDao =& DAORegistry::getDAO('ReviewFormResponseDAO');
				$responses = $reviewFormResponseDao->getReviewReviewFormResponseValues($reviewId);
				if (!empty($responses)) {
					$reviewFormResponseDao->deleteByReviewId($reviewId);
				}
				$reviewAssignment->setReviewFormId($reviewFormId);
				$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);
			}
		}
	}

	/**
	 * View review form response.
	 * @param $trackDirectorSubmission object
	 * @param $reviewId int
	 */
	function viewReviewFormResponse($trackDirectorSubmission, $reviewId) {
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignment =& $reviewAssignmentDao->getReviewAssignmentById($reviewId);

		if (HookRegistry::call('TrackDirectorAction::viewReviewFormResponse', array(&$trackDirectorSubmission, &$reviewAssignment, &$reviewId))) return $reviewId;

		if (isset($reviewAssignment) && $reviewAssignment->getPaperId() == $trackDirectorSubmission->getPaperId()) {
			$reviewFormId = $reviewAssignment->getReviewFormId();
			if ($reviewFormId != null) {
				import('submission.form.ReviewFormResponseForm');
				$reviewForm = new ReviewFormResponseForm($reviewId, $reviewFormId);
				$reviewForm->initData();
				$reviewForm->display();
			}
		}
	}

	/**
	 * Set the file to use as the default editing file.
	 * @param $trackDirectorSubmission object
	 * @param $fileId int
	 * @param $revision int
	 * @param $createGalley boolean
	 * TODO: SECURITY!
	 */
	function setEditingFile($trackDirectorSubmission, $fileId, $revision, $createGalley = false) {
		import('file.PaperFileManager');
		$paperFileManager = new PaperFileManager($trackDirectorSubmission->getPaperId());
		$trackDirectorSubmissionDao =& DAORegistry::getDAO('TrackDirectorSubmissionDAO');
		$paperFileDao =& DAORegistry::getDAO('PaperFileDAO');
		$user =& Request::getUser();

		if (!HookRegistry::call('TrackDirectorAction::setEditingFile', array(&$trackDirectorSubmission, &$fileId, &$revision))) {
			// Copy the file from the director decision file folder to the layout folder
			$newFileId = $paperFileManager->copyToLayoutFile($fileId, $revision);

			$trackDirectorSubmission->setLayoutFileId($newFileId);
			$trackDirectorSubmissionDao->updateTrackDirectorSubmission($trackDirectorSubmission);

			if ($createGalley) {
				$paperGalleyDao =& DAORegistry::getDAO('PaperGalleyDAO');
				$galleys =& $paperGalleyDao->getGalleysByPaper($trackDirectorSubmission->getPaperId());
				if (empty($galleys)) {
					$layoutFile =& $paperFileDao->getPaperFile($newFileId, 1);
					$fileType = $layoutFile->getFileType();
					$fileId = $paperFileManager->copyPublicFile($layoutFile->getFilePath(), $fileType);
					if (strstr($fileType, 'html')) {
						$galley = new PaperHTMLGalley();
					} else {
						$galley = new PaperGalley();
					}
					$galley->setPaperId($trackDirectorSubmission->getPaperId());
					$galley->setLocale(AppLocale::getLocale());
					$galley->setFileId($fileId);
					if ($galley->isHTMLGalley()) {
						$galley->setLabel('HTML');
					} elseif (strstr($fileType, 'pdf')) {
						$galley->setLabel('PDF');
					} else if (strstr($fileType, 'postscript')) {
						$galley->setLabel('Postscript');
					} else if (strstr($fileType, 'xml')) {
						$galley->setLabel('XML');
					} else {
						$galley->setLabel(__('common.untitled'));
					}
					$paperGalleyDao->insertGalley($galley);
				}
			}

			// Add log
			import('paper.log.PaperLog');
			import('paper.log.PaperEventLogEntry');
			PaperLog::logEvent($trackDirectorSubmission->getPaperId(), PAPER_LOG_LAYOUT_SET_FILE, LOG_TYPE_FILE, $trackDirectorSubmission->getLayoutFileId(), 'log.layout.layoutFileSet', array('directorName' => $user->getFullName(), 'paperId' => $trackDirectorSubmission->getPaperId()));
		}
	}

	/**
	 * Upload the review version of a paper.
	 * @param $trackDirectorSubmission object
	 */
	function uploadReviewVersion($trackDirectorSubmission) {
		import('file.PaperFileManager');
		$paperFileManager = new PaperFileManager($trackDirectorSubmission->getPaperId());
		$trackDirectorSubmissionDao =& DAORegistry::getDAO('TrackDirectorSubmissionDAO');

		$fileName = 'upload';
		if ($paperFileManager->uploadError($fileName)) return false;
		if (!$paperFileManager->uploadedFileExists($fileName)) return false;
		if (HookRegistry::call('TrackDirectorAction::uploadReviewVersion', array(&$trackDirectorSubmission))) return true;

		if ($trackDirectorSubmission->getReviewFileId() != null) {
			$reviewFileId = $paperFileManager->uploadReviewFile($fileName, $trackDirectorSubmission->getReviewFileId());
			// Increment the review revision.
			$trackDirectorSubmission->setReviewRevision($trackDirectorSubmission->getReviewRevision()+1);
		} else {
			$reviewFileId = $paperFileManager->uploadReviewFile($fileName);
			$trackDirectorSubmission->setReviewRevision(1);
		}
		$directorFileId = $paperFileManager->copyToDirectorFile($reviewFileId, $trackDirectorSubmission->getReviewRevision(), $trackDirectorSubmission->getDirectorFileId());

		if ($reviewFileId != 0 && isset($directorFileId) && $directorFileId != 0) {
			$trackDirectorSubmission->setReviewFileId($reviewFileId);
			$trackDirectorSubmission->setDirectorFileId($directorFileId);

			$trackDirectorSubmissionDao->updateTrackDirectorSubmission($trackDirectorSubmission);
		}
		return true;
	}

	/**
	 * Upload the post-review version of a paper.
	 * @param $trackDirectorSubmission object
	 */
	function uploadDirectorVersion($trackDirectorSubmission) {
		import('file.PaperFileManager');
		$paperFileManager = new PaperFileManager($trackDirectorSubmission->getPaperId());
		$trackDirectorSubmissionDao =& DAORegistry::getDAO('TrackDirectorSubmissionDAO');
		$user =& Request::getUser();

		$fileName = 'upload';
		if ($paperFileManager->uploadError($fileName)) return false;
		if ($paperFileManager->uploadedFileExists($fileName) && !HookRegistry::call('TrackDirectorAction::uploadDirectorVersion', array(&$trackDirectorSubmission))) {
			if ($trackDirectorSubmission->getDirectorFileId() != null) {
				$fileId = $paperFileManager->uploadDirectorDecisionFile($fileName, $trackDirectorSubmission->getDirectorFileId());
			} else {
				$fileId = $paperFileManager->uploadDirectorDecisionFile($fileName);
			}
		}

		if (isset($fileId) && $fileId != 0) {
			$trackDirectorSubmission->setDirectorFileId($fileId);
			$trackDirectorSubmissionDao->updateTrackDirectorSubmission($trackDirectorSubmission);

			// Add log
			import('paper.log.PaperLog');
			import('paper.log.PaperEventLogEntry');
			PaperLog::logEvent($trackDirectorSubmission->getPaperId(), PAPER_LOG_DIRECTOR_FILE, LOG_TYPE_DIRECTOR, $trackDirectorSubmission->getDirectorFileId(), 'log.director.directorFile');
		}
	}

	/**
	 * Mark a paper as published or move a previously published paper
	 * back into the queue.
	 * @param $trackDirectorSubmission object
	 * @param $complete boolean If true, complete the submission. If false,
	 * 	return it to the queue (unpublish it).
	 */
	function completePaper($trackDirectorSubmission, $complete) {
		import('file.PaperFileManager');
		$paperFileManager = new PaperFileManager($trackDirectorSubmission->getPaperId());
		$trackDirectorSubmissionDao =& DAORegistry::getDAO('TrackDirectorSubmissionDAO');
		$user =& Request::getUser();

		import('paper.log.PaperLog');
		import('paper.log.PaperEventLogEntry');

		if ($complete) { // Publish the paper.
			$trackDirectorSubmission->setStatus(STATUS_PUBLISHED);
			$trackDirectorSubmissionDao->updateTrackDirectorSubmission($trackDirectorSubmission);

			// Add a published paper object
			$publishedPaperDao =& DAORegistry::getDAO('PublishedPaperDAO');
			if(($publishedPaper = $publishedPaperDao->getPublishedPaperByPaperId($trackDirectorSubmission->getPaperId())) == null) {
				$schedConfId = $trackDirectorSubmission->getSchedConfId();

				$publishedPaper = new PublishedPaper();
				$publishedPaper->setPaperId($trackDirectorSubmission->getPaperId());
				$publishedPaper->setSchedConfId($schedConfId);
				$publishedPaper->setDatePublished(Core::getCurrentDate());
				$publishedPaper->setSeq(REALLY_BIG_NUMBER);
				$publishedPaper->setViews(0);

				$publishedPaperDao->insertPublishedPaper($publishedPaper);

				// Resequence the published papers.
				$publishedPaperDao->resequencePublishedPapers($trackDirectorSubmission->getTrackId(), $schedConfId);
			}

			// Add log
			PaperLog::logEvent($trackDirectorSubmission->getPaperId(), PAPER_LOG_DIRECTOR_PUBLISH, LOG_TYPE_DIRECTOR, $trackDirectorSubmission->getDirectorFileId(), 'log.director.publish');

		} else { // Un-publish the paper.

			$trackDirectorSubmission->setStatus(STATUS_QUEUED);
			$trackDirectorSubmissionDao->updateTrackDirectorSubmission($trackDirectorSubmission);

			// Add log
			PaperLog::logEvent($trackDirectorSubmission->getPaperId(), PAPER_LOG_DIRECTOR_UNPUBLISH, LOG_TYPE_DIRECTOR, $trackDirectorSubmission->getDirectorFileId(), 'log.director.unpublish');
		}
	}

	/**
	 * Archive a submission.
	 * @param $trackDirectorSubmission object
	 */
	function archiveSubmission($trackDirectorSubmission) {
		$trackDirectorSubmissionDao =& DAORegistry::getDAO('TrackDirectorSubmissionDAO');
		$user =& Request::getUser();

		if (HookRegistry::call('TrackDirectorAction::archiveSubmission', array(&$trackDirectorSubmission))) return;

		$trackDirectorSubmission->setStatus(STATUS_ARCHIVED);
		$trackDirectorSubmission->stampStatusModified();
		$trackDirectorSubmission->stampDateToArchive();

		$trackDirectorSubmissionDao->updateTrackDirectorSubmission($trackDirectorSubmission);

		// Add log
		import('paper.log.PaperLog');
		import('paper.log.PaperEventLogEntry');
		PaperLog::logEvent($trackDirectorSubmission->getPaperId(), PAPER_LOG_DIRECTOR_ARCHIVE, LOG_TYPE_DIRECTOR, $trackDirectorSubmission->getPaperId(), 'log.director.archived', array('paperId' => $trackDirectorSubmission->getPaperId()));
	}

	/**
	 * Restores a submission to the queue.
	 * @param $trackDirectorSubmission object
	 */
	function restoreToQueue($trackDirectorSubmission) {
		if (HookRegistry::call('TrackDirectorAction::restoreToQueue', array(&$trackDirectorSubmission))) return;

		$trackDirectorSubmissionDao =& DAORegistry::getDAO('TrackDirectorSubmissionDAO');

		// Determine which queue to return the paper to: the
		// scheduling queue or the editing queue.
		$publishedPaperDao =& DAORegistry::getDAO('PublishedPaperDAO');
		$publishedPaper =& $publishedPaperDao->getPublishedPaperByPaperId($trackDirectorSubmission->getPaperId());
		if ($publishedPaper) {
			$trackDirectorSubmission->setStatus(STATUS_PUBLISHED);
		} else {
			$trackDirectorSubmission->setStatus(STATUS_QUEUED);
		}
		unset($publishedPaper);

		$trackDirectorSubmission->stampStatusModified();

		$trackDirectorSubmissionDao->updateTrackDirectorSubmission($trackDirectorSubmission);

		// Add log
		import('paper.log.PaperLog');
		import('paper.log.PaperEventLogEntry');
		PaperLog::logEvent($trackDirectorSubmission->getPaperId(), PAPER_LOG_DIRECTOR_RESTORE, LOG_TYPE_DIRECTOR, $trackDirectorSubmission->getPaperId(), 'log.director.restored', array('paperId' => $trackDirectorSubmission->getPaperId()));
	}

	//
	// Layout Editing
	//

	/**
	 * Change the sequence order of a galley.
	 * @param $paper object
	 * @param $galleyId int
	 * @param $direction char u = up, d = down
	 */
	function orderGalley($paper, $galleyId, $direction) {
		$galleyDao =& DAORegistry::getDAO('PaperGalleyDAO');
		$galley =& $galleyDao->getGalley($galleyId, $paper->getId());
		if (isset($galley)) {
			$galley->setSequence($galley->getSequence() + ($direction == 'u' ? -1.5 : 1.5));
			$galleyDao->updateGalley($galley);
			$galleyDao->resequenceGalleys($paper->getId());
		}
	}

	/**
	 * Delete a galley.
	 * @param $paper object
	 * @param $galleyId int
	 */
	function deleteGalley($paper, $galleyId) {
import('file.PaperFileManager');

		$galleyDao =& DAORegistry::getDAO('PaperGalleyDAO');
		$galley =& $galleyDao->getGalley($galleyId, $paper->getId());

		if (isset($galley) && !HookRegistry::call('TrackDirectorAction::deleteGalley', array(&$paper, &$galley))) {
			$paperFileManager = new PaperFileManager($paper->getId());

			if ($galley->getFileId()) {
				$paperFileManager->deleteFile($galley->getFileId());
				import('search.PaperSearchIndex');
				PaperSearchIndex::deleteTextIndex($paper->getId(), PAPER_SEARCH_GALLEY_FILE, $galley->getFileId());
			}
			if ($galley->isHTMLGalley()) {
				if ($galley->getStyleFileId()) {
					$paperFileManager->deleteFile($galley->getStyleFileId());
				}
				foreach ($galley->getImageFiles() as $image) {
					$paperFileManager->deleteFile($image->getFileId());
				}
			}
			$galleyDao->deleteGalley($galley);
		}
	}

	/**
	 * Change the sequence order of a supplementary file.
	 * @param $paper object
	 * @param $suppFileId int
	 * @param $direction char u = up, d = down
	 */
	function orderSuppFile($paper, $suppFileId, $direction) {
		$suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
		$suppFile =& $suppFileDao->getSuppFile($suppFileId, $paper->getId());
		if (isset($suppFile)) {
			$suppFile->setSequence($suppFile->getSequence() + ($direction == 'u' ? -1.5 : 1.5));
			$suppFileDao->updateSuppFile($suppFile);
			$suppFileDao->resequenceSuppFiles($paper->getId());
		}
	}

	/**
	 * Delete a supplementary file.
	 * @param $paper object
	 * @param $suppFileId int
	 */
	function deleteSuppFile($paper, $suppFileId) {
		import('file.PaperFileManager');

		$suppFileDao =& DAORegistry::getDAO('SuppFileDAO');

		$suppFile =& $suppFileDao->getSuppFile($suppFileId, $paper->getId());
		if (isset($suppFile) && !HookRegistry::call('TrackDirectorAction::deleteSuppFile', array(&$paper, &$suppFile))) {
			if ($suppFile->getFileId()) {
				$paperFileManager = new PaperFileManager($paper->getId());
				$paperFileManager->deleteFile($suppFile->getFileId());
				import('search.PaperSearchIndex');
				PaperSearchIndex::deleteTextIndex($paper->getId(), PAPER_SEARCH_SUPPLEMENTARY_FILE, $suppFile->getFileId());
			}
			$suppFileDao->deleteSuppFile($suppFile);
		}
	}

	/**
	 * Delete a file from a paper.
	 * @param $submission object
	 * @param $fileId int
	 * @param $revision int (optional)
	 */
	function deletePaperFile($submission, $fileId, $revision) {
		import('file.PaperFileManager');
		$file =& $submission->getDirectorFile();

		if (isset($file) && $file->getFileId() == $fileId && !HookRegistry::call('TrackDirectorAction::deletePaperFile', array(&$submission, &$fileId, &$revision))) {
			$paperFileManager = new PaperFileManager($submission->getPaperId());
			$paperFileManager->deleteFile($fileId, $revision);
		}
	}

	/**
	 * Delete an image from a paper galley.
	 * @param $submission object
	 * @param $fileId int
	 * @param $revision int (optional)
	 */
	function deletePaperImage($submission, $fileId, $revision) {
		import('file.PaperFileManager');
		$paperGalleyDao =& DAORegistry::getDAO('PaperGalleyDAO');
		if (HookRegistry::call('TrackDirectorAction::deletePaperImage', array(&$submission, &$fileId, &$revision))) return;
		foreach ($submission->getGalleys() as $galley) {
			$images =& $paperGalleyDao->getGalleyImages($galley->getId());
			foreach ($images as $imageFile) {
				if ($imageFile->getPaperId() == $submission->getPaperId() && $fileId == $imageFile->getFileId() && $imageFile->getRevision() == $revision) {
					$paperFileManager = new PaperFileManager($submission->getPaperId());
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

		$paperNoteDao =& DAORegistry::getDAO('PaperNoteDAO');
		$user =& Request::getUser();

		$paperNote = new PaperNote();
		$paperNote->setPaperId($paperId);
		$paperNote->setUserId($user->getId());
		$paperNote->setDateCreated(Core::getCurrentDate());
		$paperNote->setDateModified(Core::getCurrentDate());
		$paperNote->setTitle(Request::getUserVar('title'));
		$paperNote->setNote(Request::getUserVar('note'));

		if (!HookRegistry::call('TrackDirectorAction::addSubmissionNote', array(&$paperId, &$paperNote))) {
			$paperFileManager = new PaperFileManager($paperId);
			if ($paperFileManager->uploadedFileExists('upload')) {
				if ($paperFileManager->uploadError('upload')) return false;
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
			$paperFileManager = new PaperFileManager($paperId);
			$paperFileManager->deleteFile($fileId);
		}

		$paperNoteDao =& DAORegistry::getDAO('PaperNoteDAO');
		$paperNoteDao->deletePaperNoteById($noteId);
	}

	/**
	 * Updates Submission Note
	 * @param $paperId int
	 */
	function updateSubmissionNote($paperId) {
		import('file.PaperFileManager');

		$paperNoteDao =& DAORegistry::getDAO('PaperNoteDAO');
		$user =& Request::getUser();

		$paperNote = new PaperNote();
		$paperNote->setNoteId(Request::getUserVar('noteId'));
		$paperNote->setPaperId($paperId);
		$paperNote->setUserId($user->getId());
		$paperNote->setDateModified(Core::getCurrentDate());
		$paperNote->setTitle(Request::getUserVar('title'));
		$paperNote->setNote(Request::getUserVar('note'));
		$paperNote->setFileId(Request::getUserVar('fileId'));

		if (HookRegistry::call('TrackDirectorAction::updateSubmissionNote', array(&$paperId, &$paperNote))) return;

		$paperFileManager = new PaperFileManager($paperId);

		if ($paperFileManager->uploadError('upload')) return false;
		// if there is a new file being uploaded
		if ($paperFileManager->uploadedFileExists('upload')) {
			// Attach the new file to the note, overwriting existing file if necessary
			$fileId = $paperFileManager->uploadSubmissionNoteFile('upload', $paperNote->getFileId(), true);
			$paperNote->setFileId($fileId);

		} else {
			if (Request::getUserVar('removeUploadedFile')) {
				$paperFileManager = new PaperFileManager($paperId);
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

		$paperNoteDao =& DAORegistry::getDAO('PaperNoteDAO');

		$fileIds = $paperNoteDao->getAllPaperNoteFileIds($paperId);

		if (!empty($fileIds)) {
			$paperFileDao =& DAORegistry::getDAO('PaperFileDAO');
			$paperFileManager = new PaperFileManager($paperId);

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

		$commentForm = new PeerReviewCommentForm($paper, $reviewId, Validation::isDirector()?ROLE_ID_DIRECTOR:ROLE_ID_TRACK_DIRECTOR);
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

		$commentForm = new PeerReviewCommentForm($paper, $reviewId, Validation::isDirector()?ROLE_ID_DIRECTOR:ROLE_ID_TRACK_DIRECTOR);
		$commentForm->readInputData();

		if ($commentForm->validate()) {
			$commentForm->execute();
			
			// Send a notification to associated users
			import('notification.NotificationManager');
			$notificationManager = new NotificationManager();
			$notificationUsers = $paper->getAssociatedUserIds(false, false);
			foreach ($notificationUsers as $userRole) {
				$url = Request::url(null, null, $userRole['role'], 'submissionReview', $paper->getId(), null, 'peerReview');
				$notificationManager->createNotification(
					$userRole['id'], 'notification.type.reviewerComment',
					$paper->getLocalizedTitle(), $url, 1, NOTIFICATION_TYPE_REVIEWER_COMMENT
				);
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

	/**
	 * View director decision comments.
	 * @param $paper object
	 */
	function viewDirectorDecisionComments($paper) {
		if (HookRegistry::call('TrackDirectorAction::viewDirectorDecisionComments', array(&$paper))) return;

		import('submission.form.comment.DirectorDecisionCommentForm');

		$commentForm = new DirectorDecisionCommentForm($paper, Validation::isDirector()?ROLE_ID_DIRECTOR:ROLE_ID_TRACK_DIRECTOR);
		$commentForm->initData();
		$commentForm->display();
	}

	/**
	 * Post director decision comment.
	 * @param $paper int
	 * @param $emailComment boolean
	 */
	function postDirectorDecisionComment($paper, $emailComment) {
		if (HookRegistry::call('TrackDirectorAction::postDirectorDecisionComment', array(&$paper, &$emailComment))) return;

		import('submission.form.comment.DirectorDecisionCommentForm');

		$commentForm = new DirectorDecisionCommentForm($paper, Validation::isDirector()?ROLE_ID_DIRECTOR:ROLE_ID_TRACK_DIRECTOR);
		$commentForm->readInputData();

		if ($commentForm->validate()) {
			$commentForm->execute();

			// Send a notification to associated users
			import('notification.NotificationManager');
			$notificationManager = new NotificationManager();
			$notificationUsers = $paper->getAssociatedUserIds(true, false);
			foreach ($notificationUsers as $userRole) {
				$url = Request::url(null, null, $userRole['role'], 'submissionReview', $paper->getId(), null, 'directorDecision');
				$notificationManager->createNotification(
					$userRole['id'], 'notification.type.directorDecisionComment',
					$paper->getLocalizedTitle(), $url, 1, NOTIFICATION_TYPE_DIRECTOR_DECISION_COMMENT
				);
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

	/**
	 * Email director decision comment.
	 * @param $trackDirectorSubmission object
	 * @param $send boolean
	 */
	function emailDirectorDecisionComment($trackDirectorSubmission, $send) {
		$userDao =& DAORegistry::getDAO('UserDAO');
		$paperCommentDao =& DAORegistry::getDAO('PaperCommentDAO');
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();

		$templateName = null;
		$reviewMode = $trackDirectorSubmission->getReviewMode();
		$stages = $trackDirectorSubmission->getDecisions();
		if (is_array($stages)) {
			$isAbstract = array_pop(array_keys($stages)) == REVIEW_STAGE_ABSTRACT;
		}
		if (isset($stages) && is_array($stages)) {
			$decisions = array_pop($stages);
			// If this round has no decision, revert to prior round
			if (empty($decisions)) $decisions = array_pop($stages);
		}
		if (isset($decisions) && is_array($decisions)) $lastDecision = array_pop($decisions);
		if (isset($lastDecision) && is_array($lastDecision)) switch ($lastDecision['decision']) {
			case SUBMISSION_DIRECTOR_DECISION_INVITE:
				$templateName = ($reviewMode==REVIEW_MODE_BOTH_SEQUENTIAL?'SUBMISSION_PAPER_INVITE':'SUBMISSION_ABSTRACT_ACCEPT');
				break;
			case SUBMISSION_DIRECTOR_DECISION_ACCEPT:
				$templateName = 'SUBMISSION_PAPER_ACCEPT';
				break;
			case SUBMISSION_DIRECTOR_DECISION_PENDING_REVISIONS:
				$templateName = $isAbstract?'SUBMISSION_ABSTRACT_REVISE':'SUBMISSION_PAPER_REVISE';
				break;
			case SUBMISSION_DIRECTOR_DECISION_DECLINE:
				$templateName = $isAbstract?'SUBMISSION_ABSTRACT_DECLINE':'SUBMISSION_PAPER_DECLINE';
				break;
		}

		$user =& Request::getUser();
		import('mail.PaperMailTemplate');
		$email = new PaperMailTemplate($trackDirectorSubmission, $templateName);

		if ($send && !$email->hasErrors()) {
			HookRegistry::call('TrackDirectorAction::emailDirectorDecisionComment', array(&$trackDirectorSubmission, &$send));
			$email->send();

			$paperComment = new PaperComment();
			$paperComment->setCommentType(COMMENT_TYPE_DIRECTOR_DECISION);
			$paperComment->setRoleId(Validation::isDirector()?ROLE_ID_DIRECTOR:ROLE_ID_TRACK_DIRECTOR);
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
				$authorUser =& $userDao->getUser($trackDirectorSubmission->getUserId());
				$authorEmail = $authorUser->getEmail();
				$email->addRecipient($authorEmail, $authorUser->getFullName());
				if ($schedConf->getSetting('notifyAllAuthorsOnDecision')) foreach ($trackDirectorSubmission->getAuthors() as $author) {
					if ($author->getEmail() != $authorEmail) {
						$email->addCc ($author->getEmail(), $author->getFullName());
					}
				}
				$email->assignParams(array(
					'conferenceDate' => strftime(Config::getVar('general', 'date_format_short'), $schedConf->getSetting('startDate')),
					'authorName' => $authorUser->getFullName(),
					'conferenceTitle' => $conference->getConferenceTitle(),
					'editorialContactSignature' => $user->getContactSignature(),
					'locationCity' => $schedConf->getSetting('locationCity'),
					'paperTitle' => $trackDirectorSubmission->getLocalizedTitle()
				));
			} elseif (Request::getUserVar('importPeerReviews')) {
				$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
				$hasBody = false;
				for ($stage = $trackDirectorSubmission->getCurrentStage(); $stage == REVIEW_STAGE_ABSTRACT || $stage == REVIEW_STAGE_PRESENTATION; $stage--) {
					$reviewAssignments =& $reviewAssignmentDao->getReviewAssignmentsByPaperId($trackDirectorSubmission->getPaperId(), $stage);
					$reviewIndexes =& $reviewAssignmentDao->getReviewIndexesForStage($trackDirectorSubmission->getPaperId(), $stage);

					$body = '';
					foreach ($reviewAssignments as $reviewAssignment) {
						// If the reviewer has completed the assignment, then import the review.
						if ($reviewAssignment->getDateCompleted() != null && !$reviewAssignment->getCancelled()) {
							// Get the comments associated with this review assignment
							$paperComments =& $paperCommentDao->getPaperComments($trackDirectorSubmission->getPaperId(), COMMENT_TYPE_PEER_REVIEW, $reviewAssignment->getId());
							
							if ($paperComments) {
								$body .= "------------------------------------------------------\n";
								$body .= __('submission.comments.importPeerReviews.reviewerLetter', array('reviewerLetter' => chr(ord('A') + $reviewIndexes[$reviewAssignment->getId()]))) . "\n";
								if (is_array($paperComments)) {
									foreach ($paperComments as $comment) {
										// If the comment is viewable by the author, then add the comment.
										if ($comment->getViewable()) {
											$body .= String::html2utf(
												strip_tags(
													str_replace(array('<p>', '<br>', '<br/>'), array("\n", "\n", "\n"), $comment->getComments())
												)
											) . "\n\n";
											$hasBody = true;
										}
									}
								}
								$body .= "------------------------------------------------------\n\n";
							} 
							if ($reviewFormId = $reviewAssignment->getReviewFormId()){
								$reviewId = $reviewAssignment->getId();
								
								$reviewFormResponseDao =& DAORegistry::getDAO('ReviewFormResponseDAO');
								$reviewFormElementDao =& DAORegistry::getDAO('ReviewFormElementDAO');
								$reviewFormElements =& $reviewFormElementDao->getReviewFormElements($reviewFormId);
								if (!$paperComments) {
									$body .= "------------------------------------------------------\n";
									$body .= __('submission.comments.importPeerReviews.reviewerLetter', array('reviewerLetter' => chr(ord('A') + $reviewIndexes[$reviewAssignment->getId()]))) . "\n\n";
								}
								foreach ($reviewFormElements as $reviewFormElement) if ($reviewFormElement->getIncluded()) {
									$body .= strip_tags($reviewFormElement->getLocalizedQuestion()) . ": \n";
									$reviewFormResponse = $reviewFormResponseDao->getReviewFormResponse($reviewId, $reviewFormElement->getId());
			
									if ($reviewFormResponse) {
										$possibleResponses = $reviewFormElement->getLocalizedPossibleResponses();
										if (in_array($reviewFormElement->getElementType(), $reviewFormElement->getMultipleResponsesElementTypes())) {
											if ($reviewFormElement->getElementType() == REVIEW_FORM_ELEMENT_TYPE_CHECKBOXES) {
												foreach ($reviewFormResponse->getValue() as $value) {
													$body .= "\t" . String::html2utf(strip_tags($possibleResponses[$value-1]['content'])) . "\n";
												}
											} else {
												$body .= "\t" . String::html2utf(strip_tags($possibleResponses[$reviewFormResponse->getValue()-1]['content'])) . "\n";
											}
											$body .= "\n";
										} else {
											$body .= "\t" . String::html2utf(strip_tags($reviewFormResponse->getValue())) . "\n\n";
										}
									}
								}
								$body .= "------------------------------------------------------\n\n";
								$hasBody = true;
							}
						} // if
					} // foreach
					if ($hasBody) {
						$oldBody = $email->getBody();
						if (!empty($oldBody)) $oldBody .= "\n";
						$email->setBody($oldBody . $body);
						break;
					}
				} // foreach
			}

			$email->displayEditForm(Request::url(null, null, null, 'emailDirectorDecisionComment', 'send'), array('paperId' => $trackDirectorSubmission->getPaperId()), 'submission/comment/directorDecisionEmail.tpl', array('isADirector' => true));

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
		$commentDao =& DAORegistry::getDAO('PaperCommentDAO');
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$conference =& Request::getConference();

		$comments =& $commentDao->getPaperComments($paper->getId(), COMMENT_TYPE_DIRECTOR_DECISION);
		$reviewAssignments =& $reviewAssignmentDao->getReviewAssignmentsByPaperId($paper->getId(), $paper->getCurrentStage());

		$commentsText = "";
		foreach ($comments as $comment) {
			$commentsText .= String::html2utf(strip_tags($comment->getComments())) . "\n\n";
		}

		$user =& Request::getUser();
		import('mail.PaperMailTemplate');
		$email = new PaperMailTemplate($paper, 'SUBMISSION_DECISION_REVIEWERS', null, null, null, null, true, true);

		if ($send && !$email->hasErrors() && !$inhibitExistingEmail) {
			HookRegistry::call('TrackDirectorAction::blindCcReviewsToReviewers', array(&$paper, &$reviewAssignments, &$email));
			$email->send();
			return true;
		} else {
			if ($inhibitExistingEmail || !Request::getUserVar('continued')) {
				$email->clearRecipients();
				foreach ($reviewAssignments as $reviewAssignment) {
					if ($reviewAssignment->getDateCompleted() != null && !$reviewAssignment->getCancelled()) {
						$reviewer =& $userDao->getUser($reviewAssignment->getReviewerId());

						if (isset($reviewer)) $email->addBcc($reviewer->getEmail(), $reviewer->getFullName());
					}
				}

				$paramArray = array(
					'comments' => $commentsText,
					'editorialContactSignature' => $user->getContactSignature()
				);
				$email->assignParams($paramArray);
			}

			$email->displayEditForm(Request::url(null, null, null, 'blindCcReviewsToReviewers'), array('paperId' => $paper->getId()));
			return false;
		}
	}

	/**
	 * Accepts the review assignment on behalf of its reviewer.
	 * @param $reviewId int
	 */
	function confirmReviewForReviewer($reviewId) {
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$user =& Request::getUser();

		$reviewAssignment =& $reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$reviewer =& $userDao->getUser($reviewAssignment->getReviewerId(), true);

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

			$entry = new PaperEventLogEntry();
			$entry->setPaperId($reviewAssignment->getPaperId());
			$entry->setUserId($user->getId());
			$entry->setDateLogged(Core::getCurrentDate());
			$entry->setEventType(PAPER_LOG_REVIEW_ACCEPT_BY_PROXY);
			$entry->setLogMessage('log.review.reviewAcceptedByProxy', array('reviewerName' => $reviewer->getFullName(), 'paperId' => $reviewAssignment->getPaperId(), 'stage' => $reviewAssignment->getStage(), 'userName' => $user->getFullName()));
			$entry->setAssocType(LOG_TYPE_REVIEW);
			$entry->setAssocId($reviewAssignment->getId());

			PaperLog::logEventEntry($reviewAssignment->getPaperId(), $entry);
		}
	}

	/**
	 * Upload a review on behalf of its reviewer.
	 * @param $reviewId int
	 */
	function uploadReviewForReviewer($reviewId) {
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$user =& Request::getUser();

		$reviewAssignment =& $reviewAssignmentDao->getReviewAssignmentById($reviewId);
		$reviewer =& $userDao->getUser($reviewAssignment->getReviewerId(), true);

		if (HookRegistry::call('TrackDirectorAction::uploadReviewForReviewer', array(&$reviewAssignment, &$reviewer))) return;

		// Upload the review file.
		import('file.PaperFileManager');
		$paperFileManager = new PaperFileManager($reviewAssignment->getPaperId());
		// Only upload the file if the reviewer has yet to submit a recommendation
		if (($reviewAssignment->getRecommendation() === null || $reviewAssignment->getRecommendation() === '') && !$reviewAssignment->getCancelled()) {
			$fileName = 'upload';
			if ($paperFileManager->uploadError($fileName)) return false;
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

			$entry = new PaperEventLogEntry();
			$entry->setPaperId($reviewAssignment->getPaperId());
			$entry->setUserId($user->getId());
			$entry->setDateLogged(Core::getCurrentDate());
			$entry->setEventType(PAPER_LOG_REVIEW_FILE_BY_PROXY);
			$entry->setLogMessage('log.review.reviewFileByProxy', array('reviewerName' => $reviewer->getFullName(), 'paperId' => $reviewAssignment->getPaperId(), 'stage' => $reviewAssignment->getStage(), 'userName' => $user->getFullName()));
			$entry->setAssocType(LOG_TYPE_REVIEW);
			$entry->setAssocId($reviewAssignment->getId());

			PaperLog::logEventEntry($reviewAssignment->getPaperId(), $entry);
		}
	}

	/**
	 * Changes the submission RT comments status.
	 * @param $submission object
	 * @param $commentsStatus int
	 */
	function updateCommentsStatus($submission, $commentsStatus) {
		if (HookRegistry::call('TrackDirectorAction::updateCommentsStatus', array(&$submission, &$commentsStatus))) return;

		$submissionDao =& DAORegistry::getDAO('TrackDirectorSubmissionDAO');
		$submission->setCommentsStatus($commentsStatus); // FIXME validate this?
		$submissionDao->updateTrackDirectorSubmission($submission);
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
				case 'history':
					$parent = array(Request::url(null, null, $track, 'submissionHistory', $paperId), 'submission.history');
					break;
			}
			if ($track != 'director' && $track != 'trackDirector') {
				$parent[0] = Request::url(null, null, $track, 'submission', $paperId);
			}
			if (isset($parent)) $breadcrumb[] = $parent;
		}
		return $breadcrumb;
	}

}

?>
