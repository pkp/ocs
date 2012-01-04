{**
 * scheduleLayoutForm.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
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
			<input type="checkbox" id="mergeSchedules" name="mergeSchedules" value="1"{if $mergeSchedules} checked="checked"{/if} />
		</td>
		<td>{fieldLabel name="mergeSchedules" key="manager.scheduler.layout.mergeSchedulesDescription"}</td>
	</tr>
	<tr valign="top">
		<td align="left">
			<input type="checkbox" id="showEndTime" name="showEndTime" value="1"{if $showEndTime} checked="checked"{/if} />
		</td>
		<td>{fieldLabel name="showEndTime" key="manager.scheduler.layout.showEndTime"}</td>
	</tr>
	<tr valign="top">
		<td align="left">
			<input type="checkbox" id="showAuthors" name="showAuthors" value="1"{if $showAuthors} checked="checked"{/if} />
		</td>
		<td>{fieldLabel name="showAuthors" key="manager.scheduler.layout.showAuthors"}</td>
	</tr>
	<tr valign="top">
		<td align="left">
			<input type="checkbox" id="hideNav" name="hideNav" value="1"{if $hideNav} checked="checked"{/if} />
		</td>
		<td>{fieldLabel name="hideNav" key="manager.scheduler.layout.hideNav"}</td>
	</tr>
	<tr valign="top">
		<td align="left">
			<input type="checkbox" id="hideLocations" name="hideLocations" value="1"{if $hideLocations} checked="checked"{/if} />
		</td>
		<td>{fieldLabel name="hideLocations" key="manager.scheduler.layout.hideLocations"}</td>
	</tr>
</table>

<h4>{translate key="manager.scheduler.layout.style"}</h4>

<p>{translate key="manager.scheduler.layout.style.description"}</p>

<table class="data" width="100%">
	<tr valign="top">
		<td width="5%" align="left">
			<input type="radio" name="layoutType" id="layoutType-compact" value="{$smarty.const.SCHEDULE_LAYOUT_COMPACT}" {if $layoutType == $smarty.const.SCHEDULE_LAYOUT_COMPACT} checked="checked"{/if} />
		</td>
		<td>{fieldLabel name="layoutType-compact" key="manager.scheduler.layout.style.compact"}</td>
	</tr>
	<tr valign="top">
		<td align="left">
			<input type="radio" name="layoutType" id="layoutType-expanded" value="{$smarty.const.SCHEDULE_LAYOUT_EXPANDED}" {if $layoutType == $smarty.const.SCHEDULE_LAYOUT_EXPANDED} checked="checked"{/if} />
		</td>
		<td>{fieldLabel name="layoutType-expanded" key="manager.scheduler.layout.style.expanded"}</td>
	</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="scheduler"}'" /></p>

</form>
</div>
{include file="common/footer.tpl"}
