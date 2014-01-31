<?php

/**
 * @file classes/core/PageRouter.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PageRouter
 * @ingroup core
 *
 * @brief Class providing OCS-specific page routing.
 *
 * FIXME: add cacheable pages
 */

// $Id$


import('core.PKPPageRouter');

class PageRouter extends PKPPageRouter {
	/**
	 * Redirect to user home page (or the role home page if the user has one role).
	 * @param $request PKPRequest the request to be routed
	 */
	function redirectHome(&$request) {
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$user = $request->getUser();
		$userId = $user->getId();

		if ($schedConf =& $this->getContext($request, 2)) {
			// The user is in the sched. conf. context, see if they have one role only
			$roles =& $roleDao->getRolesByUserId($userId, $schedConf->getConferenceId(), $schedConf->getId());
			if(count($roles) == 1) {
				$role = array_shift($roles);
				if ($role->getRoleId() == ROLE_ID_READER) $request->redirect(null, 'index');
				$request->redirect(null, null, $role->getRolePath());
			} else {
				$request->redirect(null, null, 'user');
			}
		} elseif ($conference =& $this->getContext($request, 1)) {
			// The user is in the conference context, see if they have one role only
			$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
			$roles =& $roleDao->getRolesByUserId($userId, $conference->getId());
			if(count($roles) == 1) {
				$role = array_shift($roles);
				$confPath = $conference->getPath();
				$schedConfPath = 'index';

				if ($role->getSchedConfId()) {
					$schedConf = $schedConfDao->getSchedConf($role->getSchedConfId());
					$schedConfPath = $schedConf->getPath();
				}
				if ($role->getRoleId() == ROLE_ID_READER) $request->redirect($confPath, $schedConfPath, 'index');
				$request->redirect($confPath, $schedConfPath, $role->getRolePath());
			} else {
				$request->redirect(null, null,  'user');
			}
		} else {
			// The user is at the site context, check to see if they are
			// only registered in one conf/SchedConf w/ one role
			$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');
			$schedConfDao =& DAORegistry::getDAO('SchedConfDAO');
			$roles = $roleDao->getRolesByUserId($userId);

			if(count($roles) == 1) {
				$role = array_shift($roles);
				$confPath = 'index';
				$schedConfPath = 'index';

				if ($role->getConferenceId()) {
					$conference = $conferenceDao->getConference($role->getConferenceId());
					isset($conference) ? $confPath = $conference->getPath() :
										 $confPath = 'index';
				}
				if ($role->getSchedConfId()) {
					$schedConf = $schedConfDao->getSchedConf($role->getSchedConfId());
					isset($schedConf) ? $schedConfPath = $schedConf->getPath() :
										$schedConfPath = 'index';
				}

				$request->redirect($confPath, $schedConfPath, $role->getRolePath());
			} else $request->redirect('index', 'index', 'user');
		}
	}
}

?>
