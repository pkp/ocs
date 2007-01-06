<?php

/**
 * Event.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package event
 *
 * Event class.
 * Describes basic event properties.
 *
 * $Id$
 */

import('event.EventConstants');

class Event extends DataObject {

	//
	// Event functions: the following do not operate on data from the
	// EventSettings or ConferenceSettings tables.
	//

	/**
	 * Constructor.
	 */
	function Event() {
		parent::DataObject();
	}
	
	/**
	 * Get the base URL to the event.
	 * @return string
	 */
	function getUrl() {
		// This is potentially abusable, since there's no guarantee the conference
		// component of the URL hasn't been changed. However, there's nothing to
		// gain by doing so.
		return Request::url(null, $this->getPath());
	}

	/**
	 * Get the conference for this event.
	 * @return string
	 */
	function &getConference() {
		$conferenceDao = &DAORegistry::getDAO('ConferenceDAO');
		return $conferenceDao->getConference($this->getConferenceId());
	}
	
	/**
	 * get current
	 * @return int
	 */
	function getCurrent() {
		return $this->getData('current');
	}
	 
	/**
	 * set current
	 * @param $current int
	 */
	function setCurrent($current) {
		return $this->setData('current',$current);
	}

	/**
	 * Get title of event
	 * @return string
	 */
	 function getTitle() {
	 	return $this->getData('title');
	}
	
	/**
	* Set title of event
	* @param $title string
	*/
	function setTitle($title) {
		return $this->setData('title',$title);
	}
	
	/**
	 * Get enabled flag of event
	 * @return int
	 */
	 function getEnabled() {
	 	return $this->getData('enabled');
	}
	
	/**
	* Set enabled flag of event
	* @param $enabled int
	*/
	function setEnabled($enabled) {
		return $this->setData('enabled',$enabled);
	}
	
	/**
	 * Get ID of event.
	 * @return int
	 */
	function getEventId() {
		return $this->getData('eventId');
	}
	
	/**
	 * Set ID of event.
	 * @param $eventId int
	 */
	function setEventId($eventId) {
		return $this->setData('eventId', $eventId);
	}
	
	/**
	 * Get conference ID of event.
	 * @return int
	 */
	function getConferenceId() {
		return $this->getData('conferenceId');
	}
	
	/**
	 * Set conference ID of event.
	 * @param $eventId int
	 */
	function setConferenceId($conferenceId) {
		return $this->setData('conferenceId', $conferenceId);
	}
	
	/**
	 * Get path to event (in URL).
	 * @return string
	 */
	function getPath() {
		return $this->getData('path');
	}
	
	/**
	 * Set path to event (in URL).
	 * @param $path string
	 */
	function setPath($path) {
		return $this->setData('path', $path);
	}
	
	/**
	 * Get sequence of event in site table of contents.
	 * @return float
	 */
	function getSequence() {
		return $this->getData('sequence');
	}
	
	/**
	 * Set sequence of event in site table of contents.
	 * @param $sequence float
	 */
	function setSequence($sequence) {
		return $this->setData('sequence', $sequence);
	}

	//
	// Event start/end date functions
	//
	
	/**
	 * Get start date of event.
	 * @return date
	 */
	function getStartDate() {
		return $this->getData('startDate');
	}
	
	/**
	 * Set start date of event.
	 * @param $startDate date
	 */
	function setStartDate($startDate) {
		return $this->setData('startDate', $startDate);
	}
	
	/**
	 * Get end date of event.
	 * @return date
	 */
	function getEndDate() {
		return $this->getData('endDate');
	}
	
	/**
	 * Set end date of event.
	 * @param $endDate date
	 */
	function setEndDate($endDate) {
		return $this->setData('endDate', $endDate);
	}
	
	//
	// Helper functions making use of both the Event
	// and Conference.
	//

	/**
	 * Get full title of event, including the conference title
	 * @return string
	 */
	 function getFullTitle() {
	 	$conference =& $this->getConference();
	 	return $conference->getTitle() . ' ' . $this->getData('title');
	}
	
	
	//
	// EventSettings functions: the following make use of data in the
	// ConferenceSettings or EventSettings tables.
	//

