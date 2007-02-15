<?php

/**
 * Track.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package conference
 *
 * Track class.
 * Describes basic track properties.
 *
 * $Id$
 */

class Track extends DataObject {

	/**
	 * Constructor.
	 */
	function Track() {
		parent::DataObject();
	}

	/**
	 * Get localized title of conference track.
	 */
	function getTrackTitle() {
		$schedConfId = &$this->getSchedConfId();
		$schedConfDao = &DAORegistry::getDao('SchedConfDAO');
		$schedConf = &$schedConfDao->getSchedConf($schedConfId);
		$conference = &$schedConf->getConference();
		$alternateLocaleNum = Locale::isAlternateConferenceLocale($conference->getConferenceId());
		
		$title = null;
		switch ($alternateLocaleNum) {
			case 1: $title = $this->getTitleAlt1(); break;
			case 2: $title = $this->getTitleAlt2(); break;
		}
		// Fall back on the primary locale title.
		if (empty($title)) $title = $this->getTitle();

		return $title;
	}

	/**
	 * Get localized abbreviation of conference track.
	 */
	function getTrackAbbrev() {
		$schedConfId = &$this->getSchedConfId();
		$schedConfDao = &DAORegistry::getDao('SchedConfDAO');
		$schedConf = &$schedConfDao->getSchedConf($schedConfId);
		$conference = &$schedConf->getConference();
		$alternateLocaleNum = Locale::isAlternateConferenceLocale($conference->getConferenceId());
		
		$abbrev = null;
		switch ($alternateLocaleNum) {
			case 1: $abbrev = $this->getAbbrevAlt1(); break;
			case 2: $abbrev = $this->getAbbrevAlt2(); break;
		}
		// Fall back on the primary locale title.
		if (empty($abbrev)) $abbrev = $this->getAbbrev();

		return $abbrev;
	}

	//
	// Get/set methods
	//
	
	/**
	 * Get ID of track.
	 * @return int
	 */
	function getTrackId() {
		return $this->getData('trackId');
	}
	
	/**
	 * Set ID of track.
	 * @param $trackId int
	 */
	function setTrackId($trackId) {
		return $this->setData('trackId', $trackId);
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
	 * Get title of track.
	 * @return string
	 */
	function getTitle() {
		return $this->getData('title');
	}
	
	/**
	 * Set title of track.
	 * @param $title string
	 */
	function setTitle($title) {
		return $this->setData('title', $title);
	}
	
	/**
	 * Get title of track (alternate locale 1).
	 * @return string
	 */
	function getTitleAlt1() {
		return $this->getData('titleAlt1');
	}
	
	/**
	 * Set title of track (alternate locale 1).
	 * @param $titleAlt1 string
	 */
	function setTitleAlt1($titleAlt1) {
		return $this->setData('titleAlt1', $titleAlt1);
	}
	
	/**
	 * Get title of track (alternate locale 2).
	 * @return string
	 */
	function getTitleAlt2() {
		return $this->getData('titleAlt2');
	}
	
	/**
	 * Set title of track (alternate locale 2).
	 * @param $titleAlt2 string
	 */
	function setTitleAlt2($titleAlt2) {
		return $this->setData('titleAlt2', $titleAlt2);
	}
	
	/**
	 * Get track title abbreviation.
	 * @return string
	 */
	function getAbbrev() {
		return $this->getData('abbrev');
	}
	
	/**
	 * Set track title abbreviation.
	 * @param $abbrev string
	 */
	function setAbbrev($abbrev) {
		return $this->setData('abbrev', $abbrev);
	}
	
	/**
	 * Get track title abbreviation (alternate locale 1).
	 * @return string
	 */
	function getAbbrevAlt1() {
		return $this->getData('abbrevAlt1');
	}
	
	/**
	 * Set track title abbreviation (alternate locale 1).
	 * @param $abbrevAlt1 string
	 */
	function setAbbrevAlt1($abbrevAlt1) {
		return $this->setData('abbrevAlt1', $abbrevAlt1);
	}
	
	/**
	 * Get track title abbreviation (alternate locale 2).
	 * @return string
	 */
	function getAbbrevAlt2() {
		return $this->getData('abbrevAlt2');
	}
	
	/**
	 * Set track title abbreviation (alternate locale 2).
	 * @param $abbrevAlt2 string
	 */
	function setAbbrevAlt2($abbrevAlt2) {
		return $this->setData('abbrevAlt2', $abbrevAlt2);
	}
	
	/**
	 * Get sequence of track.
	 * @return float
	 */
	function getSequence() {
		return $this->getData('sequence');
	}
	
	/**
	 * Set sequence of track.
	 * @param $sequence float
	 */
	function setSequence($sequence) {
		return $this->setData('sequence', $sequence);
	}
	
	/**
	 * Get open archive setting of track.
	 * @return boolean
	 */
	function getMetaIndexed() {
		return $this->getData('metaIndexed');
	}
	
	/**
	 * Set open archive setting of track.
	 * @param $metaIndexed boolean
	 */
	function setMetaIndexed($metaIndexed) {
		return $this->setData('metaIndexed', $metaIndexed);
	}
	
	/**
	 * Get peer review setting of track.
	 * @return boolean
	 */
	function getMetaReviewed() {
		return $this->getData('metaReviewed');
	}
	
	/**
	 * Set peer review setting of track.
	 * @param $metaReviewed boolean
	 */
	function setMetaReviewed($metaReviewed) {
		return $this->setData('metaReviewed', $metaReviewed);
	}
	
	/**
	 * Get string identifying type of items in this track.
	 * @return string
	 */
	function getIdentifyType() {
		return $this->getData('identifyType');
	}
	
	/**
	 * Set string identifying type of items in this track.
	 * @param $identifyType string
	 */
	function setIdentifyType($identifyType) {
		return $this->setData('identifyType', $identifyType);
	}
	
	/**
	 * Return boolean indicating whether or not submissions are restricted to [track] directors.
	 * @return boolean
	 */
	function getDirectorRestricted() {
		return $this->getData('directorRestricted');
	}
	
	/**
	 * Set whether or not submissions are restricted to [track] directors.
	 * @param $directorRestricted boolean
	 */
	function setDirectorRestricted($directorRestricted) {
		return $this->setData('directorRestricted', $directorRestricted);
	}
	
	/**
	 * Get policy.
	 * @return string
	 */
	function getPolicy() {
		return $this->getData('policy');
	}
	
	/**
	 * Set policy.
	 * @param $policy string
	 */
	function setPolicy($policy) {
		return $this->setData('policy', $policy);
	}
	
}

?>
