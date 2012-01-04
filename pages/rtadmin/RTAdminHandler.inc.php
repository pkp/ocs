<?php

/**
 * @file RTAdminHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RTAdminHandler
 * @ingroup pages_rtadmin
 *
 * @brief Handle Reading Tools administration requests.
 *
 */

// $Id$


import('rt.ocs.ConferenceRTAdmin');
import('handler.Handler');

class RTAdminHandler extends Handler {
	/**
	 * Constructor
	 **/
	function RTAdminHandler() {
		parent::Handler();

		$this->addCheck(new HandlerValidatorConference($this));		
		$this->addCheck(new HandlerValidatorRoles($this, true, null, null, array(ROLE_ID_SITE_ADMIN, ROLE_ID_CONFERENCE_MANAGER)));		
	}

	/**
	 * If no conference is selected, display list of conferences.
	 * Otherwise, display the index page for the selected conference.
	 */
	function index() {
		$this->validate();
		$conference = Request::getConference();
		$user = Request::getUser();
		if ($conference) {
			$rtDao =& DAORegistry::getDAO('RTDAO');
			$rt = $rtDao->getConferenceRTByConference($conference);
			if (isset($rt)) {
				$version = $rtDao->getVersion($rt->getVersion(), $conference->getId());
			}

			// Display the administration menu for this conference.

			$this->setupTemplate();
			$templateMgr =& TemplateManager::getManager();
			$templateMgr->assign('helpTopicId', 'conference.generalManagement.readingTools');
			$templateMgr->assign('versionTitle', isset($version)?$version->getTitle():null);
			$templateMgr->assign('enabled', $rt->getEnabled());

			$templateMgr->display('rtadmin/index.tpl');
		} elseif ($user) {
			// Display a list of conferences.
			$conferenceDao =& DAORegistry::getDAO('ConferenceDAO');
			$roleDao =& DAORegistry::getDAO('RoleDAO');

			$conferences = array();

			$allConferences =& $conferenceDao->getConferences();
			$allConferences =& $allConferences->toArray();

			foreach ($allConferences as $conference) {
				if ($roleDao->roleExists($conference->getId(), 0, $user->getId(), ROLE_ID_CONFERENCE_MANAGER)) {
					$conferences[] = $conference;
				}
			}

			$this->setupTemplate();
			$templateMgr =& TemplateManager::getManager();
			$templateMgr->assign_by_ref('conferences', $conferences);
			$templateMgr->assign('helpTopicId', 'conference.generalManagement.readingTools');
			$templateMgr->display('rtadmin/conferences.tpl');
		} else {
			// Not logged in.
			Validation::redirectLogin();
		}
	}

	function validateUrls($args) {
		$this->validate();

		$rtDao =& DAORegistry::getDAO('RTDAO');
		$conference = Request::getConference();

		if (!$conference) {
			Request::redirect(null, null, Request::getRequestedPage());
			return;
		}

		$versionId = isset($args[0])?$args[0]:0;
		$conferenceId = $conference->getId();

		$version = $rtDao->getVersion($versionId, $conferenceId);

		if ($version) {
			// Validate the URLs for a single version
			$versions = array(&$version);
			import('core.ArrayItemIterator');
			$versions = new ArrayItemIterator($versions, 1, 1);
		} else {
			// Validate all URLs for this conference
			$versions = $rtDao->getVersions($conferenceId);
		}

		$this->setupTemplate(true, $version);
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->register_modifier('validate_url', 'smarty_rtadmin_validate_url');
		$templateMgr->assign_by_ref('versions', $versions);
		$templateMgr->assign('helpTopicId', 'conference.generalManagement.readingTools');
		$templateMgr->display('rtadmin/validate.tpl');
	}

	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 * @param $version object The current version, if applicable
	 * @param $context object The current context, if applicable
	 * @param $search object The current search, if applicable
	 */
	function setupTemplate($subclass = false, $version = null, $context = null, $search = null) {
		parent::setupTemplate();
		AppLocale::requireComponents(array(LOCALE_COMPONENT_PKP_READER, LOCALE_COMPONENT_OCS_MANAGER));

		$conference =& Request::getConference();
		$schedConf =& Request::getSchedConf();
		$templateMgr =& TemplateManager::getManager();

		$pageHierarchy = array();

		if ($schedConf) {
			$pageHierarchy[] = array(Request::url(null, null, 'index'), $schedConf->getFullTitle(), true);
		} elseif ($conference) {
			$pageHierarchy[] = array(Request::url(null, 'index', 'index'), $conference->getConferenceTitle(), true);
		}

		$pageHierarchy[] = array(Request::url(null, null, 'user'), 'navigation.user');
		$pageHierarchy[] = array(Request::url(null, null, 'manager'), 'manager.conferenceSiteManagement');

		if ($subclass) $pageHierarchy[] = array(Request::url(null, null, 'rtadmin'), 'rt.readingTools');

		if ($version) {
			$pageHierarchy[] = array(Request::url(null, null, 'rtadmin', 'versions'), 'rt.versions');
			$pageHierarchy[] = array(Request::url(null, null, 'rtadmin', 'editVersion', $version->getVersionId()), $version->getTitle(), true);
			if ($context) {
				$pageHierarchy[] = array(Request::url(null, null, 'rtadmin', 'contexts', $version->getVersionId()), 'rt.contexts');
				$pageHierarchy[] = array(Request::url(null, null, 'rtadmin', 'editContext', array($version->getVersionId(), $context->getContextId())), $context->getAbbrev(), true);
				if ($search) {
					$pageHierarchy[] = array(Request::url(null, null, 'rtadmin', 'searches', array($version->getVersionId(), $context->getContextId())), 'rt.searches');
					$pageHierarchy[] = array(Request::url(null, null, 'rtadmin', 'editSearch', array($version->getVersionId(), $context->getContextId(), $search->getSearchId())), $search->getTitle(), true);
				}
			}
		}
		$templateMgr->assign('pageHierarchy', $pageHierarchy);
	}
}

