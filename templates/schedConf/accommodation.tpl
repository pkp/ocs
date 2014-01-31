{**
 * accommodation.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Scheduled conference accommodation page.
 *
 * $Id$
 *}
{strip}
{translate|assign:"pageTitleTranslated" key="schedConf.accommodation.title"}
{include file="common/header.tpl"}
{/strip}

<div id="accomodationDescription">{$accommodationDescription|nl2br}</div>

{if !empty($accommodationFiles)}
	<div class="separator"></div>
{/if}
<div id="accommodationFiles">
{foreach from=$accommodationFiles item=accommodationFile}
	<div>
		<a class="file" href="{$publicSchedConfFilesDir}/{$accommodationFile.uploadName}" target="_blank">{$accommodationFile.title|default:$accommodationFile.name|escape}</a>
	</div>
{/foreach}
</div>
{include file="common/footer.tpl"}
