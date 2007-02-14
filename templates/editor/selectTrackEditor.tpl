{**
 * selectTrackEditor.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List editors or track editors and give the ability to select one.
 *
 * $Id$
 *}

{assign var="pageTitle" value=`$roleName`s}
{include file="common/header.tpl"}

<h3>{translate key="editor.paper.selectEditor" roleName=$roleName|translate}</h3>

<form name="submit" method="post" action="{url op="assignEditor" paperId=$paperId}">
	<select name="searchField" size="1" class="selectMenu">
		{html_options_translate options=$fieldOptions selected=$searchField}
	</select>
	<select name="searchMatch" size="1" class="selectMenu">
		<option value="contains"{if $searchMatch == 'contains'} selected="selected"{/if}>{translate key="form.contains"}</option>
		<option value="is"{if $searchMatch == 'is'} selected="selected"{/if}>{translate key="form.is"}</option>
	</select>
	<input type="text" name="search" class="textField" value="{$search|escape}" />&nbsp;<input type="submit" value="{translate key="common.search"}" class="button" />
</form>

<p>{foreach from=$alphaList item=letter}<a href="{url op="assignEditor" paperId=$paperId searchInitial=$letter}">{if $letter == $searchInitial}<strong>{$letter}</strong>{else}{$letter}{/if}</a> {/foreach}<a href="{url op="assignEditor" paperId=$paperId}">{if $searchInitial==''}<strong>{translate key="common.all"}</strong>{else}{translate key="common.all"}{/if}</a></p>

<a name="editors"></a>

<table width="100%" class="listing">
<tr><td colspan="5" class="headseparator">&nbsp;</td></tr>
<tr valign="bottom">
	<td class="heading" width="30%">{translate key="user.name"}</td>
	<td class="heading" width="20%">{translate key="track.tracks"}</td>
	<td class="heading" width="20%">{translate key="submissions.completed"}</td>
	<td class="heading" width="20%">{translate key="submissions.active"}</td>
	<td class="heading" width="10%">{translate key="common.action"}</td>
</tr>
<tr><td colspan="5" class="headseparator">&nbsp;</td></tr>
{iterate from=editors item=editor}
{assign var=editorId value=$editor->getUserId()}
<tr valign="top">
	<td><a class="action" href="{url op="userProfile" path=$editorId}">{$editor->getFullName()}</a></td>
	<td>
		{assign var=thisEditorTracks value=$editorTracks[$editorId]}
		{foreach from=$thisEditorTracks item=track}
			{$track->getTrackAbbrev()|escape}&nbsp;
		{foreachelse}
			&mdash;
		{/foreach}
	</td>
	<td>
		{if $editorStatistics[$editorId] && $editorStatistics[$editorId].complete}
			{$editorStatistics[$editorId].complete}
		{else}
			0
		{/if}
	</td>
	<td>
		{if $editorStatistics[$editorId] && $editorStatistics[$editorId].incomplete}
			{$editorStatistics[$editorId].incomplete}
		{else}
			0
		{/if}
	</td>
	<td><a class="action" href="{url op="assignEditor" paperId=$paperId editorId=$editorId}">{translate key="common.assign"}</a></td>
</tr>
<tr><td colspan="5" class="{if $editors->eof()}end{/if}separator">&nbsp;</td></tr>
{/iterate}
{if $editors->wasEmpty()}
<tr>
<td colspan="5" class="nodata">{translate key="manager.people.noneEnrolled"}</td>
</tr>
<tr><td colspan="5" class="{if $editors->eof()}end{/if}separator">&nbsp;</td></tr>
{else}
	<tr>
		<td colspan="2" align="left">{page_info iterator=$editors}</td>
		<td colspan="3" align="right">{page_links anchor="editors" name="editors" iterator=$editors searchField=$searchField searchMatch=$searchMatch search=$search dateFromDay=$dateFromDay dateFromYear=$dateFromYear dateFromMonth=$dateFromMonth dateToDay=$dateToDay dateToYear=$dateToYear dateToMonth=$dateToMonth paperId=$paperId}</td>
	</tr>
{/if}
</table>

{include file="common/footer.tpl"}
