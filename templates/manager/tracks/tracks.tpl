{**
 * tracks.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of tracks in conference management.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="track.tracks"}
{include file="common/header.tpl"}
{/strip}

<br/>

<div id="tracks">
<table width="100%" class="listing">
	<tr>
		<td class="headseparator" colspan="3">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="60%">{translate key="track.title"}</td>
		<td width="25%">{translate key="track.abbreviation"}</td>
		<td width="15%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td class="headseparator" colspan="3">&nbsp;</td>
	</tr>
{iterate from=tracks item=track name=tracks}
	<tr valign="top">
		<td>{$track->getLocalizedTitle()|escape}</td>
		<td>{$track->getLocalizedAbbrev()|escape}</td>
		<td align="right" class="nowrap">
			<a href="{url op="editTrack" path=$track->getId()}" class="action">{translate key="common.edit"}</a>&nbsp;|&nbsp;<a href="{url op="deleteTrack" path=$track->getId()}" onclick="return confirm('{translate|escape:"jsparam" key="manager.tracks.confirmDelete"}')" class="action">{translate key="common.delete"}</a>&nbsp;|&nbsp;<a href="{url op="moveTrack" d=u trackId=$track->getId()}">&uarr;</a>&nbsp;<a href="{url op="moveTrack" d=d trackId=$track->getId()}">&darr;</a>
		</td>
	</tr>
	<tr>
		<td colspan="3" class="{if $tracks->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $tracks->wasEmpty()}
	<tr>
		<td colspan="3" class="nodata">{translate key="manager.tracks.noneCreated"}</td>
	</tr>
	<tr>
		<td colspan="3" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td align="left">{page_info iterator=$tracks}</td>
		<td colspan="2" align="right">{page_links anchor="tracks" name="tracks" iterator=$tracks}</td>
	</tr>
{/if}
</table>

<a class="action" href="{url op="createTrack"}">{translate key="manager.tracks.create"}</a>
</div>
{include file="common/footer.tpl"}
