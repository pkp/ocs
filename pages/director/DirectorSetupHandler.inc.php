<?php

/**
 * DirectorSetupHandler.inc.php
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

class DirectorSetupHandler extends DirectorHandler {
	
	/**
	 * Display conference setup form for the selected step.
	 * Displays setup index page if a valid step is not specified.
	 * @param $args array optional, if set the first parameter is the step to display
	 */
	function setup($args) {
		parent::validate();
		parent::setupTemplate(true);
		
		$step = isset($args[0]) ? (int) $args[0] : 0;
		
		if ($step >= 1 && $step <= 5) {
			
			$formClass = "ConferenceSetupStep{$step}Form";
			import("director.form.setup.$formClass");
			
			$setupForm = &new $formClass();
			$setupForm->initData();
			$setupForm->display();
		
		} else {
			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign('helpTopicId','conference.managementPages.setup');
			$templateMgr->display('director/setup/index.tpl');
		}
	}
	
	/**
	 * Save changes to conference settings.
	 * @param $args array first parameter is the step being saved
	 */
	function saveSetup($args) {
		parent::validate();
		
		$step = isset($args[0]) ? (int) $args[0] : 0;
		
		if ($step >= 1 && $step <= 5) {

			parent::setupTemplate(true);
			
			$formClass = "ConferenceSetupStep{$step}Form";
			import("director.form.setup.$formClass");
			
			$setupForm = &new $formClass();
			$setupForm->readInputData();
			
			// Check for any special cases before trying to save
			switch ($step) {
				case 2:
					if (Request::getUserVar('addCustomAboutItem')) {
						// Add a custom about item
						$editData = true;
						$customAboutItems = $setupForm->getData('customAboutItems');
						array_push($customAboutItems, array());
						$setupForm->setData('customAboutItems', $customAboutItems);
						
					} else if (($delCustomAboutItem = Request::getUserVar('delCustomAboutItem')) && count($delCustomAboutItem) == 1) {
						// Delete a custom about item
						$editData = true;
						list($delCustomAboutItem) = array_keys($delCustomAboutItem);
						$delCustomAboutItem = (int) $delCustomAboutItem;
						$customAboutItems = $setupForm->getData('customAboutItems');
						array_splice($customAboutItems, $delCustomAboutItem, 1);
						$setupForm->setData('customAboutItems', $customAboutItems);

					} else if (Request::getUserVar('addChecklist')) {
						// Add a checklist item
						$editData = true;
						$checklist = $setupForm->getData('submissionChecklist');
						if (!is_array($checklist)) {
							$checklist = array();
							$lastOrder = 0;
						} else {
							$lastOrder = $checklist[count($checklist)-1]['order'];
						}
						array_push($checklist, array('order' => $lastOrder+1));
						$setupForm->setData('submissionChecklist', $checklist);
						
					} else if (($delChecklist = Request::getUserVar('delChecklist')) && count($delChecklist) == 1) {
						// Delete a checklist item
						$editData = true;
						list($delChecklist) = array_keys($delChecklist);
						$delChecklist = (int) $delChecklist;
						$checklist = $setupForm->getData('submissionChecklist');
						array_splice($checklist, $delChecklist, 1);
						$setupForm->setData('submissionChecklist', $checklist);
					}
					
					if (!isset($editData)) {
						// Reorder checklist items
						$checklist = $setupForm->getData('submissionChecklist');
						if (is_array($checklist)) {
							usort($checklist, create_function('$a,$b','return $a[\'order\'] == $b[\'order\'] ? 0 : ($a[\'order\'] < $b[\'order\'] ? -1 : 1);'));
						}
						$setupForm->setData('submissionChecklist', $checklist);
					}
					break;

				case 4:
					$conference =& Request::getConference();
					$templates = $conference->getSetting('templates');
					import('file.ConferenceFileManager');
					$conferenceFileManager =& new ConferenceFileManager($conference);
					if (Request::getUserVar('addTemplate')) {
						// Add a layout template
						$editData = true;
						if (!is_array($templates)) $templates = array();
						$templateId = count($templates);
						$originalFilename = $_FILES['template-file']['name'];
						$fileType = $_FILES['template-file']['type'];
						$filename = "template-$templateId." . $conferenceFileManager->parseFileExtension($originalFilename);
						$conferenceFileManager->uploadFile('template-file', $filename);
						$templates[$templateId] = array(
							'originalFilename' => $originalFilename,
							'fileType' => $fileType,
							'filename' => $filename,
							'title' => Request::getUserVar('template-title')
						);
						$conference->updateSetting('templates', $templates);
					} else if (($delTemplate = Request::getUserVar('delTemplate')) && count($delTemplate) == 1) {
						// Delete a template
						$editData = true;
						list($delTemplate) = array_keys($delTemplate);
						$delTemplate = (int) $delTemplate;
						$template = $templates[$delTemplate];
						$filename = "template-$delTemplate." . $conferenceFileManager->parseFileExtension($template['originalFilename']);
						$conferenceFileManager->deleteFile($filename);
						array_splice($templates, $delTemplate, 1);
						$conference->updateSetting('templates', $templates);
					}
					$setupForm->setData('templates', $templates);
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
						if ($setupForm->uploadStyleSheet('conferenceStyleSheet')) {
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

				if($step == 5) {
					$conference =& Request::getConference();

					if($conference->getSetting('showSetupHints')) {
						$templateMgr->assign('showSetupHints',true);
					}
				}
				$templateMgr->display('director/setup/settingsSaved.tpl');
			
			} else {
				$setupForm->display();
			}
		
		} else {
			Request::redirect();
		}
	}
	
	function downloadLayoutTemplate($args) {
		parent::validate();
		$conference =& Request::getConference();
		$templates = $conference->getSetting('templates');
		import('file.ConferenceFileManager');
		$conferenceFileManager =& new ConferenceFileManager($conference);
		$templateId = (int) array_shift($args);
		if ($templateId >= count($templates) || $templateId < 0) Request::redirect(null, null, null, 'setup');
		$template =& $templates[$templateId];

		$filename = "template-$templateId." . $conferenceFileManager->parseFileExtension($template['originalFilename']);
		$conferenceFileManager->downloadFile($filename, $template['fileType']);
	}
}
?>
