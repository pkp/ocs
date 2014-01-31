<?php

/**
 * @defgroup plugins_paymethod_manual
 */
 
/**
 * @file plugins/paymethod/manual/index.php
 *
 * Copyright (c) 2000-2012 John Willinsky
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
