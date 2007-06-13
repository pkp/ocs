<?php

/**
 * CommentForm.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package rt.ocs.form
 *
 * Form to change metadata information for an RT comment.
 *
 * $Id$
 */

import('form.Form');

class CommentForm extends Form {
	
	/** @var int the ID of the comment */
	var $commentId;

	/** @var boolean Whether or not Captcha support is enabled */
	var $captchaEnabled;

	/** @var int the ID of the paper */
	var $paperId;

	/** @var Comment current comment */
	var $comment;

	/** @var Comment parent comment ID if applicable */
	var $parentId;

	/** @var int Galley view by which the user entered the comments pages */
	var $galleyId;
	
	/**
	 * Constructor.
	 */
	function CommentForm($commentId, $paperId, $galleyId, $parentId = null) {
		parent::Form('comment/comment.tpl');

		$this->paperId = $paperId;

		$commentDao = &DAORegistry::getDAO('CommentDAO');
		$this->comment = &$commentDao->getComment($commentId, $paperId);

		import('captcha.CaptchaManager');
		$captchaManager =& new CaptchaManager();
		$this->captchaEnabled = ($captchaManager->isEnabled() && Config::getVar('captcha', 'captcha_on_comments'))?true:false;

		if (isset($this->comment)) {
			$this->commentId = $commentId;
		}

		$this->parentId = $parentId;
		$this->galleyId = $galleyId;

		if ($this->captchaEnabled) {
			$this->addCheck(new FormValidatorCaptcha($this, 'captcha', 'captchaId', 'common.captchaField.badCaptcha'));
		}
		$this->addCheck(new FormValidatorPost($this));
	}
	
	/**
	 * Initialize form data from current comment.
	 */
	function initData() {
		if (isset($this->comment)) {
			$comment = &$this->comment;
			$this->_data = array(
				'title' => $comment->getTitle(),
				'body' => $comment->getBody(),
				'posterName' => $comment->getPosterName(),
				'posterEmail' => $comment->getPosterEmail()
			);
		} else {
			$this->_data = array();
			$user = Request::getUser();
			if ($user) {
				$this->_data['posterName'] = $user->getFullName();
				$this->_data['posterEmail'] = $user->getEmail();
			}
		}
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$schedConf = Request::getSchedConf();

		$templateMgr = &TemplateManager::getManager();

		if (isset($this->comment)) {
			$templateMgr->assign_by_ref('comment', $this->comment);
			$templateMgr->assign('commentId', $this->commentId);
		}

		$user = Request::getUser();
		if ($user) {
			$templateMgr->assign('userName', $user->getFullName());
			$templateMgr->assign('userEmail', $user->getEmail());
		}

		if ($this->captchaEnabled) {
			import('captcha.CaptchaManager');
			$captchaManager =& new CaptchaManager();
			$captcha =& $captchaManager->createCaptcha();
			if ($captcha) {
				$templateMgr->assign('captchaEnabled', $this->captchaEnabled);
				$this->setData('captchaId', $captcha->getCaptchaId());
			}
		}

		$templateMgr->assign('parentId', $this->parentId);
		$templateMgr->assign('paperId', $this->paperId);
		$templateMgr->assign('galleyId', $this->galleyId);
		$closeCommentsDate = $schedConf->getSetting('closeCommentsDate');
		$commentsClosed = $schedConf->getSetting('closeComments')?true:false && (strtotime($closeCommentsDate < time()));
		$templateMgr->assign('closeCommentsDate', $closeCommentsDate);
		$templateMgr->assign('commentsClosed', $commentsClosed);
		$templateMgr->assign('enableComments', $schedConf->getSetting('enableComments', true));
		$templateMgr->assign('commentsRequireRegistration', $schedConf->getSetting('commentsRequireRegistration', true));
		$templateMgr->assign('commentsAllowAnonymous', $schedConf->getSetting('commentsAllowAnonymous', true));

		parent::display();
	}
	
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$userVars = array(
			'body',
			'title',
			'posterName',
			'posterEmail'
		);
		if ($this->captchaEnabled) {
			$userVars[] = 'captchaId';
			$userVars[] = 'captcha';
		}

		$this->readUserVars($userVars);
	}

	/**
	 * Save changes to comment.
	 * @return int the comment ID
	 */
	function execute() {
		$schedConf = &Request::getSchedConf();
		$enableComments = $schedConf->getSetting('enableComments', true);
		$commentsRequireRegistration = $schedConf->getSetting('commentsRequireRegistration', true);
		$commentsAllowAnonymous = $schedConf->getSetting('commentsAllowAnonymous', true);

		$commentDao = &DAORegistry::getDAO('CommentDAO');
		
		$comment = $this->comment;
		if (!isset($comment)) {
			$comment = &new Comment();
		}

		$user = &Request::getUser();

		$comment->setTitle($this->getData('title'));
		$comment->setBody($this->getData('body'));

		if (($commentsAllowAnonymous || !$commentsRequireRegistration) && (Request::getUserVar('anonymous') || $user == null)) {
			$comment->setPosterName($this->getData('posterName'));
			$comment->setPosterEmail($this->getData('posterEmail'));
			$comment->setUser(null);
		} else {
			$comment->setPosterName($user->getFullName());
			$comment->setPosterEmail($user->getEmail());
			$comment->setUser($user);
		}

		$comment->setParentCommentId($this->parentId);

		if (isset($this->comment)) {
			$commentDao->updateComment($comment);
		} else {
			$comment->setPaperId($this->paperId);
			$comment->setChildCommentCount(0);
			$commentDao->insertComment($comment);
			$this->commentId = $comment->getCommentId();
		}
		
		return $this->commentId;
	}
	
}

?>
