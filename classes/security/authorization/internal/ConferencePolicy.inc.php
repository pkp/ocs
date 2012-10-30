<?php
/**
 * @file classes/security/authorization/internal/ConferencePolicy.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ConferencePolicy
 * @ingroup security_authorization_internal
 *
 * @brief Policy that ensures availability of an OJS conference in
 *  the request context
 */

import('lib.pkp.classes.security.authorization.PolicySet');

class ConferencePolicy extends PolicySet {
	/**
	 * Constructor
	 * @param $request PKPRequest
	 */
	function ConferencePolicy(&$request) {
		parent::PolicySet();

		// Ensure that we have a conference in the context.
		import('lib.pkp.classes.security.authorization.ContextRequiredPolicy');
		$this->addPolicy(new ContextRequiredPolicy($request, 'user.authorization.noConference'));
	}
}

?>
