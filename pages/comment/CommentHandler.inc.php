<?php

/**
 * @file CommentHandler.inc.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.user
 * @class CommentHandler
 *
 * Handle requests for user comments.
 *
 * $Id$
 */

import('rt.ocs.RTDAO');
import('rt.ocs.ConferenceRT');

class CommentHandler extends Handler {
	function view($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$commentId = isset($args[2]) ? (int) $args[2] : 0;

		list($conference, $schedConf, $paper) = CommentHandler::validate($paperId);

		$user = &Request::getUser();
		$userId = isset($user)?$user->getUserId():null;

		$commentDao = &DAORegistry::getDAO('CommentDAO');
		$comment = &$commentDao->getComment($commentId, $paperId, 2);

		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$isManager = Validation::isConferenceManager($conference->getConferenceId());

		if (!$comment) $comments = &$commentDao->getRootCommentsByPaperId($paperId, 1);
		else $comments = &$comment->getChildren();

		CommentHandler::setupTemplate($paper, $galleyId, $comment);

		$templateMgr = &TemplateManager::getManager();
		if (Request::getUserVar('refresh')) $templateMgr->setCacheability(CACHEABILITY_NO_CACHE);
		if ($comment) {
			$templateMgr->assign_by_ref('comment', $comment);
			$templateMgr->assign_by_ref('parent', $commentDao->getComment($comment->getParentCommentId(), $paperId));
		}
		$templateMgr->assign_by_ref('comments', $comments);
		$templateMgr->assign('paperId', $paperId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign('enableComments', $schedConf->getSetting('enableComments', true));
		$templateMgr->assign('commentsRequireRegistration', $schedConf->getSetting('commentsRequireRegistration', true));
		$templateMgr->assign('commentsAllowAnonymous', $schedConf->getSetting('commentsAllowAnonymous', true));
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

		list($conference, $schedConf, $paper) = CommentHandler::validate($paperId);

		// Bring in comment constants
		$commentDao = &DAORegistry::getDAO('CommentDAO');

		$enableComments = $schedConf->getSetting('enableComments', true) && (!$schedConf->getSetting('closeComments') || (strtotime($schedConf->getSetting('closeCommentsDate') > time())));
		$commentsRequireRegistration = $schedConf->getSetting('commentsRequireRegistration', true);
		$commentsAllowAnonymous = $schedConf->getSetting('commentsAllowAnonymous', true);

		if (!$enableComments) Request::redirect(null, null, 'index');
		if ($commentsRequireRegistration && !Request::getUser()) Validation::redirectLogin();

		$parent = &$commentDao->getComment($parentId, $paperId);
		if (isset($parent) && $parent->getPaperId() != $paperId) {
			Request::redirect(null, null, null, 'view', array($paperId, $galleyId));
		}

		import('comment.form.CommentForm');
		$commentForm = &new CommentForm(null, $paperId, $galleyId, isset($parent)?$parentId:null);
		$commentForm->initData();

		if (isset($args[3]) && $args[3]=='save') {
			$commentForm->readInputData();
			$commentForm->execute();
			Request::redirect(null, null, null, 'view', array($paperId, $galleyId, $parentId), array('refresh' => 1));
		} else {
			CommentHandler::setupTemplate($paper, $galleyId, $parent);
			$commentForm->display();
		}
	}

	/**
	 * Delete the specified comment and all its children.
	 */
	function delete($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$commentId = isset($args[2]) ? (int) $args[2] : 0;

		list($conference, $schedConf, $paper) = CommentHandler::validate($paperId);
		$user = &Request::getUser();
		$userId = isset($user)?$user->getUserId():null;

		$commentDao = &DAORegistry::getDAO('CommentDAO');

		if (!Validation::isConferenceManager()) {
			Request::redirect(null, null, 'index');
		}

		$comment = &$commentDao->getComment($commentId, $paperId, PAPER_COMMENT_RECURSE_ALL);
		if ($comment)$commentDao->deleteComment($comment);

		Request::redirect(null, null, null, 'view', array($paperId, $galleyId), array('refresh' => 1));
	}

	/**
	 * Validation
	 */
	function validate($paperId) {

		parent::validate();

		$conference = &Request::getConference();
		$schedConf = &Request::getSchedConf();

		// Bring in comment constants
		$commentDao = &DAORegistry::getDAO('CommentDAO');

		$enableComments = $schedConf->getSetting('enableComments', true);
		$commentsRequireRegistration = $schedConf->getSetting('commentsRequireRegistration', true);
		$commentsAllowAnonymous = $schedConf->getSetting('commentsAllowAnonymous', true);


		if (!Validation::isLoggedIn() && $schedConf->getSetting('restrictPaperAccess', true) || !$enableComments) {
			Validation::redirectLogin();
		}

		// Registration Access
		$publishedPaperDao = &DAORegistry::getDAO('PublishedPaperDAO');
		$paper = &$publishedPaperDao->getPublishedPaperByPaperId($paperId);

		if (!isset($paper)) {
			Request::redirect(null, null, 'index');
		}

		return array(&$conference, &$schedConf, &$paper);
	}

	function setupTemplate($paper, $galleyId, $comment = null) {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->setCacheability(CACHEABILITY_PUBLIC);

		$pageHierarchy = array(
			array(
				Request::url(null, null, 'paper', 'view', array(
					$paper->getBestPaperId(Request::getConference()), $galleyId
				)),
				String::stripUnsafeHtml($paper->getPaperTitle()),
				true
			)
		);

		if ($comment) $pageHierarchy[] = array(Request::url(null, null, 'comment', 'view', array($paper->getPaperId(), $galleyId)), 'comments.readerComments');
		$templateMgr->assign('pageHierarchy', $pageHierarchy);
	}
}

?>
