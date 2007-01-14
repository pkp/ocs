<?php

/**
 * LayoutEditorSubmission.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission.layoutEditor
 *
 * LayoutEditorSubmission class.
 * Describes a layout editor's view of a submission
 *
 * $Id$
 */

import('paper.Paper');

class LayoutEditorSubmission extends Paper {

	/**
	 * Constructor.
	 */
	function LayoutEditorSubmission() {
		parent::Paper();
	}
	
	//
	// Get/set methods
	//
	
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
	
	
	// FIXME These should probably be in an abstract "Submission" base class
	
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
	// Comments
	//
	
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
}

?>
