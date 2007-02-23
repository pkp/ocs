{**
 * step5.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 5 of conference setup.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.setup.access.title"}
{include file="manager/setup/setupHeader.tpl"}

<form method="post" action="{url op="saveSetup" path="5"}" enctype="multipart/form-data">
{include file="common/formErrors.tpl"}

<h3>5.1 {translate key="manager.setup.access.securitySettings"}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="restrictSiteAccess" id="restrictSiteAccess" value="1"{if $restrictSiteAccess} checked="checked"{/if} /></td>
		<td width="95%" colspan="2" class="value"><label for="restrictSiteAccess">{translate key="manager.setup.access.restrictSiteAccess"}</label></td>
	</tr>
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="enableComments" id="enableComments" value="1"{if $enableComments} checked="checked"{/if} /></td>
		<td width="95%" colspan="2" class="value"><label for="enableComments">{translate key="manager.setup.access.comments.enable"}</label></td>
	</tr>
	<tr valign="top">
		<td width="5%" class="label">&nbsp;</td>
		<td width="5%" class="label"><input type="checkbox" name="commentsRequireRegistration" id="commentsRequireRegistration" value="1"{if $commentsRequireRegistration} checked="checked"{/if} /></td>
		<td width="90%" class="value"><label for="commentsRequireRegistration">{translate key="manager.setup.access.comments.requireRegistration"}</label></td>
	</tr>
	<tr valign="top">
		<td width="5%" class="label">&nbsp;</td>
		<td width="5%" class="label"><input type="checkbox" name="commentsAllowAnonymous" id="commentsAllowAnonymous" value="1"{if $commentsAllowAnonymous} checked="checked"{/if} /></td>
		<td width="90%" class="value"><label for="commentsAllowAnonymous">{translate key="manager.setup.access.comments.allowAnonymous"}</label></td>
	</tr>
</table>

<h3>5.2 {translate key="manager.setup.access.loggingAndAuditing"}</h3>

<p>{translate key="manager.setup.access.loggingAndAuditing.description"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="paperEventLog" id="paperEventLog" value="1"{if $paperEventLog} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="paperEventLog">{translate key="manager.setup.access.submissionEventLogging"}</label></td>
	</tr>
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="paperEmailLog" id="paperEmailLog" value="1"{if $paperEmailLog} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="paperEmailLog">{translate key="manager.setup.access.submissionEmailLogging"}</label></td>
	</tr>
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="conferenceEventLog" id="conferenceEventLog" value="1"{if $conferenceEventLog} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="conferenceEventLog">{translate key="manager.setup.access.conferenceEventLogging"}</label></td>
	</tr>
</table>

<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="setup" escape=false}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
