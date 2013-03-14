<?php

/**
 * @file classes/notification/NotificationManager.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPNotificationManager
 * @ingroup notification
 * @see NotificationDAO
 * @see Notification
 * @brief Class for Notification Manager.
 */


import('lib.pkp.classes.notification.PKPNotificationManager');

class NotificationManager extends PKPNotificationManager {
	/* @var $privilegedRoles array Cache each user's most privileged role for each paper */
	var $privilegedRoles;

	/**
	 * Constructor.
	 */
	function NotificationManager() {
		parent::PKPNotificationManager();
	}

	/**
	 * Construct the contents for the notification based on its type and associated object
	 * @param $request PKPRequest
	 * @param $notification Notification
	 * @return string
	 */
	function getNotificationContents(&$request, &$notification) {
		$type = $notification->getType();
		assert(isset($type));

		$message = null;
		HookRegistry::call('NotificationManager::getNotificationContents', array(&$notification, &$message));
		if($message) return $message;

		switch ($type) {
			case NOTIFICATION_TYPE_PAPER_SUBMITTED:
				return __('notification.type.paperSubmitted', array('title' => $this->_getPaperTitle($notification)));
			case NOTIFICATION_TYPE_SUPP_FILE_MODIFIED:
				return __('notification.type.suppFileModified', array('title' => $this->_getPaperTitle($notification)));
			case NOTIFICATION_TYPE_METADATA_MODIFIED:
				return __('notification.type.metadataModified', array('title' => $this->_getPaperTitle($notification)));
			case NOTIFICATION_TYPE_GALLEY_MODIFIED:
				return __('notification.type.galleyModified', array('title' => $this->_getPaperTitle($notification)));
			case NOTIFICATION_TYPE_SUBMISSION_COMMENT:
				return __('notification.type.submissionComment', array('title' => $this->_getPaperTitle($notification)));
			case NOTIFICATION_TYPE_REVIEWER_COMMENT:
				return __('notification.type.reviewerComment', array('title' => $this->_getPaperTitle($notification)));
			case NOTIFICATION_TYPE_REVIEWER_FORM_COMMENT:
				return __('notification.type.reviewerFormComment', array('title' => $this->_getPaperTitle($notification)));
			case NOTIFICATION_TYPE_DIRECTOR_DECISION_COMMENT:
				return __('notification.type.directorDecisionComment', array('title' => $this->_getPaperTitle($notification)));
			case NOTIFICATION_TYPE_USER_COMMENT:
				return __('notification.type.userComment', array('title' => $this->_getPaperTitle($notification)));
			default:
				return parent::getNotificationContents($request, $notification);
		}
	}

	/**
	 * Construct a URL for the notification based on its type and associated object
	 * @param $request PKPRequest
	 * @param $notification Notification
	 * @return string
	 */
	function getNotificationUrl(&$request, &$notification) {
		$router =& $request->getRouter();
		$type = $notification->getType();

		switch ($type) {
			case NOTIFICATION_TYPE_PAPER_SUBMITTED:
				$role = $this->_getCachedRole($request, $notification);
				return $router->url($request, array($this->_getConfPathFromPaperNotification($notification), $this->_getSchedConfPathFromPaperNotification($notification)), $role, 'submission', $notification->getAssocId());
			case NOTIFICATION_TYPE_SUPP_FILE_MODIFIED:
				$role = $this->_getCachedRole($request, $notification, array(ROLE_ID_DIRECTOR, ROLE_ID_TRACK_DIRECTOR, ROLE_ID_AUTHOR));
				return $router->url($request, array($this->_getConfPathFromPaperNotification($notification), $this->_getSchedConfPathFromPaperNotification($notification)), $role, 'submissionReview', $notification->getAssocId(), null, 'layout');
			case NOTIFICATION_TYPE_METADATA_MODIFIED:
				$role = $this->_getCachedRole($request, $notification);
				return $router->url($request, array($this->_getConfPathFromPaperNotification($notification), $this->_getSchedConfPathFromPaperNotification($notification)), $role, 'submission', $notification->getAssocId(), null, 'metadata');
			case NOTIFICATION_TYPE_GALLEY_MODIFIED:
				$role = $this->_getCachedRole($request, $notification, array(ROLE_ID_DIRECTOR, ROLE_ID_TRACK_DIRECTOR, ROLE_ID_AUTHOR));
				return $router->url($request, array($this->_getConfPathFromPaperNotification($notification), $this->_getSchedConfPathFromPaperNotification($notification)), $role, 'submissionEditing', $notification->getAssocId(), null, 'layout');
			case NOTIFICATION_TYPE_SUBMISSION_COMMENT:
				$role = $this->_getCachedRole($request, $notification, array(ROLE_ID_DIRECTOR, ROLE_ID_TRACK_DIRECTOR, ROLE_ID_AUTHOR));
				return $router->url($request, array($this->_getConfPathFromPaperNotification($notification), $this->_getSchedConfPathFromPaperNotification($notification)), $role, 'submissionReview', $notification->getAssocId(), null, 'editorDecision');
			case NOTIFICATION_TYPE_DIRECTOR_DECISION_COMMENT:
				$role = $this->_getCachedRole($request, $notification, array(ROLE_ID_DIRECTOR, ROLE_ID_TRACK_DIRECTOR, ROLE_ID_AUTHOR));
				return $router->url($request, array($this->_getConfPathFromPaperNotification($notification), $this->_getSchedConfPathFromPaperNotification($notification)), $role, 'submissionEditing', $notification->getAssocId(), null, 'directorDecision');
			case NOTIFICATION_TYPE_REVIEWER_COMMENT:
			case NOTIFICATION_TYPE_REVIEWER_FORM_COMMENT:
				$role = $this->_getCachedRole($request, $notification, array(ROLE_ID_DIRECTOR, ROLE_ID_TRACK_DIRECTOR, ROLE_ID_AUTHOR));
				return $router->url($request, array($this->_getConfPathFromPaperNotification($notification), $this->_getSchedConfPathFromPaperNotification($notification)), $role, 'submissionReview', $notification->getAssocId(), null, 'peerReview');
			case NOTIFICATION_TYPE_USER_COMMENT:
				return $router->url($request, null, 'comment', 'view', $notification->getAssocId());
			case NOTIFICATION_TYPE_NEW_ANNOUNCEMENT:
				assert($notification->getAssocType() == ASSOC_TYPE_ANNOUNCEMENT);
				return $router->url($request, null, 'announcement', 'view', array($notification->getAssocId()));
			default:
				return parent::getNotificationUrl($request, $notification);
		}
	}

