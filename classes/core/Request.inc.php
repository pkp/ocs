<?php

/**
 * Request.inc.php
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package core
 *
 * Class providing operations associated with HTTP requests.
 * Requests are assumed to be in the format:
 *    http://host.tld/index.php
 *                   /<conference_id>
 *                   /<sched_conf_id>
 *                   /<page_name>
 *                   /<operation_name>
 *                   /<arguments...>
 * <conference_id> is assumed to be "index" for top-level site requests.
 *                 ditto for <sched_conf_id>
 *
 * $Id$
 */

// The base script through which all requests are routed
define('INDEX_SCRIPTNAME', 'index.php');

class Request {
	
	/**
	 * Perform an HTTP redirect to an absolute or relative (to base system URL) URL.
	 * @param $url string (exclude protocol for local redirects) 
	 * @param $includeConference boolean optional, for relative URLs will include the conference path in the redirect URL
	 */
	function redirectUrl($url) {
		if (HookRegistry::call('Request::redirect', array(&$url))) {
			return;
		}
		
		header("Location: $url");
		exit();
	}

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
	function redirect($conferencePath = null, $schedConfPath = null, $page = null,
			$op = null, $path = null, $params = null, $anchor = null) {
		Request::redirectUrl(Request::url($conferencePath, $schedConfPath, $page,
			$op, $path, $params, $anchor));
	}

	/**
	 * Redirect to the current URL, forcing the HTTPS protocol to be used.
	 */
	function redirectSSL() {
		$url = 'https://' . Request::getServerHost() . Request::getRequestPath();
		$queryString = Request::getQueryString();
		if (!empty($queryString)) $url .= "?$queryString";
		Request::redirectUrl($url);
	}
	
	/**
	 * Redirect to the current URL, forcing the HTTP protocol to be used.
	 */
	function redirectNonSSL() {
		$url = 'http://' . Request::getServerHost() . Request::getRequestPath();
		$queryString = Request::getQueryString();
		if (!empty($queryString)) $url .= "?$queryString";
		Request::redirectUrl($url);
	}	

	/**
	 * Get the base URL of the request (excluding script).
	 * @return string
	 */
	function getBaseUrl() {
		static $baseUrl;
		
		if (!isset($baseUrl)) {
			$serverHost = Request::getServerHost(null);
			if ($serverHost !== null) {
				// Auto-detection worked.
				$baseUrl = Request::getProtocol() . '://' . Request::getServerHost() . Request::getBasePath();
			} else {
				// Auto-detection didn't work (e.g. this is a command-line call); use configuration param
				$baseUrl = Config::getVar('general', 'base_url');
			}
			HookRegistry::call('Request::getBaseUrl', array(&$baseUrl));
		}
		
		return $baseUrl;
	}

	/**
	 * Get the base path of the request (excluding trailing slash).
	 * @return string
	 */
	function getBasePath() {
		static $basePath;
		
		if (!isset($basePath)) {
			$basePath = dirname($_SERVER['SCRIPT_NAME']);
			if ($basePath == '/' || $basePath == '\\') {
				$basePath = '';
			}
			HookRegistry::call('Request::getBasePath', array(&$basePath));
		}
		
		return $basePath;
	}

	/**
	 * Get the URL to the index script.
	 * @return string
	 */
	function getIndexUrl() {
		static $indexUrl;

		if (!isset($indexUrl)) {
			$indexUrl = Request::getBaseUrl() . '/' . INDEX_SCRIPTNAME;
			HookRegistry::call('Request::getIndexUrl', array(&$indexUrl));
		}

		return $indexUrl;
	}

	/**
	 * Get the complete URL to this page, including parameters.
	 * @return string
	 */
	function getCompleteUrl() {
		static $completeUrl;

		if (!isset($completeUrl)) {
			$completeUrl = Request::getRequestUrl();
			$queryString = Request::getQueryString();
			if (!empty($queryString)) $completeUrl .= "?$queryString";
			HookRegistry::call('Request::getCompleteUrl', array(&$completeUrl));
		}

		return $completeUrl;
	}

	/**
	 * Get the complete URL of the request.
	 * @return string
	 */
	function getRequestUrl() {
		static $requestUrl;
		
		if (!isset($requestUrl)) {
			$requestUrl = Request::getProtocol() . '://' . Request::getServerHost() . Request::getRequestPath();
			HookRegistry::call('Request::getRequestUrl', array(&$requestUrl));
		}
		
		return $requestUrl;
	}

