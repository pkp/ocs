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
{include file="common/header.tpl"}
{/strip}

{if $mainPage}
	<p>{translate key="manager.plugins.description"}</p>

	<ul id="plugins" class="plain">
	{foreach from=$plugins item=plugin}
		{if $plugin->getCategory() != $category}
			{assign var=category value=$plugin->getCategory()}
			<li>&#187; <a href="{url path=$category|escape}">{translate key="plugins.categories.$category"}</a></li>
		{/if}
	{/foreach}
		<li>&nbsp;</li>
		<li><b><a href="{url op="managePlugins" path=install}">{translate key="manager.plugins.install"}</a></b></li>
	</ul>
{else}
	{foreach from=$plugins item=plugin}
		{if $plugin->getCategory() != $category}
			{assign var=category value=$plugin->getCategory()}
			<div id="{$category|escape}">
			<p>{translate key="plugins.categories.$category.description"}</p>
			</div>
		{/if}
	{/foreach}

	<ul id="plugins" class="plain">
	{foreach from=$plugins item=plugin}
		{if !$plugin->getHideManagement()}
		{if $plugin->getCategory() != $category}
			{assign var=category value=$plugin->getCategory()}
			<div id="{$category|escape}">
			<h3>{translate key="plugins.categories.$category"}</h3>
			<p>{translate key="plugins.categories.$category.description"}</p>
			</div>
		{/if}
		<li><h4>{$plugin->getDisplayName()|escape}</h4>
		<p>
		{$plugin->getDescription()}<br/>
		{assign var=managementVerbs value=$plugin->getManagementVerbs()}
		{if $managementVerbs && $plugin->isSitePlugin() && !$isSiteAdmin}
			<em>{translate key="manager.plugins.sitePlugin"}</em>
		{elseif $managementVerbs}
			{foreach from=$managementVerbs item=verb}
				<a class="action" href="{url op="plugin" path=$category|to_array:$plugin->getName():$verb[0]}">{$verb[1]|escape}</a>&nbsp;
			{/foreach}
		{/if}
		{assign var=pluginInstallName value=$plugin->getPluginPath()|basename}
		{if $plugin->getCurrentVersion()}
			<a class="action" href="{url op="managePlugins" path="upgrade"|to_array:$pluginInstallName}">{translate key="manager.plugins.upgrade"}</a>&nbsp;
			<a class="action" href="{url op="managePlugins" path="delete"|to_array:$pluginInstallName}">{translate key="manager.plugins.delete"}</a>&nbsp;
		{/if}
		</p></li>
		{/if}{* !$plugin->getHideManagement() *}
	{/foreach}
	</ul>
{/if}

{include file="common/footer.tpl"}
