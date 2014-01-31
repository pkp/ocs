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

// $Id$


import('rt.ocs.RTDAO');
import('rt.ocs.ConferenceRT');
import('handler.Handler');

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

	function view($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$commentId = isset($args[2]) ? (int) $args[2] : 0;

		$this->validate($paperId);
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$paper =& $this->paper;

		$user =& Request::getUser();
		$userId = isset($user)?$user->getId():null;

		$commentDao =& DAORegistry::getDAO('CommentDAO');
		$comment =& $commentDao->getComment($commentId, $paperId, 2);

		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$isManager = Validation::isConferenceManager($conference->getId());

		if (!$comment) $comments =& $commentDao->getRootCommentsByPaperId($paperId, 1);
		else $comments =& $comment->getChildren();

		$this->setupTemplate($paper, $galleyId, $comment);

		$templateMgr =& TemplateManager::getManager();
		if (Request::getUserVar('refresh')) $templateMgr->setCacheability(CACHEABILITY_NO_CACHE);
		if ($comment) {
			$templateMgr->assign_by_ref('comment', $comment);
			$templateMgr->assign_by_ref('parent', $commentDao->getComment($comment->getParentCommentId(), $paperId));
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

	function add($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$parentId = isset($args[2]) ? (int) $args[2] : 0;

		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$this->validate($paperId);
		$paper =& $this->paper;

		$commentDao =& DAORegistry::getDAO('CommentDAO');
		$parent =& $commentDao->getComment($parentId, $paperId);
		if (isset($parent) && $parent->getPaperId() != $paperId) {
			Request::redirect(null, null, null, 'view', array($paperId, $galleyId));
		}

		$this->setupTemplate($paper, $galleyId, $parent);

		// Bring in comment constants
		$commentDao =& DAORegistry::getDAO('CommentDAO');

		$enableComments = $conference->getSetting('enableComments');
		$commentsRequireRegistration = $conference->getSetting('commentsRequireRegistration');
		$commentsAllowAnonymous = $conference->getSetting('commentsAllowAnonymous');

		$closeCommentsDate = $schedConf->getSetting('closeCommentsDate');
		$commentsClosed = $schedConf->getSetting('closeComments')?true:false && (strtotime($closeCommentsDate < time()));

		$enableComments = $enableComments && !$commentsClosed && $paper->getEnableComments();

		if (!$enableComments) Request::redirect(null, null, 'index');
		if ($commentsRequireRegistration && !Request::getUser()) Validation::redirectLogin();

		import('comment.form.CommentForm');
		$commentForm = new CommentForm(null, $paperId, $galleyId, isset($parent)?$parentId:null);
		$commentForm->initData();

		if (isset($args[3]) && $args[3]=='save') {
			$commentForm->readInputData();
			if ($commentForm->validate()) {
				$commentForm->execute();

				// Send a notification to associated users
				import('notification.NotificationManager');
				$notificationManager = new NotificationManager();
				$paperDAO =& DAORegistry::getDAO('PaperDAO');
				$paper =& $paperDAO->getPaper($paperId);
				$notificationUsers = $paper->getAssociatedUserIds();
				foreach ($notificationUsers as $userRole) {
					$url = Request::url(null, null, null, 'view', array($paperId, $galleyId, $parentId));
					$notificationManager->createNotification(
						$userRole['id'], 'notification.type.userComment',
						$paper->getLocalizedTitle(), $url, 1, NOTIFICATION_TYPE_USER_COMMENT
					);
				}

				Request::redirect(null, null, null, 'view', array($paperId, $galleyId, $parentId), array('refresh' => 1));
			}
		}

		$commentForm->display();
	}

	/**
	 * Delete the specified comment and all its children.
	 */
	function delete($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$commentId = isset($args[2]) ? (int) $args[2] : 0;

		$this->validate($paperId);
		$user =& Request::getUser();
		$userId = isset($user)?$user->getId():null;

		$commentDao =& DAORegistry::getDAO('CommentDAO');

		if (!Validation::isConferenceManager()) {
			Request::redirect(null, null, 'index');
		}

		$comment =& $commentDao->getComment($commentId, $paperId, PAPER_COMMENT_RECURSE_ALL);
		if ($comment)$commentDao->deleteComment($comment);

		Request::redirect(null, null, null, 'view', array($paperId, $galleyId), array('refresh' => 1));
	}

	/**
	 * Validation
	 */
	function validate($paperId) {
		parent::validate();
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();

		$publishedPaperDao =& DAORegistry::getDAO('PublishedPaperDAO');
		$paper =& $publishedPaperDao->getPublishedPaperByPaperId($paperId, $schedConf->getId(), $schedConf->getSetting('previewAbstracts'));
		$this->paper =& $paper;

		if ($paper == null) {
			Request::redirect(null, null, 'index');
		}

		// Bring in comment and view constants
		$commentDao =& DAORegistry::getDAO('CommentDAO');
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

	function setupTemplate($paper, $galleyId, $comment = null) {
		parent::setupTemplate();
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->setCacheability(CACHEABILITY_PUBLIC);
		AppLocale::requireComponents(array(LOCALE_COMPONENT_PKP_READER));

		$pageHierarchy = array(
			array(
				Request::url(null, null, 'paper', 'view', array(
					$paper->getBestPaperId(Request::getConference()), $galleyId
				)),
				String::stripUnsafeHtml($paper->getLocalizedTitle()),
				true
			)
		);

		if ($comment) $pageHierarchy[] = array(Request::url(null, null, 'comment', 'view', array($paper->getId(), $galleyId)), 'comments.readerComments');
		$templateMgr->assign('pageHierarchy', $pageHierarchy);
	}
}

?>
