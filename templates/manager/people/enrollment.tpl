{**
 * enrollment.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List enrolled users.
 *
 * $Id$
 *}
{strip}
{if $roleSymbolic == 'all'}
	{assign var="pageTitle" value="manager.people.allEnrolledUsers"}
{else}
	{assign var="pageTitle" value="manager.people.enrollment"}
{/if}
{include file="common/header.tpl"}
{/strip}

<form name="disableUser" method="post" action="{url op="disableUser"}">
	<input type="hidden" name="reason" value=""/>
	<input type="hidden" name="userId" value=""/>
</form>

<script type="text/javascript">
{literal}
<!--
function toggleChecked() {
	var elements = document.people.elements;
	for (var i=0; i < elements.length; i++) {
		if (elements[i].name == 'bcc[]') {
			elements[i].checked = !elements[i].checked;
		}
	}
}

function confirmAndPrompt(userId) {
	var reason = prompt('{/literal}{translate|escape:"javascript" key="manager.people.confirmDisable"}{literal}');
	if (reason == null) return;

	document.disableUser.reason.value = reason;
	document.disableUser.userId.value = userId;

	document.disableUser.submit();
}

function sortSearch(heading, direction) {
	document.submit.sort.value = heading;
	document.submit.sortDirection.value = direction;
	document.submit.submit();
}
// -->
{/literal}
</script>

<h3>{translate key=$roleName}</h3>
<form name="submit" method="post" action="{url path=$roleSymbolic}">
	<input type="hidden" name="sort" value="id"/>
	<input type="hidden" name="sortDirection" value="ASC"/>
	<select name="roleSymbolic" class="selectMenu">
		<option {if $roleSymbolic=='all'}selected="selected" {/if}value="all">{translate key="manager.people.allUsers"}</option>

		{if $isConferenceManagement}
			<option {if $roleSymbolic=='managers'}selected="selected" {/if}value="managers">{translate key="user.role.managers"}</option>
		{/if}
		{if $isSchedConfManagement}
			<option {if $roleSymbolic=='directors'}selected="selected" {/if}value="directors">{translate key="user.role.directors"}</option>
			<option {if $roleSymbolic=='trackDirectors'}selected="selected" {/if}value="trackDirectors">{translate key="user.role.trackDirectors"}</option>
			<option {if $roleSymbolic=='reviewers'}selected="selected" {/if}value="reviewers">{translate key="user.role.reviewers"}</option>
			<option {if $roleSymbolic=='authors'}selected="selected" {/if}value="authors">{translate key="user.role.authors"}</option>
			<option {if $roleSymbolic=='readers'}selected="selected" {/if}value="readers">{translate key="user.role.readers"}</option>
		{/if}
	</select>
	<select name="searchField" size="1" class="selectMenu">
		{html_options_translate options=$fieldOptions selected=$searchField}
	</select>
	<select name="searchMatch" size="1" class="selectMenu">
		<option value="contains"{if $searchMatch == 'contains'} selected="selected"{/if}>{translate key="form.contains"}</option>
		<option value="is"{if $searchMatch == 'is'} selected="selected"{/if}>{translate key="form.is"}</option>
		<option value="startsWith"{if $searchMatch == 'startsWith'} selected="selected"{/if}>{translate key="form.startsWith"}</option>
	</select>
	<input type="text" size="10" name="search" class="textField" value="{$search|escape}" />&nbsp;<input type="submit" value="{translate key="common.search"}" class="button" />
</form>

<p>{foreach from=$alphaList item=letter}<a href="{url path=$roleSymbolic searchInitial=$letter}">{if $letter == $searchInitial}<strong>{$letter|escape}</strong>{else}{$letter|escape}{/if}</a> {/foreach}<a href="{url path=$roleSymbolic}">{if $searchInitial==''}<strong>{translate key="common.all"}</strong>{else}{translate key="common.all"}{/if}</a></p>

{if not $roleId}
<ul>
	{if $isConferenceManagement}
		<li><a href="{url path="managers"}">{translate key="user.role.managers"}</a></li>
	{/if}
	{if $isSchedConfManagement}
		<li><a href="{url path="directors"}">{translate key="user.role.directors"}</a></li>
		<li><a href="{url path="trackDirectors"}">{translate key="user.role.trackDirectors"}</a></li>
		<li><a href="{url path="reviewers"}">{translate key="user.role.reviewers"}</a></li>
		<li><a href="{url path="authors"}">{translate key="user.role.authors"}</a></li>
		<li><a href="{url path="readers"}">{translate key="user.role.readers"}</a></li>
	{/if}
</ul>

<br />
{else}
<p><a href="{url path="all"}" class="action">{translate key="manager.people.allUsers"}</a></p>
{/if}

