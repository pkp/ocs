{**
 * step2.tpl
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 2 of conference setup.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.setup.additionalContent.title"}
{include file="manager/setup/setupHeader.tpl"}

<form method="post" action="{url op="saveSetup" path="2"}" enctype="multipart/form-data">
{include file="common/formErrors.tpl"}

<h3>2.1 {translate key="manager.setup.additionalContent.homepage"}</h3>

<h4>{translate key="manager.setup.additionalContent.homepageImage"}</h4>

<p>{translate key="manager.setup.additionalContent.homepageImage.description"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="manager.setup.additionalContent.homepageImage"}</td>
		<td width="80%" class="value"><input type="file" name="homepageImage" class="uploadField" /> <input type="submit" name="uploadHomepageImage" value="{translate key="common.upload"}" class="button" /></td>
	</tr>
</table>

{if $homepageImage}
{translate key="common.fileName"}: {$homepageImage.name} {$homepageImage.dateUploaded|date_format:$datetimeFormatShort} <input type="submit" name="deleteHomepageImage" value="{translate key="common.delete"}" class="button" />
<br />
<img src="{$publicConferenceFilesDir}/{$homepageImage.uploadName}" width="{$homepageImage.width}" height="{$homepageImage.height}" style="border: 0;" alt="" />
{/if}

<h4>{translate key="manager.setup.additionalContent.additionalContent"}</h4>

<p>{translate key="manager.setup.additionalContent.additionalContent.description"}</p>

<p><textarea name="additionalHomeContent" id="additionalHomeContent" rows="10" cols="60" class="textArea">{$additionalHomeContent|escape}</textarea></p>

<div class="separator"></div>

<h3>2.2 {translate key="manager.setup.additionalContent.information"}</h3>

<p>{translate key="manager.setup.additionalContent.information.description"}</p>

<h4>{translate key="manager.setup.additionalContent.information.forReaders"}</h4>

<p><textarea name="readerInformation" id="readerInformation" rows="10" cols="60" class="textArea">{$readerInformation|escape}</textarea></p>

<h4>{translate key="manager.setup.additionalContent.information.forPresenters"}</h4>

<p><textarea name="presenterInformation" id="presenterInformation" rows="10" cols="60" class="textArea">{$presenterInformation|escape}</textarea></p>

<div class="separator"></div>

<h3>2.3 {translate key="manager.setup.additionalContent.announcements"}</h3>

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
	<label for="enableAnnouncements">{translate key="manager.setup.additionalContent.enableAnnouncements"}</label>
</p>

<p>
	<input type="checkbox" name="enableAnnouncementsHomepage" id="enableAnnouncementsHomepage" value="1" onclick="toggleEnableAnnouncementsHomepage(this.form)"{if $enableAnnouncementsHomepage} checked="checked"{/if} />&nbsp;
	<label for="enableAnnouncementsHomepage">{translate key="manager.setup.additionalContent.enableAnnouncementsHomepage1"}</label>
	<select name="numAnnouncementsHomepage" size="1" class="selectMenu" {if not $enableAnnouncementsHomepage}disabled="disabled"{/if}>
		{section name="numAnnouncementsHomepageOptions" start=1 loop=11}
		<option value="{$smarty.section.numAnnouncementsHomepageOptions.index}"{if $numAnnouncementsHomepage eq $smarty.section.numAnnouncementsHomepageOptions.index or ($smarty.section.numAnnouncementsHomepageOptions.index eq 1 and not $numAnnouncementsHomepage)} selected="selected"{/if}>{$smarty.section.numAnnouncementsHomepageOptions.index}</option>
		{/section}
	</select>
	{translate key="manager.setup.additionalContent.enableAnnouncementsHomepage2"}
</p>

<h4>{translate key="manager.setup.additionalContent.announcementsIntroduction"}</h4>

<p>{translate key="manager.setup.additionalContent.announcementsIntroductionDescription"}</p>

<p><textarea name="announcementsIntroduction" id="announcementsIntroduction" rows="10" cols="60" class="textArea">{$announcementsIntroduction|escape}</textarea></p>

<div class="separator"></div>

<h3>2.4 {translate key="manager.setup.additionalContent.archiveAccess"}</h3>

<p>{translate key="manager.setup.additionalContent.archiveAccess.description"}</p>

<p>
	<input type="radio" name="paperAccess" id="paperAccess-1" value="{$smarty.const.PAPER_ACCESS_OPEN}" {if $paperAccess == PAPER_ACCESS_OPEN || $paperAccess == ""} checked="checked"{/if} />&nbsp;<label for="paperAccess-1">{translate key="manager.setup.additionalContent.archiveAccess.open"}</label><br/>
	<input type="radio" name="paperAccess" id="paperAccess-2" value="{$smarty.const.PAPER_ACCESS_ACCOUNT_REQUIRED}" {if $paperAccess == PAPER_ACCESS_ACCOUNT_REQUIRED} checked="checked"{/if} />&nbsp;<label for="paperAccess-2">{translate key="manager.setup.additionalContent.archiveAccess.accountRequired"}</label><br/>
	<input type="radio" name="paperAccess" id="paperAccess-3" value="{$smarty.const.PAPER_ACCESS_REGISTRATION_REQUIRED}" {if $paperAccess == PAPER_ACCESS_REGISTRATION_REQUIRED} checked="checked"{/if} />&nbsp;<label for="paperAccess-3">{translate key="manager.setup.additionalContent.archiveAccess.registrationRequired"}</label><br/>
</p>


<div class="separator"></div>

<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="setup" escape=false}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
