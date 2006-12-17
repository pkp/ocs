<?php

/**
 * AcronPlugin.inc.php
 *
 * Removes dependency on 'cron' for scheduled tasks
 *
 * $Id$
 */
 
import('classes.plugins.GenericPlugin');

class AcronPlugin extends GenericPlugin {
	function register($category, $path) {
		if (!Config::getVar('general', 'installed')) return false;
		if (parent::register($category, $path)) {
			$isEnabled = $this->getSetting(0, 'enabled');

			$this->addLocaleData();
			HookRegistry::register('TemplateManager::display',array(&$this, 'callback'));
			return true;
		}
		return false;
	}

	function callback($hookName, $args) {
		return false;
	}

	function getName() {
		return 'AcronPlugin';
	}

	function getDisplayName() {
		return Locale::translate('plugins.generic.acron.name');
	}

	function getDescription() {
		return Locale::translate('plugins.generic.acron.description');
	}

	function getManagementVerbs() {
		$isEnabled = $this->getSetting(0, 'enabled');

		$verbs = array();
		$verbs[] = array(
			($isEnabled?'disable':'enable'),
			Locale::translate($isEnabled?'director.plugins.disable':'director.plugins.enable')
		);
		return $verbs;
	}

	function manage($verb, $args) {
		switch ($verb) {
			case 'enable':
				$this->updateSetting(0, 'enabled', true);
				break;
			case 'disable':
				$this->updateSetting(0, 'enabled', false);
				break;
		}
		return false;
	}
}
?>
