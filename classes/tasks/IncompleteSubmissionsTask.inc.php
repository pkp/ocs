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

	function sendReminder ($submission, $conference, $event) {
		import('mail.PaperMailTemplate');

		$authors = $submission->getAuthors();
		$paperId = $submission->getPaperId();

		$email = &new PaperMailTemplate($submission, 'SUBMISSION_DEADLINE_WARN', null, false, $conference);
		$email->setConference($conference);
		$email->setEvent($event);
		$email->setFrom($event->getSetting('contactEmail', true), $event->getSetting('contactName', true));
		foreach($authors as $author) {
			$email->addRecipient($author->getEmail(), $author->getFullName());
		}
		$email->setAssoc(PAPER_EMAIL_EDITOR_NOTIFY_AUTHOR_EXPIRED, PAPER_EMAIL_TYPE_EDITOR, $paperId);

		$email->setSubject($email->getSubject($conference->getLocale()));
		$email->setBody($email->getBody($conference->getLocale()));

		$urlParams = array();
		$submissionReviewUrl = Request::url($conference->getPath(), $event->getPath(), 'author', 'submission', $paperId, $urlParams);

		$paramArray = array(
			'authorNames' => $submission->getAuthorString(),
			'editorialContactSignature' => $event->getSetting('contactName', true) . "\n" . $event->getFullTitle(),
			'submissionUrl' => Request::url(null, null, 'author', 'submission', $submission->getPaperId()),
			'paperTitle' => $submission->getTitle(),
			'conferenceName' => $conference->getTitle(),
			'eventName' => $event->getTitle()
		);
		$email->assignParams($paramArray);

		$email->send();
	}

	function execute() {
		$paper = null;
		$conference = null;
		$event = null;

		$authorSubmissionDao = &DAORegistry::getDAO('AuthorSubmissionDAO');
		$paperDao = &DAORegistry::getDAO('PaperDAO');
		$conferenceDao = &DAORegistry::getDAO('ConferenceDAO');
		$eventDao = &DAORegistry::getDAO('EventDAO');

		$incompleteSubmissions = &$authorSubmissionDao->getIncompleteSubmissions();
		foreach ($incompleteSubmissions as $incompleteSubmission) {

			// Fetch the Event if possible.
			$eventId = $incompleteSubmission->getEventId();
			if($eventId) {
				$event = $eventDao->getEvent($eventId);
				$conference = &$event->getConference();
			}
			
			// Check hard deadlines and reminder dates
			if(isset($event) && isset($conference)) {
				$reviewModel = $event->getReviewModel();

				$paperReminderEnabled = $event->getAutoRemindAuthors();
				$paperReminderDays = $event->getAutoRemindAuthorsDays();

				$dueDate = $event->getPaperDueDate();
				$reminderDate = strtotime("-" . (int) $paperReminderDays . " days", $dueDate);

				// If paper reminder is overdue...
				if($paperReminderEnabled && time() > $reminderDate) {
					$this->sendReminder ($incompleteSubmission, $conference, $event);
					$incompleteSubmission->setDateReminded(Core::getCurrentDate());
					$paperDao->updatePaper($incompleteSubmission);

				}
				
				// If paper is overdue...
				if($dueDate !== null && time() > $dueDate) {

					PaperLog::logEvent($incompleteSubmission->getPaperId(), PAPER_LOG_PAPER_EXPIRE, LOG_TYPE_DEFAULT, 0, 'log.default.paperPeriodExpired', array('paperId' => $incompleteSubmission->getPaperId()));

					$incompleteSubmission->setStatus(SUBMISSION_STATUS_EXPIRED);
					$paperDao->updatePaper($incompleteSubmission);
				}
			}
		}
	}
}

?>
