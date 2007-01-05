<?php

/**
 * EventDirectorSetupHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.director
 *
 * Handle requests for conference setup functions. 
 *
 * $Id$
 */

class EventDirectorSetupHandler extends EventDirectorHandler {
	
	/**
	 * Display conference setup form for the selected step.
	 * Displays setup index page if a valid step is not specified.
	 * @param $args array optional, if set the first parameter is the step to display
	 */
	function setup($args) {
		parent::validate();
		parent::setupTemplate(true);
		
		$step = isset($args[0]) ? (int) $args[0] : 0;
		
		if ($step >= 1 && $step <= 6) {
			
			$formClass = "EventSetupStep{$step}Form";
			import("eventDirector.form.setup.$formClass");
			
			$setupForm = &new $formClass();
			$setupForm->initData();
			$setupForm->display();
		
		} else {
			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign('helpTopicId','conference.managementPages.setup');
			$templateMgr->display('eventDirector/setup/index.tpl');
		}
	}
	
	/**
	 * Save changes to conference settings.
	 * @param $args array first parameter is the step being saved
	 */
	function saveSetup($args) {
		parent::validate();
		
		$step = isset($args[0]) ? (int) $args[0] : 0;
		
		if ($step >= 1 && $step <= 6) {

			parent::setupTemplate(true);
			
			$formClass = "EventSetupStep{$step}Form";
			import("eventDirector.form.setup.$formClass");
			
			$setupForm = &new $formClass();
			$setupForm->readInputData();
			
			// Check for any special cases before trying to save
			switch ($step) {
				case 1:
					if (Request::getUserVar('addSponsor')) {
						// Add a sponsor
						$editData = true;
						$sponsors = $setupForm->getData('sponsors');
						array_push($sponsors, array());
						$setupForm->setData('sponsors', $sponsors);
						
					} else if (($delSponsor = Request::getUserVar('delSponsor')) && count($delSponsor) == 1) {
						// Delete a sponsor
						$editData = true;
						list($delSponsor) = array_keys($delSponsor);
						$delSponsor = (int) $delSponsor;
						$sponsors = $setupForm->getData('sponsors');
						array_splice($sponsors, $delSponsor, 1);
						$setupForm->setData('sponsors', $sponsors);
						
					} else if (Request::getUserVar('addContributor')) {
						// Add a contributor
						$editData = true;
						$contributors = $setupForm->getData('contributors');
						array_push($contributors, array());
						$setupForm->setData('contributors', $contributors);
						
					} else if (($delContributor = Request::getUserVar('delContributor')) && count($delContributor) == 1) {
						// Delete a contributor
						$editData = true;
						list($delContributor) = array_keys($delContributor);
						$delContributor = (int) $delContributor;
						$contributors = $setupForm->getData('contributors');
						array_splice($contributors, $delContributor, 1);
						$setupForm->setData('contributors', $contributors);
					}
					break;
				case 5:	
					if (Request::getUserVar('uploadHomeHeaderTitleImage')) {
						if ($setupForm->uploadImage('homeHeaderTitleImage')) {
							$editData = true;
						} else {
							$setupForm->addError('homeHeaderTitleImage', 'director.setup.homeTitleImageInvalid');
						}

					} else if (Request::getUserVar('deleteHomeHeaderTitleImage')) {
						$editData = true;
						$setupForm->deleteImage('homeHeaderTitleImage');
						
					} else if (Request::getUserVar('uploadHomeHeaderLogoImage')) {
						if ($setupForm->uploadImage('homeHeaderLogoImage')) {
							$editData = true;
						} else {
							$setupForm->addError('homeHeaderLogoImage', 'director.setup.homeHeaderImageInvalid');
						}

					} else if (Request::getUserVar('deleteHomeHeaderLogoImage')) {
						$editData = true;
						$setupForm->deleteImage('homeHeaderLogoImage');
						
					} else if (Request::getUserVar('uploadPageHeaderTitleImage')) {
						if ($setupForm->uploadImage('pageHeaderTitleImage')) {
							$editData = true;
						} else {
							$setupForm->addError('pageHeaderTitleImage', 'director.setup.pageHeaderTitleImageInvalid');
						}

					} else if (Request::getUserVar('deletePageHeaderTitleImage')) {
						$editData = true;
						$setupForm->deleteImage('pageHeaderTitleImage');
						
					} else if (Request::getUserVar('uploadPageHeaderLogoImage')) {
						if ($setupForm->uploadImage('pageHeaderLogoImage')) {
							$editData = true;
						} else {
							$setupForm->addError('pageHeaderLogoImage', 'director.setup.pageHeaderLogoImageInvalid');
						}

					} else if (Request::getUserVar('deletePageHeaderLogoImage')) {
						$editData = true;
						$setupForm->deleteImage('pageHeaderLogoImage');
						
					} else if (Request::getUserVar('uploadHomeHeaderTitleImageAlt1')) {
						if ($setupForm->uploadImage('homeHeaderTitleImageAlt1')) {
							$editData = true;
						} else {
							$setupForm->addError('homeHeaderTitleImageAlt1', 'director.setup.homeHeaderTitleImageAlt1Invalid');
						}

					} else if (Request::getUserVar('deleteHomeHeaderTitleImageAlt1')) {
						$editData = true;
						$setupForm->deleteImage('homeHeaderTitleImageAlt1');
						
					} else if (Request::getUserVar('uploadHomeHeaderLogoImageAlt1')) {
						if ($setupForm->uploadImage('homeHeaderLogoImageAlt1')) {
							$editData = true;
						} else {
							$setupForm->addError('homeHeaderLogoImageAlt1', 'director.setup.homeHeaderLogoImageAlt1Invalid');
						}

					} else if (Request::getUserVar('deleteHomeHeaderLogoImageAlt1')) {
						$editData = true;
						$setupForm->deleteImage('homeHeaderLogoImageAlt1');
						
					} else if (Request::getUserVar('uploadPageHeaderTitleImageAlt1')) {
						if ($setupForm->uploadImage('pageHeaderTitleImageAlt1')) {
							$editData = true;
						} else {
							$setupForm->addError('pageHeaderTitleImageAlt1', 'director.setup.pageHeaderTitleImageAlt1Invalid');
						}

					} else if (Request::getUserVar('deletePageHeaderTitleImageAlt1')) {
						$editData = true;
						$setupForm->deleteImage('pageHeaderTitleImageAlt1');
						
					} else if (Request::getUserVar('uploadPageHeaderLogoImageAlt1')) {
						if ($setupForm->uploadImage('pageHeaderLogoImageAlt1')) {
							$editData = true;
						} else {
							$setupForm->addError('pageHeaderLogoImageAlt1', 'director.setup.pageHeaderLogoImageAlt1Invalid');
						}

					} else if (Request::getUserVar('deletePageHeaderLogoImageAlt1')) {
						$editData = true;
						$setupForm->deleteImage('pageHeaderLogoImageAlt1');
						
					} else if (Request::getUserVar('uploadHomeHeaderTitleImageAlt2')) {
						if ($setupForm->uploadImage('homeHeaderTitleImageAlt2')) {
							$editData = true;
						} else {
							$setupForm->addError('homeHeaderTitleImageAlt2', 'director.setup.homeHeaderTitleImageAlt2Invalid');
						}

					} else if (Request::getUserVar('deleteHomeHeaderTitleImageAlt2')) {
						$editData = true;
						$setupForm->deleteImage('homeHeaderTitleImageAlt2');
						
					} else if (Request::getUserVar('uploadHomeHeaderLogoImageAlt2')) {
						if ($setupForm->uploadImage('homeHeaderLogoImageAlt2')) {
							$editData = true;
						} else {
							$setupForm->addError('homeHeaderLogoImageAlt2', 'director.setup.homeHeaderLogoImageAlt2Invalid');
						}

					} else if (Request::getUserVar('deleteHomeHeaderLogoImageAlt2')) {
						$editData = true;
						$setupForm->deleteImage('homeHeaderLogoImageAlt2');
						
					} else if (Request::getUserVar('uploadPageHeaderTitleImageAlt2')) {
						if ($setupForm->uploadImage('pageHeaderTitleImageAlt2')) {
							$editData = true;
						} else {
							$setupForm->addError('pageHeaderTitleImageAlt2', 'director.setup.pageHeaderTitleImageAlt2Invalid');
						}

					} else if (Request::getUserVar('deletePageHeaderTitleImageAlt2')) {
						$editData = true;
						$setupForm->deleteImage('pageHeaderTitleImageAlt2');
						
					} else if (Request::getUserVar('uploadPageHeaderLogoImageAlt2')) {
						if ($setupForm->uploadImage('pageHeaderLogoImageAlt2')) {
							$editData = true;
						} else {
							$setupForm->addError('pageHeaderLogoImageAlt2', 'director.setup.pageHeaderLogoImageAlt2Invalid');
						}

					} else if (Request::getUserVar('deletePageHeaderLogoImageAlt2')) {
						$editData = true;
						$setupForm->deleteImage('pageHeaderLogoImageAlt2');
						
					} else if (Request::getUserVar('uploadHomepageImage')) {
						if ($setupForm->uploadImage('homepageImage')) {
							$editData = true;
						} else {
							$setupForm->addError('homepageImage', 'director.setup.homepageImageInvalid');
						}

					} else if (Request::getUserVar('deleteHomepageImage')) {
						$editData = true;
						$setupForm->deleteImage('homepageImage');

					} else if (Request::getUserVar('uploadConferenceStyleSheet')) {
						if ($setupForm->uploadStyleSheet('eventStyleSheet')) {
							$editData = true;
						} else {
							$setupForm->addError('conferenceStyleSheet', 'director.setup.conferenceStyleSheetInvalid');
						}

					} else if (Request::getUserVar('deleteConferenceStyleSheet')) {
						$editData = true;
						$setupForm->deleteImage('conferenceStyleSheet');
						
					} else if (Request::getUserVar('addNavItem')) {
						// Add a navigation bar item
						$editData = true;
						$navItems = $setupForm->getData('navItems');
						array_push($navItems,array());
						$setupForm->setData('navItems', $navItems);
						
					} else if (($delNavItem = Request::getUserVar('delNavItem')) && count($delNavItem) == 1) {
						// Delete a  navigation bar item
						$editData = true;
						list($delNavItem) = array_keys($delNavItem);
						$delNavItem = (int) $delNavItem;
						$navItems = $setupForm->getData('navItems');
						array_splice($navItems, $delNavItem, 1);		
						$setupForm->setData('navItems', $navItems);
					}
					break;
			}
			
			if (!isset($editData) && $setupForm->validate()) {
				$setupForm->execute();
				
				$templateMgr = &TemplateManager::getManager();
				$templateMgr->assign('setupStep', $step);
				$templateMgr->assign('helpTopicId', 'conference.managementPages.setup');
				$templateMgr->display('eventDirector/setup/settingsSaved.tpl');
			
			} else {
				$setupForm->display();
			}
		
		} else {
			Request::redirect();
		}
	}
}
?>