	/**
	 * Retrieve array of event settings.
	 * @param $includeParent
	 * @return array
	 */
	function getSettings($includeParent = false) {
		if($includeParent) {
			$conferenceSettingsDao = &DAORegistry::getDAO('ConferenceSettingsDAO');
			$conferenceSettings = &$conferenceSettingsDao->getConferenceSettings($this->getData('conferenceId'));
		} else {
			$conferenceSettings = array();
		}

		$eventSettingsDao = &DAORegistry::getDAO('EventSettingsDAO');
		$eventSettings = &$eventSettingsDao->getEventSettings($this->getData('eventId'));
		
		return array_merge($conferenceSettings, $eventSettings);
	}
	
	/**
	 * Retrieve a event setting value.
	 * @param $name
	 * @param $includeParent
	 * @return mixed
	 */
	function &getSetting($name, $includeParent = false) {
		$eventSettingsDao = &DAORegistry::getDAO('EventSettingsDAO');
		$setting = &$eventSettingsDao->getSetting($this->getData('eventId'), $name);

		if(!$setting && $includeParent) {
			$conferenceSettingsDao = &DAORegistry::getDAO('ConferenceSettingsDAO');
			$setting = &$conferenceSettingsDao->getSetting($this->getData('conferenceId'), $name);
		}

		return $setting;
	}

	/**
	 * Update a event setting value.
	 */
	function updateSetting($name, $value, $type = null) {
		$eventSettingsDao =& DAORegistry::getDAO('EventSettingsDAO');
		return $eventSettingsDao->updateSetting($this->getEventId(), $name, $value, $type);
	}

	/**
	 * Return the primary locale of this event.
	 * @return string
	 */
	function getLocale() {
		return $this->getSetting('primaryLocale', true);
	}
	
	/**
	 * Get "localized" event page title (if applicable).
	 * param $home boolean get homepage title
	 * @return string
	 */
	function getPageHeaderTitle($home = false) {
		// FIXME this is evil
		$alternateLocaleNum = Locale::isAlternateConferenceLocale($this->getData('conferenceId'));
		$prefix = $home ? 'home' : 'page';
		switch ($alternateLocaleNum) {
			case 1:
				$type = $this->getSetting($prefix . 'HeaderTitleTypeAlt1');
				if ($type) {
					$title = $this->getSetting($prefix . 'HeaderTitleImageAlt1');
				}
				if (!isset($title)) {
					$title = $this->getSetting($prefix . 'HeaderTitleAlt1');
				}
				break;
			case 2:
				$type = $this->getSetting($prefix . 'HeaderTitleTypeAlt2');
				if ($type) {
					$title = $this->getSetting($prefix . 'HeaderTitleImageAlt2');
				}
				if (!isset($title)) {
					$title = $this->getSetting($prefix . 'HeaderTitleAlt2');
				}
				break;
		}
		
		if (isset($title) && !empty($title)) {
			return $title;
			
		} else {
			$type = $this->getSetting($prefix . 'HeaderTitleType');
			if ($type) {
				$title = $this->getSetting($prefix . 'HeaderTitleImage');
			}
			if (!isset($title)) {
				$title = $this->getSetting($prefix . 'HeaderTitle', true);
			}
			
			return $title;
		}
	}
	
	/**
	 * Get "localized" event page logo (if applicable).
	 * param $home boolean get homepage logo
	 * @return string
	 */
	function getPageHeaderLogo($home = false) {
		// FIXME this is evil
		$alternateLocaleNum = Locale::isAlternateConferenceLocale($this->getData('eventId'));
		$prefix = $home ? 'home' : 'page';
		switch ($alternateLocaleNum) {
			case 1:
				$logo = $this->getSetting($prefix . 'HeaderLogoImageAlt1');
				break;
			case 2:
				$logo = $this->getSetting($prefix . 'HeaderLogoImageAlt2');
				break;
		}
		
		if (isset($logo) && !empty($logo)) {
			return $logo;
			
		} else {
			return $this->getSetting($prefix . 'HeaderLogoImage');
		}
	}
	
	/**
	 * Get CSS for this event (default to parent conference)
	 * @return string
	 */
	function getStyleFilename() {
		return $this->getSetting('eventStyleSheet');
	}
	
	//
	// Event CFP and submission deadline functions
	//
	
	/**
	 * Should a CFP message be shown automatically?
	 * @return bool
	 */
	function getAutoShowCFP() {
		return $this->getSetting('autoShowCFP');
	}

