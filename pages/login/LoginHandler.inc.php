<?php

/**
 * @file LoginHandler.inc.php
 *
 * Copyright (c) 2000-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LoginHandler
 * @ingroup pages_login
 *
 * @brief Handle login/logout requests. 
 */

//$Id$


import('pages.login.PKPLoginHandler');

class LoginHandler extends PKPLoginHandler {
	/**
	 * Constructor
	 **/
	function LoginHandler() {
		parent::PKPLoginHandler();
	}

	/**
	 * Helper Function - set mail from address
	 * @param MailTemplate $mail 
	 */
	function _setMailFrom(&$mail) {
		$site =& Request::getSite();
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		
		// Set the sender to one of three different settings, based on context
		if ($schedConf && $schedConf->getSetting('supportEmail')) {
			$mail->setFrom($schedConf->getSetting('supportEmail'), $schedConf->getSetting('supportName'));
		} elseif ($conference && $conference->getSetting('contactEmail')) {
			$mail->setFrom($conference->getSetting('contactEmail'), $conference->getSetting('contactName'));
		} else {
			$mail->setFrom($site->getLocalizedContactEmail(), $site->getLocalizedContactName());
		}
	}
}

?>
