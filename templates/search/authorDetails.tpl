{**
 * authorDetails.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Index of published papers by author.
 *
 * $Id$
 *}

{assign var="pageTitle" value="search.authorDetails"}
{include file="common/header.tpl"}

<h3>{$lastName}, {$firstName}{if $middleName} {$middleName}{/if}{if $affiliation} {$affiliation}{/if}</h3>
<ul>
{foreach from=$publishedPapers item=paper}
	{assign var=eventId value=$paper->getEventId()}
	{assign var=event value=$events[$eventId]}
	{assign var=eventUnavailable value=$eventsUnavailable.$eventId}
	{assign var=trackId value=$paper->getTrackId()}
	{assign var=track value=$tracks[$trackId]}
	{if $event->getEnabled() && !$eventUnavailable}
	<li>

		<i><a href="{url event=$event->getPath()}">{$event->getFullTitle()|escape}</a> - {$track->getTitle()|escape}</i><br />
		{$paper->getPaperTitle()|strip_unsafe_html}<br/>
		<a href="{url event=$event->getPath() page="paper" op="view" path=$paper->getBestPaperId()}" class="file">{translate key="paper.abstract"}</a>
		{if $paper->getAccessStatus()}
		{foreach from=$paper->getGalleys() item=galley name=galleyList}
			&nbsp;<a href="{url event=$event->getPath() page="paper" op="view" path=$paper->getBestPaperId()|to_array:$galley->getGalleyId()}" class="file">{$galley->getLabel()|escape}</a>
		{/foreach}
		{/if}
	</li>
	{/if}
{/foreach}
</ul>

{include file="common/footer.tpl"}
