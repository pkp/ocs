{**
 * organizingTeam.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Conference index.
 *
 * $Id$
 *}

{assign var="pageTitle" value="about.organizingTeam"}
{include file="common/header.tpl"}

{if count($editors) > 0}
	{if count($editors) == 1}
		<h4>{translate key="user.role.editor"}</h4>
	{else}
		<h4>{translate key="user.role.editors"}</h4>
	{/if}

{foreach from=$editors item=editor}
	<a href="javascript:openRTWindow('{url op="organizingTeamBio" path=$editor->getUserId()}')">{$editor->getFullName()|escape}</a>{if $editor->getAffiliation()}, {$editor->getAffiliation()|escape}{/if}{if $editor->getCountry()}{assign var=countryCode value=$editor->getCountry()}{assign var=country value=$countries.$countryCode}, {$country}{/if}
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
	<a href="javascript:openRTWindow('{url op="organizingTeamBio" path=$trackDirector->getUserId()}')">{$trackDirector->getFullName()|escape}</a>{if $trackDirector->getAffiliation()}, {$trackDirector->getAffiliation()|escape}{/if}{if $trackDirector->getCountry()}{assign var=countryCode value=$trackDirector->getCountry()}{assign var=country value=$countries.$countryCode}, {$country}{/if}
	<br/>
{/foreach}
{/if}

{include file="common/footer.tpl"}
