<?php
/**
 * @file classes/handler/HandlerValidatorConference.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class HandlerValidatorConference
 * @ingroup handler_validation
 *
 * @brief Class to validate that a conference exists
 */

import('handler.validation.HandlerValidator');

class HandlerValidatorConference extends HandlerValidator {
	/**
	 * Constructor.
	 * @param $handler Handler the associated form
	 * @param $redirectToLogin bool Send to login screen on validation fail if true
	 * @param $message string the error message for validation failures (i18n key)
	 * @param $additionalArgs Array URL arguments to include in request
	 */
	function HandlerValidatorConference(&$handler, $redirectToLogin = false, $message = null, $additionalArgs = array()) {
		parent::HandlerValidator($handler, $redirectToLogin, $message, $additionalArgs);
	}

	/**
	 * Check if field value is valid.
	 * Value is valid if it is empty and optional or validated by user-supplied function.
	 * @return boolean
	 */
	function isValid() {
		$conference =& Request::getConference();
		return ($conference)?true:false;
	}
}

?>
