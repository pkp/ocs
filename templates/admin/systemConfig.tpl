{**
 * systemConfig.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to edit system configuration.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="admin.systemConfiguration"}
{include file="common/header.tpl"}
{/strip}

<form method="post" action="{url op="saveSystemConfig"}">
<p>{translate key="admin.editSystemConfigInstructions"}</p>

{foreach from=$configData key=trackName item=trackData}
<h3>{$trackName|escape}</h3>

{if !empty($trackData)}{* Empty tables cause validation problems *}
<table class="data" width="100%">
{foreach from=$trackData key=settingName item=settingValue}
<tr valign="top">	
	<td width="20%" class="label">{$settingName|escape}</td>
	<td width="80%" class="value"><input type="text" name="{$trackName|escape}[{$settingName|escape}]" value="{if $settingValue === true}On{elseif $settingValue === false}Off{else}{$settingValue|escape}{/if}" size="40" class="textField" /></td>
</tr>
{/foreach}
</table>
{/if}{* !empty($trackData) *}

<br />
{/foreach}

<p><input type="submit" value="{translate key="admin.saveSystemConfig"}" class="button defaultButton" /> <input name="display" type="submit" value="{translate key="admin.displayNewSystemConfig"}" class="button" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="systemInfo"}'" /></p>

</form>

{include file="common/footer.tpl"}
