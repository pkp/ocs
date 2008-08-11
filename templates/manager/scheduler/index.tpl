{**
 * index.tpl
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Main landing page for the Scheduler.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="manager.scheduler"}
{include file="common/header.tpl"}
{/strip}

<p>{translate key="manager.scheduler.description"}</p>

<h3>{translate key="manager.scheduler.roomsAndEvents"}</h3>

<ul class="plain">
	<li>&#187; <a href="{url op="buildings" clearPageContext=1}">{translate key="manager.scheduler.buildingsAndRooms"}</a></li>
	<li>&#187; <a href="{url op="specialEvents" clearPageContext=1}">{translate key="manager.scheduler.specialEvents"}</a></li>
</ul>

<h3>{translate key="manager.scheduler.scheduling"}</h3>

<ul class="plain">
	<li>&#187; <a href="{url op="schedule"}">{translate key="manager.scheduler.schedule"}</a></li>
</ul>

{include file="common/footer.tpl"}
