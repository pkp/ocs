{**
 * organizingTeam.tpl
 *
 * Copyright (c) 2000-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Conference index.
 *
 * $Id$
 *}
{assign var="pageTitle" value="about.organizingTeam"}
{include file="common/header.tpl"}

{if count($directors) > 0}
	{if count($directors) == 1}
		<h4>{translate key="user.role.director"}</h4>
	{else}
		<h4>{translate key="user.role.directors"}</h4>
	{/if}

{foreach from=$directors item=director}
	<a href="javascript:openRTWindow('{url op="organizingTeamBio" path=$director->getUserId()}')">{$director->getFullName()|escape}</a>{if $director->getAffiliation()}, {$director->getAffiliation()|escape}{/if}{if $director->getCountry()}{assign var=countryCode value=$director->getCountry()}{assign var=country value=$countries.$countryCode}, {$country|escape}{/if}
	<br/>
{/foreach}
{/if}

{if count($trackDirectors) > 0}
	{if count($trackDirectors) == 1}
		<h4>{translate key="user.role.trackDirector"}</h4>
	{else}
		<h4>{translate key="user.role.trackDirectors"}</h4>
	{/if}

{foreach from=$trackDirectors item=trackDirector}
	<a href="javascript:openRTWindow('{url op="organizingTeamBio" path=$trackDirector->getUserId()}')">{$trackDirector->getFullName()|escape}</a>{if $trackDirector->getAffiliation()}, {$trackDirector->getAffiliation()|escape}{/if}{if $trackDirector->getCountry()}{assign var=countryCode value=$trackDirector->getCountry()}{assign var=country value=$countries.$countryCode}, {$country|escape}{/if}
	<br/>
{/foreach}
{/if}

{include file="about/conferenceSponsorship.tpl"}

{include file="common/footer.tpl"}
