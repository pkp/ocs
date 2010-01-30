{**
 * searchUsers.tpl
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Search form for enrolled users.
 *
 * $Id$
 *
 *}
{strip}
{assign var="pageTitle" value="manager.people.enrollExistingUser"}
{include file="common/header.tpl"}
{/strip}

<form name="disableUser" method="post" action="{url op="disableUser"}">
	<input type="hidden" name="reason" value=""/>
	<input type="hidden" name="userId" value=""/>
</form>

<script type="text/javascript">
{literal}
<!--
function confirmAndPrompt(userId) {
	var reason = prompt('{/literal}{translate|escape:"javascript" key="manager.people.confirmDisable"}{literal}');
	if (reason == null) return;

	document.disableUser.reason.value = reason;
	document.disableUser.userId.value = userId;

	document.disableUser.submit();
}

function toggleChecked() {
	var elements = document.enroll.elements;
	for (var i=0; i < elements.length; i++) {
		if (elements[i].name == 'users[]') {
			elements[i].checked = !elements[i].checked;
		}
	}
}

function sortSearch(heading, direction) {
	document.submit.sort.value = heading;
	document.submit.sortDirection.value = direction;
	document.submit.submit();
}
// -->
{/literal}
</script>

<form method="post" name="submit" action="{url op="enrollSearch"}">
	<input type="hidden" name="roleId" value="{$roleId|escape}"/>
	<input type="hidden" name="sortDirection" value="ASC"/>
	<select name="searchField" size="1" class="selectMenu">
		{html_options_translate options=$fieldOptions selected=$searchField}
	</select>
	<select name="searchMatch" size="1" class="selectMenu">
		<option value="contains"{if $searchMatch == 'contains'} selected="selected"{/if}>{translate key="form.contains"}</option>
		<option value="is"{if $searchMatch == 'is'} selected="selected"{/if}>{translate key="form.is"}</option>
		<option value="startsWith"{if $searchMatch == 'startsWith'} selected="selected"{/if}>{translate key="form.startsWith"}</option>
	</select>
	<input type="text" size="15" name="search" class="textField" value="{$search|escape}" />&nbsp;<input type="submit" value="{translate key="common.search"}" class="button" />
</form>

<p>{foreach from=$alphaList item=letter}<a href="{url op="enrollSearch" searchInitial=$letter roleId=$roleId}">{if $letter == $searchInitial}<strong>{$letter|escape}</strong>{else}{$letter|escape}{/if}</a> {/foreach}<a href="{url op="enrollSearch" roleId=$roleId}">{if $searchInitial==''}<strong>{translate key="common.all"}</strong>{else}{translate key="common.all"}{/if}</a></p>

<form name="enroll" action="{if $roleId}{url op="enroll" path=$roleId}{else}{url op="enroll"}{/if}" method="post">
{if !$roleId}
	<p>
	{translate key="manager.people.enrollUserAs"} <select name="roleId" size="1"  class="selectMenu">
		<option value=""></option>
		{if $isConferenceManagement}
			<option value="{$smarty.const.ROLE_ID_CONFERENCE_MANAGER}">{translate key="user.role.manager"}</option>
		{/if}
		{if $isSchedConfManagement}
			<option value="{$smarty.const.ROLE_ID_CONFERENCE_MANAGER}">{translate key="user.role.manager"}</option>
			<option value="{$smarty.const.ROLE_ID_DIRECTOR}">{translate key="user.role.director"}</option>
			<option value="{$smarty.const.ROLE_ID_TRACK_DIRECTOR}">{translate key="user.role.trackDirector"}</option>
			<option value="{$smarty.const.ROLE_ID_REVIEWER}">{translate key="user.role.reviewer"}</option>
			<option value="{$smarty.const.ROLE_ID_AUTHOR}">{translate key="user.role.author"}</option>
			<option value="{$smarty.const.ROLE_ID_READER}">{translate key="user.role.reader"}</option>
		{/if}
	</select>
	</p>
	<script type="text/javascript">
	<!--
	function enrollUser(userId) {ldelim}
		var fakeUrl = '{url op="enroll" path="ROLE_ID" userId="USER_ID"}';
		if (document.enroll.roleId.options[document.enroll.roleId.selectedIndex].value == '') {ldelim}
			alert("{translate|escape:"javascript" key="manager.people.mustChooseRole"}");
			return false;
		{rdelim}
		fakeUrl = fakeUrl.replace('ROLE_ID', document.enroll.roleId.options[document.enroll.roleId.selectedIndex].value);
		fakeUrl = fakeUrl.replace('USER_ID', userId);
		location.href = fakeUrl;
	{rdelim}
	// -->
	</script>
{/if}

