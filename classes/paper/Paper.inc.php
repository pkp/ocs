<?php

/**
 * @defgroup paper
 */

/**
 * @file Paper.inc.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Paper
 * @ingroup paper
 * @see PaperDAO
 *
 * @brief Paper class.
 *
 */

// $Id$


// Submission status constants
define('SUBMISSION_STATUS_ARCHIVED', 0);
define('SUBMISSION_STATUS_QUEUED', 1);
define('SUBMISSION_STATUS_PUBLISHED', 3);
define('SUBMISSION_STATUS_DECLINED', 4);

// AuthorSubmission::getSubmissionStatus will return one of these in place of QUEUED:
define ('SUBMISSION_STATUS_QUEUED_UNASSIGNED', 6);
define ('SUBMISSION_STATUS_QUEUED_REVIEW', 7);
define ('SUBMISSION_STATUS_QUEUED_EDITING', 8);
define ('SUBMISSION_STATUS_INCOMPLETE', 9);

define ('REVIEW_STAGE_ABSTRACT', 1);
define ('REVIEW_STAGE_PRESENTATION', 2);

/* These constants are used as search fields for the various submission lists */
define('SUBMISSION_FIELD_AUTHOR', 1);
define('SUBMISSION_FIELD_DIRECTOR', 2);
define('SUBMISSION_FIELD_TITLE', 3);
define('SUBMISSION_FIELD_REVIEWER', 4);

define('SUBMISSION_FIELD_DATE_SUBMITTED', 5);

// Paper RT comments
define ('COMMENTS_TRACK_DEFAULT', 0);
define ('COMMENTS_DISABLE', 1);
define ('COMMENTS_ENABLE', 2);

import('submission.Submission');

class Paper extends Submission {
	/**
	 * Constructor.
	 */
	function Paper() {
		parent::Submission();
	}

	/**
	 * Add an author.
	 * @param $author Author
	 */
	function addAuthor($author) {
		if ($author->getPaperId() == null) {
			$author->setPaperId($this->getPaperId());
		}
		parent::addAuthor($author);
	}

	/**
	 * Get "localized" paper title (if applicable).
	 * @return string
	 */
	function getPaperTitle() {
		return $this->getLocalizedData('title');
	}

	/**
	 * Get "localized" paper abstract (if applicable).
	 * @return string
	 */
	function getPaperAbstract() {
		return $this->getLocalizedData('abstract');
	}


	//
	// Get/set methods
	//

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
	 * Return the localized discipline
	 * @param $locale string
	 * @return string
	 */
	function getPaperDiscipline() {
		return $this->getLocalizedData('discipline');
	}

	/**
	 * Return the localized subject classification
	 * @param $locale string
	 * @return string
	 */
	function getPaperSubjectClass() {
		return $this->getLocalizedData('subjectClass');
	}

	/**
	 * Return the localized subject
	 * @return string
	 */
	function getPaperSubject() {
		return $this->getLocalizedData('subject');
	}

	/**
	 * Return the localized geo coverage
	 * @return string
	 */
	function getPaperCoverageGeo() {
		return $this->getLocalizedData('coverageGeo');
	}

	/**
	 * Return the localized chron coverage
	 * @return string
	 */
	function getPaperCoverageChron() {
		return $this->getLocalizedData('coverageChron');
	}

	/**
	 * Return the localized sample coverage
	 * @return string
	 */
	function getPaperCoverageSample() {
		return $this->getLocalizedData('coverageSample');
	}

	/**
	 * Return the localized type
	 * @return string
	 */
	function getPaperType() {
		return $this->getLocalizedData('type');
	}

	/**
	 * Return the localized sponsor
	 * @return string
	 */
	function getPaperSponsor() {
		return $this->getLocalizedData('sponsor');
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
	 * Get current stage.
	 * @return int
	 */
	function getCurrentStage() {
		return $this->getData('currentStage');
	}

	/**
	 * Set current stage.
	 * @param $currentStage int
	 */
	function setCurrentStage($currentStage) {
		return $this->setData('currentStage', $currentStage);
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
				$trackDao =& DAORegistry::getDAO('TrackDAO');
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
		$paperId = $this->getPaperId();
		
		$userIds = array();

		if($authors) {
			$authorDao = &DAORegistry::getDAO('AuthorDAO');
			$authors = $authorDao->getAuthorsByPaper($paperId);
			foreach ($authors as $author) {
				$userIds[] = array('id' => $author->getAuthorId(), 'role' => 'author');
			}
		}
			
		if($reviewers) {
			$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
			$reviewAssignments =& $reviewAssignmentDao->getReviewAssignmentsByPaperId($paperId);
			foreach ($reviewAssignments as $reviewAssignment) {
				$userIds[] = array('id' => $reviewAssignment->getReviewerId(), 'role' => 'reviewer');
				unset($reviewAssignment);
			}
		}

		$editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');

		if($trackDirectors) {
			$editAssignments =& $editAssignmentDao->getTrackDirectorAssignmentsByPaperId($paperId);
			while ($editAssignment =& $editAssignments->next()) {
				$userIds[] = array('id' => $editAssignment->getDirectorId(), 'role' => 'trackDirector');
				unset($editAssignment);
			}
		}

		if($directors) {
			$editAssignments =& $editAssignmentDao->getDirectorAssignmentsByPaperId($paperId);
			while ($editAssignment =& $editAssignments->next()) {
				$userIds[] = array('id' =>  $editAssignment->getDirectorId(), 'role' => 'director');
			}
		}	

		return $userIds;
	}

}

?>
