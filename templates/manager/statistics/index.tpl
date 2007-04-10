{**
 * index.tpl
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display the statistics & reporting page.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.statistics"}
{include file="common/header.tpl"}
<br/>

{include file="manager/statistics/statistics.tpl"}

{* --- Reports deferred for this release ---

<div class="separator">&nbsp;</div>

<br/>

{include file="manager/statistics/reportGenerator.tpl"}

--- *}

{include file="common/footer.tpl"}
