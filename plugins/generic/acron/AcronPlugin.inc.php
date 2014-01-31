<?php

/**
 * @file AcronPlugin.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AcronPlugin
 * @ingroup plugins_generic_acron
 *
 * @brief Removes dependency on 'cron' for scheduled tasks
 */

//$Id$

import('classes.plugins.GenericPlugin');

// TODO: Rather than parsing the crontab on each request (which is slow and
// dumb), decide when earliest possible run for each scheduled conference is, and store that
// timestamp for quick reference along with the other entry parameters.

// Alternately, store a PluginSetting containing the last time Acron actually
// considered scheduled tasks, and limit the run frequency to (e.g.) 30 minutes.

// TODO: Error handling. If a scheduled task encounters an error...?

class AcronPlugin extends GenericPlugin {

	function isSitePlugin() {
		// This is a site-wide plugin.
		return true;
	}

	function register($category, $path) {
		if (!Config::getVar('general', 'installed')) return false;
		if (parent::register($category, $path)) {

			$this->addLocaleData();
			$this->parseCrontab();

			HookRegistry::register('LoadHandler',array(&$this, 'callback'));

			return true;
		}
		return false;
	}

	function callback($hookName, $args) {
		$isEnabled = $this->getSetting(0, 0, 'enabled');
		if($isEnabled) {
			$taskDao =& DAORegistry::getDao('ScheduledTaskDAO');

			// Grab the scheduled scheduled conference tree
			$scheduledTasks = $this->getSetting(0, 0, 'crontab');
			if(!$scheduledTasks) {
				$this->parseCrontab();
				$scheduledTasks = $this->getSetting(0, 0, 'crontab');
			}

			$tasks = $this->getSetting(0, 0, 'crontab');

			foreach($tasks as $task) {

				$lastRuntime = $taskDao->getLastRunTime($task['className']);

				if (isset($task['frequency'])) {
					$canExecute = $this->checkFrequency($task['className'], $task['frequency'], $lastRuntime);
				} else {
					// WARNING: tasks without 'frequency' entries will run ON EVERY REQUEST.
					// This can incur a serious performance hit.
					$canExecute = true;
				}

				if ($canExecute) {
					// Strip off the package name(s) to get the base class name
					$className = $task['className'];

					$pos = strrpos($className, '.');
					if ($pos === false) {
						$baseClassName = $className;
					} else {
						$baseClassName = substr($className, $pos+1);
					}

					// There's a race here. Several requests may come in closely spaced.
					// Each may decide it's time to run scheduled tasks, and more than one
					// can happily go ahead and do it before the "last run" time is updated.

					// By updating the last run time as soon as feasible, we can minimize
					// the race window. TODO: there ought to be a safer way of doing this.

					$taskDao->updateLastRunTime($className, $lastRuntime);

					// Load and execute the task
					import($className);
					$task = new $baseClassName($args);
					$task->execute();
				}
			}
		}

		return false;
	}

	/*
	 * parseCrontab: reload the scheduled tasks XML.
	 */
	function parseCrontab() {
		$xmlParser = new XMLParser();

		// TODO: make this a plugin setting, rather than assuming.
		$tree = $xmlParser->parse(Config::getVar('general', 'registry_dir') . '/scheduledTasks.xml');

		if (!$tree) {
			$xmlParser->destroy();

			// TODO: graceful error handling
			fatalError('Error parsing scheduled tasks XML.');
		}

		$tasks = array();

		foreach ($tree->getChildren() as $task) {
			$frequency = $task->getChildByName('frequency');

			$args = array();
			$index = 0;
			while(($arg = $task->getChildByName('arg', $index)) != null) {
				array_push($args, $arg->getValue());
				$index++;
			}

			$tasks[] = array(
				'className' => $task->getAttribute('class'),
				'frequency' => $frequency ? $frequency->getAttributes() : null,
				'args' => $args
			);
		}

		$xmlParser->destroy();

		// Store the object.
		$this->updateSetting(0, 0, 'crontab', $tasks, 'object');		
	}

