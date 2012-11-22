{**
 * templates/registration/registrations.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of registrations in scheduled conference management.
 *
 *}
{strip}
{assign var="pageTitle" value="manager.registration"}
{assign var="pageId" value="manager.registration"}
{include file="common/header.tpl"}
{/strip}

<script type="text/javascript">
{literal}
<!--
function sortSearch(heading, direction) {
	var submitForm = document.getElementById('registrationsSubmit');
	submitForm.sort.value = heading;
	submitForm.sortDirection.value = direction;
	submitForm.submit();
}
// -->
{/literal}
</script>

<ul class="menu">
	<li class="current"><a href="{url op="registration" clearPageContext=1}">{translate key="manager.registration"}</a></li>
	<li><a href="{url op="registrationTypes" clearPageContext=1}">{translate key="manager.registrationTypes"}</a></li>
	<li><a href="{url op="registrationPolicies"}">{translate key="manager.registrationPolicies"}</a></li>
	<li><a href="{url op="registrationOptions"}">{translate key="manager.registrationOptions"}</a></li>
</ul>

<br />

{if !$dateFrom}
{assign var="dateFrom" value="--"}
{/if}

{if !$dateTo}
{assign var="dateTo" value="--"}
{/if}

<form class="pkp_form" method="post" id="registrationsSubmit" action="{url op="registration"}">
	<input type="hidden" name="sort" value="id"/>
	<input type="hidden" name="sortDirection" value="ASC"/>
	<select name="searchField" size="1" class="selectMenu">
		{html_options_translate options=$fieldOptions selected=$searchField}
	</select>
	<select name="searchMatch" size="1" class="selectMenu">
		<option value="contains"{if $searchMatch == 'contains'} selected="selected"{/if}>{translate key="form.contains"}</option>
		<option value="is"{if $searchMatch == 'is'} selected="selected"{/if}>{translate key="form.is"}</option>
		<option value="startsWith"{if $searchMatch == 'startsWith'} selected="selected"{/if}>{translate key="form.startsWith"}</option>
	</select>
	<input type="text" size="15" name="search" class="textField" value="{$search|escape}" />
	<br/>
	<select name="dateSearchField" size="1" class="selectMenu">
		{html_options_translate options=$dateFieldOptions selected=$dateSearchField}
	</select>
	{translate key="common.between"}
	{html_select_date prefix="dateFrom" time=$dateFrom all_extra="class=\"selectMenu\"" year_empty="" month_empty="" day_empty="" start_year="-5" end_year="+5"}
	{translate key="common.and"}
	{html_select_date prefix="dateTo" time=$dateTo all_extra="class=\"selectMenu\"" year_empty="" month_empty="" day_empty="" start_year="-5" end_year="+5"}
	<input type="hidden" name="dateToHour" value="23" />
	<input type="hidden" name="dateToMinute" value="59" />
	<input type="hidden" name="dateToSecond" value="59" />
	<br/>
	<input type="submit" value="{translate key="common.search"}" class="button" />
</form>

<br />

<div id="registrations">
<table width="100%" class="listing">
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="32%">{sort_search key="manager.registration.user" sort="user"}</td>
		<td width="25%">{sort_search key="manager.registration.registrationType" sort="type"}</td>
		<td width="15%">{sort_search key="manager.registration.dateRegistered" sort="registered"}</td>
		<td width="15%">{sort_search key="manager.registration.datePaid" sort="paid"}</td>
		<td width="13%">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
{iterate from=registrations item=registration}
	<tr valign="top">
		<td>{$registration->getUserFullName()|escape}</td>
		<td>{$registration->getRegistrationTypeName()|escape}</td>
		<td>{$registration->getDateRegistered()|date_format:$dateFormatShort}</td>
		<td>{$registration->getDatePaid()|date_format:$dateFormatShort}</td>
		<td><a href="{url op="editRegistration" path=$registration->getId()}" class="action">{translate key="common.edit"}</a>&nbsp;|&nbsp;<a href="{url op="deleteRegistration" path=$registration->getId()}" onclick="return confirm('{translate|escape:"jsparam" key="manager.registration.confirmDelete"}')" class="action">{translate key="common.delete"}</a></td>
	</tr>
	<tr>
		<td colspan="5" class="{if $registrations->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $registrations->wasEmpty()}
	<tr>
		<td colspan="5" class="nodata">{translate key="manager.registration.noneCreated"}</td>
	</tr>
	<tr>
		<td colspan="5" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="2" align="left">{page_info iterator=$registrations}</td>
		<td colspan="3" align="right">{page_links anchor="registrations" name="registrations" iterator=$registrations searchField=$searchField searchMatch=$searchMatch search=$search dateSearchField=$dateSearchField dateFromDay=$dateFromDay dateFromYear=$dateFromYear dateFromMonth=$dateFromMonth dateToDay=$dateToDay dateToYear=$dateToYear dateToMonth=$dateToMonth sort=$sort sortDirection=$sortDirection}</td>
	</tr>
{/if}
</table>

<a href="{url op="selectRegistrant"}" class="action">{translate key="manager.registration.create"}</a>
</div>
{include file="common/footer.tpl"}

