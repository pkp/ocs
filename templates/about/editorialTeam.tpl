{**
 * editorialTeam.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Conference index.
 *
 * $Id$
 *}

{assign var="pageTitle" value="about.editorialTeam"}
{include file="common/header.tpl"}

{if count($editors) > 0}
	{if count($editors) == 1}
		<h4>{translate key="user.role.editor"}</h4>
	{else}
		<h4>{translate key="user.role.editors"}</h4>
	{/if}

{foreach from=$editors item=editor}
	<a href="javascript:openRTWindow('{url op="editorialTeamBio" path=$editor->getUserId()}')">{$editor->getFullName()|escape}</a>{if $editor->getAffiliation()}, {$editor->getAffiliation()|escape}{/if}{if $editor->getCountry()}{assign var=countryCode value=$editor->getCountry()}{assign var=country value=$countries.$countryCode}, {$country}{/if}
	<br/>
{/foreach}
{/if}

{if count($trackEditors) > 0}
	{if count($trackEditors) == 1}
		<h4>{translate key="user.role.trackEditor"}</h4>
	{else}
		<h4>{translate key="user.role.trackEditors"}</h4>
	{/if}

{foreach from=$trackEditors item=trackEditor}
	<a href="javascript:openRTWindow('{url op="editorialTeamBio" path=$trackEditor->getUserId()}')">{$trackEditor->getFullName()|escape}</a>{if $trackEditor->getAffiliation()}, {$trackEditor->getAffiliation()|escape}{/if}{if $trackEditor->getCountry()}{assign var=countryCode value=$trackEditor->getCountry()}{assign var=country value=$countries.$countryCode}, {$country}{/if}
	<br/>
{/foreach}
{/if}

{include file="common/footer.tpl"}
