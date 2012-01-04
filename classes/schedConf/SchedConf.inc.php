<?php

/**
 * @defgroup schedConf
 */

/**
 * @file SchedConf.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SchedConf
 * @ingroup schedConf
 * @see SchedConfDAO
 *
 * @brief Describes basic scheduled conference properties.
 *
 */

// $Id$


define('SCHED_CONF_DATE_YEAR_OFFSET_FUTURE',	'+2');

define('REVIEW_MODE_ABSTRACTS_ALONE',		0x00000000);
define('REVIEW_MODE_PRESENTATIONS_ALONE',	0x00000001);
define('REVIEW_MODE_BOTH_SIMULTANEOUS',		0x00000002);
define('REVIEW_MODE_BOTH_SEQUENTIAL',		0x00000003);

define('REVIEW_DEADLINE_TYPE_RELATIVE', 	0x00000001);
define('REVIEW_DEADLINE_TYPE_ABSOLUTE', 	0x00000002);

define('SCHEDULE_LAYOUT_COMPACT', 0x00001);
define('SCHEDULE_LAYOUT_EXPANDED', 0x00002);

class SchedConf extends DataObject {

	//
	// Scheduled Conference functions: the following do not operate on data from the
	// SchedConfSettings or ConferenceSettings tables.
	//

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
		$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');
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
		return $this->setData('current', $current);
	}

	/**
	 * Get the localized title of the scheduled conference
	 * @return string
	 */
	function getLocalizedTitle() {
		return $this->getLocalizedSetting('title');
	}

	function getSchedConfTitle() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedTitle();
	}

	/**
	 * Get title of scheduled conference
	 * @param $locale string
	 * @return string
	 */
	function getTitle($locale) {
		return $this->getSetting('title', $locale);
	}

	/**
	 * Get the localized acronym of the scheduled conference
	 * @return string
	 */
	function getLocalizedAcronym() {
		return $this->getLocalizedSetting('acronym');
	}

	function getSchedConfAcronym() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedAcronym();
	}

	/**
	 * Get acronym of scheduled conference
	 * @param $locale string
	 * @return string
	 */
	function getAcronym($locale) {
		return $this->getSetting('acronym', $locale);
	}

	/**
	 * Get ID of scheduled conference.
	 * @return int
	 */
	function getSchedConfId() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getId();
	}

	/**
	 * Set ID of scheduled conference.
	 * @param $schedConfId int
	 */
	function setSchedConfId($schedConfId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->setId($schedConfId);
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

	/**
	 * Get the localized introduction of the scheduled conference
	 * @return string
	 */
	function getSchedConfIntroduction() {
		return $this->getLocalizedSetting('introduction');
	}

	//
	// Helper functions making use of both the scheduled conference
	// and Conference.
	//

	/**
	 * Get full title of scheduled conference.
	 * (Used to include conference title as well; this behavior was deprecated prior to 2.0 release.)
	 * @return string
	 */
	function getFullTitle() {
		return $this->getSchedConfTitle();
	}


	//
	// SchedConfSettings functions: the following make use of data in the
	// ConferenceSettings or SchedConfSettings tables.
	//

	/**
	 * Retrieve array of scheduled conference settings.
	 * @return array
	 */
	function getSettings() {
		$schedConfSettingsDao =& DAORegistry::getDAO('SchedConfSettingsDAO');
		return $schedConfSettingsDao->getSchedConfSettings($this->getId());
	}

	/**
	 * Get a localized scheduled conference setting.
	 * @param $name string
	 * @return mixed
	 */
	function &getLocalizedSetting($name) {
		$returner = $this->getSetting($name, AppLocale::getLocale());
		if ($returner === null) {
			unset($returner);
			$returner = $this->getSetting($name, AppLocale::getPrimaryLocale());
		}
		return $returner;
	}

	/**
	 * Retrieve a scheduled conference setting value.
	 * @param $name
	 * @param $locale string
	 * @return mixed
	 */
	function &getSetting($name, $locale = null) {
		$schedConfSettingsDao =& DAORegistry::getDAO('SchedConfSettingsDAO');
		$setting =& $schedConfSettingsDao->getSetting($this->getId(), $name, $locale);
		return $setting;
	}

	/**
	 * Update a scheduled conference setting value.
	 * @param $name string
	 * @param $value mixed
	 * @param $type string optional
	 * @param $isLocalized boolean optional
	 */
	function updateSetting($name, $value, $type = null, $isLocalized = false) {
		$schedConfSettingsDao =& DAORegistry::getDAO('SchedConfSettingsDAO');
		return $schedConfSettingsDao->updateSetting($this->getSchedConfId(), $name, $value, $type, $isLocalized);
	}

	/**
	 * Return the primary locale of this scheduled conference.
	 * @return string
	 */
	function getPrimaryLocale() {
		$conference =& $this->getConference();
		return $conference->getPrimaryLocale();
	}

	/**
	 * Set the primary locale of this conference.
	 * @param $locale string
	 */
	function setPrimaryLocale($primaryLocale) {
		return $this->setData('primaryLocale', $primaryLocale);
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
