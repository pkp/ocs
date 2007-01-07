<?php

/**
 * TimelineForm.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package eventDirector.form
 *
 * Form for creating and modifying event tracks.
 *
 * $Id$
 */

import('form.Form');

class TimelineForm extends Form {

	/**
	 * Constructor.
	 * @param $trackId int omit for a new track
	 */
	function TimelineForm() {
		parent::Form('eventDirector/timeline/timelineForm.tpl');

		$this->addCheck(new FormValidatorCustom($this, 'endDate', 'required', 'director.timeline.form.badEndDate',
			create_function('$endDate,$form',
			'return ($endDate > $form->getData(\'startDate\'));'),
			array(&$this)));

		$this->addCheck(new FormValidatorCustom($this, 'autoRemindAuthorsDays', 'required', 'director.timeline.form.badReminderDays',
			create_function('$autoRemindAuthorDays,$form',
			'if($form->getData(\'autoRemindAuthors\') == false) return true;
				return ($autoRemindAuthorDays >= 1 && $autoRemindAuthorDays <= 99) ? true : false;'),
			array(&$this)));
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$event =& Request::getEvent();
		import('event.EventConstants');
		$templateMgr = &TemplateManager::getManager();

		$templateMgr->assign('pageHierarchy', array(array(Request::url(null, null, 'eventDirector'), 'director.eventManagement')));
		$templateMgr->assign('helpTopicId','conference.managementPages.timeline');
		$templateMgr->assign('scheduledTasksEnabled', Config::getVar('general', 'scheduled_tasks'));

		$templateMgr->assign('showAbstractDueDate',!$event->getCollectPapersWithAbstracts());
		$templateMgr->assign('showPaperDueDate', $event->getAcceptPapers());
		$templateMgr->assign('showSubmissionDueDate', $event->getCollectPapersWithAbstracts());
		
		$templateMgr->assign('showAuthorSelfRegister', $event->getSetting('openRegAuthor') == true ? true:false);
		
		$defaultTimeZone = TimeZone::getDefaultTimeZone();
		
		$templateMgr->assign('defaultTimeZone', $defaultTimeZone);
		$templateMgr->assign('currentTimeDefaultTimeZone', TimeZone::formatLocalTime(null, null, $defaultTimeZone));
		
		$templateMgr->assign('yearOffsetFuture', EVENT_DATE_YEAR_OFFSET_FUTURE);

		parent::display();
	}
	
	/**
	 * Initialize form data from current settings.
	 */
	function initData() {
		$event =& Request::getEvent();

		$this->_data = array(
			'startDate' => $event->getStartDate(),
			'endDate' => $event->getEndDate(),

			'autoShowCFP' => $event->getAutoShowCFP(),
			'showCFPDate' => $event->getShowCFPDate(),
			
			'openRegAuthorDate' => $event->getSetting('openRegAuthorDate'),
			'closeRegAuthorDate' => $event->getSetting('closeRegAuthorDate'),

			'submissionState' => $event->getSubmissionState(),
			'publicationState' => $event->getPublicationState(),
			'registrationState' => $event->getRegistrationState(),

			'acceptSubmissionsDate' => $event->getAcceptSubmissionsDate(),
			'abstractDueDate' => $event->getAbstractDueDate(),
			'paperDueDate' => $event->getPaperDueDate(),

			'autoRemindAuthors' => $event->getAutoRemindAuthors(),
			'autoRemindAuthorsDays' => $event->getAutoRemindAuthorsDays(),
			'autoArchiveIncompleteSubmissions' => $event->getAutoArchiveIncompleteSubmissions(),
			
			'autoReleaseToParticipants' => $event->getAutoReleaseToParticipants(),
			'autoReleaseToParticipantsDate' => $event->getAutoReleaseToParticipantsDate(),

			'autoReleaseToPublic' => $event->getAutoReleaseToPublic(),
			'autoReleaseToPublicDate' => $event->getAutoReleaseToPublicDate(),
		);
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array(
			'submissionState', 'autoShowCFP',
			'autoAccept', 'autoRemindAuthors', 'autoRemindAuthorsDays',
			'autoArchiveIncompleteSubmissions', 'autoReleaseToParticipants',
			'autoReleaseToPublic', 'publicationState'));

		$this->_data['startDate'] = Request::getUserDateVar('startDate');
		$this->_data['endDate'] = Request::getUserDateVar('endDate');
		
		$this->_data['showCFPDate'] = Request::getUserDateVar('showCFPDate');
		$this->_data['acceptSubmissionsDate'] = Request::getUserDateVar('acceptSubmissionsDate');

		$this->_data['submissionDueDate'] = Request::getUserDateVar('submissionDueDate');
		$this->_data['abstractDueDate'] = Request::getUserDateVar('abstractDueDate');
		$this->_data['paperDueDate'] = Request::getUserDateVar('paperDueDate');

		$this->_data['autoReleaseToParticipantsDate'] = Request::getUserDateVar('autoReleaseToParticipantsDate');
		$this->_data['autoReleaseToPublicDate'] = Request::getUserDateVar('autoReleaseToPublicDate');

		$this->_data['openRegAuthorDate'] = Request::getUserDateVar('openRegAuthorDate');
		$this->_data['closeRegAuthorDate'] = Request::getUserDateVar('closeRegAuthorDate');
	}
	
