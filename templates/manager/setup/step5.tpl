{**
 * step5.tpl
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 5 of conference setup.
 *
 * $Id$
 *}
{assign var="pageTitle" value="manager.setup.loggingAndAuditing.title"}
{include file="manager/setup/setupHeader.tpl"}

<form name="setupForm" method="post" action="{url op="saveSetup" path="5"}" enctype="multipart/form-data">
{include file="common/formErrors.tpl"}

{* There are no localized settings on this page.
{if count($formLocales) > 1}
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{url|assign:"setupFormUrl" op="setup" path="5" escape=false}
			{form_language_chooser form="setupForm" url=$setupFormUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
</table>
{/if}
*}

<p>{translate key="manager.setup.loggingAndAuditing.pageDescription"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="paperEventLog" id="paperEventLog" value="1"{if $paperEventLog} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="paperEventLog">{translate key="manager.setup.loggingAndAuditing.submissionEventLogging"}</label></td>
	</tr>
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="paperEmailLog" id="paperEmailLog" value="1"{if $paperEmailLog} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="paperEmailLog">{translate key="manager.setup.loggingAndAuditing.submissionEmailLogging"}</label></td>
	</tr>
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="conferenceEventLog" id="conferenceEventLog" value="1"{if $conferenceEventLog} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="conferenceEventLog">{translate key="manager.setup.loggingAndAuditing.conferenceEventLogging"}</label></td>
	</tr>
</table>

<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="setup" escape=false}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
