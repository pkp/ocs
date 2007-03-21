{**
 * createAccountSite.tpl
 *
 * Copyright (c) 2003-2007 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Site registration.
 *
 * $Id$
 *}

{assign var="pageTitle" value="navigation.account"}
{include file="common/header.tpl"}

{translate key="user.account.selectConference"}:
<ul>
{iterate from=conferences item=conference}
	<li>
		{if $source}
			<a href="{url conference=$conference->getPath() page="user" op="account" source=$source|escape}">{$conference->getTitle()|escape}</a>
		{else}
			<a href="{url conference=$conference->getPath() page="user" op="account"}">{$conference->getTitle()|escape}</a>
		{/if}
	</li>
{/iterate}
</ul>
{if $conferences->wasEmpty()}
	{translate key="user.account.noConferences"}
{/if}

{include file="common/footer.tpl"}
