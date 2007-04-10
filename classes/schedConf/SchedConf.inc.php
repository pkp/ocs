<?php

/**
 * SchedConf.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package schedConf
 *
 * Scheduled conference class.
 * Describes basic scheduled conference properties.
 *
 * $Id$
 */

define('SCHED_CONF_DATE_YEAR_OFFSET_FUTURE',	'+2');

define('REVIEW_MODE_ABSTRACTS_ALONE',		0x00000000);
define('REVIEW_MODE_PRESENTATIONS_ALONE',	0x00000001);
define('REVIEW_MODE_BOTH_SIMULTANEOUS',		0x00000002);
define('REVIEW_MODE_BOTH_SEQUENTIAL',		0x00000003);

class SchedConf extends DataObject {

	//
	// Scheduled Conference functions: the following do not operate on data from the
	// SchedConfSettings or ConferenceSettings tables.
	//

	/**
	 * Constructor.
	 */
	function SchedConf() {
		parent::DataObject();
	}
	
	/**
	 * Get the base URL to the scheduled conference.
	 * @return string
	 */
	function getUrl() {
		// This is potentially abusable, since there's no guarantee the conference
		// component of the URL hasn't been changed. However, there's nothing to
		// gain by doing so.
		return Request::url(null, $this->getPath());
	}

	/**
	 * Get the conference for this scheduled conference.
	 * @return string
	 */
	function &getConference() {
		$conferenceDao = &DAORegistry::getDAO('ConferenceDAO');
		$returner =& $conferenceDao->getConference($this->getConferenceId());
		return $returner;
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
	 * Get title of scheduled conference
	 * @return string
	 */
	 function getTitle() {
	 	return $this->getData('title');
	}
	
	/**
	* Set title of scheduled conference
	* @param $title string
	*/
	function setTitle($title) {
		return $this->setData('title',$title);
	}
	
	/**
	 * Get ID of scheduled conference.
	 * @return int
	 */
	function getSchedConfId() {
		return $this->getData('schedConfId');
	}
	
	/**
	 * Set ID of scheduled conference.
	 * @param $schedConfId int
	 */
	function setSchedConfId($schedConfId) {
		return $this->setData('schedConfId', $schedConfId);
	}
	
	/**
	 * Get conference ID of scheduled conference.
	 * @return int
	 */
	function getConferenceId() {
		return $this->getData('conferenceId');
	}
	
	/**
	 * Set conference ID of scheduled conference.
	 * @param $schedConfId int
	 */
	function setConferenceId($conferenceId) {
		return $this->setData('conferenceId', $conferenceId);
	}
	
	/**
	 * Get path to scheduled conference (in URL).
	 * @return string
	 */
	function getPath() {
		return $this->getData('path');
	}
	
	/**
	 * Set path to scheduled conference (in URL).
	 * @param $path string
	 */
	function setPath($path) {
		return $this->setData('path', $path);
	}
	
	/**
	 * Get sequence of scheduled conference in site table of contents.
	 * @return float
	 */
	function getSequence() {
		return $this->getData('sequence');
	}
	
	/**
	 * Set sequence of scheduled conference in site table of contents.
	 * @param $sequence float
	 */
	function setSequence($sequence) {
		return $this->setData('sequence', $sequence);
	}

	//
	// Scheduled conference start/end date functions
	//
	
	/**
	 * Get start date of scheduled conference.
	 * @return date
	 */
	function getStartDate() {
		return $this->getData('startDate');
	}
	
	/**
	 * Set start date of scheduled conference.
	 * @param $startDate date
	 */
	function setStartDate($startDate) {
		return $this->setData('startDate', $startDate);
	}
	
	/**
	 * Get end date of scheduled conference.
	 * @return date
	 */
	function getEndDate() {
		return $this->getData('endDate');
	}
	
	/**
	 * Set end date of scheduled conference.
	 * @param $endDate date
	 */
	function setEndDate($endDate) {
		return $this->setData('endDate', $endDate);
	}
	
	//
	// Helper functions making use of both the scheduled conference
	// and Conference.
	//

	/**
	 * Get full title of scheduled conference, including the conference title
	 * @return string
	 */
	 function getFullTitle() {
	 	$conference =& $this->getConference();
	 	return $conference->getTitle() . ' ' . $this->getData('title');
	}
	
	
	//
	// SchedConfSettings functions: the following make use of data in the
	// ConferenceSettings or SchedConfSettings tables.
	//

	/**
	 * Retrieve array of scheduled conference settings.
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

		$schedConfSettingsDao = &DAORegistry::getDAO('SchedConfSettingsDAO');
		$schedConfSettings = &$schedConfSettingsDao->getSchedConfSettings($this->getData('schedConfId'));
		
		return array_merge($conferenceSettings, $schedConfSettings);
	}
	
	/**
	 * Retrieve a scheduled conference setting value.
	 * @param $name
	 * @param $includeParent
	 * @return mixed
	 */
	function &getSetting($name, $includeParent = false) {
		$schedConfSettingsDao = &DAORegistry::getDAO('SchedConfSettingsDAO');
		$setting = &$schedConfSettingsDao->getSetting($this->getData('schedConfId'), $name);

		if(!$setting && $includeParent) {
			$conferenceSettingsDao = &DAORegistry::getDAO('ConferenceSettingsDAO');
			$setting = &$conferenceSettingsDao->getSetting($this->getData('conferenceId'), $name);
		}

		return $setting;
	}

	/**
	 * Update a scheduled conference setting value.
	 */
	function updateSetting($name, $value, $type = null) {
		$schedConfSettingsDao =& DAORegistry::getDAO('SchedConfSettingsDAO');
		return $schedConfSettingsDao->updateSetting($this->getSchedConfId(), $name, $value, $type);
	}

	/**
	 * Return the primary locale of this scheduled conference.
	 * @return string
	 */
	function getLocale() {
		return $this->getSetting('primaryLocale', true);
	}
	
	/**
	 * Get CSS for this scheduled conference
	 * @return string
	 */
	function getStyleFilename() {
		return $this->getSetting('schedConfStyleSheet');
	}
}

?>
