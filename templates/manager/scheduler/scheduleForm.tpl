{**
 * scheduleForm.tpl
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to schedule presentations and special events.
 *
 * $Id$
 *}
{assign var="pageTitle" value="manager.scheduler.schedule"}
{assign var="pageId" value="manager.scheduler.schedule"}
{include file="common/header.tpl"}

<ul class="menu">
	<li class="current"><a href="{url op="schedule"}" onclick='return (document.schedule.actions.value.replace(/^\s+|\s+$/g,"") == "" || confirm("{translate|escape:"quotes" key="manager.scheduler.schedule.confirmLeave"}"))'>{$pageTitle|translate}</a></li>
	<li><a href="{url op="timeBlocks"}" onclick='return (document.schedule.actions.value.replace(/^\s+|\s+$/g,"") == "" || confirm("{translate|escape:"quotes" key="manager.scheduler.schedule.confirmLeave"}"))'>{translate key="manager.scheduler.timeBlocks"}</a></li>
</ul>

<script type="text/javascript">
<!--
{literal}
var dragging = false;
var x, y;
var e;
var tempx;
var tempy;
var nn = document.getElementById && !document.all;

function dragMouseDown(ev) {
	var dObj; // Drag object
	var topelement;

	// Browser compatibility
	if (nn) {
		dObj = ev.target;
		topelement = "HTML";
	} else {
		dObj = event.srcElement;
		topelement = "BODY";
	}

	// Find the draggable object (rather than potentially a subelement)
	while (dObj.tagName != topelement && dObj.className.indexOf("draggable") == -1) {
		if (nn) {
			dObj = dObj.parentNode;
		} else {
			dObj = dObj.parentElement;
		}
	}

	// If we couldn't find it, abort
	if (dObj.className.indexOf("draggable") == -1) {
		return;
	}

	// We are now dragging a draggable object. Calculate coordinates.
	dragging = true;
	e=dObj;
	tempx=parseInt(e.style.left+0); // int cast
	tempy=parseInt(e.style.top+0); // int cast
	if (nn) {
		x = ev.clientX;
		y = ev.clientY;
	} else {
		x = event.clientX;
		y = event.clientY;
	}
	document.onmousemove=dragMouseMove;
	return false;
}

function getPosition(el) {
	var i, x=0, y=0;
	for (i = el; i; i = i.offsetParent) {
		x += i.offsetLeft;
		y += i.offsetTop;
	}
	return {
		x1: x,
		y1: y,
		x2: x+el.offsetWidth,
		y2: y+el.offsetHeight
	};
}

function dragMouseUp(ev) {
	if (!dragging) return;
	dragging = false;

	var tObj = document.getElementById("scheduleTable"); // Table object
	var xd, yd; // "Average" coordinates of the dropped object

	var dPos = getPosition(e);
	var blockFound = false;

	xd = (dPos.x1 + dPos.x2) / 2;
	yd = (dPos.y1 + dPos.y2) / 2;

	var rows = tObj.tBodies[0].rows;
	for (var i=0; i<rows.length; i++) {
		var row = rows[i];
		var rd = getPosition(row);
		var cells = row.cells;
		for (var j=0; j < cells.length; j++) {
			var cell = cells[j];
			var cd = getPosition(cell);
			if (cell.className.indexOf("droppable") != -1 && cd.x1 <= xd && cd.y1 <= yd && cd.x2 >= xd && cd.y2 >= yd) {
				var cellId = cell.getAttribute("id");
				flashCell(cellId, 2, "#ff8888", "");
				document.schedule.actions.value += "\nSCHEDULE " + e.getAttribute("id") + " " + cellId;
				blockFound = true;
			}
		}
	}
	
	if (!blockFound) {
		document.schedule.actions.value += "\nUNSCHEDULE " + e.getAttribute("id");
	}
}

function dragMouseMove(ev) {
	if (dragging) {
		if (nn) {
			e.style.left = (tempx + ev.clientX - x) + "px";
			e.style.top = (tempy + ev.clientY - y) + "px";
		} else {
			e.style.pixelLeft = tempx + event.clientX - x;
			e.style.pixelTop = tempy + event.clientY - y;
		}
		return false;
	}
}

function flashCell(cellId, count, flashColour, oldColour) {
	var cell = document.getElementById(cellId); // Table object
	if (count % 2) {
		cell.bgColor = flashColour;
		cell.style.borderWidth = "1px";
	} else {
		cell.bgColor = oldColour;
	}
	if (count > 0) {
		window.setTimeout("flashCell(\"" + cell.getAttribute("id") + "\", " + (count-1) + ", \"" + flashColour + "\", \"" + oldColour + "\")", 100);
	}
}

