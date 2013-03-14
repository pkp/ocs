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

define('SCHED_CONF_DATE_YEAR_OFFSET_FUTURE',	'+2');

define('REVIEW_MODE_ABSTRACTS_ALONE',		0x00000000);
define('REVIEW_MODE_PRESENTATIONS_ALONE',	0x00000001);
define('REVIEW_MODE_BOTH_SIMULTANEOUS',		0x00000002);
define('REVIEW_MODE_BOTH_SEQUENTIAL',		0x00000003);

define('REVIEW_DEADLINE_TYPE_RELATIVE', 	0x00000001);
define('REVIEW_DEADLINE_TYPE_ABSOLUTE', 	0x00000002);

define('SCHEDULE_LAYOUT_COMPACT', 0x00001);
define('SCHEDULE_LAYOUT_EXPANDED', 0x00002);

import('lib.pkp.classes.context.Context');

class SchedConf extends Context {
	/**
	 * Constructor
	 */
	function SchedConf() {
		parent::Context();
	}

	//
	// Scheduled Conference functions: the following do not operate on data from the
	// SchedConfSettings or ConferenceSettings tables.
	//

	/**
	 * Get the conference for this scheduled conference.
	 * @return string
	 */
	function &getConference() {
		$conferenceDao = DAORegistry::getDAO('ConferenceDAO');
		$returner =& $conferenceDao->getById($this->getConferenceId());
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
	function getLocalizedIntroduction() {
		return $this->getLocalizedSetting('introduction');
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
	 * Get CSS for this scheduled conference
	 * @return string
	 */
	function getStyleFilename() {
		return $this->getSetting('schedConfStyleSheet');
	}

	/**
	 * Get the association type for this context.
	 * @return int
	 */
	function getAssocType() {
		return ASSOC_TYPE_SCHED_CONF;
	}

	/**
	 * Get the settings DAO for this context object.
	 * @return DAO
	 */
	static function getSettingsDAO() {
		return DAORegistry::getDAO('SchedConfSettingsDAO');
	}
}

?>
