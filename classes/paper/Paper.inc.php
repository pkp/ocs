<?php

/**
 * @defgroup paper
 */

/**
 * @file Paper.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Paper
 * @ingroup paper
 * @see PaperDAO
 *
 * @brief Paper class.
 *
 */


// Submission status constants
define('STATUS_ARCHIVED', 0);
define('STATUS_QUEUED', 1);
define('STATUS_PUBLISHED', 3);
define('STATUS_DECLINED', 4);

// AuthorSubmission::getSubmissionStatus will return one of these in place of QUEUED:
define ('STATUS_QUEUED_UNASSIGNED', 5);
define ('STATUS_QUEUED_REVIEW', 6);
define ('STATUS_QUEUED_EDITING', 7);
define ('STATUS_INCOMPLETE', 8);

define ('REVIEW_ROUND_ABSTRACT', 1);
define ('REVIEW_ROUND_PRESENTATION', 2);
define ('REVIEW_STAGE_ABSTRACT', 1);		// DEPRECATED
define ('REVIEW_STAGE_PRESENTATION', 2);	// DEPRECATED

/* These constants are used as search fields for the various submission lists */
define('SUBMISSION_FIELD_AUTHOR', 1);
define('SUBMISSION_FIELD_DIRECTOR', 2);
define('SUBMISSION_FIELD_TITLE', 3);
define('SUBMISSION_FIELD_REVIEWER', 4);
define('SUBMISSION_FIELD_ID', 8);

define('SUBMISSION_FIELD_DATE_SUBMITTED', 5);

// Paper RT comments
define ('COMMENTS_TRACK_DEFAULT', 0);
define ('COMMENTS_DISABLE', 1);
define ('COMMENTS_ENABLE', 2);

import('lib.pkp.classes.submission.Submission');

class Paper extends Submission {
	/**
	 * Constructor.
	 */
	function Paper() {
		parent::Submission();
	}

	/**
	 * @see Submission::getAssocType()
	 */
	function getAssocType() {
		return ASSOC_TYPE_PAPER;
	}

