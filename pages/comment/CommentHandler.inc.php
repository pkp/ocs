<?php

/**
 * @file CommentHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CommentHandler
 * @ingroup pages_user
 *
 * @brief Handle requests for user comments.
 *
 */



import('classes.rt.ocs.RTDAO');
import('classes.rt.ocs.ConferenceRT');
import('classes.handler.Handler');

class CommentHandler extends Handler {
	/** the paper associated with the comment **/
	var $paper;

	/**
	 * Constructor
	 **/
	function CommentHandler() {
		parent::Handler();

		$this->addCheck(new HandlerValidatorConference($this));
		$this->addCheck(new HandlerValidatorSchedConf($this));
	}

	function view($args, &$request) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$commentId = isset($args[2]) ? (int) $args[2] : 0;

		$this->validate($paperId);
		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();
		$paper =& $this->paper;

		$user =& $request->getUser();
		$userId = isset($user)?$user->getId():null;

		$commentDao = DAORegistry::getDAO('CommentDAO');
		$comment =& $commentDao->getById($commentId, $paperId, 2);

		$roleDao = DAORegistry::getDAO('RoleDAO');
		$isManager = Validation::isConferenceManager($conference->getId());

		if (!$comment) $comments =& $commentDao->getRootCommentsBySubmissionId($paperId, 1);
		else $comments =& $comment->getChildren();

		$this->setupTemplate($request, $paper, $galleyId, $comment);

		$templateMgr =& TemplateManager::getManager($request);
		if ($request->getUserVar('refresh')) $templateMgr->setCacheability(CACHEABILITY_NO_CACHE);
		if ($comment) {
			$templateMgr->assign_by_ref('comment', $comment);
			$templateMgr->assign_by_ref('parent', $commentDao->getById($comment->getParentCommentId(), $paperId));
		}
		$templateMgr->assign_by_ref('comments', $comments);
		$templateMgr->assign('paperId', $paperId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign('enableComments', $conference->getSetting('enableComments'));
		$templateMgr->assign('commentsRequireRegistration', $conference->getSetting('commentsRequireRegistration'));
		$templateMgr->assign('commentsAllowAnonymous', $conference->getSetting('commentsAllowAnonymous'));

		$closeCommentsDate = $schedConf->getSetting('closeCommentsDate');
		$commentsClosed = $schedConf->getSetting('closeComments')?true:false && (strtotime($closeCommentsDate < time()));

		$templateMgr->assign('closeCommentsDate', $closeCommentsDate);
		$templateMgr->assign('commentsClosed', $commentsClosed);
		$templateMgr->assign('isManager', $isManager);

		$templateMgr->display('comment/comments.tpl');
	}

	function add($args, $request) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$parentId = isset($args[2]) ? (int) $args[2] : 0;

		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();
		$this->validate($paperId);
		$paper =& $this->paper;
		$commentDao = DAORegistry::getDAO('CommentDAO');
		$parent =& $commentDao->getById($parentId, $paperId);
		if (isset($parent) && $parent->getSubmissionId() != $paperId) {
			$request->redirect(null, null, null, 'view', array($paperId, $galleyId));
		}

		$this->setupTemplate($request, $paper, $galleyId, $parent);

		// Bring in comment constants
		$commentDao = DAORegistry::getDAO('CommentDAO');

		$enableComments = $conference->getSetting('enableComments');
		$commentsRequireRegistration = $conference->getSetting('commentsRequireRegistration');
		$commentsAllowAnonymous = $conference->getSetting('commentsAllowAnonymous');

		$closeCommentsDate = $schedConf->getSetting('closeCommentsDate');
		$commentsClosed = $schedConf->getSetting('closeComments')?true:false && (strtotime($closeCommentsDate < time()));

		$enableComments = $enableComments && !$commentsClosed && $paper->getEnableComments();

		if (!$enableComments) $request->redirect(null, null, 'index');
		if ($commentsRequireRegistration && !$request->getUser()) Validation::redirectLogin();

		import('classes.comment.form.CommentForm');
		$commentForm = new CommentForm(null, $paperId, $galleyId, isset($parent)?$parentId:null);
		$commentForm->initData();

		if (isset($args[3]) && $args[3]=='save') {
			$commentForm->readInputData();
			if ($commentForm->validate()) {
				$commentForm->execute();

				// Send a notification to associated users
				import('classes.notification.NotificationManager');
				$notificationManager = new NotificationManager();
				$paperDao = DAORegistry::getDAO('PaperDAO');
				$paper =& $paperDao->getPaper($paperId);
				$notificationUsers = $paper->getAssociatedUserIds();
				foreach ($notificationUsers as $userRole) {
					$notificationManager->createNotification(
						$request, $userRole['id'], NOTIFICATION_TYPE_USER_COMMENT,
						$conference->getId(), ASSOC_TYPE_PAPER, $paper->getId()
					);
				}

				$request->redirect(null, null, null, 'view', array($paperId, $galleyId, $parentId), array('refresh' => 1));
			}
		}

		$commentForm->display();
	}

	/**
	 * Delete the specified comment and all its children.
	 */
	function delete($args, &$request) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$commentId = isset($args[2]) ? (int) $args[2] : 0;

		$this->validate($paperId);
		$user =& $request->getUser();
		$userId = isset($user)?$user->getId():null;

		$commentDao = DAORegistry::getDAO('CommentDAO');

		if (!Validation::isConferenceManager()) {
			$request->redirect(null, null, 'index');
		}

		$comment = $commentDao->getById($commentId, $paperId, SUBMISSION_COMMENT_RECURSE_ALL);
		if ($comment) $commentDao->deleteObject($comment);

		$request->redirect(null, null, null, 'view', array($paperId, $galleyId), array('refresh' => 1));
	}

	/**
	 * Validation
	 */
	function validate($paperId) {
		parent::validate();
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();

		$publishedPaperDao = DAORegistry::getDAO('PublishedPaperDAO');
		$paper =& $publishedPaperDao->getPublishedPaperByPaperId($paperId, $schedConf->getId(), $schedConf->getSetting('previewAbstracts'));
		$this->paper =& $paper;

		if ($paper == null) {
			Request::redirect(null, null, 'index');
		}

		// Bring in comment and view constants
		$commentDao = DAORegistry::getDAO('CommentDAO');
		$enableComments = $conference->getSetting('enableComments');

		if (!$enableComments || !$paper->getEnableComments()) {
			Request::redirect(null, null, 'index');
		}

		$restrictPaperAccess = $conference->getSetting('restrictPaperAccess');

		if ($restrictPaperAccess && !Validation::isLoggedIn()) {
			Validation::redirectLogin();
		}

		return true;
	}

	function setupTemplate($request, $paper, $galleyId, $comment = null) {
		parent::setupTemplate($request);
		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->setCacheability(CACHEABILITY_PUBLIC);
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_READER);

		$pageHierarchy = array(
			array(
				$request->url(null, null, 'paper', 'view', array(
					$paper->getBestPaperId($request->getConference()), $galleyId
				)),
				String::stripUnsafeHtml($paper->getLocalizedTitle()),
				true
			)
		);

		if ($comment) $pageHierarchy[] = array($request->url(null, null, 'comment', 'view', array($paper->getId(), $galleyId)), 'comments.readerComments');
		$templateMgr->assign('pageHierarchy', $pageHierarchy);
	}
}

?>
