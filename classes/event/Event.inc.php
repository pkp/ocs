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

define('EVENT_DATE_YEAR_OFFSET_FUTURE',	'+2');

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
	 * Get CSS for this event
	 * @return string
	 */
	function getStyleFilename() {
		return $this->getSetting('eventStyleSheet');
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
}

?>
