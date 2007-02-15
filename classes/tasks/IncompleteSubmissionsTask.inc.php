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
import('paper.log.PaperLog');
import('paper.log.PaperEventLogEntry');

class IncompleteSubmissionsTask extends ScheduledTask {

	/**
	 * Constructor.
	 */
	function IncompleteSubmissionsTask() {
		$this->ScheduledTask();
	}

	function sendReminder ($submission, $conference, $schedConf) {
		import('mail.PaperMailTemplate');

		$presenters = $submission->getPresenters();
		$paperId = $submission->getPaperId();

		$email = &new PaperMailTemplate($submission, 'SUBMISSION_DEADLINE_WARN', null, false, $conference);
		$email->setConference($conference);
		$email->setSchedConf($schedConf);
		$email->setFrom($schedConf->getSetting('contactEmail', true), $schedConf->getSetting('contactName', true));
		foreach($presenters as $presenter) {
			$email->addRecipient($presenter->getEmail(), $presenter->getFullName());
		}
		$email->setAssoc(PAPER_EMAIL_DIRECTOR_NOTIFY_PRESENTER_EXPIRED, PAPER_EMAIL_TYPE_DIRECTOR, $paperId);

		$email->setSubject($email->getSubject($conference->getLocale()));
		$email->setBody($email->getBody($conference->getLocale()));

		$urlParams = array();
		$submissionReviewUrl = Request::url($conference->getPath(), $schedConf->getPath(), 'presenter', 'submission', $paperId, $urlParams);

		$paramArray = array(
			'presenterNames' => $submission->getPresenterString(),
			'editorialContactSignature' => $schedConf->getSetting('contactName', true) . "\n" . $schedConf->getFullTitle(),
			'submissionUrl' => Request::url(null, null, 'presenter', 'submission', $submission->getPaperId()),
			'paperTitle' => $submission->getTitle(),
			'conferenceName' => $conference->getTitle(),
			'schedConfName' => $schedConf->getTitle()
		);
		$email->assignParams($paramArray);

		$email->send();
	}

	function execute() {
		$paper = null;
		$conference = null;
		$schedConf = null;

		$presenterSubmissionDao = &DAORegistry::getDAO('PresenterSubmissionDAO');
		$paperDao = &DAORegistry::getDAO('PaperDAO');
		$conferenceDao = &DAORegistry::getDAO('ConferenceDAO');
		$schedConfDao = &DAORegistry::getDAO('SchedConfDAO');

		$incompleteSubmissions = &$presenterSubmissionDao->getIncompleteSubmissions();
		foreach ($incompleteSubmissions as $incompleteSubmission) {

			// Fetch the Scheduled Conference if possible.
			$schedConfId = $incompleteSubmission->getSchedConfId();
			if($schedConfId) {
				$schedConf = $schedConfDao->getSchedConf($schedConfId);
				$conference = &$schedConf->getConference();
			}
/*			
			// Check hard deadlines and reminder dates
			if(isset($schedConf) && isset($conference)) {
				$reviewModel = $schedConf->getReviewModel();

				$paperReminderEnabled = $schedConf->getAutoRemindPresenters();
				$paperReminderDays = $schedConf->getAutoRemindPresentersDays();

				$dueDate = $schedConf->getSetting('submissionsCloseDate');
				$reminderDate = strtotime("-" . (int) $paperReminderDays . " days", $dueDate);

				// If paper reminder is overdue...
				if($paperReminderEnabled && time() > $reminderDate) {
					$this->sendReminder ($incompleteSubmission, $conference, $schedConf);
					$incompleteSubmission->setDateReminded(Core::getCurrentDate());
					$paperDao->updatePaper($incompleteSubmission);

				}
				
				// If paper is overdue...
				if($dueDate !== null && time() > $dueDate) {

					PaperLog::logEvent($incompleteSubmission->getPaperId(), PAPER_LOG_PAPER_EXPIRE, LOG_TYPE_DEFAULT, 0, 'log.default.paperPeriodExpired', array('paperId' => $incompleteSubmission->getPaperId()));

					$incompleteSubmission->setStatus(SUBMISSION_STATUS_EXPIRED);
					$paperDao->updatePaper($incompleteSubmission);
				}
			}*/
		}
	}
}

?>