	/**
	 * Check if the specified task should be executed according to the specified
	 * frequency and its last run time.
	 * @param $className string
	 * @param $frequency XMLNode
	 * @return string
	 */
	function checkFrequency($className, $frequency, $lastRunTime) {
		$isValid = true;

		// Check day of week
		if (isset($frequency['dayofweek'])) {
			$isValid = $this->isInRange($frequency['dayofweek'], (int)date('w'), $lastRunTime, 'day', strtotime('-1 week'));
		}

		if ($isValid) {
			// Check month
			if (isset($frequency['month'])) {
				$isValid = $this->isInRange($frequency['month'], (int)date('n'), $lastRunTime, 'month', strtotime('-1 year'));
			}
		}

		if ($isValid) {
			// Check day
			if (isset($frequency['day'])) {
				$isValid = $this->isInRange($frequency['day'], (int)date('j'), $lastRunTime, 'day', strtotime('-1 month'));
			}
		}

		if ($isValid) {
			// Check hour
			if (isset($frequency['hour'])) {
				$isValid = $this->isInRange($frequency['hour'], (int)date('G'), $lastRunTime, 'hour', strtotime('-1 day'));
			}
		}

		if ($isValid) {
			// Check minute
			if (isset($frequency['minute'])) {
				$isValid = $this->isInRange($frequency['minute'], (int)date('i'), $lastRunTime, 'min', strtotime('-1 hour'));
			}
		}

		return $isValid;
	}

	/**
	 * Check if a value is within the specified range.
	 * @param $rangeStr string the range (e.g., 0, 1-5, *, etc.)
	 * @param $currentValue int value to check if its in the range
	 * @param $lastTimestamp int the last time the task was executed
	 * @param $timeCompareStr string value to use in strtotime("-X $timeCompareStr")
	 * @param $cutoffTimestamp int value will be considered valid if older than this
	 * @return boolean
	 */
	function isInRange($rangeStr, $currentValue, $lastTimestamp, $timeCompareStr, $cutoffTimestamp) {
		$isValid = false;
		$rangeArray = explode(',', $rangeStr);

		if ($cutoffTimestamp > $lastTimestamp) {
			// Execute immediately if the cutoff time period has past since the task was last run
			$isValid = true;
		}

		for ($i = 0, $count = count($rangeArray); !$isValid && ($i < $count); $i++) {
			if ($rangeArray[$i] == '*') {
				// Is wildcard
				$isValid = true;

			} if (is_numeric($rangeArray[$i])) {
				// Is just a value
				$isValid = ($currentValue == (int)$rangeArray[$i]);

			} else if (preg_match('/^(\d*)\-(\d*)$/', $rangeArray[$i], $matches)) {
				// Is a range
				$isValid = $this->isInNumericRange($currentValue, (int)$matches[1], (int)$matches[2]);

			} else if (preg_match('/^(.+)\/(\d+)$/', $rangeArray[$i], $matches)) {
				// Is a range with a skip factor
				$skipRangeStr = $matches[1];
				$skipFactor = (int)$matches[2];

				if ($skipRangeStr == '*') {
					$isValid = true;

				} else if (preg_match('/^(\d*)\-(\d*)$/', $skipRangeStr, $matches)) {
					$isValid = $this->isInNumericRange($currentValue, (int)$matches[1], (int)$matches[2]);
				}

				if ($isValid) {
					// Check against skip factor
					$isValid = (strtotime("-$skipFactor $timeCompareStr") > $lastTimestamp);
				}
			}
		}

		return $isValid;
	}

	/**
	 * Check if a numeric value is within the specified range.
	 * @param $value int
	 * @param $min int
	 * @param $max int
	 * @return boolean
	 */
	function isInNumericRange($value, $min, $max) {
		return ($value >= $min && $value <= $max);
	}

	function getName() {
		return 'AcronPlugin';
	}

	function getDisplayName() {
		return __('plugins.generic.acron.name');
	}

	function getDescription() {
		return __('plugins.generic.acron.description');
	}

	function getManagementVerbs() {
		$isEnabled = $this->getSetting(0, 0, 'enabled');

		$verbs = array();
		$verbs[] = array(
			($isEnabled?'disable':'enable'),
			__($isEnabled?'manager.plugins.disable':'manager.plugins.enable')
		);
		$verbs[] = array(
			'reload', __('plugins.generic.acron.reload')
		);
		return $verbs;
	}

 	/*
 	 * Execute a management verb on this plugin
 	 * @param $verb string
 	 * @param $args array
	 * @param $message string Location for the plugin to put a result msg
 	 * @return boolean
 	 */
	function manage($verb, $args, &$message) {
		switch ($verb) {
			case 'enable':
				$this->updateSetting(0, 0, 'enabled', true);
				$message = __('plugins.generic.acron.enabled');
				break;
			case 'disable':
				$this->updateSetting(0, 0, 'enabled', false);
				$message = __('plugins.generic.acron.disabled');
				break;
			case 'reload':
				$this->parseCrontab();
		}
		return false;
	}
}
?>
