<?php

/**
 * @file Group.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package group
 * @class Group
 *
 * Group class.
 * Describes user groups in conferences.
 *
 * $Id$
 */

class Group extends DataObject {
	/**
	 * Get localized title of conference group.
	 */
	function getGroupTitle() {
		return $this->getLocalizedData('title');
	}

	//
	// Get/set methods
	//

	/**
	 * Get title of group (primary locale)
	 * @param $locale string
	 * @return string
	 */
	function getTitle($locale) {
		return $this->getData('title', $locale);
	}

	/**
	 * Set title of group
	 * @param $title string
	 * @param $locale string
	 */
	function setTitle($title, $locale) {
		return $this->setData('title', $title, $locale);
	}

	/**
	 * Get flag indicating whether or not the group is displayed in "About"
	 * @return boolean
	 */
	function getAboutDisplayed() {
		return $this->getData('aboutDisplayed');
	}

	/**
	 * Set flag indicating whether or not the group is displayed in "About"
	 * @param $aboutDisplayed boolean
	 */
	function setAboutDisplayed($aboutDisplayed) {
		return $this->setData('aboutDisplayed',$aboutDisplayed);
	}

	/**
	 * Get ID of group.
	 * @return int
	 */
	function getGroupId() {
		return $this->getData('groupId');
	}

	/**
	 * Set ID of group.
	 * @param $groupId int
	 */
	function setGroupId($groupId) {
		return $this->setData('groupId', $groupId);
	}

	/**
	 * Get ID of scheduled conference this group belongs to.
	 * @return int
	 */
	function getSchedConfId() {
		return $this->getData('schedConfId');
	}

	/**
	 * Set ID of scheduled conference this group belongs to.
	 * @param $schedConfId int
	 */
	function setSchedConfId($schedConfId) {
		return $this->setData('schedConfId', $schedConfId);
	}

	/**
	 * Get ID of conference this group belongs to.
	 * @return int
	 */
	function getConferenceId() {
		return $this->getData('conferenceId');
	}

	/**
	 * Set ID of conference this group belongs to.
	 * @param $conferenceId int
	 */
	function setConferenceId($conferenceId) {
		return $this->setData('conferenceId', $conferenceId);
	}

	/**
	 * Get sequence of group.
	 * @return float
	 */
	function getSequence() {
		return $this->getData('sequence');
	}

	/**
	 * Set sequence of group.
	 * @param $sequence float
	 */
	function setSequence($sequence) {
		return $this->setData('sequence', $sequence);
	}
}

?>
