<?php

/**
 * @file ManagerSetupHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ManagerSetupHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for conference setup functions. 
 */

//$Id$

import('pages.manager.ManagerHandler');

class ManagerSetupHandler extends ManagerHandler {
	/**
	 * Constructor
	 **/
	function ManagerSetupHandler() {
		parent::ManagerHandler();
	}

	/**
	 * Display conference setup form for the selected step.
	 * Displays setup index page if a valid step is not specified.
	 * @param $args array optional, if set the first parameter is the step to display
	 */
	function setup($args) {
		$this->validate();
		$this->setupTemplate(true);

		$step = isset($args[0]) ? (int) $args[0] : 0;

		if ($step >= 1 && $step <= 6) {

			$formClass = "ConferenceSetupStep{$step}Form";
			import("manager.form.setup.$formClass");

			$setupForm = new $formClass();
			if ($setupForm->isLocaleResubmit()) {
				$setupForm->readInputData();
			} else {
				$setupForm->initData();
			}
			$setupForm->display();

		} else {
			$templateMgr =& TemplateManager::getManager();
			$templateMgr->assign('helpTopicId','conference.generalManagement.websiteManagement');
			$templateMgr->display('manager/setup/index.tpl');
		}
	}

	/**
	 * Save changes to conference settings.
	 * @param $args array first parameter is the step being saved
	 */
	function saveSetup($args) {
		$this->validate();

		$step = isset($args[0]) ? (int) $args[0] : 0;

		if ($step >= 1 && $step <= 6) {

			$this->setupTemplate(true);

			$formClass = "ConferenceSetupStep{$step}Form";
			import("manager.form.setup.$formClass");

			$setupForm = new $formClass();
			$setupForm->readInputData();
			$formLocale = $setupForm->getFormLocale();

			// Check for any special cases before trying to save
			switch ($step) {
				case 1:
					if (Request::getUserVar('addCustomAboutItem')) {
						// Add a custom about item
						$editData = true;
						$customAboutItems = $setupForm->getData('customAboutItems');
						$customAboutItems[$formLocale][] = array();
						$setupForm->setData('customAboutItems', $customAboutItems);

					} else if (($delCustomAboutItem = Request::getUserVar('delCustomAboutItem')) && count($delCustomAboutItem) == 1) {
						// Delete a custom about item
						$editData = true;
						list($delCustomAboutItem) = array_keys($delCustomAboutItem);
						$delCustomAboutItem = (int) $delCustomAboutItem;
						$customAboutItems = $setupForm->getData('customAboutItems');
						if (!isset($customAboutItems[$formLocale])) $customAboutItems[$formLocale][] = array();
						array_splice($customAboutItems[$formLocale], $delCustomAboutItem, 1);
						$setupForm->setData('customAboutItems', $customAboutItems);
					}
					break;
				case 2:
					if (Request::getUserVar('uploadHomepageImage')) {
						if ($setupForm->uploadImage('homepageImage', $formLocale)) {
							$editData = true;
						} else {
							$setupForm->addError('homepageImage', __('manager.setup.homepageImageInvalid'));
						}

					} else if (Request::getUserVar('deleteHomepageImage')) {
						$editData = true;
						$setupForm->deleteImage('homepageImage', $formLocale);
					}
					break;
				case 3:	
					if (Request::getUserVar('uploadHomeHeaderTitleImage')) {
						if ($setupForm->uploadImage('homeHeaderTitleImage', $formLocale)) {
							$editData = true;
						} else {
							$setupForm->addError('homeHeaderTitleImage', __('manager.setup.homeTitleImageInvalid'));
						}

					} else if (Request::getUserVar('deleteHomeHeaderTitleImage')) {
						$editData = true;
						$setupForm->deleteImage('homeHeaderTitleImage', $formLocale);

					} else if (Request::getUserVar('uploadHomeHeaderLogoImage')) {
						if ($setupForm->uploadImage('homeHeaderLogoImage', $formLocale)) {
							$editData = true;
						} else {
							$setupForm->addError('homeHeaderLogoImage', __('manager.setup.homeHeaderImageInvalid'));
						}

					} else if (Request::getUserVar('deleteHomeHeaderLogoImage')) {
						$editData = true;
						$setupForm->deleteImage('homeHeaderLogoImage', $formLocale);

					} else if (Request::getUserVar('uploadConferenceFavicon')) {
						if ($setupForm->uploadImage('conferenceFavicon', $formLocale)) {
							$editData = true;
						} else {
							$setupForm->addError('conferenceFavicon', __('manager.setup.layout.faviconInvalid'));
						}

					} else if (Request::getUserVar('deleteConferenceFavicon')) {
						$editData = true;
						$setupForm->deleteImage('conferenceFavicon', $formLocale);

 					} else if (Request::getUserVar('uploadPageHeaderTitleImage')) {
						if ($setupForm->uploadImage('pageHeaderTitleImage', $formLocale)) {
							$editData = true;
						} else {
							$setupForm->addError('pageHeaderTitleImage', __('manager.setup.pageHeaderTitleImageInvalid'));
						}

					} else if (Request::getUserVar('deletePageHeaderTitleImage')) {
						$editData = true;
						$setupForm->deleteImage('pageHeaderTitleImage', $formLocale);

					} else if (Request::getUserVar('uploadPageHeaderLogoImage')) {
						if ($setupForm->uploadImage('pageHeaderLogoImage', $formLocale)) {
							$editData = true;
						} else {
							$setupForm->addError('pageHeaderLogoImage', __('manager.setup.pageHeaderLogoImageInvalid'));
						}

					} else if (Request::getUserVar('deletePageHeaderLogoImage')) {
						$editData = true;
						$setupForm->deleteImage('pageHeaderLogoImage', $formLocale);

					} else if (Request::getUserVar('addNavItem')) {
						// Add a navigation bar item
						$editData = true;
						$navItems = $setupForm->getData('navItems');
						$navItems[$formLocale][] = array();
						$setupForm->setData('navItems', $navItems);

					} else if (($delNavItem = Request::getUserVar('delNavItem')) && count($delNavItem) == 1) {
						// Delete a  navigation bar item
						$editData = true;
						list($delNavItem) = array_keys($delNavItem);
						$delNavItem = (int) $delNavItem;
						$navItems = $setupForm->getData('navItems');
						if (is_array($navItems) && is_array($navItems[$formLocale])) {
							array_splice($navItems[$formLocale], $delNavItem, 1);
							$setupForm->setData('navItems', $navItems);
						}
					}
					break;
				case '4':
					if (Request::getUserVar('uploadConferenceStyleSheet')) {
						if ($setupForm->uploadStyleSheet('conferenceStyleSheet')) {
							$editData = true;
						} else {
							$setupForm->addError('conferenceStyleSheet', __('manager.setup.conferenceStyleSheetInvalid'));
						}
					} else if (Request::getUserVar('deleteConferenceStyleSheet')) {
						$editData = true;
						$setupForm->deleteImage('conferenceStyleSheet');
					}
					break;
			}

			if (!isset($editData) && $setupForm->validate()) {
				$setupForm->execute();
				Request::redirect(null, null, null, 'setupSaved', $step);
			} else {
				$setupForm->display();
			}

		} else {
			Request::redirect();
		}
	}

	/**
	 * Display the "settings saved" page
	 */
	function setupSaved($args) {
		$this->validate();

		$step = isset($args[0]) ? (int) $args[0] : 0;

		if ($step >= 1 && $step <= 6) {
			$this->setupTemplate(true);
			$templateMgr =& TemplateManager::getManager();
			$templateMgr->assign('setupStep', $step);
			$templateMgr->assign('helpTopicId', 'conference.generalManagement.websiteManagement');

			if($step == 6) {
				$conference =& Request::getConference();
				$templateMgr->assign('showSetupHints',true);
			}
			$templateMgr->display('manager/setup/settingsSaved.tpl');
		} else {
			Request::redirect(null, null, 'index');
		}
	}
}

?>
