<?php

/**
 * AnnouncementType.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package announcement 
 *
 * AnnouncementType class.
 * Basic class describing an announcement type.
 *
 * $Id$
 */

class AnnouncementType extends DataObject {

	function AnnouncementType() {
		parent::DataObject();
	}
	
	//
	// Get/set methods
	//
	
	/**
	 * Get the ID of the announcement type.
	 * @return int
	 */
	function getTypeId() {
		return $this->getData('typeId');
	}
	
	/**
	 * Set the ID of the announcement type.
	 * @param $typeId int
	 */
	function setTypeId($typeId) {
		return $this->setData('typeId', $typeId);
	}

	/**
	 * Get the conference ID of the announcement type.
	 * @return int
	 */
	function getConferenceId() {
		return $this->getData('conferenceId');
	}
	
	/**
	 * Set the conference ID of the announcement type.
	 * @param $conferenceId int
	 */
	function setConferenceId($conferenceId) {
		return $this->setData('conferenceId', $conferenceId);
	}

	/**
	 * Get the type of the announcement type.
	 * @return int
	 */
	function getTypeName() {
		return $this->getData('typeName');
	}
	
	/**
	 * Set the type of the announcement type.
	 * @param $typeName int
	 */
	function setTypeName($typeName) {
		return $this->setData('typeName', $typeName);
	}

}

?>
