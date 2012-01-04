{**
 * index.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
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

<div id="timeBlockSettings">
	{assign var=enableTimeBlocks value=$currentSchedConf->getSetting('enableTimeBlocks')}
	<form action="{url op="saveSchedulerSettings"}" method="post">
		<input type="radio" name="enableTimeBlocks" id="enableTimeBlocks-0" value="0" {if !$enableTimeBlocks}checked="checked" {/if}/>&nbsp;<label for="enableTimeBlocks-0">{translate key="manager.scheduler.disableTimeBlocks"}</label><br />
		<input type="radio" name="enableTimeBlocks" id="enableTimeBlocks-1" value="1" {if $enableTimeBlocks}checked="checked" {/if}/>&nbsp;<label for="enableTimeBlocks-1">{translate key="manager.scheduler.enableTimeBlocks"}</label><br />
		<input type="submit" value="{translate key="common.record"}" class="button defaultButton" />
	</form>
</div>

<div id="roomsAndEvents">
<h3>{translate key="manager.scheduler.roomsAndEvents"}</h3>

<ul class="plain">
	<li>&#187; <a href="{url op="buildings" clearPageContext=1}">{translate key="manager.scheduler.buildingsAndRooms"}</a></li>
	<li>&#187; <a href="{url op="specialEvents" clearPageContext=1}">{translate key="manager.scheduler.specialEvents"}</a></li>
</ul>
</div>
<div id="scheduling">
<h3>{translate key="manager.scheduler.scheduling"}</h3>

<ul class="plain">
	<li>&#187; <a href="{url op="schedule"}">{translate key="manager.scheduler.schedule"}</a></li>
	{if $enableTimeBlocks}<li>&#187; <a href="{url op="timeBlocks" clearPageContext=1}">{translate key="manager.scheduler.timeBlocks"}</a></li>{/if}
	<li>&#187; <a href="{url op="scheduleLayout"}">{translate key="manager.scheduler.layout"}</a></li>
</ul>
</div>
{include file="common/footer.tpl"}
