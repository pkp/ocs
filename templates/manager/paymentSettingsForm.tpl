{**
 * paymentSettingsForm.tpl
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to edit payment settings.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.payment.paymentSettings"}
{include file="common/header.tpl"}

{include file="common/formErrors.tpl"}

<script type="text/javascript">
<!--

{literal}
function changePaymentMethod() {
	document.paymentSettingsForm.action="{/literal}{url op="paymentSettings" escape=false}{literal}";
		document.paymentSettingsForm.submit();
	}

	{/literal}
// -->
</script>

<form method="post" name="paymentSettingsForm" action="{url op="savePaymentSettings"}">

<p>{translate key="manager.payment.form.description"}</p>


<table width="100%" class="data">
	<tr valign="top">
		<td class="label" width="20%">{fieldLabel for="paymentMethodPluginName" key="manager.payment.form.method"}</td>
		<td class="data" width="80%">
			<select name="paymentMethodPluginName" id="paymentMethodPluginName" class="selectMenu" onchange="changePaymentMethod();">
				{foreach from=$paymentMethodPlugins item=plugin}
					<option {if $paymentMethodPluginName == $plugin->getName()}selected="selected" {/if}value="{$plugin->getName()|escape}">{$plugin->getDisplayName()|escape}</option>
				{/foreach}
			</select>
		</td>
	{call_hook name="Template::Manager::Payment::displayPaymentSettingsForm" plugin=$paymentMethodPluginName}
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url page="manager"}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
