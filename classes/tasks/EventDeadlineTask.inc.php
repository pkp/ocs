<?php

/**
 * EventDeadlineTask.inc.php
 *
 * Copyright (c) 2006-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Class to shift event status after a deadline (submission, etc) has passed.
 *
 * $Id$
 */

import('scheduledTask.ScheduledTask');

class EventDeadlineTask extends ScheduledTask {

	/**
	 * Constructor.
	 */
	function EventDeadlineTask() {
		$this->ScheduledTask();
	}

	function execute() {

		$time = time();
		
		// For each enabled event, check deadlines.
		$eventDao = &DAORegistry::getDAO('EventDAO');
		$enabledEvents =& $eventDao->getEnabledEvents();

		$events =& $enabledEvents->toArray();
		foreach($events as $event) {

			//
			// Submission state
			//
			
			$submissionState = $event->getSubmissionState();
			if($submissionState == SUBMISSION_STATE_ACCEPT) {
				$abstractDueDate = $event->getAbstractDueDate();
				if($time > $abstractDueDate) {
					// The submission deadline has passed.

					// ...process expired 
					
					// Log the event
					import('conference.log.ConferenceLog');
					import('conference.log.ConferenceEventLogEntry');
					ConferenceLog::logEvent(
						$event->getConferenceId(),
						$event->getEventId(),
						CONFERENCE_LOG_DEADLINE,
						LOG_TYPE_DEFAULT,
						0, 'log.deadline.submissionDeadlinePassed');

					// Update submission status.
					$event->setSubmissionState(SUBMISSION_STATE_CLOSED);
				}
			}
			
			//
			// Publication state
			//
			
			$publicationState = $event->getPublicationState();
			if($publicationState == PUBLICATION_STATE_NOTYET) {
				$internalPublicationDate = $event->getAutoReleaseToParticipantsDate();
				if($time > $internalPublicationDate && $event->getAutoReleaseToParticipants()) {
					// Release contents to conference participants.

					// ...
					
					// Log the event
					import('conference.log.ConferenceLog');
					import('conference.log.ConferenceEventLogEntry');
					ConferenceLog::logEvent(
						$event->getConferenceId(),
						$event->getEventId(),
						CONFERENCE_LOG_DEADLINE,
						LOG_TYPE_DEFAULT,
						0, 'log.deadline.proceedingsReleasedToParticipants');

					// Update submission status.
					$event->setPublicationState(PUBLICATION_STATE_PARTICIPANTS);
				}
			}
			
			// Note that this may immediately follow the past "if" statement if both
			// deadlines have passed simultaneously.
			
			if($publicationState == PUBLICATION_STATE_PARTICIPANTS) {
				$publicPublicationDate = $event->getAutoReleaseToPublicDate();
				if($time > $publicPublicationDate && $event->getAutoReleaseToPublic()) {
					// Release contents to the general public.

					// ...

					// Log the event
					import('conference.log.ConferenceLog');
					import('conference.log.ConferenceEventLogEntry');
					ConferenceLog::logEvent(
						$event->getConferenceId(),
						$event->getEventId(),
						CONFERENCE_LOG_DEADLINE,
						LOG_TYPE_DEFAULT,
						0, 'log.deadline.proceedingsReleasedToPublic');
					
					// Update submission status.
					$event->setPublicationState(PUBLICATION_STATE_PUBLIC);
				}
			}
		}
	}
}

?>
