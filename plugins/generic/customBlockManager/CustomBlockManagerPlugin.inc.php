<?php
/**
 * @file CustomBlockManagerPlugin.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.customBlockManager
 * @class CustomBlockManagerPlugin
 *
 * Plugin to let users add and delete sidebar blocks
 * 
 */

import('classes.plugins.GenericPlugin');

class CustomBlockManagerPlugin extends GenericPlugin {

	function getName() {
		return 'CustomBlockManagerPlugin';
	}

	function getDisplayName() {
		return __('plugins.generic.customBlockManager.displayName');
	}

	function getDescription() {
		return __('plugins.generic.customBlockManager.description');
	}   

	function register($category, $path) {
		if (!Config::getVar('general', 'installed')) return false;
		if (parent::register($category, $path)) {	
			if ( $this->getEnabled() ) {
				HookRegistry::register('PluginRegistry::loadCategory', array(&$this, 'callbackLoadCategory'));				
			}
			$this->addLocaleData();
			return true;
		}
		return false;
	}
	
	/**
	 * Register as a block plugin, even though this is a generic plugin.
	 * This will allow the plugin to behave as a block plugin, i.e. to
	 * have layout tasks performed on it.
	 * @param $hookName string
	 * @param $args array
	 */
	function callbackLoadCategory($hookName, $args) {
		$category =& $args[0];
		$plugins =& $args[1];
		switch ($category) {
			case 'blocks':
				$this->import('CustomBlockPlugin');
				
				$conference =& Request::getConference();
				if ( !$conference ) return false;
				
				$blocks = $this->getSetting($conference->getId(), 0, 'blocks');
				if ( !is_array($blocks) ) break;
				$i= 0;
				foreach ( $blocks as $block ) {
					$blockPlugin = new CustomBlockPlugin($block);
					
					//default the block to being enabled
					if ( $blockPlugin->getEnabled() !== false) {
						$blockPlugin->setEnabled(true);
					}
					//default the block to the right sidebar
					if ( !is_numeric($blockPlugin->getBlockContext())) {
						$blockPlugin->setBlockContext(BLOCK_CONTEXT_RIGHT_SIDEBAR);
					}
					$plugins[$blockPlugin->getSeq()][$blockPlugin->getPluginPath() . $i] =& $blockPlugin;
					
					$i++;
					unset($blockPlugin);
				}
				break;
		}
		return false;
	}	

	/**
	 * Determine whether or not this plugin is enabled.
	 */
	function getEnabled() {
		$conference =& Request::getConference();
 		$conferenceId = $conference?$conference->getId():0;
		return $this->getSetting($conferenceId, 0, 'enabled');
	}

	/**
	 * Set the enabled/disabled state of this plugin
	 */
	function setEnabled($enabled) {
 		$conference =& Request::getConference();
 		$conferenceId = $conference?$conference->getId():0;
 		$this->updateSetting($conferenceId, 0, 'enabled', $enabled);
	}

	/**
	 * Display verbs for the management interface.
	 */
	function getManagementVerbs() {
		$verbs = array();
		if ($this->getEnabled()) {
			$verbs[] = array(
				'disable',
				__('manager.plugins.disable')
			);
			$verbs[] = array(
				'settings',
				__('plugins.generic.customBlockManager.settings')
			);			
		} else {
			$verbs[] = array(
				'enable',
				__('manager.plugins.enable')
			);
		}
		return $verbs;
	}

	/**
	 * Perform management functions
	 */
	function manage($verb, $args) {
		$returner = true;

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));

		$pageCrumbs = array(
			array(
				Request::url(null, null, 'user'),
				'navigation.user'
			),
			array(
				Request::url(null, null, 'manager'),
				'user.role.manager'
			)
		);
		
		$conference =& Request::getConference();
		
		switch ($verb) {
			case 'enable':
				$this->setEnabled(true);
				break;
			case 'disable':
				$this->setEnabled(false);
				break;
			case 'settings':
				$pageCrumbs[] = array(
					Request::url(null, null, 'manager', 'plugins'),
					__('manager.plugins'),
					true
				);
				$templateMgr->assign('pageHierarchy', $pageCrumbs);

				$this->import('SettingsForm');
				$form = new SettingsForm($this, $conference->getId());
				$form->readInputData();
				
				if (Request::getUserVar('addBlock')) {
					// Add a block
					$editData = true;
					//$blocks = is_null($form->getData('blocks'))?array():$form->getData('blocks');
					$blocks = $form->getData('blocks');
					array_push($blocks, '');
					$form->_data['blocks'] = $blocks;

				} else if (($delBlock = Request::getUserVar('delBlock')) && count($delBlock) == 1) {
					// Delete an block
					$editData = true;
					list($delBlock) = array_keys($delBlock);
					$delBlock = (int) $delBlock;
					$blocks = $form->getData('blocks');
					if (isset($blocks[$delBlock]) && !empty($blocks[$delBlock])) {
						$deletedBlocks = explode(':', $form->getData('deletedBlocks'));
						array_push($deletedBlocks, $blocks[$delBlock]);
						$form->setData('deletedBlocks', join(':', $deletedBlocks));
					}
					array_splice($blocks, $delBlock, 1);
					$form->_data['blocks'] = $blocks;
				} else if ( Request::getUserVar('save') ) {
					$editData = true;
					$form->execute();
				} else {
					$form->initData();
				}

				if ( !isset($editData) && $form->validate()) {
					$form->execute();
					$form->display();
					exit;
				} else {
					$form->display();
					exit;
				}
				$returner = true;
				break;
			}
			$returner = false;				
	}
}

?>
