<?php

/**
 * @file TinyMCEPlugin.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TinyMCEPlugin
 * @ingroup plugins_generic_tinymce
 *
 * @brief TinyMCE WYSIWYG plugin for textareas - to allow cross-browser HTML editing
 */

//$Id$

import('classes.plugins.GenericPlugin');

define('TINYMCE_INSTALL_PATH', 'lib' . DIRECTORY_SEPARATOR . 'pkp' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'tinymce');
define('TINYMCE_JS_PATH', TINYMCE_INSTALL_PATH . DIRECTORY_SEPARATOR . 'jscripts' . DIRECTORY_SEPARATOR . 'tiny_mce');

class TinyMCEPlugin extends GenericPlugin {
	/**
	 * Register the plugin, if enabled; note that this plugin
	 * runs under both Conference and Site contexts.
	 * @param $category string
	 * @param $path string
	 * @return boolean
	 */
	function register($category, $path) {
		if (parent::register($category, $path)) {
			$this->addLocaleData();
			if ($this->isMCEInstalled() && $this->getEnabled()) {
				HookRegistry::register('TemplateManager::display',array(&$this, 'callback'));
			}
			return true;
		}
		return false;
	}

	/**
	 * Get the name of the settings file to be installed on new conference
	 * creation.
	 * @return string
	 */
	function getNewConferencePluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * Get the name of the settings file to be installed site-wide when
	 * OCS is installed.
	 * @return string
	 */
	function getInstallSitePluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * Given a $page and $op, return a list of field names for which
	 * the plugin should be used.
	 * @param $templateMgr object
	 * @param $page string The requested page
	 * @param $op string The requested operation
	 * @return array
	 */
	function getEnableFields(&$templateMgr, $page, $op) {
		$formLocale = $templateMgr->get_template_vars('formLocale');
		$fields = array();
		switch ("$page/$op") {
			case 'admin/settings':
			case 'admin/saveSettings':
				$fields[] = 'intro';
				$fields[] = 'aboutField';
				break;
			case 'admin/createConference':
			case 'admin/updateConference':
			case 'admin/editConference':
				$fields[] = 'description';
				break;
			case 'author/submit':
			case 'author/saveSubmit':
				switch (array_shift(Request::getRequestedArgs())) {
					case 1: $fields[] = 'commentsToDirector'; break;
					case 3:
						$count = max(1, count($templateMgr->get_template_vars('authors')));
						for ($i=0; $i<$count; $i++) {
							$fields[] = "authors-$i-affiliation";
							$fields[] = "authors-$i-biography";
						}
						$fields[] = 'abstract';
						$fields[] = 'citations';
						break;
				}
				break;
			case 'author/submitSuppFile': $fields[] = 'description'; break;
			case 'manager/createAnnouncement':
			case 'manager/editAnnouncement':
			case 'manager/updateAnnouncement':
				$fields[] = 'descriptionShort';
				$fields[] = 'description';
				break;
			case 'user/profile':
			case 'user/account':
			case 'user/saveProfile':
			case 'manager/createUser':
			case 'manager/updateUser':
				$fields[] = 'mailingAddress';
				$fields[] = 'biography';
				break;
			case 'manager/editReviewForm':
			case 'manager/updateReviewForm':
			case 'manager/createReviewForm':
				$fields[] = 'description';
				break;
			case 'manager/editReviewFormElement':
			case 'manager/createReviewFormElement':
			case 'manager/updateReviewFormElement':
				$fields[] = 'question';
				break;
			case 'manager/editTrack':
			case 'manager/updateTrack':
			case 'manager/createTrack':
				$fields[] = 'policy';
				break;
			case 'manager/setup':
			case 'manager/saveSetup':
				switch (array_shift(Request::getRequestedArgs())) {
					case 1:
						$fields[] = 'description';
						$fields[] = 'contactMailingAddress';
						$fields[] = 'copyrightNotice';
						$fields[] = 'archiveAccessPolicy';
						$fields[] = 'privacyStatement';
						$customAboutItems = $templateMgr->get_template_vars('customAboutItems');
						$count = max(1, isset($customAboutItems[$formLocale])?count($customAboutItems[$formLocale]):0);
						for ($i=0; $i<$count; $i++) {
							$fields[] = "customAboutItems-$i-content";
						}
						break;
					case 2:
						$fields[] = 'additionalHomeContent';
						$fields[] = 'readerInformation';
						$fields[] = 'authorInformation';
						$fields[] = 'announcementsIntroduction';

						break;
					case 3:
						$fields[] = 'conferencePageHeader';
						$fields[] = 'conferencePageFooter';
						break;
				}
				break;
			case 'manager/schedConfSetup':
			case 'manager/saveSchedConfSetup':
				switch (array_shift(Request::getRequestedArgs())) {
					case 1:
						$fields[] = 'introduction';
						$fields[] = 'overview';
						$fields[] = 'locationAddress';
						$fields[] = 'contactMailingAddress';
						$fields[] = 'sponsorNote';
						$fields[] = 'contributorNote';
						$count = max(1, count($templateMgr->get_template_vars('sponsors')));
						for ($i=0; $i<$count; $i++) {
							$fields[] = "sponsors-$i-address";
						}
						break;
					case 2:
						$fields[] = 'cfpMessage';
						$fields[] = 'authorGuidelines';
						$submissionChecklist = $templateMgr->get_template_vars('submissionChecklist');
						$count = max(1, isset($submissionChecklist[$formLocale])?count($submissionChecklist[$formLocale]):0);
						for ($i=0; $i<$count; $i++) {
							$fields[] = "submissionChecklist-$i";
						}
						break;
					case 3:
						$fields[] = 'reviewPolicy';
						$fields[] = 'reviewGuidelines';
						break;
				}
			case 'manager/program':
			case 'manager/saveProgramSettings':
				$fields[] = 'program';
			case 'manager/accommodation':
			case 'manager/saveAccommodationSettings':
				$fields[] = 'accommodationDescription';
				break;
			case 'manager/createBuilding':
			case 'manager/editBuilding':
			case 'manager/updateBuilding':
				$fields[] = 'description';
				break;
			case 'manager/createRoom':
			case 'manager/editRoom':
			case 'manager/updateRoom':
				$fields[] = 'description';
				break;
			case 'manager/createSpecialEvent':
			case 'manager/editSpecialEvent':
			case 'manager/updateSpecialEvent':
				$fields[] = 'description';
				break;
			case 'rtadmin/editContext':
			case 'rtadmin/editSearch':
			case 'rtadmin/editVersion':
			case 'rtadmin/createContext':
			case 'rtadmin/createSearch':
			case 'rtadmin/createVersion':
				$fields[] = 'description';
				break;
			case 'director/viewDirectorDecisionComments':
			case 'director/postDirectorDecisionComment':
			case 'director/editComment':
			case 'director/saveComment':
			case 'trackDirector/viewDirectorDecisionComments':
			case 'trackDirector/postDirectorDecisionComment':
			case 'trackDirector/editComment':
			case 'trackDirector/saveComment':
			case 'reviewer/viewPeerReviewComments':
			case 'reviewer/postPeerReviewComment':
			case 'reviewer/editComment':
			case 'reviewer/saveComment':
				$fields[] = 'comments';
				$fields[] = 'authorComments';
				break;
			case 'director/viewDirectorDecisionComments':
			case 'director/postDirectorDecisionComment':
				$fields[] = 'comments';
				break;
			case 'director/createReviewer':
			case 'trackDirector/createReviewer':
				$fields[] = 'mailingAddress';
				$fields[] = 'biography';
				break;
			case 'reviewer/viewPeerReviewComments':
			case 'reviewer/postPeerReviewComment':
				$fields[] = 'presenterComments';
				$fields[] = 'comments';
				break;
			case 'director/submissionNotes':
			case 'trackDirector/submissionNotes':
				$fields[] = 'note';
				break;
			case 'author/viewMetadata':
			case 'trackDirector/viewMetadata':
			case 'director/viewMetadata':
			case 'author/saveMetadata':
			case 'trackDirector/saveMetadata':
			case 'director/saveMetadata':
				$count = max(1, count($templateMgr->get_template_vars('authors')));
				for ($i=0; $i<$count; $i++) {
					$fields[] = "authors-$i-affiliation";
					$fields[] = "authors-$i-biography";
				}
				$fields[] = 'abstract';
				$fields[] = 'citations';
				break;
			case 'trackDirector/editSuppFile':
			case 'director/editSuppFile':
			case 'trackDirector/saveSuppFile':
			case 'director/saveSuppFile':
				$fields[] = 'description';
				break;
			case 'manager/registrationPolicies':
				$fields[] = 'registrationMailingAddress';
				$fields[] = 'registrationAdditionalInformation';
				$fields[] = 'delayedOpenAccessPolicy';
				$fields[] = 'authorSelfArchivePolicy';
				break;
			case 'manager/editRegistrationType':
			case 'manager/createRegistrationType':
			case 'manager/updateRegistrationType':
				$fields[] = 'description';
				break;
			case 'comment/add':
				$fields[] = 'bodyField';
				break;

		}
		HookRegistry::call('TinyMCEPlugin::getEnableFields', array(&$this, &$fields));
		return $fields;
	}

