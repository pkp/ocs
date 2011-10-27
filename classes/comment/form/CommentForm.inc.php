<?php

/**
 * @defgroup rt_ocs_form
 */

/**
 * @file CommentForm.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CommentForm
 * @ingroup rc_ocs_form
 *
 * @brief Form to change metadata information for an RT comment.
 *
 */

// $Id$


import('lib.pkp.classes.form.Form');

class CommentForm extends Form {

	/** @var int the ID of the comment */
	var $commentId;

	/** @var boolean Whether or not Captcha support is enabled */
	var $captchaEnabled;

	/** @var boolean whether to use reCaptcha or the default captcha */
	var $reCaptchaEnabled;

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

		$commentDao =& DAORegistry::getDAO('CommentDAO');
		$this->comment =& $commentDao->getById($commentId, $paperId);

		import('lib.pkp.classes.captcha.CaptchaManager');
		$captchaManager = new CaptchaManager();
		$this->captchaEnabled = ($captchaManager->isEnabled() && Config::getVar('captcha', 'captcha_on_comments'))?true:false;
		if ($this->captchaEnabled) {
			$this->reCaptchaEnabled = Config::getVar('captcha', 'recaptcha')?true:false;
		}

		if (isset($this->comment)) {
			$this->commentId = $commentId;
		}

		$this->parentId = $parentId;
		$this->galleyId = $galleyId;

		$this->addCheck(new FormValidator($this, 'title', 'required', 'comments.titleRequired'));
		if ($this->captchaEnabled) {
			if ($this->reCaptchaEnabled) {
				$this->addCheck(new FormValidatorReCaptcha($this, 'recaptcha_challenge_field', 'recaptcha_response_field', Request::getRemoteAddr(), 'common.captchaField.badCaptcha'));
			} else {
				$this->addCheck(new FormValidatorCaptcha($this, 'captcha', 'captchaId', 'common.captchaField.badCaptcha'));
			}
		}
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Initialize form data from current comment.
	 */
	function initData() {
		if (isset($this->comment)) {
			$comment =& $this->comment;
			$this->_data = array(
				'title' => $comment->getTitle(),
				'body' => $comment->getBody(),
				'posterName' => $comment->getPosterName(),
				'posterEmail' => $comment->getPosterEmail()
			);
		} else {
			$commentDao =& DAORegistry::getDAO('CommentDAO');
			$comment =& $commentDao->getById($this->parentId, $this->paperId);
			$this->_data = array();
			$user = Request::getUser();
			if ($user) {
				$this->_data['posterName'] = $user->getFullName();
				$this->_data['posterEmail'] = $user->getEmail();
				$this->_data['title'] = ($comment?__('common.re') . ' ' . $comment->getTitle():'');
			}
		}
	}

	/**
	 * Display the form.
	 */
	function display() {
		$conference = Request::getConference();
		$schedConf = Request::getSchedConf();

		$templateMgr =& TemplateManager::getManager();

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
			$templateMgr->assign('reCaptchaEnabled', $this->reCaptchaEnabled);
			if ($this->reCaptchaEnabled) {
				import('lib.pkp.lib.recaptcha.recaptchalib');
				$publicKey = Config::getVar('captcha', 'recaptcha_public_key');
				$useSSL = Config::getVar('security', 'force_ssl')?true:false;
				$reCaptchaHtml = recaptcha_get_html($publicKey, null, $useSSL);
				$templateMgr->assign('reCaptchaHtml', $reCaptchaHtml);
				$templateMgr->assign('captchaEnabled', $this->captchaEnabled);
			} else {
				import('lib.pkp.classes.captcha.CaptchaManager');
				$captchaManager = new CaptchaManager();
				$captcha =& $captchaManager->createCaptcha();
				if ($captcha) {
					$templateMgr->assign('captchaEnabled', $this->captchaEnabled);
					$this->setData('captchaId', $captcha->getId());
				}
			}
		}

		$templateMgr->assign('parentId', $this->parentId);
		$templateMgr->assign('paperId', $this->paperId);
		$templateMgr->assign('galleyId', $this->galleyId);
		$closeCommentsDate = $schedConf->getSetting('closeCommentsDate');
		$commentsClosed = $schedConf->getSetting('closeComments')?true:false && (strtotime($closeCommentsDate < time()));
		$templateMgr->assign('closeCommentsDate', $closeCommentsDate);
		$templateMgr->assign('commentsClosed', $commentsClosed);
		$templateMgr->assign('enableComments', $conference->getSetting('enableComments'));
		$templateMgr->assign('commentsRequireRegistration', $conference->getSetting('commentsRequireRegistration'));
		$templateMgr->assign('commentsAllowAnonymous', $conference->getSetting('commentsAllowAnonymous'));

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
			if ($this->reCaptchaEnabled) {
				$userVars[] = 'recaptcha_challenge_field';
				$userVars[] = 'recaptcha_response_field';
			} else {
				$userVars[] = 'captchaId';
				$userVars[] = 'captcha';
			}
		}

		$this->readUserVars($userVars);
	}

	/**
	 * Save changes to comment.
	 * @return int the comment ID
	 */
	function execute() {
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$enableComments = $conference->getSetting('enableComments');
		$commentsRequireRegistration = $conference->getSetting('commentsRequireRegistration');
		$commentsAllowAnonymous = $conference->getSetting('commentsAllowAnonymous');

		$commentDao =& DAORegistry::getDAO('CommentDAO');

		$comment = $this->comment;
		if (!isset($comment)) {
			$comment = new Comment();
		}

		$user =& Request::getUser();

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
			$comment->setSubmissionId($this->paperId);
			$comment->setChildCommentCount(0);
			$commentDao->insertComment($comment);
			$this->commentId = $comment->getId();
		}

		return $this->commentId;
	}

}

?>
