{**
 * index.tpl
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Main landing page for the Scheduler.
 *
 * $Id$
 *}
{assign var="pageTitle" value="manager.scheduler"}
{include file="common/header.tpl"}

<p>{translate key="manager.scheduler.description"}</p>

<h3>{translate key="manager.scheduler.roomsAndEvents"}</h3>

<ul class="plain">
	<li>&#187; <a href="{url op="buildings"}">{translate key="manager.scheduler.buildingsAndRooms"}</a></li>
	<li>&#187; <a href="{url op="specialEvents"}">{translate key="manager.scheduler.specialEvents"}</a></li>
</ul>

<h3>{translate key="manager.scheduler.scheduling"}</h3>

<ul class="plain">
	<li>&#187; <a href="{url op="schedule"}">{translate key="manager.scheduler.schedule"}</a></li>
	<li>&#187; <a href="{url op="timeBlocks"}">{translate key="manager.scheduler.timeBlocks"}</a></li>
</ul>

{include file="common/footer.tpl"}
