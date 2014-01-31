{**
 * index.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List of operations this plugin can perform
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.importexport.METSExport.displayName"}
{include file="common/header.tpl"}
{/strip}

<br/>

<h3>{translate key="plugins.importexport.METSExport.export"}</h3>
<ul class="plain">
	<li>&#187; <a href="{plugin_url path="schedConfs"}">{translate key="plugins.importexport.METSExport.export.schedConfs"}</a></li>
</ul>

{include file="common/footer.tpl"}
