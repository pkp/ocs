<?php
/**
 * @file classes/handler/HandlerValidatorConference.inc.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class HandlerValidator
 * @ingroup security
 *
 * @brief Class to represent a page validation check.
 */

import('handler.validation.HandlerValidator');

class HandlerValidatorSchedConf extends HandlerValidator {
	/**
	 * Constructor.
	 * @param $handler Handler the associated form
	 */
	 
	function HandlerValidatorSchedConf(&$handler) {
		parent::HandlerValidator($handler);
	}

	/**
	 * Check if field value is valid.
	 * Value is valid if it is empty and optional or validated by user-supplied function.
	 * @return boolean
	 */
	function isValid() {
		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		
		if ( !$conference || !$schedConf ) return false;
		return true;
	}
}

?>
