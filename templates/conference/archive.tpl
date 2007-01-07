{**
 * index.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Conference index page. Displayed when a conference, but not an event,
 * has been selected.
 *
 * $Id$
 *}

{assign var="pageTitleTranslated" value=$conferenceTitle}
{include file="common/header.tpl"}

{* Display events. *}
<h3>{translate key="conference.archive"}</h3>
{if not $events->eof()}
	{iterate from=events item=event}
		<h4><a href="{url event=$event->getPath()}">{$event->getFullTitle()|escape}</a></h4>
		<p>{$event->getSetting('location')|nl2br}</p>
		{if $event->getSetting('eventIntroduction')}
			<p>{$event->getSetting('eventIntroduction')|nl2br}</p>
		{/if}
		<p><a href="{url event=$event->getPath()}" class="action">{translate key="site.eventView"}</a> | <a href="{url event=$event->getPath() page="user" op="register"}" class="action">{translate key="site.conferenceRegister"}</a></p>
	{/iterate}
{else}
	{translate key="conference.noCurrentConferences"}
{/if}


{include file="common/footer.tpl"}
