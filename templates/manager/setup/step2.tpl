{**
 * step2.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 2 of conference setup.
 *
 * $Id$
 *}
{assign var="pageTitle" value="manager.setup.additionalContent.title"}
{include file="manager/setup/setupHeader.tpl"}

<form name="setupForm" method="post" action="{url op="saveSetup" path="2"}" enctype="multipart/form-data">
{include file="common/formErrors.tpl"}

{if count($formLocales) > 1}
<div id="locales">
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{url|assign:"setupFormUrl" op="setup" path="2" escape=false}
			{form_language_chooser form="setupForm" url=$setupFormUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
</table>
</div>
{/if}
<div id="redirect">
<h3>2.1 {translate key="manager.setup.additionalContent.redirect"}</h3>

<p>{translate key="manager.setup.additionalContent.redirect.description"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="schedConfRedirect" key="manager.setup.additionalContent.schedConfRedirect"}</td>
		<td width="80%" class="value">
			<select name="schedConfRedirect" id="schedConfRedirect" class="selectMenu">
				<option value="">{translate key="manager.setup.additionalContent.redirect.noSchedConfRedirect"}</option>
				{html_options options=$schedConfTitles selected=$schedConfRedirect}
			</select>
		</td>
	</tr>
</table>
</div>
<div id="homepage">
<h3>2.2 {translate key="manager.setup.additionalContent.homepage"}</h3>
<div id="homepageImageInfo">
<h4>{translate key="manager.setup.additionalContent.homepageImage"}</h4>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="homepageImage" key="manager.setup.additionalContent.homepageImage"}</td>
		<td width="80%" class="value"><input type="file" id="homepageImage" name="homepageImage" class="uploadField" /> <input type="submit" name="uploadHomepageImage" value="{translate key="common.upload"}" class="button" /></td>
	</tr>
</table>

{if $homepageImage[$formLocale]}
{translate key="common.fileName"}: {$homepageImage[$formLocale].name} {$homepageImage[$formLocale].dateUploaded|date_format:$datetimeFormatShort} <input type="submit" name="deleteHomepageImage" value="{translate key="common.delete"}" class="button" />
<br />
<img src="{$publicFilesDir}/{$homepageImage[$formLocale].uploadName}" width="{$homepageImage[$formLocale].width}" height="{$homepageImage[$formLocale].height}" style="border: 0;" alt="{translate key="common.conferenceHomepageImage.altText"}" />
<br />
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="homepageImageAltText" key="common.altText"}</td>
		<td width="80%" class="value"><input type="text" id="homepageImageAltText" name="homepageImageAltText[{$formLocale|escape}]" value="{$homepageImageAltText[$formLocale]|escape}" size="40" maxlength="255" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td class="value"><span class="instruct">{translate key="common.altTextInstructions"}</span></td>
		</tr>
</table>
{/if}
</div>
<div id="additionalContent">
<h4>{translate key="manager.setup.additionalContent.additionalContent"}</h4>

<p>{translate key="manager.setup.additionalContent.additionalContent.description"}</p>

<p><textarea name="additionalHomeContent[{$formLocale|escape}]" id="additionalHomeContent" rows="10" cols="60" class="textArea">{$additionalHomeContent[$formLocale]|escape}</textarea></p>
</div>
</div>
<div class="separator"></div>
<div id="additionalInformation">
<h3>2.3 {translate key="manager.setup.additionalContent.information"}</h3>

<p>{translate key="manager.setup.additionalContent.information.description"}</p>
<div id="infoForReaders">
<h4>{translate key="manager.setup.additionalContent.information.forReaders"}</h4>

<p><textarea name="readerInformation[{$formLocale|escape}]" id="readerInformation" rows="10" cols="60" class="textArea">{$readerInformation[$formLocale]|escape}</textarea></p>
</div>
<div id="forAuthors">
<h4>{translate key="manager.setup.additionalContent.information.forAuthors"}</h4>

<p><textarea name="authorInformation[{$formLocale|escape}]" id="authorInformation" rows="10" cols="60" class="textArea">{$authorInformation[$formLocale]|escape}</textarea></p>
</div>
</div>
<div class="separator"></div>
<div id="announcementsSetup">
<h3>2.4 {translate key="manager.setup.additionalContent.announcements"}</h3>

<p>{translate key="manager.setup.additionalContent.announcementsDescription"}</p>

	<script type="text/javascript">
		{literal}
		<!--
			function toggleEnableAnnouncementsHomepage(form) {
				form.numAnnouncementsHomepage.disabled = !form.numAnnouncementsHomepage.disabled;
			}
		// -->
		{/literal}
	</script>

<p>
	<input type="checkbox" name="enableAnnouncements" id="enableAnnouncements" value="1" {if $enableAnnouncements} checked="checked"{/if} />&nbsp;
	{fieldLabel name="enableAnnouncements" key="manager.setup.additionalContent.enableAnnouncements"}
</p>

<p>
	<input type="checkbox" name="enableAnnouncementsHomepage" id="enableAnnouncementsHomepage" value="1" onclick="toggleEnableAnnouncementsHomepage(this.form)"{if $enableAnnouncementsHomepage} checked="checked"{/if} />&nbsp;
	{fieldLabel name="enableAnnouncementsHomepage" key="manager.setup.additionalContent.enableAnnouncementsHomepage1"}
	<select name="numAnnouncementsHomepage" size="1" class="selectMenu" {if not $enableAnnouncementsHomepage}disabled="disabled"{/if}>
		{section name="numAnnouncementsHomepageOptions" start=1 loop=11}
		<option value="{$smarty.section.numAnnouncementsHomepageOptions.index}"{if $numAnnouncementsHomepage eq $smarty.section.numAnnouncementsHomepageOptions.index or ($smarty.section.numAnnouncementsHomepageOptions.index eq 1 and not $numAnnouncementsHomepage)} selected="selected"{/if}>{$smarty.section.numAnnouncementsHomepageOptions.index}</option>
		{/section}
	</select>
	{translate key="manager.setup.additionalContent.enableAnnouncementsHomepage2"}
</p>
<div id="announcementsIntroductionInfo">
<h4>{translate key="manager.setup.additionalContent.announcementsIntroduction"}</h4>

<p>{translate key="manager.setup.additionalContent.announcementsIntroductionDescription"}</p>

<p><textarea name="announcementsIntroduction[{$formLocale|escape}]" id="announcementsIntroduction" rows="10" cols="60" class="textArea">{$announcementsIntroduction[$formLocale]|escape}</textarea></p>
</div>
</div>

<div class="separator"></div>

<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="setup"}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
