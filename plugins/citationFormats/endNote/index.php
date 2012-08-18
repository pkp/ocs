<?php

/**
 * @defgroup plugins_citationFormats_endNote
 */
 
/**
 * @file plugins/citationFormats/endNote/index.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Wrapper for EndNote citation plugin.
 *
 * @ingroup plugins_citationFormats_endNote
 */


require_once('EndNoteCitationPlugin.inc.php');

return new EndNoteCitationPlugin();

?>
