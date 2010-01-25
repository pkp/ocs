<?php

/**
 * @defgroup pages_sitemap
 */
 
/**
 * @file pages/sitemap/index.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_sitemap
 * @brief Produce a sitemap in XML format for submitting to search engines. 
 *
 */

// $Id$


switch ($op) {
	case 'index':
		define('HANDLER_CLASS', 'SitemapHandler');
		import('pages.sitemap.SitemapHandler');
		break;
}

?>
