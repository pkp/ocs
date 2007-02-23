{**
 * step1.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 1 of scheduled conference setup.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.setup.gettingDownTheDetails}
{include file="manager/schedConfSetup/setupHeader.tpl"}

<form method="post" action="{url op="saveSchedConfSetup" path="1"}">
{include file="common/formErrors.tpl"}

<h3>1.1 {translate key="manager.setup.generalInformation"}</h3>
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="schedConfTitle" required="true" key="manager.setup.title"}</td>
		<td width="80%" class="value"><input type="text" name="schedConfTitle" id="schedConfTitle" value="{$schedConfTitle|escape}" size="40" maxlength="120" class="textField" /></td>
	</tr>
</table>

<div class="separator"></div>

<h3>1.2 {translate key="manager.setup.description"}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="schedConfIntroduction" key="manager.setup.schedConfIntroduction"}</td>
		<td width="80%" class="value">
			<textarea name="schedConfIntroduction" id="schedConfIntroduction" rows="5" cols="60" class="textArea">{$schedConfIntroduction|escape}</textarea>
			<br />
			<span class="instruct">{translate key="manager.setup.schedConfIntroductionDescription"}</span>
		</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="schedConfOverview" key="manager.setup.schedConfOverview"}</td>
		<td width="80%" class="value">
			<textarea name="schedConfOverview" id="schedConfOverview" rows="15" cols="60" class="textArea">{$schedConfOverview|escape}</textarea>
			<br />
			<span class="instruct">{translate key="manager.setup.schedConfOverviewDescription"}</span>
		</td>
	</tr>
</table>

<div class="separator"></div>

<h3>1.3 {translate key="manager.schedConfSetup.location"}</h3>

<p>{translate key="manager.schedConfSetup.locationDescription"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="location" key="manager.setup.location"}</td>
		<td width="80%" class="value">
			<textarea name="location" id="location" rows="5" cols="60" class="textArea">{$location|escape}</textarea>
		</td>
	</tr>
</table>

<div class="separator"></div>

<h3>1.4 {translate key="manager.setup.sponsors"}</h3>

<p>{translate key="manager.setup.sponsorsDescription"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="sponsorNote" key="manager.setup.note"}</td>
		<td width="80%" class="value"><textarea name="sponsorNote" id="sponsorNote" rows="5" cols="40" class="textArea">{$sponsorNote|escape}</textarea></td>
	</tr>
{foreach name=sponsors from=$sponsors key=sponsorId item=sponsor}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="sponsors-$sponsorId-institution" key="manager.setup.institution"}</td>
		<td width="80%" class="value">
			<input type="text" name="sponsors[{$sponsorId}][institution]" id="sponsors-{$sponsorId}-institution" value="{$sponsor.institution|escape}" size="40" maxlength="90" class="textField" />
			{if $smarty.foreach.sponsors.total > 1}
				<input type="submit" name="delSponsor[{$sponsorId}]" value="{translate key="common.delete"}" class="button" />
			{/if}
		</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="sponsors-$sponsorId-address" key="common.mailingAddress"}</td>
		<td width="80%" class="value">
			<textarea name="sponsors[{$sponsorId}][address]" id="sponsors-{$sponsorId}-address" rows="4" cols="35" class="textArea">{$sponsor.address|escape}</textarea>
		</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="sponsors-$sponsorId-url" key="common.url"}</td>
		<td width="80%" class="value"><input type="text" name="sponsors[{$sponsorId}][url]" id="sponsors-{$sponsorId}-url" value="{$sponsor.url|escape}" size="40" maxlength="255" class="textField" /></td>
	</tr>
	{if !$smarty.foreach.sponsors.last}
	<tr valign="top">
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
{foreachelse}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="sponsors-0-institution" key="manager.setup.institution"}</td>
		<td width="80%" class="value"><input type="text" name="sponsors[0][institution]" id="sponsors-0-institution" size="40" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="sponsors-0-address" key="common.mailingAddress"}</td>
		<td width="80%" class="value"><textarea name="sponsors[0][address]" id="sponsors-0-address" rows="4" cols="35" class="textArea">{$sponsors[0][address]|escape}</textarea>
		</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="sponsors-0-url" key="common.url"}</td>
		<td width="80%" class="value"><input type="text" name="sponsors[0][url]" id="sponsors-0-url" size="40" maxlength="255" class="textField" /></td>
	</tr>
{/foreach}
</table>

<p><input type="submit" name="addSponsor" value="{translate key="manager.setup.addSponsor"}" class="button" /></p>


<div class="separator"></div>


<h3>1.5 {translate key="manager.setup.contributors"}</h3>

<p>{translate key="manager.setup.contributorsDescription"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="contributorNote" key="manager.setup.note"}</td>
		<td width="80%" class="value"><textarea name="contributorNote" id="contributorNote" rows="5" cols="40" class="textArea">{$contributorNote|escape}</textarea></td>
	</tr>
{foreach name=contributors from=$contributors key=contributorId item=contributor}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="contributors-$contributorId-name" key="manager.setup.contributor"}</td>
		<td width="80%" class="value"><input type="text" name="contributors[{$contributorId}][name]" id="contributors-{$contributorId}-name" value="{$contributor.name|escape}" size="40" maxlength="90" class="textField" />{if $smarty.foreach.contributors.total > 1} <input type="submit" name="delContributor[{$contributorId}]" value="{translate key="common.delete"}" class="button" />{/if}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="contributors-$contributorId-url" key="common.url"}</td>
		<td width="80%" class="value"><input type="text" name="contributors[{$contributorId}][url]" id="contributors-{$contributorId}-url" value="{$contributor.url|escape}" size="40" maxlength="255" class="textField" /></td>
	</tr>
	{if !$smarty.foreach.contributors.last}
	<tr valign="top">
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
{foreachelse}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="contributors-0-name" key="manager.setup.contributor"}</td>
		<td width="80%" class="value"><input type="text" name="contributors[0][name]" id="contributors-0-name" size="40" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="contributors-0-url" key="common.url"}</td>
		<td width="80%" class="value"><input type="text" name="contributors[0][url]" id="contributors-0-url" size="40" maxlength="255" class="textField" /></td>
	</tr>
{/foreach}
</table>

<p><input type="submit" name="addContributor" value="{translate key="manager.setup.addContributor"}" class="button" /></p>


<div class="separator"></div>


<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="schedConfSetup" escape=false}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
