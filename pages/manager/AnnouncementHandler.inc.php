<?php

/**
 * @file AnnouncementHandler.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for announcement management functions. 
 */

//$Id$

import('lib.pkp.pages.manager.PKPAnnouncementHandler');

class AnnouncementHandler extends PKPAnnouncementHandler {
	/**
	 * Constructor
	 **/
	function AnnouncementHandler() {
		parent::PKPAnnouncementHandler();
	}
	/**
	 * Display a list of announcements for the current conference.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function announcements($args, &$request) {
		$templateMgr =& TemplateManager::getManager();		

 		$conference =& $request->getConference();
		if ($conference) {
			$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
			$schedConfs =& $schedConfDao->getSchedConfs(false, $conference->getId());
			$schedConfNames = array(0 => Locale::translate('common.all'));
			foreach($schedConfs->toArray() as $schedConf) {
				$schedConfNames[$schedConf->getId()] = $schedConf->getLocalizedTitle();
			}

			$templateMgr->assign_by_ref('schedConfNames', $schedConfNames);
		}
		
		//TODO: move this assignment to the abstracted templates or generalize the key 
		$templateMgr->assign('helpTopicId', 'conference.generalManagement.announcements');	
		parent::announcements($args, $request);
	}

	/**
	 * Display a list of announcement types for the current conference.
	 * @see PKPAnnouncementHandler::announcementTypes
	 */
	function announcementTypes($args, &$request) {
		$templateMgr =& TemplateManager::getManager();
		//TODO: move this assignment to the abstracted templates or generalize the key 
		$templateMgr->assign('helpTopicId', 'conference.generalManagement.announcements');
		parent::announcementTypes($args, $request);
	}		

	/**
	 * @see PKPAnnouncementHandler::_getAnnouncements
	 */
	function &_getAnnouncements($request, $rangeInfo = null) {
		$conference =& $request->getConference();
		$announcementDao =& DAORegistry::getDAO('AnnouncementDAO');
		$announcements =& $announcementDao->getAnnouncementsByConferenceId($conference->getId(), -1, $rangeInfo);

		return $announcements;
	}

	/**
	 * @see PKPAnnouncementHandler::_getAnnouncementTypes
	 */
	function &_getAnnouncementTypes($request, $rangeInfo = null) {
		$conference =& $request->getConference();
		$announcementTypeDao =& DAORegistry::getDAO('AnnouncementTypeDAO');
		$announcements =& $announcementTypeDao->getAnnouncementTypesByAssocId(ASSOC_TYPE_CONFERENCE, $conference->getId(), $rangeInfo);

		return $announcements;
	}	

	/**
	 * Checks the announcement to see if it belongs to this conference or scheduled conference
	 * @param $request PKPRequest
	 * @param $announcementId int
	 * return bool
	 */	
	function _announcementIsValid($request, $announcementId) {
		if ($announcementId == null) 
			return true;

		$announcementDao =& DAORegistry::getDAO('AnnouncementDAO');
		$announcement =& $announcementDao->getAnnouncement($announcementId);
		if ( !$announcement ) return false;
		
		$conference =& $request->getConference();
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
	 * @param $request PKPRequest
	 * @param $typeId int
	 * return bool
	 */
	function _announcementTypeIsValid($request, $typeId) {
		$conference =& $request->getConference();
		$announcementTypeDao =& DAORegistry::getDAO('AnnouncementTypeDAO');
		return (($typeId != null && $announcementTypeDao->getAnnouncementTypeAssocId($typeId) == $conference->getId()) || $typeId == null);
	}
}

?>
