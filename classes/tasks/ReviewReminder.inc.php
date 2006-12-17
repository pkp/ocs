<?php

/**
 * ReviewReminder.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Class to perform automated reminders for reviewers.
 *
 * $Id$
 */

import('scheduledTask.ScheduledTask');

class ReviewReminder extends ScheduledTask {

	/**
	 * Constructor.
	 */
	function ReviewReminder() {
		$this->ScheduledTask();
	}

	function sendReminder ($reviewAssignment, $paper, $conference, $event) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$reviewId = $reviewAssignment->getReviewId();

		$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
		if (!isset($reviewer)) return false;

		import('mail.PaperMailTemplate');

		$reviewerAccessKeysEnabled = $event->getSetting('reviewerAccessKeysEnabled',true);

		$email = &new PaperMailTemplate($paper, $reviewerAccessKeysEnabled?'REVIEW_REMIND_AUTO_ONECLICK':'REVIEW_REMIND_AUTO', null, false, $conference, $event);
		$email->setConference($conference);
		$email->setEvent($event);
		$email->setFrom($event->getSetting('contactEmail',true), $event->getSetting('contactName',true));
		$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());
		$email->setAssoc(PAPER_EMAIL_REVIEW_REMIND, PAPER_EMAIL_TYPE_REVIEW, $reviewId);

		$email->setSubject($email->getSubject($conference->getLocale()));
		$email->setBody($email->getBody($conference->getLocale()));

		$urlParams = array();
		if ($reviewerAccessKeysEnabled) {
			import('security.AccessKeyManager');
			$accessKeyManager =& new AccessKeyManager();

			// Key lifetime is the typical review period plus four weeks
			$keyLifetime = ($event->getSetting('numWeeksPerReview',true) + 4) * 7;
			$urlParams['key'] = $accessKeyManager->createKey('ReviewerContext', $reviewer->getUserId(), $reviewId, $keyLifetime);
		}
		$submissionReviewUrl = Request::url($conference->getPath(), $event->getPath(), 'reviewer', 'submission', $reviewId, $urlParams);

		$paramArray = array(
			'reviewerName' => $reviewer->getFullName(),
			'reviewerUsername' => $reviewer->getUsername(),
			'conferenceUrl' => $conference->getUrl(),
			'eventUrl' => $event->getUrl(),
			'reviewerPassword' => $reviewer->getPassword(),
			'reviewDueDate' => date('Y-m-d', strtotime($reviewAssignment->getDateDue())),
			'editorialContactSignature' => $event->getSetting('contactName',true) . "\n" . $event->getFullTitle(),
			'passwordResetUrl' => Request::url($conference->getPath(), $event->getPath(), 'login', 'resetPassword', $reviewer->getUsername(), array('confirm' => Validation::generatePasswordResetHash($reviewer->getUserId()))),
			'submissionReviewUrl' => $submissionReviewUrl
		);
		$email->assignParams($paramArray);

		$email->send();

		$reviewAssignment->setDateReminded(Core::getCurrentDate());
		$reviewAssignment->setReminderWasAutomatic(1);
		$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);

	}

	function execute() {
		$paper = null;
		$conference = null;
		$event = null;

		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$paperDao = &DAORegistry::getDAO('PaperDAO');
		$conferenceDao = &DAORegistry::getDAO('ConferenceDAO');
		$eventDao = &DAORegistry::getDAO('EventDAO');

		$incompleteAssignments = &$reviewAssignmentDao->getIncompleteReviewAssignments();
		foreach ($incompleteAssignments as $reviewAssignment) {
			// Fetch the Paper and the Event if necessary.
			if ($paper == null || $paper->getPaperId() != $reviewAssignment->getPaperId()) {
				$paper = &$paperDao->getPaper($reviewAssignment->getPaperId());
				if ($event == null || $event->getEventId() != $paper->getEventId()) {
					$event = &$eventDao->getEvent($paper->getEventId());

					$inviteReminderEnabled = $event->getSetting('remindForInvite', true);
					$submitReminderEnabled = $event->getSetting('remindForSubmit', true);
					$inviteReminderDays = $event->getSetting('numDaysBeforeInviteReminder', true);
					$submitReminderDays = $event->getSetting('numDaysBeforeSubmitReminder', true);
				}
			}

			// $paper, $event, $...ReminderEnabled, $...ReminderDays, and $reviewAssignment
			// are initialized by this point. $conference is NOT necessarily correct.
			$shouldRemind = false;
			if ($inviteReminderEnabled==1 && $reviewAssignment->getDateConfirmed() == null) {
				$checkDate = strtotime($reviewAssignment->getDateNotified());
				if (time() - $checkDate > 60 * 60 * 24 * $inviteReminderDays) {
					$shouldRemind = true;
				}
			}
			if ($submitReminderEnabled==1 && $reviewAssignment->getDateDue() != null) {
				$checkDate = strtotime($reviewAssignment->getDateDue());
				if (time() - $checkDate > 60 * 60 * 24 * $submitReminderDays) {
					$shouldRemind = true;
				}
			}

			if ($reviewAssignment->getDateReminded() !== null) {
				$shouldRemind = false;
			}

			if ($shouldRemind) {
				// We may still have to look up the conference.
				if(!$conference || $event->getConferenceId() != $conference->getConferenceId()) {
					$conference = &$conferenceDao->getConference($event->getConferenceId());
				}
				$this->sendReminder ($reviewAssignment, $paper, $conference, $event);
			}
		}
	}
}

?>