	/**
	 * Save track.
	 */
	function execute() {
		$eventDao =& DAORegistry::getDao('EventDAO');
		$event = &Request::getEvent();
		
		import('conference.log.ConferenceLog');
		import('conference.log.ConferenceEventLogEntry');

		// Start date and end date
		if($event->getStartDate() != $this->_data['startDate'])
			$event->setStartDate($this->_data['startDate']);

		if($event->getEndDate() != $this->_data['endDate'])
			$event->setEndDate($this->_data['endDate']);
		
		// User registration dates
		if($event->getSetting('openRegAuthor')) {
			if($event->getSetting('openRegAuthorDate') != $this->_data['openRegAuthorDate']) {
				$event->updateSetting('openRegAuthorDate', $this->_data['openRegAuthorDate'], 'date');
			}

			if($event->getSetting('closeRegAuthorDate') != $this->_data['closeRegAuthorDate']) {
				$event->updateSetting('closeRegAuthorDate', $this->_data['closeRegAuthorDate'], 'date');
			}
		}
			
		// CFP logic
		if($event->getSubmissionState() != $this->_data['submissionState']) {
			ConferenceLog::logEvent(
				$event->getConferenceId(),
				$event->getEventId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.submissionStateChanged',
				array('oldSubmissionState' => $event->getSubmissionState(),
					'newSubmissionState' => $this->_data['submissionState']));

			$event->setSubmissionState($this->_data['submissionState']);
		}

		if($event->getSubmissionState() == SUBMISSION_STATE_NOTYET) {
			// These are disabled if the submission state is anything else. Don't
			// clobber them.
			if($event->getAutoShowCFP() != $this->_data['autoShowCFP'])
				$event->setAutoShowCFP($this->_data['autoShowCFP']);
			if($event->getShowCFPDate() != $this->_data['showCFPDate'])
				$event->setShowCFPDate($this->_data['showCFPDate']);
			if($event->getAcceptSubmissionsDate() != $this->_data['acceptSubmissionsDate'])
				$event->setAcceptSubmissionsDate($this->_data['acceptSubmissionsDate']);
		}

		// Depending on the review model, not all of these may be populated. Even
		// though clobbering isn't a problem unless the review model is changed,
		// it can be annoying to users expecting irrelevant data to be preserved.		
		if($event->getCollectPapersWithAbstracts()) {
			if($event->getAbstractDueDate() != $this->_data['submissionDueDate']) {
				$event->setAbstractDueDate($this->_data['submissionDueDate']);
				$event->setPaperDueDate($this->_data['submissionDueDate']);
			}
		} else {
			if($event->getAbstractDueDate() != $this->_data['abstractDueDate'])
				$event->setAbstractDueDate($this->_data['abstractDueDate']);
			if($event->getAcceptPapers()) {
				if($event->getPaperDueDate() != $this->_data['paperDueDate'])
					$event->setPaperDueDate($this->_data['paperDueDate']);
			}
		}

		// Incomplete submissions logic
		// paperDueDate is taken care of above
		if($event->getAutoRemindAuthors() != $this->_data['autoRemindAuthors']) {
			$event->setAutoRemindAuthors($this->_data['autoRemindAuthors']);
			$event->setAutoRemindAuthorsDays($this->_data['autoRemindAuthorsDays']);
		}
		if($event->getAutoArchiveIncompleteSubmissions() != $this->_data['autoArchiveIncompleteSubmissions'])
			$event->setAutoArchiveIncompleteSubmissions($this->_data['autoArchiveIncompleteSubmissions']);
			
		// Publication state logic
		if($event->getPublicationState() != $this->_data['publicationState']) {
			ConferenceLog::logEvent(
				$event->getConferenceId(),
				$event->getEventId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.publicationStateChanged',
				array('oldPublicationState' => $event->getPublicationState(),
					'newPublicationState' => $this->_data['publicationState']));

			$event->setPublicationState($this->_data['publicationState']);
		}

		if($event->getAutoReleaseToParticipants() != $this->_data['autoReleaseToParticipants'])
			$event->setAutoReleaseToParticipants($this->_data['autoReleaseToParticipants']);
		if($event->getAutoReleaseToParticipants() && $event->getAutoReleaseToParticipantsDate() != $this->_data['autoReleaseToParticipantsDate'])
			$event->setAutoReleaseToParticipantsDate($this->_data['autoReleaseToParticipantsDate']);
		
		if($event->getAutoReleaseToPublic() != $this->_data['autoReleaseToPublic'])
			$event->setAutoReleaseToPublic($this->_data['autoReleaseToPublic']);
		if($event->getAutoReleaseToPublic() && $event->getAutoReleaseToPublicDate() != $this->_data['autoReleaseToPublicDate'])
			$event->setAutoReleaseToPublicDate($this->_data['autoReleaseToPublicDate']);

		$eventDao->updateEvent($event);
	}
	
}

?>
