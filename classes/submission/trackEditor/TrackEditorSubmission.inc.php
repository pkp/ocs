<?php

/**
 * TrackEditorSubmission.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * TrackEditorSubmission class.
 *
 * $Id$
 */

import('paper.Paper');

class TrackEditorSubmission extends Paper {

	/** @var array ReviewAssignments of this paper */
	var $reviewAssignments;

	/** @var array IDs of ReviewAssignments removed from this paper */
	var $removedReviewAssignments;

	/** @var array the editor decisions of this paper */
	var $editorDecisions;

	/** @var array the revisions of the editor file */
	var $editorFileRevisions;
	
	/** @var array the revisions of the author file */
	var $authorFileRevisions;

	/**
	 * Constructor.
	 */
	function TrackEditorSubmission() {
		parent::Paper();
		$this->reviewAssignments = array();
		$this->removedReviewAssignments = array();
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
	 * Add an editorial decision for this paper.
	 * @param $editorDecision array
	 * @param $type int
	 * @param $round int
	 */
	function addDecision($editorDecision, $type, $round) {
		if(!is_array($this->editorDecisions))
			$this->editorDecisions = array();
			
		if(!isset($this->editorDecisions[$type]))
			$this->editorDecisions[$type] = array();

		if(!isset($this->editorDecisions[$type][$round]))
			$this->editorDecisions[$type][$round] = array();

		array_push($this->editorDecisions[$type][$round], $editorDecision);
	}		
	
	/**
	 * Remove a review assignment.
	 * @param $reviewId ID of the review assignment to remove
	 * @return boolean review assignment was removed
	 */
	function removeReviewAssignment($reviewId) {
		$found = false;

		if ($reviewId != 0) {
			// FIXME this is kind of a mess
			
			foreach($this->reviewAssignments as $typekey => $type) {
				foreach($type as $roundkey => $round) {
					foreach($round as $reviewkey => $review) {
						if($review->getReviewId() == $reviewId) {

							if(!isset($this->removedReviewAssignments[$typekey]))
								$this->removedReviewAssignments[$typekey] = array();
							if(!isset($this->removedReviewAssignments[$typekey][$roundkey]))
								$this->removedReviewAssignments[$typekey][$roundkey] = array();

							$this->removedReviewAssignments[$typekey][$roundkey][$reviewkey] = $this->removedAssignments[$typekey][$roundkey][$reviewkey];
							$this->reviewAssignments[$typekey][$roundkey][$reviewkey] = null;
							$found = true;
							break;
						}
					}
				}
			}
		}
		return $found;
	}
	
	/**
	 * Updates an existing review assignment.
	 * @param $reviewAssignment ReviewAssignment
	 */
	function updateReviewAssignment($reviewAssignment) {
		$reviewAssignments = array();
		$roundReviewAssignments = $this->reviewAssignments[$reviewAssignment->getType()][$reviewAssignment->getRound()];
		for ($i=0, $count=count($roundReviewAssignments); $i < $count; $i++) {
			if ($roundReviewAssignments[$i]->getReviewId() == $reviewAssignment->getReviewId()) {
				array_push($reviewAssignments, $reviewAssignment);
			} else {
				array_push($reviewAssignments, $roundReviewAssignments[$i]);
			}
		}
		$this->reviewAssignments[$reviewAssignment->getType()][$reviewAssignment->getRound()] = $reviewAssignments;
	}

	/**
	 * Get the submission status. Returns one of the defined constants
	 * (SUBMISSION_STATUS_INCOMPLETE, SUBMISSION_STATUS_ARCHIVED,
	 * SUBMISSION_STATUS_DECLINED, SUBMISSION_STATUS_QUEUED_UNASSIGNED,
	 * SUBMISSION_STATUS_QUEUED_REVIEW, or SUBMISSION_STATUS_QUEUED_EDITING). Note that this function never returns
	 * a value of SUBMISSION_STATUS_QUEUED -- the three SUBMISSION_STATUS_QUEUED_... constants
	 * indicate a queued submission.
	 * NOTE that this code is similar to getSubmissionStatus in
	 * the AuthorSubmission class and changes should be made there as well.
	 */
	function getSubmissionStatus() {
		$status = $this->getStatus();
		if ($status == SUBMISSION_STATUS_ARCHIVED ||
				$status == SUBMISSION_STATUS_PUBLISHED ||
		    $status == SUBMISSION_STATUS_DECLINED) return $status;

		// The submission is SUBMISSION_STATUS_QUEUED or the author's submission was SUBMISSION_STATUS_INCOMPLETE.
		if ($this->getSubmissionProgress()) return (SUBMISSION_STATUS_INCOMPLETE);

		// The submission is SUBMISSION_STATUS_QUEUED. Find out where it's queued.
		$editAssignments = $this->getEditAssignments();
		if (empty($editAssignments))
			return (SUBMISSION_STATUS_QUEUED_UNASSIGNED);

		$decisions = $this->getDecisions();
		$decision = array_pop($decisions);
		if (!empty($decision)) {
			$latestDecision = array_pop($decision);
			if ($latestDecision['decision'] == SUBMISSION_EDITOR_DECISION_ACCEPT || $latestDecision['decision'] == SUBMISSION_EDITOR_DECISION_DECLINE) {
				return SUBMISSION_STATUS_QUEUED_EDITING;
			}
		}
		return SUBMISSION_STATUS_QUEUED_REVIEW;
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
	
	/**
	 * Get the IDs of all review assignments removed.
	 * @return array int
	 */
	function &getRemovedReviewAssignments() {
		return $this->removedReviewAssignments;
	}
	
	//
	// Editor Decisions
	//

	/**
	 * Get editor decisions.
	 * @return array
	 */
	function getDecisions($type = null, $round = null) {
		if ($type == null)
			return $this->editorDecisions;

		if(!isset($this->editorDecisions[$type]))
			return null;
		
		if ($round == null)
			return $this->editorDecisions[$type];

		if(!isset($this->editorDecisions[$type][$round]))
			return null;

		return $this->editorDecisions[$type][$round];
	}
	
	/**
	 * Set editor decisions.
	 * @param $editorDecisions array
	 * @param $type int
	 * @param $round int
	 */
	function setDecisions($editorDecisions, $type, $round) {
		$this->stampStatusModified();
		return $this->editorDecisions[$type][$round] = $editorDecisions;
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
	 * Get all editor file revisions.
	 * @return array PaperFiles
	 */
	function getEditorFileRevisions($round = null) {
		if ($round == null) {
			return $this->editorFileRevisions;
		} else {
			return $this->editorFileRevisions[$round];
		}
	}
	
	/**
	 * Set all editor file revisions.
	 * @param $editorFileRevisions array PaperFiles
	 */
	function setEditorFileRevisions($editorFileRevisions, $round) {
		return $this->editorFileRevisions[$round] = $editorFileRevisions;
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
	function &getEditorFile() {
		$returner =& $this->getData('editorFile');
		return $returner;
	}
	
	/**
	 * Set post-review file.
	 * @param $editorFile PaperFile
	 */
	function setEditorFile($editorFile) {
		return $this->setData('editorFile', $editorFile);
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
	 * Get most recent editor decision comment.
	 * @return PaperComment
	 */
	function getMostRecentEditorDecisionComment() {
		return $this->getData('mostRecentEditorDecisionComment');
	}
	
	/**
	 * Set most recent editor decision comment.
	 * @param $mostRecentEditorDecisionComment PaperComment
	 */
	function setMostRecentEditorDecisionComment($mostRecentEditorDecisionComment) {
		return $this->setData('mostRecentEditorDecisionComment', $mostRecentEditorDecisionComment);
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
	 * Get the layout assignment for an paper.
	 * @return LayoutAssignment
	 */
	function &getLayoutAssignment() {
		$layoutAssignment = &$this->getData('layoutAssignment');
		return $layoutAssignment;
	}
	
	/**
	 * Set the layout assignment for an paper.
	 * @param $layoutAssignment LayoutAssignment
	 */
	function setLayoutAssignment(&$layoutAssignment) {
		return $this->setData('layoutAssignment', $layoutAssignment);
	}
	
	/**
	 * Get the galleys for an paper.
	 * @return array PaperGalley
	 */
	function &getGalleys() {
		$galleys = &$this->getData('galleys');
		return $galleys;
	}
	
	/**
	 * Set the galleys for an paper.
	 * @param $galleys array PaperGalley
	 */
	function setGalleys(&$galleys) {
		return $this->setData('galleys', $galleys);
	}

	/**
	 * Return array mapping editor decision constants to their locale strings.
	 * (Includes default mapping '' => "Choose One".)
	 * @return array decision => localeString
	 */
	function &getEditorDecisionOptions() {
		static $editorDecisionOptions = array(
			'' => 'common.chooseOne',
			SUBMISSION_EDITOR_DECISION_ACCEPT => 'editor.paper.decision.accept',
			SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS => 'editor.paper.decision.pendingRevisions',
			SUBMISSION_EDITOR_DECISION_RESUBMIT => 'editor.paper.decision.resubmit',
			SUBMISSION_EDITOR_DECISION_DECLINE => 'editor.paper.decision.decline'
		);
		return $editorDecisionOptions;
	}
}

?>
