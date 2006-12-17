{**
 * step4.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 4 of conference setup.
 *
 * $Id$
 *}

{assign var="pageTitle" value="director.setup.indexingAndMetadata}
{include file="director/setup/setupHeader.tpl"}

<form method="post" action="{url op="saveSetup" path="4"}" enctype="multipart/form-data">
{include file="common/formErrors.tpl"}

<h3>4.1 {translate key="director.setup.searchEngineIndexing"}</h3>

<p>{translate key="director.setup.searchEngineIndexingDescription"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="searchDescription" key="common.description"}</td>
		<td width="80%" class="value"><input type="text" name="searchDescription" id="searchDescription" value="{$searchDescription|escape}" size="40" maxlength="255" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="searchKeywords" key="common.keywords"}</td>
		<td width="80%" class="value"><input type="text" name="searchKeywords" id="searchKeywords" value="{$searchKeywords|escape}" size="40" maxlength="255" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="customHeaders" key="director.setup.customTags"}</td>
		<td width="80%" class="value">
			<textarea name="customHeaders" id="customHeaders" rows="3" cols="40" class="textArea">{$customHeaders|escape}</textarea>
			<br />
			<span class="instruct">{translate key="director.setup.customTagsDescription"}</span>
		</td>
	</tr>
</table>


<div class="separator"></div>


<h3>4.2 {translate key="director.setup.forAuthorsToIndexTheirWork"}</h3>