	/**
	 * Hook callback function for TemplateManager::display
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function callback($hookName, $args) {
		$templateManager =& $args[0];

		$page = Request::getRequestedPage();
		$op = Request::getRequestedOp();
		$enableFields = $this->getEnableFields($templateManager, $page, $op);

		if (!empty($enableFields)) {
			$baseUrl = $templateManager->get_template_vars('baseUrl');
			$additionalHeadData = $templateManager->get_template_vars('additionalHeadData');
			$enableFields = join(',', $enableFields);
			$allLocales = AppLocale::getAllLocales();
			$localeList = array();
			foreach ($allLocales as $key => $locale) {
				$localeList[] = String::substr($key, 0, 2);
			}

			$tinymceScript = '
			<script language="javascript" type="text/javascript" src="'.$baseUrl.'/'.TINYMCE_JS_PATH.'/tiny_mce_gzip.js"></script>
			<script language="javascript" type="text/javascript">
				tinyMCE_GZ.init({
					relative_urls : "false",
					plugins : "paste,fullscreen,jbimages",
					themes : "advanced",
					languages : "' . join(',', $localeList) . '",
					disk_cache : true
				});
			</script>
			<script language="javascript" type="text/javascript">
				tinyMCE.init({
					entity_encoding : "raw",
					plugins : "paste,fullscreen,jbimages",
					mode : "exact",
					language : "' . String::substr(AppLocale::getLocale(), 0, 2) . '",
					elements : "' . $enableFields . '",
					relative_urls : false,
					forced_root_block : false,
					paste_auto_cleanup_on_paste : true,
					apply_source_formatting : false,
					theme : "advanced",
					theme_advanced_buttons1 : "cut,copy,paste,|,bold,italic,underline,bullist,numlist,|,link,unlink,help,code,fullscreen,jbimages",
					theme_advanced_buttons2 : "",
					theme_advanced_buttons3 : ""
				});
			</script>';

			$templateManager->assign('additionalHeadData', $additionalHeadData."\n".$tinymceScript);
		}
		return false;
	}

	/**
	 * Get the symbolic name of this plugin
	 * @return string
	 */
	function getName() {
		return 'TinyMCEPlugin';
	}

