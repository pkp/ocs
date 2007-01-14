{**
 * registerSite.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Site registration.
 *
 * $Id$
 *}

{assign var="pageTitle" value="user.register"}
{include file="common/header.tpl"}

{translate key="user.register.selectConference"}:
<ul>
{iterate from=conferences item=conference}
	<li>
		{if $source}
			<a href="{url conference=$conference->getPath() page="user" op="register" source=$source|escape}">{$conference->getTitle()|escape}</a>
		{else}
			<a href="{url conference=$conference->getPath() page="user" op="register"}">{$conference->getTitle()|escape}</a>
		{/if}
	</li>
{/iterate}
</ul>
{if $conferences->wasEmpty()}
	{translate key="user.register.noConferences"}
{/if}

{include file="common/footer.tpl"}
