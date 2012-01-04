<?php

/**
 * @defgroup plugins_citationFormats_bibtex
 */
 
/**
 * @file index.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Wrapper for BibTeX citation plugin.
 *
 * @ingroup plugins_citationFormats_bibtex
 */

//$Id$

require_once('BibtexCitationPlugin.inc.php');

return new BibtexCitationPlugin();

?>
