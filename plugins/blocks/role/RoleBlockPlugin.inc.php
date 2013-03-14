<?php

/**
 * @file RoleBlockPlugin.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RoleBlockPlugin
 * @ingroup plugins_blocks_role
 *
 * @brief Class for role block plugin
 */


import('lib.pkp.classes.plugins.BlockPlugin');

class RoleBlockPlugin extends BlockPlugin {
	/**
	 * Install default settings on conference creation.
	 * @return string
	 */
	function getContextSpecificPluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * Get the display name of this plugin.
	 * @return String
	 */
	function getDisplayName() {
		return __('plugins.block.role.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return __('plugins.block.role.description');
	}

	/**
	 * Override the block contents based on the current role being
	 * browsed.
	 * @return string
	 */
	function getBlockTemplateFilename() {
		$request =& $this->getRequest();
		$conference =& $request->getConference();
		$schedConf =& $request->getSchedConf();
		$user =& $request->getUser();
		if (!$conference || !$schedConf || !$user) return null;

		$userId = $user->getId();
		$conferenceId = $conference->getId();
		$schedConfId = $schedConf->getId();

		$templateMgr =& TemplateManager::getManager();

		switch ($request->getRequestedPage()) {
			case 'author': switch ($request->getRequestedOp()) {
				case 'submit':
				case 'saveSubmit':
				case 'submitSuppFile':
				case 'saveSubmitSuppFile':
				case 'deleteSubmitSuppFile':
					// Block disabled for submission
					return null;
				default:
					$authorSubmissionDao = DAORegistry::getDAO('AuthorSubmissionDAO');
					$submissionsCount = $authorSubmissionDao->getSubmissionsCount($userId, $schedConfId);
					$templateMgr->assign('submissionsCount', $submissionsCount);
					return 'author.tpl';
			}
			case 'director':
				if ($request->getRequestedOp() == 'index') return null;
				$directorSubmissionDao = DAORegistry::getDAO('DirectorSubmissionDAO');
				$submissionsCount =& $directorSubmissionDao->getDirectorSubmissionsCount($schedConfId);
				$templateMgr->assign('submissionsCount', $submissionsCount);
				return 'director.tpl';
			case 'trackDirector':
				$trackDirectorSubmissionDao = DAORegistry::getDAO('TrackDirectorSubmissionDAO');
				$submissionsCount =& $trackDirectorSubmissionDao->getTrackDirectorSubmissionsCount($userId, $schedConfId);
				$templateMgr->assign('submissionsCount', $submissionsCount);
				return 'trackDirector.tpl';
			case 'reviewer':
				$reviewerSubmissionDao = DAORegistry::getDAO('ReviewerSubmissionDAO');
				$submissionsCount = $reviewerSubmissionDao->getSubmissionsCount($userId, $schedConfId);
				$templateMgr->assign('submissionsCount', $submissionsCount);
				return 'reviewer.tpl';
		}
		return null;
	}
}

?>
