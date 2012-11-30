{**
 * templates/admin/conferences.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of conferences in site administration.
 *
 *}
{strip}
{assign var="pageTitle" value="conference.conferences"}
{include file="common/header.tpl"}
{/strip}

{url|assign:conferencesUrl router=$smarty.const.ROUTE_COMPONENT component="grid.admin.conference.ConferenceGridHandler" op="fetchGrid"}
{load_url_in_div id="conferenceGridContainer" url=$conferencesUrl}

{include file="common/footer.tpl"}
