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

{translate key="user.register.selectSchedConf"}:
<ul>
{iterate from=schedConfs item=schedConf}
	<li>
		{if $source}
			<a href="{url schedConf=$schedConf->getPath() page="user" op="register" source=$source|escape}">{$schedConf->getFullTitle()|escape}</a>
		{else}
			<a href="{url schedConf=$schedConf->getPath() page="user" op="register"}">{$schedConf->getFullTitle()|escape}</a>
		{/if}
	</li>
{/iterate}
</ul>
{if $schedConfs->wasEmpty()}
	{translate key="user.register.noSchedConfs"}
{/if}

{include file="common/footer.tpl"}