<form name="people" action="{url page="user" op="email"}" method="post">
<input type="hidden" name="redirectUrl" value="{url path=$roleSymbolic}"/>

<div id="users">
<table width="100%" class="listing">
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="5%">&nbsp;</td>
		<td width="12%">{sort_search key="user.username" sort="username"}</td>
		<td width="20%">{sort_search key="user.name" sort="name"}</td>
		<td width="23%">{sort_search key="user.email" sort="email"}</td>
		<td width="40%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
	{iterate from=users item=user}
	{assign var=userExists value=1}
	<tr valign="top">
		<td><input type="checkbox" name="bcc[]" value="{$user->getEmail()|escape}"/></td>
		<td><a class="action" href="{url op="userProfile" path=$user->getId()}">{$user->getUsername()|escape|wordwrap:15:" ":true}</a></td>
		<td>{$user->getFullName()|escape}</td>
		<td class="nowrap">
			{assign var=emailString value=$user->getFullName()|concat:" <":$user->getEmail():">"}
			{url|assign:"redirectUrl" path=$roleSymbolic}
			{url|assign:"url" page="user" op="email" to=$emailString|to_array redirectUrl=$redirectUrl}
			{$user->getEmail()|truncate:15:"..."|escape}&nbsp;{icon name="mail" url=$url}
		</td>
		<td align="right">
			{if $roleId}
			<a href="{url op="unEnroll" path=$roleId userId=$user->getId()}" onclick="return confirm('{translate|escape:"jsparam" key="manager.people.confirmUnenroll"}')" class="action">{translate key="manager.people.unenroll"}</a>&nbsp;|
			{/if}
			<a href="{url op="editUser" path=$user->getId()}" class="action">{translate key="common.edit"}</a>
			{if $thisUser->getId() != $user->getId()}
				|&nbsp;<a href="{url page="login" op="signInAsUser" path=$user->getId()}" class="action">{translate key="manager.people.signInAs"}</a>
				{if !$roleId}|&nbsp;<a href="{url op="removeUser" path=$user->getId()}" onclick="return confirm('{translate|escape:"jsparam" key="manager.people.confirmRemove"}')" class="action">{translate key="manager.people.remove"}</a>{/if}
				{if $user->getDisabled()}
					|&nbsp;<a href="{url op="enableUser" path=$user->getId()}" class="action">{translate key="manager.people.enable"}</a>
				{else}
					|&nbsp;<a href="javascript:confirmAndPrompt({$user->getId()})" class="action">{translate key="manager.people.disable"}</a>
				{/if}
			{/if}
		</td>
	</tr>
	<tr>
		<td colspan="5" class="{if $users->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $users->wasEmpty()}
	<tr>
		<td colspan="5" class="nodata">{translate key="manager.people.noneEnrolled"}</td>
	</tr>
	<tr>
		<td colspan="5" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="4" align="left">{page_info iterator=$users}</td>
		<td align="right">{page_links anchor="users" name="users" iterator=$users searchField=$searchField searchMatch=$searchMatch search=$search dateFromDay=$dateFromDay dateFromYear=$dateFromYear dateFromMonth=$dateFromMonth dateToDay=$dateToDay dateToYear=$dateToYear dateToMonth=$dateToMonth roleSymbolic=$roleSymbolic searchInitial=$searchInitial sort=$sort sortDirection=$sortDirection}</td>
	</tr>
{/if}
</table>

{if $userExists}
	<p><input type="submit" value="{translate key="email.compose"}" class="button defaultButton"/>&nbsp;<input type="button" value="{translate key="common.selectAll"}" class="button" onclick="toggleChecked()" />  <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url page="manager"}'" /></p>
{/if}
</div>
</form>

<a href="{url op="enrollSearch" path=$roleId}" class="action">{translate key="manager.people.enrollExistingUser"}</a> |
{url|assign:"enrollmentUrl" path=$roleSymbolic searchField=$searchField searchMatch=$searchMatch search=$search dateFromDay=$dateFromDay dateFromYear=$dateFromYear dateFromMonth=$dateFromMonth dateToDay=$dateToDay dateToYear=$dateToYear dateToMonth=$dateToMonth searchInitial=$searchInitial}
<a href="{if $roleId}{url op="createUser" roleId=$roleId source=$enrollmentUrl}{else}{url op="createUser" source=$enrollmentUrl}{/if}" class="action">{translate key="manager.people.createUser"}</a>{if $currentSchedConf} | <a href="{url op="enrollSyncSelect" path=$rolePath}" class="action">{translate key="manager.people.enrollSync"}</a>{/if}

{include file="common/footer.tpl"}