	/**
	 * Get a scheduled conference path from a notification with a Paper assoc_type
	 * @param $notification
	 * @return string
	 */
	function _getSchedConfPathFromPaperNotification($notification) {
		assert($notification->getAssocType() == ASSOC_TYPE_PAPER);
		$paperId = (int) $notification->getAssocId();

		$paperDao = DAORegistry::getDAO('PaperDAO'); /* @var $paperDao PaperDAO */
		$paper =& $paperDao->getPaper($paperId);

		$schedConfDao = DAORegistry::getDAO('SchedConfDAO'); /* @var $schedConfDao SchedConfDAO */
		$schedConf = $schedConfDao->getById($paper->getSchedConfId());

		return $schedConf->getPath();
	}

	/**
	 * Get a conference path from a notification with a Paper assoc_type
	 * @param $notification
	 * @return string
	 */
	function _getConfPathFromPaperNotification($notification) {
		assert($notification->getAssocType() == ASSOC_TYPE_PAPER);
		$paperId = (int) $notification->getAssocId();

		$paperDao = DAORegistry::getDAO('PaperDAO'); /* @var $paperDao PaperDAO */
		$paper =& $paperDao->getPaper($paperId);

		$schedConfDao = DAORegistry::getDAO('SchedConfDAO'); /* @var $schedConfDao SchedConfDAO */
		$schedConf = $schedConfDao->getById($paper->getSchedConfId());

		$conferenceDao = DAORegistry::getDAO('ConferenceDAO'); /* @var $conferenceDaoDao ConferenceDAO */
		$conferenceId = $schedConf->getConferenceId();
		$conference =& $conferenceDao->getById($conferenceId);

		return $conference->getPath();
	}

	/*
	 * Get the cached list of roles for a user WRT a paper, or generate them if missing
	 * @param $request Request
	 * @param $notification Notification
	 * @param $validRoles array List of possible roles to return
	 */
	function _getCachedRole(&$request, &$notification, $validRoles = null) {
		assert($notification->getAssocType() == ASSOC_TYPE_PAPER);
		$paperId = (int) $notification->getAssocId();
		$userId = $notification->getUserId();

		// Check if we've already set the roles for this user and paper, otherwise fetch them
		if(!isset($this->privilegedRoles[$userId][$paperId])) $this->privilegedRoles[$userId][$paperId] = $this->_getHighestPrivilegedRolesForPaper($request, $paperId);

		$roleDao = DAORegistry::getDAO('RoleDAO'); /* @var $roleDao RoleDAO */

		if(is_array($validRoles)) {
			// We've specified a list of roles that should be the only roles considered
			foreach ($this->privilegedRoles[$userId][$paperId] as $roleId) {
				// Get the first role that is in the validRoles list
				if (in_array($roleId, $validRoles)) {
					$role =& $roleDao->newDataObject();
					$role->setId($roleId);
					return $role->getPath();
				}
			}
		} else {
			// Return first (most privileged) role
			$roleId = isset($this->privilegedRoles[$userId][$paperId][0]) ? $this->privilegedRoles[$userId][$paperId][0] : null;
			$role =& $roleDao->newDataObject();
			$role->setId($roleId);
			return $role->getPath();
		}
	}