function rtadmin_validate_url($url, $useGet = false, $redirectsAllowed = 5) {
	$data = parse_url($url);
	if(!isset($data['host'])) {
		return false;
	}

	$fp = @ fsockopen($data['host'], isset($data['port']) && !empty($data['port']) ? $data['port'] : 80, $errno, $errstr, 10);
	if (!$fp) {
		return false;
	}

	$req = sprintf("%s %s HTTP/1.0\r\nHost: %s\r\nUser-Agent: Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.4b) Gecko/20030516\r\n\r\n", ($useGet ? 'GET' : 'HEAD'), (isset($data['path']) && $data['path'] !== '' ? $data['path'] : '/') .  (isset($data['query']) && $data['query'] !== '' ? '?' .  $data['query'] : ''), $data['host']);

	fputs($fp, $req);

	for($res = '', $time = time(); !feof($fp) && $time >= time() - 15; ) {
		$res .= fgets($fp, 128);
	}

	fclose($fp);

	// Check result for HTTP status code.
	if(!preg_match('!^HTTP/(\d\.?\d*) (\d+)\s*(.+)[\n\r]!m', $res, $matches)) {
		return false;
	}
	list($match, $http_version, $http_status_no, $http_status_str) = $matches;

	// If HTTP status code 2XX (Success)
	if(preg_match('!^2\d\d$!', $http_status_no)) return true;

	// If HTTP status code 3XX (Moved)
	if(preg_match('!^(?:(?:Location)|(?:URI)|(?:location)): ([^\s]+)[\r\n]!m', $res, $matches)) {
		// Recursively validate the URL if an additional redirect is allowed..
		if ($redirectsAllowed >= 1) return rtadmin_validate_url(preg_match('!^https?://!', $matches[1]) ? $matches[1] : $data['scheme'] . '://' . $data['host'] . ($data['path'] !== '' && strpos($matches[1], '/') !== 0  ? $data['path'] : (strpos($matches[1], '/') === 0 ? '' : '/')) . $matches[1], $useGet, $redirectsAllowed-1);
		return false;
	}

	// If it's not found or there is an error condition
	if(($http_status_no == 403 || $http_status_no == 404 || $http_status_no == 405 || $http_status_no == 500 || strstr($res, 'Bad Request') || strstr($res, 'Bad HTTP Request') || trim($res) == '') && !$useGet) {
		return rtadmin_validate_url($url, true, $redirectsAllowed-1);
	}

	return false;
}

function smarty_rtadmin_validate_url ($search, $errors) {
	// Make sure any prior content is flushed to the user's browser.
	flush();
	ob_flush();

	if (!is_array($errors)) $errors = array();

	if (!rtadmin_validate_url($search->getUrl())) $errors[] = array('url' => $search->getUrl(), 'id' => $search->getSearchId());
	if ($search->getSearchUrl() && !rtadmin_validate_url($search->getSearchUrl())) $errors[] = array('url' => $search->getSearchUrl(), 'id' => $search->getSearchId());

	return $errors;
}

?>
