<?php
/**
 * @file classes/security/authorization/OcsConferenceAccessPolicy.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OcsConferenceAccessPolicy
 * @ingroup security_authorization
 *
 * @brief Class to control access to OCS' conference setup components
 */

import('classes.security.authorization.internal.ConferencePolicy');

class OcsConferenceAccessPolicy extends ConferencePolicy {
	/**
	 * Constructor
	 * @param $request PKPRequest
	 * @param $roleAssignments array
	 */
	function OcsConferenceAccessPolicy(&$request, $roleAssignments) {
		parent::ConferencePolicy($request);

		// On conference level we don't have role-specific conditions
		// so we can simply add all role assignments. It's ok if
		// any of these role conditions permits access.
		$conferenceRolePolicy = new PolicySet(COMBINING_PERMIT_OVERRIDES);
		import('lib.pkp.classes.security.authorization.RoleBasedHandlerOperationPolicy');
		foreach($roleAssignments as $role => $operations) {
			$conferenceRolePolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, $role, $operations));
		}
		$this->addPolicy($conferenceRolePolicy);
	}
}

?>
