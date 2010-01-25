{**
 * editCustomBlockForm.tpl
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for editing a custom sidebar block
 *
 *}
{assign var="pageTitle" value="plugins.generic.customBlock.editContent"} 
{include file="common/header.tpl"}
<br />
<form method="post" name="editCustomBlockForm" action="{plugin_url path="save"}" >
{include file="common/formErrors.tpl"}
<table class="data" width="100%">
	<tr>
		<td width="20%" class="label" valign="top">{fieldLabel required="true" name="blockContent" key="plugins.generic.customBlock.content"}</td>
		<td>
		<textarea name="blockContent" cols="30" rows="30">{$blockContent|escape}</textarea>
		</td>
	</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" />
<input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{plugin_url path="settings"}'" /></p>

</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
