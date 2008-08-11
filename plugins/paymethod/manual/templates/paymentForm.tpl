{**
 * paymentForm.tpl
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Manual payment page
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.paymethod.manual"}
{include file="common/header.tpl"}
{/strip}

<p>{$message|nl2br}</p>

{include file="common/footer.tpl"}
