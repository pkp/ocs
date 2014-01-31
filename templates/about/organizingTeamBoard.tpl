{**
 * organizingTeam.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Conference index.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="about.organizingTeam"}
{include file="common/header.tpl"}
{/strip}

{foreach from=$groups item=group}
	<h4>{$group->getLocalizedTitle()}</h4>
	{assign var=groupId value=$group->getId()}
	{assign var=members value=$teamInfo[$groupId]}

	<ol class="organizingTeam">
		{foreach from=$members item=member}
			{assign var=user value=$member->getUser()}
			<li><a href="javascript:openRTWindow('{url op="organizingTeamBio" path=$user->getId()}')">{$user->getFullName()|escape}</a>{if $user->getAffiliation()}, {$user->getAffiliation()|escape}{/if}{if $user->getCountry()}{assign var=countryCode value=$user->getCountry()}{assign var=country value=$countries.$countryCode}, {$country|escape}{/if}</li>
		{/foreach}{* $members *}
	</ol>
{/foreach}{* $groups *}

{include file="about/conferenceSponsorship.tpl"}

{include file="common/footer.tpl"}
