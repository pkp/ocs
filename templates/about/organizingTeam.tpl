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

{if count($directors) > 0}
	{if count($directors) == 1}
		<h4>{translate key="user.role.director"}</h4>
	{else}
		<h4>{translate key="user.role.directors"}</h4>
	{/if}

	<ol class="organizingTeam">
		{foreach from=$directors item=director}
			<li><a href="javascript:openRTWindow('{url op="organizingTeamBio" path=$director->getId()}')">{$director->getFullName()|escape}</a>{if $director->getAffiliation()}, {$director->getAffiliation()|escape}{/if}{if $director->getCountry()}{assign var=countryCode value=$director->getCountry()}{assign var=country value=$countries.$countryCode}, {$country|escape}{/if}</li>
		{/foreach}
	</ol>
{/if}

{if count($trackDirectors) > 0}
	{if count($trackDirectors) == 1}
		<h4>{translate key="user.role.trackDirector"}</h4>
	{else}
		<h4>{translate key="user.role.trackDirectors"}</h4>
	{/if}

	<ol class="organizingTeam">
		{foreach from=$trackDirectors item=trackDirector}
			<li><a href="javascript:openRTWindow('{url op="organizingTeamBio" path=$trackDirector->getId()}')">{$trackDirector->getFullName()|escape}</a>{if $trackDirector->getAffiliation()}, {$trackDirector->getAffiliation()|escape}{/if}{if $trackDirector->getCountry()}{assign var=countryCode value=$trackDirector->getCountry()}{assign var=country value=$countries.$countryCode}, {$country|escape}{/if}</li>
		{/foreach}
	</ol>
{/if}

{include file="about/conferenceSponsorship.tpl"}

{include file="common/footer.tpl"}
