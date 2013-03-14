<?php

/**
 * @file LoginHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LoginHandler
 * @ingroup pages_login
 *
 * @brief Handle login/logout requests. 
 */



import('lib.pkp.pages.login.PKPLoginHandler');

class LoginHandler extends PKPLoginHandler {
	/**
	 * Sign in as another user.
	 * @param $args array ($userId)
	 * @param $request PKPRequest
	 */
	function signInAsUser($args, &$request) {
		$this->addCheck(new HandlerValidatorConference($this));		
		$this->addCheck(new HandlerValidatorRoles($this, true, null, null, array(ROLE_ID_SITE_ADMIN, ROLE_ID_MANAGER)));
		$this->validate();

		if (isset($args[0]) && !empty($args[0])) {
			$userId = (int)$args[0];
			$conference =& $request->getConference();

			if (!Validation::canAdminister($conference->getId(), $userId)) {
				$this->setupTemplate($request);
				// We don't have administrative rights
				// over this user. Display an error.
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->assign('pageTitle', 'manager.people');
				$templateMgr->assign('errorMsg', 'manager.people.noAdministrativeRights');
				$templateMgr->assign('backLink', $request->url(null, null, null, 'people', 'all'));
				$templateMgr->assign('backLinkLabel', 'manager.people.allUsers');
				return $templateMgr->display('common/error.tpl');
			}

			$userDao = DAORegistry::getDAO('UserDAO');
			$newUser =& $userDao->getById($userId);
			$session =& $request->getSession();

			// FIXME Support "stack" of signed-in-as user IDs?
			if (isset($newUser) && $session->getUserId() != $newUser->getId()) {
				$session->setSessionVar('signedInAs', $session->getUserId());
				$session->setSessionVar('userId', $userId);
				$session->setUserId($userId);
				$session->setSessionVar('username', $newUser->getUsername());
				$request->redirect(null, null, 'user');
			}
		}
		$request->redirect(null, null, $request->getRequestedPage());
	}

	/**
	 * Restore original user account after signing in as a user.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function signOutAsUser($args, &$request) {
		$this->validate();

		$session =& $request->getSession();
		$signedInAs = $session->getSessionVar('signedInAs');

		if (isset($signedInAs) && !empty($signedInAs)) {
			$signedInAs = (int)$signedInAs;

			$userDao = DAORegistry::getDAO('UserDAO');
			$oldUser =& $userDao->getById($signedInAs);

			$session->unsetSessionVar('signedInAs');

			if (isset($oldUser)) {
				$session->setSessionVar('userId', $signedInAs);
				$session->setUserId($signedInAs);
				$session->setSessionVar('username', $oldUser->getUsername());
			}
		}

		$request->redirect(null, 'user');
	}

	/**
	 * Get the log in URL.
	 * @param $request PKPRequest
	 */
	function _getLoginUrl($request) {
		return $request->url(null, null, 'login', 'signIn');
	}

	/**
	 * Helper Function - set mail from address
	 * @param $request PKPRequest
	 * @param $mail MailTemplate
	 */
	function _setMailFrom($request, &$mail) {
		$site =& $request->getSite();
		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();
		
		// Set the sender to one of three different settings, based on context
		if ($schedConf && $schedConf->getSetting('supportEmail')) {
			$mail->setFrom($schedConf->getSetting('supportEmail'), $schedConf->getSetting('supportName'));
		} elseif ($conference && $conference->getSetting('contactEmail')) {
			$mail->setFrom($conference->getSetting('contactEmail'), $conference->getSetting('contactName'));
		} else {
			$mail->setFrom($site->getLocalizedContactEmail(), $site->getLocalizedContactName());
		}
	}

	/**
	 * Configure the template for display.
	 */
	function setupTemplate($request) {
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_MANAGER, LOCALE_COMPONENT_PKP_MANAGER);
		parent::setupTemplate($request);
	}
}

?>
