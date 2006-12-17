<?php

/**
 * CommentHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.user
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

		list($conference, $issue, $paper) = CommentHandler::validate($paperId);

		$user = &Request::getUser();
		$userId = isset($user)?$user->getUserId():null;

		$commentDao = &DAORegistry::getDAO('CommentDAO');
		$comment = &$commentDao->getComment($commentId, $paperId, 2);

		$roleDao = &DAORegistry::getDAO('RoleDAO');
		$isDirector = $roleDao->roleExists($conference->getConferenceId(), FIXME event, $userId, ROLE_ID_CONFERENCE_DIRECTOR);

		if (!$comment) $comments = &$commentDao->getRootCommentsByPaperId($paperId, 1);
		else $comments = &$comment->getChildren();

		CommentHandler::setupTemplate($paper, $galleyId, $comment);

		$templateMgr = &TemplateManager::getManager();
		if ($comment) {
			$templateMgr->assign_by_ref('comment', $comment);
			$templateMgr->assign_by_ref('parent', $commentDao->getComment($comment->getParentCommentId(), $paperId));
		}
		$templateMgr->assign_by_ref('comments', $comments);
		$templateMgr->assign('paperId', $paperId);
		$templateMgr->assign('galleyId', $galleyId);
		$templateMgr->assign('enableComments', $conference->getSetting('enableComments'));
		$templateMgr->assign('isDirector', $isDirector);

		$templateMgr->display('comment/comments.tpl');
	}

	function add($args) {
		$paperId = isset($args[0]) ? (int) $args[0] : 0;
		$galleyId = isset($args[1]) ? (int) $args[1] : 0;
		$parentId = isset($args[2]) ? (int) $args[2] : 0;

		list($conference, $issue, $paper) = CommentHandler::validate($paperId);

		// Bring in comment constants
		$commentDao = &DAORegistry::getDAO('CommentDAO');

		$enableComments = $conference->getSetting('enableComments');
		switch ($enableComments) {
			case COMMENTS_UNAUTHENTICATED:
				break;
			case COMMENTS_AUTHENTICATED:
			case COMMENTS_ANONYMOUS:
				// The user must be logged in to post comments.
				if (!Request::getUser()) {
					Validation::redirectLogin();
				}
				break;
			default:
				// Comments are disabled.
				Validation::redirectLogin();
		}

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
			Request::redirect(null, null, null, 'view', array($paperId, $galleyId, $parentId));
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

		list($conference, $issue, $paper) = CommentHandler::validate($paperId);
		$user = &Request::getUser();
		$userId = isset($user)?$user->getUserId():null;

		$commentDao = &DAORegistry::getDAO('CommentDAO');

		$roleDao = &DAORegistry::getDAO('RoleDAO');
		if (!$roleDao->roleExists($conference->getConferenceId(), FIXME event, $userId, ROLE_ID_CONFERENCE_DIRECTOR)) {
			Request::redirect(null, null, 'index');
		}

		$comment = &$commentDao->getComment($commentId, $paperId, PAPER_COMMENT_RECURSE_ALL);
		if ($comment)$commentDao->deleteComment($comment);

		Request::redirect(null, null, null, 'view', array($paperId, $galleyId));
	}

	/**
	 * Validation
	 */
	function validate($paperId) {

		parent::validate();

		$conference = &Request::getConference();
		$conferenceId = $conference->getConferenceId();
		$conferenceSettingsDao = &DAORegistry::getDAO('ConferenceSettingsDAO');

		// Bring in comment constants
		$commentDao = &DAORegistry::getDAO('CommentDAO');

		$enableComments = $conference->getSetting('enableComments');

		if (!Validation::isLoggedIn() && $conferenceSettingsDao->getSetting($conferenceId,'restrictPaperAccess') || ($enableComments != COMMENTS_ANONYMOUS && $enableComments != COMMENTS_AUTHENTICATED && $enableComments != COMMENTS_UNAUTHENTICATED)) {
			Validation::redirectLogin();
		}

		// Subscription Access
		$issueDao = &DAORegistry::getDAO('IssueDAO');
		$issue = &$issueDao->getIssueByPaperId($paperId);

		$publishedPaperDao = &DAORegistry::getDAO('PublishedPaperDAO');
		$paper = &$publishedPaperDao->getPublishedPaperByPaperId($paperId);

		if (isset($issue) && isset($paper)) {
			import('issue.IssueAction');
			$subscriptionRequired = IssueAction::subscriptionRequired($issue);
			$subscribedUser = IssueAction::subscribedUser($conference);

			if (!(!$subscriptionRequired || $paper->getAccessStatus() || $subscribedUser)) {
				Request::redirect(null, null, 'index');
			}
		} else {
			Request::redirect(null, null, 'index');
		}

		return array(&$conference, &$issue, &$paper);
	}

	function setupTemplate($paper, $galleyId, $comment = null) {
		$templateMgr = &TemplateManager::getManager();

		$pageHierarchy = array(
			array(
				Request::url(null, 'paper', 'view', array(
					$paper->getBestPaperId(Request::getConference()), $galleyId
				)),
				String::stripUnsafeHtml($paper->getPaperTitle()),
				true
			)
		);

		if ($comment) $pageHierarchy[] = array(Request::url(null, 'comment', 'view', array($paper->getPaperId(), $galleyId)), 'comments.readerComments');
		$templateMgr->assign('pageHierarchy', $pageHierarchy);
	}
}

?>
