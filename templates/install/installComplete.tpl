{**
 * installComplete.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display confirmation of successful installation.
 * If necessary, will also display new config file contents if config file could not be written.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="installer.ocsInstallation"}
{include file="common/header.tpl"}
{/strip}

{url|assign:"loginUrl" page="login"}
{translate key="installer.installationComplete" loginUrl=$loginUrl}

{if $writeConfigFailed}
{translate key="installer.overwriteConfigFileInstructions"}

<form action="#">
<p>
{translate key="installer.contentsOfConfigFile"}:<br />
<textarea name="config" cols="80" rows="20" class="textArea" style="font-family: Courier,'Courier New',fixed-width">{$configFileContents|escape}</textarea>
</p>
</form>
{/if}

{include file="common/footer.tpl"}