	/**
	 * Set whether a CFP message be shown automatically
	 * @param bool
	 */
	function setAutoShowCFP($autoShowCFP) {
		return $this->updateSetting('autoShowCFP', $autoShowCFP, 'bool');
	}

	/**
	 * Get date at which a CFP message should be shown
	 * @return date
	 */
	function getShowCFPDate() {
		return $this->getSetting('showCFPDate');
	}
	
	/**
	 * Set date at which a CFP message should be shown
	 * @param date
	 */
	function setShowCFPDate($showCFPDate) {
		return $this->updateSetting('showCFPDate', $showCFPDate, 'date');
	}

	/**
	 * Get date at which submissions start being accepted.
	 * @return date
	 */
	function getAcceptSubmissionsDate() {
		return $this->getSetting('acceptSubmissionsDate');
	}
	
	/**
	 * Set date at which submissions start being accepted.
	 * @param date
	 */
	function setAcceptSubmissionsDate($acceptSubmissionsDate) {
		return $this->updateSetting('acceptSubmissionsDate', $acceptSubmissionsDate, 'date');
	}
	
	/**
	 * Get date at which abstracts stop being accepted.
	 * @return date
	 */
	function getAbstractDueDate() {
		return $this->getSetting('abstractDueDate');
	}
	
	/**
	 * Set date at which abstracts stop being accepted.
	 * @param date
	 */
	function setAbstractDueDate($abstractDueDate) {
		return $this->updateSetting('abstractDueDate', $abstractDueDate, 'date');
	}
	
	/**
	 * Get date at which papers stop being accepted.
	 * @return date
	 */
	function getPaperDueDate() {
		return $this->getSetting('paperDueDate');
	}

	/**
	 * Set date at which papers stop being accepted.
	 * @param date
	 */
	function setPaperDueDate($paperDueDate) {
		return $this->updateSetting('paperDueDate', $paperDueDate, 'date');
	}
	
	//
	// Publication date accessors
	//
		
	/**
	 * Automatically release proceedings to participants?
	 * @return bool
	 */
	function getAutoReleaseToParticipants() {
		return $this->getSetting('autoReleaseToParticipants');
	}

	/**
	 * Set whether or not we release proceedings to participants
	 * @param bool
	 */
	function setAutoReleaseToParticipants($autoReleaseToParticipants) {
		return $this->updateSetting('autoReleaseToParticipants', $autoReleaseToParticipants, 'bool');
	}

	/**
	 * Get date at which publications are released to conference participants.
	 * @return date
	 */
	function getAutoReleaseToParticipantsDate() {
		return $this->getSetting('autoReleaseToParticipantsDate');
	}

	/**
	 * Set date at which publications are released to conference participants.
	 * @param date
	 */
	function setAutoReleaseToParticipantsDate($autoReleaseToParticipantsDate) {
		return $this->updateSetting('autoReleaseToParticipantsDate', $autoReleaseToParticipantsDate, 'date');
	}

	/**
	 * Automatically release proceedings to public?
	 * @return bool
	 */
	function getAutoReleaseToPublic() {
		return $this->getSetting('autoReleaseToPublic');
	}

	/**
	 * Set whether or not we release proceedings to public
	 * @param bool
	 */
	function setAutoReleaseToPublic($autoReleaseToPublic) {
		return $this->updateSetting('autoReleaseToPublic', $autoReleaseToPublic, 'bool');
	}

	/**
	 * Get date at which publications are released to the general public.
	 * @return date
	 */
	function getAutoReleaseToPublicDate() {
		return $this->getSetting('autoReleaseToPublicDate');
	}
	
	/**
	 * Set date at which publications are released to the general public.
	 * @param date
	 */
	function setAutoReleaseToPublicDate($autoReleaseToPublicDate) {
		return $this->updateSetting('autoReleaseToPublicDate', $autoReleaseToPublicDate, 'date');
	}

	//
	// Model and state variables
	//
	
	/**
	 * Are we configured to accept papers?
	 * @return bool
	 */
	function getAcceptPapers() {
		return $this->getSetting('acceptPapers', true);
	}
	
	/**
	 * Are we configured to review papers and abstracts simultaneously?
	 * @return bool
	 */
	function getCollectPapersWithAbstracts() {
		return $this->getSetting('collectPapersWithAbstracts', true);
	}
	
