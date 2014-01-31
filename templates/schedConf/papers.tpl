{**
 * papers.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Issue
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="schedConf.presentations"}
{include file="common/header.tpl"}
{/strip}

{if $mayViewProceedings}
	<form method="post" name="submit" action="{url op="presentations"}">
		<select name="searchField" size="1" class="selectMenu">
			{html_options_translate options=$fieldOptions selected=$searchField}
		</select>
		<select name="searchMatch" size="1" class="selectMenu">
			<option value="contains"{if $searchMatch == 'contains'} selected="selected"{/if}>{translate key="form.contains"}</option>
			<option value="is"{if $searchMatch == 'is'} selected="selected"{/if}>{translate key="form.is"}</option>
		</select>
		<input type="text" size="15" name="search" class="textField" value="{$search|escape}" />
		<input type="submit" value="{translate key="common.search"}" class="button" />
		<br />
		{translate key="user.lastName"}
		{foreach from=$alphaList item=letter}<a href="{url op="presentations" searchInitial=$letter track=$track}">{if $letter == $searchInitial}<strong>{$letter|escape}</strong>{else}{$letter|escape}{/if}</a> {/foreach}<a href="{url op="presentations" track=$track}">{if $searchInitial==''}<strong>{translate key="common.all"}</strong>{else}{translate key="common.all"}{/if}</a>
		<br />
		{translate key="track.track"}: <select name="track" onchange="location.href='{url|escape:"javascript" searchField=$searchField searchMatch=$searchMatch search=$search track="TRACK_ID"}'.replace('TRACK_ID', this.options[this.selectedIndex].value)" size="1" class="selectMenu">{html_options options=$trackOptions selected=$track}</select>
	</form>
	&nbsp;

	{foreach name=tracks from=$publishedPapers item=track key=trackId}
		{if $track.title}<h4>{$track.title|escape}</h4>{/if}

		{foreach from=$track.papers item=paper}
			<table width="100%">
			<tr valign="top">
				<td width="75%">
				{if !$mayViewPapers || $paper->getLocalizedAbstract() != ""}<a href="{url page="paper" op="view" path=$paper->getBestPaperId($currentConference)}">{$paper->getLocalizedTitle()|strip_unsafe_html}</a>{else}{$paper->getLocalizedTitle()|strip_unsafe_html}{/if}
				</td>
				<td align="right" width="25%">
					{if $mayViewPapers && $paper->getStatus() == $smarty.const.STATUS_PUBLISHED}
						{foreach from=$paper->getGalleys() item=galley name=galleyList}
							<a href="{url page="paper" op="view" path=$paper->getBestPaperId($currentConference)|to_array:$galley->getId()}" class="file">{$galley->getGalleyLabel()|escape}</a>
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
			{translate key="presentations.trackEmpty"}
		{/foreach}

		{if !$smarty.foreach.tracks.last}
			<div class="separator"></div>
		{/if}
	{foreachelse}
		<br />
		{translate key="presentations.schedConfEmpty"}
	{/foreach}
{else} {* notPermitted *}
	{translate key="presentations.notPermitted"}
{/if}

{include file="common/footer.tpl"}

