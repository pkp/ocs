{**
 * scheduleForm.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Building form under Scheduler.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="manager.scheduler.schedule"}
{assign var="pageId" value="manager.scheduler.schedule.scheduleForm"}
{include file="common/header.tpl"}
{/strip}

<script type="text/javascript">
<!--
{literal}

// Used to update the actions list when the location of a presentation is
// changed or the corresponding checkbox is toggled. Room ID 0 indicates that
// a room has not been chosen for the presentation.
function changeLocation(paperId) {
	var checkVarName = "paper" + paperId + "RoomExists";
	var isChecked = eval("document.schedule." + checkVarName + ".checked");

	var roomIdVarName = "paper" + paperId + "Room";
	var roomId = eval("document.schedule." + roomIdVarName + ".value");

	if (!isChecked) {
		roomId = 0;
	}

	document.schedule.changes.value += "\n" + paperId + " location " + roomId;
}

function checkScheduled(paperId) {
	var isChecked = eval("document.schedule.paper" + paperId + "DateExists.checked");
	if (!isChecked) {
		eval("document.schedule.paper" + paperId + "DateExists.checked = true;");
		changeDate(paperId);
	}
}

// Used to update the actions list when the date of a paper is changed
// or the corresponding checkbox is toggled. A date of 0 indicates that a
// date has not been chosen for the presentation.
function changeDate(paperId) {
	var checkVarName = "paper" + paperId + "DateExists";
	var isChecked = eval("document.schedule." + checkVarName + ".checked");

	var datePrefixName = "document.schedule.paper" + paperId + "Date";
	var paperMonth = eval(datePrefixName + "Month.value");
	var paperDay = eval(datePrefixName + "Day.value");
	var paperYear = eval(datePrefixName + "Year.value");

	var paperDate;

	if (isChecked) {
		paperDate = paperMonth + "-" + paperDay + "-" + paperYear;
	} else {
		paperDate = "0";
	}

	document.schedule.changes.value += "\n" + paperId + " date " + paperDate;
}

// Used to update the actions list when the time block of a paper is changed
// or the corresponding checkbox is toggled. A time block ID of 0 indicates that
// a time block has not been chosen for the presentation.
function changeTimeBlock(paperId) {
	var checkVarName = "paper" + paperId + "TimeBlockExists";
	var isChecked = eval("document.schedule." + checkVarName + ".checked");

	var timeBlockIdVarName = "paper" + paperId + "TimeBlock";
	var timeBlockId = eval("document.schedule." + timeBlockIdVarName + ".value");

	if (!isChecked) {
		timeBlockId = 0;
	}

	document.schedule.changes.value += "\n" + paperId + " timeBlock " + timeBlockId;
}

// Used to update the actions list when the start or end time of a paper is
// changed.
function changeTime(paperId) {
	var timePrefixName = "document.schedule.paper" + paperId + "StartTime";
	var paperHour = eval(timePrefixName + "Hour.value");
	var paperMinute = eval(timePrefixName + "Minute.value");
	var paperMeridian = eval(timePrefixName + "Meridian.value");

	var paperTime = paperHour + ":" + paperMinute + ' ' + paperMeridian;
	document.schedule.changes.value += "\n" + paperId + " startTime" + " " + paperTime;

	var timePrefixName = "document.schedule.paper" + paperId + "EndTime";
	var paperHour = eval(timePrefixName + "Hour.value");
	var paperMinute = eval(timePrefixName + "Minute.value");
	var paperMeridian = eval(timePrefixName + "Meridian.value");

	var paperTime = paperHour + ":" + paperMinute + ' ' + paperMeridian;

	document.schedule.changes.value += "\n" + paperId + " endTime" + " " + paperTime;
}

// Used to sort the display by a certain piece of data
function sortBy(sortName) {
	document.schedule.sort.value = sortName;
	document.schedule.action = "{/literal}{url op="schedule"}{literal}";
	document.schedule.submit();
}

{/literal}
// -->
</script>

