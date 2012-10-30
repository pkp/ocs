{**
 * templates/manager/emails/emails.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of email templates in conference management.
 *
 *}
{strip}
{assign var="pageTitle" value="manager.emails"}
{include file="common/header.tpl"}
{/strip}

{url|assign:preparedEmailsGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.settings.preparedEmails.preparedEmailsGridHandler" op="fetchGrid"}
{load_url_in_div id="preparedEmailsGridDiv" url=$preparedEmailsGridUrl}
