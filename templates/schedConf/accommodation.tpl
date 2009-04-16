{**
 * accommodation.tpl
 *
 * Copyright (c) 2000-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Scheduled conference accommodation page.
 *
 * $Id$
 *}
{translate|assign:"pageTitleTranslated" key="schedConf.accommodation.title"}{include file="common/header.tpl"}
{assign var="helpTopicId" value="user.conferenceInformation"}

<div>{$accommodationDescription|nl2br}</div>

{if !empty($accommodationFiles)}
	<div class="separator"></div>
{/if}

{foreach from=$accommodationFiles item=accommodationFile}
	<div>
		<a class="file" href="{$publicFilesDir}/{$accommodationFile.uploadName}" target="_blank">{$accommodationFile.title|default:$accommodationFile.name|escape}</a>
	</div>
{/foreach}

{include file="common/footer.tpl"}
