<?php

/**
 * @defgroup plugins_paymethod_manual
 */
 
/**
 * @file index.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Wrapper for manual payment plugin.
 *
 * @ingroup plugins_paymethod_manual
 */

//$Id$

require_once('ManualPaymentPlugin.inc.php');

return new ManualPaymentPlugin();

?> 
