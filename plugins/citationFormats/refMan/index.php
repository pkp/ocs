<?php

/**
 * @defgroup plugins_citationFormats_refMan
 */
 
/**
 * @file plugins/citationFormats/refMan/index.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Wrapper for ReferenceManager citation plugin.
 *
 * @ingroup plugins_citationFormats_refMan
 */

//$Id$

require_once('RefManCitationPlugin.inc.php');

return new RefManCitationPlugin();

?>
