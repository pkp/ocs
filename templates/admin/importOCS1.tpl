{**
 * importOCS1.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Basic conference settings under site administration.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="admin.conferences.importOCS1"}
{assign var="helpTopicId" value="site.siteManagement"}
{include file="common/header.tpl"}
{/strip}

<form method="post" action="{url page="admin" op="doImportOCS1"}">

{include file="common/formErrors.tpl"}

{if $importError}
<p>
	<span class="formError">{translate key="admin.conferences.importErrors"}:</span>
	<ul class="formErrorList">
		<li>{$importError|escape}</li>
	</ul>
</p>
{/if}

<p><span class="instruct">{translate key="admin.conferences.importOCS1Instructions"}</span></p>

<table class="data" width="100%">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="title" key="common.path" required="true"}</td>
		<td width="80%" class="value">
			<input type="text" id="conferencePath" name="conferencePath" value="{$conferencePath|escape}" size="16" maxlength="32" class="textField" />
			<br />
			<span class="instruct">{translate key="admin.conference.pathImportInstructions"}</span>
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="title" key="admin.conference.importPath" required="true"}</td>
		<td class="value">
			<input type="text" id="importPath" name="importPath" value="{$importPath|escape}" size="60" maxlength="255" class="textField" />
			<br />
			<span class="instruct">{translate key="admin.conference.importPathInstructions"}</span>
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="common.options"}</td>
		<td class="value">
			<input type="checkbox" name="options[]" id="options-importRegistrations" value="importRegistrations"{if $options && in_array('importRegistrations', $options)} checked="checked"{/if} /> <label for="options-importRegistrations">{translate key="admin.conferences.importRegistrations"}</label><br/>
			<input type="checkbox" name="options[]" id="options-emailUsers" value="emailUsers"{if $options && in_array('emailUsers', $options)} checked="checked"{/if} /> <label for="options-emailUsers">{translate key="admin.conferences.emailUsers"}</label><br/>
			<input type="checkbox" name="options[]" id="options-transcode" value="transcode"{if $options && in_array('transcode', $options)} checked="checked"{/if} /> <label for="options-transcode">{translate key="admin.conferences.transcode"}</label>
		</td>
	</tr>
</table>

<p><input type="submit" value="{translate key="common.import"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url page="admin" op="conferences"}'" /></p>

</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
