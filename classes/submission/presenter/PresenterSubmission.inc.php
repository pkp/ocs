<?php

/**
 * PresenterSubmission.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * PresenterSubmission class.
 *
 * $Id$
 */

import('paper.Paper');

class PresenterSubmission extends Paper {

	/** @var array ReviewAssignments of this paper */
	var $reviewAssignments;

	/** @var array the director decisions of this paper */
	var $directorDecisions;
	
	/** @var array the revisions of the presenter file */
	var $presenterFileRevisions;
	
	/** @var array the revisions of the director file */
	var $directorFileRevisions;

	/**
	 * Constructor.
	 */
	function PresenterSubmission() {
		parent::Paper();
		$this->reviewAssignments = array();
	}
	
	/**
	 * Get/Set Methods.
	 */
	 
	/**
	 * Get edit assignments for this paper.
	 * @return array
	 */
	function &getEditAssignments() {
		$editAssignments = &$this->getData('editAssignments');
		return $editAssignments;
	}
	
	/**
	 * Set edit assignments for this paper.
	 * @param $editAssignments array
	 */
	function setEditAssignments($editAssignments) {
		return $this->setData('editAssignments', $editAssignments);
	}
	
	/**
	 * Add a review assignment for this paper.
	 * @param $reviewAssignment ReviewAssignment
	 */
	function addReviewAssignment($reviewAssignment) {
		if ($reviewAssignment->getPaperId() == null) {
			$reviewAssignment->setPaperId($this->getPaperId());
		}
		
		$type = $reviewAssignment->getType();
		$round = $reviewAssignment->getRound();
		
		if(!isset($this->reviewAssignments[$type]))
			$this->reviewAssignments[$type] = array();
		
		if(!isset($this->reviewAssignments[$type][$round]))
			$this->reviewAssignments[$type][$round] = array();
		
		$this->reviewAssignments[$type][$round][] = $reviewAssignment;
		
		return $this->reviewAssignments[$type][$round];
	}
	
	/**
	 * Remove a review assignment.
	 * @param $reviewId ID of the review assignment to remove
	 * @return boolean review assignment was removed
	 */
	function removeReviewAssignment($reviewId) {
		$reviewAssignments = array();
		$found = false;
		for ($i=0, $count=count($this->reviewAssignments); $i < $count; $i++) {
			if ($this->reviewAssignments[$i]->getReviewId() == $reviewId) {
				$found = true;
			} else {
				array_push($reviewAssignments, $this->reviewAssignments[$i]);
			}
		}
		$this->reviewAssignments = $reviewAssignments;

		return $found;
	}
	
	//
	// Review Assignments
	//
	
	/**
	 * Get review assignments for this paper.
	 * @return array ReviewAssignments
	 */
	function getReviewAssignments($type, $round) {
		if($type == null)
			return $this->reviewAssignments;
		
		if(!isset($this->reviewAssignments[$type]))
			return null;
		
		if($round == null)
			return $this->reviewAssignments[$type];
		
		if(!isset($this->reviewAssignments[$type][$round]))
			return null;
		
		return $this->reviewAssignments[$type][$round];
	}
	
	/**
	 * Set review assignments for this paper.
	 * @param $reviewAssignments array ReviewAssignments
	 */
	function setReviewAssignments($reviewAssignments, $type, $round) {
		return $this->reviewAssignments[$type][$round] = $reviewAssignments;
	}
	
	//
	// Director Decisions
	//

	/**
	 * Get director decisions.
	 * @return array
	 */
	function getDecisions($type = null, $round = null) {
		if ($type == null)
			return $this->directorDecisions;

		if(!isset($this->directorDecisions[$type]))
			return null;
		
		if ($round == null)
			return $this->directorDecisions[$type];

		if(!isset($this->directorDecisions[$type][$round]))
			return null;

		return $this->directorDecisions[$type][$round];
	}
	
	/**
	 * Set director decisions.
	 * @param $directorDecisions array
	 * @param $type int
	 * @param $round int
	 */
	function setDecisions($directorDecisions, $type, $round) {
		$this->stampStatusModified();
		return $this->directorDecisions[$type][$round] = $directorDecisions;
	}

	/**
	 * Get the submission status. Returns one of the defined constants
	 * (SUBMISSION_STATUS_INCOMPLETE, SUBMISSION_STATUS_ARCHIVED, SUBMISSION_STATUS_PUBLISHED,
	 * SUBMISSION_STATUS_DECLINED, SUBMISSION_STATUS_QUEUED_UNASSIGNED,
	 * SUBMISSION_STATUS_QUEUED_REVIEW, or SUBMISSION_STATUS_QUEUED_EDITING). Note that this function never returns
	 * a value of SUBMISSION_STATUS_QUEUED -- the three SUBMISSION_STATUS_QUEUED_... constants indicate a queued
	 * submission. NOTE that this code is similar to getSubmissionStatus in
	 * the TrackDirectorSubmission class and changes here should be propagated.
	 */
	function getSubmissionStatus() {
		// Optimization: Use the Request scheduled conference object
		// if available and if it's the same as the paper's sched conf
		$schedConf = &Request::getSchedConf();
		if (!$schedConf || $this->getSchedConfId() != $schedConf->getSchedConfId()) {
			unset($schedConf);
			$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
			$schedConf =& $schedConfDao->getSchedConf($this->getSchedConfId());
		}
		
		$status = $this->getStatus();
		if ($status == SUBMISSION_STATUS_ARCHIVED ||
				$status == SUBMISSION_STATUS_PUBLISHED ||
		    $status == SUBMISSION_STATUS_DECLINED) return $status;

		// The submission is SUBMISSION_STATUS_QUEUED or the presenter's submission was SUBMISSION_STATUS_INCOMPLETE.
		if ($this->getSubmissionProgress()) return (SUBMISSION_STATUS_INCOMPLETE);

		// The submission is SUBMISSION_STATUS_QUEUED. Find out where it's queued.
		$editAssignments = $this->getEditAssignments();
		if (empty($editAssignments)) 
			return (SUBMISSION_STATUS_QUEUED_UNASSIGNED);

		$decisions = $this->getDecisions();
		$decision = array_pop($decisions);
		if (!empty($decision)) {
			$latestDecision = array_pop($decision);
			if ($latestDecision['decision'] == SUBMISSION_DIRECTOR_DECISION_ACCEPT || $latestDecision['decision'] == SUBMISSION_DIRECTOR_DECISION_DECLINE) {
				return SUBMISSION_STATUS_QUEUED_EDITING;
			}
		}
		return SUBMISSION_STATUS_QUEUED_REVIEW;
	}

