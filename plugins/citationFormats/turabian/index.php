<?php

/**
 * @defgroup plugins_citationFormats_turabian
 */
 
/**
 * @file plugins/citationFormats/turabian/index.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Wrapper for Turabian citation plugin.
 *
 * @ingroup plugins_citationFormats_turabian
 */

//$Id$

require_once('TurabianCitationPlugin.inc.php');

return new TurabianCitationPlugin();

?>
