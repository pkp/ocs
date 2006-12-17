{**
 * enrollSync.tpl
 *
 * Copyright (c) 2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Synchronize user enrollment with another conference.
 *
 * $Id$
 *}

{assign var="pageTitle" value="director.people.enrollment"}
{include file="common/header.tpl"}

<h3>{translate key="director.people.syncUsers"}</h3>

<p><span class="instruct">{translate key="director.people.syncUserDescription"}</span></p>

<form method="post" action="{url op="enrollSync"}">

<table class="data" width="100%">
	<tr valign="top">
		<td width="20%" class="label"><label for="rolePath">{translate key="director.people.enrollSyncRole"}</label></td>
		<td width="80%" class="value">
			{if $rolePath}
				<input type="hidden" name="rolePath" value="{$rolePath|escape}" />
				{translate key=$roleName}
			{else}
				<select name="rolePath" id="rolePath" size="1" class="selectMenu">
					<option value=""></option>
					<option value="all">{translate key="director.people.allUsers"}</option>
					<option value="director">{translate key="user.role.director"}</option>
					<option value="editor">{translate key="user.role.editor"}</option>
					<option value="trackEditor">{translate key="user.role.trackEditor"}</option>
					<option value="reviewer">{translate key="user.role.reviewer"}</option>
					{*<option value="invitedAuthor">{translate key="user.role.invitedAuthor"}</option>*}
					<option value="author">{translate key="user.role.author"}</option>
					<option value="reader">{translate key="user.role.reader"}</option>
					<option value="registrationManager">{translate key="user.role.registrationManager"}</option>
				</select>
			{/if}
		</td>
	</tr>
	<tr valign="top">
		<td class="label"><label for="syncConference">{translate key="director.people.enrollSyncConference"}</label></td>
		<td class="value">
			<select name="syncConference" id="syncConference" size="1" class="selectMenu">
				<option value=""></option>
				<option value="all">{translate key="director.people.allConferences"}</option>
				{html_options options=$conferenceOptions}
			</select>
		</td>
	</tr>
</table>

<p><input type="submit" value="{translate key="director.people.enrollSync"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="history.go(-1)" /></p>

</form>

{include file="common/footer.tpl"}
