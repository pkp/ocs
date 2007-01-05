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

{translate key="user.register.selectEvent"}:
<ul>
{iterate from=events item=event}
	<li>
		{if $source}
			<a href="{url event=$event->getPath() page="user" op="register" source=$source|escape}">{$event->getFullTitle()|escape}</a>
		{else}
			<a href="{url event=$event->getPath() page="user" op="register"}">{$event->getFullTitle()|escape}</a>
		{/if}
	</li>
{/iterate}
</ul>
{if $events->wasEmpty()}
	{translate key="user.register.noEvents"}
{/if}

{include file="common/footer.tpl"}
