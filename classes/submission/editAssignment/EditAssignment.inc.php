<?php

/**
 * @file EditAssignment.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EditAssignment
 * @ingroup submission
 * @see EditAssignmentDAO
 *
 * @brief Describes edit assignment properties.
 */

//$Id$

class EditAssignment extends DataObject {

	/**
	 * Constructor.
	 */
	function EditAssignment() {
		parent::DataObject();
	}

	//
	// Get/set methods
	//

	/**
	 * Get ID of edit assignment.
	 * @return int
	 */
	function getEditId() {
		return $this->getData('editId');
	}

	/**
	 * Set ID of edit assignment
	 * @param $editId int
	 */
	function setEditId($editId) {
		return $this->setData('editId', $editId);
	}

	/**
	 * Get ID of paper.
	 * @return int
	 */
	function getPaperId() {
		return $this->getData('paperId');
	}

	/**
	 * Set ID of paper.
	 * @param $paperId int
	 */
	function setPaperId($paperId) {
		return $this->setData('paperId', $paperId);
	}

	/**
	 * Get ID of director.
	 * @return int
	 */
	function getDirectorId() {
		return $this->getData('directorId');
	}

	/**
	 * Set ID of director.
	 * @param $directorId int
	 */
	function setDirectorId($directorId) {
		return $this->setData('directorId', $directorId);
	}

	/**
	 * Get flag indicating whether this entry is for a director or a track director.
	 * @return boolean
	 */
	function getIsDirector() {
		return $this->getData('isDirector');
	}

	/**
	 * Set flag indicating whether this entry is for a director or a track director.
	 * @param $isDirector boolean
	 */
	function setIsDirector($isDirector) {
		return $this->setData('isDirector', $isDirector);
	}

	/**
	 * Get date director notified.
	 * @return timestamp
	 */
	function getDateNotified() {
		return $this->getData('dateNotified');
	}

	/**
	 * Set date director notified.
	 * @param $dateNotified timestamp
	 */
	function setDateNotified($dateNotified) {
		return $this->setData('dateNotified', $dateNotified);
	}

	/**
	 * Get date director underway.
	 * @return timestamp
	 */
	function getDateUnderway() {
		return $this->getData('dateUnderway');
	}

	/**
	 * Set date director underway.
	 * @param $dateUnderway timestamp
	 */
	function setDateUnderway($dateUnderway) {
		return $this->setData('dateUnderway', $dateUnderway);
	}

	/**
	 * Get full name of director.
	 * @return string
	 */
	function getDirectorFullName() {
		return $this->getData('directorFullName');
	}

	/**
	 * Set full name of director.
	 * @param $directorFullName string
	 */
	function setDirectorFullName($directorFullName) {
		return $this->setData('directorFullName', $directorFullName);
	}

	/**
	 * Get first name of director.
	 * @return string
	 */
	function getDirectorFirstName() {
		return $this->getData('directorFirstName');
	}

	/**
	 * Set first name of director.
	 * @param $directorFirstName string
	 */
	function setDirectorFirstName($directorFirstName) {
		return $this->setData('directorFirstName', $directorFirstName);
	}

	/**
	 * Get last name of director.
	 * @return string
	 */
	function getDirectorLastName() {
		return $this->getData('directorLastName');
	}

	/**
	 * Set last name of director.
	 * @param $directorLastName string
	 */
	function setDirectorLastName($directorLastName) {
		return $this->setData('directorLastName', $directorLastName);
	}

	/**
	 * Get initials of director.
	 * @return string
	 */
	function getDirectorInitials() {
		if ($this->getData('directorInitials')) {
			return $this->getData('directorInitials');
		} else {
			return substr($this->getDirectorFirstName(), 0, 1) . substr($this->getDirectorLastName(), 0, 1);
		}
	}

	/**
	 * Set initials of director.
	 * @param $directorInitials string
	 */
	function setDirectorInitials($directorInitials) {
		return $this->setData('directorInitials', $directorInitials);
	}

	/**
	 * Get email of director.
	 * @return string
	 */
	function getDirectorEmail() {
		return $this->getData('directorEmail');
	}

	/**
	 * Set full name of director.
	 * @param $directorEmail string
	 */
	function setDirectorEmail($directorEmail) {
		return $this->setData('directorEmail', $directorEmail);
	}
}

?>
