{**
 * selectMergeUser.tpl
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List enrolled users so the manager can choose users to merge.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="manager.people.mergeUsers"}
{include file="common/header.tpl"}
{/strip}

<p>{if $oldUserId != ''}{translate key="manager.people.mergeUsers.into.description"}{else}{translate key="manager.people.mergeUsers.from.description"}{/if}</p>

<h3>{translate key=$roleName}</h3>
<form method="post" action="{url path=$roleSymbolic oldUserId=$oldUserId}">
	<select name="roleSymbolic" class="selectMenu">
		<option {if $roleSymbolic=='all'}selected="selected" {/if}value="all">{translate key="manager.people.allUsers"}</option>
		<option {if $roleSymbolic=='managers'}selected="selected" {/if}value="managers">{translate key="user.role.managers"}</option>
		<option {if $roleSymbolic=='directors'}selected="selected" {/if}value="directors">{translate key="user.role.directors"}</option>
		<option {if $roleSymbolic=='trackDirectors'}selected="selected" {/if}value="trackDirectors">{translate key="user.role.trackDirectors"}</option>
		<option {if $roleSymbolic=='reviewers'}selected="selected" {/if}value="reviewers">{translate key="user.role.reviewers"}</option>
		<option {if $roleSymbolic=='authors'}selected="selected" {/if}value="authors">{translate key="user.role.authors"}</option>
		<option {if $roleSymbolic=='readers'}selected="selected" {/if}value="readers">{translate key="user.role.readers"}</option>
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

<p>{foreach from=$alphaList item=letter}<a href="{url path=$roleSymbolic oldUserId=$oldUserId searchInitial=$letter}">{if $letter == $searchInitial}<strong>{$letter|escape}</strong>{else}{$letter|escape}{/if}</a> {/foreach}<a href="{url path=$roleSymbolic oldUserId=$oldUserId}">{if $searchInitial==''}<strong>{translate key="common.all"}</strong>{else}{translate key="common.all"}{/if}</a></p>

{if not $roleId}
<ul>
	<li><a href="{url path="managers" oldUserId=$oldUserId}">{translate key="user.role.managers"}</a></li>
	<li><a href="{url path="directors" oldUserId=$oldUserId}">{translate key="user.role.directors"}</a></li>
	<li><a href="{url path="trackDirectors" oldUserId=$oldUserId}">{translate key="user.role.trackDirectors"}</a></li>
	<li><a href="{url path="reviewers" oldUserId=$oldUserId}">{translate key="user.role.reviewers"}</a></li>
	<li><a href="{url path="authors" oldUserId=$oldUserId}">{translate key="user.role.authors"}</a></li>
	<li><a href="{url path="readers" oldUserId=$oldUserId}">{translate key="user.role.readers"}</a></li>
</ul>

<br />
{else}
<p><a href="{url path="all" oldUserId=$oldUserId}" class="action">{translate key="manager.people.allUsers"}</a></p>
{/if}

<div id="users">
<table width="100%" class="listing">
	<tr>
		<td colspan="4" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="29%">{translate key="user.username"}</td>
		<td width="29%">{translate key="user.name"}</td>
		<td width="29%">{translate key="user.email"}</td>
		<td width="13%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="4" class="headseparator">&nbsp;</td>
	</tr>
	{iterate from=users item=user}
	{assign var=userExists value=1}
	<tr valign="top">
		<td><a class="action" href="{url op="userProfile" path=$user->getId()}">{$user->getUsername()|escape|wordwrap:15:" ":true}</a></td>
		<td>{$user->getFullName()|escape}</td>
		<td class="nowrap">
			{assign var=emailString value="`$user->getFullName()` <`$user->getEmail()`>"}
			{url|assign:"redirectUrl" path=$roleSymbolic}
			{url|assign:"url" page="user" op="email" to=$emailString|to_array redirectUrl=$redirectUrl}
			{$user->getEmail()|truncate:15:"..."|escape}&nbsp;{icon name="mail" url=$url}
		</td>
		<td align="right">
			{if $oldUserId != ''}
				{if $oldUserId != $user->getId()}
					<a href="#" onclick="confirmAction('{url oldUserId=$oldUserId newUserId=$user->getId()}', '{translate|escape:"jsparam" key="manager.people.mergeUsers.confirm" oldUsername=$oldUsername newUsername=$user->getUsername()}')" class="action">{translate key="manager.people.mergeUser"}</a>
				{/if}
			{elseif $thisUser->getId() != $user->getId()}
				<a href="{url oldUserId=$user->getId()}" class="action">{translate key="manager.people.mergeUser"}</a>
			{/if}
		</td>
	</tr>
	<tr>
		<td colspan="4" class="{if $users->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $users->wasEmpty()}
	<tr>
		<td colspan="4" class="nodata">{translate key="manager.people.noneEnrolled"}</td>
	</tr>
	<tr>
		<td colspan="4" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="2" align="left">{page_info iterator=$users}</td>
		<td colspan="2" align="right">{page_links anchor="users" name="users" iterator=$users searchInitial=$searchInitial searchField=$searchField searchMatch=$searchMatch search=$search dateFromDay=$dateFromDay dateFromYear=$dateFromYear dateFromMonth=$dateFromMonth dateToDay=$dateToDay dateToYear=$dateToYear dateToMonth=$dateToMonth roleSymbolic=$roleSymbolic oldUserId=$oldUserId}</td>
	</tr>
{/if}
</table>
</div>
{include file="common/footer.tpl"}
