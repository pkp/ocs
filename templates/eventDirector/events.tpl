{**
 * index.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Conference index page.
 *
 * $Id$
 *}

{assign var="pageTitle" value="director.events"}
{include file="common/header.tpl"}

<h3>{translate key="director.eventManagement"}</h3>

<p>{translate key="director.eventManagement.description"}</p>

{if not $events->eof()}
	{iterate from=events item=event}
		<ul class="plain">
			<li>&#187; <a href="{url event=$event->getPath() page="eventDirector"}" class="action">{$event->getTitle()|escape}</a></li>
		</ul>
	{/iterate}
{else}
	{translate key="event.noScheduledConferences"}
{/if}

<div class="separator"></div>

{include file="common/footer.tpl"}
