<?php

/**
 * @defgroup pages_director
 */
 
/**
 * @file pages/director/index.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Handle requests for director functions. 
 *
 * @ingroup pages_director
 */


switch ($op) {
	//
	// Submission Tracking
	//
	case 'enrollSearch':
	case 'createReviewer':
	case 'enroll':
	case 'submission':
	case 'submissionRegrets':
	case 'submissionReview':
	case 'submissionHistory':
	case 'changeTrack':
	case 'changeSessionType':
	case 'recordDecision':
	case 'selectReviewer':
	case 'notifyReviewer':
	case 'notifyAllReviewers':
	case 'userProfile':
	case 'clearReview':
	case 'cancelReview':
	case 'remindReviewer':
	case 'thankReviewer':
	case 'rateReviewer':
	case 'reassignReviewer':
	case 'confirmReviewForReviewer':
	case 'uploadReviewForReviewer':
	case 'enterReviewerRecommendation':
	case 'makeReviewerFileViewable':
	case 'setDueDate':
	case 'viewMetadata':
	case 'saveMetadata':
	case 'directorReview':
	case 'uploadReviewVersion':
	case 'addSuppFile':
	case 'setSuppFileVisibility':
	case 'editSuppFile':
	case 'saveSuppFile':
	case 'deleteSuppFile':
	case 'deletePaperFile':
	case 'archiveSubmission':
	case 'unsuitableSubmission':
	case 'restoreToQueue':
	case 'updateCommentsStatus':
	//
	// Layout Editing
	//
	case 'deletePaperImage':
	case 'uploadLayoutFile':
	case 'uploadGalley':
	case 'editGalley':
	case 'saveGalley':
	case 'orderGalley':
	case 'deleteGalley':
	case 'proofGalley':
	case 'proofGalleyTop':
	case 'proofGalleyFile':
	case 'uploadSuppFile':
	case 'orderSuppFile':
	case 'completePaper':
	//
	// Submission History
	//
	case 'submissionEventLog':
	case 'submissionEventLogType':
	case 'clearSubmissionEventLog':
	case 'submissionEmailLog':
	case 'submissionEmailLogType':
	case 'clearSubmissionEmailLog':
	case 'addSubmissionNote':
	case 'removeSubmissionNote':
	case 'updateSubmissionNote':
	case 'clearAllSubmissionNotes':
	case 'submissionNotes':
	//
	// Submission Review Form
	//
	case 'clearReviewForm':
	case 'selectReviewForm':
	case 'previewReviewForm':
	case 'viewReviewFormResponse':
	//
	// Misc.
	//
	case 'downloadFile':
	case 'viewFile':
	case 'suggestUsername':
		define('HANDLER_CLASS', 'SubmissionEditHandler');
		import('pages.trackDirector.SubmissionEditHandler');
		break;
	//
	// Submission Comments
	//
	case 'viewPeerReviewComments':
	case 'postPeerReviewComment':
	case 'viewDirectorDecisionComments':
	case 'blindCcReviewsToReviewers':
	case 'postDirectorDecisionComment':
	case 'emailDirectorDecisionComment':
	case 'editComment':
	case 'saveComment':
	case 'deleteComment':
		define('HANDLER_CLASS', 'SubmissionCommentsHandler');
		import('pages.trackDirector.SubmissionCommentsHandler');
		break;
	case 'index':
	case 'submissions':
	case 'deleteEditAssignment':
	case 'assignDirector':
	case 'deleteSubmission':
	case 'movePaper':
	case 'notifyUsers':
	case 'instructions':
		define('HANDLER_CLASS', 'DirectorHandler');
		import('pages.director.DirectorHandler');
}

?>