	/**
	 * Are we configured to review papers, or are they automatically accepted?
	 * @return bool
	 */
	function getReviewPapers() {
		return $this->getSetting('reviewPapers', true);
	}
	
	/**
	 * Get submission state of event.
	 * @return int
	 */
	function getSubmissionState() {
		return $this->getSetting('submissionState');
	}
	
	/**
	 * Set submission state of event.
	 * @param $cfpState int
	 */
	function setSubmissionState($submissionState) {
		return $this->updateSetting('submissionState', $submissionState, 'int');
	}

	/**
	 * Get publication state of event.
	 * @return int
	 */
	function getPublicationState() {
		return $this->getSetting('publicationState');
	}
	
	/**
	 * Set publication state of event.
	 * @param $publicationState int
	 */
	function setPublicationState($publicationState) {
		return $this->updateSetting('publicationState', $publicationState, 'int');
	}

	/**
	 * Get registration state of event.
	 * @return int
	 */
	function getRegistrationState() {
		return $this->getSetting('registrationState');
	}
	
	/**
	 * Set registration state of event.
	 * @param $registrationState int
	 */
	function setRegistrationState($registrationState) {
		return $this->updateSetting('registrationState', $registrationState, 'int');
	}
	
	/**
	 * Do we automatically remind authors when deadlines approach?
	 * @return bool
	 */
	function getAutoRemindAuthors() {
		return $this->getSetting('autoRemindAuthors');
	}
	
	/**
	 * Set if we automatically remind authors when deadlines approach
	 * @param $autoRemindAuthors bool
	 */
	function setAutoRemindAuthors($autoRemindAuthors) {
		return $this->updateSetting('autoRemindAuthors', $autoRemindAuthors, 'bool');
	}
		
	/**
	 * How many days' warning do authors with incomplete submissions get?
	 * @return int
	 */
	function getAutoRemindAuthorsDays() {
		return $this->getSetting('autoRemindAuthorsDays', true);
	}
	
	/**
	 * Set how many days' warning authors with incomplete submissions get.
	 * @param $autoRemindAuthorsDays int
	 */
	function setAutoRemindAuthorsDays($autoRemindAuthorsDays) {
		return $this->updateSetting('autoRemindAuthorsDays', $autoRemindAuthorsDays, 'int');
	}
		
	/**
	 * Do we automatically archive incomplete submissions when the deadline passes?
	 * @return bool
	 */
	function getAutoArchiveIncompleteSubmissions() {
		return $this->getSetting('autoArchiveIncompleteSubmissions');
	}
	
	/**
	 * Set if we automatically remind authors when deadlines approach
	 * @param $autoArchiveIncompleteSubmissions bool
	 */
	function setAutoArchiveIncompleteSubmissions($autoArchiveIncompleteSubmissions) {
		return $this->updateSetting('autoArchiveIncompleteSubmissions', $autoArchiveIncompleteSubmissions, 'bool');
	}
	
	/**
	 * Get whether or not we permit users to register as readers
	 */
	function getAllowRegReader() {
		$allowRegReader = false;
		if($this->getSetting('openRegReader') && time() > $this->getSetting('openRegReaderDate')) {
			$allowRegReader = true;
		}
		if($this->getSetting('closeRegReader') && time() > $this->getSetting('closeRegReaderDate')) {
			$allowRegReader = false;
		}
		return $allowRegReader;
	}

	/**
	 * Get whether or not we permit users to register as reviewers
	 */
	function getAllowRegReviewer() {
		$allowRegReviewer = false;
		if($this->getSetting('openRegReviewer') && time() > $this->getSetting('openRegReviewerDate')) {
			$allowRegReviewer = true;
		}
		if($this->getSetting('closeRegReviewer') && time() > $this->getSetting('closeRegReviewerDate')) {
			$allowRegReviewer = false;
		}
		return $allowRegReviewer;
	}

	/**
	 * Get whether or not we permit users to register as authors
	 */
	function getAllowRegAuthor() {
		$allowRegAuthor = false;
		if($this->getSetting('openRegAuthor') && time() > $this->getSetting('openRegAuthorDate')) {
			$allowRegAuthor = true;
		}
		if($this->getSetting('closeRegAuthor') && time() > $this->getSetting('closeRegAuthorDate')) {
			$allowRegAuthor = false;
		}
		return $allowRegAuthor;
	}
}

?>