<p>{translate key="director.setup.forAuthorsToIndexTheirWorkDescription"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="metaDiscipline" id="metaDiscipline" value="1"{if $metaDiscipline} checked="checked"{/if} /></td>
		<td width="95%" class="value">
			<strong>{fieldLabel name="metaDiscipline" key="director.setup.discipline"}</strong>
			<br />
			<span class="instruct">{translate key="director.setup.disciplineDescription"}</span>
		</td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td class="value">
			<span class="instruct">{translate key="director.setup.disciplineProvideExamples"}:</span>
			<br />
			<input type="text" name="metaDisciplineExamples" id="metaDisciplineExamples" value="{$metaDisciplineExamples|escape}" size="60" maxlength="255" class="textField" />
			<br />
			<span class="instruct">{translate key="director.setup.disciplineExamples"}</span>
		</td>
	</tr>
	
	<tr>
		<td class="separator" colspan="2"><br />&nbsp;</td>
	</tr>
	
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="metaSubjectClass" id="metaSubjectClass" value="1"{if $metaSubjectClass} checked="checked"{/if} /></td>
		<td width="95%" class="value">
			<strong>{fieldLabel name="metaSubjectClass" key="director.setup.subjectClassification"}</strong>
		</td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td class="value">
			<table width="100%">
				<tr valign="top">
					<td width="10%">{fieldLabel name="metaSubjectClassTitle" key="common.title"}</td>
					<td width="90%"><input type="text" name="metaSubjectClassTitle" id="metaSubjectClassTitle" value="{$metaSubjectClassTitle|escape}" size="40" maxlength="255" class="textField" /></td>
				</tr>
				<tr valign="top">
					<td width="10%">{fieldLabel name="metaSubjectClassUrl" key="common.url"}</td>
					<td width="90%"><input type="text" name="metaSubjectClassUrl" id="metaSubjectClassUrl" value="{if $metaSubjectClassUrl}{$metaSubjectClassUrl|escape}{else}http://{/if}" size="40" maxlength="255" class="textField" /></td>
				</tr>
			</table>
			<span class="instruct">{translate key="director.setup.subjectClassificationExamples"}</span>
		</td>
	</tr>
	
	<tr>
		<td class="separator" colspan="2"><br />&nbsp;</td>
	</tr>
	
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="metaSubject" id="metaSubject" value="1"{if $metaSubject} checked="checked"{/if} /></td>
		<td width="95%" class="value">
			<strong>{fieldLabel name="metaSubject" key="director.setup.subjectKeywordTopic"}</strong>
		</td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td class="value">
			<span class="instruct">{translate key="director.setup.subjectProvideExamples"}:</span>
			<br />
			<input type="text" name="metaSubjectExamples" id="metaSubjectExamples" value="{$metaSubjectExamples|escape}" size="60" maxlength="255" class="textField" />
			<br />
			<span class="instruct">{translate key="director.setup.subjectExamples"}</span>
		</td>
	</tr>
	
	<tr>
		<td class="separator" colspan="2"><br />&nbsp;</td>
	</tr>
	
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="metaCoverage" id="metaCoverage" value="1"{if $metaCoverage} checked="checked"{/if} /></td>
		<td width="95%" class="value">
			<strong>{fieldLabel name="metaCoverage" key="director.setup.coverage"}</strong>
			<br />
			<span class="instruct">{translate key="director.setup.coverageDescription"}</span>
		</td>
	</tr>
	<tr>
		<td class="separator" colspan="2">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td class="value">
			<span class="instruct">{translate key="director.setup.coverageGeoProvideExamples"}:</span>
			<br />
			<input type="text" name="metaCoverageGeoExamples" id="metaCoverageGeoExamples" value="{$metaCoverageGeoExamples|escape}" size="60" maxlength="255" class="textField" />
			<br />
			<span class="instruct">{translate key="director.setup.coverageGeoExamples"}</span>
		</td>
	</tr>
	<tr>
		<td class="separator" colspan="2">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td class="value">
			<span class="instruct">{translate key="director.setup.coverageChronProvideExamples"}:</span>
			<br />
			<input type="text" name="metaCoverageChronExamples" id="metaCoverageChronExamples" value="{$metaCoverageChronExamples|escape}" size="60" maxlength="255" class="textField" />
			<br />
			<span class="instruct">{translate key="director.setup.coverageChronExamples"}</span>
		</td>
	</tr>
	<tr>
		<td class="separator" colspan="2">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td class="value">
			<span class="instruct">{translate key="director.setup.coverageResearchSampleProvideExamples"}:</span>
			<br />
			<input type="text" name="metaCoverageResearchSampleExamples" id="metaCoverageResearchSampleExamples" value="{$metaCoverageResearchSampleExamples|escape}" size="60" maxlength="255" class="textField" />
			<br />
			<span class="instruct">{translate key="director.setup.coverageResearchSampleExamples"}</span>
		</td>
	</tr>
	
	<tr>
		<td class="separator" colspan="2"><br />&nbsp;</td>
	</tr>
	
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="metaType" id="metaType" value="1"{if $metaType} checked="checked"{/if} /></td>
		<td width="95%" class="value">
			<strong>{fieldLabel name="metaType" key="director.setup.typeMethodApproach"}</strong>
		</td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td class="value">
			<span class="instruct">{translate key="director.setup.typeProvideExamples"}:</span>
			<br />
			<input type="text" name="metaTypeExamples" id="metaTypeExamples" value="{$metaTypeExamples|escape}" size="60" maxlength="255" class="textField" />
			<br />
			<span class="instruct">{translate key="director.setup.typeExamples"}</span>
		</td>
	</tr>
</table>


<div class="separator"></div>


<h3>4.3 {translate key="director.setup.registerConferenceForIndexing"}</h3>

{url|assign:"oaiSiteUrl" conference=$currentConference->getPath()}
{url|assign:"oaiUrl" page="oai"}
<p>{translate key="director.setup.registerConferenceForIndexingDescription" siteUrl=$oaiSiteUrl oaiUrl=$oaiUrl}</p>


<div class="separator"></div>

<h3>4.4 {translate key="director.setup.publicIdentifier"}</h3>

<h4>{translate key="director.setup.uniqueIdentifier"}</h4>

<p>{translate key="director.setup.uniqueIdentifierDescription"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="enablePublicPaperId" id="enablePublicPaperId" value="1"{if $enablePublicPaperId} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="enablePublicPaperId">{translate key="director.setup.enablePublicPaperId"}</label></td>
	</tr>
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="enablePublicSuppFileId" id="enablePublicSuppFileId" value="1"{if $enablePublicSuppFileId} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="enablePublicSuppFileId">{translate key="director.setup.enablePublicSuppFileId"}</label></td>
	</tr>
</table>

<br />

<div class="separator"></div>


<h3>4.5 {translate key="director.setup.announcements"}</h3>

<p>{translate key="director.setup.announcementsDescription"}</p>

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
	<label for="enableAnnouncements">{translate key="director.setup.enableAnnouncements"}</label>
</p>

<p>
	<input type="checkbox" name="enableAnnouncementsHomepage" id="enableAnnouncementsHomepage" value="1" onclick="toggleEnableAnnouncementsHomepage(this.form)"{if $enableAnnouncementsHomepage} checked="checked"{/if} />&nbsp;
	<label for="enableAnnouncementsHomepage">{translate key="director.setup.enableAnnouncementsHomepage1"}</label>
	<select name="numAnnouncementsHomepage" size="1" class="selectMenu" {if not $enableAnnouncementsHomepage}disabled="disabled"{/if}>
		{section name="numAnnouncementsHomepageOptions" start=1 loop=11}
		<option value="{$smarty.section.numAnnouncementsHomepageOptions.index}"{if $numAnnouncementsHomepage eq $smarty.section.numAnnouncementsHomepageOptions.index or ($smarty.section.numAnnouncementsHomepageOptions.index eq 1 and not $numAnnouncementsHomepage)} selected="selected"{/if}>{$smarty.section.numAnnouncementsHomepageOptions.index}</option>
		{/section}
	</select>
	{translate key="director.setup.enableAnnouncementsHomepage2"}
</p>

<h4>{translate key="director.setup.announcementsIntroduction"}</h4>

<p>{translate key="director.setup.announcementsIntroductionDescription"}</p>

<p><textarea name="announcementsIntroduction" id="announcementsIntroduction" rows="12" cols="60" class="textArea">{$announcementsIntroduction|escape}</textarea></p>


<div class="separator"></div>


<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="setup" escape=false}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
