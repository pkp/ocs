<?php

/**
 * @file Request.inc.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Request
 * @ingroup core
 *
 * @brief Class providing operations associated with HTTP requests.
 * Requests are assumed to be in the format:
 *    http://host.tld/index.php
 *                   /<conference_id>
 *                   /<sched_conf_id>
 *                   /<page_name>
 *                   /<operation_name>
 *                   /<arguments...>
 * <conference_id> is assumed to be "index" for top-level site requests.
 *                 ditto for <sched_conf_id>
 */

//$Id$


import('core.PKPRequest');

class Request extends PKPRequest {
	/**
	 * Redirect to the specified page within OCS. Shorthand for a common call to Request::redirect(Request::url(...)).
	 * @param $conferencePath string The path of the conference to redirect to.
	 * @param $schedConfPath string The path of the conference to redirect to.
	 * @param $page string The name of the op to redirect to.
	 * @param $op string optional The name of the op to redirect to.
	 * @param $path mixed string or array containing path info for redirect.
	 * @param $params array Map of name => value pairs for additional parameters
	 * @param $anchor string Name of desired anchor on the target page
	 */
	function redirect($conferencePath = null, $schedConfPath = null, $page = null, $op = null, $path = null, $params = null, $anchor = null) {
		Request::redirectUrl(Request::url($conferencePath, $schedConfPath, $page, $op, $path, $params, $anchor));
	}

	/**
	 * Get the conference path requested in the URL ("index" for top-level site requests).
	 * @return string 
	 */
	function getRequestedConferencePath() {
		static $conference;

		if (!isset($conference)) {
			if (Request::isPathInfoEnabled()) {
				$conference = '';
				if (isset($_SERVER['PATH_INFO'])) {
					$vars = explode('/', $_SERVER['PATH_INFO']);
					if (count($vars) >= 3) {
						$conference = Core::cleanFileVar($vars[1]);
					}
				}
			} else {
				$conference = Request::getUserVar('conference');
			}

			$conference = empty($conference) ? 'index' : $conference;
			HookRegistry::call('Request::getRequestedConferencePath', array(&$conference));
		}

		return $conference;
	}

	/**
	 * Get the scheduled conference path requested in the URL ("index" for top-level site requests).
	 * @return string 
	 */
	function getRequestedSchedConfPath() {
		static $schedConf;

		if (!isset($schedConf)) {
			if (Request::isPathInfoEnabled()) {
				$schedConf = '';
				if (isset($_SERVER['PATH_INFO'])) {
					$vars = explode('/', $_SERVER['PATH_INFO']);
					if (count($vars) >= 3) {
						$schedConf = Core::cleanFileVar($vars[2]);
					}
				}
			} else {
				$schedConf = Request::getUserVar('schedConf');
			}

			$schedConf = empty($schedConf) ? 'index' : $schedConf;
			HookRegistry::call('Request::getRequestedSchedConfPath', array(&$schedConf));
		}

		return $schedConf;
	}
	
	function getRequestedContextPath($contextLevel = null) {
		switch ($contextLevel) {
			case 1:
				return array(Request::getRequestedConferencePath());
				break;
			case 2:
				return array(Request::getRequestedSchedConfPath());
				break;
			default:
				return array(Request::getRequestedConferencePath(), Request::getRequestedSchedConfPath());
		}
	}
	
	function &getContext($level = 1) {
		$returner = false;
		switch ($level) {
			case 1:
				$returner =& Request::getConference();
				break;
			case 2:
				$returner =& Request::getSchedConf();
				break;	
		}
		return $returner;	
	}
	
	function &getContextByName($contextName) {
		$returner = false;
		switch ($contextName) {
			case 'conference':
				$returner =& Request::getConference();
				break;
			case 'schedConf':
				$returner =& Request::getSchedConf();
				break;	
		}
		return $returner;
	}

	/**
	 * Get the conference associated with the current request.
	 * @return Conference
	 */
	function &getConference() {
		static $conference;

		if (!isset($conference)) {
			$path = Request::getRequestedConferencePath();
			if ($path != 'index') {
				$conferenceDao = &DAORegistry::getDAO('ConferenceDAO');
				$conference = $conferenceDao->getConferenceByPath(Request::getRequestedConferencePath());
			}
		}

		return $conference;
	}

	/**
	 * Get the scheduled conference associated with the current request.
	 * @return schedConf object
	 */
	function &getSchedConf() {
		static $schedConf;

		if (!isset($schedConf)) {
			$path = Request::getRequestedSchedConfPath();
			if ($path != 'index') {
				$schedConfDao = &DAORegistry::getDAO('SchedConfDAO');
				$schedConf = $schedConfDao->getSchedConfByPath(Request::getRequestedSchedConfPath());
			}
		}

		return $schedConf;
	}

	/**
	 * Build a URL into OCS.
	 * @param $conferencePath string Optional path for conference to use
	 * @param $schedConfPath string Optional path for scheduled conference to use
	 * @param $page string Optional name of page to invoke
	 * @param $op string Optional name of operation to invoke
	 * @param $path mixed Optional string or array of args to pass to handler
	 * @param $params array Optional set of name => value pairs to pass as user parameters
	 * @param $anchor string Optional name of anchor to add to URL
	 * @param $escape boolean Whether or not to escape ampersands for this URL; default false.
	 */
	function url($conferencePath = null, $schedConfPath = null, $page = null,
			$op = null, $path = null, $params = null, $anchor = null, $escape = false) {
		return parent::url(array($conferencePath, $schedConfPath), $page, $op, $path, $params, $anchor, $escape);
	}
}

?>
