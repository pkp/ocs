<?php

/**
 * TimelineForm.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form
 *
 * Form for creating and modifying scheduled conference tracks.
 *
 * $Id$
 */

import('form.Form');

class TimelineForm extends Form {

	/** @var boolean can edit metadata */
	var $canEdit;
	
	/**
	 * Constructor.
	 * @param $trackId int omit for a new track
	 */
	function TimelineForm() {
		$this->canEdit = false;
		if (Validation::isEditor() || Validation::isConferenceManager()) {
			$this->canEdit = true;
		}

		if($this->canEdit) {
			parent::Form('trackEditor/timelineEdit.tpl');
		} else {
			parent::Form('trackEditor/timelineView.tpl');
		}

		/*$this->addCheck(new FormValidatorCustom($this, 'endDate', 'required', 'manager.timeline.form.badEndDate',
			create_function('$endDate,$form',
			'return ($endDate > $form->getData(\'startDate\'));'),
			array(&$this)));

		$this->addCheck(new FormValidatorCustom($this, 'autoRemindPresentersDays', 'required', 'manager.timeline.form.badReminderDays',
			create_function('$autoRemindPresenterDays,$form',
			'if($form->getData(\'autoRemindPresenters\') == false) return true;
				return ($autoRemindPresenterDays >= 1 && $autoRemindPresenterDays <= 99) ? true : false;'),
			array(&$this)));*/
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$schedConf =& Request::getSchedConf();
		$templateMgr = &TemplateManager::getManager();

		$templateMgr->assign('pageHierarchy', array(array(Request::url(null, null, 'editor'), 'user.role.editor')));
		$templateMgr->assign('helpTopicId','conference.managementPages.timeline');

		$templateMgr->assign('yearOffsetFuture', SCHED_CONF_DATE_YEAR_OFFSET_FUTURE);

		parent::display();
	}
	