document.onmousedown = dragMouseDown;
document.onmouseup = dragMouseUp;

{/literal}
// -->
</script>

<form name="schedule" method="post" action="{url op="schedule" path="execute"}">

{include file="common/formErrors.tpl"}

<p>{translate key="manager.scheduler.schedule.description"}</p>

<table id="scheduleTable" class="listing" width="100%" border="0">

{assign var=baseDatesCount value=$baseDates|@count}
<tr valign="top"><td colspan="{$baseDatesCount+1}" class="headseparator">&nbsp;</td></tr>

<tr valign="top" class="heading">
	<td width="10%">&nbsp;</td>
	{foreach from=$baseDates item=baseDate}
		<td width="{math equation="(100 - 10)/x" x=$baseDatesCount}%">{$baseDate|date_format:$dateFormatShort}</td>
	{/foreach}
</tr>

<tr valign="top"><td colspan="{$baseDatesCount+1}" class="headseparator">&nbsp;</td></tr>

{foreach name=boundaryTimes from=$boundaryTimes item=boundaryTime key=boundaryTimeKey}
{assign var=nextBoundaryTimeKey value=$boundaryTimeKey+1}
{assign var=nextBoundaryTime value=$boundaryTimes.$nextBoundaryTimeKey}
<tr valign="top"{if $nextBoundaryTime} style="height: {math equation="max((x - y)/1000, 3)" x=$nextBoundaryTime y=$boundaryTime}em;"{/if}>
	<td class="timeRowLabel">
		{$boundaryTime+$baseDate|date_format:$timeFormat}
	</td>
	{foreach from=$baseDates item=baseDate}
		{assign var=timeBlock value=$timeBlockGrid.$baseDate.$boundaryTime.timeBlockStarts}
		{if $timeBlock}{* This is an existing time block; display it and its contents *}
			{assign var="timeBlockId" value=$timeBlock->getTimeBlockId()}
			{assign var="rowspan" value=$timeBlockGrid.$baseDate.$boundaryTime.rowspan}
			<td id="TIME-{$timeBlockId|escape}" class="borderBox droppable"{if $rowspan} rowspan="{$rowspan|escape}"{/if}>
				{$timeBlock->getTimeBlockName()|escape}
				{foreach from=$scheduledEventsByTimeBlockId.$timeBlockId item=event}
					<div id="EVENT-{$event->getSpecialEventId()|escape}" class="draggable floatLeft borderBox schedulerEvent">
						<table class="data">
							<tr valign="top">
								<td colspan="2" class="schedulerEventHeader">{$event->getSpecialEventName()|escape|truncate:35:"..."}</td>
							</tr>
							{assign var=fieldId value=EVENT-`$event->getSpecialEventId()`-ROOM}
							<tr valign="top">
								<td class="label">{fieldLabel for=$fieldId key="manager.scheduler.room"}</td>
								<td class="value">
									<select id="{$fieldId|escape}" name="{$fieldId|escape}" class="selectMenu">
									<option value="UNASSIGN">{translate key="manager.scheduler.room.unassigned"}</option>
									{foreach from=$buildingsAndRooms key=buildingId item=buildingEntry}
										<option disabled="disabled" value="">{$buildingEntry.building->getBuildingAbbrev()}</option>
										{foreach from=$buildingEntry.rooms key=roomId item=room}
											<option value="{$roomId|escape}">&nbsp;&#187;&nbsp;{$room->getRoomAbbrev()}</option>
										{/foreach}
									{/foreach}
									</select>
								</td>
							</tr>
						</table>
					</div>
				{/foreach}
				{foreach from=$scheduledPresentationsByTimeBlockId[$timeBlockId] item=presentation}
					<div id="PRESENTATION-{$presentation->getPaperId()|escape}" class="draggable floatLeft borderBox schedulerPresentation">
						<table class="data">
							<tr valign="top">
								<td colspan="2" class="schedulerPresentationHeader">{$presentation->getPaperTitle()|escape|truncate:35:"..."}</td>
							</tr>
							<tr valign="top">
								<td class="label">{translate key="paper.presenters"}</td>
								<td class="value">{$presentation->getPresenterString()|escape}</td>
							</tr>
							{assign var=fieldId value=PRESENTATION-`$presentation->getPaperId()`-ROOM}
							<tr valign="top">
								<td class="label">{fieldLabel for=$fieldId key="manager.scheduler.room"}</td>
								<td class="value">
									<select id="{$fieldId|escape}" name="{$fieldId|escape}" class="selectMenu">
									<option value="UNASSIGN">{translate key="manager.scheduler.room.unassigned"}</option>
									{foreach from=$buildingsAndRooms key=buildingId item=buildingEntry}
										<option disabled="disabled" value="">{$buildingEntry.building->getBuildingAbbrev()}</option>
										{foreach from=$buildingEntry.rooms key=roomId item=room}
											<option value="{$roomId|escape}">&nbsp;&#187;&nbsp;{$room->getRoomAbbrev()}</option>
										{/foreach}
									{/foreach}
									</select>
								</td>
							</tr>
						</table>
					</div>
				{/foreach}
			</td>
		{elseif !$gridSlotUsed.$baseDate.$boundaryTime}{* This is a "hole" in the schedule *}
			<td class="hole">&nbsp</td>
		{else}{* This is a rowspanned part of a time block; do nothing. *}
		{/if}
	{/foreach}
