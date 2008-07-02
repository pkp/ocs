<?php

/**
 * @file ReviewReminder.inc.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewReminder
 * @ingroup tasks
 *
 * @brief Class to perform automated reminders for reviewers.
 *
 */

// $Id$


import('scheduledTask.ScheduledTask');

class ReviewReminder extends ScheduledTask {

	/**
	 * Constructor.
	 */
	function ReviewReminder() {
		$this->ScheduledTask();
	}

	function sendReminder ($reviewAssignment, $paper, $conference, $schedConf) {
		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$reviewId = $reviewAssignment->getReviewId();

		$reviewer = &$userDao->getUser($reviewAssignment->getReviewerId());
		if (!isset($reviewer)) return false;

		import('mail.PaperMailTemplate');

		$reviewerAccessKeysEnabled = $schedConf->getSetting('reviewerAccessKeysEnabled');

		$email = &new PaperMailTemplate($paper, $reviewerAccessKeysEnabled?'REVIEW_REMIND_AUTO_ONECLICK':'REVIEW_REMIND_AUTO', null, false, $conference, $schedConf);
		$email->setConference($conference);
		$email->setSchedConf($schedConf);

		$contactEmail = $schedConf->getSetting('contactEmail') ? $schedConf->getSetting('contactEmail') : $conference->getSetting('contactEmail');
		$contactName = $schedConf->getSetting('contactName') ? $schedConf->getSetting('contactName') : $conference->getSetting('contactName');
		$email->setFrom($contactEmail, $contactName);

		$email->addRecipient($reviewer->getEmail(), $reviewer->getFullName());
		$email->setAssoc(PAPER_EMAIL_REVIEW_REMIND, PAPER_EMAIL_TYPE_REVIEW, $reviewId);

		$email->setSubject($email->getSubject($conference->getPrimaryLocale()));
		$email->setBody($email->getBody($conference->getPrimaryLocale()));

		$urlParams = array();
		if ($reviewerAccessKeysEnabled) {
			import('security.AccessKeyManager');
			$accessKeyManager =& new AccessKeyManager();

			// Key lifetime is the typical review period plus four weeks
			$keyLifetime = ($schedConf->getSetting('numWeeksPerReview') + 4) * 7;
			$urlParams['key'] = $accessKeyManager->createKey('ReviewerContext', $reviewer->getUserId(), $reviewId, $keyLifetime);
		}
		$submissionReviewUrl = Request::url($conference->getPath(), $schedConf->getPath(), 'reviewer', 'submission', $reviewId, $urlParams);

		$paramArray = array(
			'reviewerName' => $reviewer->getFullName(),
			'reviewerUsername' => $reviewer->getUsername(),
			'conferenceUrl' => $conference->getUrl(),
			'schedConfUrl' => $schedConf->getUrl(),
			'reviewerPassword' => $reviewer->getPassword(),
			'reviewDueDate' => strftime(Config::getVar('general', 'date_format_short'), strtotime($reviewAssignment->getDateDue())),
			'editorialContactSignature' => $contactName . "\n" . $schedConf->getFullTitle(),
			'passwordResetUrl' => Request::url($conference->getPath(), $schedConf->getPath(), 'login', 'resetPassword', $reviewer->getUsername(), array('confirm' => Validation::generatePasswordResetHash($reviewer->getUserId()))),
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
		$schedConf = null;

		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$paperDao = &DAORegistry::getDAO('PaperDAO');
		$conferenceDao = &DAORegistry::getDAO('ConferenceDAO');
		$schedConfDao = &DAORegistry::getDAO('SchedConfDAO');

		$incompleteAssignments = &$reviewAssignmentDao->getIncompleteReviewAssignments();
		foreach ($incompleteAssignments as $reviewAssignment) {
			// Fetch the Paper and the Sched Conf if necessary.
			if ($paper == null || $paper->getPaperId() != $reviewAssignment->getPaperId()) {
				$paper = &$paperDao->getPaper($reviewAssignment->getPaperId());
				if ($schedConf == null || $schedConf->getSchedConfId() != $paper->getSchedConfId()) {
					$schedConf = &$schedConfDao->getSchedConf($paper->getSchedConfId());

					$inviteReminderEnabled = $schedConf->getSetting('remindForInvite');
					$submitReminderEnabled = $schedConf->getSetting('remindForSubmit');
					$inviteReminderDays = $schedConf->getSetting('numDaysBeforeInviteReminder');
					$submitReminderDays = $schedConf->getSetting('numDaysBeforeSubmitReminder');
				}
			}

			// $paper, $schedConf, $...ReminderEnabled, $...ReminderDays, and $reviewAssignment
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
				if(!$conference || $schedConf->getConferenceId() != $conference->getConferenceId()) {
					$conference = &$conferenceDao->getConference($schedConf->getConferenceId());
				}
				$this->sendReminder ($reviewAssignment, $paper, $conference, $schedConf);
			}
		}
	}
}

?>
