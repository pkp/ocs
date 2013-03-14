<?php

/**
 * @file ReviewerHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewerHandler
 * @ingroup pages_reviewer
 *
 * @brief Handle requests for reviewer functions. 
 *
 */



import('classes.submission.reviewer.ReviewerAction');
import('classes.handler.Handler');

class ReviewerHandler extends Handler {
	/**
	 * Constructor
	 **/
	function ReviewerHandler() {
		parent::Handler();

		$this->addCheck(new HandlerValidatorConference($this));
		$this->addCheck(new HandlerValidatorSchedConf($this));
		$this->addCheck(new HandlerValidatorRoles($this, true, null, null, array(ROLE_ID_REVIEWER)));		
	}

	/**
	 * Display reviewer index page.
	 */
	function index($args, $request) {
		$this->validate($request);
		$this->setupTemplate($request);

		$schedConf =& $request->getSchedConf();
		$user =& $request->getUser();
		$reviewerSubmissionDao = DAORegistry::getDAO('ReviewerSubmissionDAO');
		$rangeInfo = $this->getRangeInfo($request, 'submissions');

		$page = isset($args[0]) ? $args[0] : '';
		switch($page) {
			case 'completed':
				AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR);
				$active = false;
				break;
			default:
				$page = 'active';
				$active = true;
		}

		$submissions = $reviewerSubmissionDao->getReviewerSubmissionsByReviewerId($user->getId(), $schedConf->getId(), $active, $rangeInfo);

		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign('reviewerRecommendationOptions', ReviewAssignment::getReviewerRecommendationOptions());
		$templateMgr->assign('pageToDisplay', $page);
		$templateMgr->assign_by_ref('submissions', $submissions);
		$templateMgr->assign_by_ref('schedConf', $schedConf);

		import('classes.submission.reviewAssignment.ReviewAssignment');
		$templateMgr->assign_by_ref('reviewerRecommendationOptions', ReviewAssignment::getReviewerRecommendationOptions());

		$templateMgr->assign('helpTopicId', 'editorial.reviewersRole.submissions');
		$templateMgr->display('reviewer/index.tpl');
	}

	/**
	 * Used by subclasses to validate access keys when they are allowed.
	 * @param $userId int The user this key refers to
	 * @param $reviewId int The ID of the review this key refers to
	 * @param $newKey string The new key name, if one was supplied; otherwise, the existing one (if it exists) is used
	 * @return object Valid user object if the key was valid; otherwise NULL.
	 */
	function &_validateAccessKey($request, $userId, $reviewId, $newKey = null) {
		$schedConf =& $request->getSchedConf();
		if (!$schedConf || !$schedConf->getSetting('reviewerAccessKeysEnabled')) {
			$accessKey = false;
			return $accessKey;
		}

		define('REVIEWER_ACCESS_KEY_SESSION_VAR', 'ReviewerAccessKey');

		import('lib.pkp.classes.security.AccessKeyManager');
		$accessKeyManager = new AccessKeyManager();

		$session =& $request->getSession();
		// Check to see if a new access key is being used.
		if (!empty($newKey)) {
			if (Validation::isLoggedIn()) {
				Validation::logout();
			}
			$keyHash = $accessKeyManager->generateKeyHash($newKey);
			$session->setSessionVar(REVIEWER_ACCESS_KEY_SESSION_VAR, $keyHash);
		} else {
			$keyHash = $session->getSessionVar(REVIEWER_ACCESS_KEY_SESSION_VAR);
		}

		// Now that we've gotten the key hash (if one exists), validate it.
		$accessKey =& $accessKeyManager->validateKey(
			'ReviewerContext',
			$userId,
			$keyHash,
			$reviewId
		);

		if ($accessKey) {
			$userDao = DAORegistry::getDAO('UserDAO');
			$user =& $userDao->getById($accessKey->getUserId(), false);
			return $user;
		}

		// No valid access key -- return NULL.
		return $accessKey;
	}

	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($request, $subclass = false, $paperId = 0, $reviewId = 0) {
		parent::setupTemplate($request);
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION);
		$templateMgr =& TemplateManager::getManager($request);
		$pageHierarchy = $subclass ? array(array($request->url(null, null, 'user'), 'navigation.user'), array($request->url(null, null, 'reviewer'), 'user.role.reviewer'))
				: array(array($request->url(null, null, 'user'), 'navigation.user'), array($request->url(null, null, 'reviewer'), 'user.role.reviewer'));

		if ($paperId && $reviewId) {
			$pageHierarchy[] = array($request->url(null, null, 'reviewer', 'submission', $reviewId), "#$paperId", true);
		}
		$templateMgr->assign('pageHierarchy', $pageHierarchy);
	}
}

?>
