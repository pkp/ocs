{**
 * program.tpl
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Scheduled conference program page.
 *
 * $Id$
 *}
{translate|assign:"pageTitleTranslated" key="schedConf.program.title"}{include file="common/header.tpl"}

{if $programFile}
	<span class="instruct">{translate key="schedConf.program.programFileInstructions"}</span>
	<div>
		<a class="file" href="{$publicFilesDir}/{$programFile.uploadName}" target="_blank">
			{if $programFileTitle}{$programFileTitle|escape}
			{else}{$programFile.name}{/if}</a>
	</div>

<div class="separator"></div>
{/if}


<div>{$program|nl2br}</div>

{include file="common/footer.tpl"}
