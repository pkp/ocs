<?php

/**
 * @file classes/submission/reviewAssignment/ReviewAssignment.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewAssignment
 * @ingroup submission
 * @see ReviewAssignmentDAO
 *
 * @brief Describes review assignment properties.
 */



import('lib.pkp.classes.submission.reviewAssignment.PKPReviewAssignment');

class ReviewAssignment extends PKPReviewAssignment {
	/**
	 * Constructor.
	 */
	function ReviewAssignment() {
		parent::PKPReviewAssignment();
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
		return $this->getSubmissionId();
	}

	/**
	 * Set ID of paper.
	 * @param $paperId int
	 */
	function setPaperId($paperId) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->setSubmissionId($paperId);
	}

	/**
	 * Get stage. DEPRECATED.
	 * @return int
	 */
	function getStage() {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->getRound();
	}

	/**
	 * Set stage. DEPRECATED.
	 * @param $stage int
	 */
	function setStage($stage) {
		if (Config::getVar('debug', 'deprecation_warnings')) trigger_error('Deprecated function.');
		return $this->setRound($stage);
	}

	/**
	 * Get an associative array matching reviewer recommendation codes with locale strings.
	 * (Includes default '' => "Choose One" string.)
	 * @return array recommendation => localeString
	 */
	function &getReviewerRecommendationOptions() {
		// Bring in reviewer constants
		import('classes.submission.reviewer.ReviewerSubmission');

		static $reviewerRecommendationOptions = array(
			'' => 'common.chooseOne',
			SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT => 'reviewer.paper.decision.accept',
			SUBMISSION_REVIEWER_RECOMMENDATION_PENDING_REVISIONS => 'reviewer.paper.decision.pendingRevisions',
			SUBMISSION_REVIEWER_RECOMMENDATION_RESUBMIT_ELSEWHERE => 'reviewer.paper.decision.resubmitElsewhere',
			SUBMISSION_REVIEWER_RECOMMENDATION_DECLINE => 'reviewer.paper.decision.decline',
			SUBMISSION_REVIEWER_RECOMMENDATION_SEE_COMMENTS => 'reviewer.paper.decision.seeComments'
		);
		return $reviewerRecommendationOptions;
	}

	/**
	 * Get an associative array matching reviewer rating codes with locale strings.
	 * @return array recommendation => localeString
	 */
	function &getReviewerRatingOptions() {
		static $reviewerRatingOptions = array(
			SUBMISSION_REVIEWER_RATING_VERY_GOOD => 'director.paper.reviewerRating.veryGood',
			SUBMISSION_REVIEWER_RATING_GOOD => 'director.paper.reviewerRating.good',
			SUBMISSION_REVIEWER_RATING_AVERAGE => 'director.paper.reviewerRating.average',
			SUBMISSION_REVIEWER_RATING_POOR => 'director.paper.reviewerRating.poor',
			SUBMISSION_REVIEWER_RATING_VERY_POOR => 'director.paper.reviewerRating.veryPoor'
		);
		return $reviewerRatingOptions;
	}
}

?>
