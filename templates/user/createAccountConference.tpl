{**
 * createAccountConference.tpl
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Site account creation.
 *
 * $Id$
 *}
{assign var="pageTitle" value="navigation.account"}
{include file="common/header.tpl"}

{translate key="user.account.selectSchedConf"}:
<ul>
{iterate from=schedConfs item=schedConf}
	<li>
		{if $source}
			<a href="{url schedConf=$schedConf->getPath() page="user" op="account" source=$source|escape}">{$schedConf->getFullTitle()|escape}</a>
		{else}
			<a href="{url schedConf=$schedConf->getPath() page="user" op="account"}">{$schedConf->getFullTitle()|escape}</a>
		{/if}
	</li>
{/iterate}
</ul>
{if $schedConfs->wasEmpty()}
	{translate key="user.account.noSchedConfs"}
{/if}

{include file="common/footer.tpl"}
