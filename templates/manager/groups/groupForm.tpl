{**
 * groupForm.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Group form under conference management.
 *
 * $Id$
 *}
{strip}
{assign var="pageId" value="manager.groups.groupForm"}
{assign var="pageCrumbTitle" value=$pageTitle}
{include file="common/header.tpl"}
{/strip}

{if $group}
	<ul class="menu">
		<li class="current"><a href="{url op="editGroup" path=$group->getId()}">{translate key="manager.groups.editTitle"}</a></li>
		<li><a href="{url op="groupMembership" path=$group->getId() clearPageContext=1}">{translate key="manager.groups.membership}</a></li>
	</ul>
{/if}

<br/>

<form name="groupForm" method="post" action="{url op="updateGroup"}">
{if $group}
	<input type="hidden" name="groupId" value="{$group->getId()}"/>
{/if}

{include file="common/formErrors.tpl"}
<table class="data" width="100%">
{if count($formLocales) > 1}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{if $group}{url|assign:"groupFormUrl" op="editGroup" path=$group->getId() escape=false}
			{else}{url|assign:"groupFormUrl" op="createGroup"}
			{/if}
			{form_language_chooser form="groupForm" url=$groupFormUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
{/if}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="title" required="true" key="manager.groups.title"}</td>
		<td width="80%" class="value"><input type="text" name="title[{$formLocale|escape}]" value="{$title[$formLocale]|escape}" size="35" maxlength="80" id="title" class="textField" /></td>
	</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="groups"}'" /></p>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
