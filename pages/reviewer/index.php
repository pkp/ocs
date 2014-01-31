<?php

/**
 * @defgroup pages_reviewer
 */
 
/**
 * @file pages/reviewer/index.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Handle requests for reviewer functions.
 *
 * @ingroup pages_reviewer
 */

//$Id$


switch ($op) {
	//
	// Submission Tracking
	//
	case 'submission':
	case 'confirmReview':
	case 'recordRecommendation':
	case 'viewMetadata':
	case 'uploadReviewerVersion':
	case 'deleteReviewerVersion':
	//
	// Misc.
	//
	case 'downloadFile':
	//
	// Submission Review Form
	//
	case 'editReviewFormResponse':
	case 'saveReviewFormResponse':
		define('HANDLER_CLASS', 'SubmissionReviewHandler');
		import('pages.reviewer.SubmissionReviewHandler');
		break;
	//
	// Submission Comments
	//
	case 'viewPeerReviewComments':
	case 'postPeerReviewComment':
	case 'editComment':
	case 'saveComment':
	case 'deleteComment':
		define('HANDLER_CLASS', 'SubmissionCommentsHandler');
		import('pages.reviewer.SubmissionCommentsHandler');
		break;
	case 'index':
		define('HANDLER_CLASS', 'ReviewerHandler');
		import('pages.reviewer.ReviewerHandler');
		break;
}

?>