	//
	// Files
	//
	
	/**
	 * Get submission file for this paper.
	 * @return PaperFile
	 */
	function &getSubmissionFile() {
		$returner =& $this->getData('submissionFile');
		return $returner;
	}
	
	/**
	 * Set submission file for this paper.
	 * @param $submissionFile PaperFile
	 */
	function setSubmissionFile($submissionFile) {
		return $this->setData('submissionFile', $submissionFile);
	}
	
	/**
	 * Get revised file for this paper.
	 * @return PaperFile
	 */
	function &getRevisedFile() {
		$returner =& $this->getData('revisedFile');
		return $returner;
	}
	
	/**
	 * Set revised file for this paper.
	 * @param $submissionFile PaperFile
	 */
	function setRevisedFile($revisedFile) {
		return $this->setData('revisedFile', $revisedFile);
	}
	
	/**
	 * Get supplementary files for this paper.
	 * @return array SuppFiles
	 */
	function &getSuppFiles() {
		$returner =& $this->getData('suppFiles');
		return $returner;
	}
	
	/**
	 * Set supplementary file for this paper.
	 * @param $suppFiles array SuppFiles
	 */
	function setSuppFiles($suppFiles) {
		return $this->setData('suppFiles', $suppFiles);
	}
	
	/**
	 * Get all presenter file revisions.
	 * @return array PaperFiles
	 */
	function getPresenterFileRevisions($round = null) {
		if ($round == null) {
			return $this->presenterFileRevisions;
		} else {
			return $this->presenterFileRevisions[$round];
		}
	}
	
	/**
	 * Set all presenter file revisions.
	 * @param $presenterFileRevisions array PaperFiles
	 */
	function setPresenterFileRevisions($presenterFileRevisions, $round) {
		return $this->presenterFileRevisions[$round] = $presenterFileRevisions;
	}
	
	/**
	 * Get all director file revisions.
	 * @return array PaperFiles
	 */
	function getDirectorFileRevisions($round = null) {
		if ($round == null) {
			return $this->directorFileRevisions;
		} else {
			return $this->directorFileRevisions[$round];
		}
	}
	
	/**
	 * Set all director file revisions.
	 * @param $directorFileRevisions array PaperFiles
	 */
	function setDirectorFileRevisions($directorFileRevisions, $round) {
		return $this->directorFileRevisions[$round] = $directorFileRevisions;
	}
	
	/**
	 * Get the galleys for an article.
	 * @return array ArticleGalley
	 */
	function &getGalleys() {
		$galleys = &$this->getData('galleys');
		return $galleys;
	}
	
	/**
	 * Set the galleys for an article.
	 * @param $galleys array ArticleGalley
	 */
	function setGalleys(&$galleys) {
		return $this->setData('galleys', $galleys);
	}
	
	//
	// Comments
	//
	
	/**
	 * Get most recent director decision comment.
	 * @return PaperComment
	 */
	function getMostRecentDirectorDecisionComment() {
		return $this->getData('mostRecentDirectorDecisionComment');
	}
	
	/**
	 * Set most recent director decision comment.
	 * @param $mostRecentDirectorDecisionComment PaperComment
	 */
	function setMostRecentDirectorDecisionComment($mostRecentDirectorDecisionComment) {
		return $this->setData('mostRecentDirectorDecisionComment', $mostRecentDirectorDecisionComment);
	}
	
	/**
	 * Get most recent layout comment.
	 * @return PaperComment
	 */
	function getMostRecentLayoutComment() {
		return $this->getData('mostRecentLayoutComment');
	}
	
	/**
	 * Set most recent layout comment.
	 * @param $mostRecentLayoutComment PaperComment
	 */
	function setMostRecentLayoutComment($mostRecentLayoutComment) {
		return $this->setData('mostRecentLayoutComment', $mostRecentLayoutComment);
	}
		
	/**
	 * Get layout assignment.
	 * @return layoutAssignment object
	 */
	function &getLayoutAssignment() {
		$layoutAssignment = &$this->getData('layoutAssignment');
		return $layoutAssignment;
	}

	/**
	 * Set layout assignment.
	 * @param $layoutAssignment
	 */
	function setLayoutAssignment($layoutAssignment) {
		return $this->setData('layoutAssignment', $layoutAssignment);
	}
}

?>
