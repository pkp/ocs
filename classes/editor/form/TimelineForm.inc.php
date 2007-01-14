<?php

/**
 * TimelineForm.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package director.form
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
		parent::Form('editor/timeline/timelineForm.tpl');

		/*$this->addCheck(new FormValidatorCustom($this, 'endDate', 'required', 'director.timeline.form.badEndDate',
			create_function('$endDate,$form',
			'return ($endDate > $form->getData(\'startDate\'));'),
			array(&$this)));

		$this->addCheck(new FormValidatorCustom($this, 'autoRemindAuthorsDays', 'required', 'director.timeline.form.badReminderDays',
			create_function('$autoRemindAuthorDays,$form',
			'if($form->getData(\'autoRemindAuthors\') == false) return true;
				return ($autoRemindAuthorDays >= 1 && $autoRemindAuthorDays <= 99) ? true : false;'),
			array(&$this)));*/
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$event =& Request::getEvent();
		$templateMgr = &TemplateManager::getManager();

		$templateMgr->assign('pageHierarchy', array(array(Request::url(null, null, 'editor'), 'user.role.editor')));
		$templateMgr->assign('helpTopicId','conference.managementPages.timeline');

		$templateMgr->assign('yearOffsetFuture', EVENT_DATE_YEAR_OFFSET_FUTURE);

		parent::display();
	}
	
	/**
	 * Initialize form data from current settings.
	 */
	function initData() {
		$event =& Request::getEvent();

		$this->_data = array(
			'siteStartDate' => $event->getStartDate(),
			'siteEndDate' => $event->getEndDate(),

			'startDate' => $event->getSetting('startDate'),
			'endDate' => $event->getSetting('endDate'),

			'regAuthorOpenDate' => $event->getSetting('regAuthorOpenDate'),
			'regAuthorCloseDate' => $event->getSetting('regAuthorCloseDate'),
			'showCFPDate' => $event->getSetting('showCFPDate'),
			'proposalsOpenDate' => $event->getSetting('proposalsOpenDate'),
			'proposalsCloseDate' => $event->getSetting('proposalsCloseDate'),
			'submissionsCloseDate' => $event->getSetting('submissionsCloseDate'),
			
			'regReviewerOpenDate' => $event->getSetting('regReviewerOpenDate'),
			'regReviewerCloseDate' => $event->getSetting('regReviewerCloseDate'),
			'closeReviewProcessDate' => $event->getSetting('closeReviewProcessDate'),
			'secondRoundDueDate' => $event->getSetting('secondRoundDueDate'),

			'regRegistrantOpenDate' => $event->getSetting('regRegistrantOpenDate'),
			'regRegistrantCloseDate' => $event->getSetting('regRegistrantCloseDate'),

			//'postPresentations' => $event->getSetting('postPresentations'),
			//'postPresentationsDate' => $event->getSetting('postPresentationsDate'),
			'postAbstracts' => $event->getSetting('postAbstracts'),
			'postAbstractsDate' => $event->getSetting('postAbstractsDate'),
			'postPapers' => $event->getSetting('postPapers'),
			'postPapersDate' => $event->getSetting('postPapersDate'),
			'delayOpenAccess' => $event->getSetting('delayOpenAccess'),
			'delayOpenAccessDate' => $event->getSetting('delayOpenAccessDate'),
			'closeComments' => $event->getSetting('closeComments'),
			'closeCommentsDate' => $event->getSetting('closeCommentsDate')
		);
		
		if($event->getSetting('collectPapersWithAbstracts')) {
			$this->_data['showSubmissionsOpenDate'] = true;
			$this->_data['showSubmissionsCloseDate'] = true;
		} else {
			$this->_data['showProposalsOpenDate'] = true;
			$this->_data['showProposalsCloseDate'] = true;
			if ($event->getSetting('acceptPapers')) {
				$this->_data['showSubmissionsCloseDate'] = true;
			}
		}
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserDateVars(array(
			'siteStartDate', 'siteEndDate',
			'startDate', 'endDate',
			'regAuthorOpenDate', 'regAuthorCloseDate',
			'showCFPDate',
			'proposalsOpenDate', 'proposalsCloseDate',
			'submissionsOpenDate', 'submissionsCloseDate',
			'regReviewerOpenDate', 'regReviewerCloseDate', 'closeReviewProcessDate',
			'regRegistrantOpenDate', 'regRegistrantCloseDate',
			//'postPresentationsDate',
			'postAbstractsDate',
			'postPapersDate',
			'delayOpenAccessDate',
			'closeCommentsDate'
		));

		$this->readUserVars(array(
			//'postPresentations',
			'postAbstracts',
			'postPapers',
			'delayOpenAccess',
			'closeComments'
		));
	}
	
	/**
	 * Save track.
	 */
	function execute() {
		$eventDao =& DAORegistry::getDao('EventDAO');
		$event = &Request::getEvent();
		
		import('conference.log.ConferenceLog');
		import('conference.log.ConferenceEventLogEntry');

		//
		// Don't log these, since they aren't particularly nefarious.
		//
		
		// Website start date and end date.
		
		if($event->getStartDate() != $this->_data['siteStartDate'])
			$event->setStartDate($this->_data['siteStartDate']);

		if($event->getEndDate() != $this->_data['siteEndDate'])
			$event->setEndDate($this->_data['siteEndDate']);
		
		//
		// Log the rest.
		//
		
		// Physical event start date and end date
		if($event->getSetting('startDate') != $this->_data['startDate']) {
			ConferenceLog::logEvent(
				$event->getConferenceId(),
				$event->getEventId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.startDateChanged',
				array('oldStartDate' => $event->getSetting('startDate'),
					'newStartDate' => $this->_data['startDate']));
			$event->updateSetting('startDate', $this->_data['startDate'], 'date');
		}

		if($event->getSetting('endDate') != $this->_data['endDate']) {
			ConferenceLog::logEvent(
				$event->getConferenceId(),
				$event->getEventId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.endDateChanged',
				array('oldEndDate' => $event->getSetting('endDate'),
					'newEndDate' => $this->_data['endDate']));
			$event->updateSetting('endDate', $this->_data['endDate'], 'date');
		}

		if($event->getSetting('regAuthorOpenDate') != $this->_data['regAuthorOpenDate']) {
			ConferenceLog::logEvent(
				$event->getConferenceId(),
				$event->getEventId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.regAuthorOpenDateChanged',
				array('oldRegAuthorOpenDate' => $event->getSetting('regAuthorOpenDate'),
					'newRegAuthorOpenDate' => $this->_data['regAuthorOpenDate']));
			$event->updateSetting('regAuthorOpenDate', $this->_data['regAuthorOpenDate'], 'date');
		}
		if($event->getSetting('regAuthorCloseDate') != $this->_data['regAuthorCloseDate']) {
			ConferenceLog::logEvent(
				$event->getConferenceId(),
				$event->getEventId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.regAuthorCloseDateChanged',
				array('oldRegAuthorCloseDate' => $event->getSetting('regAuthorCloseDate'),
					'newRegAuthorCloseDate' => $this->_data['regAuthorCloseDate']));
			$event->updateSetting('regAuthorCloseDate', $this->_data['regAuthorCloseDate'], 'date');
		}
		if($event->getSetting('showCFPDate') != $this->_data['showCFPDate']) {
			ConferenceLog::logEvent(
				$event->getConferenceId(),
				$event->getEventId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.showCFPDateChanged',
				array('oldShowCFPDate' => $event->getSetting('showCFPDate'),
					'newShowCFPDate' => $this->_data['showCFPDate']));
			$event->updateSetting('showCFPDate', $this->_data['showCFPDate'], 'date');
		}

		// Abstract and submission due dates depend on the submission and review
		// model, so they're not quite as straightforward as the rest.
		
		if($event->getSetting('collectPapersWithAbstracts')) {
			$proposalsOpenDate = $submissionsOpenDate = $this->_data['submissionsOpenDate'];
			$proposalsCloseDate = $submissionsCloseDate = $this->_data['submissionsCloseDate'];
		} else {
			$proposalsOpenDate = $submissionsOpenDate = $this->_data['proposalsOpenDate'];
			$proposalsCloseDate = $this->_data['proposalsCloseDate'];
			if ($event->getSetting('acceptPapers')) {
				$submissionsCloseDate = $this->_data['submissionsCloseDate'];
			} else {
				$submissionsCloseDate = $proposalsCloseDate;
			}
		}

		if($event->getSetting('proposalsOpenDate') != $proposalsOpenDate) {
			ConferenceLog::logEvent(
				$event->getConferenceId(),
				$event->getEventId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.proposalsOpenDateChanged',
				array('oldProposalsOpenDate' => $event->getSetting('proposalsOpenDate'),
					'newProposalsOpenDate' => $proposalsOpenDate));
			$event->updateSetting('proposalsOpenDate', $proposalsOpenDate, 'date');
		}
		if($event->getSetting('proposalsCloseDate') != $proposalsCloseDate) {
			ConferenceLog::logEvent(
				$event->getConferenceId(),
				$event->getEventId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.proposalsCloseDateChanged',
				array('oldProposalsCloseDate' => $event->getSetting('proposalsCloseDate'),
					'newProposalsCloseDate' => $proposalsCloseDate));
			$event->updateSetting('proposalsCloseDate', $proposalsCloseDate, 'date');
		}
		if($event->getSetting('submissionsOpenDate') != $submissionsOpenDate) {
			ConferenceLog::logEvent(
				$event->getConferenceId(),
				$event->getEventId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.submissionsOpenDateChanged',
				array('oldSubmissionsOpenDate' => $event->getSetting('submissionsOpenDate'),
					'newSubmissionsOpenDate' => $submissionsOpenDate));
			$event->updateSetting('submissionsOpenDate', $submissionsOpenDate, 'date');
		}
		if($event->getSetting('submissionsCloseDate') != $submissionsCloseDate) {
			ConferenceLog::logEvent(
				$event->getConferenceId(),
				$event->getEventId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.submissionsCloseDateChanged',
				array('oldSubmissionsCloseDate' => $event->getSetting('submissionsCloseDate'),
					'newSubmissionsCloseDate' => $submissionsCloseDate));
			$event->updateSetting('submissionsCloseDate', $submissionsCloseDate, 'date');
		}
		if($event->getSetting('regReviewerOpenDate') != $this->_data['regReviewerOpenDate']) {
			ConferenceLog::logEvent(
				$event->getConferenceId(),
				$event->getEventId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.regReviewerOpenDateChanged',
				array('oldRegReviewerOpenDate' => $event->getSetting('regReviewerOpenDate'),
					'newRegReviewerOpenDate' => $this->_data['regReviewerOpenDate']));
			$event->updateSetting('regReviewerOpenDate', $this->_data['regReviewerOpenDate'], 'date');
		}
		if($event->getSetting('regReviewerCloseDate') != $this->_data['regReviewerCloseDate']) {
			ConferenceLog::logEvent(
				$event->getConferenceId(),
				$event->getEventId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.regReviewerCloseDateChanged',
				array('oldRegReviewerCloseDate' => $event->getSetting('regReviewerCloseDate'),
					'newRegReviewerCloseDate' => $this->_data['regReviewerCloseDate']));
			$event->updateSetting('regReviewerCloseDate', $this->_data['regReviewerCloseDate'], 'date');
		}
		if($event->getSetting('closeReviewProcessDate') != $this->_data['closeReviewProcessDate']) {
			ConferenceLog::logEvent(
				$event->getConferenceId(),
				$event->getEventId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.closeReviewProcessDateChanged',
				array('oldCloseReviewProcessDate' => $event->getSetting('closeReviewProcessDate'),
					'newCloseReviewProcessDate' => $this->_data['closeReviewProcessDate']));
			$event->updateSetting('closeReviewProcessDate', $this->_data['closeReviewProcessDate'], 'date');
		}
		if($event->getSetting('regRegistrantOpenDate') != $this->_data['regRegistrantOpenDate']) {
			ConferenceLog::logEvent(
				$event->getConferenceId(),
				$event->getEventId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.regRegistrantOpenDateChanged',
				array('oldRegRegistrantOpenDate' => $event->getSetting('regRegistrantOpenDate'),
					'newRegRegistrantOpenDate' => $this->_data['regRegistrantOpenDate']));
			$event->updateSetting('regRegistrantOpenDate', $this->_data['regRegistrantOpenDate'], 'date');
		}
		if($event->getSetting('regRegistrantCloseDate') != $this->_data['regRegistrantCloseDate']) {
			ConferenceLog::logEvent(
				$event->getConferenceId(),
				$event->getEventId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.regRegistrantCloseDateChanged',
				array('oldRegRegistrantCloseDate' => $event->getSetting('regRegistrantCloseDate'),
					'newRegRegistrantCloseDate' => $this->_data['regRegistrantCloseDate']));
			$event->updateSetting('regRegistrantCloseDate', $this->_data['regRegistrantCloseDate'], 'date');
		}
		/*if($event->getSetting('postPresentationsDate') != $this->_data['postPresentationsDate']) {
			ConferenceLog::logEvent(
				$event->getConferenceId(),
				$event->getEventId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.postPresentationsDateChanged',
				array('oldPostPresentationsDate' => $event->getSetting('postPresentationsDate'),
					'newPostPresentationsDate' => $this->_data['postPresentationsDate']));
			$event->updateSetting('postPresentationsDate', $this->_data['postPresentationsDate'], 'date');
		}
		if($event->getSetting('postPresentations') != $this->_data['postPresentations']) {
			ConferenceLog::logEvent(
				$event->getConferenceId(),
				$event->getEventId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.postPresentationsChanged',
				array('oldPostPresentations' => $event->getSetting('postPresentations'),
					'newPostPresentations' => $this->_data['postPresentations']));
			$event->updateSetting('postPresentations', $this->_data['postPresentations'], 'bool');
		}*/
		if($event->getSetting('postAbstractsDate') != $this->_data['postAbstractsDate']) {
			ConferenceLog::logEvent(
				$event->getConferenceId(),
				$event->getEventId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.postAbstractsDateChanged',
				array('oldPostAbstractsDate' => $event->getSetting('postAbstractsDate'),
					'newPostAbstractsDate' => $this->_data['postAbstractsDate']));
			$event->updateSetting('postAbstractsDate', $this->_data['postAbstractsDate'], 'date');
		}
		if($event->getSetting('postAbstracts') != $this->_data['postAbstracts']) {
			ConferenceLog::logEvent(
				$event->getConferenceId(),
				$event->getEventId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.postAbstractsChanged',
				array('oldPostAbstracts' => $event->getSetting('postAbstracts'),
					'newPostAbstracts' => $this->_data['postAbstracts']));
			$event->updateSetting('postAbstracts', $this->_data['postAbstracts'], 'bool');
		}
		if($event->getSetting('postPapersDate') != $this->_data['postPapersDate']) {
			ConferenceLog::logEvent(
				$event->getConferenceId(),
				$event->getEventId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.postPapersDateChanged',
				array('oldPostPapersDate' => $event->getSetting('postPapersDate'),
					'newPostPapersDate' => $this->_data['postPapersDate']));
			$event->updateSetting('postPapersDate', $this->_data['postPapersDate'], 'date');
		}
		if($event->getSetting('postPapers') != $this->_data['postPapers']) {
			ConferenceLog::logEvent(
				$event->getConferenceId(),
				$event->getEventId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.postPapersChanged',
				array('oldPostPapers' => $event->getSetting('postPapers'),
					'newPostPapers' => $this->_data['postPapers']));
			$event->updateSetting('postPapers', $this->_data['postPapers'], 'bool');
		}
		if($event->getSetting('delayOpenAccessDate') != $this->_data['delayOpenAccessDate']) {
			ConferenceLog::logEvent(
				$event->getConferenceId(),
				$event->getEventId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.delayOpenAccessDateChanged',
				array('oldDelayOpenAccessDate' => $event->getSetting('delayOpenAccessDate'),
					'newDelayOpenAccessDate' => $this->_data['delayOpenAccessDate']));
			$event->updateSetting('delayOpenAccessDate', $this->_data['delayOpenAccessDate'], 'date');
		}
		if($event->getSetting('delayOpenAccess') != $this->_data['delayOpenAccess']) {
			ConferenceLog::logEvent(
				$event->getConferenceId(),
				$event->getEventId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.delayOpenAccessChanged',
				array('oldDelayOpenAccess' => $event->getSetting('delayOpenAccess'),
					'newDelayOpenAccess' => $this->_data['delayOpenAccess']));
			$event->updateSetting('delayOpenAccess', $this->_data['delayOpenAccess'], 'bool');
		}
		if($event->getSetting('closeCommentsDate') != $this->_data['closeCommentsDate']) {
			ConferenceLog::logEvent(
				$event->getConferenceId(),
				$event->getEventId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.closeCommentsDateChanged',
				array('oldCloseCommentsDate' => $event->getSetting('closeCommentsDate'),
					'newCloseCommentsDate' => $this->_data['closeCommentsDate']));
			$event->updateSetting('closeCommentsDate', $this->_data['closeCommentsDate'], 'date');
		}
		if($event->getSetting('closeComments') != $this->_data['closeComments']) {
			ConferenceLog::logEvent(
				$event->getConferenceId(),
				$event->getEventId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.closeCommentsChanged',
				array('oldCloseComments' => $event->getSetting('closeComments'),
					'newCloseComments' => $this->_data['closeComments']));
			$event->updateSetting('closeComments', $this->_data['closeComments'], 'bool');
		}
	}
}

?>
