<?php

/**
 * @defgroup log
 */
 
/**
 * @file EventLogConstants.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup log
 *
 * @brief Contains descriptive constants for paper and conference event logs.
 */

//$Id$

// Log levels
define('LOG_LEVEL_INFO', 'I');
define('LOG_LEVEL_NOTICE', 'N');
define('LOG_LEVEL_WARNING', 'W');
define('LOG_LEVEL_ERROR', 'E');

// Log entry associative types. All types must be defined here
define('LOG_TYPE_DEFAULT', 			0);
define('LOG_TYPE_AUTHOR', 			0x01);
define('LOG_TYPE_DIRECTOR', 			0x02);
define('LOG_TYPE_REVIEW', 			0x03);
define('LOG_TYPE_FILE', 			0x04);

?>
