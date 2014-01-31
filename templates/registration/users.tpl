{**
 * users.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Search form for users
 *
 * $Id$
 *
 *}
{strip}
{if $registrationId}
	{assign var="pageTitle" value="manager.registration.selectRegistrant"}
{else}
	{assign var="pageTitle" value="manager.registration.select"}
{/if}
{include file="common/header.tpl"}
{/strip}

{if $registrationCreated}
<br/>{translate key="manager.registration.registrationCreatedSuccessfully"}<br/>
{/if}

<p>{translate key="manager.registration.selectRegistrant.desc"}</p>
<form method="post" name="submit" action="{if $registrationId}{url op="selectRegistrant" registrationId=$registrationId}{else}{url op="selectRegistrant" registrationId=$registrationId}{/if}">
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

<p>{foreach from=$alphaList item=letter}<a href="{if $registrationId}{url op="selectRegistrant" searchInitial=$letter registrationId=$registrationId}{else}{url op="selectRegistrant" searchInitial=$letter}{/if}">{if $letter == $searchInitial}<strong>{$letter|escape}</strong>{else}{$letter|escape}{/if}</a> {/foreach}<a href="{if $registrationId}{url op="selectRegistrant" registrationId=$registrationId}{else}{url op="selectRegistrant"}{/if}">{if $searchInitial==''}<strong>{translate key="common.all"}</strong>{else}{translate key="common.all"}{/if}</a></p>

<div id="users">
<table width="100%" class="listing">
<tr><td colspan="4" class="headseparator">&nbsp;</td></tr>
<tr class="heading" valign="bottom">
	<td width="25%">{sort_heading key="user.username" sort="username"}</td>
	<td width="35%">{sort_heading key="user.name" sort="name"}</td>
	<td width="30%">{sort_heading key="user.email" sort="email"}</td>
	<td width="10%" align="right"></td>
</tr>
<tr><td colspan="4" class="headseparator">&nbsp;</td></tr>
{iterate from=users item=user}
{assign var="userid" value=$user->getId()}
<tr valign="top">
	<td>{if $isSchedConfManager}<a class="action" href="{url op="userProfile" path=$userid}">{/if}{$user->getUsername()|escape}{if $isSchedConfManager}</a>{/if}</td>
	<td>{$user->getFullName(true)|escape}</td>
	<td class="nowrap">
		{assign var=emailString value=$user->getFullName()|concat:" <":$user->getEmail():">"}
		{url|assign:"url" page="user" op="email" to=$emailString|to_array}
		{$user->getEmail()|truncate:20:"..."|escape}&nbsp;{icon name="mail" url=$url}
	</td>
	<td align="right" class="nowrap">
		<a href="{if $registrationId}{url op="editRegistration" path=$registrationId userId=$user->getId()}{else}{url op="createRegistration" userId=$user->getId()}{/if}" class="action">{translate key="manager.registration.enroll"}</a>
	</td>
</tr>
<tr><td colspan="4" class="{if $users->eof()}end{/if}separator">&nbsp;</td></tr>
{/iterate}
{if $users->wasEmpty()}
	<tr>
	<td colspan="4" class="nodata">{translate key="common.none"}</td>
	</tr>
	<tr><td colspan="4" class="endseparator">&nbsp;</td></tr>
{else}
	<tr>
		<td colspan="3" align="left">{page_info iterator=$users}</td>
		<td colspan="2" align="right">{page_links anchor="users" name="users" iterator=$users searchInitial=$searchInitial searchField=$searchField searchMatch=$searchMatch search=$search dateFromDay=$dateFromDay dateFromYear=$dateFromYear dateFromMonth=$dateFromMonth dateToDay=$dateToDay dateToYear=$dateToYear dateToMonth=$dateToMonth sort=$sort sortDirection=$sortDirection}</td>
	</tr>
{/if}
</table>
</div>
{include file="common/footer.tpl"}
