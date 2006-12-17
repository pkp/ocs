{**
 * registerConference.tpl
 *
 * Copyright (c) 2006-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Site registration.
 *
 * $Id$
 *}

{assign var="pageTitle" value="user.register"}
{include file="common/header.tpl"}

{iterate from=events item=event}
	{if !$notFirstEvent}
		{translate key="user.register.selectEvent"}:
		<ul>
		{assign var=notFirstevent value=1}
	{/if}
	<li>
		{if $source}
			<a href="{url event=$event->getPath() page="user" op="register" source=$source|escape}">{$event->getFullTitle()|escape}</a>
		{else}
			<a href="{url event=$event->getPath() page="user" op="register"}">{$event->getFullTitle()|escape}</a>
		{/if}
	</li}
{/iterate}
{if $events->wasEmpty()}
	{translate key="user.register.noEvents"}
{else}
	</ul>
{/if}

{include file="common/footer.tpl"}