{assign var=enableTimeBlocks value=$currentSchedConf->getSetting('enableTimeBlocks')}
{if $enableTimeBlocks}
	<ul class="menu">
		<li class="current"><a href="{url op="schedule"}">{translate key="manager.scheduler.schedule"}</a></li>
		<li><a onclick="return (document.schedule.changes.value == ''?true:confirm('Are you sure you wish to leave the Scheduler? You will lose any changes you have made.'))" href="{url op="timeBlocks"}">{translate key="manager.scheduler.timeBlocks"}</a></li>
	</ul>
{/if}{* $enableTimeBlocks *}

<br/>

<form name="schedule" method="post" action="{url op="saveSchedule"}">
<input name="changes" type="hidden" value="{$changes|escape}" />
<input name="sort" type="hidden" value="{$sort|truncate:20|escape}" />
{include file="common/formErrors.tpl"}

<div id="publishedPapers">

<table width="100%" class="listing">
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
	<tr valign="top" class="heading">
		<td width="5%">{translate key="common.id"}</td>
		<td width="49%">{translate key="paper.title"}</td>
		<td colspan="3" width="46%">{translate key="manager.scheduler.schedule"}</td>
	</tr>
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
	{foreach name=publishedPapers from=$publishedPapers item=publishedPaper}
	{assign var=startTime value=$publishedPaper->getStartTime()}
	{assign var=endTime value=$publishedPaper->getEndTime()}
	<tr valign="top">
		<td rowspan="{if $enableTimeBlocks}2{else}4{/if}">{$publishedPaper->getId()|escape}</td>
		<td rowspan="{if $enableTimeBlocks}2{else}4{/if}">
			<input name="paperIds[]" type="hidden" value="{$publishedPaper->getId()|escape}" />
			{$publishedPaper->getLocalizedTitle()|escape} ({$publishedPaper->getTrackTitle()|escape})<br />
			<em>{$publishedPaper->getAuthorString()|escape}</em>
		</td>
		<td width="4%"><input id="paper{$publishedPaper->getId()|escape}RoomExists" type="checkbox" {if $publishedPaper->getRoomId()}checked="checked" {/if}name="paper{$publishedPaper->getId()|escape}RoomExists" onchange="changeLocation({$publishedPaper->getId()|escape});" /></td>
		<td width="9%">{fieldLabel name="paper"|concat:$publishedPaper->getId():"RoomExists" key="manager.scheduler.location"}</td>
		<td width="33%">
			<select id="paper{$publishedPaper->getId()}Room" name="paper{$publishedPaper->getId()}Room" onchange="document.schedule.paper{$publishedPaper->getId()|escape}RoomExists.checked = true; changeLocation({$publishedPaper->getId()|escape});" class="selectMenu">
				{foreach from=$buildingsAndRooms key=buildingId item=buildingEntry}
					<option disabled="disabled" value="">{$buildingEntry.building->getBuildingAbbrev()}</option>
					{foreach from=$buildingEntry.rooms key=roomId item=room}
						<option {if $publishedPaper->getRoomId() == $roomId}selected="selected" {/if}value="{$roomId|escape}">&nbsp;&#187;&nbsp;{$room->getRoomAbbrev()|truncate:15:"..."}</option>
					{/foreach}
				{/foreach}
			</select>
		</td>
	</tr>
{if $enableTimeBlocks}
	<tr>
		<td><input type="checkbox" {if $startTime}checked="checked" {/if}id="paper{$publishedPaper->getId()|escape}TimeBlockExists" name="paper{$publishedPaper->getId()|escape}TimeBlockExists" onchange="changeTimeBlock({$publishedPaper->getId()|escape});" /></td>
		<td>{fieldLabel name="paper"|concat:$publishedPaper->getId():"TimeBlockExists" key="common.date"}</td>
		<td>
			{* Kludge: Determine whether or not this is a
			 * non-existent time block, and disable if needed.
			 *}
			{assign var=timeBlockFound value=0}
			{foreach from=$timeBlocks item=timeBlock}
				{if $timeBlock->getStartTime() == $startTime && $timeBlock->getEndTime() == $endTime}
					{assign var=timeBlockFound value=1}
				{/if}
			{/foreach}

			<select {if $startTime && !$timeBlockFound}disabled="disabled" {/if} id="paper{$publishedPaper->getId()}TimeBlock" name="paper{$publishedPaper->getId()}TimeBlock" onchange="document.schedule.paper{$publishedPaper->getId()|escape}TimeBlockExists.checked = true; changeTimeBlock({$publishedPaper->getId()|escape});" class="selectMenu">
				{if $startTime && !$timeBlockFound}
					{* Orphaned time without a time block *}
					<option>{$startTime|date_format:$datetimeFormatShort} &mdash; {$endTime|date_format:$timeFormat}</option>
				{/if}
				{foreach from=$timeBlocks item=timeBlock}
					<option {if $timeBlock->getStartTime() == $startTime && $timeBlock->getEndTime() == $endTime}selected="selected" {/if}value="{$timeBlock->getId()|escape}">{$timeBlock->getStartTime()|date_format:$datetimeFormatShort} &mdash; {$timeBlock->getEndTime()|date_format:$timeFormat}</option>
				{/foreach}
			</select>
		</td>
	</tr>
{else}{* $enableTimeBlocks *}
	<tr>
		<td><input type="checkbox" {if $startTime}checked="checked" {/if}id="paper{$publishedPaper->getId()|escape}DateExists" name="paper{$publishedPaper->getId()|escape}DateExists" onchange="changeDate({$publishedPaper->getId()|escape});" /></td>
		<td>{fieldLabel name="paper"|concat:$publishedPaper->getId():"DateExists" key="common.date"}</td>
		<td>{html_select_date prefix="paper"|concat:$publishedPaper->getId():"Date" all_extra="class=\"selectMenu\" onchange=\"checkScheduled("|concat:$publishedPaper->getId():"); changeDate(":$publishedPaper->getId():");\"" time=$startTime|default:$defaultStartTime start_year=$firstYear end_year=$lastYear}</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>{fieldLabel name="paper"|concat:$publishedPaper->getId():"StartTime" key="manager.scheduler.startTime"}</td>
		<td id="{"paper"|concat:$publishedPaper->getId():"StartTime"}">{html_select_time prefix="paper"|concat:$publishedPaper->getId():"StartTime" all_extra="class=\"selectMenu\" onchange=\"checkScheduled("|concat:$publishedPaper->getId():"); changeTime(":$publishedPaper->getId():");\"" display_seconds=false display_meridian=true use_24_hours=false time=$startTime|default:$defaultStartTime}</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>{fieldLabel name="paper"|concat:$publishedPaper->getId():"EndTime" key="manager.scheduler.endTime"}</td>
		<td id="{"paper"|concat:$publishedPaper->getId():"EndTime"}">
			{html_select_time prefix="paper"|concat:$publishedPaper->getId():"EndTime" all_extra="class=\"selectMenu\" onchange=\"checkScheduled("|concat:$publishedPaper->getId():"); changeTime(":$publishedPaper->getId():");\"" display_seconds=false display_meridian=true use_24_hours=false time=$endTime|default:$defaultStartTime}
		</td>
	</tr>
{/if}{* $enableTimeBlocks *}
	<tr>
		<td colspan="5" class="{if $smarty.foreach.publishedPapers.last}end{/if}separator">&nbsp;</td>
	</tr>
	{/foreach}
	{if empty($publishedPapers)}
	<tr>
		<td colspan="5" class="nodata">{translate key="common.none"}</td>
	</tr>
	<tr>
		<td colspan="5" class="endseparator">&nbsp;</td>
	</tr>
	{/if}
</table>
</div>
<p>
	{translate key="common.sortBy"}
	<a href="javascript:sortBy('startTime');">{translate key="manager.scheduler.startTime"}</a>&nbsp;|
	<a href="javascript:sortBy('author');">{translate key="user.role.author"}</a>&nbsp;|
	<a href="javascript:sortBy('room');">{translate key="paper.location"}</a>&nbsp;|
	<a href="javascript:sortBy('track');">{translate key="track.track"}</a>&nbsp;|
	<a href="javascript:sortBy('title');">{translate key="paper.title"}</a>
</p>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="scheduler"}'" /></p>

</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
