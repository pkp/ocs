{**
 * registrations.tpl
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of registrations in scheduled conference management.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="manager.registration"}
{assign var="pageId" value="manager.registration"}
{include file="common/header.tpl"}
{/strip}

<ul class="menu">
	<li class="current"><a href="{url op="registration" clearPageContext=1}">{translate key="manager.registration"}</a></li>
	<li><a href="{url op="registrationTypes" clearPageContext=1}">{translate key="manager.registrationTypes"}</a></li>
	<li><a href="{url op="registrationPolicies"}">{translate key="manager.registrationPolicies"}</a></li>
</ul>

<br />

{if !$dateFrom}
{assign var="dateFrom" value="--"}
{/if}

{if !$dateTo}
{assign var="dateTo" value="--"}
{/if}

<form method="post" name="submit" action="{url op="registration"}">
	<select name="searchField" size="1" class="selectMenu">
		{html_options_translate options=$fieldOptions selected=$searchField}
	</select>
	<select name="searchMatch" size="1" class="selectMenu">
		<option value="contains"{if $searchMatch == 'contains'} selected="selected"{/if}>{translate key="form.contains"}</option>
		<option value="is"{if $searchMatch == 'is'} selected="selected"{/if}>{translate key="form.is"}</option>
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

<a name="registrations"></a>

<table width="100%" class="listing">
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="32%">{translate key="manager.registration.user"}</td>
		<td width="25%">{translate key="manager.registration.registrationType"}</td>
		<td width="15%">{translate key="manager.registration.dateRegistered"}</td>
		<td width="15%">{translate key="manager.registration.datePaid"}</td>
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
		<td><a href="{url op="editRegistration" path=$registration->getRegistrationId()}" class="action">{translate key="common.edit"}</a>&nbsp;|&nbsp;<a href="{url op="deleteRegistration" path=$registration->getRegistrationId()}" onclick="return confirm('{translate|escape:"jsparam" key="manager.registration.confirmDelete"}')" class="action">{translate key="common.delete"}</a></td>
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
		<td colspan="3" align="right">{page_links anchor="registrations" name="registrations" iterator=$registrations}</td>
	</tr>
{/if}
</table>

<a href="{url op="selectRegistrant"}" class="action">{translate key="manager.registration.create"}</a>

{include file="common/footer.tpl"}
