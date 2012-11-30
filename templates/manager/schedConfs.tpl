{**
 * templates/manager/schedConfs.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of scheduled conferences in conference management.
 *
 *}
{strip}
{assign var="pageTitle" value="schedConf.scheduledConferences"}
{include file="common/header.tpl"}
{/strip}

{url|assign:schedConfsUrl router=$smarty.const.ROUTE_COMPONENT component="grid.settings.schedConf.SchedConfGridHandler" op="fetchGrid"}
{load_url_in_div id="schedConfGridContainer" url=$schedConfsUrl}

{include file="common/footer.tpl"}
