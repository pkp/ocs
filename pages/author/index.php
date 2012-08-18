<?php

/**
 * @defgroup pages_author
 */
 
/**
 * @file pages/author/index.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Handle requests for author functions.
 *
 * @ingroup pages_author
 */



switch ($op) {
	//
	// Paper Submission
	//
	case 'submit':
	case 'saveSubmit':
	case 'submitSuppFile':
	case 'saveSubmitSuppFile':
	case 'deleteSubmitSuppFile':
		define('HANDLER_CLASS', 'SubmitHandler');
		import('pages.author.SubmitHandler');
		break;
	//
	// Submission Tracking
	//
	case 'deletePaperFile':
	case 'deleteSubmission':
	case 'submission':
	case 'viewSuppFile':
	case 'editSuppFile':
	case 'setSuppFileVisibility':
	case 'saveSuppFile':
	case 'addSuppFile':
	case 'submissionReview':
	case 'uploadRevisedVersion':
	case 'viewMetadata':
	case 'saveMetadata':
	//
	// Misc.
	//
	case 'downloadFile':
	case 'viewFile':
	case 'download':
		define('HANDLER_CLASS', 'TrackSubmissionHandler');
		import('pages.author.TrackSubmissionHandler');
		break;
	//
	// Submission Comments
	//
	case 'viewDirectorDecisionComments':
	case 'emailDirectorDecisionComment':
	case 'editComment':
	case 'saveComment':
	case 'deleteComment':
		define('HANDLER_CLASS', 'SubmissionCommentsHandler');
		import('pages.author.SubmissionCommentsHandler');
		break;
	case 'index':
		define('HANDLER_CLASS', 'AuthorHandler');
		import('pages.author.AuthorHandler');
		break;
}

?>
