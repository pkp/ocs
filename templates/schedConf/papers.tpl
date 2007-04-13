{**
 * papers.tpl
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Issue
 *
 * $Id$
 *}

{assign var="pageTitle" value="schedConf.presentations"}
{include file="common/header.tpl"}

{if $mayViewProceedings}
	{foreach name=tracks from=$publishedPapers item=track key=trackId}
		{if $track.title}<h4>{$track.title|escape}</h4>{/if}

		{foreach from=$track.papers item=paper}
			<table width="100%">
			<tr valign="top">
				<td width="75%">{$paper->getPaperTitle()|strip_unsafe_html}</td>
				<td align="right" width="25%">

					{if !$mayViewPapers || $paper->getAbstract() != ""}<a href="{url page="paper" op="view" path=$paper->getBestPaperId($currentConference)}" class="file">{if $paper->getAbstract() == ""}{translate key="paper.details"}{else}{translate key="paper.abstract"}{/if}</a>{/if}

					{if $mayViewPapers}
					{foreach from=$paper->getGalleys() item=galley name=galleyList}
						<a href="{url page="paper" op="view" path=$paper->getBestPaperId($currentConference)|to_array:$galley->getGalleyId()}" class="file">{$galley->getLabel()|escape}</a>
					{/foreach}
					{/if}
				</td>
			</tr>
			<tr>
				<td style="padding-left: 30px;font-style: italic;">
					{foreach from=$paper->getPresenters() item=presenter name=presenterList}
						{$presenter->getFullName()|escape}{if !$smarty.foreach.presenterList.last},{/if}
					{/foreach}
				</td>
				<td align="right">{$paper->getPages()|escape}</td>
			</tr>
			</table>
		{foreachelse}
			{translate key="presentations.trackEmpty"}
		{/foreach}

		{if !$smarty.foreach.tracks.last}
			<div class="separator"></div>
		{/if}
	{foreachelse}
		{translate key="presentations.schedConfEmpty"}
	{/foreach}
{else} {* notPermitted *}
	{translate key="presentations.notPermitted"}
{/if}

{include file="common/footer.tpl"}

