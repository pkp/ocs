{**
 * papers.tpl
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Issue
 *
 * $Id$
 *}

{include file="event/header.tpl"}

{if not $notPermitted}
	{foreach name=tracks from=$publishedPapers item=track key=trackId}
		{if $track.title}<h4>{$track.title|escape}</h4>{/if}

		{foreach from=$track.papers item=paper}
			<table width="100%">
			<tr valign="top">
				<td width="75%">{$paper->getPaperTitle()|strip_unsafe_html}</td>
				<td align="right" width="25%">
					{if (!$registrationRequired || $paper->getAccessStatus() || $registeredUser || $registeredDomain)}
						{assign var=hasAccess value=1}
					{else}
						{assign var=hasAccess value=0}
					{/if}

					{if !$hasAccess || $paper->getAbstract() != ""}<a href="{url page="paper" op="view" path=$paper->getBestPaperId($currentConference)}" class="file">{translate key="paper.abstract"}</a>{/if}

					{if $hasAccess}
					{foreach from=$paper->getGalleys() item=galley name=galleyList}
						<a href="{url page="paper" op="view" path=$paper->getBestPaperId($currentConference)|to_array:$galley->getGalleyId()}" class="file">{$galley->getLabel()|escape}</a>
					{/foreach}
					{/if}
				</td>
			</tr>
			<tr>
				<td style="padding-left: 30px;font-style: italic;">
					{foreach from=$paper->getAuthors() item=author name=authorList}
						{$author->getFullName()|escape}{if !$smarty.foreach.authorList.last},{/if}
					{/foreach}
				</td>
				<td align="right">{$paper->getPages()|escape}</td>
			</tr>
			</table>
		{foreachelse}
			{translate key="proceedings.trackEmpty"}
		{/foreach}

		{if !$smarty.foreach.tracks.last}
			<div class="separator"></div>
		{/if}
	{foreachelse}
		{translate key="proceedings.eventEmpty"}
	{/foreach}
{else} {* notPermitted *}
	{if $releasedToParticipants}
		{translate key="proceedings.subscribersOnly"}
	{else}
		{translate key="proceedings.notReleasedYet"}
	{/if}
{/if}

{include file="common/footer.tpl"}

