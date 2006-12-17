<?php

/**
 * RegistrationExpiryReminder.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Class to perform automated reminders for reviewers.
 *
 * $Id$
 */

import('scheduledTask.ScheduledTask');

class RegistrationExpiryReminder extends ScheduledTask {

	/**
	 * Constructor.
	 */
	function RegistrationExpiryReminder() {
		$this->ScheduledTask();
	}

	function sendReminder ($registration, $conference, $event, $emailKey) {

		$userDao = &DAORegistry::getDAO('UserDAO');
		$registrationTypeDao = &DAORegistry::getDAO('RegistrationTypeDAO');

		$eventName = $event->getTitle();
		$eventId = $event->getEventId();
		$user = &$userDao->getUser($registration->getUserId());
		if (!isset($user)) return false;

		$registrationType = &$registrationTypeDao->getRegistrationType($registration->getTypeId());

		$registrationName = $event->getSetting('registrationName', true);
		$registrationEmail = $event->getSetting('registrationEmail', true);
		$registrationPhone = $event->getSetting('registrationPhone', true);
		$registrationFax = $event->getSetting('registrationFax', true);
		$registrationMailingAddress = $event->getSetting('registrationMailingAddress', true);

		$registrationContactSignature = $registrationName;

		if ($registrationMailingAddress != '') {
			$registrationContactSignature .= "\n" . $registrationMailingAddress;
		}
		if ($registrationPhone != '') {
			$registrationContactSignature .= "\n" . Locale::Translate('user.phone') . ': ' . $registrationPhone;
		}
		if ($registrationFax != '') {
			$registrationContactSignature .= "\n" . Locale::Translate('user.fax') . ': ' . $registrationFax;
		}

		$registrationContactSignature .= "\n" . Locale::Translate('user.email') . ': ' . $registrationEmail;

		$paramArray = array(
			'subscriberName' => $user->getFullName(),
			'conferenceName' => $conferenceName,
			'eventName' => $eventName,
			'registrationType' => $registrationType->getSummaryString(),
			'expiryDate' => $registration->getDateEnd(),
			'username' => $user->getUsername(),
			'registrationContactSignature' => $registrationContactSignature 
		);

		import('mail.MailTemplate');
		$mail = &new MailTemplate($emailKey);
		$mail->setFrom($registrationEmail, $registrationName);
		$mail->addRecipient($user->getEmail(), $user->getFullName());
		$mail->setSubject($mail->getSubject($conference->getLocale()));
		$mail->setBody($mail->getBody($conference->getLocale()));
		$mail->assignParams($paramArray);
		$mail->send();
	}

	function sendEventReminders ($conference, $event, $curDate) {

		// Only send reminders if registration are enabled
		if ($event->getSetting('enableRegistration', true)) {

			$curYear = $curDate['year'];
			$curMonth = $curDate['month'];
			$curDay = $curDate['day'];
			
			// Check if expiry notification before months is enabled
			if ($event->getSetting('enableRegistrationExpiryReminderBeforeMonths', true)) {

				$beforeMonths = $event->getSetting('numMonthsBeforeRegistrationExpiryReminder', true);
				$beforeYears = (int)floor($beforeMonths/12);
				$beforeMonths = (int)fmod($beforeMonths,12);

				$expiryYear = $curYear + $beforeYears + (int)floor(($curMonth+$beforeMonths)/12);
				$expiryMonth = (int)fmod($curMonth+$beforeMonths,12);
				$expiryDay = $curDay;

				// Retrieve all registration that match expiry date
				$registrationDao = &DAORegistry::getDAO('RegistrationDAO');
				$dateEnd = $expiryYear . '-' . $expiryMonth . '-' . $expiryDay;
				$registration = &$registrationDao->getRegistrationByDateEnd($dateEnd, $event->getEventId()); 

				while (!$registration->eof()) {
					$registration = &$registration->next();
					$this->sendReminder($registration, $conference, $event, 'SUBSCRIPTION_BEFORE_EXPIRY');
				}
			}

			// Check if expiry notification before weeks is enabled
			if ($event->getSetting('enableRegistrationExpiryReminderBeforeWeeks', true)) {

				$beforeWeeks = $event->getSetting('numWeeksBeforeRegistrationExpiryReminder', true);
				$beforeDays = $beforeWeeks * 7;

				$expiryMonth = $curMonth + (int)floor(($curDay+$beforeDays)/31);
				$expiryYear = $curYear + (int)floor($expiryMonth/12);
				$expiryDay = (int)fmod($curDay+$beforeDays,31);
				$expiryMonth = (int)fmod($expiryMonth,12);				

				// Retrieve all registration that match expiry date
				$registrationDao = &DAORegistry::getDAO('RegistrationDAO');
				$dateEnd = $expiryYear . '-' . $expiryMonth . '-' . $expiryDay;
				$registration = &$registrationDao->getRegistrationByDateEnd($dateEnd, $event->getEventId()); 

				while (!$registration->eof()) {
					$registration = &$registration->next();
					$this->sendReminder($registration, $conference, $event, 'SUBSCRIPTION_BEFORE_EXPIRY');
				}
			}

			// Check if expiry notification after months is enabled
			if ($event->getSetting('enableRegistrationExpiryReminderAfterMonths')) {

				$afterMonths = $event->getSetting('numMonthsAfterRegistrationExpiryReminder', true);
				$afterYears = (int)floor($afterMonths/12);
				$afterMonths = (int)fmod($afterMonths,12);

				if (($curMonth - $afterMonths) <= 0) {
					$afterYears++;
					$expiryMonth = 12 + ($curMonth - $afterMonths);
				} else {
					$expiryMonth = $curMonth - $afterMonths;
				}

				$expiryYear = $curYear - $afterYears;
				$expiryDay = $curDay;

				// Retrieve all registration that match expiry date
				$registrationDao = &DAORegistry::getDAO('RegistrationDAO');
				$dateEnd = $expiryYear . '-' . $expiryMonth . '-' . $expiryDay;
				$registration = &$registrationDao->getRegistrationByDateEnd($dateEnd, $event->getEventId()); 

				while (!$registration->eof()) {
					$registration = &$registration->next();
					// Ensure that user does not have another, valid registration
					if (!$registrationDao->isValidRegistrationByUser($registration->getUserId(), $event->getEventId())) {
						$this->sendReminder($registration, $conference, $event, 'SUBSCRIPTION_AFTER_EXPIRY_LAST');
					}
				}
			}

			// Check if expiry notification after weeks is enabled
			if ($event->getSetting('enableRegistrationExpiryReminderAfterWeeks', true)) {

				$afterWeeks = $event->getSetting('numWeeksAfterRegistrationExpiryReminder', true);
				$afterDays = $afterWeeks * 7;

				if (($curDay - $afterDays) <= 0) {
					$afterMonths = 1;
					$expiryDay = 31 + ($curDay - $afterDays);
				} else {
					$afterMonths = 0;
					$expiryDay = $curDay - $afterDays;
				}

				if (($curMonth - $afterMonths) == 0) {
					$afterYears = 1;
					$expiryMonth = 12;
				} else {
					$afterYears = 0;
					$expiryMonth = $curMonth - $afterMonths;
				}

				$expiryYear = $curYear - $afterYears;

				// Retrieve all registration that match expiry date
				$registrationDao = &DAORegistry::getDAO('RegistrationDAO');
				$dateEnd = $expiryYear . '-' . $expiryMonth . '-' . $expiryDay;
				$registration = &$registrationDao->getRegistrationByDateEnd($dateEnd, $event->getEventId()); 

				while (!$registration->eof()) {
					$registration = &$registration->next();
					// Ensure that user does not have another, valid registration
					if (!$registrationDao->isValidRegistrationByUser($registration->getUserId(), $event->getEventId())) {
						$this->sendReminder($registration, $conference, $event, 'SUBSCRIPTION_AFTER_EXPIRY');
					}
				}
			}
		}
	}