	/**
	 * Get the complete set of URL parameters to the current request.
	 * @return string
	 */
	function getQueryString() {
		static $queryString;

		if (!isset($queryString)) {
			$queryString = isset($_SERVER['QUERY_STRING'])?$_SERVER['QUERY_STRING']:'';
			HookRegistry::call('Request::getQueryString', array(&$queryString));
		}

		return $queryString;
	}

	/**
	 * Get the completed path of the request.
	 * @return string
	 */
	function getRequestPath() {
		static $requestPath;
		if (!isset($requestPath)) {
			$requestPath = $_SERVER['SCRIPT_NAME'];
			if (Request::isPathInfoEnabled()) {
				$requestPath .= isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
			}
			HookRegistry::call('Request::getRequestPath', array(&$requestPath));
		}
		return $requestPath;
	}
	
	/**
	 * Get the server hostname in the request.
	 * @return string
	 */
	function getServerHost($default = 'localhost') {
		static $serverHost;
		if (!isset($serverHost)) {
			$serverHost = isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST']
				: (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST']
				: (isset($_SERVER['HOSTNAME']) ? $_SERVER['HOSTNAME']
				: $default));
			HookRegistry::call('Request::getServerHost', array(&$serverHost));
		}
		return $serverHost;
	}

	/**
	 * Get the protocol used for the request (HTTP or HTTPS).
	 * @return string
	 */
	function getProtocol() {
		static $protocol;
		if (!isset($protocol)) {
			$protocol = (!isset($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS']) != 'on') ? 'http' : 'https';
			HookRegistry::call('Request::getProtocol', array(&$protocol));
		}
		return $protocol;
	}

	/**
	 * Get the remote IP address of the current request.
	 * @return string
	 */
	function getRemoteAddr() {
		static $ipaddr;
		if (!isset($ipaddr)) {
			if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				$ipaddr = $_SERVER['HTTP_X_FORWARDED_FOR'];
			} else if (isset($_SERVER['REMOTE_ADDR'])) {
				$ipaddr = $_SERVER['REMOTE_ADDR'];
			}
			if (!isset($ipaddr) || empty($ipaddr)) {
				$ipaddr = getenv('REMOTE_ADDR');
			}
			if (!isset($ipaddr) || $ipaddr == false) {
				$ipaddr = '';
			}

			// If multiple addresses are listed, take the first. (Supports ipv6.)
			if (preg_match('/^([0-9.a-fA-F:]+)/', $ipaddr, $matches)) {
				$ipaddr = $matches[1];
			}
			HookRegistry::call('Request::getRemoteAddr', array(&$ipaddr));
		}
		return $ipaddr;
	}
	
	/**
	 * Get the remote domain of the current request
	 * @return string
	 */
	function getRemoteDomain() {
		static $remoteDomain;
		if (!isset($remoteDomain)) {
			$remoteDomain = getHostByAddr(Request::getRemoteAddr());
			HookRegistry::call('Request::getRemoteDomain', array(&$remoteDomain));
		}
	}
	
	/**
	 * Get the user agent of the current request.
	 * @return string
	 */
	function getUserAgent() {
		static $userAgent;
		if (!isset($userAgent)) {
			if (isset($_SERVER['HTTP_USER_AGENT'])) {
				$userAgent = $_SERVER['HTTP_USER_AGENT'];
			}
			if (!isset($userAgent) || empty($userAgent)) {
				$userAgent = getenv('HTTP_USER_AGENT');
			}
			if (!isset($userAgent) || $userAgent == false) {
				$userAgent = '';
			}
			HookRegistry::call('Request::getUserAgent', array(&$userAgent));
		}
		return $userAgent;
	}

	/**
	 * Return true iff PATH_INFO is enabled.
	 */
	function isPathInfoEnabled() {
		static $isPathInfoEnabled;
		if (!isset($isPathInfoEnabled)) {
			$isPathInfoEnabled = Config::getVar('general', 'disable_path_info')?false:true;
		}
		return $isPathInfoEnabled;
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
	
	/**
	 * Get site data.
	 * @return Site
	 */
	 function &getSite() {
	 	static $site;
	 	
	 	if (!isset($site)) {
		 	$siteDao = &DAORegistry::getDAO('SiteDAO');
		 	$site = $siteDao->getSite();
	 	}
	 	
	 	return $site;
	 }
	
	/**
	 * Get the user session associated with the current request.
	 * @return Session
	 */
	 function &getSession() {
	 	static $session;
	 	
	 	if (!isset($session)) {
	 		$sessionManager = &SessionManager::getManager();
	 		$session = $sessionManager->getUserSession();
	 	}
	 	
	 	return $session;
	 }
	
	/**
	 * Get the user associated with the current request.
	 * @return User
	 */
	 function &getUser() {
	 	static $user;
	 	
	 	if (!isset($user)) {
	 		$sessionManager = &SessionManager::getManager();
	 		$session = &$sessionManager->getUserSession();
	 		$user = $session->getUser();
	 	}
	 	
	 	return $user;
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
	 * Get the page requested in the URL.
	 * @return String the page path (under the "pages" directory)
	 */
	function getRequestedPage() {
		static $page;
		
		if (!isset($page)) {
			if (Request::isPathInfoEnabled()) {
				$page = '';
				if (isset($_SERVER['PATH_INFO'])) {
					$vars = explode('/', $_SERVER['PATH_INFO']);
					if (count($vars) >= 4) {
						$page = Core::cleanFileVar($vars[3]);
					}
				}
			} else {
				$page = Request::getUserVar('page');
			}
		}
		
		return $page;
	}
	
	/**
	 * Get the operation requested in the URL (assumed to exist in the requested page handler).
	 * @return string
	 */
	function getRequestedOp() {
		static $op;
		
		if (!isset($op)) {
			if (Request::isPathInfoEnabled()) {
				$op = '';
				if (isset($_SERVER['PATH_INFO'])) {
					$vars = explode('/', $_SERVER['PATH_INFO']);
					if (count($vars) >= 5) {
						$op = Core::cleanFileVar($vars[4]);
					}
				}
			} else {
				return Request::getUserVar('op');
			}
			$op = empty($op) ? 'index' : $op;
		}
		
		return $op;
	}
	
	/**
	 * Get the arguments requested in the URL (not GET/POST arguments, only arguments prepended to the URL separated by "/").
	 * @return array
	 */
	function getRequestedArgs() {
		if (Request::isPathInfoEnabled()) {
			$args = array();
			if (isset($_SERVER['PATH_INFO'])) {
				$vars = explode('/', $_SERVER['PATH_INFO']);
				if (count($vars) > 4) {
					$args = array_slice($vars, 5);
					for ($i=0, $count=count($args); $i<$count; $i++) {
						$args[$i] = Core::cleanVar(get_magic_quotes_gpc() ? stripslashes($args[$i]) : $args[$i]);
					}
				}
			}
		} else {
			$args = Request::getUserVar('path');
			if (empty($args)) $args = array();
			elseif (!is_array($args)) $args = array($args);
		}
		return $args;	
	}
	
	/**
	 * Get the value of a GET/POST variable.
	 * @return mixed
	 */
	function getUserVar($key) {
		static $vars;
		
		if (!isset($vars)) {
			$vars = array_merge($_GET, $_POST);
		}
		
		if (isset($vars[$key])) {
			// FIXME Do not clean vars again if function is called more than once?
			Request::cleanUserVar($vars[$key]);
			return $vars[$key];
		} else {
			return null;
		}
	}

	/**
	 * Get the value of a GET/POST variable generated using the Smarty
	 * html_select_date and/or html_select_time function.
	 * @param $prefix string
	 * @param $defaultDay int
	 * @param $defaultMonth int
	 * @param $defaultYear int
	 * @param $defaultHour int
	 * @param $defaultMinute int
	 * @param $defaultSecond int
	 * @return Date
	 */
	function getUserDateVar($prefix, $defaultDay = null, $defaultMonth = null, $defaultYear = null, $defaultHour = 0, $defaultMinute = 0, $defaultSecond = 0) {
		$monthPart = Request::getUserVar($prefix . 'Month');
		$dayPart = Request::getUserVar($prefix . 'Day');
		$yearPart = Request::getUserVar($prefix . 'Year');
		$hourPart = Request::getUserVar($prefix . 'Hour');
		$minutePart = Request::getUserVar($prefix . 'Minute');
		$secondPart = Request::getUserVar($prefix . 'Second');

		if (empty($dayPart)) $dayPart = $defaultDay;
		if (empty($monthPart)) $monthPart = $defaultMonth;
		if (empty($yearPart)) $yearPart = $defaultYear;
		if (empty($hourPart)) $hourPart = $defaultHour;
		if (empty($minutePart)) $minutePart = $defaultMinute;
		if (empty($secondPart)) $secondPart = $defaultSecond;

		if (empty($monthPart) || empty($dayPart) || empty($yearPart)) return null;
		return mktime($hourPart, $minutePart, $secondPart, $monthPart, $dayPart, $yearPart);
	}

	/**
	 * Sanitize a user-submitted variable (i.e., GET/POST/Cookie variable).
	 * Strips slashes if necessary, then sanitizes variable as per Core::cleanVar().
	 * @param $var mixed
	 */
	function cleanUserVar(&$var, $stripHtml = false) {
		if (isset($var) && is_array($var)) {
			array_walk($var, create_function('&$item,$key', 'Request::cleanUserVar($item, ' . ');'));
		
		} else if (isset($var)) {
			$var = Core::cleanVar(get_magic_quotes_gpc() ? stripslashes($var) : $var);
			
		} else {
			return null;
		}
	}
	
	/**
	 * Get the value of a cookie variable.
	 * @return mixed
	 */
	function getCookieVar($key) {
		if (isset($_COOKIE[$key])) {
			$value = $_COOKIE[$key];
			Request::cleanUserVar($value);
			return $value;
		} else {
			return null;
		}
	}
	
	/**
	 * Set a cookie variable.
	 * @param $key string
	 * @param $value mixed
	 */
	function setCookieVar($key, $value) {
		setcookie($key, $value, 0, Request::getBasePath());
		$_COOKIE[$key] = $value;
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
		$pathInfoDisabled = !Request::isPathInfoEnabled();

		$amp = $escape?'&amp;':'&';
		$prefix = $pathInfoDisabled?$amp:'?';

		// Establish defaults for page and op
		$defaultPage = Request::getRequestedPage();
		$defaultOp = Request::getRequestedOp();

		if($page == 'install') {
			$conferencePath = 'index';
			$schedConfPath = 'index';
		} else {
			if (isset($conferencePath)) {
				$conferencePath = rawurlencode($conferencePath);
				$conferencePathProvided = true;
			} else {
				$conference =& Request::getConference();
				if ($conference) $conferencePath = $conference->getPath();
				else $conferencePath = 'index';
			}

			if(isset($schedConfPath)) {
				$schedConfPath = rawurlencode($schedConfPath);
				$schedConfPathProvided = true;
			} else {
				$schedConf =& Request::getSchedConf();
				if ($schedConf) $schedConfPath = $schedConf->getPath();
				else $schedConfPath = 'index';
			}
		}

		// If a conference and scheduled conference have been specified, don't supply default
		// page or op.
		if(isset($schedConfPathProvided) || isset($conferencePathProvided)) {
			$defaultPage = null;
			$defaultOp = null;
		}

		// Get overridden base URLs (if available).
		$overriddenBaseUrl = Config::getVar('general', "base_url[$conferencePath]");

		// If a page has been specified, don't supply a default op.
		if ($page) {
			$page = rawurlencode($page);
			$defaultOp = null;
		} else {
			$page = $defaultPage;
		}

		// Encode the op.
		if ($op) $op = rawurlencode($op);
		else $op = $defaultOp;

		// Process additional parameters
		$additionalParams = '';
		if (!empty($params)) foreach ($params as $key => $value) {
			if (is_array($value)) foreach($value as $element) {
				$additionalParams .= $prefix . $key . '%5B%5D=' . rawurlencode($element);
				$prefix = $amp;
			} else {
				$additionalParams .= $prefix . $key . '=' . rawurlencode($value);
				$prefix = $amp;
			}
		}

		// Process anchor
		if (!empty($anchor)) $anchor = '#' . rawurlencode($anchor);
		else $anchor = '';

		if (!empty($path)) {
			if (is_array($path)) $path = array_map('rawurlencode', $path);
			else $path = array(rawurlencode($path));
			if (!$page) $page = 'index';
			if (!$op) $op = 'index';
		}

		$pathString = '';
		if ($pathInfoDisabled) {
			$joiner = $amp . 'path%5B%5D=';
			if (!empty($path)) $pathString = $joiner . implode($joiner, $path);
			if (empty($overriddenBaseUrl)) $baseParams = "?conference=$conferencePath&schedConf=$schedConfPath";
			else $baseParams = '';

			if (!empty($page) || !empty($overriddenBaseUrl)) {
				$baseParams .= empty($baseParams)?'?':$amp . "page=$page";
				if (!empty($op)) {
					$baseParams .= $amp . "op=$op";
				}
			}
		} else {
			if (!empty($path)) $pathString = '/' . implode('/', $path);
			if (empty($overriddenBaseUrl)) $baseParams = "/$conferencePath/$schedConfPath";
			else $baseParams = '';

			if (!empty($page)) {
				$baseParams .= "/$page";
				if (!empty($op)) {
					$baseParams .= "/$op";
				}
			}
		}

		return ((empty($overriddenBaseUrl)?Request::getIndexUrl():$overriddenBaseUrl) . $baseParams . $pathString . $additionalParams . $anchor);
	}
}

?>