	/**
	 * Get "localized" paper title (if applicable). DEPRECATED
	 * in favour of getLocalizedTitle.
	 * @return string
	 */
	function getPaperTitle() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedTitle();
	}

	/**
	 * Get "localized" paper abstract (if applicable). DEPRECATED
	 * in favour of getLocalizedAbstract.
	 * @return string
	 */
	function getPaperAbstract() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedAbstract();
	}

	//
	// Get/set methods
	//

	/**
	 * Get ID of paper.
	 * @return int
	 */
	function getPaperId() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getId();
	}

	/**
	 * Set ID of paper.
	 * @param $paperId int
	 */
	function setPaperId($paperId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->setId($paperId);
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
	 * Get ID of paper's track.
	 * @return int
	 */
	function getTrackId() {
		return $this->getData('trackId');
	}

	/**
	 * Set ID of paper's track.
	 * @param $trackId int
	 */
	function setTrackId($trackId) {
		return $this->setData('trackId', $trackId);
	}

	/**
	 * Get title of paper's track.
	 * @return string
	 */
	function getTrackTitle() {
		return $this->getData('trackTitle');
	}

	/**
	 * Set title of paper's track.
	 * @param $trackTitle string
	 */
	function setTrackTitle($trackTitle) {
		return $this->setData('trackTitle', $trackTitle);
	}

	/**
	 * Get track abbreviation.
	 * @return string
	 */
	function getTrackAbbrev() {
		return $this->getData('trackAbbrev');
	}

	/**
	 * Set track abbreviation.
	 * @param $trackAbbrev string
	 */
	function setTrackAbbrev($trackAbbrev) {
		return $this->setData('trackAbbrev', $trackAbbrev);
	}

	/**
	 * Return the localized discipline. DEPRECATED in favour
	 * of getLocalizedDiscipline.
	 * @param $locale string
	 * @return string
	 */
	function getPaperDiscipline() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedDiscipline();
	}

	/**
	 * Return the localized subject classification. DEPRECATED
	 * in favour of getLocalizedSubjectClass.
	 * @param $locale string
	 * @return string
	 */
	function getPaperSubjectClass() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedSubjectClass();
	}

	/**
	 * Return the localized subject. DEPRECATED
	 * in favour of getLocalizedSubject.
	 * @return string
	 */
	function getPaperSubject() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedSubject();
	}

	/**
	 * Return the localized geo coverage. DEPRECATED in favour of
	 * getLocalizedCoverageGeo.
	 * @return string
	 */
	function getPaperCoverageGeo() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedCoverageGeo();
	}

	/**
	 * Return the localized chron coverage. DEPRECATED in favour
	 * of getLocalizedCoverageChron.
	 * @return string
	 */
	function getPaperCoverageChron() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedCoverageChron();
	}

	/**
	 * Return the localized sample coverage. DEPRECATED in favour
	 * of getLocalizedCoverageSample.
	 * @return string
	 */
	function getPaperCoverageSample() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedCoverageSample();
	}

	/**
	 * Return the localized type. DEPRECATED in favour of
	 * getLocalizedType.
	 * @return string
	 */
	function getPaperType() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedType();
	}

	/**
	 * Return the localized sponsor. DEPRECATED in favour of
	 * getLocalizedSponsor.
	 * @return string
	 */
	function getPaperSponsor() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getLocalizedSponsor();
	}

	/**
	 * Get comments to director.
	 * @return string
	 */
	function getCommentsToDirector() {
		return $this->getData('commentsToDirector');
	}

	/**
	 * Set comments to director.
	 * @param $commentsToDirector string
	 */
	function setCommentsToDirector($commentsToDirector) {
		return $this->setData('commentsToDirector', $commentsToDirector);
	}

	/**
	 * Get date sent to presentations.
	 * @return date
	 */
	function getDateToPresentations() {
		return $this->getData('dateToPresentations');
	}

	/**
	 * Set date sent to presentations.
	 * @param $dateToPresentations date
	 */
	function setDateToPresentations($dateToPresentations) {
		return $this->setData('dateToPresentations', $dateToPresentations);
	}

	/**
	 * Get date sent to presentations.
	 * @return date
	 */
	function getDateToArchive() {
		return $this->getData('dateToArchive');
	}

	/**
	 * Set date sent to presentations.
	 * @param $dateToArchive date
	 */
	function setDateToArchive($dateToArchive) {
		return $this->setData('dateToArchive', $dateToArchive);
	}

	/**
	 * Stamp the date moved to the archive to the current time.
	 */
	function stampDateToArchive() {
		return $this->setDateToArchive(Core::getCurrentDate());
	}

	/**
	 * Stamp the date moved to presentations to the current time.
	 */
	function stampDateToPresentations() {
		return $this->setDateToPresentations(Core::getCurrentDate());
	}

	/**
	 * Get the date of the "submission due" reminder.
	 * @return date
	 */
	function getDateReminded() {
		return $this->getData('dateReminded');
	}

	/**
	 * Set the date of the "submission due" reminder.
	 * @param $dateModified date
	 */
	function setDateReminded($dateReminded) {
		return $this->setData('dateReminded', $dateReminded);
	}

	/**
	 * Get the presentation start time.
	 * @return date
	 */
	function getStartTime() {
		return $this->getData('startTime');
	}

	/**
	 * Set the presentation start time.
	 * @param $startTime date
	 */
	function setStartTime($startTime) {
		return $this->setData('startTime', $startTime);
	}

	/**
	 * Get the presentation end time.
	 * @return date
	 */
	function getEndTime() {
		return $this->getData('endTime');
	}

	/**
	 * Get the presentation end time.
	 * @param $endTime date
	 */
	function setEndTime($endTime) {
		return $this->setData('endTime', $endTime);
	}

	/**
	 * Get current round.
	 * @return int
	 */
	function getCurrentRound() {
		return $this->getData('currentRound');
	}

	/**
	 * Set current round.
	 * @param $currentRound int
	 */
	function setCurrentRound($currentRound) {
		return $this->setData('currentRound', $currentRound);
	}

	/**
	 * Get current stage. DEPRECATED.
	 * @return int
	 */
	function getCurrentStage() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getCurrentRound();
	}

	/**
	 * Set current stage. DEPRECATED.
	 * @param $stage int
	 */
	function setCurrentStage($stage) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->setCurrentRound($stage);
	}

	/**
	 * Get review mode.
	 * @return int REVIEW_MODE_...
	 */
	function getReviewMode() {
		return $this->getData('reviewMode');
	}

	/**
	 * Set review mode.
	 * @param $reviewMode int REVIEW_MODE_...
	 */
	function setReviewMode($reviewMode) {
		return $this->setData('reviewMode', $reviewMode);
	}

	/**
	 * Get layout file id.
	 * @return int
	 */
	function getLayoutFileId() {
		return $this->getData('layoutFileId');
	}

	/**
	 * Set layout file id.
	 * @param $layoutFileId int
	 */
	function setLayoutFileId($layoutFileId) {
		return $this->setData('layoutFileId', $layoutFileId);
	}

	/**
	 * Get director file id.
	 * @return int
	 */
	function getDirectorFileId() {
		return $this->getData('directorFileId');
	}

	/**
	 * Set director file id.
	 * @param $directorFileId int
	 */
	function setDirectorFileId($directorFileId) {
		return $this->setData('directorFileId', $directorFileId);
	}

	/**
	 * Return locale string corresponding to RT comments status.
	 * @return string
	 */
	function getCommentsStatusString() {
		switch ($this->getCommentsStatus()) {
			case COMMENTS_DISABLE:
				return 'paper.comments.disable';
			case COMMENTS_ENABLE:
				return 'paper.comments.enable';
			default:
				return 'paper.comments.trackDefault';
		}
	}

	/**
	 * Return boolean indicating if paper RT comments should be enabled.
	 * Checks both the track and paper comments status. Paper status
	 * overrides track status.
	 * @return int
	 */
	function getEnableComments() {
		switch ($this->getCommentsStatus()) {
			case COMMENTS_DISABLE:
				return false;
			case COMMENTS_ENABLE:
				return true;
			case COMMENTS_TRACK_DEFAULT:
				$trackDao = DAORegistry::getDAO('TrackDAO');
				$track =& $trackDao->getTrack($this->getTrackId(), $this->getSchedConfId());
				if ($track->getDisableComments()) {
					return false;
				} else {
					return true;
				}
		}
	}

	/**
	 * Get an associative array matching RT comments status codes with locale strings.
	 * @return array comments status => localeString
	 */
	function &getCommentsStatusOptions() {
		static $commentsStatusOptions = array(
			COMMENTS_TRACK_DEFAULT => 'paper.comments.trackDefault',
			COMMENTS_DISABLE => 'paper.comments.disable',
			COMMENTS_ENABLE => 'paper.comments.enable'
		);
		return $commentsStatusOptions;
	}

	/**
	 * Get an array of user IDs associated with this paper
	 * @param $authors boolean
	 * @param $reviewers boolean
	 * @param $trackDirectors boolean
	 * @param $directors boolean
	 * @return array User IDs
	 */
	function getAssociatedUserIds($authors = true, $reviewers = true, $trackDirectors = true, $directors = true) {
		$paperId = $this->getId();

		$userIds = array();

		if($authors) {
			$userId = $this->getUserId();
			if ($userId) $userIds[] = array('id' => $userId, 'role' => 'author');
		}

		if($reviewers) {
			$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
			$reviewAssignments =& $reviewAssignmentDao->getBySubmissionId($paperId);
			foreach ($reviewAssignments as $reviewAssignment) {
				$userId = $reviewAssignment->getReviewerId();
				if ($userId) $userIds[] = array('id' => $userId, 'role' => 'reviewer');
				unset($reviewAssignment);
			}
		}

		$editAssignmentDao = DAORegistry::getDAO('EditAssignmentDAO');

		if($trackDirectors) {
			$editAssignments =& $editAssignmentDao->getTrackDirectorAssignmentsByPaperId($paperId);
			while ($editAssignment =& $editAssignments->next()) {
				$userId = $editAssignment->getDirectorId();
				if ($userId) $userIds[] = array('id' => $userId, 'role' => 'trackDirector');
				unset($editAssignment);
			}
		}

		if($directors) {
			$editAssignments =& $editAssignmentDao->getDirectorAssignmentsByPaperId($paperId);
			while ($editAssignment =& $editAssignments->next()) {
				$userId = $editAssignment->getDirectorId();
				if ($userId) $userIds[] = array('id' =>  $userId, 'role' => 'director');
			}
		}

		return $userIds;
	}
}

?>
