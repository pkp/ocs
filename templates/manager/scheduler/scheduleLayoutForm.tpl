{**
 * scheduleLayoutForm.tpl
 *
 * Copyright (c) 2000-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Schedule layout form under scheduler (allows customization of schedules).
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="manager.scheduler.layout"}
{include file="common/header.tpl"}
{/strip}

<div id="scheduleLayoutForm">

<form name="scheduleLayout" method="post" action="{url op="saveScheduleLayout"}">
{include file="common/formErrors.tpl"}

<h4>{translate key="manager.scheduler.layout.mergeSchedules"}</h4>
<table class="data" width="100%">
	<tr valign="top">
		<td width="5%" align="left">
			<input type="checkbox" name="mergeSchedules" value="1"{if $mergeSchedules} checked="checked"{/if} />
		</td>
		<td>
			<label for="mergeSchedules">{translate key="manager.scheduler.layout.mergeSchedulesDescription"}</label>
		</td>
	</tr>
	<tr valign="top">
		<td width="5%" align="left">
			<input type="checkbox" name="showEndTime" value="1"{if $showEndTime} checked="checked"{/if} />
		</td>
		<td>
			<label for="showEndTime">{translate key="manager.scheduler.layout.showEndTime"}</label>
		</td>
	</tr>
	<tr valign="top">
		<td width="5%" align="left">
			<input type="checkbox" name="showAuthors" value="1"{if $showAuthors} checked="checked"{/if} />
		</td>
		<td>
			<label for="showAuthors">{translate key="manager.scheduler.layout.showAuthors"}</label>
		</td>
	</tr>
	<tr valign="top">
		<td width="5%" align="left">
			<input type="checkbox" name="hideNav" value="1"{if $hideNav} checked="checked"{/if} />
		</td>
		<td>
			<label for="hideNav">{translate key="manager.scheduler.layout.hideNav"}</label>
		</td>
	</tr>
	<tr valign="top">
		<td width="5%" align="left">
			<input type="checkbox" name="hideLocations" value="1"{if $hideLocations} checked="checked"{/if} />
		</td>
		<td>
			<label for="hideLocations">{translate key="manager.scheduler.layout.hideLocations"}</label>
		</td>
	</tr>
</table>

<h4>{translate key="manager.scheduler.layout.style"}</h4>

<p>{translate key="manager.scheduler.layout.style.description"}</p>

<table class="data" width="100%">
	<tr valign="top">
		<td width="5%" align="left">
			<input type="radio" name="layoutType" id="layoutType-compact" value="{$smarty.const.SCHEDULE_LAYOUT_COMPACT}" {if $layoutType == $smarty.const.SCHEDULE_LAYOUT_COMPACT} checked="checked"{/if} />
		</td>
		<td>
			<label for="layoutType">{translate key="manager.scheduler.layout.style.compact"}</label>
		</td>
	</tr>
	<tr valign="top">
		<td width="5%" align="left">
			<input type="radio" name="layoutType" id="layoutType-expanded" value="{$smarty.const.SCHEDULE_LAYOUT_EXPANDED}" {if $layoutType == $smarty.const.SCHEDULE_LAYOUT_EXPANDED} checked="checked"{/if} />
		</td>
		<td>
			<label for="layoutType">{translate key="manager.scheduler.layout.style.expanded"}</label>
		</td>
	</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url page="scheduler" escape=false}'" /></p>

</form>
</div>
{include file="common/footer.tpl"}