	/**
	 * Initialize form data from current settings.
	 */
	function initData() {
		$schedConf =& Request::getSchedConf();

		$this->_data = array(
			'siteStartDate' => $schedConf->getStartDate(),
			'siteEndDate' => $schedConf->getEndDate(),

			'startDate' => $schedConf->getSetting('startDate'),
			'endDate' => $schedConf->getSetting('endDate'),

			'regPresenterOpenDate' => $schedConf->getSetting('regPresenterOpenDate'),
			'regPresenterCloseDate' => $schedConf->getSetting('regPresenterCloseDate'),
			'showCFPDate' => $schedConf->getSetting('showCFPDate'),
			'proposalsOpenDate' => $schedConf->getSetting('proposalsOpenDate'),
			'proposalsCloseDate' => $schedConf->getSetting('proposalsCloseDate'),
			'submissionsCloseDate' => $schedConf->getSetting('submissionsCloseDate'),
			
			'regReviewerOpenDate' => $schedConf->getSetting('regReviewerOpenDate'),
			'regReviewerCloseDate' => $schedConf->getSetting('regReviewerCloseDate'),
			'closeReviewProcessDate' => $schedConf->getSetting('closeReviewProcessDate'),
			'secondRoundDueDate' => $schedConf->getSetting('secondRoundDueDate'),

			'regRegistrantOpenDate' => $schedConf->getSetting('regRegistrantOpenDate'),
			'regRegistrantCloseDate' => $schedConf->getSetting('regRegistrantCloseDate'),

			//'postPresentations' => $schedConf->getSetting('postPresentations'),
			//'postPresentationsDate' => $schedConf->getSetting('postPresentationsDate'),
			'postAbstracts' => $schedConf->getSetting('postAbstracts'),
			'postAbstractsDate' => $schedConf->getSetting('postAbstractsDate'),
			'postPapers' => $schedConf->getSetting('postPapers'),
			'postPapersDate' => $schedConf->getSetting('postPapersDate'),
			'delayOpenAccess' => $schedConf->getSetting('delayOpenAccess'),
			'delayOpenAccessDate' => $schedConf->getSetting('delayOpenAccessDate'),
			'closeComments' => $schedConf->getSetting('closeComments'),
			'closeCommentsDate' => $schedConf->getSetting('closeCommentsDate')
		);
		
		if($schedConf->getSetting('collectPapersWithAbstracts')) {
			$this->_data['showSubmissionsOpenDate'] = true;
			$this->_data['showSubmissionsCloseDate'] = true;
		} else {
			$this->_data['showProposalsOpenDate'] = true;
			$this->_data['showProposalsCloseDate'] = true;
			if ($schedConf->getSetting('acceptPapers')) {
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
			'regPresenterOpenDate', 'regPresenterCloseDate',
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
		$schedConfDao =& DAORegistry::getDao('SchedConfDAO');
		$schedConf = &Request::getSchedConf();
		
		import('conference.log.ConferenceLog');
		import('conference.log.ConferenceEventLogEntry');

		//
		// Don't log these, since they aren't particularly nefarious.
		//
		
		// Website start date and end date.
		
		if($schedConf->getStartDate() != $this->_data['siteStartDate']) {
			$schedConf->setStartDate($this->_data['siteStartDate']);
			$schedConfDao->updateSchedConf($schedConf);
		}

		if($schedConf->getEndDate() != $this->_data['siteEndDate']) {
			$schedConf->setEndDate($this->_data['siteEndDate']);
			$schedConfDao->updateSchedConf($schedConf);
		}
		
		//
		// Log the rest.
		//
		
		// Physical scheduled conference start date and end date
		if($schedConf->getSetting('startDate') != $this->_data['startDate']) {
			ConferenceLog::logEvent(
				$schedConf->getConferenceId(),
				$schedConf->getSchedConfId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.startDateChanged',
				array('oldStartDate' => $schedConf->getSetting('startDate'),
					'newStartDate' => $this->_data['startDate']));
			$schedConf->updateSetting('startDate', $this->_data['startDate'], 'date');
		}

		if($schedConf->getSetting('endDate') != $this->_data['endDate']) {
			ConferenceLog::logEvent(
				$schedConf->getConferenceId(),
				$schedConf->getSchedConfId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.endDateChanged',
				array('oldEndDate' => $schedConf->getSetting('endDate'),
					'newEndDate' => $this->_data['endDate']));
			$schedConf->updateSetting('endDate', $this->_data['endDate'], 'date');
		}

		if($schedConf->getSetting('regPresenterOpenDate') != $this->_data['regPresenterOpenDate']) {
			ConferenceLog::logEvent(
				$schedConf->getConferenceId(),
				$schedConf->getSchedConfId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.regPresenterOpenDateChanged',
				array('oldRegPresenterOpenDate' => $schedConf->getSetting('regPresenterOpenDate'),
					'newRegPresenterOpenDate' => $this->_data['regPresenterOpenDate']));
			$schedConf->updateSetting('regPresenterOpenDate', $this->_data['regPresenterOpenDate'], 'date');
		}
		if($schedConf->getSetting('regPresenterCloseDate') != $this->_data['regPresenterCloseDate']) {
			ConferenceLog::logEvent(
				$schedConf->getConferenceId(),
				$schedConf->getSchedConfId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.regPresenterCloseDateChanged',
				array('oldRegPresenterCloseDate' => $schedConf->getSetting('regPresenterCloseDate'),
					'newRegPresenterCloseDate' => $this->_data['regPresenterCloseDate']));
			$schedConf->updateSetting('regPresenterCloseDate', $this->_data['regPresenterCloseDate'], 'date');
		}
		if($schedConf->getSetting('showCFPDate') != $this->_data['showCFPDate']) {
			ConferenceLog::logEvent(
				$schedConf->getConferenceId(),
				$schedConf->getSchedConfId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.showCFPDateChanged',
				array('oldShowCFPDate' => $schedConf->getSetting('showCFPDate'),
					'newShowCFPDate' => $this->_data['showCFPDate']));
			$schedConf->updateSetting('showCFPDate', $this->_data['showCFPDate'], 'date');
		}

		// Abstract and submission due dates depend on the submission and review
		// model, so they're not quite as straightforward as the rest.
		
		if($schedConf->getSetting('collectPapersWithAbstracts')) {
			$proposalsOpenDate = $submissionsOpenDate = $this->_data['submissionsOpenDate'];
			$proposalsCloseDate = $submissionsCloseDate = $this->_data['submissionsCloseDate'];
		} else {
			$proposalsOpenDate = $submissionsOpenDate = $this->_data['proposalsOpenDate'];
			$proposalsCloseDate = $this->_data['proposalsCloseDate'];
			if ($schedConf->getSetting('acceptPapers')) {
				$submissionsCloseDate = $this->_data['submissionsCloseDate'];
			} else {
				$submissionsCloseDate = $proposalsCloseDate;
			}
		}

		if($schedConf->getSetting('proposalsOpenDate') != $proposalsOpenDate) {
			ConferenceLog::logEvent(
				$schedConf->getConferenceId(),
				$schedConf->getSchedConfId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.proposalsOpenDateChanged',
				array('oldProposalsOpenDate' => $schedConf->getSetting('proposalsOpenDate'),
					'newProposalsOpenDate' => $proposalsOpenDate));
			$schedConf->updateSetting('proposalsOpenDate', $proposalsOpenDate, 'date');
		}
		if($schedConf->getSetting('proposalsCloseDate') != $proposalsCloseDate) {
			ConferenceLog::logEvent(
				$schedConf->getConferenceId(),
				$schedConf->getSchedConfId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.proposalsCloseDateChanged',
				array('oldProposalsCloseDate' => $schedConf->getSetting('proposalsCloseDate'),
					'newProposalsCloseDate' => $proposalsCloseDate));
			$schedConf->updateSetting('proposalsCloseDate', $proposalsCloseDate, 'date');
		}
		if($schedConf->getSetting('submissionsOpenDate') != $submissionsOpenDate) {
			ConferenceLog::logEvent(
				$schedConf->getConferenceId(),
				$schedConf->getSchedConfId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.submissionsOpenDateChanged',
				array('oldSubmissionsOpenDate' => $schedConf->getSetting('submissionsOpenDate'),
					'newSubmissionsOpenDate' => $submissionsOpenDate));
			$schedConf->updateSetting('submissionsOpenDate', $submissionsOpenDate, 'date');
		}
		if($schedConf->getSetting('submissionsCloseDate') != $submissionsCloseDate) {
			ConferenceLog::logEvent(
				$schedConf->getConferenceId(),
				$schedConf->getSchedConfId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.submissionsCloseDateChanged',
				array('oldSubmissionsCloseDate' => $schedConf->getSetting('submissionsCloseDate'),
					'newSubmissionsCloseDate' => $submissionsCloseDate));
			$schedConf->updateSetting('submissionsCloseDate', $submissionsCloseDate, 'date');
		}
		if($schedConf->getSetting('regReviewerOpenDate') != $this->_data['regReviewerOpenDate']) {
			ConferenceLog::logEvent(
				$schedConf->getConferenceId(),
				$schedConf->getSchedConfId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.regReviewerOpenDateChanged',
				array('oldRegReviewerOpenDate' => $schedConf->getSetting('regReviewerOpenDate'),
					'newRegReviewerOpenDate' => $this->_data['regReviewerOpenDate']));
			$schedConf->updateSetting('regReviewerOpenDate', $this->_data['regReviewerOpenDate'], 'date');
		}
		if($schedConf->getSetting('regReviewerCloseDate') != $this->_data['regReviewerCloseDate']) {
			ConferenceLog::logEvent(
				$schedConf->getConferenceId(),
				$schedConf->getSchedConfId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.regReviewerCloseDateChanged',
				array('oldRegReviewerCloseDate' => $schedConf->getSetting('regReviewerCloseDate'),
					'newRegReviewerCloseDate' => $this->_data['regReviewerCloseDate']));
			$schedConf->updateSetting('regReviewerCloseDate', $this->_data['regReviewerCloseDate'], 'date');
		}
		if($schedConf->getSetting('closeReviewProcessDate') != $this->_data['closeReviewProcessDate']) {
			ConferenceLog::logEvent(
				$schedConf->getConferenceId(),
				$schedConf->getSchedConfId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.closeReviewProcessDateChanged',
				array('oldCloseReviewProcessDate' => $schedConf->getSetting('closeReviewProcessDate'),
					'newCloseReviewProcessDate' => $this->_data['closeReviewProcessDate']));
			$schedConf->updateSetting('closeReviewProcessDate', $this->_data['closeReviewProcessDate'], 'date');
		}
		if($schedConf->getSetting('regRegistrantOpenDate') != $this->_data['regRegistrantOpenDate']) {
			ConferenceLog::logEvent(
				$schedConf->getConferenceId(),
				$schedConf->getSchedConfId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.regRegistrantOpenDateChanged',
				array('oldRegRegistrantOpenDate' => $schedConf->getSetting('regRegistrantOpenDate'),
					'newRegRegistrantOpenDate' => $this->_data['regRegistrantOpenDate']));
			$schedConf->updateSetting('regRegistrantOpenDate', $this->_data['regRegistrantOpenDate'], 'date');
		}
		if($schedConf->getSetting('regRegistrantCloseDate') != $this->_data['regRegistrantCloseDate']) {
			ConferenceLog::logEvent(
				$schedConf->getConferenceId(),
				$schedConf->getSchedConfId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.regRegistrantCloseDateChanged',
				array('oldRegRegistrantCloseDate' => $schedConf->getSetting('regRegistrantCloseDate'),
					'newRegRegistrantCloseDate' => $this->_data['regRegistrantCloseDate']));
			$schedConf->updateSetting('regRegistrantCloseDate', $this->_data['regRegistrantCloseDate'], 'date');
		}
		/*if($schedConf->getSetting('postPresentationsDate') != $this->_data['postPresentationsDate']) {
			ConferenceLog::logEvent(
				$schedConf->getConferenceId(),
				$schedConf->getSchedConfId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.postPresentationsDateChanged',
				array('oldPostPresentationsDate' => $schedConf->getSetting('postPresentationsDate'),
					'newPostPresentationsDate' => $this->_data['postPresentationsDate']));
			$schedConf->updateSetting('postPresentationsDate', $this->_data['postPresentationsDate'], 'date');
		}
		if($schedConf->getSetting('postPresentations') != $this->_data['postPresentations']) {
			ConferenceLog::logEvent(
				$schedConf->getConferenceId(),
				$schedConf->getSchedConfId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.postPresentationsChanged',
				array('oldPostPresentations' => $schedConf->getSetting('postPresentations'),
					'newPostPresentations' => $this->_data['postPresentations']));
			$schedConf->updateSetting('postPresentations', $this->_data['postPresentations'], 'bool');
		}*/
		if($schedConf->getSetting('postAbstractsDate') != $this->_data['postAbstractsDate']) {
			ConferenceLog::logEvent(
				$schedConf->getConferenceId(),
				$schedConf->getSchedConfId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.postAbstractsDateChanged',
				array('oldPostAbstractsDate' => $schedConf->getSetting('postAbstractsDate'),
					'newPostAbstractsDate' => $this->_data['postAbstractsDate']));
			$schedConf->updateSetting('postAbstractsDate', $this->_data['postAbstractsDate'], 'date');
		}
		if($schedConf->getSetting('postAbstracts') != $this->_data['postAbstracts']) {
			ConferenceLog::logEvent(
				$schedConf->getConferenceId(),
				$schedConf->getSchedConfId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.postAbstractsChanged',
				array('oldPostAbstracts' => $schedConf->getSetting('postAbstracts'),
					'newPostAbstracts' => $this->_data['postAbstracts']));
			$schedConf->updateSetting('postAbstracts', $this->_data['postAbstracts'], 'bool');
		}
		if($schedConf->getSetting('postPapersDate') != $this->_data['postPapersDate']) {
			ConferenceLog::logEvent(
				$schedConf->getConferenceId(),
				$schedConf->getSchedConfId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.postPapersDateChanged',
				array('oldPostPapersDate' => $schedConf->getSetting('postPapersDate'),
					'newPostPapersDate' => $this->_data['postPapersDate']));
			$schedConf->updateSetting('postPapersDate', $this->_data['postPapersDate'], 'date');
		}
		if($schedConf->getSetting('postPapers') != $this->_data['postPapers']) {
			ConferenceLog::logEvent(
				$schedConf->getConferenceId(),
				$schedConf->getSchedConfId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.postPapersChanged',
				array('oldPostPapers' => $schedConf->getSetting('postPapers'),
					'newPostPapers' => $this->_data['postPapers']));
			$schedConf->updateSetting('postPapers', $this->_data['postPapers'], 'bool');
		}
		if($schedConf->getSetting('delayOpenAccessDate') != $this->_data['delayOpenAccessDate']) {
			ConferenceLog::logEvent(
				$schedConf->getConferenceId(),
				$schedConf->getSchedConfId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.delayOpenAccessDateChanged',
				array('oldDelayOpenAccessDate' => $schedConf->getSetting('delayOpenAccessDate'),
					'newDelayOpenAccessDate' => $this->_data['delayOpenAccessDate']));
			$schedConf->updateSetting('delayOpenAccessDate', $this->_data['delayOpenAccessDate'], 'date');
		}
		if($schedConf->getSetting('delayOpenAccess') != $this->_data['delayOpenAccess']) {
			ConferenceLog::logEvent(
				$schedConf->getConferenceId(),
				$schedConf->getSchedConfId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.delayOpenAccessChanged',
				array('oldDelayOpenAccess' => $schedConf->getSetting('delayOpenAccess'),
					'newDelayOpenAccess' => $this->_data['delayOpenAccess']));
			$schedConf->updateSetting('delayOpenAccess', $this->_data['delayOpenAccess'], 'bool');
		}
		if($schedConf->getSetting('closeCommentsDate') != $this->_data['closeCommentsDate']) {
			ConferenceLog::logEvent(
				$schedConf->getConferenceId(),
				$schedConf->getSchedConfId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.closeCommentsDateChanged',
				array('oldCloseCommentsDate' => $schedConf->getSetting('closeCommentsDate'),
					'newCloseCommentsDate' => $this->_data['closeCommentsDate']));
			$schedConf->updateSetting('closeCommentsDate', $this->_data['closeCommentsDate'], 'date');
		}
		if($schedConf->getSetting('closeComments') != $this->_data['closeComments']) {
			ConferenceLog::logEvent(
				$schedConf->getConferenceId(),
				$schedConf->getSchedConfId(),
				CONFERENCE_LOG_CONFIGURATION,
				LOG_TYPE_DEFAULT,
				0, 'log.timeline.closeCommentsChanged',
				array('oldCloseComments' => $schedConf->getSetting('closeComments'),
					'newCloseComments' => $this->_data['closeComments']));
			$schedConf->updateSetting('closeComments', $this->_data['closeComments'], 'bool');
		}
	}
}

?>