	/**
	 * Get a list of the most 'privileged' roles a user has associated with an paper.  This will
	 *  determine the URL to point them to for notifications about papers.  Returns roles in
	 *  order of 'importance'
	 * @param $paperId
	 * @return array
	 */
	function _getHighestPrivilegedRolesForPaper(&$request, $paperId) {
		$user =& $request->getUser();
		$userId = $user->getId();
		$roleDao = DAORegistry::getDAO('RoleDAO'); /* @var $roleDao RoleDAO */
		$paperDao = DAORegistry::getDAO('PaperDAO'); /* @var $paperDao PaperDAO */
		$paper =& $paperDao->getPaper($paperId);

		$schedConfDao = DAORegistry::getDAO('SchedConfDAO'); /* @var $schedConfDao SchedConfDAO */
		$schedConf = $schedConfDao->getById($paper->getSchedConfId());

		$roles = array();
		// Check if user is director
		if(Validation::isDirector($schedConf->getConferenceId(), $schedConf->getId())) {
			$roles[] = ROLE_ID_DIRECTOR;
		}

		$editAssignmentDao = DAORegistry::getDAO('EditAssignmentDAO'); /* @var $editAssignmentDao EditAssignmentDAO */
		$editAssignments =& $editAssignmentDao->getTrackDirectorAssignmentsByPaperId($paperId);
		while ($editAssignment =& $editAssignments->next()) {
			if ($userId == $editAssignment->getDirectorId()) $roles[] = ROLE_ID_TRACK_DIRECTOR;
			unset($editAssignment);
		}

		// Check if user is author
		if ($userId == $paper->getUserId()) $roles[] = ROLE_ID_AUTHOR;

		// Check if user is reviewer
		$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO'); /* @var $reviewAssignmentDao ReviewAssignmentDAO */
		$reviewAssignments =& $reviewAssignmentDao->getBySubmissionId($paperId);
		foreach ($reviewAssignments as $reviewAssignment) {
			if ($userId == $reviewAssignment->getReviewerId()) $roles[] = ROLE_ID_REVIEWER;
		}

		return $roles;
	}

	/**
	 * Helper function to get an paper title from a notification's associated object
	 * @param $notification
	 * @return string
	 */
	function _getPaperTitle(&$notification) {
		assert($notification->getAssocType() == ASSOC_TYPE_PAPER);
		assert(is_numeric($notification->getAssocId()));
		$paperDao = DAORegistry::getDAO('PaperDAO'); /* @var $paperDao PaperDAO */
		$paper =& $paperDao->getPaper($notification->getAssocId());
		return $paper->getLocalizedTitle();
	}

	/**
	 * Return a CSS class containing the icon of this notification type
	 * @param $notification Notification
	 * @return string
	 */
	function getIconClass(&$notification) {
		switch ($notification->getType()) {
			case NOTIFICATION_TYPE_PAPER_SUBMITTED:
				return 'notifyIconNewPage';
			case NOTIFICATION_TYPE_SUPP_FILE_MODIFIED:
				return 'notifyIconPageAttachment';
			case NOTIFICATION_TYPE_METADATA_MODIFIED:
			case NOTIFICATION_TYPE_GALLEY_MODIFIED:
				return 'notifyIconEdit';
			case NOTIFICATION_TYPE_SUBMISSION_COMMENT:
			case NOTIFICATION_TYPE_REVIEWER_COMMENT:
			case NOTIFICATION_TYPE_REVIEWER_FORM_COMMENT:
			case NOTIFICATION_TYPE_DIRECTOR_DECISION_COMMENT:
			case NOTIFICATION_TYPE_USER_COMMENT:
				return 'notifyIconNewComment';
			case NOTIFICATION_TYPE_NEW_ANNOUNCEMENT:
				return 'notifyIconNewAnnouncement';
			default: return parent::getIconClass($notification);
		}
	}

	/**
	 * Returns an array of information on the conference's subscription settings
	 * @return array
	 */
	function getSubscriptionSettings() {
		$conference = Request::getConference();
		if (!$conference) return array();

		import('classes.payment.ocs.OCSPaymentManager');
		$paymentManager = new OCSPaymentManager($request);

		$settings = array(
			'allowRegReviewer' => $conference->getSetting('allowRegReviewer'),
			'allowRegAuthor' => $conference->getSetting('allowRegAuthor')
		);

		return $settings;
	}
}

?>