<div id="users">
<table width="100%" class="listing">
<tr><td colspan="5" class="headseparator">&nbsp;</td></tr>
<tr class="heading" valign="bottom">
	<td width="5%">&nbsp;</td>
	<td width="25%">{sort_heading key="user.username" sort="username"}</td>
	<td width="30%">{sort_heading key="user.name" sort="name"}</td>
	<td width="30%">{sort_heading key="user.email" sort="email"}</td>
	<td width="10%" align="right">{translate key="common.action"}</td>
</tr>
<tr><td colspan="5" class="headseparator">&nbsp;</td></tr>
{iterate from=users item=user}
{assign var="userid" value=$user->getId()}
{assign var="stats" value=$statistics[$userid]}
<tr valign="top">
	<td><input type="checkbox" name="users[]" value="{$user->getId()}" /></td>
	<td><a class="action" href="{url op="userProfile" path=$userid}">{$user->getUsername()|escape}</a></td>
	<td>{$user->getFullName(true)|escape}</td>
	<td class="nowrap">
		{assign var=emailString value="`$user->getFullName()` <`$user->getEmail()`>"}
		{url|assign:"url" page="user" op="email" to=$emailString|to_array}
		{$user->getEmail()|truncate:20:"..."|escape}&nbsp;{icon name="mail" url=$url}
	</td>
	<td align="right" class="nowrap">
		{if $roleId}
		<a href="{url op="enroll" path=$roleId userId=$user->getId()}" class="action">{translate key="manager.people.enroll"}</a>
		{else}
		<a href="#" onclick="enrollUser({$user->getId()})" class="action">{translate key="manager.people.enroll"}</a>
		{/if}
		{if $thisUser->getId() != $user->getId()}
			{if $user->getDisabled()}
				|&nbsp;<a href="{url op="enableUser" path=$user->getId()}" class="action">{translate key="manager.people.enable"}</a>
			{else}
				|&nbsp;<a href="javascript:confirmAndPrompt({$user->getId()})" class="action">{translate key="manager.people.disable"}</a>
			{/if}
		{/if}
	</td>
</tr>
<tr><td colspan="5" class="{if $users->eof()}end{/if}separator">&nbsp;</td></tr>
{/iterate}
{if $users->wasEmpty()}
	<tr>
	<td colspan="5" class="nodata">{translate key="common.none"}</td>
	</tr>
	<tr><td colspan="5" class="endseparator">&nbsp;</td></tr>
{else}
	<tr>
		<td colspan="3" align="left">{page_info iterator=$users}</td>
		<td colspan="2" align="right">{page_links anchor="users" name="users" iterator=$users searchInitial=$searchInitial searchField=$searchField searchMatch=$searchMatch search=$search dateFromDay=$dateFromDay dateFromYear=$dateFromYear dateFromMonth=$dateFromMonth dateToDay=$dateToDay dateToYear=$dateToYear dateToMonth=$dateToMonth sort=$sort sortDirection=$sortDirection}</td>
	</tr>
{/if}
</table>

<input type="submit" value="{translate key="manager.people.enrollSelected"}" class="button defaultButton" /> <input type="button" value="{translate key="common.selectAll"}" class="button" onclick="toggleChecked()" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url page="manager"}'" />
</div>

</form>


{if $backLink}
<a href="{$backLink}">{translate key="$backLinkLabel"}</a>
{/if}

{include file="common/footer.tpl"}
