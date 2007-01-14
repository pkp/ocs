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

{include file="common/header.tpl"}

{iterate from=conferences item=conference}
	{if !$notFirstConference}
		{translate key="user.register.selectConference"}:
		<ul>
		{assign var=notFirstconference value=1}
	{/if}
	<li>
		{if $source}
			<a href="{url conference=$conference->getPath() page="user" op="register" source=$source|escape}">{$conference->getTitle()|escape}</a>
		{else}
			<a href="{url conference=$conference->getPath() page="user" op="register"}">{$conference->getTitle()|escape}</a>
		{/if}
	</li>
{/iterate}
{if $conferences->wasEmpty()}
	{translate key="user.register.noConferences"}
{else}
	</ul>
{/if}

{include file="common/footer.tpl"}
