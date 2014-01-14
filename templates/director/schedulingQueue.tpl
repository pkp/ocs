{**
 * schedulingQueue.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Papers waiting to be scheduled for publishing.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="director.schedulingQueue"}
{url|assign:"currentUrl" op="schedulingQueue"}
{include file="common/header.tpl"}
{/strip}

<ul class="menu">
	<li><a href="{url op="createIssue"}">{translate key="director.navigation.createIssue"}</a></li>
	<li class="current"><a href="{url op="schedulingQueue"}">{translate key="common.queue.short.submissionsInScheduling"}</a></li>
	<li><a href="{url op="futureIssues"}">{translate key="director.navigation.futureIssues"}</a></li>
	<li><a href="{url op="backIssues"}">{translate key="director.navigation.issueArchive"}</a></li>
</ul>

<br/>

<form action="#">{translate key="track.track"}:&nbsp;<select name="track" onchange="location.href='{url|escape:"javascript" track="TRACK_ID"}'.replace('TRACK_ID', this.options[this.selectedIndex].value)" size="1" class="selectMenu">{html_options options=$trackOptions selected=$track}</select></form>

<br />

<form method="post" action="{url op="updateSchedulingQueue"}" onsubmit="return confirm('{translate|escape:"jsparam" key="director.schedulingQueue.saveChanges"}')">

<div id="papers">
<table class="listing" width="100%">
	<tr>
		<td colspan="7" class="headseparator">&nbsp;</td>
	</tr>
	<tr valign="bottom" class="heading">
		<td width="5%">{translate key="common.id"}</td>
		<td width="5%"><span class="disabled">MM-DD</span><br />{translate key="submissions.submit"}</td>
		<td width="5%">{translate key="submissions.track"}</td>
		<td width="20%">{translate key="paper.authors"}</td>
		<td width="35%">{translate key="paper.title"}</td>
		<td width="20%">{translate key="director.schedulingQueue.schedule"}</td>
		<td width="10%">{translate key="common.remove"}</td>
	</tr>
	<tr>
		<td colspan="7" class="headseparator">&nbsp;</td>
	</tr>
	{iterate from=schedulingQueueSubmissions item=submission}
	<tr valign="top">
		<td>{$submission->getPaperId()}</td>
		<td>{$submission->getDateSubmitted()|date_format:$dateFormatTrunc}</td>
		<td>{$submission->getTrackAbbrev()|escape}</td>
		<td>{$submission->getAuthorString(true)|truncate:40:"..."|escape}</td>
		<td><a href="{url op="submission" path=$submission->getPaperId()}" class="action">{$submission->getLocalizedTitle()|strip_tags|truncate:40:"..."|default:"&mdash;"}</a></td>
		<td><select name="schedule[{$submission->getPaperId()}]" class="selectMenu">{html_options options=$issueOptions|truncate:40:"..."}</select></td>
		<td width="10%"><input type="checkbox" name="remove[]" value="{$submission->getPaperId()}" /></td>
	</tr>
	<tr>
		<td colspan="7" class="{if $schedulingQueueSubmissions->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $schedulingQueueSubmissions->wasEmpty()}
	<tr>
		<td colspan="7" class="nodata">{translate key="submissions.noSubmissions"}</td>
	</tr>
	<tr>
		<td colspan="7" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="4" align="left">{page_info iterator=$schedulingQueueSubmissions}</td>
		<td colspan="3" align="right">{page_links anchor="papers" name="papers" iterator=$schedulingQueueSubmissions track=$track}</td>
	</tr>
{/if}
</table>

<input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" />
</form>
</div>
{include file="common/footer.tpl"}