</tr>

{/foreach}{* timeBlocks *}

</table>

<div id="unscheduledPresentations" style="clear: both; height: 6em;">
<h4>{translate key="manager.scheduler.schedule.unscheduledPresentations"}</h4>

{foreach from=$unscheduledPresentations item=presentation}

<div id="PRESENTATION-{$presentation->getPaperId()|escape}" class="draggable floatLeft borderBox schedulerPresentation">
	<table class="data">
		<tr valign="top">
			<td colspan="2" class="schedulerPresentationHeader">{$presentation->getPaperTitle()|escape|truncate:35:"..."}</td>
		</tr>
		<tr valign="top">
			<td class="label">{translate key="paper.presenters"}</td>
			<td class="value">{$presentation->getPresenterString()|escape}</td>
		</tr>
		{assign var=fieldId value=PRESENTATION-`$presentation->getPaperId()`-ROOM}
		<tr valign="top">
			<td class="label">{fieldLabel for=$fieldId key="manager.scheduler.room"}</td>
			<td class="value">
				<select id="{$fieldId|escape}" name="{$fieldId|escape}" class="selectMenu">
				<option value="UNASSIGN">{translate key="manager.scheduler.room.unassigned"}</option>
				{foreach from=$buildingsAndRooms key=buildingId item=buildingEntry}
					<option disabled="disabled" value="">{$buildingEntry.building->getBuildingAbbrev()}</option>
					{foreach from=$buildingEntry.rooms key=roomId item=room}
						<option value="{$roomId|escape}">&nbsp;&#187;&nbsp;{$room->getRoomAbbrev()}</option>
					{/foreach}
				{/foreach}
				</select>
			</td>
		</tr>
	</table>
</div>

{/foreach}
{if $unscheduledPresentations|@count == 0}
	<i>{translate key="common.none"}</i><br/>
{/if}

</div>

<div id="unscheduledEvents" style="clear: both; height: 6em;">
<h4>{translate key="manager.scheduler.schedule.unscheduledEvents"}</h4>
{foreach from=$unscheduledEvents item=event key=eventIndex}

<div id="EVENT-{$event->getSpecialEventId()|escape}" class="draggable floatLeft borderBox schedulerEvent">
	<table class="data">
		<tr valign="top">
			<td colspan="2" class="schedulerEventHeader">{$event->getSpecialEventName()|escape|truncate:35:"..."}</td>
		</tr>
		{assign var=fieldId value=EVENT-`$event->getSpecialEventId()`-ROOM}
		<tr valign="top">
			<td class="label">{fieldLabel for=$fieldId key="manager.scheduler.room"}</td>
			<td class="value">
				<select id="{$fieldId|escape}" name="{$fieldId|escape}" class="selectMenu">
				<option value="UNASSIGN">{translate key="manager.scheduler.room.unassigned"}</option>
				{foreach from=$buildingsAndRooms key=buildingId item=buildingEntry}
					<option disabled="disabled" value="">{$buildingEntry.building->getBuildingAbbrev()}</option>
					{foreach from=$buildingEntry.rooms key=roomId item=room}
						<option value="{$roomId|escape}">&nbsp;&#187;&nbsp;{$room->getRoomAbbrev()}</option>
					{/foreach}
				{/foreach}
				</select>
			</td>
		</tr>
	</table>
</div>

{/foreach}

</div>

<p style="clear: both;"><input type="submit" value="{translate key="common.done"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="scheduler" escape=false}'" /></p>

<input type="hidden" name="actions" value="{$actions|escape}">

</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
