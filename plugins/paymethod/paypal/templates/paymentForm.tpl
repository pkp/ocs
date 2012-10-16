{**
 * plugins/paymethod/paypal/templates/paymentForm.tpl
 *
 * Copyright (c) 2006-2009 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for submitting a PayPal payment
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.paymethod.paypal"}
{include file="common/header.tpl"}
{/strip}

<table>
	<tr>
		<td><img src="{$baseUrl}/plugins/paymethod/paypal/images/paypal.gif" alt="paypal" /></td>
		<td>{$paypalDescription}</td>
	</tr>
</table>

<p>{translate key="plugins.paymethod.paypal.warning"}</p>

<form class="pkp_form" action="{$paypalFormUrl}" id="paypalPaymentForm" method="post" style="margin-bottom: 0px;">
	{include file="common/formErrors.tpl"}

	{if $registration}
		{assign var=totalCost value=$registrationType->getCost()}
		<table class="data" width="100%">
			<tr valign="top">
				<td class="label" width="20%">{translate key="plugins.paymethod.paypal.purchase.description"}</td>
				<td class="value" width="80%">
					{$registrationType->getSummaryString()}
					{foreach from=$registrationOptions item=registrationOption}
						{assign var=registrationOptionId value=$registrationOption->getOptionId()}
						{assign var=registrationOptionCost value=$registrationOptionCosts[$registrationOptionId]}
						{if in_array($registrationOptionId, $registrationOptionIds)}
							<br/>{$registrationOption->getRegistrationOptionName()} - {$registrationOptionCosts[$registrationOptionId]|string_format:"%.2f"} {$registrationType->getCurrencyCodeAlpha()}
							{assign var=totalCost value=$totalCost+$registrationOptionCost}
						{/if}
					{/foreach}
				</td>
			</tr>
			<tr valign="top">
				<td class="label">{translate key="plugins.paymethod.paypal.purchase.total"}</td>
				<td class="value"><strong>{$totalCost|string_format:"%.2f"} {$registrationType->getCurrencyCodeAlpha()}</strong></td>
			</tr>
			<tr valign="top">
				<td class="label">{translate key="schedConf.registration.specialRequests"}</td>
				<td class="value">{$registration->getSpecialRequests()|escape|default:"&mdash;"}</td>
			</tr>
		</table>
	{else}{* $registration *}
		<table class="data" width="100%">
		        <tr valign="top">
		                <td class="label" width="20%">{translate key="plugins.paymethod.paypal.purchase.amount"}</td>
		                <td class="value" width="80%"><strong>{$params.amount|string_format:"%.2f"}{if $params.currency_code} ({$params.currency_code|escape}){/if}</strong></td>
		        </tr>
		        <tr valign="top">
		                <td class="label">{translate key="plugins.paymethod.paypal.purchase.description"}</td>
		                <td class="value"><strong>{$params.item_name|escape}</strong></td>
		        </tr>
		</table>
	{/if}{* $registration *}

	{foreach from=$params key="name" item="value"}
		<input type="hidden" name="{$name|escape}" value="{$value|escape}" />
	{/foreach}

	<p><input type="submit" name="submitBtn" value="{translate key="common.continue"}" class="button defaultButton" /></p>
</form>

{include file="common/footer.tpl"}
