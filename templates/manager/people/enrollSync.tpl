{**
 * enrollSync.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Synchronize user enrollment with another conference.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="manager.people.syncUsers"}
{include file="common/header.tpl"}
{/strip}

<p><span class="instruct">{translate key="manager.people.syncUserDescription"}</span></p>

<form method="post" action="{url op="enrollSync"}">

<table class="data" width="100%">
	<tr valign="top">
		<td width="20%" class="label"><label for="rolePath">{translate key="manager.people.enrollSyncRole"}</label></td>
		<td width="80%" class="value">
			{if $rolePath}
				<input type="hidden" name="rolePath" value="{$rolePath|escape}" />
				{translate key=$roleName}
			{else}
				<select name="rolePath" id="rolePath" size="1" class="selectMenu">
					<option value=""></option>
					<option value="all">{translate key="manager.people.allUsers"}</option>
					<option value="manager">{translate key="user.role.manager"}</option>
					<option value="director">{translate key="user.role.director"}</option>
					<option value="trackDirector">{translate key="user.role.trackDirector"}</option>
					<option value="reviewer">{translate key="user.role.reviewer"}</option>
					<option value="author">{translate key="user.role.author"}</option>
					<option value="reader">{translate key="user.role.reader"}</option>
				</select>
			{/if}
		</td>
	</tr>
	<tr valign="top">
		<td class="label"><label for="syncConference">{translate key="manager.people.enrollSyncConference"}</label></td>
		<td class="value">
			<select name="syncConference" id="syncConference" size="1" class="selectMenu">
				<option value=""></option>
				<option value="all">{translate key="manager.people.allConferences"}</option>
				{html_options options=$conferenceOptions}
			</select>
		</td>
	</tr>
</table>

<p><input type="submit" value="{translate key="manager.people.enrollSync"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="history.go(-1)" /></p>

</form>

{include file="common/footer.tpl"}
