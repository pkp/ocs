{**
 * program.tpl
 *
 * Copyright (c) 2006-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Scheduled conference program page.
 *
 * $Id$
 *}

{assign var="pageTitle" value="schedConf.program"}
{include file="common/header.tpl"}

{if $programFile}
	<span class="instruct">{translate key="schedConf.program.programFileInstructions"}</span>
	<div>
		<a class="file" href="{$publicFilesDir}/{$programFile.uploadName}" target="_blank" alt="">{translate key="schedConf.program.viewProgramFile"}</a>
	</div>
{/if}

<div class="separator"></div>

<div>{$program|escape|nl2br}</div>

{include file="common/footer.tpl"}
