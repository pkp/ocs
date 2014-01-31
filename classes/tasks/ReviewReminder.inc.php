<?php

/**
 * @file ReviewReminder.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
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
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$userDao =& DAORegistry::getDAO('UserDAO');
		$reviewId = $reviewAssignment->getId();

		$reviewer =& $userDao->getUser($reviewAssignment->getReviewerId());
		if (!isset($reviewer)) return false;

		import('mail.PaperMailTemplate');

		$reviewerAccessKeysEnabled = $schedConf->getSetting('reviewerAccessKeysEnabled');

		$email = new PaperMailTemplate($paper, $reviewerAccessKeysEnabled?'REVIEW_REMIND_AUTO_ONECLICK':'REVIEW_REMIND_AUTO', $conference->getPrimaryLocale(), false, $conference, $schedConf);
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
			$urlParams['key'] = $accessKeyManager->createKey('ReviewerContext', $reviewer->getId(), $reviewId, $keyLifetime);
		}
		$submissionReviewUrl = Request::url($conference->getPath(), $schedConf->getPath(), 'reviewer', 'submission', $reviewId, $urlParams);

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
			'conferenceUrl' => $conference->getUrl(),
			'schedConfUrl' => $schedConf->getUrl(),
			'reviewerPassword' => $reviewer->getPassword(),
			'reviewDueDate' => $reviewDueDate,
			'weekLaterDate' => strftime(Config::getVar('general', 'date_format_short'), strtotime('+1 week')),
			'editorialContactSignature' => $contactName . "\n" . $schedConf->getFullTitle(),
			'passwordResetUrl' => Request::url($conference->getPath(), $schedConf->getPath(), 'login', 'resetPassword', $reviewer->getUsername(), array('confirm' => Validation::generatePasswordResetHash($reviewer->getId()))),
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

		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$paperDao =& DAORegistry::getDAO('PaperDAO');
		$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');
		$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');

		$incompleteAssignments =& $reviewAssignmentDao->getIncompleteReviewAssignments();
		foreach ($incompleteAssignments as $reviewAssignment) {
			// Fetch the Paper and the Sched Conf if necessary.
			if ($paper == null || $paper->getId() != $reviewAssignment->getPaperId()) {
				$paper =& $paperDao->getPaper($reviewAssignment->getPaperId());
				if ($schedConf == null || $schedConf->getId() != $paper->getSchedConfId()) {
					$schedConf =& $schedConfDao->getSchedConf($paper->getSchedConfId());

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
				if(!$conference || $schedConf->getConferenceId() != $conference->getId()) {
					$conference =& $conferenceDao->getConference($schedConf->getConferenceId());
				}
				$this->sendReminder ($reviewAssignment, $paper, $conference, $schedConf);
			}
		}
	}
}

?>
