<?php

/**
 * @file ChangePasswordForm.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ChangePasswordForm
 * @ingroup user_form
 *
 * @brief Form to change a user's password.
 */

//$Id$

import('form.Form');

class ChangePasswordForm extends Form {

	/**
	 * Constructor.
	 */
	function ChangePasswordForm() {
		parent::Form('user/changePassword.tpl');
		$user =& Request::getUser();
		$site =& Request::getSite();

		// Validation checks for this form
		$this->addCheck(new FormValidatorCustom($this, 'oldPassword', 'required', 'user.profile.form.oldPasswordInvalid', create_function('$password,$username', 'return Validation::checkCredentials($username,$password);'), array($user->getUsername())));
		$this->addCheck(new FormValidatorLength($this, 'password', 'required', 'user.account.form.passwordLengthTooShort', '>=', $site->getMinPasswordLength()));
		$this->addCheck(new FormValidator($this, 'password', 'required', 'user.profile.form.newPasswordRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'password', 'required', 'user.account.form.passwordsDoNotMatch', create_function('$password,$form', 'return $password == $form->getData(\'password2\');'), array(&$this)));
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Display the form.
	 */
	function display() {
		$user =& Request::getUser();
		$templateMgr =& TemplateManager::getManager();
		$site =& Request::getSite();
		$templateMgr->assign('minPasswordLength', $site->getMinPasswordLength());
		$templateMgr->assign('username', $user->getUsername());
		parent::display();
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('oldPassword', 'password', 'password2'));
	}

	/**
	 * Save new password.
	 */
	function execute() {
		$user =& Request::getUser();

		if ($user->getAuthId()) {
			$authDao =& DAORegistry::getDAO('AuthSourceDAO');
			$auth =& $authDao->getPlugin($user->getAuthId());
		}

		if (isset($auth)) {
			$auth->doSetUserPassword($user->getUsername(), $this->getData('password'));
			$user->setPassword(Validation::encryptCredentials($user->getId(), Validation::generatePassword())); // Used for PW reset hash only
		} else {
			$user->setPassword(Validation::encryptCredentials($user->getUsername(), $this->getData('password')));
		}

		$userDao =& DAORegistry::getDAO('UserDAO');
		$userDao->updateObject($user);
	}
}

?>
