{**
 * plugins.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List available import/export plugins.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="manager.importExport"}
{include file="common/header.tpl"}
{/strip}

<ul id="plugins">
	{foreach from=$plugins item=plugin}
	<li><a href="{url op="importexport" path="plugin"|to_array:$plugin->getName()}">{$plugin->getDisplayName()|escape}</a>:&nbsp;{$plugin->getDescription()|escape}</li>
	{foreachelse}
		<li>{translate key="manager.importExport.noPlugins"}</li>
	{/foreach}
</ul>

{include file="common/footer.tpl"}
