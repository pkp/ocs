{**
 * step3.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 3 of conference setup.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.setup.accessAndPermissions}
{include file="manager/setup/setupHeader.tpl"}

<form method="post" action="{url op="saveSetup" path="3"}">
{include file="common/formErrors.tpl"}

<h3>3.1 {translate key="manager.setup.securitySettings"}</h3>

<p>{translate key="manager.setup.securitySettingsDescription"}</p>

<h4>{translate key="manager.setup.siteAccess"}</h4>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label"><input type="radio" name="restrictSiteAccess" id="restrictSiteAccess-0" value="0"{if !$restrictSiteAccess} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="restrictSiteAccess-0">{translate key="manager.setup.noRestrictSiteAccess"}</label></td>
	</tr>
	<tr valign="top">
		<td width="5%" class="label"><input type="radio" name="restrictSiteAccess" id="restrictSiteAccess-1" value="1"{if $restrictSiteAccess} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="restrictSiteAccess-1">{translate key="manager.setup.restrictSiteAccess"}</label></td>
	</tr>
</table>

<h4>{translate key="manager.setup.paperAccess"}</h4>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label"><input type="radio" name="restrictPaperAccess" id="restrictPaperAccess-0" value="0"{if !$restrictPaperAccess} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="restrictPaperAccess-0">{translate key="manager.setup.noRestrictPaperAccess"}</label></td>
	</tr>
	<tr valign="top">
		<td width="5%" class="label"><input type="radio" name="restrictPaperAccess" id="restrictPaperAccess-1" value="1"{if $restrictPaperAccess} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="restrictPaperAccess-1">{translate key="manager.setup.restrictPaperAccess"}</label></td>
	</tr>
</table>

<h4>{translate key="manager.setup.comments"}</h4>

<table width="100%" class="data">
{foreach from=$commentsOptions item=keyName key=value}
	<tr valign="top">
		<td width="5%" class="label"><input type="radio" name="enableComments" id="enableComments-{$value}" value="{$value}"{if $enableComments==$value} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="enableComments-{$value}">{translate key=$keyName}</label></td>
	</tr>
{/foreach}
</table>

<h4>{translate key="manager.setup.loggingAndAuditing"}</h4>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="paperEventLog" id="paperEventLog" value="1"{if $paperEventLog} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="paperEventLog">{translate key="manager.setup.submissionEventLogging"}</label></td>
	</tr>
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="paperEmailLog" id="paperEmailLog" value="1"{if $paperEmailLog} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="paperEmailLog">{translate key="manager.setup.submissionEmailLogging"}</label></td>
	</tr>
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="conferenceEventLog" id="conferenceEventLog" value="1"{if $conferenceEventLog} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="conferenceEventLog">{translate key="manager.setup.conferenceEventLogging"}</label></td>
	</tr>
</table>

{*
<div class="separator"></div>


<h3>3.2 {translate key="manager.setup.conferenceArchiving"}</h3>

<p>{translate key="manager.setup.lockssDescription"}</p>

{url|assign:"lockssExistingArchiveUrl" page="manager" op="email" template="LOCKSS_EXISTING_ARCHIVE"}
{url|assign:"lockssNewArchiveUrl" page="manager" op="email" template="LOCKSS_NEW_ARCHIVE"}
<p>{translate key="manager.setup.lockssRegister" lockssExistingArchiveUrl=$lockssExistingArchiveUrl lockssNewArchiveUrl=$lockssNewArchiveUrl}</p>

{url|assign:"lockssUrl" page="gateway" op="lockss"}
<p><input type="checkbox" name="enableLockss" id="enableLockss" value="1"{if $enableLockss} checked="checked"{/if} /> <label for="enableLockss">{translate key="manager.setup.lockssEnable" lockssUrl=$lockssUrl}</label></p>

<p>
	<textarea name="lockssLicense" id="lockssLicense" rows="6" cols="60" class="textArea">{$lockssLicense|escape}</textarea>
	<br />
	<span class="instruct">{translate key="manager.setup.lockssLicenses"}</span>
</p>
*}

<div class="separator"></div>


<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="setup" escape=false}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
