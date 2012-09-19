<?php

/**
 * @file TrackDirectorSubmission.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TrackDirectorSubmission
 * @ingroup submission
 * @see TrackDirectorSubmission
 *
 * @brief TrackDirectorSubmission class.
 */


import('classes.paper.Paper');

class TrackDirectorSubmission extends Paper {

	/** @var array ReviewAssignments of this paper */
	var $reviewAssignments;

	/** @var array IDs of ReviewAssignments removed from this paper */
	var $removedReviewAssignments;

	/** @var array the director decisions of this paper */
	var $directorDecisions;

	/** @var array the revisions of the director file */
	var $directorFileRevisions;

	/** @var array the revisions of the author file */
	var $authorFileRevisions;

	/**
	 * Constructor.
	 */
	function TrackDirectorSubmission() {
		parent::Paper();
		$this->reviewAssignments = array();
		$this->removedReviewAssignments = array();
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
	 * Add an editorial decision for this paper.
	 * @param $directorDecision array
	 * @param $round int
	 */
	function addDecision($directorDecision, $round) {
		if(!is_array($this->directorDecisions))
			$this->directorDecisions = array();

		if(!isset($this->directorDecisions[$round]))
			$this->directorDecisions[$round] = array();

		array_push($this->directorDecisions[$round], $directorDecision);
	}		

	/**
	 * Remove a review assignment.
	 * @param $reviewId ID of the review assignment to remove
	 * @return boolean review assignment was removed
	 */
	function removeReviewAssignment($reviewId) {
		if ($reviewId == 0) return false;

		foreach($this->getReviewAssignments() as $roundKey => $reviews) {
			foreach ($reviews as $reviewKey => $review) {
				if($review->getId() == $reviewId) {
					$this->removedReviewAssignments[] =& $this->reviewAssignments[$roundKey][$reviewKey];
					unset($this->reviewAssignments[$roundKey][$reviewKey]);
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Updates an existing review assignment.
	 * @param $reviewAssignment ReviewAssignment
	 */
	function updateReviewAssignment($reviewAssignment) {
		$reviewAssignments = array();
		$roundReviewAssignments = $this->reviewAssignments[$reviewAssignment->getRound()];
		for ($i=0, $count=count($roundReviewAssignments); $i < $count; $i++) {
			if ($roundReviewAssignments[$i]->getId() == $reviewAssignment->getId()) {
				array_push($reviewAssignments, $reviewAssignment);
			} else {
				array_push($reviewAssignments, $roundReviewAssignments[$i]);
			}
		}
		$this->reviewAssignments[$reviewAssignment->getRound()] = $reviewAssignments;
	}

	/**
	 * Get the submission status. Returns one of the defined constants
	 * (STATUS_INCOMPLETE, STATUS_ARCHIVED, STATUS_DECLINED,
	 * STATUS_QUEUED_UNASSIGNED, STATUS_QUEUED_REVIEW, or
	 * STATUS_QUEUED_EDITING). Note that this function never returns a
	 * value of STATUS_QUEUED -- the three STATUS_QUEUED_... constants
	 * indicate a queued submission.
	 * NOTE that this code is similar to getSubmissionStatus in
	 * the AuthorSubmission class and changes should be made there as well.
	 */
	function getSubmissionStatus() {
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

		$decisions = $this->getDecisions();
		$decision = array_pop($decisions);
		if (!empty($decision)) {
			$latestDecision = array_pop($decision);
			if ($latestDecision['decision'] == SUBMISSION_DIRECTOR_DECISION_ACCEPT) {
				return STATUS_QUEUED_EDITING;
			}
		}
		return STATUS_QUEUED_REVIEW;
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

	//
	// Review Assignments
	//

	/**
	 * Get review assignments for this paper.
	 * @return array ReviewAssignments
	 */
	function getReviewAssignments($round = null) {
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

	/**
	 * Get the IDs of all review assignments removed.
	 * @return array int
	 */
	function &getRemovedReviewAssignments() {
		return $this->removedReviewAssignments;
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
	 * Get review file.
	 * @return PaperFile
	 */
	function &getReviewFile() {
		$returner =& $this->getData('reviewFile');
		return $returner;
	}

	/**
	 * Set review file.
	 * @param $reviewFile PaperFile
	 */
	function setReviewFile($reviewFile) {
		return $this->setData('reviewFile', $reviewFile);
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
	 * Get post-review file.
	 * @return PaperFile
	 */
	function &getDirectorFile() {
		$returner =& $this->getData('directorFile');
		return $returner;
	}

	/**
	 * Set post-review file.
	 * @param $directorFile PaperFile
	 */
	function setDirectorFile($directorFile) {
		return $this->setData('directorFile', $directorFile);
	}

	//
	// Review Rounds
	//

	/**
	 * Get review file revision.
	 * @return int
	 */
	function getReviewRevision() {
		return $this->getData('reviewRevision');
	}

	/**
	 * Set review file revision.
	 * @param $reviewRevision int
	 */
	function setReviewRevision($reviewRevision) {
		return $this->setData('reviewRevision', $reviewRevision);
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

	/**
	 * Return array mapping director decision constants to their locale strings.
	 * (Includes default mapping '' => "Choose One".)
	 * @return array decision => localeString
	 */
	function &getDirectorDecisionOptions($schedConf = null, $round = null) {
		$directorDecisionOptions = array('' => 'common.chooseOne');
		if (!$schedConf || ($round == REVIEW_ROUND_ABSTRACT && $this->getReviewMode() == REVIEW_MODE_BOTH_SEQUENTIAL)) $directorDecisionOptions[SUBMISSION_DIRECTOR_DECISION_INVITE] = 'director.paper.decision.invitePresentation';
		if (!$schedConf || ($round != REVIEW_ROUND_ABSTRACT || $this->getReviewMode() != REVIEW_MODE_BOTH_SEQUENTIAL)) $directorDecisionOptions[SUBMISSION_DIRECTOR_DECISION_ACCEPT] = 'director.paper.decision.accept';

		$directorDecisionOptions[SUBMISSION_DIRECTOR_DECISION_PENDING_REVISIONS] = 'director.paper.decision.pendingRevisions';
		$directorDecisionOptions[SUBMISSION_DIRECTOR_DECISION_DECLINE] = 'director.paper.decision.decline';
		return $directorDecisionOptions;
	}

	function isOriginalSubmissionComplete() {
		$reviewMode = $this->getReviewMode();
		if ($reviewMode == REVIEW_MODE_BOTH_SEQUENTIAL) {
			return ($this->getSubmissionProgress() != 1);
		}
		return ($this->getSubmissionProgress() == 0);
	}
}

?>
