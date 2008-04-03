{**
 * scheduleForm.tpl
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Building form under Scheduler.
 *
 * $Id$
 *}
{assign var="pageCrumbTitle" value="$scheduleTitle"}
{assign var="pageId" value="manager.scheduler.schedule.scheduleForm"}
{include file="common/header.tpl"}

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
	document.schedule.action = "{/literal}{url op="schedule" escape=false}{literal}";
	document.schedule.submit();
}

{/literal}
// -->
</script>

<br/>

<form name="schedule" method="post" action="{url op="saveSchedule"}">
<input name="changes" type="hidden" value="{$changes|escape}" />
<input name="sort" type="hidden" value="{$sort|truncate:20|escape}" />
{include file="common/formErrors.tpl"}

<a name="publishedPapers"></a>

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
	<tr valign="top">
		<td rowspan="4">{$publishedPaper->getPaperId()|escape}</a></td>
		<td rowspan="4">
			<input name="paperIds[]" type="hidden" value="{$publishedPaper->getPaperId()|escape}" />
			{$publishedPaper->getPaperTitle()|escape}<br />
			<i>{$publishedPaper->getPresenterString()|escape}</i>
		</td>
		<td width="4%"><input id="paper{$publishedPaper->getPaperId()|escape}RoomExists" type="checkbox" {if $publishedPaper->getRoomId()}checked="checked" {/if}name="paper{$publishedPaper->getPaperId()|escape}RoomExists" onchange="changeLocation({$publishedPaper->getPaperId()|escape});" /></td>
		<td width="9%">{fieldLabel name="paper`$publishedPaper->getPaperId()`RoomExists" key="manager.scheduler.location"}</td>
		<td width="33%">
			<select id="paper{$publishedPaper->getPaperId()}Room" name="paper{$publishedPaper->getPaperId()}Room" onchange="document.schedule.paper{$publishedPaper->getPaperId()|escape}RoomExists.checked = true; changeLocation({$publishedPaper->getPaperId()|escape});" class="selectMenu">
				{foreach from=$buildingsAndRooms key=buildingId item=buildingEntry}
					<option disabled="disabled" value="">{$buildingEntry.building->getBuildingAbbrev()}</option>
					{foreach from=$buildingEntry.rooms key=roomId item=room}
						<option {if $publishedPaper->getRoomId() == $roomId}selected="selected" {/if}value="{$roomId|escape}">&nbsp;&#187;&nbsp;{$room->getRoomAbbrev()|truncate:15:"..."}</option>
					{/foreach}
				{/foreach}
			</select>
		</td>
	</tr>
	<tr>
		<td><input type="checkbox" {if $publishedPaper->getStartTime()}checked="checked" {/if}id="paper{$publishedPaper->getPaperId()|escape}DateExists" name="paper{$publishedPaper->getPaperId()|escape}DateExists" onchange="changeDate({$publishedPaper->getPaperId()|escape});" /></td>
		<td>{fieldLabel name="paper`$publishedPaper->getPaperId()`DateExists" key="common.date"}</td>
		<td>{html_select_date prefix="paper`$publishedPaper->getPaperId()`Date" all_extra="class=\"selectMenu\" onchange=\"document.schedule.paper`$publishedPaper->getPaperId()`DateExists.checked = true; changeDate(`$publishedPaper->getPaperId()`);\"" time=$publishedPaper->getStartTime()|default:$defaultStartTime start_year=$firstYear end_year=$lastYear}</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>{fieldLabel name="paper`$publishedPaper->getPaperId()`StartTime" key="manager.scheduler.startTime"}</td>
		<td>{html_select_time prefix="paper`$publishedPaper->getPaperId()`StartTime" all_extra="class=\"selectMenu\" onchange=\"document.schedule.paper`$publishedPaper->getPaperId()`DateExists.checked = true; changeTime(`$publishedPaper->getPaperId()`);\"" display_seconds=false display_meridian=true use_24_hours=false time=$publishedPaper->getStartTime()|default:$defaultStartTime}</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>{fieldLabel name="paper`$publishedPaper->getPaperId()`EndTime" key="manager.scheduler.endTime"}</td>
		<td>
			{html_select_time prefix="paper`$publishedPaper->getPaperId()`EndTime" all_extra="class=\"selectMenu\" onchange=\"changeTime(`$publishedPaper->getPaperId()`);\"" display_seconds=false display_meridian=true use_24_hours=false time=$publishedPaper->getEndTime()|default:$defaultStartTime}
		</td>
	</tr>
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
	<tr>
	{/if}
</table>

<p>
	{translate key="common.sortBy"}
	<a href="javascript:sortBy('startTime');">{translate key="manager.scheduler.startTime"}</a>&nbsp;|
	<a href="javascript:sortBy('presenter');">{translate key="user.role.presenter"}</a>&nbsp;|
	<a href="javascript:sortBy('room');">{translate key="paper.location"}</a>&nbsp;|
	<a href="javascript:sortBy('title');">{translate key="paper.title"}</a>
</p>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="schedules" escape=false}'" /></p>

</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
