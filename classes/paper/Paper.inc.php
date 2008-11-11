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

// Submission type constants
define('SUBMISSION_TYPE_SINGLE', 0);
define('SUBMISSION_TYPE_PANEL', 1);

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

import('document.Document');

class Paper extends Document {

	/** @var array Authors of this paper */
	var $authors;

	/** @var array IDs of Authors removed from this paper */
	var $removedAuthors;

	/**
	 * Constructor.
	 */
	function Paper() {
		parent::Document();
		$this->authors = array();
		$this->removedAuthors = array();
	}

	/**
	 * Add an author.
	 * @param $author Author
	 */
	function addAuthor($author) {
		if ($author->getPaperId() == null) {
			$author->setPaperId($this->getPaperId());
		}
		if ($author->getSequence() == null) {
			$author->setSequence(count($this->authors) + 1);
		}
		array_push($this->authors, $author);
	}

	/**
	 * Remove an author.
	 * @param $authorId ID of the author to remove
	 * @return boolean author was removed
	 */
	function removeAuthor($authorId) {
		$found = false;

		if ($authorId != 0) {
			// FIXME maintain a hash of ID to author for quicker get/remove
			$authors = array();
			for ($i=0, $count=count($this->authors); $i < $count; $i++) {
				if ($this->authors[$i]->getAuthorId() == $authorId) {
					array_push($this->removedAuthors, $authorId);
					$found = true;
				} else {
					array_push($authors, $this->authors[$i]);
				}
			}
			$this->authors = $authors;
		}
		return $found;
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

	/**
	 * Return string of author names, separated by the specified token
	 * @param $lastOnly boolean return list of lastnames only (default false)
	 * @param $separator string separator for names (default comma+space)
	 * @return string
	 */
	function getAuthorString($lastOnly = false, $separator = ', ') {
		$str = '';
		foreach ($this->authors as $a) {
			if (!empty($str)) {
				$str .= $separator;
			}
			$str .= $lastOnly ? $a->getLastName() : $a->getFullName();
		}
		return $str;
	}

	/**
	 * Return a list of author email addresses.
	 * @return array
	 */
	function getAuthorEmails() {
		import('mail.Mail');
		$returner = array();
		foreach ($this->authors as $a) {
			$returner[] = Mail::encodeDisplayName($a->getFullName()) . ' <' . $a->getEmail() . '>';
		}
		return $returner;
	}

	/**
	 * Return first author
	 * @param $lastOnly boolean return lastname only (default false)
	 * @return string
	 */
	function getFirstAuthor($lastOnly = false) {
		$author = $this->authors[0];
		return $lastOnly ? $author->getLastName() : $author->getFullName();
	}


	//
	// Get/set methods
	//

	/**
	 * Get paper type (SUBMISSION_TYPE_...).
	 * @return int
	 */
	function getTypeConst() {
		return $this->getData('paperType');
	}

	/**
	 * Set paper type (SUBMISSION_TYPE_...).
	 * @param $type int
	 */
	function setTypeConst($typeConst) {
		return $this->setData('paperType', $typeConst);
	}

	/**
	 * Get all authors of this paper.
	 * @return array Authors
	 */
	function &getAuthors() {
		return $this->authors;
	}

	/**
	 * Get a specific author of this paper.
	 * @param $authorId int
	 * @return array Authors
	 */
	function &getAuthor($authorId) {
		$author = null;

		if ($authorId != 0) {
			for ($i=0, $count=count($this->authors); $i < $count && $author == null; $i++) {
				if ($this->authors[$i]->getAuthorId() == $authorId) {
					$author = &$this->authors[$i];
				}
			}
		}
		return $author;
	}

	/**
	 * Get the IDs of all authors removed from this paper.
	 * @return array int
	 */
	function &getRemovedAuthors() {
		return $this->removedAuthors;
	}

	/**
	 * Set authors of this paper.
	 * @param $authors array Authors
	 */
	function setAuthors($authors) {
		return $this->authors = $authors;
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
}

?>
