<?php

/**
 * @file AnnouncementHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for announcement management functions. 
 */

//$Id$

import('manager.PKPAnnouncementHandler');

class AnnouncementHandler extends PKPAnnouncementHandler {
	/**
	 * Constructor
	 **/
	function AnnouncementHandler() {
		parent::PKPAnnouncementHandler();
	}
	/**
	 * Display a list of announcements for the current conference.
	 */
	function announcements() {
		$templateMgr =& TemplateManager::getManager();		

 		$conference =& Request::getConference();
		if ( $conference ) {
			$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
			$schedConfs =& $schedConfDao->getSchedConfsByConferenceId($conference->getId());
			$schedConfNames = array(0 => __('common.all'));
			foreach($schedConfs->toArray() as $schedConf) {
				$schedConfNames[$schedConf->getId()] = $schedConf->getSchedConfTitle();
			}

			$templateMgr->assign_by_ref('schedConfNames', $schedConfNames);
		}
		
		//TODO: move this assignment to the abstracted templates or generalize the key 
		$templateMgr->assign('helpTopicId', 'conference.generalManagement.announcements');	
		parent::announcements();
	}

	/**
	 * Display a list of announcement types for the current conference.
	 */
	function announcementTypes() {
		$templateMgr =& TemplateManager::getManager();
		//TODO: move this assignment to the abstracted templates or generalize the key 
		$templateMgr->assign('helpTopicId', 'conference.generalManagement.announcements');
		parent::announcementTypes();
	}		

	function &_getAnnouncements($rangeInfo = null) {
		$conference =& Request::getConference();
		$announcementDao =& DAORegistry::getDAO('AnnouncementDAO');
		$announcements =& $announcementDao->getAnnouncementsByConferenceId($conference->getId(), -1, $rangeInfo);

		return $announcements;
	}
	
	function &_getAnnouncementTypes($rangeInfo = null) {
		$conference =& Request::getConference();
		$announcementTypeDao =& DAORegistry::getDAO('AnnouncementTypeDAO');
		$announcements =& $announcementTypeDao->getAnnouncementTypesByAssocId(ASSOC_TYPE_CONFERENCE, $conference->getId(), $rangeInfo);

		return $announcements;
	}	

	/**
	 * Checks the announcement to see if it belongs to this conference or scheduled conference
	 * @param $announcementId int
	 * return bool
	 */	
	function _announcementIsValid($announcementId) {
		if ($announcementId == null) 
			return true;

		$announcementDao =& DAORegistry::getDAO('AnnouncementDAO');
		$announcement =& $announcementDao->getAnnouncement($announcementId);
		if ( !$announcement ) return false;
		
		$conference =& Request::getConference();
		if ( $conference 
			&& $announcement->getAssocType() == ASSOC_TYPE_CONFERENCE 
			&& $announcement->getAssocId() == $conference->getId())
				return true;
		
		// if its a schedConf announcements, make sure it is for a schedConf that belongs to the current conference
		if ( $announcement->getAssocType() == ASSOC_TYPE_SCHED_CONF ) {
			$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
			$schedConf =& $schedConfDao->getSchedConf($announcement->getAssocId());
			if ( $schedConf 
				&& $conference 
				&& $schedConf->getConferenceId() == $conference->getId() ) 
					return true;
		}
					
		return false;
	}	

	/**
	 * Checks the announcement type to see if it belongs to this conference.  All announcement types are set at the conference level.
	 * @param $typeId int
	 * return bool
	 */
	function _announcementTypeIsValid($typeId) {
		$conference =& Request::getConference();
		$announcementTypeDao =& DAORegistry::getDAO('AnnouncementTypeDAO');
		return (($typeId != null && $announcementTypeDao->getAnnouncementTypeAssocId($typeId) == $conference->getId()) || $typeId == null);
	}
}

?>
