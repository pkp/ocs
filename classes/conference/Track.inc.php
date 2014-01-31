<?php

/**
 * @file Track.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Track
 * @ingroup conference
 * @see TrackDAO
 *
 * @brief Describes basic track properties.
 *
 */

// $Id$


class Track extends DataObject {

	/**
	 * Constructor.
	 */
	function Track() {
		parent::DataObject();
	}

	/**
	 * Get localized title of conference track.
	 * @return string
	 */
	function getLocalizedTitle() {
		return $this->getLocalizedData('title');
	}

	function getTrackTitle() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedTitle();
	}

	/**
	 * Get localized abbreviation of conference track.
	 */
	function getLocalizedAbbrev() {
		return $this->getLocalizedData('abbrev');
	}

	function getTrackAbbrev() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedAbbrev();
	}

	//
	// Get/set methods
	//

	/**
	 * Get ID of track.
	 * @return int
	 */
	function getTrackId() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getId();
	}

	/**
	 * Set ID of track.
	 * @param $trackId int
	 */
	function setTrackId($trackId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->setId($trackId);
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
	 * Get ID of primary review form.
	 * @return int
	 */
	function getReviewFormId() {
		return $this->getData('reviewFormId');
	}

	/**
	 * Set ID of primary review form.
	 * @param $reviewFormId int
	 */
	function setReviewFormId($reviewFormId) {
		return $this->setData('reviewFormId', $reviewFormId);
	}

	/**
	 * Get title of track.
	 * @param $locale string
	 * @return string
	 */
	function getTitle($locale) {
		return $this->getData('title', $locale);
	}

	/**
	 * Set title of track.
	 * @param $title string
	 * @param $locale string
	 */
	function setTitle($title, $locale) {
		return $this->setData('title', $title, $locale);
	}

	/**
	 * Get track title abbreviation.
	 * @param $locale string
	 * @return string
	 */
	function getAbbrev($locale) {
		return $this->getData('abbrev', $locale);
	}

	/**
	 * Set track title abbreviation.
	 * @param $abbrev string
	 * @param $locale string
	 */
	function setAbbrev($abbrev, $locale) {
		return $this->setData('abbrev', $abbrev, $locale);
	}
	
	/**
	 * Get abstract word count limit.
	 * @return int
	 */
	function getAbstractWordCount() {
		return $this->getData('wordCount');
	}


	/**
	 * Set abstract word count limit.
	 * @param $wordCount int
	 */
	function setAbstractWordCount($wordCount) {
		return $this->setData('wordCount', $wordCount);
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
	 * Get localized string identifying type of items in this track.
	 * @return string
	 */
	function getLocalizedIdentifyType() {
		return $this->getLocalizedData('identifyType');
	}

	function getTrackIdentifyType() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedIdentifyType();
	}

	/**
	 * Get string identifying type of items in this track.
	 * @param $locale string
	 * @return string
	 */
	function getIdentifyType($locale) {
		return $this->getData('identifyType', $locale);
	}

	/**
	 * Set string identifying type of items in this track.
	 * @param $identifyType string
	 * @param $locale string
	 */
	function setIdentifyType($identifyType, $locale) {
		return $this->setData('identifyType', $identifyType, $locale);
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
	 * Return boolean indicating if title should be hidden in About.
	 * @return boolean
	 */
	function getHideAbout() {
		return $this->getData('hideAbout');
	}

	/**
	 * Set if title should be hidden in About.
	 * @param $hideAbout boolean
	 */
	function setHideAbout($hideAbout) {
		return $this->setData('hideAbout', $hideAbout);
	}

	/**
	 * Return boolean indicating if RT comments should be disabled.
	 * @return boolean
	 */
	function getDisableComments() {
		return $this->getData('disableComments');
	}

	/**
	 * Set if RT comments should be disabled.
	 * @param $disableComments boolean
	 */
	function setDisableComments($disableComments) {
		return $this->setData('disableComments', $disableComments);
	}

	/**
	 * Get localized track policy.
	 * @return string
	 */
	function getLocalizedPolicy() {
		return $this->getLocalizedData('policy');
	}

	function getTrackPolicy() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedPolicy();
	}

	/**
	 * Get policy.
	 * @param $locale string
	 * @return string
	 */
	function getPolicy($locale) {
		return $this->getData('policy', $locale);
	}

	/**
	 * Set policy.
	 * @param $policy string
	 * @param $locale string
	 */
	function setPolicy($policy, $locale) {
		return $this->setData('policy', $policy, $locale);
	}
}

?>
