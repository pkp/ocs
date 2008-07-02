<?php

/**
 * @file Handler.inc.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Handler
 * @ingroup core
 *
 * @brief Base request handler class.
 */

//$Id$

class Handler {

	/**
	 * Fallback method in case request handler does not implement index method.
	 */
	function index() {
		header('HTTP/1.0 404 Not Found');
		fatalError('404 Not Found');
	}

	/**
	 * Perform request access validation based on security settings.
	 * @param $requiresConference boolean
	 */
	function validate($requiresConference = false, $requiresSchedConf = false) {
		if (Config::getVar('security', 'force_ssl') && Request::getProtocol() != 'https') {
			// Force SSL connections site-wide
			Request::redirectSSL();
		}

		$conference = &Request::getConference();
		$schedConf = &Request::getSchedConf();

		if($requiresConference) {
			if ($conference == null) {
				// Requested page is only allowed when a conference is provided
				Request::redirect(null, null, 'about');
			}
		}

		if($requiresSchedConf) {
			if ($schedConf == null) {
				// Requested page is only allowed when a scheduled conference is provided
				Request::redirect(null, null, 'about');
			}
		}

		// Extraneous checks, just to make sure we aren't being fooled
		if ($conference && $schedConf) {
			if($schedConf->getConferenceId() != $conference->getConferenceId())
				Request::redirect(null, null, 'about');
		}

		return array(&$conference, &$schedConf);
	}

	/**
	 * Generate a unique-ish hash of the page's identity, including all context that differentiates
	 * it from other similar pages (e.g. all articles vs. all articles starting with "l").
	 * @param $contextData array A set of information identifying the page
	 * @return string hash
	 */
	function hashPageContext($contextData = array()) {
		return md5(
			Request::getRequestedConferencePath() . ',' .
			Request::getRequestedSchedConfPath() . ',' .
			Request::getRequestedPage() . ',' .
			Request::getRequestedOp() . ',' .
			serialize($contextData)
		);
	}

	/**
	 * Return the DBResultRange structure and misc. variables describing the current page of a set of pages.
	 * @param $rangeName string Symbolic name of range of pages; must match the Smarty {page_list ...} name.
	 * @param $contextData array If set, this should contain a set of data that are required to
	 * 	define the context of this request (for maintaining page numbers across requests).
	 *	To disable persistent page contexts, set this variable to null.
	 * @return array ($pageNum, $dbResultRange)
	 */
	function &getRangeInfo($rangeName, $contextData = null) {
		$conference = &Request::getConference();
		$conferenceSettingsDao = &DAORegistry::getDAO('ConferenceSettingsDAO');

		$pageNum = Request::getUserVar($rangeName . 'Page');
		if (empty($pageNum)) {
			$session =& Request::getSession();
			$pageNum = 1; // Default to page 1
			if ($session && $contextData !== null) {
				// See if we can get a page number from a prior request
				$context = Handler::hashPageContext($contextData);

				if (Request::getUserVar('clearPageContext')) {
					// Explicitly clear the old page context
					$session->unsetSessionVar("page-$context");
				} else {
					$oldPage = $session->getSessionVar("page-$context");
					if (is_numeric($oldPage)) $pageNum = $oldPage;
				}
			}
		} else {
			$session =& Request::getSession();
			if ($session && $contextData !== null) {
				// Store the page number
				$context = Handler::hashPageContext($contextData);
				$session->setSessionVar("page-$context", $pageNum);
			}
		}

		if ($conference) $count = $conferenceSettingsDao->getSetting($conference->getConferenceId(), 'itemsPerPage');
		if (!isset($count)) $count = Config::getVar('interface', 'items_per_page');

		import('db.DBResultRange');

		if (isset($count)) $returner = &new DBResultRange($count, $pageNum);
		else $returner = &new DBResultRange(-1, -1);

		return $returner;
	}
}

?>
