{**
 * registrations.tpl
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of registrations in scheduled conference management.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.registrations"}
{assign var="pageId" value="manager.registrations"}
{include file="common/header.tpl"}

<ul class="menu">
	<li class="current"><a href="{url op="registrations"}">{translate key="manager.registrations"}</a></li>
	<li><a href="{url op="registrationTypes"}">{translate key="manager.registrationTypes"}</a></li>
	<li><a href="{url op="registrationPolicies"}">{translate key="manager.registrationPolicies"}</a></li>
</ul>

<br />

<a name="registrations"></a>

<table width="100%" class="listing">
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="32%">{translate key="manager.registrations.user"}</td>
		<td width="25%">{translate key="manager.registrations.registrationType"}</td>
		<td width="15%">{translate key="manager.registrations.dateRegistered"}</td>
		<td width="15%">{translate key="manager.registrations.datePaid"}</td>
		<td width="13%">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
{iterate from=registrations item=registration}
	<tr valign="top">
		<td>{$registration->getUserFullName()|escape}</td>
		<td>{$registration->getTypeName()|escape}</td>
		<td>{$registration->getDateRegistered()|date_format:$dateFormatShort}</td>
		<td>{$registration->getDatePaid()|date_format:$dateFormatShort}</td>
		<td><a href="{url op="editRegistration" path=$registration->getRegistrationId()}" class="action">{translate key="common.edit"}</a>&nbsp;|&nbsp;<a href="{url op="deleteRegistration" path=$registration->getRegistrationId()}" onclick="return confirm('{translate|escape:"javascript" key="manager.registrations.confirmDelete"}')" class="action">{translate key="common.delete"}</a></td>
	</tr>
	<tr>
		<td colspan="5" class="{if $registrations->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $registrations->wasEmpty()}
	<tr>
		<td colspan="5" class="nodata">{translate key="manager.registrations.noneCreated"}</td>
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

<a href="{url op="selectRegistrant"}" class="action">{translate key="manager.registrations.create"}</a>

{include file="common/footer.tpl"}
