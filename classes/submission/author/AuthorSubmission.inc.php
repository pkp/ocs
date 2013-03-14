<?php

/**
 * @file AuthorSubmission.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthorSubmission
 * @ingroup submission
 * @see AuthorSubmissionDAO
 *
 * @brief AuthorSubmission class.
 */


import('classes.paper.Paper');

class AuthorSubmission extends Paper {

	/** @var array ReviewAssignments of this paper */
	var $reviewAssignments;

	/** @var array the director decisions of this paper */
	var $directorDecisions;

	/** @var array the revisions of the author file */
	var $authorFileRevisions;

	/** @var array the revisions of the director file */
	var $directorFileRevisions;

	/**
	 * Constructor.
	 */
	function AuthorSubmission() {
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
		$editAssignments =& $this->getData('editAssignments');
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
		if ($reviewAssignment->getSubmissionId() == null) {
			$reviewAssignment->setSubmissionId($this->getPaperId());
		}

		$round = $reviewAssignment->getRound();

		if(!isset($this->reviewAssignments[$round]))
			$this->reviewAssignments[$round] = array();

		$this->reviewAssignments[$round][] = $reviewAssignment;

		return $this->reviewAssignments[$round];
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
			if ($this->reviewAssignments[$i]->getId() == $reviewId) {
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
	function getReviewAssignments($round) {
		if($round == null)
			return $this->reviewAssignments;

		if(!isset($this->reviewAssignments[$round]))
			return null;

		return $this->reviewAssignments[$round];
	}

	/**
	 * Set review assignments for this paper.
	 * @param $reviewAssignments array ReviewAssignments
	 */
	function setReviewAssignments($reviewAssignments, $round) {
		return $this->reviewAssignments[$round] = $reviewAssignments;
	}

	//
	// Director Decisions
	//

	/**
	 * Get director decisions.
	 * @return array
	 */
	function getDecisions($round = null) {
		if ($round == null)
			return $this->directorDecisions;

		if(!isset($this->directorDecisions[$round]))
			return null;

		return $this->directorDecisions[$round];
	}

	/**
	 * Set director decisions.
	 * @param $directorDecisions array
	 * @param $round int
	 */
	function setDecisions($directorDecisions, $round) {
		return $this->directorDecisions[$round] = $directorDecisions;
	}

	/**
	 * Get the submission status. Returns one of the defined constants
	 * (STATUS_INCOMPLETE, STATUS_ARCHIVED, STATUS_PUBLISHED,
	 * STATUS_DECLINED, STATUS_QUEUED_UNASSIGNED, * STATUS_QUEUED_REVIEW,
	 * or STATUS_QUEUED_EDITING). Note that this function never returns
	 * a value of STATUS_QUEUED -- the three STATUS_QUEUED_... constants
	 * indicate a queued submission. NOTE that this code is similar to
	 * getSubmissionStatus in the TrackDirectorSubmission class and changes
	 * here should be propagated.
	 */
	function getSubmissionStatus() {
		// Optimization: Use the Request scheduled conference object
		// if available and if it's the same as the paper's sched conf
		$schedConf =& Request::getSchedConf();
		if (!$schedConf || $this->getSchedConfId() != $schedConf->getId()) {
			unset($schedConf);
			$schedConfDao = DAORegistry::getDAO('SchedConfDAO');
			$schedConf = $schedConfDao->getById($this->getSchedConfId());
		}

		$status = $this->getStatus();
		if ($status == STATUS_ARCHIVED ||
		    $status == STATUS_PUBLISHED ||
		    $status == STATUS_DECLINED) return $status;

		// The submission is STATUS_QUEUED or the author's submission was STATUS_INCOMPLETE.
		if ($this->getSubmissionProgress()) return (STATUS_INCOMPLETE);

		// The submission is STATUS_QUEUED. Find out where it's queued.
		$editAssignments = $this->getEditAssignments();
		if (empty($editAssignments)) 
			return (STATUS_QUEUED_UNASSIGNED);

		$latestDecision = $this->getMostRecentDecision();
		if ($latestDecision) {
			if ($latestDecision == SUBMISSION_DIRECTOR_DECISION_ACCEPT || $latestDecision == SUBMISSION_DIRECTOR_DECISION_DECLINE) {
				return STATUS_QUEUED_EDITING;
			}
		}
		return STATUS_QUEUED_REVIEW;
	}

	/**
	 * Get the most recent decision.
	 * @return int SUBMISSION_EDITOR_DECISION_...
	 */
	function getMostRecentDecision() {
		$decisions = $this->getDecisions();
		$decision = array_pop($decisions);
		if (!empty($decision)) {
			$latestDecision = array_pop($decision);
			if (isset($latestDecision['decision'])) return $latestDecision['decision'];
		}
		return null;
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
	 * Get layout file.
	 * @return PaperFile
	 */
	function &getLayoutFile() {
		$returner =& $this->getData('layoutFile');
		return $returner;
	}

	/**
	 * Set layout file.
	 * @param $layoutFile PaperFile
	 */
	function setLayoutFile($layoutFile) {
		return $this->setData('layoutFile', $layoutFile);
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
	 * Get all author file revisions.
	 * @return array PaperFiles
	 */
	function getAuthorFileRevisions($round = null) {
		if ($round == null) {
			return $this->authorFileRevisions;
		} else {
			return $this->authorFileRevisions[$round];
		}
	}

	/**
	 * Set all author file revisions.
	 * @param $authorFileRevisions array PaperFiles
	 */
	function setAuthorFileRevisions($authorFileRevisions, $round) {
		return $this->authorFileRevisions[$round] = $authorFileRevisions;
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
	 * Get the galleys for a paper.
	 * @return array PaperGalley
	 */
	function &getGalleys() {
		$galleys =& $this->getData('galleys');
		return $galleys;
	}

	/**
	 * Set the galleys for a paper.
	 * @param $galleys array PaperGalley
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
}

?>
