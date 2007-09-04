{**
 * upgrade.tpl
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Upgrade form.
 *
 * $Id$
 *}
{assign var="pageTitle" value="installer.ocsUpgrade"}
{include file="common/header.tpl"}

{translate key="installer.upgradeInstructions" version=$version->getVersionString() baseUrl=$baseUrl}


<div class="separator"></div>


<form method="post" action="{url op="installUpgrade"}">
{include file="common/formErrors.tpl"}

{if $isInstallError}
<p>
	<span class="formError">{translate key="installer.installErrorsOccurred"}:</span>
	<ul class="formErrorList">
		<li>{if $dbErrorMsg}{translate key="common.error.databaseError" error=$dbErrorMsg}{else}{translate key=$errorMsg}{/if}</li>
	</ul>
</p>
{/if}


<p><input type="submit" value="{translate key="installer.upgradeOCS"}" class="button defaultButton" /> <input type="submit" name="manualInstall" value="{translate key="installer.manualUpgrade"}" class="button" /></p>

</form>

{include file="common/footer.tpl"}
