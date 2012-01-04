<?php

/**
 * @defgroup pages_paper
 */
 
/**
 * @file pages/paper/index.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Handle requests for paper functions.
 *
 * @ingroup pages_paper
 */

//$Id$


switch ($op) {
	case 'view':
	case 'viewPDFInterstitial':
	case 'viewDownloadInterstitial':
	case 'viewPaper':
	case 'viewRST':
	case 'viewFile':
	case 'download':
	case 'downloadSuppFile':
		define('HANDLER_CLASS', 'PaperHandler');
		import('pages.paper.PaperHandler');
		break;
}

?>