	/**
	 * Get the display name of this plugin
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.generic.tinymce.name');
	}

	/**
	 * Get the description of this plugin
	 * @return string
	 */
	function getDescription() {
		if ($this->isMCEInstalled()) return __('plugins.generic.tinymce.description');
		return __('plugins.generic.tinymce.descriptionDisabled', array('tinyMcePath' => TINYMCE_INSTALL_PATH));
	}

	/**
	 * Check whether or not the TinyMCE library is installed
	 * @return boolean
	 */
	function isMCEInstalled() {
		return file_exists(TINYMCE_JS_PATH . '/tiny_mce.js');
	}

	/**
	 * Check whether or not this plugin is enabled
	 * @return boolean
	 */
	function getEnabled() {
		$conference =& Request::getConference();
		$conferenceId = $conference?$conference->getId():0;
		return $this->getSetting($conferenceId, 0, 'enabled');
	}

	/**
	 * Get a list of available management verbs for this plugin
	 * @return array
	 */
	function getManagementVerbs() {
		$verbs = array();
		if ($this->isMCEInstalled()) $verbs[] = array(
			($this->getEnabled()?'disable':'enable'),
			__($this->getEnabled()?'manager.plugins.disable':'manager.plugins.enable')
		);
		return $verbs;
	}

	/**
	 * Execute a management verb on this plugin
	 * @param $verb string
	 * @param $args array
	 * @param $message string Location for the plugin to put a result msg
	 * @return boolean
	 */
	function manage($verb, $args, &$message) {
		$conference =& Request::getConference();
		$conferenceId = $conference?$conference->getId():0;
		switch ($verb) {
			case 'enable':
				$this->updateSetting($conferenceId, 0, 'enabled', true);
				$message = __('plugins.generic.tinymce.enabled');
				break;
			case 'disable':
				$this->updateSetting($conferenceId, 0, 'enabled', false);
				$message = __('plugins.generic.tinymce.disabled');
				break;
		}
		return false;
	}
}

?>
