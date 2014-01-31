<?php

/**
 * @file Request.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
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
		$_this =& PKPRequest::_checkThis();
		$_this->redirectUrl($_this->url($conferencePath, $schedConfPath, $page, $op, $path, $params, $anchor));
	}

	/**
	 * Deprecated
	 * @see PKPPageRouter::getRequestedContextPath()
	 */
	function getRequestedConferencePath() {
		static $conference;
		$_this =& PKPRequest::_checkThis();

		if (!isset($conference)) {
			$conference = $_this->_delegateToRouter('getRequestedContextPath', 1);
			HookRegistry::call('Request::getRequestedConferencePath', array(&$conference));
		}

		return $conference;
	}

	/**
	 * Deprecated
	 * @see PKPPageRouter::getRequestedContextPath()
	 */
	function getRequestedSchedConfPath() {
		static $schedConf;
		$_this =& PKPRequest::_checkThis();

		if (!isset($schedConf)) {
			$schedConf = $_this->_delegateToRouter('getRequestedContextPath', 2);
			HookRegistry::call('Request::getRequestedSchedConfPath', array(&$schedConf));
		}

		return $schedConf;
	}

	/**
	 * Deprecated
	 * @see PKPPageRouter::getContext()
	 */
	function &getConference() {
		$_this =& PKPRequest::_checkThis();
		$returner = $_this->_delegateToRouter('getContext', 1);
		return $returner;
	}

	/**
	 * Deprecated
	 * @see PKPPageRouter::getContext()
	 */
	function &getSchedConf() {
		$_this =& PKPRequest::_checkThis();
		$returner = $_this->_delegateToRouter('getContext', 2);
		return $returner;
	}

	/**
	 * Deprecated
	 * @see PKPPageRouter::getRequestedContextPath()
	 */
	function getRequestedContextPath($contextLevel = null) {
		$_this =& PKPRequest::_checkThis();

		// Emulate the old behavior of getRequestedContextPath for
		// backwards compatibility.
		if (is_null($contextLevel)) {
			return $_this->_delegateToRouter('getRequestedContextPaths');
		} else {
			return array($_this->_delegateToRouter('getRequestedContextPath', $contextLevel));
		}
	}

	/**
	 * Deprecated
	 * @see PKPPageRouter::getContext()
	 */
	function &getContext($level = 1) {
		$_this =& PKPRequest::_checkThis();
		$returner = $_this->_delegateToRouter('getContext', $level);
		return $returner;
	}

	/**
	 * Deprecated
	 * @see PKPPageRouter::getContextByName()
	 */
	function &getContextByName($contextName) {
		$_this =& PKPRequest::_checkThis();
		$returner = $_this->_delegateToRouter('getContextByName', $contextName);
		return $returner;
	}

	/**
	 * Deprecated
	 * @see PKPPageRouter::url()
	 */
	function url($conferencePath = null, $schedConfPath = null, $page = null,
			$op = null, $path = null, $params = null, $anchor = null, $escape = false) {
		$_this =& PKPRequest::_checkThis();
		return $_this->_delegateToRouter('url', array($conferencePath, $schedConfPath), $page, $op, $path,
			$params, $anchor, $escape);
	}

	/**
	 * Deprecated
	 * @see PageRouter::redirectHome()
	 */
	function redirectHome() {
		$_this =& PKPRequest::_checkThis();
		return $_this->_delegateToRouter('redirectHome');
	}
}

?>