	function execute() {
		$eventDao = &DAORegistry::getDAO('EventDAO');
		$conferenceDao = &DAORegistry::getDAO('ConferenceDAO');
		$events = &$eventDao->getEnabledEvents();
		$conference = null;

		$todayDate = array(
						'year' => date('Y'),
						'month' => date('n'),
						'day' => date('j')
					);

		while (!$events->eof()) {
			$event = &$events->next();

			if(!$conference || $event->getConferenceId() != $conference->getConferenceId()) {
				$conference = &$conferenceDao->getConference($event->getConferenceId());
			}

			// Send reminders based on current date
			$this->sendEventReminders($event, $conference, $todayDate);
			unset($event);
		}

		// If it is the first day of a month but previous month had only
		// 30 days then simulate 31st day for expiry dates that end on
		// that day.
		$shortMonths = array(2,4,6,8,10,12);

		if (($todayDate['day'] == 1) && in_array(($todayDate['month'] - 1), $shortMonths)) {

			$curDate['day'] = 31;
			$curDate['month'] = $todayDate['month'] - 1;

			if ($curDate['month'] == 12) {
				$curDate['year'] = $todayDate['year'] - 1;
			} else {
				$curDate['year'] = $todayDate['year'];
			}

			$events = &$eventDao->getEnabledEvents();
			
			while (!$events->eof()) {
				$event = &$events->next();

				if(!$conference || $event->getConferenceId() != $conference->getConferenceId()) {
					$conference = &$conferenceDao->getConference($event->getConferenceId());
				}
				
				// Send reminders for simulated 31st day of short month
				$this->sendEventReminders($event, $conference, $curDate);
				unset($event);
			}
		}

		// If it is the first day of March, simulate 29th and 30th days for February
		// or just the 30th day in a leap year.
		if (($todayDate['day'] == 1) && ($todayDate['month'] == 3)) {

			$curDate['day'] = 30;
			$curDate['month'] = 2;
			$curDate['year'] = $todayDate['year'];

			$events = &$eventDao->getEnabledEvents();

			while (!$events->eof()) {
				$event = &$events->next();

				if(!$conference || $event->getConferenceId() != $conference->getConferenceId()) {
					$conference = &$conferenceDao->getConference($event->getConferenceId());
				}
				
				// Send reminders for simulated 30th day of February
				$this->sendEventReminders($event, $conference, $curDate);
				unset($event);
			}

			// Check if it's a leap year
			if (date("L", mktime(0,0,0,0,0,$curDate['year'])) != '1') {

				$curDate['day'] = 29;

				$events = &$eventDao->getEnabledEvents();

				while (!$events->eof()) {
					$event = &$events->next();

					if(!$conference || $event->getConferenceId() != $conference->getConferenceId()) {
						$conference = &$conferenceDao->getConference($event->getConferenceId());
					}

					// Send reminders for simulated 29th day of February
					$this->sendEventReminders($event, $conference, $curDate);
					unset($event);
				}
			}
		}
	}
}

?>
