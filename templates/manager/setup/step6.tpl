{**
 * step6.tpl
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 6 of conference setup.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.setup.indexing.title"}
{include file="manager/setup/setupHeader.tpl"}

<form method="post" action="{url op="saveSetup" path="6"}">
{include file="common/formErrors.tpl"}

<h3>6.1 {translate key="manager.setup.indexing.searchEngineIndexing"}</h3>

<p>{translate key="manager.setup.indexing.searchEngineIndexing.description"}</p>

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
		<td width="20%" class="label">{fieldLabel name="customHeaders" key="manager.setup.indexing.customTags"}</td>
		<td width="80%" class="value">
			<textarea name="customHeaders" id="customHeaders" rows="3" cols="40" class="textArea">{$customHeaders|escape}</textarea>
			<br />
			<span class="instruct">{translate key="manager.setup.indexing.customTagsDescription"}</span>
		</td>
	</tr>
</table>

<div class="separator"></div>

<h3>6.2 {translate key="manager.setup.indexing.registerConferenceForIndexing"}</h3>

{url|assign:"oaiSiteUrl" conference=$currentConference->getPath()}
{url|assign:"oaiUrl" page="oai"}
<p>{translate key="manager.setup.indexing.registerConferenceForIndexing.description" siteUrl=$oaiSiteUrl oaiUrl=$oaiUrl}</p>

<div class="separator"></div>

<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="setup" escape=false}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
